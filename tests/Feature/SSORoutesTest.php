<?php

use Eighteen73\SSO\Tests\TestUser;

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

it('logs out the user and redirects to the provider logout url if id token is present', function () {
    config()->set('services.zitadel.base_url', 'https://auth.example.com');
    config()->set('services.zitadel.post_logout_redirect_uri', 'https://myapp.com');
    config()->set('services.zitadel.client_id', 'client-123');

    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    $this->actingAs($user)
        ->withSession(['sso_id_token' => 'dummy_id_token']);

    $response = $this->get('/sso/logout');

    $response->assertRedirect();
    $this->assertGuest();

    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain('https://auth.example.com/oidc/v1/end_session');
    expect($redirectUrl)->toContain('id_token_hint=dummy_id_token');
});

it('logs out the user and redirects home if no id token is present', function () {
    $user = TestUser::create([
        'name' => 'Test User 2',
        'email' => 'test2@example.com',
        'password' => 'secret',
    ]);

    $this->actingAs($user);

    $response = $this->get('/sso/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
