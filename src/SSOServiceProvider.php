<?php

namespace Eighteen73\SSO;

use Eighteen73\SSO\Actions\ResolveUserContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class SSOServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sso.php', 'sso');

        $this->app->bind(ResolveUserContract::class, config('sso.user_resolver'));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sso');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sso.php' => config_path('sso.php'),
            ], 'sso-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/sso'),
            ], 'sso-views');
        }

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('zitadel', \SocialiteProviders\Zitadel\Provider::class);
        });

        $this->registerFilamentIntegration();
    }

    protected function registerFilamentIntegration(): void
    {
        if (! class_exists(\Filament\Facades\Filament::class) || ! config('sso.filament.enabled', true)) {
            return;
        }

        $panels = config('sso.filament.panels', ['*']);

        if ($panels === ['*']) {
            \Filament\Support\Facades\FilamentView::registerRenderHook(
                \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): \Illuminate\Contracts\View\View => view('sso::login-button')
            );
        } else {
            \Filament\Support\Facades\FilamentView::registerRenderHook(
                \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): \Illuminate\Contracts\View\View => view('sso::login-button'),
                $panels
            );
        }
    }
}
