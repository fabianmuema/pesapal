<?php

namespace Fabian\Pesapal;

use Illuminate\Support\ServiceProvider;

class PesapalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/pesapal.php' => config_path('pesapal.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes.php';
        $this->app->make('Knox\Pesapal\PesapalAPIController');
        $this->app->bind('pesapal', function()
        {
            return new \Knox\Pesapal\Pesapal;
        });
    }
}
