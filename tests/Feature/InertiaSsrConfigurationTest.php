<?php

test('server-side rendering is opt-in when no managed renderer is configured', function () {
    expect(config('inertia.ssr.enabled'))->toBeFalse();

    $this->get('/')->assertSuccessful();
});
