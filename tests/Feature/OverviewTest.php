<?php

use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Project;
use App\Models\Server;
use App\Models\Ssl;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function overviewBackup(Server $server, StorageProvider $storage, string $path): Backup
{
    return Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $server->id,
        'storage_id' => $storage->id,
        'path' => $path,
    ]);
}

test('home redirects to the overview', function () {
    $this->actingAs($this->user);

    $this->get(route('home'))->assertRedirect(route('overview'));
});

test('overview shows counts, recent servers and sites', function () {
    $this->actingAs($this->user);

    $this->get(route('overview'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('overview/index')
            ->where('overview.counts.servers', 1)
            ->where('overview.counts.sites', 1)
            ->where('overview.servers.0.id', $this->server->id)
            ->where('overview.sites.0.domain', $this->site->domain)
            ->where('overview.sites.0.server_name', $this->server->name)
            ->where('projectCounts.servers', 1)
            ->where('projectCounts.sites', 1));
});

test('overview lists overdue and failed backups only', function () {
    $storage = StorageProvider::factory()->create(['user_id' => $this->user->id]);
    $overdue = overviewBackup($this->server, $storage, '/home/vito/overdue');
    $overdue->forceFill(['health_alerted_at' => now()])->save();
    $failed = overviewBackup($this->server, $storage, '/home/vito/failed');
    BackupFile::factory()->create(['backup_id' => $failed->id, 'status' => BackupFileStatus::FAILED]);
    $healthy = overviewBackup($this->server, $storage, '/home/vito/healthy');
    BackupFile::factory()->create(['backup_id' => $healthy->id, 'status' => BackupFileStatus::CREATED]);
    $disabled = overviewBackup($this->server, $storage, '/home/vito/disabled');
    $disabled->forceFill(['enabled' => false, 'health_alerted_at' => now()])->save();
    $this->actingAs($this->user);

    $this->get(route('overview'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('overview.backup_problems', 2)
            ->where('overview.backup_problems.0.target', '/home/vito/overdue')
            ->where('overview.backup_problems.0.problem', 'overdue')
            ->where('overview.backup_problems.1.target', '/home/vito/failed')
            ->where('overview.backup_problems.1.problem', 'failed')
            ->where('overview.counts.backups', 4));
});

test('overview lists ssl certificates expiring within two weeks', function () {
    Ssl::factory()->create(['site_id' => $this->site->id, 'expires_at' => now()->addDays(3)]);
    Ssl::factory()->create(['site_id' => $this->site->id, 'expires_at' => now()->addDays(60)]);
    $this->actingAs($this->user);

    $this->get(route('overview'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('overview.expiring_ssls', 1)
            ->where('overview.expiring_ssls.0.domain', $this->site->domain)
            ->where('overview.expiring_ssls.0.server_id', $this->server->id));
});

test('overview only shows the current project', function () {
    $other = Project::factory()->create();
    Server::factory()->create(['project_id' => $other->id]);
    $this->actingAs($this->user);

    $this->get(route('overview'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('overview.counts.servers', 1));
});

test('rows per page come from the query string', function (int $requested, int $expected) {
    $this->actingAs($this->user);

    $this->get(route('servers', ['per_page' => $requested]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('servers.meta.per_page', $expected)
            ->where('servers.meta.total', 1));
})->with([
    'allowed' => [25, 25],
    'not allowed' => [1000, 10],
]);

test('rows per page ignores array input and does not leak into later requests', function () {
    $this->actingAs($this->user);

    $this->get(route('servers').'?per_page[]=25')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('servers.meta.per_page', 10));

    $this->get(route('servers', ['per_page' => 50]))->assertOk();

    expect(config('web.pagination_size'))->toBe(10);
});
