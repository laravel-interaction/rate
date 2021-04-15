<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate;

use Illuminate\Support\ServiceProvider;

class RateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->getConfigPath() => config_path('rate.php'),
            ], 'rate-config');
            $this->publishes([
                $this->getMigrationsPath() => database_path('migrations'),
            ], 'rate-migrations');
            if ($this->shouldLoadMigrations()) {
                $this->loadMigrationsFrom($this->getMigrationsPath());
            }
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'rate');
    }

    protected function getConfigPath(): string
    {
        return __DIR__ . '/../config/rate.php';
    }

    protected function getMigrationsPath(): string
    {
        return __DIR__ . '/../migrations';
    }

    private function shouldLoadMigrations(): bool
    {
        return (bool) config('rate.load_migrations');
    }
}
