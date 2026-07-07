<?php

namespace Eighteen73\SSO\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasSsoAccounts
{
    public function ssoAccounts(): HasMany;

    public function hasSsoAccount(?string $provider = null): bool;

    public function requiresSsoLogin(): bool;

    public function canUsePasswordLogin(): bool;
}
