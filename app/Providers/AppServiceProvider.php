<?php

namespace App\Providers;

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
        // Register helper classes if needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
