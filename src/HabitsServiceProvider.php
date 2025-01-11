<?php

namespace NickKlein\Habits;

use Illuminate\Support\ServiceProvider;
use NickKlein\Habits\Commands\RunSeederCommand;

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
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/Routes/auth.php');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');

        $this->commands([
            RunSeederCommand::class,
        ]);
    }
}

