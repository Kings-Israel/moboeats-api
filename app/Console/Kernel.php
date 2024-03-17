<?php

namespace App\Console;

use App\Jobs\Payouts;
use App\Jobs\ReassignOrder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('sanctum:prune-expired --hours=24')->daily();
        $schedule->job(new ReassignOrder)->everyMinute();
        $schedule->job(new Payouts)->weeklyOn(7, '08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
