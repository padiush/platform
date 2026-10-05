<?php

namespace App\Console;

use App\Models\SystemRun;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sitemap:generate')->dailyAt('00:00');

        // Parts of resumable uploads nobody came back for
        // (docs/decisions/0012-resumable-media-upload.md).
        $schedule->command('media:abort-stale-uploads')->dailyAt('03:00');

        // A heartbeat, so the admin panel can tell whether the scheduler, and
        // so the jobs above, are running at all.
        $schedule->call(fn () => SystemRun::mark(SystemRun::SCHEDULER))
            ->everyMinute()
            ->name('system-heartbeat');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
