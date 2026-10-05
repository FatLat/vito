<?php

use App\Enums\BackupFileStatus;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\NotificationChannel;
use App\Models\StorageProvider;
use App\Notifications\BackupOverdue;
use App\Notifications\BackupRecovered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow('2026-10-05 12:30:00');
    config(['core.backup_run_timeout' => 3600]);
    $this->channel = NotificationChannel::factory()->create();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup(array $attributes = []): Backup
{
    $database = Database::factory()->create(['server_id' => test()->server->id]);
    $storage = StorageProvider::factory()->dropbox()->create(['user_id' => test()->user->id]);

    return Backup::factory()->create(array_merge([
        'server_id' => test()->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
        'interval' => '0 * * * *',
        'keep_backups' => 10,
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ], $attributes));
}

function vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile(Backup $backup, BackupFileStatus $status, Carbon $createdAt): BackupFile
{
    return BackupFile::factory()->create([
        'backup_id' => $backup->id,
        'status' => $status,
        'created_at' => $createdAt,
    ]);
}

test('a backup with a recent successful file is healthy', function () {
    $backup = vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup();
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::CREATED, now()->subHour()->startOfHour());

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertNothingSent();
    expect($backup->refresh()->health_alerted_at)->toBeNull();
});

test('an overdue backup alerts once', function () {
    $backup = vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup();
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::CREATED, now()->subHours(5));

    $this->artisan('backups:check-health')->assertSuccessful();
    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertSentToTimes($this->channel, BackupOverdue::class, 1);
    expect($backup->refresh()->health_alerted_at)->not->toBeNull();
});

test('a newly created backup is not overdue', function () {
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup(['created_at' => now()->subMinutes(10)]);

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertNothingSent();
});

test('a failed backup is marked without a second failure alert', function () {
    $backup = vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup();
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::CREATED, now()->subHours(2));
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::FAILED, now()->subHour()->startOfHour());

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertNothingSent();
    expect($backup->refresh()->health_alerted_at)->not->toBeNull();
});

test('a backup that runs again after an alert sends a recovered notice', function () {
    $backup = vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup();
    $backup->health_alerted_at = now()->subHours(3);
    $backup->save();
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::CREATED, now()->subHour()->startOfHour());

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertSentToTimes($this->channel, BackupRecovered::class, 1);
    expect($backup->refresh()->health_alerted_at)->toBeNull();
});

test('disabled backups are skipped and their alert flag is cleared', function () {
    $backup = vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup(['health_alerted_at' => now()->subHour()]);
    $backup->enabled = false;
    $backup->saveQuietly();

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertNothingSent();
    expect($backup->refresh()->health_alerted_at)->toBeNull();
});

test('a failed backup that runs again sends a recovered notice', function () {
    $backup = vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup();
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::FAILED, now()->subHours(2));

    $this->artisan('backups:check-health')->assertSuccessful();
    Notification::assertNothingSent();

    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::CREATED, now()->subHour()->startOfHour());
    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertSentToTimes($this->channel, BackupRecovered::class, 1);
});

test('a backup changed after its last expected run is not overdue', function () {
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup(['updated_at' => now()->subMinutes(30)]);

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertNothingSent();
});

test('a run still in progress counts as having run', function () {
    $backup = vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup();
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateFile($backup, BackupFileStatus::CREATING, now()->subHour()->startOfHour());

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertNothingSent();
});

test('an impossible cron expression does not stop other backups from being checked', function () {
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup(['interval' => '0 0 31 2 *']);
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup();

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertSentToTimes($this->channel, BackupOverdue::class, 1);
});

test('an alerted backup that is still overdue stays quiet', function () {
    vitoPestUnitCommandsCheckBackupHealthCommandTestCreateBackup(['health_alerted_at' => now()->subHour()]);

    $this->artisan('backups:check-health')->assertSuccessful();

    Notification::assertNothingSent();
});
