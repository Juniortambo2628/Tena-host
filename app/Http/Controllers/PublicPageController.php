<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\PolicyDocument;
use App\Models\Setting;
use App\Services\Cms\V2Pages;
use App\Support\TrialSlots;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
        ], $pageSeo, $page->path());
    }

    /**
     * The v2 landing page (the OKJT kit's design, /v2): a preview while it's
     * built. Admin -> Settings -> "V2 public pages" opens it to everyone;
     * admins can always see it. Never indexed by search engines.
     */
    public function v2(): Response
    {
        abort_unless(static::v2Visible(), 404);

        // Its own CMS page (Admin -> Public Pages -> "Home (v2 design)"), or the
        // live homepage's sections until that page exists.
        $sections = LandingPage::publicSections(V2Pages::HOME) ?: LandingPage::publicSections('home');
        $pageSeo = collect($sections)->firstWhere('section_key', 'seo');

        return $this->render('Public/V2/Home', [
            'page' => ['slug' => V2Pages::HOME, 'name' => 'Home (v2)'],
            'sections' => $sections,
        ], $pageSeo, '/v2', index: false);
    }

    /**
     * The v2 audience pages (/v2/hosts, /v2/business, /v2/events), on Naoa's
     * journey template. Their sections are their own CMS page; the sign-up
     * form is the live page's (one form definition, validated by the server).
     */
    public function v2Audience(string $audience): Response
    {
        abort_unless(static::v2Visible(), 404);

        $sections = LandingPage::publicSections(V2Pages::audienceSlug($audience)) ?: LandingPage::publicSections($audience);
        $live = collect(LandingPage::publicSections($audience));
        $pageSeo = collect($sections)->firstWhere('section_key', 'seo');

        return $this->render('Public/V2/Audience', [
            'page' => ['slug' => V2Pages::audienceSlug($audience), 'name' => ucfirst($audience).' (v2)'],
            'audience' => $audience,
            'sections' => $sections,
            // The live page's sign-up form, and the plans it prices from.
            'signup' => [
                'page' => $audience,
                'sections' => $live->filter(fn ($s) => in_array($s['section_key'], ['signup', 'pricing'], true))->values(),
            ],
            'others' => collect(array_keys(V2Pages::AUDIENCES))->reject(fn ($a) => $a === $audience)
                ->map(fn ($a) => static::audienceCard($a))->filter()->values(),
        ], $pageSeo, "/v2/{$audience}", index: false);
    }

    /**
     * A link card to another v2 audience page: that audience's card on the
     * v2 homepage ("One platform, three kinds of places"), else its hero.
     */
    private static function audienceCard(string $audience): ?array
    {
        $home = collect(LandingPage::publicSections(V2Pages::HOME) ?: LandingPage::publicSections('home'))->firstWhere('section_key', 'path_cards');
        $plain = fn ($value) => trim(strip_tags((string) $value));
        foreach (array_keys($home['content'] ?? []) as $key) {
            if (preg_match('/^cards\.(\d+)\.href$/', $key, $m) && preg_match("#^(/v2)?/{$audience}(\#.*)?$#", $plain($home['content'][$key]))) {
                $i = $m[1];

                return [
                    'to' => "/v2/{$audience}",
                    'badge' => preg_replace('/^\d+\s*·\s*/u', '', $plain($home['content']["cards.{$i}.label"] ?? '')),
                    'title' => $plain($home['content']["cards.{$i}.title"] ?? ''),
                    'text' => $plain($home['content']["cards.{$i}.description"] ?? ''),
                    'image' => $home['media']["card_{$i}_image"] ?? null,
                ];
            }
        }

        $hero = collect(LandingPage::publicSections(V2Pages::audienceSlug($audience)) ?: LandingPage::publicSections($audience))->firstWhere('section_key', 'hero');

        return $hero ? [
            'to' => "/v2/{$audience}",
            'badge' => $plain($hero['content']['badge'] ?? ''),
            'title' => $plain($hero['content']['title'] ?? ''),
            'text' => '',
            'image' => null,
        ] : null;
    }

    /** The v2 pages are open to everyone when switched on, and to admins always. */
    public static function v2Visible(): bool
    {
        return filter_var(Setting::getValue('v2_pages_enabled', '0'), FILTER_VALIDATE_BOOLEAN)
            || (bool) Auth::user()?->isAdmin();
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

    private function render(string $component, array $props, ?array $pageSeo, string $path, bool $index = true): Response
    {
        $site = LandingPage::siteSections();

        return Inertia::render($component, $props + [
            'site' => $site,
            'menus' => LandingPage::navMenus(),
            'trial' => TrialSlots::summary(),
            // Live rental plans, quoted by the homepage hero and trial card.
            'plans' => LandingPage::livePlans('hosts'),
            // The hero's call-back form shows only when switched on in Settings.
            'callbacks' => CallbackController::enabled(),
            // The homepage "Try it yourself" section links to /demo only while it's on.
            'demo' => DemoController::enabled(),
            'seo' => $this->seo($site['seo'] ?? null, $pageSeo, $path, $index),
        ]);
    }

    /**
     * Page SEO falls back to the site-wide defaults, field by field.
     */
    private function seo(?array $siteSeo, ?array $pageSeo, string $path, bool $index = true): array
    {
        $pick = fn (string $key) => Arr::get($pageSeo, "content.{$key}") ?: Arr::get($siteSeo, "content.{$key}");
        $image = Arr::get($pageSeo, 'media.og_image') ?: Arr::get($siteSeo, 'media.og_image');

        return [
            'title' => strip_tags((string) $pick('meta_title')),
            'description' => strip_tags((string) $pick('meta_description')),
            'site_name' => Arr::get($siteSeo, 'content.site_name', 'TenaFi'),
            'image' => $image ? url($image) : null,
            'url' => url($path),
            'index' => $index,
        ];
    }
}
