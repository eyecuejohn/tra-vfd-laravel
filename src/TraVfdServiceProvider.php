<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd;

use Illuminate\Support\ServiceProvider;

class TraVfdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tra-vfd.php', 'tra-vfd');

        $this->app->singleton('tra-vfd', function ($app) {
            return new TraVfdManager($app);
        });

        $this->commands([
        \Eyecuejohn\TraVfd\Commands\ProcessTraQueue::class,
        \Eyecuejohn\TraVfd\Commands\TestTraIntegration::class, // Added here
        ]);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish Config
            $this->publishes([
                __DIR__ . '/../config/tra-vfd.php' => config_path('tra-vfd.php'),
            ], 'tra-vfd-config');

            // Publish Views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/tra-vfd'),
            ], 'tra-vfd-views');

            // Publish Assets (The TRA SVG Logo)
            $this->publishes([
                __DIR__ . '/../resources/assets' => public_path('vendor/tra-vfd'),
            ], 'tra-vfd-assets');

            $this->publishes([
                 __DIR__ . '/../database' => database_path('migrations')
            ], 'tra-vfd-migrations');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tra-vfd');
    }
}