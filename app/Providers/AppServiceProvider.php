<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Trust ngrok and other reverse proxies
        \Symfony\Component\HttpFoundation\Request::setTrustedProxies(
            ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'REMOTE_ADDR'],
            \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
            \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
            \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
            \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO
        );

        if (str_contains(request()->getHost(), 'ngrok-free.app')) {
            URL::forceScheme('https');
            URL::forceRootUrl('https://' . request()->getHost());
        }

        // Microsoft-provider registreren bij Socialite (socialiteproviders/microsoft)
        Event::listen(
            \SocialiteProviders\Manager\SocialiteWasCalled::class,
            \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class . '@handle'
        );
    }
}
