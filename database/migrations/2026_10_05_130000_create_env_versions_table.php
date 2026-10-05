<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('env_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->longText('content');
            $table->timestamps();

            $table->index(['site_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('env_versions');
    }
};
