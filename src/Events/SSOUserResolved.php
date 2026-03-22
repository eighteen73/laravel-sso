<?php

namespace Eighteen73\SSO\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SSOUserResolved
{
    public function __construct(
        public string $provider,
        public ProviderUser $ssoUser,
        public Authenticatable $user
    ) {}
}
