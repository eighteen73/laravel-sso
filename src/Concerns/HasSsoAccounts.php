<?php

namespace Eighteen73\SSO\Concerns;

use Eighteen73\SSO\Models\SSOAccount;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasSsoAccounts
{
    public function ssoAccounts(): HasMany
    {
        return $this->hasMany(SSOAccount::class, 'user_id');
    }

    public function hasSsoAccount(?string $provider = null): bool
    {
        return $this->ssoAccounts()
            ->when($provider !== null, fn ($query) => $query->where('provider', $provider))
            ->exists();
    }

    public function requiresSsoLogin(): bool
    {
        return false;
    }

    public function canUsePasswordLogin(): bool
    {
        return true;
    }
}
