<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_login_at')->index();
            $table->timestamp('deactivated_at')->nullable()->after('expires_at');
            $table->text('deactivation_reason')->nullable()->after('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['expires_at', 'deactivated_at', 'deactivation_reason']);
        });
    }
};
