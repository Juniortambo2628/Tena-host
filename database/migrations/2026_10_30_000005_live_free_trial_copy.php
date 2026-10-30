<?php

use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Database\Migrations\Migration;

/**
 * Brings the live public pages onto the free December trial (Glen, 30 Oct).
 *
 * A one-off exception to "never overwrite live CMS copy", asked for by Glen:
 * a field is replaced only when it still holds the old Founding 20 default
 * exactly, or still advertises the old offer. Anything an admin rewrote
 * (e.g. pricing copy already on the trial) is left alone.
 */
return new class extends Migration
{
    /** Wording that only the old offer used. */
    private const OLD_OFFER = '/Founding 20|two (free )?months free|first two months|12-month plan|Billing begins|Apply for a spot/i';

    /** page => section => content key => [old blueprint default, new default] */
    private const COPY = [
        'home' => [
            'cta_banner__founding' => [
                'badge' => [
                    0 => 'Founding 20',
                    1 => 'Free trial',
                ],
                'body' => [
                    0 => 'TenaFi was born inside Stay Awhile Rentals: 1,400+ reservations, 5,000+ guest nights and 800+ reviews. We built the tool we needed. Now our Founding 20 (10 rentals and 10 businesses) get it from December 2026, with the first two months free.',
                    1 => 'TenaFi was born inside Stay Awhile Rentals: 1,400+ reservations, 5,000+ guest nights and 800+ reviews. We built the tool we needed. Now you can try it free for the whole of December, with installs from 16 November.',
                ],
            ],
        ],
        'hosts' => [
            'pricing' => [
                'subtitle' => [
                    0 => 'The device, installation, WiFi page, guest list and support, on a 12-month plan, then month to month.',
                    1 => 'The device, installation, WiFi page, guest list and support. Free for December, then month to month.',
                ],
                'footnote' => [
                    0 => 'Multi-unit operators: 20% off from 10 units, 30% off from 50. Extra devices at the same site: KES 1,500 a month each. Pay quarterly for 5% off, or yearly and get 2 months free. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi’s; we maintain and replace it.',
                    1 => 'Free trial: installs from 16 November, and the rest of November and all of December are free. Then month to month; cancel with 30 days’ notice. Pay yearly and get 2 months free. Multi-unit operators: 20% off from 10 units, 30% off from 50. Extra devices at the same site: KES 1,500 a month each. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi’s; we maintain and replace it.',
                ],
            ],
            'cta_banner__founding' => [
                'badge' => [
                    0 => 'Founding 20',
                    1 => 'Free trial',
                ],
                'title' => [
                    0 => 'Be one of our Founding 20.',
                    1 => 'Try TenaFi free for the whole of December.',
                ],
                'body' => [
                    0 => '10 short-term rental operators get TenaFi installed from December 2026, in time for the high season, with the first two months free. In return: honest feedback, results we can share, and referrals if you love it.',
                    1 => 'Installs start 16 November, in time for the high season, and the rest of November is free too. From January it’s month to month: cancel any time with 30 days’ notice. Pay yearly and get 2 months free.',
                ],
                'note' => [
                    0 => 'Your 12-month plan starts on install day. Billing begins after the two free months.',
                    1 => '',
                ],
                'buttons.0.label' => [
                    0 => 'Apply for a spot',
                    1 => 'Book my free trial',
                ],
            ],
            'faq' => [
                'items.1.answer' => [
                    0 => 'Reviews, upsells and Instagram follows start in the first weeks. Repeat bookings build as your guest list grows, which is why plans run for 12 months.',
                    1 => 'Reviews, upsells and Instagram follows start in the first weeks. Repeat bookings build as your guest list grows, month after month.',
                ],
            ],
            'signup' => [
                'menu_label' => [
                    0 => 'Apply',
                    1 => 'Free trial',
                ],
                'menu_description' => [
                    0 => 'Join the Founding 20. First two months free.',
                    1 => 'Book your free trial. December is on us.',
                ],
                'badge' => [
                    0 => 'Founding 20',
                    1 => 'Free trial',
                ],
                'subtitle' => [
                    0 => 'Apply for the Founding 20 for short-term rental operators. First two months free.',
                    1 => 'Book a free trial for your short-term rentals. Installs start 16 November; the rest of November and all of December are free.',
                ],
                'submit_label' => [
                    0 => 'Submit application',
                    1 => 'Book my free trial',
                ],
                'success_message' => [
                    0 => 'Your application is in. Here’s what we have.',
                    1 => 'Your free trial booking is in. Here’s what we have.',
                ],
            ],
        ],
        'business' => [
            'pricing' => [
                'subtitle' => [
                    0 => 'The device, installation, WiFi page, customer list and support. A 12-month plan, then month to month.',
                    1 => 'The device, installation, WiFi page, customer list and support. Free for December, then month to month.',
                ],
                'footnote' => [
                    0 => 'Several locations? We’ll quote group pricing. Extra devices at the same site: KES 1,500 a month each. Pay quarterly for 5% off, or yearly and get 2 months free. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi’s; we maintain and replace it.',
                    1 => 'Free trial: installs from 16 November, and the rest of November and all of December are free. Then month to month; cancel with 30 days’ notice. Pay yearly and get 2 months free. Several locations? We’ll quote group pricing. Extra devices at the same site: KES 1,500 a month each. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi’s; we maintain and replace it.',
                ],
            ],
            'cta_banner__founding' => [
                'badge' => [
                    0 => 'Founding 20',
                    1 => 'Free trial',
                ],
                'title' => [
                    0 => 'Be one of our Founding 20.',
                    1 => 'Try TenaFi free for the whole of December.',
                ],
                'body' => [
                    0 => '10 businesses get TenaFi installed from December 2026, with the first two months free. In return: honest feedback, results we can share, and referrals if you love it.',
                    1 => 'Installs start 16 November and the rest of November is free too. From January it’s month to month: cancel any time with 30 days’ notice. Pay yearly and get 2 months free.',
                ],
                'note' => [
                    0 => 'Your 12-month plan starts on install day. Billing begins after the two free months.',
                    1 => '',
                ],
                'buttons.0.label' => [
                    0 => 'Apply for a spot',
                    1 => 'Book my free trial',
                ],
            ],
            'signup' => [
                'menu_label' => [
                    0 => 'Sign up',
                    1 => 'Free trial',
                ],
                'menu_description' => [
                    0 => 'Join the Founding 20. First two months free.',
                    1 => 'Book your free trial. December is on us.',
                ],
                'badge' => [
                    0 => 'Founding 20',
                    1 => 'Free trial',
                ],
                'subtitle' => [
                    0 => 'Apply for the Founding 20 for business owners. First two months free.',
                    1 => 'Book a free trial for your business. Installs start 16 November; the rest of November and all of December are free.',
                ],
                'submit_label' => [
                    0 => 'Submit application',
                    1 => 'Book my free trial',
                ],
                'success_message' => [
                    0 => 'Your application is in. Here’s what we have.',
                    1 => 'Your free trial booking is in. Here’s what we have.',
                ],
            ],
        ],
    ];

    public function up(): void
    {
        foreach (self::COPY as $slug => $sections) {
            foreach ($sections as $key => $fields) {
                $section = LandingSection::where('section_key', $key)
                    ->whereHas('page', fn ($q) => $q->where('slug', $slug))
                    ->first();

                foreach ($section ? $fields : [] as $contentKey => [$old, $new]) {
                    $row = $section->contents()->where('content_key', $contentKey)->first();
                    if (! $row) {
                        continue;
                    }
                    $live = self::plain($row->value);
                    $unchanged = $live === self::plain($old);
                    // List rows (buttons.0.label, items.1.answer) only by exact match: their order may have changed.
                    $stale = ! preg_match('/\.\d+\./', $contentKey) && preg_match(self::OLD_OFFER, $live);

                    if ($unchanged || $stale) {
                        $row->update(['value' => $new, 'type' => 'text']);
                    }
                }
            }
        }

        LandingPage::clearCache();
    }

    public function down(): void
    {
        //
    }

    /** The rich-text editor may wrap text in <p> or encode quotes. */
    private static function plain(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
};
