<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'notifications' => $request->user()
                    ? Cache::remember(
                        'app_notifications_'.$request->user()->id,
                        60,
                        fn () => Notification::where('user_id', $request->user()->id)
                            ->latest()
                            ->take(5)
                            ->get()
                            ->map(fn ($n) => [
                                'id' => $n->id,
                                'title' => $n->title,
                                'time' => $n->created_at->diffForHumans(),
                                'type' => $n->type,
                                'read_at' => $n->is_read ? $n->updated_at : null,
                            ])
                            ->toArray()
                    )
                    : [],
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
