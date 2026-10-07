<?php

namespace App\Actions\Projects;

use App\Actions\User\CreateUser;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateProjectUser
{
    /**
     * Creates a regular app user, adds it to the project with the given project role
     * and makes that project the user's current one. Callers must authorize app-level user creation.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Project $project, array $input): User
    {
        $this->validate($input);

        return DB::transaction(function () use ($project, $input): User {
            $user = app(CreateUser::class)->create([
                ...Arr::only($input, ['name', 'email', 'password']),
                'role' => UserRole::USER->value,
            ]);

            $project->users()->whereNull('user_id')->whereRaw('lower(email) = ?', [strtolower($user->email)])->delete();
            $project->users()->create([
                'user_id' => $user->id,
                'role' => UserRole::from($input['role']),
            ]);

            $user->update(['current_project_id' => $project->id]);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'role' => ['required', Rule::in([UserRole::ADMIN, UserRole::USER])],
        ])->validate();
    }
}
