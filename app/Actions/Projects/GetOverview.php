<?php

namespace App\Actions\Projects;

use App\Enums\BackupFileStatus;
use App\Models\Backup;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class GetOverview
{
    private const RECENT_LIMIT = 5;

    private const SSL_WARNING_DAYS = 14;

    private const ATTENTION_LIMIT = 10;

    /**
     * @return array{
     *     counts: array{servers: int, sites: int, backups: int, domains: int},
     *     servers: list<array{id: int, name: string, ip: ?string, status: string, status_color: string}>,
     *     sites: list<array{id: int, server_id: int, server_name: string, domain: string, status: string, status_color: string}>,
     *     backup_problems: list<array{id: int, server_id: int, server_name: string, target: string, problem: string}>,
     *     expiring_ssls: list<array{id: int, server_id: ?int, site_id: ?int, domain: string, expires_at: string}>
     * }
     */
    public function handle(Project $project): array
    {
        return [
            'counts' => [
                'servers' => $project->servers()->count(),
                'sites' => $project->sites()->count(),
                'backups' => $project->backups()->count(),
                'domains' => $project->domains()->count(),
            ],
            'servers' => $this->recentServers($project),
            'sites' => $this->recentSites($project),
            'backup_problems' => $this->backupProblems($project),
            'expiring_ssls' => $this->expiringSsls($project),
        ];
    }

    /**
     * @return list<array{id: int, name: string, ip: ?string, status: string, status_color: string}>
     */
    private function recentServers(Project $project): array
    {
        return $project->servers()->latest()->limit(self::RECENT_LIMIT)->get()
            ->map(fn (Server $server): array => [
                'id' => $server->id,
                'name' => $server->name,
                'ip' => $server->ip,
                'status' => $server->status->getText(),
                'status_color' => $server->status->getColor(),
            ])->values()->all();
    }

    /**
     * @return list<array{id: int, server_id: int, server_name: string, domain: string, status: string, status_color: string}>
     */
    private function recentSites(Project $project): array
    {
        return $project->sites()->with('server')->latest('sites.created_at')->limit(self::RECENT_LIMIT)->get()
            ->map(fn (Site $site): array => [
                'id' => $site->id,
                'server_id' => $site->server_id,
                'server_name' => $site->server->name,
                'domain' => $site->domain,
                'status' => $site->status->getText(),
                'status_color' => $site->status->getColor(),
            ])->values()->all();
    }

    /**
     * @return list<array{id: int, server_id: int, server_name: string, target: string, problem: string}>
     */
    private function backupProblems(Project $project): array
    {
        return $project->backups()
            ->where('enabled', true)
            ->where(fn (Builder $query) => $query
                ->whereNotNull('health_alerted_at')
                ->orWhereHas('lastFile', fn (Builder $file) => $file->where('status', BackupFileStatus::FAILED)))
            ->with(['server', 'database', 'lastFile'])
            ->orderBy('backups.id')
            ->limit(self::ATTENTION_LIMIT)
            ->get()
            ->map(fn (Backup $backup): array => $this->backupRow($backup, $backup->health_alerted_at !== null ? 'overdue' : 'failed'))
            ->values()->all();
    }

    /**
     * @return array{id: int, server_id: int, server_name: string, target: string, problem: string}
     */
    private function backupRow(Backup $backup, string $problem): array
    {
        return [
            'id' => $backup->id,
            'server_id' => $backup->server_id,
            'server_name' => $backup->server->name,
            'target' => $backup->database->name ?? $backup->path,
            'problem' => $problem,
        ];
    }

    /**
     * @return list<array{id: int, server_id: ?int, site_id: ?int, domain: string, expires_at: string}>
     */
    private function expiringSsls(Project $project): array
    {
        return Ssl::query()
            ->where(fn (Builder $query) => $query
                ->whereIn('server_id', $project->servers()->select('id'))
                ->orWhereIn('site_id', $project->sites()->select('sites.id')))
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(self::SSL_WARNING_DAYS))
            ->with('site')
            ->orderBy('expires_at')
            ->limit(self::ATTENTION_LIMIT)
            ->get()
            ->map(fn (Ssl $ssl): array => [
                'id' => $ssl->id,
                'server_id' => $ssl->server_id ?? $ssl->site?->server_id,
                'site_id' => $ssl->site_id,
                'domain' => $ssl->site->domain ?? Arr::wrap($ssl->domains)[0] ?? '-',
                'expires_at' => (string) $ssl->expires_at?->toIso8601String(),
            ])->values()->all();
    }
}
