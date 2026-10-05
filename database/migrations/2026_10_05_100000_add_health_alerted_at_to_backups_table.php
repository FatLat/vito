<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table): void {
            $table->timestamp('health_alerted_at')->nullable()->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table): void {
            $table->dropColumn('health_alerted_at');
        });
    }
};
