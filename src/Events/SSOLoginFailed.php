<?php

namespace Eighteen73\SSO\Events;

use Laravel\Socialite\Contracts\User as ProviderUser;

class SSOLoginFailed
{
    public function __construct(
        public string $provider,
        public ?ProviderUser $ssoUser = null,
        public ?\Throwable $exception = null
    ) {}
}
