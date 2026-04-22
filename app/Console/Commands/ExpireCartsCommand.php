<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cart;

class ExpireCartsCommand extends Command
{
    protected $signature = 'carts:expire';

    protected $description = 'Expire carts that have passed their expiration time';

    public function handle(): int
    {
        $expiredCarts = Cart::where('expires_at', '<', now())->get();

        foreach ($expiredCarts as $cart) {
            $cart->delete();
        }

        $this->info('Expired carts have been cleared.');

        return Command::SUCCESS;
    }
}