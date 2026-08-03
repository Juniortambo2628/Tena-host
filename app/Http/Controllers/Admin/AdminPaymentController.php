<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MpesaTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = MpesaTransaction::with('user');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('Status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('MpesaReceiptNumber', 'like', "%{$search}%")
                  ->orWhere('PhoneNumber', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->latest()->paginate(20);

        $stats = [
            'total' => MpesaTransaction::count(),
            'completed' => MpesaTransaction::where('Status', 'completed')->count(),
            'pending' => MpesaTransaction::where('Status', 'pending')->count(),
            'failed' => MpesaTransaction::where('Status', 'failed')->count(),
            'total_revenue' => MpesaTransaction::where('Status', 'completed')->sum('Amount'),
            'today_count' => MpesaTransaction::whereDate('created_at', today())->count(),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(MpesaTransaction $transaction)
    {
        $transaction->load('user');

        return Inertia::render('Admin/Payments/Show', [
            'transaction' => $transaction,
        ]);
    }

    public function hosts()
    {
        $hosts = User::where('role', 'host')
            ->with('subscriptions', 'mpesaTransactions')
            ->get()
            ->map(function ($host) {
                $latestTransaction = $host->mpesaTransactions()->latest()->first();
                return [
                    'id' => $host->id,
                    'name' => $host->name,
                    'email' => $host->email,
                    'phone_number' => $host->phone_number,
                    'subscribed' => $host->subscribed('default'),
                    'subscription_status' => $host->subscription('default')?->stripe_status ?? 'none',
                    'total_paid' => $host->mpesaTransactions()->where('Status', 'completed')->sum('Amount'),
                    'transaction_count' => $host->mpesaTransactions()->count(),
                    'last_payment' => $latestTransaction?->created_at,
                    'created_at' => $host->created_at,
                ];
            });

        return Inertia::render('Admin/Payments/Hosts', [
            'hosts' => $hosts,
        ]);
    }
}
