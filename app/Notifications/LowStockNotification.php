<?php

namespace App\Notifications;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Item $item)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => 'Stock for "' . $this->item->name . '" is low (only ' . $this->item->availableQuantity() . ' left).',
            'item_id' => $this->item->id,
            'item_name' => $this->item->name,
            'available_quantity' => $this->item->availableQuantity(),
            'action_url' => route('admin.inventory.edit', $this->item),
            'action_label' => 'Restock Item',
        ];
    }
}
