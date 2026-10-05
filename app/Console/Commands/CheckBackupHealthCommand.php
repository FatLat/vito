<?php

namespace App\Console\Commands;

use App\Actions\Backup\CheckBackupHealth;
use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckBackupHealthCommand extends Command
{
    protected $signature = 'backups:check-health';

    protected $description = 'Alert when backups fail or stop running, and when they recover';

    public function handle(): void
    {
        Backup::query()
            ->where('enabled', false)
            ->whereNotNull('health_alerted_at')
            ->toBase()
            ->update(['health_alerted_at' => null]);

        Backup::query()
            ->where('enabled', true)
            ->whereNull('status')
            ->whereHas('server')
            ->with('server')
            ->chunkById(100, function ($backups): void {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    try {
                        app(CheckBackupHealth::class)->check($backup);
                    } catch (Throwable $e) {
                        Log::warning('Failed to check backup health', [
                            'backup_id' => $backup->id,
                            'server_id' => $backup->server_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
