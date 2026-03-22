<?php

namespace Eighteen73\SSO;

use Eighteen73\SSO\Actions\ResolveUserContract;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Zitadel\Provider;

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

        // Automatically inject the zitadel config if it's missing from the host app
        if (! config()->has('services.zitadel')) {
            config(['services.zitadel' => config('sso.config')]);
        }

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('zitadel', Provider::class);
        });

        $this->registerFilamentIntegration();
    }

    protected function registerFilamentIntegration(): void
    {
        if (! class_exists(Filament::class) || ! config('sso.filament.enabled', true)) {
            return;
        }

        $panels = config('sso.filament.panels', ['*']);

        if ($panels === ['*']) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): View => view('sso::login-button')
            );
        } else {
            FilamentView::registerRenderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): View => view('sso::login-button'),
                $panels
            );
        }
    }
}
