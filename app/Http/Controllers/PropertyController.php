<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyStats;
use App\Services\ReviewRequestService;
use App\Services\Unifi\UnifiService;
use App\Support\SplashDesign;
use App\Traits\HasImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PropertyController extends Controller
{
    use HasImageUpload;

    public function index(PropertyStats $stats)
    {
        $properties = Auth::user()->properties()->withCount(['guests', 'accessPoints'])->get();

        return Inertia::render('Host/Properties/Index', [
            'properties' => $properties,
            'stats' => $stats->forHost(Auth::user()),
        ]);
    }

    /**
     * Download the host's properties as CSV.
     */
    public function export()
    {
        $properties = Auth::user()->properties()->withCount(['guests', 'accessPoints'])->orderBy('name')->get();

        return response()->streamDownload(function () use ($properties) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Address', 'WiFi SSID', 'Guests', 'Access points', 'Created']);
            foreach ($properties as $p) {
                fputcsv($out, [$p->name, $p->address, $p->wifi_ssid, $p->guests_count, $p->access_points_count, $p->created_at?->toDateString()]);
            }
            fclose($out);
        }, 'properties-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function show(Property $property)
    {
        $this->authorize('view', $property);

        return Inertia::render('Host/Properties/Show', [
            'property' => $property->loadCount(['guests', 'accessPoints'])->load('accessPoints'),
            'wifi' => [
                'controllerConfigured' => UnifiService::fromSettings()->isConfigured(),
                'portalUrl' => route('portal.show'),
                'guests' => $property->guests()->whereNotNull('device_mac')->count(),
                'visits' => (int) $property->guests()->whereNotNull('device_mac')->sum('total_visits'),
                'recentGuests' => $property->guests()->where('last_connected', '>=', now()->subDay())->count(),
                'marketingOptIns' => $property->guests()->whereNotNull('device_mac')->where('marketing_opt_in', true)->count(),
                'latestGuests' => $property->guests()->whereNotNull('device_mac')->latest('last_connected')->limit(10)
                    ->get(['id', 'first_name', 'last_connected', 'total_visits', 'marketing_opt_in']),
            ],
        ]);
    }

    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        return Inertia::render('Host/Properties/Edit', [
            'property' => $property,
            'reviewDefaults' => ['message' => ReviewRequestService::defaultMessage($property)],
            'venueUrl' => SplashDesign::isBusiness($property) ? route('venue.show', $property) : null,
            'reviewStats' => [
                'requested' => $property->guests()->whereNotNull('review_requested_at')->count(),
                'clicked' => $property->guests()->whereNotNull('review_clicked_at')->count(),
            ],
        ]);
    }

    /**
     * The splash studio: design and guest content with a live preview.
     */
    public function splash(Property $property)
    {
        $this->authorize('update', $property);

        return Inertia::render('Host/Properties/Splash', [
            'property' => $property->only(['id', 'name', 'business_type', 'address', 'wifi_ssid', 'review_url', 'logo_path', 'splash_image_path']) + [
                'branding_json' => SplashDesign::clean($property->branding_json ?? []),
            ],
            'isBusiness' => SplashDesign::isBusiness($property),
            'otherProperties' => Auth::user()->properties()->whereKeyNot($property->id)->orderBy('name')
                ->get(['id', 'name', 'address', 'splash_image_path']),
            'hostPhone' => $property->host?->phone_number,
            'templates' => SplashDesign::TEMPLATES,
        ]);
    }

    /**
     * Save the studio. It always sends the whole design, so lists left empty are cleared.
     */
    public function updateSplash(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $rules = SplashDesign::rules();
        $rules['branding_json'] = str_replace('nullable', 'required', $rules['branding_json']);
        $validated = $request->validate($rules + [
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'splash_image' => 'nullable|image|max:4096',
            // Automatic review requests need somewhere to send guests.
            'review_url' => ['nullable', 'url:https', 'max:500', Rule::requiredIf((bool) $property->review_requests_enabled)],
        ], ['review_url.required' => 'Automatic review requests are on, so keep a review link (or turn them off on the property page).']);

        $changes = ['branding_json' => SplashDesign::clean($validated['branding_json'])];
        if (! isset($validated['branding_json']['fields']) && ! in_array($changes['branding_json']['template'], SplashDesign::ORIGINAL, true)) {
            $changes['branding_json'] = SplashDesign::withDefaults($changes['branding_json'], SplashDesign::isBusiness($property));
        }
        if ($request->has('review_url')) {
            $changes['review_url'] = $validated['review_url'] ?? null;
        }
        if ($request->hasFile('splash_image')) {
            $changes['splash_image_path'] = $this->updateImage($request->file('splash_image'), $property->splash_image_path, 'properties');
        }
        if ($request->hasFile('logo')) {
            $changes['logo_path'] = $this->updateImage($request->file('logo'), $property->logo_path, 'properties');
        }

        $property->update($changes);

        return redirect()->back()->with('success', 'Splash page saved.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        // A new page starts with its template's headline, sign-up fields and tiles.
        $business = SplashDesign::isBusiness((new Property(['business_type' => $validated['business_type'] ?? null]))->setRelation('host', Auth::user()));
        $validated['branding_json'] = SplashDesign::clean(SplashDesign::withDefaults($validated['branding_json'] ?? ['template' => 'bento'], $business));

        if ($request->hasFile('splash_image')) {
            $validated['splash_image_path'] = $this->storeImage($request->file('splash_image'), 'properties');
        }

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $this->storeImage($request->file('logo'), 'properties');
        }

        Auth::user()->properties()->create($validated);

        return redirect()->back()->with('success', 'Property created successfully.');
    }

    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $validated = $request->validate($this->rules());

        // The property form only carries the template and colour; keep the studio's content.
        if (isset($validated['branding_json'])) {
            $saved = $property->branding_json ?? [];
            $business = SplashDesign::isBusiness((clone $property)->fill(['business_type' => array_key_exists('business_type', $validated) ? $validated['business_type'] : $property->business_type]));
            $validated['branding_json'] = SplashDesign::clean(SplashDesign::switchTemplate($saved, array_replace($saved, $validated['branding_json']), $business));
        }

        if ($request->hasFile('splash_image')) {
            $validated['splash_image_path'] = $this->updateImage(
                $request->file('splash_image'),
                $property->splash_image_path,
                'properties'
            );
        }

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $this->updateImage($request->file('logo'), $property->logo_path, 'properties');
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
            'business_type' => ['nullable', Rule::in(SplashDesign::businessTypes())],
            'address' => 'nullable|string|max:255',
            'wifi_ssid' => 'nullable|string|max:255',
            'occupancy_threshold' => 'integer|min:1',
            ...SplashDesign::rules(),
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'splash_image' => 'nullable|image|max:2048',
            'review_url' => 'nullable|url:https|max:500|required_if_accepted:review_requests_enabled',
            'review_requests_enabled' => 'sometimes|boolean',
            'offers' => 'nullable|string|max:2000',
            'events' => 'nullable|string|max:2000',
            'birthday_offer' => 'nullable|string|max:500',
            'review_request_delay_hours' => 'sometimes|integer|min:1|max:168',
            'review_message' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                if ($value && ! str_contains($value, '{review_link}')) {
                    $fail('Include {review_link} so guests can find your review page.');
                }
            }],
        ];
    }
}
