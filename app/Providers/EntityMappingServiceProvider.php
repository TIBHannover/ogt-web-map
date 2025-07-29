<?php

namespace App\Providers;

use App\Services\EntityMapping\EntityMappingProviderInterface;
use App\Services\EntityMapping\WikidataEntityMappingProvider;
use App\Services\EntityMapping\FactGridEntityMappingProvider;
use Illuminate\Support\ServiceProvider;

class EntityMappingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the Wikidata mapping provider
        $this->app->singleton('entity.mapping.wikidata', function ($app) {
            return new WikidataEntityMappingProvider();
        });
        
        // Register the FactGrid mapping provider
        $this->app->singleton('entity.mapping.factgrid', function ($app) {
            return new FactGridEntityMappingProvider();
        });
        
        // Bind the interface to the appropriate implementation based on config
        $this->app->bind(EntityMappingProviderInterface::class, function ($app) {
            $exportTarget = config('export.target', 'wikidata');
            
            return $app->make("entity.mapping.$exportTarget");
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