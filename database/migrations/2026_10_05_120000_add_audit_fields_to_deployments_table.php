<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('site_id')->constrained('users')->nullOnDelete();
            $table->string('trigger')->nullable()->after('user_id');
            $table->foreignId('rolled_back_by_id')->nullable()->after('active')->constrained('users')->nullOnDelete();
            $table->timestamp('rolled_back_at')->nullable()->after('rolled_back_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rolled_back_by_id');
            $table->dropColumn('rolled_back_at');
            $table->dropColumn('trigger');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
