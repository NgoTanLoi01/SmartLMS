<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\AuditHash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AuditIntegrityService
{
    private const STATE_ID = 1;

    public function append(array $attributes): AuditLog
    {
        $now = Carbon::now()->startOfSecond();
        $attributes['created_at'] = $attributes['created_at'] ?? $now;
        $attributes['updated_at'] = $attributes['updated_at'] ?? $now;

        if (! $this->supportsIntegrityChain()) {
            $availableColumns = array_flip(Schema::getColumnListing('audit_logs'));

            return AuditLog::query()->create(array_intersect_key($attributes, $availableColumns));
        }

        return DB::transaction(function () use ($attributes) {
            DB::table('audit_log_chain_states')->insertOrIgnore([
                'id' => self::STATE_ID,
                'last_position' => 0,
                'last_audit_log_id' => null,
                'last_hash' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $state = DB::table('audit_log_chain_states')
                ->where('id', self::STATE_ID)
                ->lockForUpdate()
                ->first();
            if (! $state) {
                throw new RuntimeException('Không thể khóa trạng thái chuỗi audit log.');
            }

            $latest = AuditLog::query()
                ->whereNotNull('chain_position')
                ->orderByDesc('chain_position')
                ->first(['id', 'chain_position', 'entry_hash']);
            if ((int) $state->last_position !== (int) ($latest?->chain_position ?? 0)
                || (string) ($state->last_hash ?? '') !== (string) ($latest?->entry_hash ?? '')) {
                throw new RuntimeException('Trạng thái chuỗi audit log không khớp với bản ghi cuối cùng.');
            }

            $attributes['chain_position'] = (int) $state->last_position + 1;
            $attributes['previous_hash'] = $state->last_hash;
            $attributes['integrity_version'] = AuditHash::VERSION;
            $attributes['entry_hash'] = AuditHash::digest($attributes);

            $auditLog = AuditLog::query()->create($attributes);

            DB::table('audit_log_chain_states')->where('id', self::STATE_ID)->update([
                'last_position' => $auditLog->chain_position,
                'last_audit_log_id' => $auditLog->id,
                'last_hash' => $auditLog->entry_hash,
                'updated_at' => now(),
            ]);

            return $auditLog;
        }, 3);
    }

    public function verify(): array
    {
        if (! $this->supportsIntegrityChain()) {
            return $this->failed('Cấu trúc chuỗi toàn vẹn audit log chưa được cài đặt.');
        }

        $expectedPosition = 1;
        $previousHash = null;
        $checked = 0;

        foreach (AuditLog::query()->orderBy('chain_position')->orderBy('id')->cursor() as $log) {
            if ($log->chain_position === null || blank($log->entry_hash)) {
                return $this->failed("Bản ghi #{$log->id} chưa có chữ ký toàn vẹn.", $checked, $log->id);
            }
            if ((int) $log->chain_position !== $expectedPosition) {
                return $this->failed("Chuỗi bị thiếu hoặc sai thứ tự tại bản ghi #{$log->id}.", $checked, $log->id);
            }
            if ((string) ($log->previous_hash ?? '') !== (string) ($previousHash ?? '')) {
                return $this->failed("Liên kết hash trước không hợp lệ tại bản ghi #{$log->id}.", $checked, $log->id);
            }

            $expectedHash = AuditHash::digest($log->getAttributes());
            if (! hash_equals($expectedHash, (string) $log->entry_hash)) {
                return $this->failed("Nội dung hoặc chữ ký của bản ghi #{$log->id} đã thay đổi.", $checked, $log->id);
            }

            $previousHash = $log->entry_hash;
            $expectedPosition++;
            $checked++;
        }

        $state = DB::table('audit_log_chain_states')->where('id', self::STATE_ID)->first();
        if (! $state
            || (int) $state->last_position !== $checked
            || (string) ($state->last_hash ?? '') !== (string) ($previousHash ?? '')) {
            return $this->failed('Trạng thái cuối chuỗi không khớp với dữ liệu audit log.', $checked);
        }

        return [
            'valid' => true,
            'checked' => $checked,
            'last_hash' => $previousHash,
            'failed_id' => null,
            'message' => "Chuỗi audit log hợp lệ ({$checked} bản ghi).",
        ];
    }

    public function archiveExpired(int $retentionDays, bool $dryRun = false): int
    {
        $retentionDays = max(1, $retentionDays);
        $cutoff = now()->subDays($retentionDays)->startOfDay();
        $query = AuditLog::query()->whereNull('archived_at')->where('created_at', '<', $cutoff);
        $count = (clone $query)->count();

        if (! $dryRun && $count > 0) {
            DB::table('audit_logs')
                ->whereNull('archived_at')
                ->where('created_at', '<', $cutoff)
                ->update(['archived_at' => now()]);
        }

        return $count;
    }

    public function supportsIntegrityChain(): bool
    {
        return Schema::hasTable('audit_logs')
            && Schema::hasTable('audit_log_chain_states')
            && Schema::hasColumns('audit_logs', [
                'actor_id', 'chain_position', 'previous_hash', 'entry_hash', 'integrity_version', 'archived_at',
            ]);
    }

    private function failed(string $message, int $checked = 0, ?int $failedId = null): array
    {
        return [
            'valid' => false,
            'checked' => $checked,
            'last_hash' => null,
            'failed_id' => $failedId,
            'message' => $message,
        ];
    }
}
