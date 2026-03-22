<?php

it('redirects to the socialite provider on login', function () {
    $response = $this->get('/sso/login');

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

it('handles callback gracefully when socialite fails', function () {
    $response = $this->get('/sso/callback');

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['sso' => 'Authentication failed or was cancelled.']);
});
