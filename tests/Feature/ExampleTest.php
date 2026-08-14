<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the application returns a successful response', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home'));

    // The homepage's type-selector links are built client-side from these
    // route definitions (Ziggy mirrors them in the browser), so verifying
    // the PHP side generates the expected query string is the backend's
    // share of this contract — the rendered HTML itself is React's.
    expect(route('product-type.select', ['preferred_product_type' => 'agriculture', 'redirect_to' => 'home']))
        ->toContain('preferred_product_type=agriculture')
        ->toContain('redirect_to=home');

    expect(route('product-type.select', ['preferred_product_type' => 'veterinary', 'redirect_to' => 'home']))
        ->toContain('preferred_product_type=veterinary');
});
