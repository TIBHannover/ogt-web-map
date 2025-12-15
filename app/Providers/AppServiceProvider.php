<?php

namespace App\Providers;

use App\Http\Clients\FactgridClient;
use App\Http\Clients\WikidataClient;
use App\Services\EntityMapping\FactgridEntityMappingProvider;
use App\Services\EntityMapping\WikidataEntityMappingProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Hier können Policies definiert werden
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Wikidata and FactGrid clients
        $this->app->singleton(WikidataClient::class, function ($app) {
            return new WikidataClient();
        });

        $this->app->singleton(FactgridClient::class, function ($app) {
            return new FactgridClient();
        });

        // Register entity mapping providers
        $this->app->singleton(WikidataEntityMappingProvider::class, function (
            $app,
        ) {
            return new WikidataEntityMappingProvider();
        });

        $this->app->singleton(FactgridEntityMappingProvider::class, function (
            $app,
        ) {
            return new FactgridEntityMappingProvider();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
