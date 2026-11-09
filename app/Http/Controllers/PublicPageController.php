<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\PolicyDocument;
use App\Models\Setting;
use App\Services\Cms\V2Pages;
use App\Support\TrialSlots;
use Illuminate\Http\RedirectResponse;
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
        // /pricing: off until switched on (Admin -> Settings); admins can always preview it.
        if ($slug === 'pricing') {
            abort_unless(static::pricingPageEnabled() || Auth::user()?->isAdmin(), 404);
        }

        $page = LandingPage::where('slug', $slug)
            ->where('is_routable', true)
            ->where('is_active', true)
            ->firstOrFail();

        // The v2 design is the live site (Admin -> Settings): /, /hosts,
        // /business and /events show their v2 pages at the usual address.
        if (static::v2Live() && ($slug === 'home' || isset(V2Pages::AUDIENCES[$slug]))) {
            return $slug === 'home' ? $this->v2Home(true) : $this->v2AudiencePage($slug, true);
        }

        $sections = LandingPage::publicSections($page->slug);
        $pageSeo = collect($sections)->firstWhere('section_key', 'seo');

        // The devices and pricing page is built in the v2 design only.
        if ($slug === 'pricing') {
            return $this->render('Public/V2/Pricing', [
                'page' => ['slug' => $page->slug, 'name' => $page->name],
                'sections' => $sections,
                'v2Base' => '',
            ], $pageSeo, $page->path(), index: static::pricingPageEnabled());
        }

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
    public function v2(): Response|RedirectResponse
    {
        abort_unless(static::v2Visible(), 404);

        // Once v2 is the live site, its preview address points at the real one.
        if (static::v2Live()) {
            return redirect('/');
        }

        return $this->v2Home(false);
    }

    /** The v2 homepage, at / when it's the live site, else at /v2 (never indexed there). */
    private function v2Home(bool $live): Response
    {
        // Its own CMS page (Admin -> Public Pages -> "Home (v2 design)"), or the
        // live homepage's sections until that page exists.
        $sections = LandingPage::publicSections(V2Pages::HOME) ?: LandingPage::publicSections('home');
        $pageSeo = collect($sections)->firstWhere('section_key', 'seo');

        return $this->render('Public/V2/Home', [
            'page' => ['slug' => V2Pages::HOME, 'name' => 'Home (v2)'],
            'sections' => $sections,
            'v2Base' => $live ? '' : '/v2',
        ], $pageSeo, $live ? '/' : '/v2', index: $live);
    }

    /**
     * The v2 audience pages (/v2/hosts, /v2/business, /v2/events), on Naoa's
     * journey template. Their sections are their own CMS page; the sign-up
     * form is the live page's (one form definition, validated by the server).
     */
    public function v2Audience(string $audience): Response|RedirectResponse
    {
        abort_unless(static::v2Visible(), 404);

        if (static::v2Live()) {
            return redirect("/{$audience}");
        }

        return $this->v2AudiencePage($audience, false);
    }

    private function v2AudiencePage(string $audience, bool $isLive): Response
    {
        $base = $isLive ? '' : '/v2';
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
                ->map(fn ($a) => static::audienceCard($a, $base))->filter()->values(),
            'v2Base' => $base,
        ], $pageSeo, "{$base}/{$audience}", index: $isLive);
    }

    /**
     * A link card to another v2 audience page: that audience's card on the
     * v2 homepage ("One platform, three kinds of places"), else its hero.
     */
    private static function audienceCard(string $audience, string $base = '/v2'): ?array
    {
        // The audience page's own card picture (its hero's "Card picture" slot) wins.
        $own = collect(LandingPage::publicSections(V2Pages::audienceSlug($audience)))->firstWhere('section_key', 'hero')['media']['card_image'] ?? null;
        $home = collect(LandingPage::publicSections(V2Pages::HOME) ?: LandingPage::publicSections('home'))->firstWhere('section_key', 'path_cards');
        $plain = fn ($value) => trim(strip_tags((string) $value));
        foreach (array_keys($home['content'] ?? []) as $key) {
            if (preg_match('/^cards\.(\d+)\.href$/', $key, $m) && preg_match("#^(/v2)?/{$audience}(\#.*)?$#", $plain($home['content'][$key]))) {
                $i = $m[1];

                return [
                    'to' => "{$base}/{$audience}",
                    'badge' => preg_replace('/^\d+\s*·\s*/u', '', $plain($home['content']["cards.{$i}.label"] ?? '')),
                    'title' => $plain($home['content']["cards.{$i}.title"] ?? ''),
                    'text' => $plain($home['content']["cards.{$i}.description"] ?? ''),
                    'image' => $own ?: ($home['media']["card_{$i}_image"] ?? null),
                ];
            }
        }

        $hero = collect(LandingPage::publicSections(V2Pages::audienceSlug($audience)) ?: LandingPage::publicSections($audience))->firstWhere('section_key', 'hero');

        return $hero ? [
            'to' => "{$base}/{$audience}",
            'badge' => $plain($hero['content']['badge'] ?? ''),
            'title' => $plain($hero['content']['title'] ?? ''),
            'text' => '',
            'image' => $own,
        ] : null;
    }

    /**
     * The v2 design is the live site: /, /hosts, /business and /events show the
     * v2 pages, and /v2... redirects there. On unless switched off in Admin ->
     * Settings, which brings the previous design straight back.
     */
    public static function v2Live(): bool
    {
        return filter_var(Setting::getValue('v2_live', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    /** /pricing (devices and the two ways to pay) is shown once switched on in Admin -> Settings. */
    public static function pricingPageEnabled(): bool
    {
        return filter_var(Setting::getValue('pricing_page_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
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
            // Pricing sections link to /pricing once it's switched on.
            'pricingPage' => static::pricingPageEnabled() ? '/pricing' : null,
            // Device prices, for the sign-up form's summary.
            'devices' => LandingPage::liveDevices(),
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
