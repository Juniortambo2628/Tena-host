<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;

class NotificationService
{
    public static function userCreated(User $user, ?User $creator = null): ?Notification
    {
        return self::create(
            $user->id,
            'user_created',
            'New User Created',
            "A new {$user->role} account has been created for {$user->first_name} {$user->last_name}.",
            ['user_id' => $user->id, 'creator_id' => $creator?->id]
        );
    }

    public static function roleChanged(User $user, string $oldRole, string $newRole, ?User $changedBy = null): ?Notification
    {
        return self::create(
            $user->id,
            'role_changed',
            'Role Updated',
            "Your role has been changed from {$oldRole} to {$newRole}.",
            ['user_id' => $user->id, 'old_role' => $oldRole, 'new_role' => $newRole, 'changed_by' => $changedBy?->id]
        );
    }

    public static function guestConnected(string $propertyName, int $count = 1): void
    {
        $property = Property::where('name', $propertyName)->first();
        if (! $property) {
            return;
        }

        self::create(
            $property->user_id,
            'guest_connected',
            'New Guest Connected',
            "{$count} new guest(s) connected to {$propertyName}.",
            ['property_id' => $property->id, 'count' => $count]
        );
    }

    public static function campaignCompleted(int $campaignId, int $sentCount): void
    {
        $campaign = Campaign::find($campaignId);
        if (! $campaign) {
            return;
        }

        self::create(
            $campaign->user_id,
            'campaign_completed',
            'Campaign Completed',
            "Campaign \"{$campaign->name}\" has been sent to {$sentCount} guests.",
            ['campaign_id' => $campaignId, 'sent_count' => $sentCount]
        );
    }

    public static function orderPlaced(int $orderId): void
    {
        $order = Order::with('guest.property')->find($orderId);
        if (! $order || ! $order->guest?->property) {
            return;
        }

        self::create(
            $order->guest->property->user_id,
            'order_placed',
            'New Order Received',
            "A new order has been placed by {$order->guest->first_name} {$order->guest->last_name}.",
            ['order_id' => $orderId, 'property_id' => $order->guest->property->id]
        );
    }

    public static function systemAlert(string $title, string $message, int $userId): ?Notification
    {
        return self::create(
            $userId,
            'system_alert',
            $title,
            $message
        );
    }

    private static function create(int $userId, string $type, string $title, string $message, array $data = []): ?Notification
    {
        $user = User::find($userId);
        if (! $user) {
            return null;
        }

        // Check notification preferences
        $category = self::categoryFromType($type);
        $preference = NotificationPreference::where('user_id', $userId)
            ->where('category', $category)
            ->first();

        // If preference exists and dashboard is disabled, skip
        if ($preference && ! $preference->dashboard_enabled) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    private static function categoryFromType(string $type): string
    {
        $map = [
            'user_created' => 'user',
            'role_changed' => 'user',
            'guest_connected' => 'user',
            'campaign_completed' => 'user',
            'order_placed' => 'user',
            'system_alert' => 'system',
        ];

        return $map[$type] ?? 'system';
    }
}
