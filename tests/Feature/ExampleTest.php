<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('preferred_product_type=agriculture', false)
        ->assertSee('preferred_product_type=veterinary', false)
        ->assertSee('redirect_to=home', false);
});
