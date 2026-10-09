<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Upload or reset the logos in App\Support\Brand::LOGOS. Every page, the
 * captive portal and every email read them from there.
 */
class BrandingController extends Controller
{
    public function upload(Request $request, string $key)
    {
        abort_unless(isset(Brand::LOGOS[$key]), 404);

        // Emails need a raster image: many clients block SVG.
        $types = $key === 'logo_url' ? 'png,jpg,jpeg,webp' : 'png,jpg,jpeg,webp,svg,ico';
        $request->validate(['file' => ['required', 'file', 'max:2048', "mimes:{$types}"]]);

        $this->deleteUpload($key);
        $path = $request->file('file')->store('branding', 'public');
        Setting::setValue($key, Storage::disk('public')->url($path), 'branding');
        Cache::forget('app_settings');

        return back()->with('success', Brand::LOGOS[$key][0].' updated.');
    }

    public function reset(string $key)
    {
        abort_unless(isset(Brand::LOGOS[$key]), 404);

        $this->deleteUpload($key);
        Setting::where('key', $key)->delete();
        Cache::forget('app_settings');

        return back()->with('success', Brand::LOGOS[$key][0].' reset to the TenaFi default.');
    }

    private function deleteUpload(string $key): void
    {
        $current = (string) Setting::getValue($key);
        $prefix = Storage::disk('public')->url('branding/');
        if (str_starts_with($current, $prefix)) {
            Storage::disk('public')->delete('branding/'.basename($current));
        }
    }
}
