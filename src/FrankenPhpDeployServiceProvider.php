<?php

namespace AsamoahBoateng\FrankenPhpDeploy;

use Illuminate\Support\ServiceProvider;
use AsamoahBoateng\FrankenPhpDeploy\Console\InstallCommand;

class FrankenPhpDeployServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->configurePublishing();
    }

    /**
     * Register the package's artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Configure the publishable resources offered by the package.
     */
    protected function configurePublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/frankenphp.php' => config_path('frankenphp.php'),
            ], ['frankenphp', 'frankenphp-config']);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/frankenphp.php',
            'frankenphp'
        );
    }
}
