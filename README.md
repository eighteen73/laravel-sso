# Eighteen73 SSO

A reusable Laravel package for integrating Single Sign-On (SSO) using Laravel Socialite, with built-in support for Zitadel and Filament.

**Note:** This is an opinionated, internal project designed primarily to meet the requirements of eighteen73. While it is open-sourced and feedback is welcome, its development is driven by our specific needs and workflows.

## Features

- Automatic Socialite provider registration for Zitadel.
- Dedicated `social_accounts` table to map SSO identities to local users.
- Configurable user resolution and auto-creation logic.
- Automatic integration with Filament login forms via render hooks.
- Support for multiple SSO connections per user.

## Installation

You can install the package via composer:

```bash
composer require eighteen73/laravel-sso
```

You should publish the migration and the config file with:

```bash
php artisan vendor:publish --tag="sso-config"
php artisan vendor:publish --tag="sso-migrations"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

The configuration file is located at `config/sso.php`. You can customise the following:

- `provider`: The Socialite driver to use (defaulting to `zitadel`).
- `auto_create_users`: Whether to create a new local user if the SSO email is not found.
- `redirect_path`: The path to redirect to after a successful login.
- `user_resolver`: The action class used to map SSO data to a local user.
- `filament`: Settings for Filament integration, including which panels to display the SSO button on.

## Customising User Resolution

If you need to perform additional logic when a user is resolved (such as assigning roles or updating custom attributes), you can create a custom action that implements `Eighteen73\SSO\Actions\ResolveUserContract` and update the `user_resolver` in your config.

```php
namespace App\Actions;

use Eighteen73\SSO\Actions\ResolveUser;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomResolveUser extends ResolveUser
{
    public function resolve(string $provider, ProviderUser $ssoUser): Authenticatable
    {
        $user = parent::resolve($provider, $ssoUser);
        
        // Add your custom logic here
        
        return $user;
    }
}
```

## Testing

The package uses Pest for testing. You can run the tests with:

```bash
./vendor/bin/pest
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
