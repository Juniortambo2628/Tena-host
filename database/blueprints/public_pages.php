<?php

/*
|--------------------------------------------------------------------------
| TenaFi public pages blueprint
|--------------------------------------------------------------------------
|
| Default sections, copy and media slots for every public page, taken from
| Glen's final handoff (tenafi-website-handoff, 2026-10-02). Synced into
| landing_pages / landing_sections / landing_content / landing_media by
| App\Services\Cms\PageBlueprint, after which the admin CMS owns the text.
|
| Conventions (same as the CMS editor):
|   - a top-level list of rows flattens to "name.{i}.field" keys
|     (e.g. steps.0.title) and gets its own tab in the editor;
|   - a list nested inside a row is stored as a JSON string
|     (e.g. steps.0.fields);
|   - multi-line text fields (points, features) hold one item per line;
|     end a line with [[feature_key]] to badge it "Coming soon" until that
|     feature is marked live under Site-wide -> Feature status;
|   - "anchor" makes the section reachable at #anchor; adding "menu_label"
|     (and optionally "menu_description") also lists it in that page's
|     header megamenu, so menus always match the page;
|   - a section key may carry a variant suffix ("stats__problem") so one
|     page can use the same section type more than once;
|   - select options are "value|Label" (or just "Label").
|
*/

$img = fn (string $file) => "/legacy/assets/Tena-Landing/{$file}";
$hl = fn (string $text) => '<span class="text-[#FFD300]">'.$text.'</span>';

$consent = 'I agree that TenaFi can contact me on WhatsApp, SMS or email about my application, and I accept the privacy policy.';

$detailsStep = fn (array $extra = []) => [
    'title' => 'Your details',
    'heading' => '',
    'description' => '',
    'fields' => array_merge([
        ['key' => 'firstName', 'label' => 'First name', 'type' => 'text', 'required' => true, 'options' => [], 'placeholder' => '', 'hint' => ''],
        ['key' => 'lastName', 'label' => 'Last name', 'type' => 'text', 'required' => false, 'options' => [], 'placeholder' => '', 'hint' => ''],
        ['key' => 'phone', 'label' => 'WhatsApp number', 'type' => 'tel', 'required' => true, 'options' => [], 'placeholder' => '7XX XXX XXX', 'hint' => '+254'],
        ['key' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => false, 'options' => [], 'placeholder' => '', 'hint' => ''],
    ], $extra),
];

$planField = fn (string $unit) => ['key' => 'plan', 'label' => 'Pick a starting plan', 'type' => 'planCards', 'required' => true, 'options' => ['basic|Basic', 'starter|Starter', 'growth|Growth', 'notSure|Not sure yet'], 'placeholder' => 'starter', 'hint' => "(per {$unit}, per month)"];

$plans = fn (string $unit, array $features) => [
    ['id' => 'basic', 'label' => 'Basic', 'tagline' => $features['basic'][0], 'badge' => '', 'price' => 'KES 3,000', 'price_kes' => '3000', 'unit' => "per {$unit}, per month (about \$23)", 'features' => $features['basic'][1], 'cta' => 'Choose Basic', 'variant' => 'outline'],
    ['id' => 'starter', 'label' => 'Starter', 'tagline' => $features['starter'][0], 'badge' => 'Most popular', 'price' => 'KES 4,500', 'price_kes' => '4500', 'unit' => "per {$unit}, per month (about \$35)", 'features' => $features['starter'][1], 'cta' => 'Choose Starter', 'variant' => 'dark'],
    ['id' => 'growth', 'label' => 'Growth', 'tagline' => $features['growth'][0], 'badge' => '', 'price' => 'KES 6,000', 'price_kes' => '6000', 'unit' => "per {$unit}, per month (about \$47)", 'features' => $features['growth'][1], 'cta' => 'Choose Growth', 'variant' => 'outline'],
];

$founding = fn (string $who) => [
    'title' => 'Founding 20',
    'content' => [
        'badge' => 'Founding 20',
        'title' => 'Be one of our Founding 20.',
        'body' => "10 {$who} get TenaFi installed from December 2026".($who === 'short-term rental operators' ? ', in time for the high season,' : ',').' with the first two months free. In return: honest feedback, results we can share, and referrals if you love it.',
        'note' => 'Your 12-month plan starts on install day. Billing begins after the two free months.',
        'buttons' => [['label' => 'Apply for a spot', 'href' => '']],
    ],
];

return [

    // ------------------------------------------------------------------
    // Site-wide: shared by every public page (and the policy pages)
    // ------------------------------------------------------------------
    'site' => [
        'seo' => [
            'title' => 'Default SEO & social',
            'content' => [
                'site_name' => 'TenaFi',
                'meta_title' => "TenaFi | Africa\u{2019}s guest relationship platform",
                'meta_description' => 'Turn your existing WiFi into growth: higher occupancy for short-term rentals and more Google reviews for local businesses, all managed for you.',
            ],
            'media' => ['og_image' => '/legacy/assets/Tena-logo-square.jpg'],
        ],
        'header' => [
            'title' => 'Header & navigation (default)',
            'content' => [
                'login_label' => 'Login',
                'login_url' => '/login',
                'join_label' => 'Join',
                'links' => [
                    ['label' => 'Short-term rentals', 'href' => '/hosts'],
                    ['label' => 'Business owners', 'href' => '/business'],
                    ['label' => 'How it works', 'href' => '/#how'],
                ],
            ],
            'media' => ['logo' => '/legacy/assets/Tena-logo-square.jpg'],
        ],
        'footer' => [
            'title' => 'Footer',
            'content' => [
                'description' => "Africa\u{2019}s guest relationship platform. Built by Superhosts in Nairobi.",
                'tagline' => "\u{201C}Tena\u{201D} means again in Swahili.",
                'contact_email' => 'glen@tena.host',
                'whatsapp' => '+254 703 501 597',
                'location' => 'Nairobi, Kenya',
                'copyright' => 'Tenafi Host Technologies, Nairobi, Kenya',
                'links' => [
                    ['label' => 'For short-term rental operators', 'href' => '/hosts'],
                    ['label' => 'For business owners', 'href' => '/business'],
                    ['label' => 'How it works', 'href' => '/#how'],
                    ['label' => 'Privacy policy', 'href' => '/privacy'],
                    ['label' => 'Terms', 'href' => '/terms'],
                ],
            ],
        ],
        'feature_status' => [
            'title' => 'Feature status ("Coming soon" badges)',
            'content' => [
                'badge_label' => 'Coming soon',
                'items' => [
                    ['key' => 'guest_homepage', 'label' => 'Guest homepage (house guide, local tips)', 'status' => 'live'],
                    ['key' => 'pms_sync', 'label' => 'PMS / channel manager sync', 'status' => 'live'],
                    ['key' => 'mpesa_extras', 'label' => 'Paid extras by M-Pesa on the guest homepage', 'status' => 'coming_soon'],
                    ['key' => 'monthly_report', 'label' => 'Monthly report', 'status' => 'coming_soon'],
                    ['key' => 'occupancy_alerts', 'label' => 'Occupancy alerts', 'status' => 'coming_soon'],
                    ['key' => 'outage_alerts', 'label' => 'Outage alerts', 'status' => 'coming_soon'],
                    ['key' => 'business_homepage', 'label' => 'Business customer homepage (menu, offers)', 'status' => 'coming_soon'],
                    ['key' => 'tena_direct', 'label' => 'Tena Direct booking page', 'status' => 'coming_soon'],
                    ['key' => 'vip_wifi', 'label' => 'Free and VIP WiFi tiers', 'status' => 'coming_soon'],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Main landing page: /
    // ------------------------------------------------------------------
    'home' => [
        'seo' => [
            'title' => 'SEO & social',
            'content' => [
                'meta_title' => "TenaFi | Africa\u{2019}s guest relationship platform",
                'meta_description' => 'Turn your existing WiFi into growth: higher occupancy for short-term rentals and more Google reviews for local businesses, all managed for you.',
            ],
        ],
        'hero' => [
            'title' => 'Hero',
            'bg' => 'gray',
            'content' => [
                'badge' => "TenaFi | Africa\u{2019}s guest relationship platform",
                'title' => 'Turn your existing WiFi into '.$hl('growth.'),
                'subtitle' => '<strong>Higher occupancy for short-term rentals. More Google reviews for local businesses.</strong>',
                'body' => 'TenaFi turns your existing WiFi into a powerful marketing tool. Capture guest contacts, generate reviews and bring customers back with WhatsApp, SMS and email campaigns, all managed for you.',
                'cta_primary' => 'Get started',
                'cta_primary_url' => '#choose',
                'cta_secondary' => 'See how it works',
                'cta_secondary_url' => '#how',
                'note' => '',
            ],
            'media' => ['main_image' => $img('Tena-Hero-1.jpg')],
        ],
        'path_cards' => [
            'title' => 'Which one are you?',
            'content' => [
                'anchor' => 'choose',
                'title' => 'Which one are you?',
                'subtitle' => '',
                'cards' => [
                    ['label' => 'For short-term rental operators', 'title' => 'Turn your existing WiFi into higher occupancy.', 'description' => 'Turn every stay into reviews, repeat bookings and less dependence on OTAs.', 'cta' => 'Grow your occupancy', 'href' => '/hosts', 'event' => 'path_card_hosts'],
                    ['label' => 'For business owners', 'title' => 'Turn your existing WiFi into more Google reviews.', 'description' => 'Turn everyday visitors into lasting customer relationships with review requests and follow-up marketing.', 'cta' => 'Get more reviews', 'href' => '/business', 'event' => 'path_card_business'],
                ],
            ],
            'media' => [
                'card_0_image' => $img('Tena-Welcome-Divine-1.jpg'),
                'card_1_image' => $img('Clients-view.jpg'),
            ],
        ],
        'comparison__problem' => [
            'title' => 'The problem',
            'content' => [
                'anchor' => 'problem',
                'badge' => 'The problem',
                'title' => 'Your guests connect. Then leave as strangers.',
                'subtitle' => 'Every day, guests connect to your WiFi, enjoy your service and leave. Most businesses have no effective way to stay in touch.',
                'note' => 'The WiFi login is the one moment every guest connects. TenaFi turns it into a relationship, and runs the follow-up for you.',
                'sources' => 'Airbtics, Nairobi median occupancy 44% (Feb 2025 to Jan 2026); AirDNA puts it at about 50% (August 2026). BrightLocal, Local Consumer Review Survey 2026.',
                'columns' => [
                    ['label' => 'Short-term rentals', 'stat' => '56%', 'stat_label' => 'of nights go unbooked in a typical Nairobi Airbnb', 'points' => "Guests leave as strangers; the OTA keeps the contact\nRepeat guests rebook through the OTA at ~15%\nNo one markets to past guests, so nights sit empty", 'text' => '', 'highlight' => '0'],
                    ['label' => 'Local businesses', 'stat' => '47%', 'stat_label' => 'of consumers skip a business with fewer than 20 reviews', 'points' => "QR review cards need the customer\u{2019}s own data and effort\nOne chance on the spot, then the customer is gone\nFewer reviews means fewer new customers on Google", 'text' => '', 'highlight' => '0'],
                ],
            ],
        ],
        'how_it_works' => [
            'title' => 'How it works',
            'content' => [
                'anchor' => 'how',
                'title' => 'How TenaFi works',
                'subtitle' => "The same four steps for rentals and local businesses. We don\u{2019}t sell internet; TenaFi plugs into the connection you already have.",
                'steps' => [
                    ['step' => '1. Connect', 'icon' => 'fas fa-wifi', 'title' => 'We install your branded WiFi', 'description' => 'Our team installs the TenaFi device on your existing internet connection and sets up your branded guest WiFi.', 'feature' => ''],
                    ['step' => '2. Capture', 'icon' => 'fas fa-address-card', 'title' => 'Guests opt in', 'description' => 'Guests connect through your branded welcome page and share their details with consent. No app.', 'feature' => ''],
                    ['step' => '3. Engage', 'icon' => 'fas fa-comments', 'title' => 'We run the follow-up', 'description' => 'We manage your review requests and marketing campaigns on WhatsApp, SMS and email.', 'feature' => ''],
                    ['step' => '4. Grow', 'icon' => 'fas fa-chart-line', 'title' => 'They come back', 'description' => 'More reviews, more repeat visits and more direct bookings, with results in a monthly report.', 'feature' => ''],
                ],
            ],
        ],
        'cta_banner__founding' => [
            'title' => 'Built by Superhosts / Founding 20',
            'content' => [
                'badge' => 'Founding 20',
                'title' => 'Built by a 16x Airbnb Superhost team in Nairobi.',
                'body' => 'TenaFi was born inside Stay Awhile Rentals: 1,400+ reservations, 5,000+ guest nights and 800+ reviews. We built the tool we needed. Now our Founding 20 (10 rentals and 10 businesses) get it from December 2026, with the first two months free.',
                'note' => '',
                'buttons' => [
                    ['label' => 'I run short-term rentals', 'href' => '/hosts#join'],
                    ['label' => 'I own a business', 'href' => '/business#signup'],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Short-term rental operators: /hosts
    // ------------------------------------------------------------------
    'hosts' => [
        'seo' => [
            'title' => 'SEO & social',
            'content' => [
                'meta_title' => 'TenaFi for short-term rental operators: turn your WiFi into higher occupancy',
                'meta_description' => 'Turn your existing WiFi into higher occupancy. TenaFi turns every guest into someone you can invite back, so past guests fill your empty nights and book direct, commission-free.',
            ],
        ],
        'hero' => [
            'title' => 'Hero',
            'bg' => 'gray',
            'content' => [
                'badge' => 'For short-term rental operators',
                'title' => 'Turn your existing WiFi into '.$hl('higher occupancy.'),
                'subtitle' => 'The typical Nairobi Airbnb sits empty more than half the time, and new listings keep arriving. TenaFi turns every guest who connects to your WiFi into someone you can invite back, so past guests fill the gaps between your OTA bookings and book direct, commission-free.',
                'body' => '',
                'cta_primary' => 'Grow your occupancy',
                'cta_primary_url' => '#join',
                'cta_secondary' => 'See how it works',
                'cta_secondary_url' => '#h-how',
                'note' => "Africa\u{2019}s guest relationship platform. No new internet needed. Live in 7 days. From KES 3,000 per unit a month.",
            ],
            'media' => ['main_image' => $img('Branded-Splash-Page.jpg')],
        ],
        'stats__problem' => [
            'title' => 'The real problem',
            'content' => [
                'anchor' => 'h-problem',
                'menu_label' => 'Occupancy',
                'badge' => 'The real problem',
                'title' => "It isn\u{2019}t just OTA fees. It\u{2019}s empty nights.",
                'subtitle' => 'Nairobi added listings much faster than it added guests, so more hosts are chasing the same OTA traffic. Meanwhile every guest who already loved your place leaves without you keeping their contact, and the only way they can find you again is through the OTA.',
                'note' => '',
                'image_caption' => 'Illustration only, not a forecast. Results depend on your property, season and how long your guest list has been growing.',
                'sources' => 'Airbtics: Nairobi median occupancy 44% (Feb 2025 to Jan 2026) and Kenya Short-Term Rental Market Review 2025. AirDNA puts Nairobi occupancy at about 50% (August 2026). Airbnb 15.5% host fee; Booking.com ~15% average (2026).',
                'stats' => [
                    ['value' => '56%', 'label' => 'of nights go unbooked in a typical Nairobi Airbnb (median occupancy 44%)'],
                    ['value' => '+52%', 'label' => 'more Nairobi listings in 2025, while revenue per listing fell 8%'],
                    ['value' => '~15%', 'label' => 'OTA fee paid again on every repeat stay that comes back through Airbnb or Booking.com'],
                ],
            ],
        ],
        'features__outcomes' => [
            'title' => 'Three things improve at once',
            'content' => [
                'title' => 'Three things improve at once.',
                'subtitle' => '',
                'items' => [
                    ['step' => '1. The main goal', 'icon' => 'fas fa-calendar-check', 'title' => 'Higher occupancy', 'description' => "Every month we send your past guests offers for your empty dates, holidays and low season. A guest who already stayed with you is the easiest booking you\u{2019}ll ever get.", 'feature' => ''],
                    ['step' => '2.', 'icon' => 'fas fa-handshake', 'title' => 'More direct bookings', 'description' => 'Returning guests book with you directly instead of searching the OTA again, so the relationship stays yours.', 'feature' => ''],
                    ['step' => '3.', 'icon' => 'fas fa-percent', 'title' => 'Lower OTA fees', 'description' => "Every direct booking is one you don\u{2019}t pay ~15% commission on. The savings come with the occupancy, not instead of it.", 'feature' => ''],
                ],
            ],
        ],
        'how_it_works' => [
            'title' => 'How TenaFi fills your calendar',
            'content' => [
                'anchor' => 'h-how',
                'menu_label' => 'How it works',
                'title' => 'How TenaFi fills your calendar',
                'subtitle' => 'Value from the first week, long before a guest comes back. Then it keeps building: the longer TenaFi runs, the bigger your guest list and the more nights it can help fill.',
                'steps' => [
                    ['step' => 'During every stay', 'icon' => 'fas fa-wifi', 'title' => 'Every guest joins your list', 'description' => 'Our device plugs into your router. Guests connect through your branded WiFi page and opt in: everyone in the party, not just the booker.', 'feature' => ''],
                    ['step' => 'Week one', 'icon' => 'fas fa-concierge-bell', 'title' => 'Upsells straight away', 'description' => 'Late checkout, extra nights, cleaning and welcome packs, offered during the stay and paid by M-Pesa.', 'feature' => 'mpesa_extras'],
                    ['step' => 'Month one', 'icon' => 'fas fa-star', 'title' => 'Reviews and follows', 'description' => 'After checkout, a thank-you on WhatsApp, SMS or email asks for an Airbnb or Google review and an Instagram follow.', 'feature' => ''],
                    ['step' => 'Every month', 'icon' => 'fas fa-bullhorn', 'title' => 'Campaigns for your empty dates', 'description' => 'We write and send offers to your guest list for gaps, holidays and low season, with a code to book direct.', 'feature' => ''],
                    ['step' => 'All year', 'icon' => 'fas fa-home', 'title' => 'Guests come back and book direct', 'description' => 'On your Tena Direct page: 0% commission and no guest fees, included on Starter and Growth. Your monthly report shows guests captured, campaigns sent and direct bookings.', 'feature' => 'tena_direct'],
                ],
            ],
        ],
        'stats__commission' => [
            'title' => 'Commission comparison',
            'bg' => 'gray',
            'content' => [
                'badge' => '',
                'title' => "What a returning guest\u{2019}s KES 100,000 stay costs you in commission",
                'subtitle' => '',
                'note' => '',
                'image_caption' => '',
                'sources' => '',
                'stats' => [
                    ['value' => 'KES 15,500', 'label' => 'Back through Airbnb (15.5%)'],
                    ['value' => 'about KES 15,000', 'label' => 'Back through Booking.com (~15%)'],
                    ['value' => 'KES 0', 'label' => 'Direct on Tena Direct'],
                ],
            ],
        ],
        'comparison__party' => [
            'title' => 'Every guest, not just the booker',
            'content' => [
                'anchor' => 'h-party',
                'badge' => 'Every guest, not just the booker',
                'title' => 'The OTA gives you one name. TenaFi gets the whole party.',
                'subtitle' => "A booking for four comes with one name and a masked contact. But all four guests connect to your WiFi, and each can opt in. That\u{2019}s four people who can come back, recommend you and bring friends.",
                'note' => '',
                'sources' => '',
                'columns' => [
                    ['label' => 'From the booking platform', 'stat' => '1', 'stat_label' => 'name', 'points' => '', 'text' => "One booker\u{2019}s name. The contact stays inside the OTA.", 'highlight' => '0'],
                    ['label' => 'From your TenaFi WiFi', 'stat' => '4', 'stat_label' => 'guests', 'points' => '', 'text' => 'Everyone who connects and opts in joins your own guest list, with consent.', 'highlight' => '1'],
                ],
            ],
        ],
        'detailed_features' => [
            'title' => 'What your guests see, and what you get',
            'content' => [
                'anchor' => 'h-product',
                'menu_label' => 'Product',
                'title' => 'What your guests see, and what you get',
                'subtitle' => "From WiFi login to a booking you don\u{2019}t pay commission on.",
                'cta_text' => 'Grow your occupancy',
                'sections' => [
                    [
                        'label' => '1. Your guest homepage',
                        'heading' => 'House guide, local tips and paid extras.',
                        'description' => 'After connecting, guests get the house guide, local tips and paid extras.',
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-wifi', 'title' => 'WiFi details and house guide', 'desc' => 'Answers before guests have to message you.', 'feature' => 'guest_homepage'],
                            ['icon' => 'fas fa-map-marker-alt', 'title' => 'Local tips and contact host', 'desc' => 'Your favourite spots, one tap away.', 'feature' => 'guest_homepage'],
                            ['icon' => 'fas fa-mobile-alt', 'title' => 'Late checkout, extra nights, cleaning', 'desc' => 'Requested during the stay and paid by M-Pesa.', 'feature' => 'mpesa_extras'],
                        ],
                    ],
                    [
                        'label' => '2. Messages we send for you',
                        'heading' => 'Thank-yous, review requests and offers for your empty dates.',
                        'description' => "\u{201C}Hi Amina, asante for staying with us in Kilimani! Would you leave us a quick review? Coming back to Nairobi? Book direct next time with code KARIBU10.\u{201D}",
                        'bg' => 'gray', 'reverse' => '1',
                        'features' => [
                            ['icon' => 'fab fa-whatsapp', 'title' => 'WhatsApp, SMS and email', 'desc' => 'Written, scheduled and sent for you.', 'feature' => ''],
                            ['icon' => 'fas fa-star', 'title' => 'Leave a review', 'desc' => 'An Airbnb or Google review request after checkout.', 'feature' => ''],
                            ['icon' => 'fas fa-calendar-plus', 'title' => 'Book direct', 'desc' => 'Offers with a code for your empty dates.', 'feature' => ''],
                        ],
                    ],
                    [
                        'label' => '3. Results you can see',
                        'heading' => 'One report every month.',
                        'description' => 'So you know exactly what TenaFi is doing for your occupancy: guests added to your list, review requests sent, upsells sold, nights booked direct and OTA commission avoided. Example only; your numbers will differ.',
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-file-alt', 'title' => 'Your monthly report', 'desc' => 'Guests captured, campaigns sent and direct bookings.', 'feature' => 'monthly_report'],
                        ],
                    ],
                ],
            ],
            'media' => [
                'section_0_image' => $img('Tena-Portrait-1.jpg'),
                'section_1_image' => $img('Tena-Welcome-Divine.jpg'),
                'section_2_image' => $img('Tena-Features-1.jpg'),
            ],
        ],
        'features__protect' => [
            'title' => 'Protect your property',
            'bg' => 'gray',
            'content' => [
                'anchor' => 'h-protect',
                'menu_label' => 'Protect your property',
                'menu_description' => 'Occupancy and outage alerts, PMS sync.',
                'title' => 'Protect your property',
                'subtitle' => "Know what\u{2019}s happening at your units, even when you\u{2019}re not there.",
                'items' => [
                    ['step' => '', 'icon' => 'fas fa-plug', 'title' => 'Plug and play', 'description' => 'The TenaFi device plugs into your existing router. Our installer sets it up and tests it; nothing for you to configure.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-user-shield', 'title' => 'Occupancy alerts', 'description' => 'Get an alert when more people connect than the booking allows: an early warning for parties and extra guests.', 'feature' => 'occupancy_alerts'],
                    ['step' => '', 'icon' => 'fas fa-exclamation-triangle', 'title' => 'Outage alerts', 'description' => 'Know when the internet drops at a unit before a guest messages you about it.', 'feature' => 'outage_alerts'],
                    ['step' => '', 'icon' => 'fas fa-sync', 'title' => 'PMS and channel manager sync', 'description' => 'Keep bookings and guest details in sync with the tools you already use to run your units.', 'feature' => 'pms_sync'],
                ],
            ],
        ],
        'credibility' => [
            'title' => 'The experience behind TenaFi',
            'content' => [
                'badge' => 'The experience behind TenaFi',
                'title' => 'Built inside our own rentals first.',
                'subtitle' => 'TenaFi was born from Stay Awhile Rentals, our short-term rental business in Nairobi. After years of hosting thousands of guests, we saw how hard it is to fill quiet nights when every guest belongs to the OTA. So we built the tool we needed.',
                'closing_line' => '',
                'tagline' => '',
                'stats' => [
                    ['value' => '16x', 'label' => 'Airbnb Superhost'],
                    ['value' => '1,400+', 'label' => 'reservations'],
                    ['value' => '5,000+', 'label' => 'guest nights'],
                    ['value' => '800+', 'label' => 'guest reviews'],
                    ['value' => '4.9/5', 'label' => 'guest rating'],
                ],
            ],
            'media' => [
                'main_image' => $img('Tena-Hero-1.jpg'),
                'stay_awhile_logo' => '/legacy/assets/Tena-logo-square.jpg',
            ],
        ],
        'pricing' => [
            'title' => 'Pricing',
            'bg' => 'gray',
            'content' => [
                'anchor' => 'h-pricing',
                'menu_label' => 'Pricing',
                'title' => 'One price per unit. Everything included.',
                'subtitle' => 'The device, installation, WiFi page, guest list and support, on a 12-month plan, then month to month.',
                'footnote' => "Multi-unit operators: 20% off from 10 units, 30% off from 50. Extra devices at the same site: KES 1,500 a month each. Pay quarterly for 5% off, or yearly and get 2 months free. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi\u{2019}s; we maintain and replace it.",
                'show_contact_form' => '0',
                'cta_headline' => '',
                'plans' => $plans('unit', [
                    'basic' => ['Capture guests and collect reviews', "Branded WiFi page and guest list\nA review request to every guest, by SMS and email\nA campaign every quarter"],
                    'starter' => ['A campaign every month to fill empty dates', "Everything in Basic\nA campaign and results report every month [[monthly_report]]\nUpsells, and your Tena Direct page at 0% commission [[tena_direct]]"],
                    'growth' => ['Full done-for-you marketing', "Everything in Starter\n2 to 4 campaigns a month\nFeatured on Tena Direct, plus VIP WiFi tiers [[vip_wifi]]"],
                ]),
            ],
        ],
        'cta_banner__founding' => $founding('short-term rental operators'),
        'faq' => [
            'title' => 'Questions operators ask',
            'content' => [
                'title' => 'Questions operators ask',
                'items' => [
                    ['question' => 'How does this increase my occupancy?', 'answer' => 'Every stay adds guests to your own list. Each month we send that list offers for your empty dates, holidays and low season. Past guests already know and trust your place, which makes them some of your likeliest bookings. The list grows with every stay, so it can fill more nights over time. TenaFi is designed to help raise occupancy; results vary by property and season.'],
                    ['question' => 'How soon will I see results?', 'answer' => 'Reviews, upsells and Instagram follows start in the first weeks. Repeat bookings build as your guest list grows, which is why plans run for 12 months.'],
                    ['question' => 'Do I need new internet?', 'answer' => 'No. The TenaFi device plugs into your existing router and adds a separate guest network. Your own network stays private.'],
                    ['question' => 'Who owns the guest data?', 'answer' => "You do. Guests opt in on your WiFi page, and every contact is collected with consent under Kenya\u{2019}s Data Protection Act, 2019."],
                    ['question' => 'Do I have to write the messages?', 'answer' => 'No. We write, schedule and send every campaign, and you approve anything that goes out under your name.'],
                    ['question' => 'I manage lots of units.', 'answer' => 'You get 20% off from 10 units and 30% off from 50, and we install building by building. One report covers every unit.'],
                ],
            ],
        ],
        'cta_banner__crosssell' => [
            'title' => 'Cross-link to /business',
            'bg' => 'gray',
            'content' => [
                'badge' => '',
                'title' => 'Run a café, salon, clinic or other business?',
                'body' => 'Turn your existing WiFi into more Google reviews.',
                'note' => '',
                'buttons' => [['label' => 'TenaFi for business owners', 'href' => '/business']],
            ],
        ],
        'signup' => [
            'title' => 'Sign-up (#join)',
            'content' => [
                'anchor' => 'join',
                'menu_label' => 'Apply',
                'menu_description' => 'Join the Founding 20. First two months free.',
                'signup_type' => 'host',
                'badge' => 'Founding 20',
                'title' => "Let\u{2019}s get started",
                'subtitle' => 'Apply for the Founding 20 for short-term rental operators. First two months free.',
                'submit_label' => 'Submit application',
                'consent_text' => $consent,
                'success_title' => 'Asante, {firstName}!',
                'success_message' => "Your application is in. Here\u{2019}s what we have.",
                'next_steps_title' => 'What happens next',
                'next_steps' => "We message you on WhatsApp to book a short setup call.\nWe check your routers and agree install dates, building by building if you have several units.\nYour first guests are captured within 7 days of install.",
                'error_message' => 'Something went wrong. Please try again or WhatsApp us on +254 703 501 597.',
                'price_suffix' => 'per unit / month',
                'discount_field' => 'units',
                'discount_hint' => 'Hosts with 10 or more units get 20% off, and 30% off from 50.',
                'discount_applied' => 'Includes your {percent}% multi-unit discount.',
                'discounts' => [
                    ['match' => '10-49', 'percent' => '20'],
                    ['match' => '50+', 'percent' => '30'],
                ],
                'steps' => [
                    $detailsStep(),
                    ['title' => 'Your rentals', 'heading' => 'Tell us about your rentals', 'description' => 'So we can plan your devices and campaigns.', 'fields' => [
                        ['key' => 'units', 'label' => 'How many units?', 'type' => 'select', 'required' => true, 'options' => ['1|1 unit', '2-4|2 to 4', '5-9|5 to 9', '10-49|10 to 49', '50+|50 or more'], 'placeholder' => '', 'hint' => ''],
                        ['key' => 'area', 'label' => 'Where are they?', 'type' => 'text', 'required' => false, 'options' => [], 'placeholder' => 'e.g. Kilimani, Nairobi', 'hint' => ''],
                        ['key' => 'platforms', 'label' => 'Where do guests book you today?', 'type' => 'multiSelect', 'required' => false, 'options' => ['Airbnb', 'Booking.com', 'Vrbo', 'Direct or own website', 'Other'], 'placeholder' => '', 'hint' => ''],
                        ['key' => 'superhost', 'label' => 'Are you an Airbnb Superhost?', 'type' => 'singleSelect', 'required' => false, 'options' => ['Yes', 'Not yet'], 'placeholder' => '', 'hint' => '(optional)'],
                        ['key' => 'isp', 'label' => 'Internet provider', 'type' => 'select', 'required' => false, 'options' => ['Safaricom', 'Zuku', 'Faiba', 'Airtel', 'Different per unit', 'Other', 'Not sure'], 'placeholder' => '', 'hint' => ''],
                        $planField('unit'),
                    ]],
                ],
            ],
        ],
        'roi_calculator' => [
            'title' => 'ROI calculator',
            'is_active' => false,
            'content' => ['commission_rate' => '15'],
        ],
    ],

    // ------------------------------------------------------------------
    // Business owners: /business
    // ------------------------------------------------------------------
    'business' => [
        'seo' => [
            'title' => 'SEO & social',
            'content' => [
                'meta_title' => 'TenaFi for business owners: turn your WiFi into more Google reviews',
                'meta_description' => 'Turn your existing WiFi into more Google reviews. Every customer who connects gets a same-day thank-you with a one-tap review link, then offers that bring them back. Managed for you.',
            ],
        ],
        'hero' => [
            'title' => 'Hero',
            'bg' => 'gray',
            'content' => [
                'badge' => 'For business owners',
                'title' => 'Turn your existing WiFi into '.$hl('more Google reviews.'),
                'subtitle' => 'Every customer who connects gets a same-day thank-you with a one-tap Google review link, with no QR card to scan. Then we send offers that bring them back. Turn everyday visitors into lasting customer relationships, all managed for you.',
                'body' => '',
                'cta_primary' => 'Get more reviews',
                'cta_primary_url' => '#signup',
                'cta_secondary' => 'Learn how it works',
                'cta_secondary_url' => '#b-how',
                'note' => "Africa\u{2019}s guest relationship platform. No new internet needed. Live in 7 days. From KES 3,000 a month.",
            ],
            'media' => ['main_image' => $img('Clients-view.jpg')],
        ],
        'features__why' => [
            'title' => 'Why businesses choose TenaFi',
            'content' => [
                'title' => 'Why businesses choose TenaFi',
                'subtitle' => 'Your WiFi already meets every customer. Now it remembers them.',
                'items' => [
                    ['step' => '', 'icon' => 'fas fa-address-book', 'title' => 'Build your customer list', 'description' => 'Capture names and phone numbers, with consent, every time someone joins your WiFi. The list belongs to you.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fab fa-google', 'title' => 'Get more Google reviews', 'description' => 'Every customer gets a same-day thank-you with a one-tap review link, not just the few who scan a sign.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-redo', 'title' => 'Bring customers back', 'description' => 'Second-visit offers, birthday treats and event invites on WhatsApp, SMS and email, sent for you every month.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-hands-helping', 'title' => 'Done for you, from day one', 'description' => 'Our installer plugs the TenaFi device into your existing router. We build your WiFi page and run the campaigns.', 'feature' => ''],
                ],
            ],
        ],
        'comparison__qr' => [
            'title' => 'QR card vs TenaFi',
            'bg' => 'gray',
            'content' => [
                'anchor' => 'b-qr',
                'badge' => '',
                'title' => 'The QR card waits for a scan. TenaFi asks every customer.',
                'subtitle' => 'More Google reviews without asking customers to spend their own data or scan anything.',
                'note' => 'Fresh reviews, all year. Most customers only care about recent reviews. QR cards bring reviews in bursts; TenaFi asks every month, so your rating stays current and new customers keep finding you.',
                'sources' => '',
                'columns' => [
                    ['label' => 'Today: a QR card on the table', 'stat' => '', 'stat_label' => '', 'points' => "Needs the customer\u{2019}s own mobile data\nNeeds effort: scan, open, write, on the spot\nOne chance; once they leave, it\u{2019}s gone", 'text' => 'Many happy customers never scan, so they never review.', 'highlight' => '0'],
                    ['label' => "TenaFi: a \u{201C}Free WiFi\u{201D} sign", 'stat' => '', 'stat_label' => '', 'points' => "Customers want to connect; it saves their data\nA branded welcome page that looks professional\nFree and VIP WiFi tiers that you control [[vip_wifi]]", 'text' => 'Every customer who connects gets a review request later that day. No scan, no effort.', 'highlight' => '1'],
                ],
            ],
        ],
        'features__industries' => [
            'title' => 'Who it fits',
            'content' => [
                'title' => 'If your WiFi password is on the wall, TenaFi fits.',
                'subtitle' => 'Any business where customers sit, wait or linger. Same device, same service; only the campaigns change.',
                'items' => [
                    ['step' => '', 'icon' => 'fas fa-mug-hot', 'title' => 'Cafés and restaurants', 'description' => 'Turn first-time diners into regulars.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-cut', 'title' => 'Salons, barbers and spas', 'description' => 'Fill quiet weekdays with offers to past clients.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-tooth', 'title' => 'Clinics and dental practices', 'description' => 'Reviews from every patient in the waiting room.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-dumbbell', 'title' => 'Gyms and studios', 'description' => 'Win back members who stopped coming.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-glass-cheers', 'title' => 'Bars, lounges and event spaces', 'description' => 'Fill event nights from your own list.', 'feature' => ''],
                    ['step' => '', 'icon' => 'fas fa-store', 'title' => 'Shops and showrooms', 'description' => 'Turn browsers into people you can invite back.', 'feature' => ''],
                ],
            ],
        ],
        'how_it_works' => [
            'title' => 'How it works',
            'content' => [
                'anchor' => 'b-how',
                'menu_label' => 'How it works',
                'title' => 'How TenaFi works',
                'subtitle' => "Four steps, and we run all of them. We don\u{2019}t sell internet; TenaFi plugs into the connection you already have.",
                'steps' => [
                    ['step' => 'Step 1', 'icon' => 'fas fa-wifi', 'title' => 'Customer joins your WiFi', 'description' => "They see \u{201C}Free WiFi\u{201D} on the door, menu, reception desk or table card.", 'feature' => ''],
                    ['step' => 'Step 2', 'icon' => 'fas fa-user-check', 'title' => 'They opt in on your page', 'description' => 'Your logo, a reason to join like a birthday treat, and a name and number. No app.', 'feature' => ''],
                    ['step' => 'Step 3', 'icon' => 'fab fa-google', 'title' => 'Same-day review request', 'description' => 'A thank-you on WhatsApp, SMS or email with a one-tap link to your Google review page.', 'feature' => ''],
                    ['step' => 'Step 4', 'icon' => 'fas fa-redo', 'title' => 'A reason to come back', 'description' => 'Monthly offers, birthdays and events, plus a report on new reviews and return visits.', 'feature' => ''],
                ],
            ],
        ],
        'detailed_features' => [
            'title' => 'What your customers see, and what you get',
            'content' => [
                'anchor' => 'b-product',
                'menu_label' => 'Product',
                'title' => 'What your customers see, and what you get',
                'subtitle' => 'From WiFi login to a new Google review. Example businesses.',
                'cta_text' => 'Get more reviews',
                'sections' => [
                    [
                        'label' => '1. Your customer homepage',
                        'heading' => "Your menu, today\u{2019}s offer and events.",
                        'description' => "After connecting, customers see your menu, today\u{2019}s offer and events.",
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-store', 'title' => 'Menu, offers, events and birthday treats', 'desc' => 'Your business in front of every customer.', 'feature' => 'business_homepage'],
                        ],
                    ],
                    [
                        'label' => '2. A review request, the same day',
                        'heading' => 'Sent to every customer who connected, not just the happy few.',
                        'description' => "\u{201C}Hi Brian, thanks for visiting Kahawa House today! How did we do? It takes 30 seconds to share your experience on Google.\u{201D}",
                        'bg' => 'gray', 'reverse' => '1',
                        'features' => [
                            ['icon' => 'fab fa-google', 'title' => 'Review us on Google', 'desc' => 'One tap to your Google review page.', 'feature' => ''],
                            ['icon' => 'fas fa-tags', 'title' => "See this week\u{2019}s offers", 'desc' => 'A reason to come back.', 'feature' => ''],
                        ],
                    ],
                    [
                        'label' => '3. Results you can see',
                        'heading' => 'New reviews, your rating trend and return visits, every month.',
                        'description' => 'Customers added to your list, review requests sent, new Google reviews, your rating and return visits from offers. Example only; your numbers will differ.',
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-file-alt', 'title' => 'Your monthly report', 'desc' => 'Reviews and return visits in one place.', 'feature' => 'monthly_report'],
                        ],
                    ],
                ],
            ],
            'media' => [
                'section_0_image' => $img('Tena-Welcome-Divine.jpg'),
                'section_1_image' => $img('Branded-Splash-Page.jpg'),
                'section_2_image' => $img('Tena-Features-1.jpg'),
            ],
        ],
        'stats__reviews' => [
            'title' => 'Did you know?',
            'bg' => 'gray',
            'content' => [
                'anchor' => 'b-reviews',
                'menu_label' => 'Google reviews',
                'badge' => 'Did you know?',
                'title' => 'Reviews decide who finds you. Repeat visits decide your profit.',
                'subtitle' => '',
                'note' => "By Google\u{2019}s rules. We ask every customer for a review and never reward reviews or filter out unhappy ones.",
                'image_caption' => '',
                'sources' => 'Sources: BrightLocal Local Consumer Review Survey 2026 (US consumers); Thanx study of restaurant and retail customers, 2018.',
                'stats' => [
                    ['value' => '97%', 'label' => 'of consumers read reviews for local businesses'],
                    ['value' => '47%', 'label' => "won\u{2019}t use a business with fewer than 20 reviews"],
                    ['value' => '28%', 'label' => 'always write a review when asked, up from 16% a year earlier'],
                    ['value' => '~70%', 'label' => 'of first-time restaurant guests never come back'],
                ],
            ],
        ],
        'pricing' => [
            'title' => 'Pricing',
            'content' => [
                'anchor' => 'b-pricing',
                'menu_label' => 'Pricing',
                'title' => 'One price per location. Everything included.',
                'subtitle' => 'The device, installation, WiFi page, customer list and support. A 12-month plan, then month to month.',
                'footnote' => "Several locations? We\u{2019}ll quote group pricing. Extra devices at the same site: KES 1,500 a month each. Pay quarterly for 5% off, or yearly and get 2 months free. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi\u{2019}s; we maintain and replace it.",
                'show_contact_form' => '0',
                'cta_headline' => '',
                'plans' => $plans('location', [
                    'basic' => ['Google reviews from every customer', "A Google review request to every customer\nBy SMS and email\nA campaign every quarter"],
                    'starter' => ['A campaign every month', "Everything in Basic\nA campaign every month on WhatsApp, SMS and email\nA monthly report on reviews and return visits [[monthly_report]]"],
                    'growth' => ['Full done-for-you marketing', "Everything in Starter\n2 to 4 campaigns a month\nFree and VIP WiFi tiers [[vip_wifi]]"],
                ]),
            ],
        ],
        'cta_banner__founding' => $founding('businesses'),
        'faq' => [
            'title' => 'Questions owners ask',
            'content' => [
                'title' => 'Questions owners ask',
                'items' => [
                    ['question' => 'Do I need new internet?', 'answer' => 'No. The TenaFi device plugs into the router you already have and adds a separate customer network. Your business network stays private.'],
                    ['question' => 'What do you collect from customers?', 'answer' => "Only what they choose to give: a name, phone number, and optionally an email or birthday. Never health, payment or ID details. Every contact is collected with consent under Kenya\u{2019}s Data Protection Act, 2019, and the list belongs to your business."],
                    ['question' => 'Is asking for Google reviews allowed?', 'answer' => "Yes, as long as you ask every customer and never offer a reward for a review. That\u{2019}s exactly how TenaFi works."],
                    ['question' => 'Do I have to write the messages?', 'answer' => 'No. We draft, schedule and send every campaign, and you approve anything that goes out under your name.'],
                    ['question' => 'How long does setup take?', 'answer' => 'About a week. We build your WiFi page from your logo, our installer plugs in and tests the device, and your first customers join by day seven.'],
                    ['question' => 'I have more than one location.', 'answer' => 'Each location gets its own device and page, and you see every location in one report. Talk to us about group pricing.'],
                ],
            ],
        ],
        'cta_banner__crosssell' => [
            'title' => 'Cross-link to /hosts',
            'bg' => 'gray',
            'content' => [
                'badge' => '',
                'title' => 'Run short-term rentals or a hotel?',
                'body' => 'Turn your existing WiFi into higher occupancy.',
                'note' => '',
                'buttons' => [['label' => 'TenaFi for short-term rentals', 'href' => '/hosts']],
            ],
        ],
        'signup' => [
            'title' => 'Sign-up (#signup)',
            'content' => [
                'anchor' => 'signup',
                'menu_label' => 'Sign up',
                'menu_description' => 'Join the Founding 20. First two months free.',
                'signup_type' => 'business',
                'badge' => 'Founding 20',
                'title' => "Let\u{2019}s get started",
                'subtitle' => 'Apply for the Founding 20 for business owners. First two months free.',
                'submit_label' => 'Submit application',
                'consent_text' => $consent,
                'success_title' => 'Asante, {firstName}!',
                'success_message' => "Your application is in. Here\u{2019}s what we have.",
                'next_steps_title' => 'What happens next',
                'next_steps' => "We message you on WhatsApp to book a short setup call.\nIf you don\u{2019}t have a Google Business Profile yet, we help you set one up.\nYour WiFi page goes live and your first customers join within 7 days of install.",
                'error_message' => 'Something went wrong. Please try again or WhatsApp us on +254 703 501 597.',
                'price_suffix' => 'per location / month',
                'discount_field' => '',
                'discount_hint' => '',
                'discount_applied' => '',
                'steps' => [
                    $detailsStep([
                        ['key' => 'role', 'label' => 'Your role', 'type' => 'select', 'required' => false, 'options' => ['Owner', 'Manager', 'Marketing', 'Other'], 'placeholder' => '', 'hint' => ''],
                    ]),
                    ['title' => 'Your business', 'heading' => 'Tell us about your business', 'description' => 'We set up your WiFi page and campaigns around it.', 'fields' => [
                        ['key' => 'businessName', 'label' => 'Business name', 'type' => 'text', 'required' => true, 'options' => [], 'placeholder' => '', 'hint' => ''],
                        ['key' => 'businessType', 'label' => 'Type of business', 'type' => 'select', 'required' => false, 'options' => ['Café', 'Restaurant', 'Bar or lounge', 'Salon, barber or spa', 'Clinic or dental practice', 'Gym or studio', 'Shop or showroom', 'Event space', 'Other'], 'placeholder' => '', 'hint' => ''],
                        ['key' => 'locations', 'label' => 'Locations', 'type' => 'select', 'required' => false, 'options' => ['1 location', '2 to 5', '6 to 24', '25 or more'], 'placeholder' => '', 'hint' => ''],
                        ['key' => 'area', 'label' => 'Area', 'type' => 'text', 'required' => false, 'options' => [], 'placeholder' => 'e.g. Westlands, Nairobi', 'hint' => ''],
                        ['key' => 'customersPerDay', 'label' => 'Customers per day', 'type' => 'select', 'required' => false, 'options' => ['Under 30', '30 to 100', '100 to 300', 'More than 300'], 'placeholder' => '', 'hint' => '(roughly)'],
                        ['key' => 'googleProfile', 'label' => 'Do you have a Google Business Profile?', 'type' => 'singleSelect', 'required' => false, 'options' => ['Yes', 'No', 'Not sure'], 'placeholder' => '', 'hint' => ''],
                        ['key' => 'isp', 'label' => 'Internet provider', 'type' => 'select', 'required' => false, 'options' => ['Safaricom', 'Zuku', 'Faiba', 'Airtel', 'Other', 'Not sure'], 'placeholder' => '', 'hint' => ''],
                        $planField('location'),
                    ]],
                ],
            ],
        ],
    ],
];
