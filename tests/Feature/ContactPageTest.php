<?php

use App\Models\ContactMessage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the contact page is public and linked by both public navigation variants', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Contact'));

    expect(file_get_contents(resource_path('js/Components/public/PublicHeader.jsx')))
        ->toContain("route('contact')")
        ->and(file_get_contents(resource_path('js/Components/public/MobileDrawer.jsx')))
        ->toContain("route('contact')");
});

test('a valid public contact submission is stored', function () {
    $this->postJson(route('api.contact.store'), [
        'name' => 'Public Visitor',
        'email' => 'visitor@example.com',
        'message' => 'Please contact me about an order.',
    ])->assertCreated()
        ->assertJsonPath('data.status', ContactMessage::STATUS_PENDING);

    $this->assertDatabaseHas('contact_messages', [
        'name' => 'Public Visitor',
        'email' => 'visitor@example.com',
        'message' => 'Please contact me about an order.',
    ]);
});

test('invalid contact submissions return field errors', function () {
    $this->postJson(route('api.contact.store'), ['email' => 'invalid', 'message' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'message']);
});

test('submitted html is stored as text and is not exposed by the public page', function () {
    $script = '<script>alert("contact")</script>';

    $this->postJson(route('api.contact.store'), ['email' => 'safe@example.com', 'message' => $script])
        ->assertCreated();

    $this->assertDatabaseHas('contact_messages', ['message' => $script]);
    $this->get(route('contact'))->assertOk()->assertDontSee($script, false);
});

test('a regular user cannot access admin contact management', function () {
    $this->actingAs(User::factory()->create(['type' => User::TYPE_USER]))
        ->get(route('admin.contact-messages.index'))
        ->assertRedirect();
});
