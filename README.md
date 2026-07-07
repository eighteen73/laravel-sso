# Eighteen73 SSO

A reusable Laravel package for integrating Single Sign-On (SSO) using Laravel Socialite, with built-in support for Zitadel and Filament.

**Note:** This is an opinionated, internal project designed primarily to meet the requirements of eighteen73. While it is open-sourced and feedback is welcome, its development is driven by our specific needs and workflows.

## Features

- Automatic Socialite provider registration for Zitadel.
- Dedicated `sso_accounts` table to map SSO identities to local users.
- Configurable user resolution with opt-in auto-creation logic.
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
- `auto_create_users`: Whether to create a new local user if the SSO email is not found. This is disabled by default, so unknown SSO users are rejected unless you explicitly opt into provisioning.
- `redirect_path`: The path to redirect to after a successful login.
- `user_resolver`: The action class used to map SSO data to a local user.
- `filament`: Settings for Filament integration, including which panels to display the SSO button on.

## Global Logout

The package provides a `/sso/logout` route that not only logs the user out of your local Laravel application but also securely terminates their global SSO session at Zitadel (or the active provider). By default, the user will be redirected back to your application's home page after logging out of Zitadel.

To customise the return path, ensure you set the `ZITADEL_POST_LOGOUT_REDIRECT_URI` environment variable in your host application:

```env
ZITADEL_POST_LOGOUT_REDIRECT_URI=https://your-app.com/logged-out
```

## Customising User Resolution

By default, the package links an SSO identity to an existing local user by matching the SSO email address. If no local user exists, login fails unless `auto_create_users` is explicitly enabled.

The built-in auto-creation path only fills common `name`, `email`, and `password` attributes. If your application requires additional columns such as `first_name`, `last_name`, `is_enabled`, `role`, tenant IDs, or any other app-specific state, create a custom action that implements `Eighteen73\SSO\Actions\ResolveUserContract` and update the `user_resolver` in your config.

You can also extend the default resolver if you only need to add behavior around the package's built-in linking logic:

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
