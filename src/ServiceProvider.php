<?php

namespace Luminee\Base;

use Illuminate\Support\ServiceProvider as _ServiceProvider;

class ServiceProvider extends _ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.luminee.base.provider.migrate', function () {
            return new \Luminee\Base\Console\Commands\ProviderMigrateCommand();
        });
    
        $this->commands('command.luminee.base.provider.migrate');
        
    }
}