<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\SteamExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(
            SocialiteWasCalled::class,
            [SteamExtendSocialite::class, 'handle']
        );

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $globalEloAvg = (int) \App\Models\User::avg('points') ?? 0;
            $view->with('globalEloAvg', $globalEloAvg);
        });
    }
}
