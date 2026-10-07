<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\ReviewRequestService;
use App\Traits\HasImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PropertyController extends Controller
{
    use HasImageUpload;

    public function index()
    {
        $properties = Auth::user()->properties()->withCount(['guests', 'accessPoints'])->get();

        return Inertia::render('Host/Properties/Index', [
            'properties' => $properties,
        ]);
    }

    public function show(Property $property)
    {
        $this->authorize('view', $property);

        return Inertia::render('Host/Properties/Show', [
            'property' => $property->loadCount(['guests', 'accessPoints']),
        ]);
    }

    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        return Inertia::render('Host/Properties/Edit', [
            'property' => $property,
            'reviewDefaults' => ['message' => ReviewRequestService::DEFAULT_MESSAGE],
            'reviewStats' => [
                'requested' => $property->guests()->whereNotNull('review_requested_at')->count(),
                'clicked' => $property->guests()->whereNotNull('review_clicked_at')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('splash_image')) {
            $validated['splash_image_path'] = $this->storeImage($request->file('splash_image'), 'properties');
        }

        Auth::user()->properties()->create($validated);

        return redirect()->back()->with('success', 'Property created successfully.');
    }

    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $validated = $request->validate($this->rules());

        if ($request->hasFile('splash_image')) {
            $validated['splash_image_path'] = $this->updateImage(
                $request->file('splash_image'),
                $property->splash_image_path,
                'properties'
            );
        }

        $property->update($validated);

        return redirect()->back()->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);

        $this->deleteImage($property->splash_image_path);
        $property->delete();

        return redirect()->back()->with('success', 'Property deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'wifi_ssid' => 'nullable|string|max:255',
            'occupancy_threshold' => 'integer|min:1',
            'branding_json' => 'nullable|array',
            'splash_image' => 'nullable|image|max:2048',
            'review_url' => 'nullable|url:https|max:500|required_if_accepted:review_requests_enabled',
            'review_requests_enabled' => 'sometimes|boolean',
            'review_request_delay_hours' => 'sometimes|integer|min:1|max:168',
            'review_message' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                if ($value && ! str_contains($value, '{review_link}')) {
                    $fail('Include {review_link} so guests can find your review page.');
                }
            }],
        ];
    }
}
