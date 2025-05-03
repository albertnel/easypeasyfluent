<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge the custom config file
        $this->mergeConfigFrom(
            config_path('easypeasyfluent.php'),
            'easypeasyfluent'
        );

        // Merge the background jobs config file
        $this->mergeConfigFrom(
            config_path('background-jobs.php'),
            'background-jobs'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
