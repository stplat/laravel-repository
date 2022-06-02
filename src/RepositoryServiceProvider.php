<?php

namespace Stsp\LaravelRepository;

use Illuminate\Support\ServiceProvider;
use Stsp\LaravelRepository\Console\RepositoryMakeCommand;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(
            'command.laravel-menu.migrate',
            function ($app) {
                return new MigrateCommand($app['files']);
            }
        );

        $this->commands('command.laravel-menu.migrate');

        $this->app->singleton(
            'command.laravel-menu.seed',
            function ($app) {
                return new SeederCommand($app['files']);
            }
        );

        $this->commands('command.laravel-menu.seed');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
    }
}
