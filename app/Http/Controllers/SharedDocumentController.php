<?php

namespace App\Http\Controllers;

use App\Models\SharedDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SharedDocumentController extends Controller
{
    private const ALLOWED_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,html,htm,txt,csv,zip,jpg,jpeg,png,webp';

    public function index(Request $request)
    {
        Gate::authorize('viewAny', SharedDocument::class);

        $user = $request->user();
        $baseQuery = SharedDocument::query()->accessibleTo($user);
        $documents = (clone $baseQuery)
            ->with('owner')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->input('q'));
                $query->where(function ($match) use ($search) {
                    $match->where('title', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('extension'), fn ($query) => $query->where('extension', $request->input('extension')))
            ->when($request->filled('folder'), fn ($query) => $query->where('folder', $request->input('folder')))
            ->when($request->input('scope') === 'mine', fn ($query) => $query->where('owner_id', $user->id))
            ->when($request->input('scope') === 'shared', fn ($query) => $query
                ->where('visibility', SharedDocument::VISIBILITY_TEACHERS)
                ->where('owner_id', '!=', $user->id))
            ->latest()
            ->paginate(18)
            ->withQueryString();

        $folders = (clone $baseQuery)
            ->whereNotNull('folder')
            ->where('folder', '!=', '')
            ->distinct()
            ->orderBy('folder')
            ->pluck('folder');

        $extensions = (clone $baseQuery)
            ->whereNotNull('extension')
            ->distinct()
            ->orderBy('extension')
            ->pluck('extension');

        return view('shared-documents.index', [
            'documents' => $documents,
            'folders' => $folders,
            'extensions' => $extensions,
            'totalDocuments' => (clone $baseQuery)->count(),
            'myDocuments' => (clone $baseQuery)->where('owner_id', $user->id)->count(),
            'sharedDocuments' => (clone $baseQuery)
                ->where('visibility', SharedDocument::VISIBILITY_TEACHERS)
                ->where('owner_id', '!=', $user->id)
                ->count(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', SharedDocument::class);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:20480', 'mimes:'.self::ALLOWED_EXTENSIONS],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder' => ['nullable', 'string', 'max:100'],
            'visibility' => ['required', 'in:private,teachers'],
        ], [
            'files.required' => 'Vui lòng chọn ít nhất một tài liệu.',
            'files.max' => 'Mỗi lần chỉ được tải tối đa 10 tài liệu.',
            'files.*.max' => 'Mỗi tài liệu không được vượt quá 20 MB.',
            'files.*.mimes' => 'Định dạng tài liệu chưa được hỗ trợ.',
        ]);

        $disk = (string) config('filesystems.shared_document_disk', 'r2');
        abort_unless(config("filesystems.disks.{$disk}"), 500, 'Disk lưu trữ tài liệu chưa được cấu hình.');

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $validated, $disk, &$storedPaths) {
                foreach ($request->file('files', []) as $file) {
                    $document = $this->storeFile($file, $request->user()->id, $validated, $disk);
                    $storedPaths[] = $document->file_path;
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }

        return redirect()->route('shared-documents.index')
            ->with('success', 'Đã tải '.count($storedPaths).' tài liệu lên kho chung.');
    }

    public function update(Request $request, SharedDocument $sharedDocument)
    {
        Gate::authorize('update', $sharedDocument);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder' => ['nullable', 'string', 'max:100'],
            'visibility' => ['required', 'in:private,teachers'],
        ]);

        $sharedDocument->update([
            ...$validated,
            'folder' => $this->normalizeFolder($validated['folder'] ?? null),
        ]);

        return back()->with('success', 'Đã cập nhật thông tin tài liệu.');
    }

    public function download(SharedDocument $sharedDocument)
    {
        Gate::authorize('download', $sharedDocument);

        $disk = Storage::disk($sharedDocument->disk);
        abort_unless($disk->exists($sharedDocument->file_path), 404, 'Không tìm thấy tài liệu trên kho lưu trữ.');

        return $disk->download($sharedDocument->file_path, $sharedDocument->original_name);
    }

    public function destroy(SharedDocument $sharedDocument)
    {
        Gate::authorize('delete', $sharedDocument);

        $disk = Storage::disk($sharedDocument->disk);
        if ($disk->exists($sharedDocument->file_path)) {
            abort_unless($disk->delete($sharedDocument->file_path), 500, 'Không thể xóa tài liệu khỏi kho lưu trữ.');
        }

        $sharedDocument->delete();

        return back()->with('success', 'Đã xóa tài liệu.');
    }

    private function storeFile(UploadedFile $file, int $ownerId, array $data, string $disk): SharedDocument
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid().($extension ? ".{$extension}" : '');
        $directory = 'shared-documents/'.$ownerId.'/'.now()->format('Y/m');
        $storedPath = $file->storeAs($directory, $storedName, $disk);

        if (! $storedPath) {
            throw new \RuntimeException('Không thể tải tài liệu lên kho lưu trữ.');
        }

        $originalName = basename($file->getClientOriginalName());

        try {
            return SharedDocument::create([
                'owner_id' => $ownerId,
                'title' => pathinfo($originalName, PATHINFO_FILENAME),
                'description' => $data['description'] ?? null,
                'folder' => $this->normalizeFolder($data['folder'] ?? null),
                'visibility' => $data['visibility'],
                'disk' => $disk,
                'file_path' => $storedPath,
                'original_name' => $originalName,
                'mime_type' => $file->getClientMimeType(),
                'extension' => $extension ?: null,
                'file_size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);
            throw $exception;
        }
    }

    private function normalizeFolder(?string $folder): ?string
    {
        $folder = trim((string) $folder);

        return $folder === '' ? null : preg_replace('/\s+/', ' ', $folder);
    }
}
