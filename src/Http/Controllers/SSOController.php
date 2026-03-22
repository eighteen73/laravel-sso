<?php

namespace Eighteen73\SSO\Http\Controllers;

use Eighteen73\SSO\Actions\ResolveUserContract;
use Eighteen73\SSO\Events\SSOLoginFailed;
use Eighteen73\SSO\Exceptions\SSOException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SSOController extends Controller
{
    public function login()
    {
        $provider = config('sso.provider', 'zitadel');
        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request)
    {
        $provider = config('sso.provider', 'zitadel');
        
        try {
            $ssoUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            event(new SSOLoginFailed($provider, null, $e));
            return redirect('/login')->withErrors(['sso' => 'Authentication failed or was cancelled.']);
        }

        /** @var ResolveUserContract $resolver */
        $resolver = app(ResolveUserContract::class);
        
        try {
            $user = $resolver->resolve($provider, $ssoUser);
        } catch (SSOException $e) {
            event(new SSOLoginFailed($provider, $ssoUser, $e));
            return redirect('/login')->withErrors(['sso' => $e->getMessage()]);
        } catch (\Exception $e) {
            event(new SSOLoginFailed($provider, $ssoUser, $e));
            return redirect('/login')->withErrors(['sso' => 'An unexpected error occurred during login.']);
        }

        Auth::login($user);

        return redirect()->intended(config('sso.redirect_path', '/'));
    }
}
