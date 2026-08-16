<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the bilingual inertia not found page for unknown web routes', function (string $locale, string $direction) {
    $this->withCookie('locale', $locale)
        ->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/NotFound')
            ->where('status', 404)
            ->where('locale', $locale)
            ->where('direction', $direction));
})->with([
    'English' => ['en', 'ltr'],
    'Arabic' => ['ar', 'rtl'],
]);

it('keeps unknown api routes as json responses', function () {
    $this->getJson('/api/this-endpoint-does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('status', 404);
});
