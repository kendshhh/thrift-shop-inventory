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
            'status-badge-pending' => ['background' => '#f59e0b', 'color' => '#1f2937', 'border' => '#d97706'],
            'status-badge-pending-subtle' => ['background' => '#fef3c7', 'color' => '#92400e', 'border' => '#f59e0b'],
            'status-badge-ready' => ['background' => '#0ea5e9', 'color' => '#082f49', 'border' => '#0284c7'],
            'status-badge-ready-subtle' => ['background' => '#dbeafe', 'color' => '#1d4ed8', 'border' => '#60a5fa'],
            'status-badge-success' => ['background' => '#16a34a', 'color' => '#ffffff', 'border' => '#15803d'],
            'status-badge-success-subtle' => ['background' => '#dcfce7', 'color' => '#166534', 'border' => '#4ade80'],
            'status-badge-overdue' => ['background' => '#dc2626', 'color' => '#ffffff', 'border' => '#b91c1c'],
            'status-badge-overdue-subtle' => ['background' => '#fee2e2', 'color' => '#991b1b', 'border' => '#f87171'],
            'status-badge-new' => ['background' => '#2563eb', 'color' => '#ffffff', 'border' => '#1d4ed8'],
            'status-badge-admin' => ['background' => '#1e293b', 'color' => '#ffffff', 'border' => '#0f172a'],
            'status-badge-archived' => ['background' => '#334155', 'color' => '#ffffff', 'border' => '#1e293b'],
            'status-badge-unread' => ['background' => '#dc2626', 'color' => '#ffffff', 'border' => '#b91c1c'],
            'status-badge-read' => ['background' => '#64748b', 'color' => '#ffffff', 'border' => '#475569'],
            'status-badge-muted-subtle' => ['background' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1'],
            default => ['background' => '#64748b', 'color' => '#ffffff', 'border' => '#475569'],
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
