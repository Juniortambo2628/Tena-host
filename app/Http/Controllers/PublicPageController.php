<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\PolicyDocument;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders every CMS-driven public page (/, /hosts, /business) and the
 * policy pages through the same layout, with server-side SEO props that
 * app.blade.php turns into title / description / og:* / twitter:* tags.
 */
class PublicPageController extends Controller
{
    public function show(string $slug = 'home'): Response
    {
        $page = LandingPage::where('slug', $slug)
            ->where('is_routable', true)
            ->where('is_active', true)
            ->firstOrFail();

        $sections = LandingPage::publicSections($page->slug);
        $pageSeo = collect($sections)->firstWhere('section_key', 'seo');

        return $this->render('Public/Page', [
            'page' => ['slug' => $page->slug, 'name' => $page->name],
            'sections' => $sections,
        ], $pageSeo, $page->slug === 'home' ? '/' : "/{$page->slug}");
    }

    public function policy(string $slug): Response
    {
        $document = PolicyDocument::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return $this->render('Public/Policy', [
            'page' => ['slug' => $slug, 'name' => $document->title],
            'document' => $document->only(['title', 'description', 'content', 'version', 'effective_date', 'updated_at']),
        ], ['content' => [
            'meta_title' => $document->title.' | TenaFi',
            'meta_description' => $document->description,
        ]], '/'.request()->path());
    }

    private function render(string $component, array $props, ?array $pageSeo, string $path): Response
    {
        $site = LandingPage::siteSections();

        return Inertia::render($component, $props + [
            'site' => $site,
            'seo' => $this->seo($site['seo'] ?? null, $pageSeo, $path),
        ]);
    }

    /**
     * Page SEO falls back to the site-wide defaults, field by field.
     */
    private function seo(?array $siteSeo, ?array $pageSeo, string $path): array
    {
        $pick = fn (string $key) => Arr::get($pageSeo, "content.{$key}") ?: Arr::get($siteSeo, "content.{$key}");
        $image = Arr::get($pageSeo, 'media.og_image') ?: Arr::get($siteSeo, 'media.og_image');

        return [
            'title' => strip_tags((string) $pick('meta_title')),
            'description' => strip_tags((string) $pick('meta_description')),
            'site_name' => Arr::get($siteSeo, 'content.site_name', 'TenaFi'),
            'image' => $image ? url($image) : null,
            'url' => url($path),
        ];
    }
}
