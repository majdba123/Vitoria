<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('name="preferred_product_type"', false)
        ->assertSee('value="agriculture"', false)
        ->assertSee('value="veterinary"', false);
});
