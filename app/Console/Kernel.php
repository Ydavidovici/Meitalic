<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * If you generated a BackupDatabase command, add it here.
     */
    protected $commands = [
        // \App\Console\Commands\BackupDatabase::class,
    ];

    /**
     * Define your scheduled tasks.
     */
    protected function schedule(Schedule $schedule)
    {
        // your DB backup job
        $schedule->command('backup:database')->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        // auto-load any commands in app/Console/Commands
        $this->load(__DIR__ . '/Commands');

        // you can leave this in even if you don't use console routes
        require base_path('routes/console.php');
    }
}
