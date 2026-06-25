<?php

use App\Models\User;

test('guests are redirected from admin users index', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access admin users pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $target = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.show', $target))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.users.update', $target), [
            'name' => 'updatedhandle',
            'email' => 'updated@example.com',
        ])
        ->assertForbidden();
});

test('site admins can view the users index', function () {
    User::factory()->count(3)->create();

    $this->actingAs(siteAdmin())
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users.data', 4)
            ->has('filters.search')
            ->where('selectedUser', null));
});

test('site admins can search users by handle or email', function () {
    $admin = siteAdmin();

    User::factory()->create([
        'name' => 'searchablehandle',
        'email' => 'findme@example.com',
    ]);

    User::factory()->create([
        'name' => 'otherhandle',
        'email' => 'other@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'searchable']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'searchablehandle'));

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'findme@']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'findme@example.com'));
});

test('site admins can view a user', function () {
    $target = User::factory()->create([
        'name' => 'playerhandle',
        'email' => 'player@example.com',
    ]);

    $this->actingAs(siteAdmin())
        ->get(route('admin.users.show', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->where('selectedUser.id', $target->id)
            ->where('selectedUser.name', 'playerhandle')
            ->where('selectedUser.email', 'player@example.com')
            ->has('users.data'));
});

test('site admins can update a user', function () {
    $target = User::factory()->create([
        'name' => 'oldhandle',
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.users.update', $target), [
            'name' => 'newhandle',
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('admin.users.index'));

    $target->refresh();

    expect($target->name)->toBe('newhandle')
        ->and($target->email)->toBe('new@example.com')
        ->and($target->email_verified_at)->toBeNull();
});

test('admin user updates validate unique email addresses', function () {
    $existing = User::factory()->create([
        'email' => 'taken@example.com',
    ]);

    $target = User::factory()->create([
        'email' => 'unique@example.com',
    ]);

    $this->actingAs(siteAdmin())
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.update', $target), [
            'name' => 'somehandle',
            'email' => $existing->email,
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('email');
});
