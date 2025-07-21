<?php

namespace App\Providers;

use App\Services\Wikidata\WikidataFormatterModels;
use Illuminate\Support\ServiceProvider;

class WikidataServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register WikidataFormatterModels as a transient service (new instance each time)
        $this->app->bind(WikidataFormatterModels::class, function ($app) {
            return new WikidataFormatterModels();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}