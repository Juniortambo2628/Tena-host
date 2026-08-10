<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Host;
use App\Models\LandingSection;
use App\Models\PolicyDocument;
use App\Models\Property;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $query = $request->input('q');
        $user = $request->user();
        $role = $user->role;
        $results = [];

        if ($role === 'admin') {
            $results = $this->searchAdmin($query);
        } elseif ($role === 'host') {
            $results = $this->searchHost($query, $user);
        } elseif ($role === 'staff') {
            $results = $this->searchStaff($query);
        }

        return response()->json([
            'results' => $results,
            'query' => $query,
        ]);
    }

    private function searchAdmin(string $query): array
    {
        $results = [];

        User::where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(function ($user) use (&$results) {
                $results[] = [
                    'id' => $user->id,
                    'title' => "{$user->first_name} {$user->last_name}",
                    'subtitle' => $user->email,
                    'type' => 'user',
                    'icon' => 'user',
                    'url' => route('admin.users.show', $user->id),
                ];
            });

        Host::where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(function ($host) use (&$results) {
                $results[] = [
                    'id' => $host->id,
                    'title' => "{$host->first_name} {$host->last_name}",
                    'subtitle' => $host->email,
                    'type' => 'host',
                    'icon' => 'building',
                    'url' => route('admin.hosts.show', $host->id),
                ];
            });

        Registration::where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(function ($reg) use (&$results) {
                $results[] = [
                    'id' => $reg->id,
                    'title' => "{$reg->first_name} {$reg->last_name}",
                    'subtitle' => $reg->email,
                    'type' => 'registration',
                    'icon' => 'clipboard',
                    'url' => route('admin.registrations.index'),
                ];
            });

        Property::where('name', 'LIKE', "%{$query}%")
            ->orWhere('address', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(function ($property) use (&$results) {
                $results[] = [
                    'id' => $property->id,
                    'title' => $property->name,
                    'subtitle' => $property->address,
                    'type' => 'property',
                    'icon' => 'home',
                    'url' => route('admin.hosts.show', $property->host_id),
                ];
            });

        Guest::where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(function ($guest) use (&$results) {
                $results[] = [
                    'id' => $guest->id,
                    'title' => "{$guest->first_name} {$guest->last_name}",
                    'subtitle' => $guest->email,
                    'type' => 'guest',
                    'icon' => 'users',
                    'url' => route('admin.users.index'),
                ];
            });

        PolicyDocument::where('title', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(function ($policy) use (&$results) {
                $results[] = [
                    'id' => $policy->id,
                    'title' => $policy->title,
                    'subtitle' => $policy->category,
                    'type' => 'policy',
                    'icon' => 'file',
                    'url' => route('admin.policies.index'),
                ];
            });

        LandingSection::where('title', 'LIKE', "%{$query}%")
            ->orWhere('section_key', 'LIKE', "%{$query}%")
            ->limit(3)
            ->get()
            ->each(function ($section) use (&$results) {
                $results[] = [
                    'id' => $section->id,
                    'title' => $section->title,
                    'subtitle' => $section->section_key,
                    'type' => 'landing_section',
                    'icon' => 'globe',
                    'url' => route('admin.landing.index'),
                ];
            });

        return $results;
    }

    private function searchHost(string $query, User $user): array
    {
        $results = [];
        $propertyIds = Property::where('user_id', $user->id)->pluck('id');

        Guest::whereIn('property_id', $propertyIds)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->each(function ($guest) use (&$results) {
                $results[] = [
                    'id' => $guest->id,
                    'title' => "{$guest->first_name} {$guest->last_name}",
                    'subtitle' => $guest->email,
                    'type' => 'guest',
                    'icon' => 'users',
                    'url' => route('host.guests.show', $guest->id),
                ];
            });

        Property::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('address', 'LIKE', "%{$query}%")
                    ->orWhere('wifi_ssid', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->each(function ($property) use (&$results) {
                $results[] = [
                    'id' => $property->id,
                    'title' => $property->name,
                    'subtitle' => $property->address,
                    'type' => 'property',
                    'icon' => 'home',
                    'url' => route('host.properties.show', $property->id),
                ];
            });

        return $results;
    }

    private function searchStaff(string $query): array
    {
        return [];
    }
}
