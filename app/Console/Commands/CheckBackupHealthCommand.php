<?php

namespace App\Console\Commands;

use App\Actions\Backup\CheckBackupHealth;
use App\Models\Backup;
use Illuminate\Console\Command;

class CheckBackupHealthCommand extends Command
{
    protected $signature = 'backups:check-health';

    protected $description = 'Alert when backups fail or stop running, and when they recover';

    public function handle(): void
    {
        Backup::query()
            ->where('enabled', true)
            ->whereNull('status')
            ->whereHas('server')
            ->with('server')
            ->chunkById(100, function ($backups): void {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    app(CheckBackupHealth::class)->check($backup);
                }
            });
    }
}
