<?php

namespace App\Http\Controllers\Project;

use App\Actions\Projects\CreateProjectUser;
use App\Actions\Projects\InviteToProject;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\UserProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/projects/{project}/users')]
#[Middleware(['auth'])]
class ProjectUserController extends Controller
{
    #[Post('/', name: 'projects.users.store')]
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        app(InviteToProject::class)->invite($project, $request->input());

        return back()->with('success', __('An invitation has been sent to the email address.'));
    }

    #[Post('/create', name: 'projects.users.create')]
    public function create(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->authorize('create', User::class);

        app(CreateProjectUser::class)->create($project, $request->only(['name', 'email', 'password', 'role']));

        return back()->with('success', __('The account has been created and added to the project.'));
    }

    #[Delete('{id}', name: 'projects.users.destroy')]
    public function destroy(Project $project, int $id): RedirectResponse
    {
        $this->authorize('update', $project);

        /** @var ?UserProject $userProject */
        $userProject = $project->users()->where('id', $id)->first();

        if ($userProject?->user && $project->role($userProject->user) === UserRole::OWNER) {
            return back()->with('error', __('You cannot remove the project owner.'));
        }

        if ($userProject?->email === user()->email || $userProject?->user_id === user()->id) {
            return back()->with('error', __('You cannot remove yourself from the project.'));
        }

        $project->users()
            ->where('id', $id)
            ->delete();

        return back()->with('success', __('The user has been removed.'));
    }
}
