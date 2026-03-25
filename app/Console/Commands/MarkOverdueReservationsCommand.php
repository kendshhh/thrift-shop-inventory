<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Console\Command;

class MarkOverdueReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark completed reservations as overdue when pickup date has passed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $affected = Reservation::query()
            ->where('status', ReservationStatus::COMPLETED->value)
            ->where('payment_status', PaymentStatus::COMPLETED->value)
            ->whereDate('pickup_date', '<', now()->toDateString())
            ->update([
                'status' => ReservationStatus::OVERDUE->value,
                'updated_at' => now(),
            ]);

        $this->info("Marked {$affected} reservation(s) as overdue.");

        return self::SUCCESS;
    }
}
