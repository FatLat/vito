<?php

namespace App\Http\Controllers;

use App\Actions\Server\System\ClearLogFile;
use App\Actions\Server\System\KillProcess;
use App\Exceptions\SSHError;
use App\Http\Resources\ServerLogResource;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/system')]
#[Middleware(['auth', 'has-project'])]
class ServerSystemController extends Controller
{
    private const UPDATE_LOG_TYPES = ['upgrade', 'upgrade-kernel', 'reboot', 'check-available-updates', 'update-server-failed'];

    #[Get('/', name: 'server-system')]
    public function index(Server $server): Response
    {
        $this->authorize('view', $server);

        return Inertia::render('server-system/index');
    }

    /**
     * @throws SSHError
     */
    #[Get('/json', name: 'server-system.json')]
    public function json(Server $server): JsonResponse
    {
        $this->authorize('view', $server);
        abort_unless($server->isReady(), 422, __('Server is not ready.'));

        return response()->json($server->system()->overview());
    }

    /**
     * @throws SSHError
     */
    #[Post('/logs/clear', name: 'server-system.logs.clear')]
    public function clearLog(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(ClearLogFile::class)->clear($server, $request->only('path'));

        return back()->with('success', __('Log file cleared.'));
    }

    #[Get('/processes', name: 'server-system.processes')]
    public function processes(Server $server): Response
    {
        $this->authorize('manage', $server);

        return Inertia::render('server-system/processes');
    }

    /**
     * @throws SSHError
     */
    #[Get('/processes/json', name: 'server-system.processes.json')]
    public function processesJson(Server $server): JsonResponse
    {
        $this->authorize('manage', $server);

        return response()->json($server->system()->processes());
    }

    /**
     * @throws SSHError
     */
    #[Post('/processes/kill', name: 'server-system.processes.kill')]
    public function kill(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('manage', $server);

        app(KillProcess::class)->kill($server, $request->only(['pid', 'signal']));

        return back()->with('success', __('Signal sent to the process.'));
    }

    #[Get('/updates', name: 'server-system.updates')]
    public function updates(Server $server): Response
    {
        $this->authorize('view', $server);

        return Inertia::render('server-system/updates', [
            'logs' => ServerLogResource::collection(
                $server->logs()->whereIn('type', self::UPDATE_LOG_TYPES)->latest()->limit(10)->get()
            ),
        ]);
    }

    /**
     * @throws SSHError
     */
    #[Get('/updates/json', name: 'server-system.updates.json')]
    public function updatesJson(Server $server): JsonResponse
    {
        $this->authorize('view', $server);
        abort_unless($server->isReady(), 422, __('Server is not ready.'));

        return response()->json($server->system()->upgradablePackages());
    }

    #[Get('/commands', name: 'server-system.commands')]
    public function commands(Server $server): Response
    {
        $this->authorize('manage', $server);

        return Inertia::render('server-system/commands');
    }

    /**
     * @throws SSHError
     */
    #[Get('/commands/json', name: 'server-system.commands.json')]
    public function commandsJson(Server $server): JsonResponse
    {
        $this->authorize('manage', $server);

        return response()->json($server->system()->commandHistory());
    }
}
