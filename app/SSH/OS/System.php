<?php

namespace App\SSH\OS;

use App\Exceptions\SSHError;
use App\Models\Server;
use Illuminate\Contracts\View\View;

class System
{
    private const KERNEL_PACKAGE = '/^linux-(image|headers|modules|modules-extra|generic|virtual|lowlatency|hwe|tools|cloud-tools|aws|azure|gcp|oracle|kvm)/';

    public function __construct(protected Server $server) {}

    /**
     * @return array{
     *     info: array<string, string>,
     *     disks: list<array{mount: string, filesystem: string, size: int, used: int, available: int}>,
     *     directories: list<array{path: string, size: int}>,
     *     logs: list<array{path: string, size: int}>
     * }
     *
     * @throws SSHError
     */
    public function overview(): array
    {
        $overview = ['info' => [], 'disks' => [], 'directories' => [], 'logs' => []];

        foreach ($this->rows(view('ssh.os.system-overview'), 6) as $row) {
            match ($row[0]) {
                'info' => $overview['info'][$row[1]] = $row[2] ?? '',
                'disk' => $overview['disks'][] = [
                    'mount' => $row[1],
                    'filesystem' => $row[2] ?? '',
                    'size' => (int) ($row[3] ?? 0),
                    'used' => (int) ($row[4] ?? 0),
                    'available' => (int) ($row[5] ?? 0),
                ],
                'directory' => $overview['directories'][] = ['path' => $row[1], 'size' => (int) ($row[2] ?? 0)],
                'log' => $overview['logs'][] = ['path' => $row[1], 'size' => (int) ($row[2] ?? 0)],
                default => null,
            };
        }

        return $overview;
    }

    /**
     * @return list<array{pid: int, user: string, cpu: float, memory: float, rss: int, elapsed: int, command: string}>
     *
     * @throws SSHError
     */
    public function processes(): array
    {
        $output = $this->server->ssh()->exec(view('ssh.os.processes'));

        $processes = [];
        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            $columns = preg_split('/\s+/', trim($line), 7);
            if ($columns === false || count($columns) < 7 || ! ctype_digit($columns[0])) {
                continue;
            }

            $processes[] = [
                'pid' => (int) $columns[0],
                'user' => $columns[1],
                'cpu' => (float) $columns[2],
                'memory' => (float) $columns[3],
                'rss' => (int) $columns[4] * 1024,
                'elapsed' => (int) $columns[5],
                'command' => mb_strimwidth($columns[6], 0, 300, '…'),
            ];
        }

        return $processes;
    }

    /**
     * @throws SSHError
     */
    public function kill(int $pid, string $signal): void
    {
        $this->server->ssh()->exec(
            view('ssh.os.kill-process', ['pid' => $pid, 'signal' => $signal]),
            'kill-process'
        );
    }

    /**
     * @return array{reboot_required: bool, packages: list<array{name: string, current: string, candidate: string, kernel: bool}>}
     *
     * @throws SSHError
     */
    public function upgradablePackages(): array
    {
        $output = $this->server->ssh()->exec(view('ssh.os.upgradable-packages'));

        $packages = [];
        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            if (preg_match('/^([^\/\s]+)\/\S+\s+(\S+)\s+\S+\s+\[upgradable from: ([^\]]+)\]/', trim($line), $matches) !== 1) {
                continue;
            }

            $packages[] = [
                'name' => $matches[1],
                'current' => $matches[3],
                'candidate' => $matches[2],
                'kernel' => preg_match(self::KERNEL_PACKAGE, $matches[1]) === 1,
            ];
        }

        return [
            'reboot_required' => str_contains($output, 'VITO_REBOOT_REQUIRED'),
            'packages' => $packages,
        ];
    }

    /**
     * @return list<array{source: string, time: ?string, user: string, run_as: ?string, command: string}>
     *
     * @throws SSHError
     */
    public function commandHistory(): array
    {
        $history = [];
        foreach ($this->rows(view('ssh.os.command-history'), 3) as $row) {
            $entry = match ($row[0]) {
                'sudo' => $this->parseSudoEntry($row[1] ?? ''),
                'bash' => isset($row[2]) && trim($row[2]) !== '' && ! str_starts_with($row[2], '#')
                    ? ['source' => 'bash', 'time' => null, 'user' => $row[1], 'run_as' => null, 'command' => trim($row[2])]
                    : null,
                default => null,
            };

            if ($entry !== null) {
                $history[] = $entry;
            }
        }

        return array_reverse($history);
    }

    /**
     * @return ?array{source: string, time: ?string, user: string, run_as: ?string, command: string}
     */
    private function parseSudoEntry(string $line): ?array
    {
        if (preg_match('/^(\S+)\s+\S+\s+sudo\[\d+\]:\s+(\S+)\s*:.*?COMMAND=(.+)$/', $line, $matches) !== 1) {
            return null;
        }

        return [
            'source' => 'sudo',
            'time' => $matches[1],
            'user' => $matches[2],
            'run_as' => preg_match('/USER=([^\s;]+)/', $line, $runAs) === 1 ? $runAs[1] : null,
            'command' => trim($matches[3]),
        ];
    }

    /**
     * @return list<list<string>>
     *
     * @throws SSHError
     */
    private function rows(View $script, int $columns): array
    {
        $output = $this->server->ssh()->exec($script, timeout: 30);

        $rows = [];
        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            if (str_contains($line, "\t")) {
                $rows[] = explode("\t", $line, $columns);
            }
        }

        return $rows;
    }
}
