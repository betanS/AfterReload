<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event; // Importante
use SocialiteProviders\Manager\SocialiteWasCalled; // Importante
use SocialiteProviders\Steam\SteamExtendSocialite; // Importante

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registramos el listener de Steam manualmente
        Event::listen(
            SocialiteWasCalled::class,
            [SteamExtendSocialite::class, 'handle']
        );

        // Global Elo average for topbar
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $globalEloAvg = (int) \App\Models\User::avg('rank_points') ?? 0;
            $view->with('globalEloAvg', $globalEloAvg);
        });
    }
}