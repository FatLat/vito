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
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(Project $project, array $input): User
    {
        Validator::make($input, [
            'role' => ['required', Rule::in([UserRole::ADMIN->value, UserRole::USER->value])],
        ])->validate();

        return DB::transaction(function () use ($project, $input): User {
            $user = app(CreateUser::class)->create([
                ...Arr::only($input, ['name', 'email', 'password']),
                'role' => UserRole::USER->value,
            ]);

            $project->users()->where('email', $user->email)->delete();
            $project->users()->create([
                'user_id' => $user->id,
                'role' => UserRole::from($input['role']),
            ]);

            $user->update(['current_project_id' => $project->id]);

            return $user;
        });
    }
}
