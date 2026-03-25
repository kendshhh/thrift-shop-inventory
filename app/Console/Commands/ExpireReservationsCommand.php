<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending reservations that passed the 48-hour payment window.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = 0;

        Reservation::query()
            ->where('status', ReservationStatus::PENDING->value)
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use (&$expiredCount): void {
                foreach ($reservations as $reservation) {
                    DB::transaction(function () use ($reservation, &$expiredCount): void {
                        $lockedReservation = Reservation::query()
                            ->lockForUpdate()
                            ->with('reservationItems.item')
                            ->find($reservation->id);

                        if (
                            $lockedReservation === null
                            || $lockedReservation->status !== ReservationStatus::PENDING
                            || now()->lessThan($lockedReservation->expires_at)
                        ) {
                            return;
                        }

                        foreach ($lockedReservation->reservationItems as $lineItem) {
                            if ($lineItem->item === null) {
                                continue;
                            }

                            $decrementBy = min($lineItem->item->reserved_quantity, $lineItem->quantity);

                            if ($decrementBy > 0) {
                                $lineItem->item->decrement('reserved_quantity', $decrementBy);
                            }
                        }

                        $lockedReservation->status = ReservationStatus::EXPIRED;

                        if ($lockedReservation->payment_status === PaymentStatus::PENDING) {
                            $lockedReservation->payment_status = PaymentStatus::OVERDUE;
                        }

                        $lockedReservation->save();
                        $expiredCount++;
                    });
                }
            });

        $this->info("Expired {$expiredCount} reservation(s).");

        return self::SUCCESS;
    }
}
