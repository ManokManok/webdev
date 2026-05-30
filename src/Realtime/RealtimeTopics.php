<?php

namespace App\Realtime;

/**
 * Mercure topic URLs (must be absolute IRIs).
 */
final class RealtimeTopics
{
    public const BASE = 'https://onins.app';

    public static function products(): string
    {
        return self::BASE.'/products';
    }

    public static function customerOrders(int $userId): string
    {
        return sprintf('%s/customer/%d/orders', self::BASE, $userId);
    }

    public static function customerBookings(int $userId): string
    {
        return sprintf('%s/customer/%d/bookings', self::BASE, $userId);
    }

    public static function customerPayments(int $userId): string
    {
        return sprintf('%s/customer/%d/payments', self::BASE, $userId);
    }

    public static function adminOrders(): string
    {
        return self::BASE.'/admin/orders';
    }

    public static function adminBookings(): string
    {
        return self::BASE.'/admin/bookings';
    }

    public static function adminPayments(): string
    {
        return self::BASE.'/admin/payments';
    }

    /** @return string[] */
    public static function forCustomer(int $userId): array
    {
        return [
            self::products(),
            self::customerOrders($userId),
            self::customerBookings($userId),
            self::customerPayments($userId),
        ];
    }

    /** @return string[] */
    public static function forAdmin(): array
    {
        return [
            self::adminOrders(),
            self::adminBookings(),
            self::adminPayments(),
        ];
    }
}
