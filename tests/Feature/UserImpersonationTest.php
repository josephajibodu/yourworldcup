<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot impersonate users', function () {
    $target = User::factory()->create();

    $this->post(route('admin.users.impersonate', $target))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot impersonate users', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.users.impersonate', $target))
        ->assertForbidden();
});

test('site admins can impersonate a player', function () {
    $admin = siteAdmin();
    $target = User::factory()->create([
        'name' => 'playerhandle',
        'email' => 'player@example.com',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $target))
        ->assertRedirect(route('predict'));

    $this->assertAuthenticatedAs($target);
    expect(session('impersonator_id'))->toBe($admin->id);
});

test('site admins cannot impersonate themselves', function () {
    $admin = siteAdmin();

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $admin))
        ->assertForbidden();

    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});

test('site admins cannot impersonate while already impersonating', function () {
    $admin = siteAdmin();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', $first))
        ->assertRedirect(route('predict'));

    $this->post(route('admin.users.impersonate', $second))
        ->assertForbidden();
});

test('impersonated users cannot access admin pages', function () {
    $admin = siteAdmin();
    $target = User::factory()->create();

    $this->actingAs($target)
        ->withSession(['impersonator_id' => $admin->id])
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('impersonated users see the impersonation banner state', function () {
    $admin = siteAdmin();
    $target = User::factory()->create(['name' => 'playerhandle']);

    $this->actingAs($target)
        ->withSession(['impersonator_id' => $admin->id])
        ->get(route('predict'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('impersonating.userName', 'playerhandle')
            ->where('auth.user.id', $target->id)
            ->where('auth.isAdmin', false));
});

test('admins can leave impersonation mode', function () {
    $admin = siteAdmin();
    $target = User::factory()->create();

    $this->actingAs($target)
        ->withSession(['impersonator_id' => $admin->id])
        ->delete(route('impersonation.destroy'))
        ->assertRedirect(route('admin.users.index'));

    $this->assertAuthenticatedAs($admin);
    expect(session('impersonator_id'))->toBeNull();
});

test('users who are not impersonating cannot leave impersonation mode', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('impersonation.destroy'))
        ->assertForbidden();
});
