<?php

it('redirects to the correct zitadel domain on login', function () {
    // Set a custom zitadel base_url
    config()->set('services.zitadel.base_url', 'https://auth.example.com');
    
    $response = $this->get('/sso/login');

    $response->assertRedirect();
    $redirectUrl = $response->headers->get('Location');
    
    expect($redirectUrl)->toContain('https://auth.example.com');
});

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
