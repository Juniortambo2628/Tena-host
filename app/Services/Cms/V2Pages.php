<?php

namespace App\Services\Cms;

use App\Models\LandingContent;
use App\Models\LandingMedia;
use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Support\Facades\DB;

/**
 * The v2 public pages (/v2…, the OKJT kit's design) are CMS pages like the
 * live ones, edited in Admin -> Public Pages. They start as copies of the
 * live pages' sections (same section types, words and pictures), so the
 * editor and its Media slots work unchanged, and v2 opens with the live
 * words. After that the copies are independent: editing one never changes
 * the other. Never routable themselves (no catch-all, menus or sitemap):
 * PublicPageController serves them behind the "V2 public pages" switch.
 */
class V2Pages
{
    public const HOME = 'v2';

    /** The live homepage's sections /v2 is built from, in page order. */
    public const HOME_SECTIONS = [
        'seo', 'hero', 'partners', 'path_cards', 'comparison__problem', 'detailed_features__home',
        'how_it_works', 'comparison_table', 'credibility', 'faq', 'cta_banner__founding',
    ];

    /** Real screens, at the paths a fresh install ships them (public/legacy/assets/v2). */
    public const SHOWCASE = [
        ['title' => 'Your WiFi page, in your brand', 'text' => 'Guests join the WiFi and land on a page with your logo, colours and welcome. Name and WhatsApp number, and they’re online.', 'tags' => 'WiFi page', 'shot' => 'screen-wifi-page.webp'],
        ['title' => 'A message they actually read', 'text' => 'Write once on WhatsApp, SMS or email; every past guest gets it with their own name in it.', 'tags' => 'Campaigns, WhatsApp', 'shot' => 'screen-whatsapp-campaign.webp'],
        ['title' => 'Reviews and regulars, at a glance', 'text' => 'How many guests came back this month, and how many Google review requests went out and were opened.', 'tags' => 'Dashboard', 'shot' => 'screen-reviews-dashboard.webp'],
        ['title' => 'Extras paid by M-Pesa', 'text' => 'Airport pickups, late check-out, breakfast: guests order from their phone and pay with an M-Pesa prompt.', 'tags' => 'Paid extras', 'shot' => 'screen-mpesa-extras.webp'],
        ['title' => 'Know who came to your event', 'text' => 'Every sign-in joins the event’s attendee list, with the days they came and their answer to your question.', 'tags' => 'Events', 'shot' => 'screen-event-attendees.webp'],
        ['title' => 'A sign-in page for each event', 'text' => 'While an event runs, the venue’s WiFi page shows its own welcome, an extra question and the sponsor.', 'tags' => 'Events, sponsors', 'shot' => 'screen-event-wifi-page.webp'],
    ];

    /**
     * Create /v2 from the live homepage (and the live rentals pricing), once.
     * Does nothing if the page already exists, so an admin's edits stay.
     */
    public static function createHome(): void
    {
        if (LandingPage::where('slug', self::HOME)->exists() || ! LandingPage::where('slug', 'home')->exists()) {
            return;
        }

        DB::transaction(function () {
            $page = LandingPage::create([
                'slug' => self::HOME,
                'name' => 'Home (v2 design)',
                'is_routable' => false,
                'is_active' => true,
                'sort_order' => (int) LandingPage::max('sort_order') + 1,
            ]);

            $order = 0;
            foreach (self::HOME_SECTIONS as $key) {
                if ($key === 'faq') {
                    // The rentals plans, before the questions, as the live /hosts page has them.
                    static::copySection('hosts', 'pricing', $page, ++$order);
                }
                if ($key === 'how_it_works') {
                    static::addShowcase($page, ++$order);
                }
                static::copySection('home', $key, $page, ++$order);
            }
        });

        LandingPage::clearCache();
    }

    /** Copy one section (its words and pictures) from a live page into a v2 page. */
    public static function copySection(string $fromSlug, string $key, LandingPage $to, int $order): ?LandingSection
    {
        $source = LandingSection::whereHas('page', fn ($q) => $q->where('slug', $fromSlug))
            ->where('section_key', $key)->with(['contents', 'media'])->first();
        if (! $source) {
            return null;
        }

        $copy = LandingSection::create([
            'page_id' => $to->id,
            'section_key' => $key,
            'title' => $source->title,
            'subtitle' => $source->subtitle,
            'badge' => $source->badge,
            'bg' => $source->bg,
            'is_active' => $source->is_active,
            'sort_order' => $order,
        ]);

        foreach ($source->contents as $content) {
            LandingContent::create(['section_id' => $copy->id] + $content->only(['content_key', 'value', 'type']));
        }
        foreach ($source->media as $media) {
            // The same file, not a copy: deleting a picture only removes a file no other page uses (MediaUploadService).
            LandingMedia::create(['section_id' => $copy->id] + $media->only([
                'media_key', 'original_path', 'optimized_path', 'thumbnail_path', 'mime_type',
                'file_size', 'width', 'height', 'duration', 'sort_order', 'crop_data',
            ]));
        }

        return $copy;
    }

    /** "See it in action": real screens with a line each, replaceable in the section's Media tab. */
    private static function addShowcase(LandingPage $page, int $order): void
    {
        $section = LandingSection::create([
            'page_id' => $page->id, 'section_key' => 'showcase', 'title' => 'See it in action (screens)',
            'bg' => 'white', 'is_active' => true, 'sort_order' => $order,
        ]);

        $content = [
            'anchor' => 'in-action',
            'menu_label' => 'In action',
            'badge' => 'See it in action',
            'title' => 'What your guests see, and what you get',
            'subtitle' => 'Real screens from TenaFi, with sample guests.',
        ];
        foreach (self::SHOWCASE as $i => $item) {
            foreach (['title', 'text', 'tags'] as $field) {
                $content["items.{$i}.{$field}"] = $item[$field];
            }
            LandingMedia::create([
                'section_id' => $section->id, 'media_key' => "item_{$i}_shot",
                'original_path' => '/legacy/assets/v2/'.$item['shot'], 'mime_type' => 'image/webp', 'file_size' => 0, 'sort_order' => $i,
            ]);
        }
        foreach ($content as $key => $value) {
            LandingContent::create(['section_id' => $section->id, 'content_key' => $key, 'value' => $value, 'type' => 'text']);
        }
    }
}
