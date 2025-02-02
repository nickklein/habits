<?php

namespace NickKlein\Habits;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use NickKlein\Habits\Commands\RunSeederCommand;
use NickKlein\Habits\Middleware\PublicAPI;

class HabitsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->registerMiddleware($this->app->router);
        /*
         * Optional methods to load your package assets
         */
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/Routes/auth.php');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/js' => resource_path('js/Pages/Habits'),
            ], 'assets');
        }

        $this->commands([
            RunSeederCommand::class,
        ]);
    }

    public function registerMiddleware(Router $router)
    {
        $router->aliasMiddleware('publicapi', PublicAPI::class);
    }
}

