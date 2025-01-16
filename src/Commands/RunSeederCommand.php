<?php

namespace NickKlein\Habits\Commands;

use NickKlein\Habits\Seeders\HabitSeeder;
use NickKlein\Habits\Seeders\HabitTimeSeeder;
use NickKlein\Habits\Seeders\HabitUserTableSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use NickKlein\Habits\Models\HabitUser;

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
    public function __construct(public HabitSeeder $habitSeeder, public HabitTimeSeeder $habitTimeSeeder, public HabitUserTableSeeder $habitUserSeeder)
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
        // Add habits
        $this->habitSeeder->run(8);

        // Add habit times
        $this->habitTimeSeeder->run([], 15);

        // connect relationships
        $this->habitUserSeeder->run([]);
    }
}
