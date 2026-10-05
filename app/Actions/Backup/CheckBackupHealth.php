<?php

namespace App\Actions\Backup;

use App\Enums\BackupFileStatus;
use App\Facades\Notifier;
use App\Models\Backup;
use App\Notifications\BackupOverdue;
use App\Notifications\BackupRecovered;
use Carbon\Carbon;
use Cron\CronExpression;
use RuntimeException;

class CheckBackupHealth
{
    /**
     * How much earlier than the scheduled time a file may be created and still count
     * for that run, to absorb queue and clock skew.
     */
    private const int SCHEDULE_TOLERANCE_MINUTES = 5;

    /**
     * Alert once when a backup becomes unhealthy and once more when it recovers.
     * A failed latest run only marks the backup, because the run job already
     * notifies about failures; an overdue backup sends its own alert.
     */
    public function check(Backup $backup): void
    {
        $failed = $this->latestRunFailed($backup);
        $overdue = ! $failed && $this->isOverdue($backup);

        if (! $failed && ! $overdue) {
            $this->markRecovered($backup);

            return;
        }

        if ($backup->health_alerted_at !== null) {
            return;
        }

        if ($overdue) {
            Notifier::send($backup->server, new BackupOverdue($backup));
        }

        $this->saveAlertedAt($backup, now());
    }

    private function markRecovered(Backup $backup): void
    {
        if ($backup->health_alerted_at === null) {
            return;
        }

        Notifier::send($backup->server, new BackupRecovered($backup));
        $this->saveAlertedAt($backup, null);
    }

    /**
     * Saved without touching updated_at, which marks when the backup was last
     * changed and is used as the start of the expected schedule.
     */
    private function saveAlertedAt(Backup $backup, ?Carbon $alertedAt): void
    {
        $backup->health_alerted_at = $alertedAt;
        $backup->timestamps = false;
        $backup->saveQuietly();
        $backup->timestamps = true;
    }

    /**
     * Only finished runs count: a file that is still being created is neither a
     * success nor a failure yet, and restore/delete states mean the run succeeded.
     */
    private function latestRunFailed(Backup $backup): bool
    {
        $latest = $backup->files()
            ->where('status', '!=', BackupFileStatus::CREATING)
            ->latest('id')
            ->first();

        return $latest?->status === BackupFileStatus::FAILED;
    }

    /**
     * A backup is overdue when its last scheduled run, allowing one run timeout to
     * finish, has no file that was not a failure. Runs scheduled before the backup
     * was created or last changed (enabled again, new interval) are not expected.
     */
    private function isOverdue(Backup $backup): bool
    {
        if (! CronExpression::isValidExpression($backup->interval)) {
            return false;
        }

        $grace = (int) config('core.backup_run_timeout');

        try {
            $expectedAt = Carbon::instance(
                (new CronExpression($backup->interval))->getPreviousRunDate(now()->subSeconds($grace), 0, true, config('app.timezone'))
            );
        } catch (RuntimeException) {
            return false;
        }

        if ($backup->updated_at->greaterThan($expectedAt) || $backup->created_at->greaterThan($expectedAt)) {
            return false;
        }

        return ! $backup->files()
            ->where('status', '!=', BackupFileStatus::FAILED)
            ->where('created_at', '>=', $expectedAt->copy()->subMinutes(self::SCHEDULE_TOLERANCE_MINUTES))
            ->exists();
    }
}
