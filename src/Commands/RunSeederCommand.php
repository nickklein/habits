<?php

namespace NickKlein\Habits\Commands;

use NickKlein\Habits\Seeders\HabitSeeder;
use NickKlein\Habits\Seeders\HabitTimeSeeder;
use NickKlein\Habits\Seeders\HabitUserTableSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunSeederCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:habits-seeder';

    /**
     * The console Clean up user related things.
     *
     * @var string
     */
    protected $description = 'Runs Seeder for Habits';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Artisan::call('db:seed', ['--class' => HabitSeeder::class]);
        $this->info('HabitSeeder executed successfully.');

        Artisan::call('db:seed', ['--class' => HabitTimeSeeder::class]);
        $this->info('HabitTimeSeeder executed successfully.');

        Artisan::call('db:seed', ['--class' => HabitUserTableSeeder::class]);
        $this->info('HabitUserTableSeeder executed successfully.');
    }
}
