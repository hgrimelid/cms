<?php

namespace Statamic\StaticCaching;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Statamic\StaticCaching\NoCache\NoCacheManager;
use Statamic\StaticCaching\Replacers\NoCacheReplacer;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $this->app->singleton(StaticCacheManager::class, function ($app) {
            return new StaticCacheManager($app);
        });

        $this->app->bind(Cacher::class, function ($app) {
            return $app[StaticCacheManager::class]->driver();
        });

        $this->app->bind(Invalidator::class, function ($app) {
            $class = config('statamic.static_caching.invalidation.class') ?? DefaultInvalidator::class;

            return $app[$class];
        });

        $this->app->bind(DefaultInvalidator::class, function ($app) {
            return new DefaultInvalidator(
                $app[Cacher::class],
                $app['config']['statamic.static_caching.invalidation.rules']
            );
        });

        $this->app->singleton(NoCacheManager::class, function ($app) {
            return new NoCacheManager;
        });

        $this->app->beforeResolving(NoCacheManager::class, function ($manager) {
            $configKey = 'statamic.static_caching.replacers';
            $replacers = config($configKey);
            $replacers[] = NoCacheReplacer::class;
            config([$configKey => $replacers]);
        });
    }

    public function boot()
    {
        if (config('statamic.static_caching.strategy')) {
            Event::subscribe(Invalidate::class);
        }
    }
}
