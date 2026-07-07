<?php

namespace Eighteen73\SSO\Tests;

use Eighteen73\SSO\Concerns\HasSsoAccounts;
use Eighteen73\SSO\Contracts\HasSsoAccounts as HasSsoAccountsContract;
use Eighteen73\SSO\SSOServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use SocialiteProviders\Manager\ServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            SSOServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('app.key', 'base64:JjR0yY1sNf5mK/A0X2+D2D+y2G/2lZ3N8V7lU4jFwG8=');
        config()->set('auth.providers.users.model', TestUser::class);
    }

    protected function defineDatabaseMigrations()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

class TestUser extends User
{
    use HasSsoAccounts;

    protected $table = 'users';

    protected $guarded = [];
}

class SsoRequiredTestUser extends TestUser implements HasSsoAccountsContract
{
    public function requiresSsoLogin(): bool
    {
        return true;
    }
}
