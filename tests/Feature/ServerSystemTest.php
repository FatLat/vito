<?php

use App\Actions\Server\System\ClearLogFile;
use App\Enums\ServerStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Models\ServerLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see system page', function () {
    $this->actingAs($this->user);

    $this->get(route('server-system', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-system/index'));
});

test('system overview is parsed', function () {
    SSH::fake(implode("\n", [
        "info\thostname\tvito-test",
        "info\tos\tUbuntu 24.04.1 LTS",
        "info\tkernel\t6.8.0-45-generic",
        "disk\t/\t/dev/root\t31000000000\t9000000000\t22000000000",
        "directory\t/var/lib\t2000000000",
        "log\t/var/log/syslog\t52428800",
        'noise without tabs',
    ]));
    $this->actingAs($this->user);

    $this->get(route('server-system.json', $this->server))
        ->assertOk()
        ->assertJsonPath('info.hostname', 'vito-test')
        ->assertJsonPath('info.os', 'Ubuntu 24.04.1 LTS')
        ->assertJsonPath('disks.0.mount', '/')
        ->assertJsonPath('disks.0.used', 9000000000)
        ->assertJsonPath('directories.0.path', '/var/lib')
        ->assertJsonPath('logs.0.size', 52428800);
});

test('system overview needs a ready server', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::DISCONNECTED]);
    $this->actingAs($this->user);

    $this->get(route('server-system.json', $this->server))->assertStatus(422);

    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('read only user can see the system overview', function () {
    SSH::fake("info\thostname\tvito-test");
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->actingAs($this->user);

    $this->get(route('server-system.json', $this->server))->assertOk();
});

test('clear a log file', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('server-system.logs.clear', $this->server), ['path' => '/var/log/nginx/access.log'])
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('realpath -e -- "$FILE_PATH"');
    SSH::assertExecutedContains('/var/log/nginx/access.log');
    $this->assertDatabaseHas('server_logs', ['server_id' => $this->server->id, 'type' => 'clear-log']);
});

test('only files under /var/log can be cleared', function (string $path) {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('server-system.logs.clear', $this->server), ['path' => $path])
        ->assertSessionHasErrors('path');

    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([
    '/etc/passwd',
    '/var/log/../../etc/shadow',
    '/var/log/syslog; rm -rf /',
    '/var/log/$(whoami)',
]);

test('processes are parsed', function () {
    SSH::fake(implode("\n", [
        '  812 www-data  12.5  3.1  64000  3600 php-fpm: pool www',
        '    1 root       0.0  0.2  12000 90000 /sbin/init',
    ]));
    $this->actingAs($this->user);

    $this->get(route('server-system.processes', $this->server))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-system/processes'));

    $this->get(route('server-system.processes.json', $this->server))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.pid', 812)
        ->assertJsonPath('0.user', 'www-data')
        ->assertJsonPath('0.rss', 64000 * 1024)
        ->assertJsonPath('0.command', 'php-fpm: pool www');
});

test('kill a process', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('server-system.processes.kill', $this->server), ['pid' => 812, 'signal' => 'TERM'])
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('kill -TERM 812');
    $this->assertDatabaseHas('server_logs', ['server_id' => $this->server->id, 'type' => 'kill-process']);
});

test('kill input is validated', function (array $input, string $error) {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('server-system.processes.kill', $this->server), $input)
        ->assertSessionHasErrors($error);

    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([
    'init' => [['pid' => 1, 'signal' => 'TERM'], 'pid'],
    'not a number' => [['pid' => '1; reboot', 'signal' => 'TERM'], 'pid'],
    'unknown signal' => [['pid' => 812, 'signal' => 'HUP'], 'signal'],
]);

test('upgradable packages are parsed', function () {
    ServerLog::factory()->create(['server_id' => $this->server->id, 'type' => 'upgrade']);
    ServerLog::factory()->create(['server_id' => $this->server->id, 'type' => 'deploy']);
    SSH::fake(implode("\n", [
        'curl/noble-updates 8.5.0-2ubuntu10.6 amd64 [upgradable from: 8.5.0-2ubuntu10.5]',
        'linux-image-generic/noble-updates 6.8.0-48.48 amd64 [upgradable from: 6.8.0-45.45]',
        'VITO_REBOOT_REQUIRED',
    ]));
    $this->actingAs($this->user);

    $this->get(route('server-system.updates', $this->server))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('server-system/updates')
            ->has('logs', 1)
            ->where('logs.0.type', 'upgrade'));

    $this->get(route('server-system.updates.json', $this->server))
        ->assertOk()
        ->assertJsonPath('reboot_required', true)
        ->assertJsonPath('packages.0.name', 'curl')
        ->assertJsonPath('packages.0.current', '8.5.0-2ubuntu10.5')
        ->assertJsonPath('packages.0.candidate', '8.5.0-2ubuntu10.6')
        ->assertJsonPath('packages.0.kernel', false)
        ->assertJsonPath('packages.1.kernel', true);
});

test('command history is parsed newest first', function () {
    SSH::fake(implode("\n", [
        "sudo\t2026-10-05T10:00:00+0000 vito sudo[1200]:     vito : PWD=/home/vito ; USER=root ; COMMAND=/usr/bin/apt-get update",
        "sudo\t2026-10-05T10:05:00+0000 vito sudo[1300]:     latif : TTY=pts/0 ; PWD=/root ; USER=root ; COMMAND=/usr/bin/systemctl restart nginx",
        "bash\troot\tls -la",
        "bash\troot\t#1728120000",
        "bash\tplaka\tphp artisan migrate",
    ]));
    $this->actingAs($this->user);

    $this->get(route('server-system.commands', $this->server))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-system/commands'));

    $this->get(route('server-system.commands.json', $this->server))
        ->assertOk()
        ->assertJsonCount(4)
        ->assertJsonPath('0.source', 'bash')
        ->assertJsonPath('0.user', 'plaka')
        ->assertJsonPath('0.command', 'php artisan migrate')
        ->assertJsonPath('2.source', 'sudo')
        ->assertJsonPath('2.user', 'latif')
        ->assertJsonPath('2.run_as', 'root')
        ->assertJsonPath('2.time', '2026-10-05T10:05:00+0000')
        ->assertJsonPath('2.command', '/usr/bin/systemctl restart nginx');
});

test('read only user cannot manage processes or see command history', function (string $method, string $route) {
    SSH::fake();
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->actingAs($this->user);

    $this->{$method}(route($route, $this->server), ['pid' => 812, 'signal' => 'TERM', 'path' => '/var/log/syslog'])
        ->assertForbidden();

    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([
    ['get', 'server-system.processes'],
    ['get', 'server-system.processes.json'],
    ['post', 'server-system.processes.kill'],
    ['post', 'server-system.logs.clear'],
    ['get', 'server-system.commands'],
    ['get', 'server-system.commands.json'],
]);

test('system refuses unsupported signals even without the action', function () {
    SSH::fake();

    expect(fn () => $this->server->system()->kill(812, 'HUP'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $this->server->system()->kill(1, 'TERM'))->toThrow(InvalidArgumentException::class)
        ->and(SSH::getExecutedCommands())->toBeEmpty();
});

test('a trailing newline does not pass log path validation', function () {
    SSH::fake();

    expect(fn () => app(ClearLogFile::class)->clear($this->server, ['path' => "/var/log/syslog\n"]))
        ->toThrow(ValidationException::class)
        ->and(SSH::getExecutedCommands())->toBeEmpty();
});
