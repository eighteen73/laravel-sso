<?php

namespace Eighteen73\SSO\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Socialite\Contracts\User as ProviderUser;

interface ResolveUserContract
{
    /**
     * Resolve a local user from a Socialite provider user.
     */
    public function resolve(string $provider, ProviderUser $ssoUser): Authenticatable;
}
