<?php

namespace App\Support;

class StatusBadge
{
    /**
     * @return array{background:string,color:string,border:string}
     */
    public static function palette(string $type, mixed $value): array
    {
        $class = self::for($type, $value);

        return match ($class) {
            'status-badge-pending' => ['background' => 'rgba(245, 158, 11, 0.2)', 'color' => '#92400e', 'border' => 'rgba(245, 158, 11, 0.3)'],
            'status-badge-pending-subtle' => ['background' => 'rgba(254, 243, 199, 0.6)', 'color' => '#92400e', 'border' => 'rgba(245, 158, 11, 0.24)'],
            'status-badge-ready' => ['background' => 'rgba(14, 165, 233, 0.2)', 'color' => '#0c4a6e', 'border' => 'rgba(14, 165, 233, 0.28)'],
            'status-badge-ready-subtle' => ['background' => 'rgba(219, 234, 254, 0.58)', 'color' => '#1d4ed8', 'border' => 'rgba(96, 165, 250, 0.24)'],
            'status-badge-success' => ['background' => 'rgba(22, 163, 74, 0.2)', 'color' => '#166534', 'border' => 'rgba(22, 163, 74, 0.28)'],
            'status-badge-success-subtle' => ['background' => 'rgba(220, 252, 231, 0.62)', 'color' => '#166534', 'border' => 'rgba(74, 222, 128, 0.24)'],
            'status-badge-overdue' => ['background' => 'rgba(220, 38, 38, 0.2)', 'color' => '#991b1b', 'border' => 'rgba(220, 38, 38, 0.28)'],
            'status-badge-overdue-subtle' => ['background' => 'rgba(254, 226, 226, 0.62)', 'color' => '#991b1b', 'border' => 'rgba(248, 113, 113, 0.24)'],
            'status-badge-new' => ['background' => 'rgba(37, 99, 235, 0.22)', 'color' => '#1d4ed8', 'border' => 'rgba(37, 99, 235, 0.28)'],
            'status-badge-admin' => ['background' => 'rgba(30, 41, 59, 0.22)', 'color' => '#0f172a', 'border' => 'rgba(15, 23, 42, 0.24)'],
            'status-badge-archived' => ['background' => 'rgba(51, 65, 85, 0.24)', 'color' => '#e2e8f0', 'border' => 'rgba(30, 41, 59, 0.32)'],
            'status-badge-unread' => ['background' => 'rgba(220, 38, 38, 0.22)', 'color' => '#991b1b', 'border' => 'rgba(220, 38, 38, 0.28)'],
            'status-badge-read' => ['background' => 'rgba(100, 116, 139, 0.2)', 'color' => '#334155', 'border' => 'rgba(71, 85, 105, 0.24)'],
            'status-badge-muted-subtle' => ['background' => 'rgba(241, 245, 249, 0.66)', 'color' => '#475569', 'border' => 'rgba(203, 213, 225, 0.28)'],
            default => ['background' => 'rgba(100, 116, 139, 0.2)', 'color' => '#334155', 'border' => 'rgba(71, 85, 105, 0.24)'],
        };
    }

    public static function for(string $type, mixed $value): string
    {
        $normalized = is_string($value) ? strtolower($value) : '';

        return match ($type) {
            'reservation' => match ($normalized) {
                'pending' => 'status-badge-pending',
                'ready_for_pickup' => 'status-badge-ready',
                'completed' => 'status-badge-success',
                'overdue' => 'status-badge-overdue',
                'expired' => 'status-badge-muted',
                default => 'status-badge-muted',
            },
            'payment' => match ($normalized) {
                'pending' => 'status-badge-pending',
                'completed' => 'status-badge-success',
                'overdue' => 'status-badge-overdue',
                default => 'status-badge-muted',
            },
            'inventory' => match ($normalized) {
                'active' => 'status-badge-success',
                'out_of_stock' => 'status-badge-muted',
                'archived' => 'status-badge-archived',
                default => 'status-badge-muted',
            },
            'request' => match ($normalized) {
                'pending' => 'status-badge-pending',
                'approved' => 'status-badge-success',
                'declined' => 'status-badge-overdue',
                default => 'status-badge-muted',
            },
            'notification-state' => match ($normalized) {
                'unread' => 'status-badge-unread',
                'read' => 'status-badge-read',
                default => 'status-badge-read',
            },
            'notification-event' => match ($normalized) {
                'new_reservation' => 'status-badge-new',
                'customer_request_submitted' => 'status-badge-pending',
                'status_changed' => 'status-badge-ready',
                'low_stock' => 'status-badge-pending',
                'overdue_reservation' => 'status-badge-overdue',
                default => 'status-badge-muted',
            },
            'user-role' => match ($normalized) {
                'admin' => 'status-badge-admin',
                'customer' => 'status-badge-new',
                default => 'status-badge-muted',
            },
            'user-state' => match ($normalized) {
                'active' => 'status-badge-success',
                'suspended' => 'status-badge-overdue',
                default => 'status-badge-muted',
            },
            default => 'status-badge-muted',
        };
    }

    public static function label(mixed $value): string
    {
        $normalized = trim(str_replace('_', ' ', (string) $value));

        if ($normalized === '') {
            return 'Unknown';
        }

        return ucwords($normalized);
    }
}
