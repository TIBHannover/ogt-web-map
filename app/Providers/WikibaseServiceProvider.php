<?php

namespace App\Providers;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use App\Services\Wikibase\WikibaseFormatterModels;
use App\Services\Wikibase\WikibaseOptimizer;
use App\Services\Wikibase\WikibaseService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class WikibaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the Wikibase formatter
        $this->app->singleton(WikibaseFormatterModels::class, function ($app) {
            return new WikibaseFormatterModels(
                $app->make(EntityMappingProviderInterface::class),
            );
        });

        // Register the Wikibase optimizer
        $this->app->singleton(WikibaseOptimizer::class, function ($app) {
            return new WikibaseOptimizer(
                $app->make(EntityMappingProviderInterface::class),
            );
        });

        // Register the Wikibase service
        $this->app->singleton(WikibaseService::class, function ($app) {
            $baseUrl = config(
                "services.wikibase.api_url",
                "https://test.wikidata.org/w/api.php",
            );

            return new WikibaseService(
                new Client(),
                $app->make(WikibaseFormatterModels::class),
                $app->make(WikibaseOptimizer::class),
                $app->make(EntityMappingProviderInterface::class),
                $baseUrl,
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // No boot actions needed
    }
}
