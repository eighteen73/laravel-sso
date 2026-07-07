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
        $loginParameters = array_filter(
            $this->loginParameters($provider),
            fn ($value) => $value !== null && $value !== ''
        );

        $driver = Socialite::driver($provider);

        if ($loginParameters !== []) {
            $driver->with($loginParameters);
        }

        return $driver->redirect();
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

        if ($provider === 'zitadel' && config('sso.zitadel.enforce_mfa', false) && ! $this->hasMfaAuthenticationMethod($ssoUser->accessTokenResponseBody['id_token'] ?? null)) {
            event(new SSOLoginFailed($provider, $ssoUser, new SSOException('Multi-factor authentication is required.')));

            return redirect('/login')->withErrors(['sso' => 'Multi-factor authentication is required.']);
        }

        // Store the ID token for global logout
        if (! empty($ssoUser->accessTokenResponseBody['id_token'])) {
            session()->put('sso_id_token', $ssoUser->accessTokenResponseBody['id_token']);
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

    public function logout(Request $request)
    {
        $provider = config('sso.provider', 'zitadel');
        $idToken = session('sso_id_token');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($idToken && $provider === 'zitadel') {
            try {
                // Zitadel Socialite provider supports getLogoutUrl
                $logoutUrl = Socialite::driver($provider)->getLogoutUrl($idToken);

                return redirect()->away($logoutUrl);
            } catch (\Exception $e) {
                // Fallback if something goes wrong
            }
        }

        return redirect('/');
    }

    private function hasMfaAuthenticationMethod(?string $idToken): bool
    {
        if (! $idToken) {
            return false;
        }

        $parts = explode('.', $idToken);

        if (count($parts) < 2) {
            return false;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (! is_array($payload) || ! isset($payload['amr'])) {
            return false;
        }

        $methods = is_array($payload['amr']) ? $payload['amr'] : explode(' ', (string) $payload['amr']);

        return in_array('mfa', $methods, true);
    }

    private function loginParameters(string $provider): array
    {
        $parameters = config('sso.login_parameters', []);

        if ($provider !== 'zitadel' || ! config('sso.zitadel.select_account', false)) {
            return $parameters;
        }

        $prompts = array_filter(explode(' ', (string) ($parameters['prompt'] ?? '')));

        if (! in_array('select_account', $prompts, true)) {
            $prompts[] = 'select_account';
        }

        $parameters['prompt'] = implode(' ', $prompts);

        return $parameters;
    }
}
