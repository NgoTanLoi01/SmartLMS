<?php

use App\Support\AuditHash;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable()->after('user_id')->index();
            $table->string('actor_name')->nullable()->after('actor_id');
            $table->string('actor_email')->nullable()->after('actor_name');
            $table->unsignedBigInteger('chain_position')->nullable()->after('user_agent')->unique();
            $table->char('previous_hash', 64)->nullable()->after('chain_position');
            $table->char('entry_hash', 64)->nullable()->after('previous_hash')->unique();
            $table->unsignedTinyInteger('integrity_version')->default(AuditHash::VERSION)->after('entry_hash');
            $table->timestamp('archived_at')->nullable()->after('integrity_version')->index();
        });

        Schema::create('audit_log_chain_states', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_position')->default(0);
            $table->unsignedBigInteger('last_audit_log_id')->nullable();
            $table->char('last_hash', 64)->nullable();
            $table->timestamps();
        });

        $position = 0;
        $previousHash = null;
        $lastAuditLogId = null;

        DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->orderBy('audit_logs.id')
            ->select('audit_logs.*', 'users.name as resolved_actor_name', 'users.email as resolved_actor_email')
            ->chunkById(250, function ($logs) use (&$position, &$previousHash, &$lastAuditLogId): void {
                foreach ($logs as $log) {
                    $position++;
                    $attributes = (array) $log;
                    $attributes['actor_id'] = $log->user_id;
                    $attributes['actor_name'] = $log->resolved_actor_name;
                    $attributes['actor_email'] = $log->resolved_actor_email;
                    $attributes['chain_position'] = $position;
                    $attributes['previous_hash'] = $previousHash;
                    $attributes['integrity_version'] = AuditHash::VERSION;
                    $attributes['entry_hash'] = AuditHash::digest($attributes);

                    DB::table('audit_logs')->where('id', $log->id)->update([
                        'actor_id' => $attributes['actor_id'],
                        'actor_name' => $attributes['actor_name'],
                        'actor_email' => $attributes['actor_email'],
                        'chain_position' => $position,
                        'previous_hash' => $previousHash,
                        'entry_hash' => $attributes['entry_hash'],
                        'integrity_version' => AuditHash::VERSION,
                    ]);

                    $previousHash = $attributes['entry_hash'];
                    $lastAuditLogId = $log->id;
                }
            }, 'audit_logs.id', 'id');

        DB::table('audit_log_chain_states')->insert([
            'id' => 1,
            'last_position' => $position,
            'last_audit_log_id' => $lastAuditLogId,
            'last_hash' => $previousHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_chain_states');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_id']);
            $table->dropUnique(['chain_position']);
            $table->dropUnique(['entry_hash']);
            $table->dropIndex(['archived_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'actor_id',
                'actor_name',
                'actor_email',
                'chain_position',
                'previous_hash',
                'entry_hash',
                'integrity_version',
                'archived_at',
            ]);
        });
    }
};
