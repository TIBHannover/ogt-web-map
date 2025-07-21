<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class UrlServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Nur weitermachen, wenn APP_URL gesetzt ist
        if (!env("APP_URL")) {
            return;
        }

        // Basis-URL für die Anwendung setzen
        URL::forceRootUrl(env("APP_URL"));

        // Schema automatisch basierend auf der URL erkennen
        if (str_starts_with(env("APP_URL"), "https://")) {
            // Test/Produktion: HTTPS erzwingen
            URL::forceScheme("https");
        } else {
            // Lokale Entwicklung: HTTP verwenden (Standard)
            URL::forceScheme("http");
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
