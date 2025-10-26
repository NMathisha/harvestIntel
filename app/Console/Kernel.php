<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // Explicit registration
    protected $commands = [
        \App\Console\Commands\TrainModels::class,
        \App\Console\Commands\PredictAll::class, // optional
    ];

    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    protected function commands(): void
    {
        // You can keep this to auto-load any additional commands
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
