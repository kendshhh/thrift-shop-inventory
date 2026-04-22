<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reservations:expire')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('reservations:mark-overdue')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        $schedule->command('carts:expire')->hourly();

        // Add a scheduled task to release expired reservations
        $schedule->call(function () {
            CartItem::where('expires_at', '<', now())->each(function ($cartItem) {
                $user = $cartItem->cart->user;
                $user->notify(new HoldExpiredNotification($cartItem->item->name));
                $cartItem->delete();
                Item::where('id', $cartItem->item_id)->update(['status' => 'available']);
            });
        })->everyMinute();
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
