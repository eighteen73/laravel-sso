<?php

use Eighteen73\SSO\Actions\ResolveUserContract;
use Eighteen73\SSO\Tests\TestUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;

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

it('includes configured login parameters on login redirects', function () {
    config()->set('sso.login_parameters', [
        'acr_values' => 'urn:example:mfa',
        'max_age' => 0,
        'prompt' => 'login',
    ]);

    $response = $this->get('/sso/login');

    $response->assertRedirect();

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query)
        ->toHaveKey('acr_values', 'urn:example:mfa')
        ->toHaveKey('max_age', '0')
        ->toHaveKey('prompt', 'login');
});

it('can force zitadel account selection on login redirects', function () {
    config()->set('sso.zitadel.select_account', true);

    $response = $this->get('/sso/login');

    $response->assertRedirect();

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query)->toHaveKey('prompt', 'select_account');
});

it('merges zitadel account selection with configured login prompt', function () {
    config()->set('sso.login_parameters.prompt', 'login');
    config()->set('sso.zitadel.select_account', true);

    $response = $this->get('/sso/login');

    $response->assertRedirect();

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query)->toHaveKey('prompt', 'login select_account');
});

it('handles callback gracefully when socialite fails', function () {
    $response = $this->get('/sso/callback');

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['sso' => 'Authentication failed or was cancelled.']);
});

it('rejects zitadel callbacks without mfa when mfa enforcement is enabled', function () {
    config()->set('sso.zitadel.enforce_mfa', true);

    $ssoUser = new ProviderUser;
    $ssoUser->map([
        'id' => 'sso-user-id',
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
    $ssoUser->accessTokenResponseBody = [
        'id_token' => idTokenWithAuthenticationMethods(['pwd']),
    ];

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn($ssoUser);

    Socialite::shouldReceive('driver')->with('zitadel')->once()->andReturn($provider);

    $response = $this->get('/sso/callback');

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['sso' => 'Multi-factor authentication is required.']);
});

it('accepts zitadel callbacks with mfa when mfa enforcement is enabled', function () {
    config()->set('sso.zitadel.enforce_mfa', true);

    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    $ssoUser = new ProviderUser;
    $ssoUser->map([
        'id' => 'sso-user-id',
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
    $ssoUser->accessTokenResponseBody = [
        'id_token' => idTokenWithAuthenticationMethods(['pwd', 'mfa']),
    ];

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn($ssoUser);

    Socialite::shouldReceive('driver')->with('zitadel')->once()->andReturn($provider);

    app()->bind(ResolveUserContract::class, fn () => new class($user) implements ResolveUserContract
    {
        public function __construct(private TestUser $user) {}

        public function resolve(string $provider, User $ssoUser): Authenticatable
        {
            return $this->user;
        }
    });

    $response = $this->get('/sso/callback');

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
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

function idTokenWithAuthenticationMethods(array $methods): string
{
    $payload = rtrim(strtr(base64_encode(json_encode(['amr' => $methods])), '+/', '-_'), '=');

    return 'header.'.$payload.'.signature';
}
