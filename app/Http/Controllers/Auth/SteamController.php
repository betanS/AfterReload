<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SteamController extends Controller
{
    public function redirectToSteam(): RedirectResponse
    {
        if (empty(config('services.steam.client_secret'))) {
            return redirect()->route('welcome')->with('auth_error', 'Falta configurar STEAM_CLIENT_SECRET en el archivo .env.');
        }

        return Socialite::driver('steam')
            ->redirectUrl(route('login.steam.callback'))
            ->redirect();
    }

    public function handleSteamCallback(): RedirectResponse
    {
        try {
            $steamUser = Socialite::driver('steam')
                ->redirectUrl(route('login.steam.callback'))
                ->user();

            $nickname = $steamUser->getNickname() ?: 'Steam User';
            $realName = $steamUser->getName();

            $user = User::updateOrCreate(
                ['steam_id' => $steamUser->getId()],
                [
                    'name' => $nickname,
                    'steam_nickname' => $nickname,
                    'steam_real_name' => $realName,
                    'avatar' => $steamUser->getAvatar(),
                    'email' => null,
                    'password' => null,
                ]
            );

            Auth::login($user, true);

            return redirect()->intended(route('home'));
        } catch (Throwable $e) {
            Log::error('Steam auth callback failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return redirect()->route('welcome')->with('auth_error', 'No se pudo iniciar sesion con Steam. Verifica STEAM_CLIENT_SECRET y STEAM_REDIRECT_URI.');
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}