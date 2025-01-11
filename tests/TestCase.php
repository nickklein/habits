<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\HabitsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers for the application.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            HabitsServiceProvider::class,
        ];
    }
    /**
     * Set up the environment for the tests.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', \NickKlein\Habits\Tests\TestModels\User::class);
    }
}
