<?php

use Eighteen73\SSO\Models\SSOAccount;
use Eighteen73\SSO\Tests\SsoRequiredTestUser;
use Eighteen73\SSO\Tests\TestUser;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('provides an sso accounts relationship', function () {
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    expect($user->ssoAccounts())->toBeInstanceOf(HasMany::class);
});

it('knows whether any sso account is linked', function () {
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    expect($user->hasSsoAccount())->toBeFalse();

    SSOAccount::create([
        'user_id' => $user->getAuthIdentifier(),
        'provider' => 'zitadel',
        'provider_id' => 'zitadel-user-id',
    ]);

    expect($user->hasSsoAccount())->toBeTrue();
});

it('can check sso accounts for a specific provider', function () {
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    SSOAccount::create([
        'user_id' => $user->getAuthIdentifier(),
        'provider' => 'zitadel',
        'provider_id' => 'zitadel-user-id',
    ]);

    expect($user->hasSsoAccount('zitadel'))->toBeTrue()
        ->and($user->hasSsoAccount('github'))->toBeFalse();
});

it('defaults to permissive sso and password login policy', function () {
    $user = new TestUser;

    expect($user->requiresSsoLogin())->toBeFalse()
        ->and($user->canUsePasswordLogin())->toBeTrue();
});

it('allows applications to override sso login requirements without changing password login default', function () {
    $user = new SsoRequiredTestUser;

    expect($user->requiresSsoLogin())->toBeTrue()
        ->and($user->canUsePasswordLogin())->toBeTrue();
});
