<?php

namespace App\Providers;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use App\Services\Wikibase\WikibaseFormatterModels;
use App\Services\Wikibase\WikibaseOptimizer;
use App\Services\Wikibase\WikibaseService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
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
            // First check for custom Wikibase URL (higher priority)
            $baseUrl = config('wikidata.custom_wikibase_url');
            
            // If sandbox mode is enabled, use sandbox URL
            if (empty($baseUrl) && config('wikidata.sandbox_mode', false)) {
                $baseUrl = config('wikidata.sandbox_api_url', 'https://test.wikidata.org/w/api.php');
            }
            
            // If no custom URL, use regular Wikidata API URL
            if (empty($baseUrl)) {
                $baseUrl = config(
                    "wikidata.api_url",
                    "https://www.wikidata.org/w/api.php"
                );
            }
            
            // Log which API URL is being used
            if (config('wikidata.log_api_urls', false)) {
                Log::info("WikibaseService initialized with API URL: {$baseUrl}");
            }

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
