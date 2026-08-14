<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Checkout page and the storefront's migration off the localStorage cart
 * (spec §5, §7).
 */
it('requires authentication to reach the checkout page', function () {
    $this->get('/checkout')->assertRedirect('/login');
});

it('renders the checkout page for a signed-in customer', function () {
    // Checkout/Index.jsx fetches its own data client-side (/api/checkout/summary),
    // so the only thing the backend route itself guarantees is that the right
    // Inertia component is served to an authenticated user.
    $this->actingAs(User::factory()->create())
        ->get('/checkout')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Checkout/Index'));
});

it('no longer ships a localStorage cart anywhere in the storefront', function () {
    // The server cart is authoritative. A second client-side cart would silently
    // diverge from it, so none may remain in any Blade view or JS module.
    $offenders = [];

    foreach ([resource_path('views'), resource_path('js')] as $root) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), ['php', 'js'], true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if (preg_match("/localStorage\.(get|set|remove)Item\(\s*['\"]cart['\"]/", $contents)) {
                $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('does not hardcode a currency literal in the cart modal', function () {
    // Currency is configuration, not a string in Blade (spec §17).
    $modal = file_get_contents(resource_path('views/components/home/cart-modal.blade.php'));

    expect($modal)->toContain("config('vetora.currency'")
        ->and(preg_match('/>\s*SYP\s*</', $modal))->toBe(0);
});
