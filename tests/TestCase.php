<?php

namespace NickKlein\Habits\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use NickKlein\Habits\HabitsServiceProvider;
use NickKlein\Habits\Tests\TestModels\User;
use Orchestra\Testbench\TestCase as Orchestra;
use NickKlein\Habits\Seeders\HabitSeeder;
use NickKlein\Habits\Seeders\HabitTimeSeeder;
use NickKlein\Habits\Seeders\HabitUserTableSeeder;

class TestCase extends Orchestra
{
    protected array $seeders = [
        HabitSeeder::class,
        HabitTimeSeeder::class,
        HabitUserTableSeeder::class,
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->runMigrations();
        $this->truncateTables();
        $this->seedData();
    }
    
    protected function truncateTables()
    {
        DB::table('habit_times')->truncate();
        DB::table('habit_user')->truncate();
        DB::table('habits')->truncate();
        DB::table('habit_times_tags')->truncate();
    }

    public function runMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('timezone')->default('UTC');  // You can default it to 'UTC' or any other default value
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }
    public function seedData()
    {
        $user = User::find(1); 
        if ($user === null) {
            $user = User::create([
                'name' => 'nick',
                'email' => '1294247+nickklein@users.noreply.github.com',
                'password' => Hash::make('password'), 
                'timezone' => 'America/Los_Angeles',
            ]);
        }

        foreach ($this->seeders as $seeder) {
            $this->app->make($seeder)->run();
        }
    }
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
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function tearDown(): void
    {
        $this->truncateTables();
        parent::tearDown();
    }
}
