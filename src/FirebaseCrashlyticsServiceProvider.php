<?php

namespace Codingwithrk\FirebaseCrashlytics;

use Codingwithrk\FirebaseCrashlytics\Commands\ApplyGradlePluginCommand;
use Illuminate\Support\ServiceProvider;

class FirebaseCrashlyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirebaseCrashlytics::class, function () {
            return new FirebaseCrashlytics();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ApplyGradlePluginCommand::class,
            ]);
        }
    }
}
