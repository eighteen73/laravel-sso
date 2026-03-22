<?php

namespace Eighteen73\SSO\Actions;

use Laravel\Socialite\Contracts\User as ProviderUser;
use Illuminate\Contracts\Auth\Authenticatable;

interface ResolveUserContract
{
    /**
     * Resolve a local user from a Socialite provider user.
     *
     * @param string $provider
     * @param ProviderUser $ssoUser
     * @return Authenticatable
     */
    public function resolve(string $provider, ProviderUser $ssoUser): Authenticatable;
}
