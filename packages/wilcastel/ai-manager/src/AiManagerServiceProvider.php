<?php

namespace Wilcastel\AiManager;

use Illuminate\Support\ServiceProvider;

class AiManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai-manager.php', 'ai-manager'
        );

        $this->app->singleton('ai-manager', function ($app) {
            return new \Wilcastel\AiManager\Services\AiManagerService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish Config
        $this->publishes([
            __DIR__.'/../config/ai-manager.php' => config_path('ai-manager.php'),
        ], 'ai-manager-config');

        // Publish Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
       
        // Publish Scripts (Ollama Bridge)
        $this->publishes([
            __DIR__.'/../resources/scripts' => base_path('scripts'),
        ], 'ai-manager-scripts');

        // Load Routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-manager');

        // Publish Views (Optional)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/ai-manager'),
        ], 'ai-manager-views');
    }
}
