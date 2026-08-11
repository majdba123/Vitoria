<?php

test('public layout exposes direction and keyboard navigation landmarks', function (string $locale, string $direction) {
    $this->withSession(['locale' => $locale])->get('/')
        ->assertOk()
        ->assertSee('lang="'.$locale.'"', false)
        ->assertSee('dir="'.$direction.'"', false)
        ->assertSee('href="#main-content"', false)
        ->assertSee('id="main-content"', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false);
})->with([
    'English' => ['en', 'ltr'],
    'Arabic' => ['ar', 'rtl'],
]);

test('mobile navigation remains hidden below its responsive breakpoint', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="nav-guest" class="hidden items-center gap-2 sm:flex"', false)
        ->assertSee('aria-controls="mobile-drawer"', false)
        ->assertSee('aria-expanded="false"', false);
});
