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
    }
}