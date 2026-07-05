<?php

use App\Models\User;

test('guest locale switch updates page language and direction', function () {
    $this->from('/')
        ->get('/locale/en')
        ->assertRedirect('/');

    $this->get('/')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false);

    $this->from('/')
        ->get('/locale/ar')
        ->assertRedirect('/');

    $this->get('/')
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false);
});

test('authenticated locale switch persists to the user profile', function () {
    $user = User::factory()->create([
        'locale' => 'ar',
    ]);

    $this->actingAs($user)
        ->from('/')
        ->get('/locale/en')
        ->assertRedirect('/');

    expect($user->refresh()->locale)->toBe('en');

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false);
});

test('api responses respect accept language for guest requests', function () {
    $this->withSession([])
        ->withCookie('locale', '')
        ->withCookie('sz_locale', '')
        ->withHeader('Accept-Language', 'en')
        ->getJson('/api/cities')
        ->assertOk()
        ->assertJsonPath('message', 'Cities retrieved successfully.');

    $this->withSession([])
        ->withCookie('locale', '')
        ->withCookie('sz_locale', '')
        ->withHeader('Accept-Language', 'ar')
        ->getJson('/api/cities')
        ->assertOk()
        ->assertJsonPath('message', 'تم جلب المدن بنجاح.');
});
