<?php

use Inertia\Testing\AssertableInertia as Assert;

test('public layout exposes direction for keyboard/screen-reader navigation', function (string $locale, string $direction) {
    // lang/dir are rendered server-side by resources/views/app.blade.php, so
    // they're still directly observable here. The skip-link/dialog/aria
    // markup this test used to check is rendered by PublicLayout/MobileDrawer
    // (React) instead of Blade now; SSR isn't running during `php artisan
    // test`, so that markup never reaches the raw response body here. Proving
    // it renders correctly now belongs to a browser-level (JS/e2e) test.
    $this->withSession(['locale' => $locale])->get('/')
        ->assertOk()
        ->assertSee('lang="'.$locale.'"', false)
        ->assertSee('dir="'.$direction.'"', false)
        ->assertInertia(fn (Assert $page) => $page->where('locale', $locale)->where('direction', $direction));
})->with([
    'English' => ['en', 'ltr'],
    'Arabic' => ['ar', 'rtl'],
]);

test('homepage renders successfully for the mobile navigation to mount on', function () {
    // The hidden/responsive nav classes and aria-controls/aria-expanded state
    // this test used to check are React-rendered (PublicHeader/MobileDrawer)
    // and, like above, aren't observable without SSR or a browser test.
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home'));
});
