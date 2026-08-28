<?php

test('shared public API caches vary localized responses by language and cookie state', function () {
    $response = $this->withHeader('Accept-Language', 'en')->getJson('/api/categories');

    $response->assertOk();

    $vary = implode(', ', $response->headers->all('Vary'));

    expect($response->headers->get('Cache-Control'))->toContain('public')
        ->and($vary)->toContain('Accept')
        ->toContain('Accept-Language')
        ->toContain('Cookie');
});
