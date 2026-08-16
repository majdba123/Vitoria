<?php

use App\Models\Syndicate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the employee notification center for employees', function () {
    $employee = User::factory()->employee()->create();

    $this->actingAs($employee)
        ->get(route('employee.notifications.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Employee/Notifications/Index'));
});

it('renders the syndicate notification center for active syndicates', function () {
    $syndicate = Syndicate::factory()->create();

    $this->actingAs($syndicate->user)
        ->get(route('syndicate.notifications.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Syndicate/Notifications/Index'));
});

it('protects role notification centers from unrelated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('employee.notifications.index'))->assertRedirect(route('login'));
    $this->actingAs($user)->get(route('syndicate.notifications.index'))->assertRedirect(route('login'));
});
