<?php

namespace Eighteen73\SSO\Actions;

use Eighteen73\SSO\Events\SSOUserCreated;
use Eighteen73\SSO\Events\SSOUserResolved;
use Eighteen73\SSO\Exceptions\UserNotFoundException;
use Eighteen73\SSO\Models\SocialAccount;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;

class ResolveUser implements ResolveUserContract
{
    public function resolve(string $provider, ProviderUser $ssoUser): Authenticatable
    {
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $ssoUser->getId())
            ->first();

        if ($socialAccount) {
            $this->updateTokens($socialAccount, $ssoUser);
            $user = $socialAccount->user;
            
            event(new SSOUserResolved($provider, $ssoUser, $user));
            
            return $user;
        }

        $userModel = config('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);
        $user = $userModel::where('email', $ssoUser->getEmail())->first();

        if (! $user) {
            if (! config('sso.auto_create_users', true)) {
                throw new UserNotFoundException('User not found and auto-creation is disabled.');
            }

            $user = $this->createUser($ssoUser, $userModel);
            event(new SSOUserCreated($provider, $ssoUser, $user));
        }

        $this->linkUser($user, $provider, $ssoUser);
        
        event(new SSOUserResolved($provider, $ssoUser, $user));

        return $user;
    }

    protected function createUser(ProviderUser $ssoUser, string $userModel): Authenticatable
    {
        return $userModel::create([
            'name' => $ssoUser->getName() ?? $ssoUser->getNickname() ?? 'User',
            'email' => $ssoUser->getEmail(),
            'password' => Hash::make(Str::random(32)),
        ]);
    }

    protected function linkUser(Authenticatable $user, string $provider, ProviderUser $ssoUser): void
    {
        SocialAccount::create([
            'user_id' => $user->getAuthIdentifier(),
            'provider' => $provider,
            'provider_id' => $ssoUser->getId(),
            'token' => $ssoUser->token,
            'refresh_token' => $ssoUser->refreshToken,
            'expires_at' => property_exists($ssoUser, 'expiresIn') ? now()->addSeconds($ssoUser->expiresIn) : null,
        ]);
    }

    protected function updateTokens(SocialAccount $socialAccount, ProviderUser $ssoUser): void
    {
        $socialAccount->update([
            'token' => $ssoUser->token,
            'refresh_token' => $ssoUser->refreshToken,
            'expires_at' => property_exists($ssoUser, 'expiresIn') ? now()->addSeconds($ssoUser->expiresIn) : $socialAccount->expires_at,
        ]);
    }
}
