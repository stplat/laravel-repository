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
            'command.laravel-repository.make',
            function ($app) {
                return new RepositoryMakeCommand($app['files']);
            }
        );

        $this->commands('command.laravel-repository.make');
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
