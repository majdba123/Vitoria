<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can render the login page', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
});

test('admins can render the live dashboard page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));
});

test('non admins cannot render the admin dashboard page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});
