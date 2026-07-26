<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'category',
        'title',
        'message',
        'data',
        'is_read',
        'is_archived',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'is_archived' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            Cache::forget('app_notifications_'.$notification->user_id);
        });

        static::updated(function (Notification $notification) {
            Cache::forget('app_notifications_'.$notification->user_id);
        });

        static::deleted(function (Notification $notification) {
            Cache::forget('app_notifications_'.$notification->user_id);
        });
    }
}
