<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class HostOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::whereIn('property_id', $request->user()->propertyIds())
            ->with(['guest', 'amenity'])
            ->latest()
            ->paginate(50);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        if (! in_array($order->property_id, $request->user()->propertyIds())) {
            abort(403);
        }

        return new OrderResource($order->load(['guest', 'amenity', 'property']));
    }

    public function update(Request $request, Order $order)
    {
        if (! in_array($order->property_id, $request->user()->propertyIds())) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,fulfilled,cancelled',
        ]);

        $order->update($validated);

        return new OrderResource($order);
    }
}
