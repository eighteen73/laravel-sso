<?php

use Eighteen73\SSO\Actions\ResolveUser;
use Eighteen73\SSO\Tests\TestUser;
use Laravel\Socialite\Two\User as ProviderUser;

it('creates a new user and social account when auto create is true', function () {
    config()->set('sso.auto_create_users', true);

    $ssoUser = new ProviderUser();
    $ssoUser->map([
        'id' => '12345',
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
    $ssoUser->setToken('fake-token');

    $resolver = new ResolveUser();
    $user = $resolver->resolve('zitadel', $ssoUser);

    expect($user)->toBeInstanceOf(TestUser::class)
        ->and($user->email)->toBe('test@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'zitadel',
        'provider_id' => '12345',
        'token' => 'fake-token',
    ]);
});

it('links existing user when email matches', function () {
    config()->set('sso.auto_create_users', true);

    $existingUser = TestUser::create([
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'password' => 'secret',
    ]);

    $ssoUser = new ProviderUser();
    $ssoUser->map([
        'id' => '67890',
        'email' => 'existing@example.com',
        'name' => 'Existing User SSO',
    ]);
    $ssoUser->setToken('fake-token2');

    $resolver = new ResolveUser();
    $user = $resolver->resolve('zitadel', $ssoUser);

    expect($user->id)->toBe($existingUser->id);

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $existingUser->id,
        'provider' => 'zitadel',
        'provider_id' => '67890',
    ]);
});

it('throws exception when auto create is false and user does not exist', function () {
    config()->set('sso.auto_create_users', false);

    $ssoUser = new ProviderUser();
    $ssoUser->map([
        'id' => 'abcde',
        'email' => 'missing@example.com',
        'name' => 'Missing User',
    ]);

    $resolver = new ResolveUser();
    
    expect(fn () => $resolver->resolve('zitadel', $ssoUser))
        ->toThrow(\Eighteen73\SSO\Exceptions\UserNotFoundException::class, 'User not found and auto-creation is disabled.');
});
