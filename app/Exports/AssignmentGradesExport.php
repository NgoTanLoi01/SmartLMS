<?php

namespace App\Exports;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssignmentGradesExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly Assignments $assignment) {}

    public function collection(): Collection
    {
        return AssignmentSubmission::query()
            ->with('user:id,name,email,student_code')
            ->where('assignment_id', $this->assignment->id)
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID bài nộp',
            'Mã học viên',
            'Họ và tên',
            'Email',
            'Điểm',
            'Trạng thái',
            'Nhận xét',
            'Nộp lúc',
        ];
    }

    public function map($submission): array
    {
        return [
            (int) $submission->id,
            $this->safeText($submission->user?->student_code),
            $this->safeText($submission->user?->name),
            $this->safeText($submission->user?->email),
            $submission->grade !== null ? (float) $submission->grade : null,
            $submission->isGradePublished() ? 'Công bố' : 'Nháp',
            $this->safeText($submission->feedback),
            $submission->formatSubmittedAt('Y-m-d H:i:s'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => '0.00',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 18,
            'C' => 28,
            'D' => 32,
            'E' => 12,
            'F' => 15,
            'G' => 52,
            'H' => 21,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H1');
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('G:G')->getAlignment()->setWrapText(true);

        return [];
    }

    private function safeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = (string) $value;

        return preg_match('/^[=+\-@]/', ltrim($text)) === 1 ? "'{$text}" : $text;
    }
}
