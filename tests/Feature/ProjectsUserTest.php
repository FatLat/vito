<?php

use App\Enums\UserRole;
use App\Mail\ProjectInvitation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('user can invite others', function () {
    Mail::fake();

    $this->actingAs($this->user);

    $project = $this->user->ensureHasDefaultProject();

    $this
        ->from(route('projects'))
        ->post(route('projects.users.store', ['project' => $project]), [
            'email' => 'new-user@example.com',
            'role' => UserRole::ADMIN->value,
        ])
        ->assertRedirect(route('projects'))
        ->assertSessionDoesntHaveErrors()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('user_project', [
        'project_id' => $project->id,
        'email' => 'new-user@example.com',
    ]);

    Mail::assertSent(ProjectInvitation::class);
});

test('can remove registered user from project', function () {
    $this->actingAs($this->user);

    $project = $this->user->ensureHasDefaultProject();

    /** @var User $newUser */
    $newUser = User::factory()->create();

    $userProject = $project->users()->create([
        'project_id' => $project->id,
        'user_id' => $newUser->id,
        'role' => UserRole::USER,
    ]);

    $this
        ->from(route('projects'))
        ->delete(route('projects.users.destroy', ['project' => $project, 'id' => $userProject->id]))
        ->assertRedirect(route('projects'))
        ->assertSessionDoesntHaveErrors()
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $project->id,
        'user_id' => $newUser->id,
    ]);
});

test('can remove owner from project', function () {
    $this->actingAs($this->user);

    $project = $this->user->ensureHasDefaultProject();

    $id = $project->users()->where('user_id', $this->user->id)->first()->id;

    $this
        ->from(route('projects'))
        ->delete(route('projects.users.destroy', ['project' => $project, 'id' => $id]))
        ->assertSessionHas([
            'error' => __('You cannot remove the project owner.'),
        ]);
});

test('can remove invited user from project', function () {
    $this->actingAs($this->user);

    $project = $this->user->ensureHasDefaultProject();

    $userProject = $project->users()->create([
        'project_id' => $project->id,
        'email' => 'new-user@example.com',
        'role' => UserRole::USER,
    ]);

    $this
        ->from(route('projects'))
        ->delete(route('projects.users.destroy', ['project' => $project, 'id' => $userProject->id]))
        ->assertRedirect(route('projects'))
        ->assertSessionDoesntHaveErrors()
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $project->id,
        'email' => 'new-user@example.com',
    ]);
});

test('user can accept invitation', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ]);

    $this
        ->from(route('projects'))
        ->get(route('projects.invitations.accept', ['project' => $ownerProject]))
        ->assertRedirect(route('projects'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('user_project', [
        'project_id' => $ownerProject->id,
        'user_id' => $this->user->id,
    ]);
});

test('user cannot join without invitation', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $this
        ->from(route('projects'))
        ->get(route('projects.invitations.accept', ['project' => $ownerProject]))
        ->assertNotFound();

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $ownerProject->id,
        'user_id' => $this->user->id,
    ]);
});

test('user can leave project', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $ownerProject->users()->create([
        'email' => $this->user->email,
        'role' => UserRole::USER,
    ]);

    $this
        ->from(route('projects'))
        ->delete(route('projects.leave', ['project' => $ownerProject]))
        ->assertRedirect(route('projects'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('user_project', [
        'project_id' => $ownerProject->id,
        'email' => $this->user->email,
    ]);
});

test('user can leave project that is not invited', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $ownerProject = $owner->ensureHasDefaultProject();

    $this->actingAs($this->user);

    $this
        ->from(route('projects'))
        ->delete(route('projects.leave', ['project' => $ownerProject]))
        ->assertNotFound();
});

test('cannot delete yourself from project', function () {
    $this->actingAs($this->user);

    $project = Project::factory()->create();

    $userProject = $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::ADMIN,
    ]);

    $this->delete(route('projects.users.destroy', ['project' => $project->id, 'id' => $userProject->id]))
        ->assertSessionHas([
            'error' => 'You cannot remove yourself from the project.',
        ]);
});

test('cannot delete the owner', function () {
    $this->actingAs($this->user);

    $project = Project::factory()->create();

    $userProject = $project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::OWNER,
    ]);

    $this->delete(route('projects.users.destroy', ['project' => $project->id, 'id' => $userProject->id]))
        ->assertSessionHas([
            'error' => 'You cannot remove the project owner.',
        ]);
});

test('app admin can create an account and add it to the project', function () {
    $this->user->update(['is_admin' => true]);
    $this->actingAs($this->user);
    $project = $this->user->ensureHasDefaultProject();
    $project->users()->create(['email' => 'Friend@Example.com', 'role' => UserRole::USER]);

    $this
        ->from(route('projects'))
        ->post(route('projects.users.create', ['project' => $project]), [
            'name' => 'Friend',
            'email' => 'friend@example.com',
            'password' => 'secret-pass',
            'role' => UserRole::ADMIN->value,
            'is_admin' => true,
        ])
        ->assertSessionDoesntHaveErrors()
        ->assertSessionHas('success');

    $friend = User::query()->where('email', 'friend@example.com')->firstOrFail();
    expect($friend->is_admin)->toBeFalse()
        ->and($friend->current_project_id)->toBe($project->id)
        ->and($project->role($friend))->toBe(UserRole::ADMIN)
        ->and($project->users()->whereNull('user_id')->exists())->toBeFalse();
});

test('project role must be admin or user when creating an account', function () {
    $this->user->update(['is_admin' => true]);
    $this->actingAs($this->user);
    $project = $this->user->ensureHasDefaultProject();

    $this->post(route('projects.users.create', ['project' => $project]), [
        'name' => 'Friend',
        'email' => 'friend@example.com',
        'password' => 'secret-pass',
        'role' => UserRole::OWNER->value,
    ])->assertSessionHasErrors('role');

    $this->assertDatabaseMissing('users', ['email' => 'friend@example.com']);
});

test('a failed account creation leaves nothing behind', function () {
    $this->user->update(['is_admin' => true]);
    $this->actingAs($this->user);
    $project = $this->user->ensureHasDefaultProject();

    $this->post(route('projects.users.create', ['project' => $project]), [
        'name' => 'Friend',
        'email' => $this->user->email,
        'password' => 'secret-pass',
        'role' => UserRole::USER->value,
    ])->assertSessionHasErrors('email');

    expect($project->users()->count())->toBe(1);
});

test('only app admins can create accounts from a project', function () {
    $this->user->update(['is_admin' => false]);
    $this->actingAs($this->user);
    $project = $this->user->ensureHasDefaultProject();

    $this->post(route('projects.users.create', ['project' => $project]), [
        'name' => 'Friend',
        'email' => 'friend@example.com',
        'password' => 'secret-pass',
        'role' => UserRole::USER->value,
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'friend@example.com']);
});

test('app admins without write access to the project cannot create accounts in it', function () {
    $this->user->update(['is_admin' => true]);
    $project = $this->user->ensureHasDefaultProject();
    $project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->actingAs($this->user);

    $this->post(route('projects.users.create', ['project' => $project]), [
        'name' => 'Friend',
        'email' => 'friend@example.com',
        'password' => 'secret-pass',
        'role' => UserRole::USER->value,
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'friend@example.com']);
});
