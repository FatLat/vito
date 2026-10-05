<?php

namespace App\SiteTypes;

use App\Actions\Site\SetUpLaravelApp;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Site;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Laravel extends PHPSite
{
    /**
     * Optional steps that finish a Laravel site without manual work.
     */
    public const array SETUP_OPTIONS = ['production_env', 'setup_database', 'setup_queue_worker', 'setup_scheduler'];

    public static function id(): string
    {
        return 'laravel';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function createRules(array $input): array
    {
        $rules = parent::createRules($input);

        foreach (self::SETUP_OPTIONS as $option) {
            $rules[$option] = ['nullable', 'boolean'];
        }

        if (! empty($input['setup_database'])) {
            $rules['setup_database'][] = function (string $attribute, mixed $value, Closure $fail) use ($input): void {
                $this->validateDatabaseSetup($input, $fail);
            };
        }

        return $rules;
    }

    public function data(array $input): array
    {
        $data = parent::data($input);

        foreach (self::SETUP_OPTIONS as $option) {
            $data[$option] = (bool) ($input[$option] ?? false);
        }

        return $data;
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     * @throws ValidationException
     */
    public function install(): void
    {
        $appPath = SetUpLaravelApp::appPath($this->site);

        if ($appPath !== $this->site->path && empty($this->site->type_data['env_path'])) {
            $this->site->jsonUpdate('type_data', 'env_path', $appPath.'/.env');
        }

        parent::install();

        $envPath = $this->site->type_data['env_path'] ?? $this->site->path.'/.env';
        $examplePath = $appPath.'/.env.example';

        $this->site->server->ssh($this->site->user)->exec(
            view('ssh.laravel.ensure-env', [
                'envPath' => $envPath,
                'examplePath' => $examplePath,
            ]),
            'ensure-env',
            $this->site->id,
        );

        if ($this->hasSetupOptions()) {
            $this->progress(95, 'setting-up-laravel');
            app(SetUpLaravelApp::class)->setUp($this->site);
        }
    }

    /**
     * Apps in a subdirectory run their commands from there after pulling.
     */
    public function defaultDeploymentScript(): string
    {
        $script = parent::defaultDeploymentScript();
        $directory = SetUpLaravelApp::appDirectory($this->site);

        if ($directory === '' || $script === '') {
            return $script;
        }

        return Str::replaceFirst('git pull origin $BRANCH', "git pull origin \$BRANCH\n\ncd {$directory}", $script);
    }

    protected function composerPath(): string
    {
        return SetUpLaravelApp::appPath($this->site);
    }

    public function baseCommands(): array
    {
        return array_merge(parent::baseCommands(), [
            [
                'name' => 'cache:clear',
                'command' => 'php artisan cache:clear',
            ],
            [
                'name' => 'down',
                'command' => 'php artisan down --retry=5 --refresh=6 --quiet',
            ],
            [
                'name' => 'up',
                'command' => 'php artisan up',
            ],
        ]);
    }

    private function hasSetupOptions(): bool
    {
        foreach (self::SETUP_OPTIONS as $option) {
            if ($this->site->type_data[$option] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validateDatabaseSetup(array $input, Closure $fail): void
    {
        $server = $this->site->server;

        if (! $server->database()) {
            $fail(__('Install a database service on this server first.'));

            return;
        }

        if (! is_string($input['user'] ?? null) || $input['user'] === '') {
            return;
        }

        $name = SetUpLaravelApp::databaseName(new Site(['user' => $input['user']]));

        $taken = Database::query()->where('server_id', $server->id)->where('name', $name)->exists()
            || DatabaseUser::query()->where('server_id', $server->id)->where('username', $name)->exists();

        if ($taken) {
            $fail(__('A database or database user named :name already exists on this server.', ['name' => $name]));
        }
    }
}
