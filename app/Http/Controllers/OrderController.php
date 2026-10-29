<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Order;
use App\Services\ExtrasPaymentService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Store a newly created order (host or guest).
     */
    public function store(Request $request, ExtrasPaymentService $payments)
    {
        $user = $request->user();

        if ($user->isGuest()) {
            $guest = $user->guestRecords()->latest()->first();

            if (! $guest) {
                return back()->with('error', 'No guest profile found.');
            }

            $validated = $request->validate([
                'amenity_id' => 'required|exists:amenities,id',
                // M-Pesa number for the payment prompt (priced extras only).
                'phone' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                    if ($value && ! Phone::isValid(Phone::toE164($value))) {
                        $fail('Enter your M-Pesa number, e.g. 0712 345 678.');
                    }
                }],
            ]);

            $amenity = Amenity::findOrFail($validated['amenity_id']);

            // Security: ensure amenity belongs to a property the guest can access.
            $propertyIds = $user->guestRecords()->pluck('property_id');
            if (! $propertyIds->contains($amenity->property_id)) {
                abort(403, 'This amenity is not available at your property.');
            }

            $order = Order::create([
                'guest_id' => $guest->id,
                'property_id' => $amenity->property_id,
                'amenity_id' => $amenity->id,
                'status' => 'pending',
                'total' => $amenity->price,
            ]);

            $result = $payments->checkout($order, $validated['phone'] ?? $guest->phone);

            return redirect()->back()->with($result['status'] === 'failed' ? 'error' : 'success', $result['message']);
        }

        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::in($user->propertyIds())],
            // The guest and the extra must belong to that same property.
            'guest_id' => ['required', 'integer', Rule::exists('guests', 'id')->where('property_id', $request->integer('property_id'))],
            'amenity_id' => ['required', 'integer', Rule::exists('amenities', 'id')->where('property_id', $request->integer('property_id'))],
            'status' => 'nullable|in:pending,fulfilled,cancelled',
            'total' => 'required|numeric|min:0',
        ]);

        $this->authorize('create', Order::class);

        Order::create($validated);

        return redirect()->back()->with('success', 'Order created successfully.');
    }

    /**
     * Display a listing of orders for the host's properties.
     */
    public function index(Request $request)
    {
        $orders = Order::forHost(Auth::user())
            ->with(['guest:id,first_name,last_name', 'property:id,name', 'amenity:id,name'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('Host/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        return Inertia::render('Host/Orders/Show', [
            'order' => $order->load(['guest:id,first_name,last_name,email', 'property:id,name,address', 'amenity:id,name']),
        ]);
    }

    /**
     * Update the specified order status.
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => 'required|in:pending,fulfilled,cancelled',
        ]);

        $order->update($validated);

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);
        $order->delete();

        return redirect()->back()->with('success', 'Order deleted successfully.');
    }
}
