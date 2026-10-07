<?php

namespace App\Http\Controllers;

use App\Actions\Projects\GetOverview;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;

#[Middleware(['auth', 'has-project'])]
class OverviewController extends Controller
{
    #[Get('/overview', name: 'overview')]
    public function __invoke(): Response
    {
        $project = user()->currentProject;

        $this->authorize('view', $project);

        return Inertia::render('overview/index', [
            'overview' => app(GetOverview::class)->handle($project),
        ]);
    }
}
