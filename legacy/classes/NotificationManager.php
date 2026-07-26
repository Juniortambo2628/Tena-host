<?php

/**
 * Notification Manager Class
 * Handles all notification operations
 */

namespace Tena;

class NotificationManager
{
    private \PDO $db;

    public function __construct(\PDO $database)
    {
        $this->db = $database;
    }

    /**
     * Create a new notification
     */
    public function create($data)
    {
        $query = 'INSERT INTO notifications (user_id, type, category, title, message, data, expires_at) 
                  VALUES (:user_id, :type, :category, :title, :message, :data, :expires_at)';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':message', $data['message']);
        $dataJson = $data['data'] ? json_encode($data['data']) : null;
        $stmt->bindParam(':data', $dataJson);
        $stmt->bindParam(':expires_at', $data['expires_at']);

        if ($stmt->execute()) {
            $notificationId = $this->db->lastInsertId();

            // Send real-time notification if user is online
            $this->sendRealtimeNotification($notificationId, $data);

            return $notificationId;
        }

        return false;
    }

    /**
     * Get notifications for a user
     */
    public function getForUser($userId, $limit = 20, $unreadOnly = false)
    {
        $whereClause = 'WHERE (user_id = :user_id OR user_id IS NULL) AND is_archived = FALSE';
        $params = [':user_id' => $userId];

        if ($unreadOnly) {
            $whereClause .= ' AND is_read = FALSE';
        }

        // Add expiration check
        $whereClause .= ' AND (expires_at IS NULL OR expires_at > NOW())';

        $query = "SELECT * FROM notifications $whereClause 
                  ORDER BY created_at DESC 
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId)
    {
        $query = 'UPDATE notifications 
                  SET is_read = TRUE 
                  WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $notificationId);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId)
    {
        $query = 'UPDATE notifications 
                  SET is_read = TRUE 
                  WHERE (user_id = :user_id OR user_id IS NULL) AND is_read = FALSE';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    /**
     * Archive notification
     */
    public function archive($notificationId, $userId)
    {
        $query = 'UPDATE notifications 
                  SET is_archived = TRUE 
                  WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $notificationId);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount($userId)
    {
        $query = 'SELECT COUNT(*) as count 
                  FROM notifications 
                  WHERE (user_id = :user_id OR user_id IS NULL) 
                    AND is_read = FALSE 
                    AND is_archived = FALSE
                    AND (expires_at IS NULL OR expires_at > NOW())';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['count'];
    }

    /**
     * Clean up expired notifications
     */
    public function cleanupExpired()
    {
        $query = 'UPDATE notifications 
                  SET is_archived = TRUE 
                  WHERE expires_at IS NOT NULL AND expires_at < NOW()';

        $stmt = $this->db->prepare($query);

        return $stmt->execute();
    }

    /**
     * Create system notification
     */
    public function createSystemNotification($title, $message, $type = NOTIFICATION_INFO, $data = null)
    {
        return $this->create([
            'user_id' => null,
            'type' => $type,
            'category' => NOTIF_CAT_SYSTEM,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'expires_at' => date('Y-m-d H:i:s', time() + NOTIFICATION_TTL),
        ]);
    }

    /**
     * Create user-specific notification
     */
    public function createUserNotification($userId, $title, $message, $type = NOTIFICATION_INFO, $category = NOTIF_CAT_USER, $data = null)
    {
        return $this->create([
            'user_id' => $userId,
            'type' => $type,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'expires_at' => date('Y-m-d H:i:s', time() + NOTIFICATION_TTL),
        ]);
    }

    /**
     * Send real-time notification via AJAX
     */
    private function sendRealtimeNotification($notificationId, $data)
    {
        // This would typically use WebSockets or Server-Sent Events
        // For now, we'll store it in a temporary file that AJAX can check
        $realtimeFile = DATA_PATH.'/realtime_notifications.json';

        $notifications = [];
        if (file_exists($realtimeFile)) {
            $notifications = json_decode(file_get_contents($realtimeFile), true) ?: [];
        }

        $notifications[] = [
            'id' => $notificationId,
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'category' => $data['category'],
            'title' => $data['title'],
            'message' => $data['message'],
            'data' => $data['data'],
            'timestamp' => time(),
        ];

        // Keep only last 100 notifications
        if (count($notifications) > 100) {
            $notifications = array_slice($notifications, -100);
        }

        file_put_contents($realtimeFile, json_encode($notifications));
    }

    /**
     * Get real-time notifications
     */
    public function getRealtimeNotifications($userId, $lastCheck = 0)
    {
        $realtimeFile = DATA_PATH.'/realtime_notifications.json';

        if (! file_exists($realtimeFile)) {
            return [];
        }

        $notifications = json_decode(file_get_contents($realtimeFile), true) ?: [];

        // Filter for user and new notifications
        $userNotifications = array_filter($notifications, function ($notif) use ($userId, $lastCheck) {
            return ($notif['user_id'] === null || $notif['user_id'] == $userId) &&
                   $notif['timestamp'] > $lastCheck;
        });

        return array_values($userNotifications);
    }

    /**
     * Get notification preferences for user
     */
    public function getPreferences($userId)
    {
        $query = 'SELECT category, email_enabled, dashboard_enabled 
                  FROM notification_preferences 
                  WHERE user_id = :user_id';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $preferences = [];
        while ($row = $stmt->fetch()) {
            $preferences[$row['category']] = [
                'email_enabled' => (bool) $row['email_enabled'],
                'dashboard_enabled' => (bool) $row['dashboard_enabled'],
            ];
        }

        return $preferences;
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences($userId, $category, $emailEnabled, $dashboardEnabled)
    {
        $query = 'INSERT INTO notification_preferences (user_id, category, email_enabled, dashboard_enabled) 
                  VALUES (:user_id, :category, :email_enabled, :dashboard_enabled)
                  ON DUPLICATE KEY UPDATE 
                  email_enabled = :email_enabled, 
                  dashboard_enabled = :dashboard_enabled';

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':email_enabled', $emailEnabled, \PDO::PARAM_BOOL);
        $stmt->bindParam(':dashboard_enabled', $dashboardEnabled, \PDO::PARAM_BOOL);

        return $stmt->execute();
    }
}
