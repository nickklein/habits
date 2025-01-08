<?php

namespace NickKlein\Habits;

use Illuminate\Support\ServiceProvider;
use NickKlein\Habits\Commands\RunSeederCommand;
use Illuminate\Contracts\Routing\Registrar as Router;

class HabitsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        $this->loadRoutesFrom(__DIR__ . '/Routes/auth.php');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');

        // Publish 
        //
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/assets/' => resource_path('js/Pages/Packages/Habits'),
            ], 'assets');

            // Pulish python cron folder
            $this->publishes([
                __DIR__ . '/../resources/cron' => base_path('cron'),
            ], 'assets');
        }

        $this->commands([
            RunSeederCommand::class
        ]);
    }
}

