<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default Socialite provider to use for SSO authentication.
    |
    */
    'provider' => 'zitadel',

    /*
    |--------------------------------------------------------------------------
    | User Auto-Creation
    |--------------------------------------------------------------------------
    |
    | If true, a new user will be created if their SSO email is not found
    | in the local database. If false, an exception will be thrown.
    |
    */
    'auto_create_users' => true,

    /*
    |--------------------------------------------------------------------------
    | Redirect Path
    |--------------------------------------------------------------------------
    |
    | The path to redirect the user to after a successful SSO login.
    |
    */
    'redirect_path' => '/',

    /*
    |--------------------------------------------------------------------------
    | User Resolver Action
    |--------------------------------------------------------------------------
    |
    | The action class responsible for resolving a local user from the SSO data.
    | Must implement Eighteen73\SSO\Actions\ResolveUserContract.
    |
    */
    'user_resolver' => \Eighteen73\SSO\Actions\ResolveUser::class,

    /*
    |--------------------------------------------------------------------------
    | Filament Integration
    |--------------------------------------------------------------------------
    |
    | Configure how the SSO login button integrates with Filament panels.
    | Set 'panels' to ['*'] to show on all panels, or an array of panel IDs.
    |
    */
    'filament' => [
        'enabled' => true,
        'panels' => ['*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes used for SSO authentication.
    |
    */
    'routes' => [
        'prefix' => 'sso',
        'middleware' => ['web'],
    ],
];
