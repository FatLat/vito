<?php

use App\Actions\Site\SetUpLaravelApp;
use App\Enums\CronjobStatus;
use App\Enums\SiteStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Site;
use App\Models\SourceControl;
use App\SiteTypes\Laravel;
use App\SourceControlProviders\Github;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        'https://api.github.com/repos/*' => Http::response([], 201),
    ]);

    $this->sourceControl = SourceControl::factory()->create([
        'provider' => Github::id(),
        'user_id' => $this->user->id,
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function vitoPestFeatureLaravelSetupTestInput(array $overrides = []): array
{
    return array_merge([
        'type' => Laravel::id(),
        'domain' => 'plaka.example.com',
        'php_version' => '8.2',
        'web_directory' => 'panel/public',
        'repository' => 'test/test',
        'branch' => 'release',
        'composer' => false,
        'node_version' => 'none',
        'user' => 'plaka',
        'source_control' => test()->sourceControl->id,
        'production_env' => true,
        'setup_database' => true,
        'setup_queue_worker' => true,
        'setup_scheduler' => true,
    ], $overrides);
}

test('laravel site with every setup option is ready to run', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), vitoPestFeatureLaravelSetupTestInput())
        ->assertSessionDoesntHaveErrors();

    /** @var Site $site */
    $site = Site::query()->where('domain', 'plaka.example.com')->firstOrFail();
    $appPath = '/home/plaka/plaka.example.com/panel';

    expect($site->status)->toBe(SiteStatus::READY);
    expect($site->type_data['env_path'])->toBe($appPath.'/.env');

    $this->assertDatabaseHas('databases', ['server_id' => $this->server->id, 'name' => 'plaka']);
    $databaseUser = DatabaseUser::query()->where('server_id', $this->server->id)->where('username', 'plaka')->firstOrFail();
    expect($databaseUser->databases)->toBe(['plaka']);

    $this->assertDatabaseHas('workers', [
        'site_id' => $site->id,
        'name' => 'queue',
        'user' => 'plaka',
        'command' => 'php panel/artisan queue:work --sleep=3 --tries=3 --max-time=3600',
    ]);
    $this->assertDatabaseHas('cron_jobs', [
        'site_id' => $site->id,
        'user' => 'plaka',
        'frequency' => '* * * * *',
        'command' => "cd {$appPath} && php artisan schedule:run >> /dev/null 2>&1",
    ]);

    SSH::assertExecutedContains("cp -- '{$appPath}/.env.example' '{$appPath}/.env'");

    $site->createDefaultDeploymentScript();
    expect($site->refresh()->deploymentScript->content)->toContain("git pull origin \$BRANCH\n\ncd panel");
});

test('setup writes production values and database credentials to env', function () {
    $ssh = SSH::fake("APP_NAME=Laravel\nAPP_ENV=local\nAPP_KEY=\n# keep me\nDB_CONNECTION=sqlite");
    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), vitoPestFeatureLaravelSetupTestInput([
        'setup_queue_worker' => false,
        'setup_scheduler' => false,
    ]))->assertSessionDoesntHaveErrors();

    $password = DatabaseUser::query()->where('username', 'plaka')->firstOrFail()->password;
    $env = $ssh->getUploadedContent();

    expect($env)
        ->toContain('APP_NAME=Laravel')
        ->toContain('# keep me')
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('APP_URL=https://plaka.example.com')
        ->toMatch('/^APP_KEY=base64:\S+$/m')
        ->toContain('DB_CONNECTION=mysql')
        ->toContain('DB_DATABASE=plaka')
        ->toContain('DB_USERNAME=plaka')
        ->toContain('DB_PASSWORD='.$password)
        ->not->toContain('DB_CONNECTION=sqlite');
});

test('laravel site without setup options does not create extras', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), vitoPestFeatureLaravelSetupTestInput([
        'web_directory' => 'public',
        'production_env' => false,
        'setup_database' => false,
        'setup_queue_worker' => false,
        'setup_scheduler' => false,
    ]))->assertSessionDoesntHaveErrors();

    $site = Site::query()->where('domain', 'plaka.example.com')->firstOrFail();

    expect($site->type_data['env_path'] ?? null)->toBeNull();
    expect(Database::query()->where('name', 'plaka')->exists())->toBeFalse();
    expect($site->workers()->count())->toBe(0);
    expect($site->cronJobs()->count())->toBe(0);
});

test('database setup is rejected when the name is taken', function () {
    SSH::fake();
    $this->actingAs($this->user);

    Database::factory()->create(['server_id' => $this->server->id, 'name' => 'plaka']);

    $this->post(route('sites.store', ['server' => $this->server]), vitoPestFeatureLaravelSetupTestInput())
        ->assertSessionHasErrors('setup_database');
});

test('app directory is derived from the web directory', function (string $webDirectory, string $appDirectory) {
    $site = new Site(['path' => '/home/u/site.com', 'web_directory' => $webDirectory]);

    expect(SetUpLaravelApp::appDirectory($site))->toBe($appDirectory);
})->with([
    ['public', ''],
    ['', ''],
    ['panel/public', 'panel'],
    ['apps/api/public', 'apps/api'],
    ['dist', ''],
]);

test('a retried setup reuses the existing database user password', function () {
    $ssh = SSH::fake("APP_KEY=base64:existing\nDB_PASSWORD=");
    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), vitoPestFeatureLaravelSetupTestInput([
        'setup_database' => false,
        'setup_queue_worker' => false,
        'setup_scheduler' => false,
    ]))->assertSessionDoesntHaveErrors();

    $site = Site::query()->where('domain', 'plaka.example.com')->firstOrFail();
    Database::factory()->create(['server_id' => $this->server->id, 'name' => 'plaka']);
    DatabaseUser::factory()->create([
        'server_id' => $this->server->id,
        'username' => 'plaka',
        'password' => 'stored-password',
        'databases' => ['plaka'],
    ]);
    $site->jsonUpdate('type_data', 'setup_database', true);

    app(SetUpLaravelApp::class)->setUp($site->refresh());

    expect(DatabaseUser::query()->where('username', 'plaka')->count())->toBe(1);
    expect($ssh->getUploadedContent())
        ->toContain('DB_PASSWORD=stored-password')
        ->toContain('APP_KEY=base64:existing');
});

test('a retried setup replaces a scheduler that never reached the crontab', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('sites.store', ['server' => $this->server]), vitoPestFeatureLaravelSetupTestInput([
        'production_env' => false,
        'setup_database' => false,
        'setup_queue_worker' => false,
    ]))->assertSessionDoesntHaveErrors();

    $site = Site::query()->where('domain', 'plaka.example.com')->firstOrFail();
    $cronJob = $site->cronJobs()->firstOrFail();
    $cronJob->update(['status' => CronjobStatus::CREATING]);

    app(SetUpLaravelApp::class)->setUp($site->refresh());

    expect($site->cronJobs()->count())->toBe(1);
    expect($site->cronJobs()->first()->status)->toBe(CronjobStatus::READY);
});
