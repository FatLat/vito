<?php

namespace App\Actions\Backup;

use App\Enums\BackupFileStatus;
use App\Facades\Notifier;
use App\Models\Backup;
use App\Notifications\BackupOverdue;
use App\Notifications\BackupRecovered;
use Carbon\Carbon;
use Cron\CronExpression;

class CheckBackupHealth
{
    /**
     * Alert once when a backup becomes unhealthy (its latest run failed, or a scheduled
     * run never produced a file) and once more when it recovers. Failures are already
     * notified by the run job, so only overdue backups send an alert here.
     */
    public function check(Backup $backup): void
    {
        $failed = $this->latestRunFailed($backup);
        $overdue = $this->isOverdue($backup);

        if ($failed || $overdue) {
            if ($backup->health_alerted_at === null) {
                if (! $failed) {
                    Notifier::send($backup->server, new BackupOverdue($backup));
                }
                $backup->health_alerted_at = now();
                $backup->save();
            }

            return;
        }

        if ($backup->health_alerted_at !== null) {
            Notifier::send($backup->server, new BackupRecovered($backup));
            $backup->health_alerted_at = null;
            $backup->save();
        }
    }

    private function latestRunFailed(Backup $backup): bool
    {
        $latest = $backup->files()
            ->where('status', '!=', BackupFileStatus::CREATING)
            ->latest('id')
            ->first();

        return $latest?->status === BackupFileStatus::FAILED;
    }

    /**
     * A backup is overdue when its last scheduled run (allowing one run timeout to
     * finish) has no file created at or after it.
     */
    private function isOverdue(Backup $backup): bool
    {
        if (! CronExpression::isValidExpression($backup->interval)) {
            return false;
        }

        $grace = (int) config('core.backup_run_timeout');
        $expectedAt = Carbon::instance(
            (new CronExpression($backup->interval))->getPreviousRunDate(now()->subSeconds($grace), 0, true, config('app.timezone'))
        );

        if ($backup->created_at === null || $backup->created_at->greaterThan($expectedAt)) {
            return false;
        }

        return ! $backup->files()
            ->whereNotIn('status', [BackupFileStatus::CREATING, BackupFileStatus::FAILED])
            ->where('created_at', '>=', $expectedAt->copy()->subMinutes(5))
            ->exists();
    }
}
