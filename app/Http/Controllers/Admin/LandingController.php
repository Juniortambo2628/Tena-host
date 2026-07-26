<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSection;
use App\Models\LandingContent;
use App\Models\LandingMedia;
use App\Services\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function __construct(
        private MediaUploadService $mediaService
    ) {}

    /**
     * Display the landing page CMS editor.
     */
    public function index()
    {
        $sections = LandingSection::with(['contents', 'media'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($section) => [
                'id' => $section->id,
                'section_key' => $section->section_key,
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'badge' => $section->badge,
                'bg' => $section->bg,
                'is_active' => $section->is_active,
                'sort_order' => $section->sort_order,
                'content' => $section->contents->pluck('value', 'content_key')->toArray(),
                'media' => $section->media->keyBy('media_key')->map(fn($m) => [
                    'id' => $m->id,
                    'media_key' => $m->media_key,
                    'original_path' => $m->original_path,
                    'optimized_path' => $m->optimized_path,
                    'thumbnail_path' => $m->thumbnail_path,
                    'mime_type' => $m->mime_type,
                    'file_size' => $m->file_size,
                    'width' => $m->width,
                    'height' => $m->height,
                    'duration' => $m->duration,
                    'crop_data' => $m->crop_data,
                ])->toArray(),
            ]);

        return Inertia::render('Admin/Landing/Index', [
            'sections' => $sections,
            'mediaConfig' => [
                'maxSize' => MediaUploadService::getMaxFileSize(),
                'allowedTypes' => MediaUploadService::getAllowedTypes(),
            ],
        ]);
    }

    /**
     * Update a section's metadata (title, subtitle, badge, bg, is_active, sort_order).
     */
    public function updateSection(Request $request, LandingSection $section)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:500',
            'subtitle' => 'sometimes|string|max:1000',
            'badge' => 'sometimes|string|max:255',
            'bg' => 'sometimes|in:white,gray,dark',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $section->update($data);
        LandingSection::clearCache();

        return back();
    }

    /**
     * Reorder sections via drag-and-drop.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:landing_sections,id',
        ]);

        foreach ($request->order as $index => $sectionId) {
            LandingSection::where('id', $sectionId)->update(['sort_order' => $index]);
        }

        LandingSection::clearCache();

        return back()->with('success', 'Sections reordered.');
    }

    /**
     * Create new content entry.
     */
    public function storeContent(Request $request)
    {
        $data = $request->validate([
            'section_id' => 'required|exists:landing_sections,id',
            'content_key' => 'required|string|max:255',
            'value' => 'nullable|string',
            'type' => 'sometimes|in:text,html,json',
        ]);

        $content = LandingContent::updateOrCreate(
            ['section_id' => $data['section_id'], 'content_key' => $data['content_key']],
            ['value' => $data['value'], 'type' => $data['type'] ?? 'text']
        );

        LandingSection::clearCache();

        return back();
    }

    /**
     * Bulk update content for a section.
     */
    public function updateContent(Request $request)
    {
        $data = $request->validate([
            'section_id' => 'required|exists:landing_sections,id',
            'items' => 'required|array',
            'items.*.content_key' => 'required|string',
            'items.*.value' => 'nullable|string',
            'items.*.type' => 'sometimes|in:text,html,json',
        ]);

        foreach ($data['items'] as $item) {
            LandingContent::updateOrCreate(
                ['section_id' => $data['section_id'], 'content_key' => $item['content_key']],
                ['value' => $item['value'], 'type' => $item['type'] ?? 'text']
            );
        }

        LandingSection::clearCache();

        return back();
    }

    /**
     * Delete content entry.
     */
    public function destroyContent(LandingContent $content)
    {
        $content->delete();
        LandingSection::clearCache();

        return back();
    }

    /**
     * Assign existing media from library to a section slot.
     */
    public function assignMedia(Request $request, LandingSection $section)
    {
        $data = $request->validate([
            'media_id' => 'required|exists:landing_media,id',
            'media_key' => 'required|string|max:255',
        ]);

        $source = LandingMedia::findOrFail($data['media_id']);

        // Delete existing media with same key in this section
        LandingMedia::where('section_id', $section->id)
            ->where('media_key', $data['media_key'])
            ->each(fn($m) => $this->mediaService->delete($m));

        $media = LandingMedia::create([
            'section_id' => $section->id,
            'media_key' => $data['media_key'],
            'original_path' => $source->original_path,
            'optimized_path' => $source->optimized_path,
            'thumbnail_path' => $source->thumbnail_path,
            'mime_type' => $source->mime_type,
            'file_size' => $source->file_size,
            'width' => $source->width,
            'height' => $source->height,
            'duration' => $source->duration,
            'sort_order' => 0,
        ]);

        LandingSection::clearCache();

        return response()->json(['media' => $media]);
    }

    /**
     * Upload media for a section.
     */
    public function uploadMedia(Request $request, LandingSection $section)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB
            'media_key' => 'required|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $media = $this->mediaService->upload($request->file('file'));

        // Delete existing media with same key
        LandingMedia::where('section_id', $section->id)
            ->where('media_key', $request->input('media_key'))
            ->each(fn($m) => $this->mediaService->delete($m));

        $media->update([
            'section_id' => $section->id,
            'media_key' => $request->input('media_key'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        LandingSection::clearCache();

        return response()->json(['media' => $media]);
    }

    /**
     * Update crop data for media.
     */
    public function updateCrop(Request $request, LandingMedia $media)
    {
        $data = $request->validate([
            'crop_data' => 'required|array',
            'crop_data.x' => 'required|numeric|min:0',
            'crop_data.y' => 'required|numeric|min:0',
            'crop_data.width' => 'required|numeric|min:1',
            'crop_data.height' => 'required|numeric|min:1',
        ]);

        $media->update(['crop_data' => $data['crop_data']]);
        LandingSection::clearCache();

        return back();
    }

    /**
     * Delete media.
     */
    public function destroyMedia(LandingMedia $media)
    {
        $this->mediaService->delete($media);
        LandingSection::clearCache();

        return back()->with('success', 'Media deleted.');
    }

    /**
     * Download media file.
     */
    public function downloadMedia(LandingMedia $media)
    {
        $path = ltrim($media->original_path, '/');

        // Check public disk first, then physical public directory
        if (Storage::disk('public')->exists($path)) {
            $filename = basename($media->original_path);
            return Storage::disk('public')->download($path, $filename);
        }

        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            $filename = basename($media->original_path);
            return response()->download($fullPath, $filename);
        }

        abort(404, 'File not found.');
    }

    /**
     * List all media items (for the library picker).
     */
    public function listMedia()
    {
        $media = LandingMedia::with('section:id,section_key,title')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'media_key' => $m->media_key,
                'original_path' => $m->original_path,
                'optimized_path' => $m->optimized_path,
                'thumbnail_path' => $m->thumbnail_path,
                'mime_type' => $m->mime_type,
                'file_size' => $m->file_size,
                'width' => $m->width,
                'height' => $m->height,
                'duration' => $m->duration,
                'section_key' => $m->section?->section_key,
                'section_title' => $m->section?->title,
            ]);

        return response()->json($media);
    }

    /**
     * Get public landing page data (for the frontend).
     */
    public static function getPublicData(): array
    {
        return LandingSection::getActiveSections();
    }
}
