<?php

namespace App\Actions\Site;

use App\Actions\CronJob\CreateCronJob;
use App\Actions\Database\CreateDatabase;
use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Actions\Worker\CreateWorker;
use App\Enums\CronjobStatus;
use App\Enums\DatabaseUserPermission;
use App\Exceptions\SSHError;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Site;
use App\Services\Database\Mariadb;
use App\Services\Database\Postgresql;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Finishes a Laravel site so it runs without manual steps: production values in
 * .env (with an app key), an optional database owned by its own user, a queue
 * worker and the scheduler. Every step is skipped when it is already in place,
 * so a retried installation can run it again.
 */
class SetUpLaravelApp
{
    public const string WORKER_NAME = 'queue';

    /**
     * @throws SSHError
     * @throws ValidationException
     */
    public function setUp(Site $site): void
    {
        $env = [];

        if ($site->type_data['production_env'] ?? false) {
            $env['APP_ENV'] = 'production';
            $env['APP_DEBUG'] = 'false';
            $env['APP_URL'] = 'https://'.$site->domain;
        }

        if ($site->type_data['setup_database'] ?? false) {
            $env = array_merge($env, $this->createDatabase($site));
        }

        if ($env !== []) {
            $this->writeEnv($site, $env);
        }

        if ($site->type_data['setup_queue_worker'] ?? false) {
            $this->createQueueWorker($site);
        }

        if ($site->type_data['setup_scheduler'] ?? false) {
            $this->createScheduler($site);
        }
    }

    /**
     * The Laravel app lives next to the web directory, so a site served from
     * `panel/public` is an app in `panel`.
     */
    public static function appDirectory(Site $site): string
    {
        $webDirectory = trim((string) $site->web_directory, '/');

        if ($webDirectory === 'public') {
            return '';
        }

        return str_ends_with($webDirectory, '/public') ? Str::beforeLast($webDirectory, '/public') : '';
    }

    public static function appPath(Site $site): string
    {
        $directory = self::appDirectory($site);

        return $directory === '' ? $site->path : $site->path.'/'.$directory;
    }

    public static function databaseName(Site $site): string
    {
        return Str::of($site->user)->replace('-', '_')->toString();
    }

    /**
     * Creates whatever is missing of the database, its user and the link between
     * them, and returns the credentials. A retried installation reuses the stored
     * password, so .env always receives working values.
     *
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    private function createDatabase(Site $site): array
    {
        $server = $site->server;
        $name = self::databaseName($site);

        $service = $server->database();
        if (! $service) {
            throw ValidationException::withMessages([
                'setup_database' => __('A database service is required to create a database.'),
            ]);
        }

        $handler = $service->handler();
        $databaseUser = DatabaseUser::query()->where('server_id', $server->id)->where('username', $name)->first();
        $databaseExists = Database::query()->where('server_id', $server->id)->where('name', $name)->exists();

        if (! $databaseExists) {
            $charset = (string) ($service->type_data['defaultCharset'] ?? '') ?: $this->defaultCharset($handler);
            $collation = (string) ($service->type_data['charsets'][$charset]['default'] ?? '') ?: $this->defaultCollation($handler);

            app(CreateDatabase::class)->create($server, [
                'name' => $name,
                'charset' => $charset,
                'collation' => $collation,
            ]);
        }

        if (! $databaseUser) {
            $databaseUser = app(CreateDatabaseUser::class)->create($server, [
                'username' => $name,
                'password' => Str::random(32),
                'permission' => DatabaseUserPermission::ADMIN->value,
            ], [$name]);
        } elseif (! in_array($name, $databaseUser->databases ?? [], true)) {
            app(LinkUser::class)->link($databaseUser, [
                'databases' => array_values(array_unique([...($databaseUser->databases ?? []), $name])),
            ]);
        }

        return [
            'DB_CONNECTION' => $handler instanceof Postgresql ? 'pgsql' : ($handler instanceof Mariadb ? 'mariadb' : 'mysql'),
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => $handler instanceof Postgresql ? '5432' : '3306',
            'DB_DATABASE' => $name,
            'DB_USERNAME' => $name,
            'DB_PASSWORD' => (string) $databaseUser->password,
        ];
    }

    private function defaultCharset(object $handler): string
    {
        return $handler instanceof Postgresql ? 'UTF8' : 'utf8mb4';
    }

    private function defaultCollation(object $handler): string
    {
        return $handler instanceof Postgresql ? 'C.utf8' : 'utf8mb4_unicode_ci';
    }

    /**
     * Patches the live .env in place so comments and unrelated values survive,
     * and adds an app key to a production .env that has none.
     *
     * @param  array<string, string>  $values
     *
     * @throws SSHError
     */
    private function writeEnv(Site $site, array $values): void
    {
        $path = $site->resolveEnvPath();
        $content = $site->server->os()->readFile($path);

        if (($site->type_data['production_env'] ?? false) && ! preg_match('/^APP_KEY=["\']?[^\s"\']+/m', $content)) {
            $values['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
        }

        foreach ($values as $key => $value) {
            $line = $key.'='.$value;
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            $content = preg_match($pattern, $content)
                ? (string) preg_replace_callback($pattern, fn (): string => $line, $content)
                : rtrim($content)."\n".$line;
        }

        $site->server->os()->write($path, trim($content)."\n", $site->user);
    }

    /**
     * @throws ValidationException
     */
    private function createQueueWorker(Site $site): void
    {
        if ($site->workers()->where('name', self::WORKER_NAME)->exists()) {
            return;
        }

        $directory = self::appDirectory($site);
        $artisan = $directory === '' ? 'artisan' : $directory.'/artisan';

        app(CreateWorker::class)->create($site->server, [
            'name' => self::WORKER_NAME,
            'command' => "php {$artisan} queue:work --sleep=3 --tries=3 --max-time=3600",
            'user' => $site->user,
            'auto_start' => true,
            'auto_restart' => true,
            'numprocs' => 1,
        ], $site);
    }

    /**
     * @throws SSHError
     */
    private function createScheduler(Site $site): void
    {
        $command = 'cd '.self::appPath($site).' && php artisan schedule:run >> /dev/null 2>&1';

        $existing = $site->cronJobs()->where('command', $command)->first();
        if ($existing?->status === CronjobStatus::READY) {
            return;
        }
        $existing?->delete();

        app(CreateCronJob::class)->create($site->server, [
            'name' => 'Laravel scheduler',
            'command' => $command,
            'user' => $site->user,
            'frequency' => '* * * * *',
        ], $site);
    }
}
