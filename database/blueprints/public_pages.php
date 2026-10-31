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

// The free trial (live copy, Oct 2026): installs start 16 November, the rest
// of November is free, then a free month in December. After that it's month
// to month (cancel with 30 days' notice), and paying yearly gives 2 months free.
//
// TODO(Glen): does the KES 2,500 installation fee apply after the trial?
//   Undecided, so no copy mentions it yet.
// TODO(Glen): does the multi-unit discount start at 5 or 10 units?
//   Undecided; the copy and the signup discounts below still say 10.
$founding = fn (string $who) => [
    // Section key kept as cta_banner__founding so live content stays attached.
    'title' => 'Free trial (December)',
    'content' => [
        'badge' => 'Free trial',
        'title' => 'Try TenaFi free for the whole of December.',
        'body' => 'Installs start 16 November'.($who === 'short-term rental operators' ? ', in time for the high season,' : '')." and the rest of November is free too. From January it\u{2019}s month to month: cancel any time with 30 days\u{2019} notice. Pay yearly and get 2 months free.",
        'note' => '',
        'buttons' => [['label' => 'Book my free trial', 'href' => '']],
    ],
];

// "What you get in your first month" (journey section, above pricing).
// Each step draws a phone mockup; "screen" holds its lines, one per line,
// parts split by " | " (see JourneySection.jsx). Numbers are a labelled example.
$journey = fn (string $audience) => $audience === 'hosts' ? [
    'title' => 'Your first month',
    'content' => [
        'anchor' => 'h-first-month',
        'menu_label' => 'Your first month',
        'menu_description' => 'Log on. Opt in. Come back.',
        'eyebrow' => 'Log on. Opt in. Come back.',
        'title' => 'What you get in your first month',
        'subtitle' => 'Your WiFi builds a guest list you own, gives guests a better stay, and brings them back to book direct.',
        'brand_name' => 'The Loft Nairobi',
        'brand_tagline' => 'Serviced apartments',
        'brand_color' => '#0F4C45',
        'steps' => [
            ['title' => 'Guest checks in', 'text' => 'They connect to your WiFi on arrival. No password to hunt for.', 'mockup' => 'wifi', 'screen' => "The Loft Nairobi WiFi\nSafaricom-Home-4G\nApt-5B"],
            ['title' => 'Your branded WiFi page', 'text' => 'Your name and colours. Guests join your list with name, email and phone.', 'mockup' => 'splash', 'screen' => "Welcome home.\nFirst name\nEmail address\n+254 Phone number\n[ ] Send me offers and early access to direct deals"],
            ['title' => 'Guidebook and extras', 'text' => 'Your house guide, plus extras guests can request: late checkout, transfers, extra nights.', 'mockup' => 'guide', 'screen' => "Welcome to The Loft | Everything for your stay\n\u{1F4D6} | House guide | Check-out, house rules, tips\n\u{1F551} | Late checkout | Stay until 2pm\n\u{1F695} | Airport transfer | JKIA pickup or drop-off\n\u{1F319} | Extra nights | Extend your stay\n\u{1F992} | Local experiences | Tours and things to do"],
            ['title' => 'Reviews, then a book-direct offer', 'text' => 'A thank-you and review request after checkout. A separate offer to book direct next time.', 'mockup' => 'chat', 'screen' => "Thanks for staying with us, Sarah! If you enjoyed your stay, we\u{2019}d love a review. | Leave a review | Checkout day\nPlanning your next trip to Nairobi? Book direct and save 10% with code KARIBU10. | Book direct | A week later | dark"],
            ['title' => 'CRM and campaigns', 'text' => 'Past guests hear from you, not the booking sites, and book direct on Tena Direct.', 'mockup' => 'crm', 'screen' => "\u{1F465} | TenaFi CRM | Your guest list, in one place\n\u{2193}\n\u{1F4AC} | WhatsApp \u{00B7} SMS \u{00B7} Email | Offers, quiet-season deals, events\n\u{2193}\nhighlight: \u{1F3E0} | Tena Direct | Book direct. 0% commission."],
        ],
        'example_title' => 'Example first month',
        'example_caption' => '3 apartments at typical Nairobi occupancy (about 44%)',
        'numbers' => [
            ['value' => '24', 'label' => 'guests connected to your WiFi'],
            ['value' => '16', 'label' => 'added to your guest list'],
            ['value' => '16', 'label' => 'book-direct offers sent'],
            ['value' => '2', 'label' => 'late checkouts or extra nights'],
        ],
        'example_note' => 'About 14 stays in the month; about 2 in 3 guests who connect join your list. Direct bookings usually start from month 2 or 3, as past guests plan their next trip. Your numbers will differ.',
        'outcome_label' => 'The outcome',
        'outcomes' => [
            ['title' => 'Higher occupancy', 'text' => 'Past guests fill the gaps between OTA bookings'],
            ['title' => 'More repeat stays', 'text' => 'Guests come back to the place they know'],
            ['title' => 'More reviews', 'text' => 'Every guest is asked, at the right moment'],
            ['title' => 'Less OTA dependence', 'text' => 'Direct bookings pay no commission'],
        ],
        'bar_lead' => 'One product, one team:',
        'bar_text' => 'device + WiFi page + CRM + done-for-you campaigns.',
        'cta_label' => 'Book my free trial',
        'cta_url' => '/hosts#join',
    ],
] : [
    'title' => 'Your first month',
    'content' => [
        'anchor' => 'b-first-month',
        'menu_label' => 'Your first month',
        'menu_description' => 'Log on. Opt in. Come back.',
        'eyebrow' => 'Log on. Opt in. Come back.',
        'title' => 'What you get in your first month',
        'subtitle' => 'Free WiFi turns every visit into a customer you can reach again, a Google review invite, and a reason to come back.',
        'brand_name' => 'Kahawa House',
        'brand_tagline' => "Coffee \u{00B7} Kitchen",
        'brand_color' => '#4A3123',
        'steps' => [
            ['title' => 'Customer sees free WiFi', 'text' => 'A table card and window sticker invite every customer to connect.', 'mockup' => 'tent', 'screen' => "Free WiFi\nConnect to\nKahawa House WiFi"],
            ['title' => 'Your branded WiFi page', 'text' => 'Your logo and colours. They join with name, phone and birthday.', 'mockup' => 'splash', 'screen' => "Karibu! Free WiFi\nFirst name\n+254 Phone number\nBirthday (day and month)\n[ ] Send me members-only offers and news"],
            ['title' => 'Value on login', 'text' => "They\u{2019}re online in seconds, with a welcome offer for joining your list.", 'mockup' => 'welcome', 'screen' => "You\u{2019}re connected! | Welcome to the Kahawa list, Brian.\noffer: Your welcome offer | 10% off | your next order | Show at the counter\n\u{2605} | Members\u{2019} list | Offers before anyone else\n\u{1F382} | Birthday treat | On us, in your birthday week\n\u{2615} | See our menu | Coffee, breakfast, lunch"],
            ['title' => 'Google review request', 'text' => 'Later that day, a thank-you with a one-tap Google review link. No QR code to scan.', 'mockup' => 'chat', 'screen' => 'Hi Brian, thanks for visiting Kahawa House today! How did we do? It takes 30 seconds to share it on Google. | Review us on Google | 16:05'],
            ['title' => 'CRM and offers that bring them back', 'text' => 'We send the campaigns for you, so customers come back again and again.', 'mockup' => 'crm', 'screen' => "\u{1F465} | TenaFi CRM | Your customer list, in one place\n\u{2193}\n\u{1F382} | Birthdays | A treat in their birthday week\n\u{1F3F7}\u{FE0F} | New offers | \u{201C}2-for-1 pastries before 10am\u{201D}\n\u{1F44B} | We miss you | For customers who haven\u{2019}t been back\n\u{2193}\nWhatsApp \u{00B7} SMS \u{00B7} Email"],
        ],
        'example_title' => 'Example first month',
        'example_caption' => "A caf\u{00E9} serving about 60 customers a day",
        'numbers' => [
            ['value' => '310', 'label' => 'customers on your list'],
            ['value' => '310', 'label' => 'Google review invites sent'],
            ['value' => '+12', 'label' => "new Google reviews (42 \u{2192} 54)"],
            ['value' => "4.4 \u{2192} 4.5", 'label' => 'Google rating'],
        ],
        'example_note' => 'About 1 in 4 customers joins the WiFi and roughly 4% of review invites become reviews. Offers to your list start in month one; return visits build from there. Your numbers will differ.',
        'outcome_label' => 'The outcome',
        'outcomes' => [
            ['title' => 'More Google reviews', 'text' => 'Show up higher on Google Maps, so new customers find you'],
            ['title' => 'More repeat customers', 'text' => 'Offers and birthday treats bring them back'],
            ['title' => 'Customer data you own', 'text' => "Your list, not a platform\u{2019}s"],
            ['title' => 'Better remarketing', 'text' => 'The right offer to the right customer at the right time'],
        ],
        'bar_lead' => 'One product, one team:',
        'bar_text' => 'device + WiFi page + CRM + done-for-you campaigns.',
        'cta_label' => 'Book my free trial',
        'cta_url' => '/business#signup',
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
            'media' => ['og_image' => '/brand/tenafi-logo.png'],
        ],
        'header' => [
            'title' => 'Header & navigation (default)',
            'content' => [
                'join_label' => 'Book Now',
                // TODO(events page): /events once it exists.
                'links' => [
                    ['label' => 'Short-term rentals', 'href' => '/hosts'],
                    ['label' => 'Business owners', 'href' => '/business'],
                    ['label' => 'Events', 'href' => '/#events'],
                    ['label' => 'How it works', 'href' => '/#how'],
                    ['label' => 'Pricing', 'href' => '/#pricing'],
                ],
            ],
        ],
        'announcement' => [
            'title' => 'Announcement bar',
            'content' => [
                'enabled' => '1',
                // {taken}, {total} and {left} come from the free trial slot counter (Admin -> Settings).
                'text' => 'Free trial for December · Installs from 16 November · {taken} of {total} slots taken',
                // Used when the counter is off (no total in Settings).
                'text_without_slots' => 'Free trial for December · Installs from 16 November',
                'link_label' => 'Book a slot',
                'link_href' => '/#trial',
                // Comma-separated page slugs (home, hosts, business). Empty = every page.
                'pages' => 'home',
            ],
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
                // Grouped columns. links: one per line, "Label | /href".
                // TODO(events page): /events once it exists.
                'groups' => [
                    ['title' => 'Who it’s for', 'links' => "Short-term rentals | /hosts\nCafés & restaurants | /business\nSalons, spas & barbers | /business\nGyms & studios | /business\nClinics & pharmacies | /business\nShops | /business\nEvents, weddings & venues | /#events"],
                    ['title' => 'What it does', 'links' => "Branded guest WiFi | /#wifi-page\nGuest list (CRM) | /#all-in-one\nGoogle review requests | /#reviews\nWhatsApp, SMS & email campaigns | /#campaigns\nEvent & sponsor WiFi | /#events"],
                    ['title' => 'Company', 'links' => "How it works | /#how\nFree trial | /#trial\nPrivacy policy | /privacy\nTerms | /terms"],
                ],
                // The single "Quick Links" column, used when there are no groups.
                'links' => [
                    ['label' => 'For short-term rental operators', 'href' => '/hosts'],
                    ['label' => 'For business owners', 'href' => '/business'],
                    ['label' => 'How it works', 'href' => '/#how'],
                    ['label' => 'Privacy policy', 'href' => '/privacy'],
                    ['label' => 'Terms', 'href' => '/terms'],
                ],
            ],
        ],
        'whatsapp_button' => [
            'title' => 'Floating WhatsApp button',
            'content' => [
                'enabled' => '1',
                'number' => '+254 703 501 597',
                'label' => 'WhatsApp us',
                // Pre-filled message, by page.
                'message_home' => "Hi TenaFi, I\u{2019}d like to find out about your free trial.",
                'message_hosts' => "Hi TenaFi, I\u{2019}d like to book a free trial for my short-term rental(s).",
                'message_business' => "Hi TenaFi, I\u{2019}d like to book a free trial for my business.",
            ],
        ],
        'trial_slots' => [
            // The numbers come from real sign-ups; the total and start date are in Admin -> Settings.
            'title' => 'Free trial slot counter',
            'content' => [
                'taken_text' => '{taken} of {total} free trial slots taken',
                'left_text' => '{left} slots left',
                'last_text' => 'Only 1 slot left',
                'full_text' => 'All {total} free trial slots taken',
                'full_note' => 'WhatsApp us to join the waitlist',
            ],
        ],
        'feature_status' => [
            'title' => 'Feature status ("Coming soon" badges)',
            'content' => [
                'badge_label' => 'Coming soon',
                'items' => [
                    ['key' => 'guest_homepage', 'label' => 'Guest homepage (house guide, local tips)', 'status' => 'live'],
                    ['key' => 'pms_sync', 'label' => 'PMS / channel manager sync', 'status' => 'live'],
                    ['key' => 'mpesa_extras', 'label' => 'Paid extras by M-Pesa on the guest homepage', 'status' => 'live'],
                    ['key' => 'monthly_report', 'label' => 'Monthly report', 'status' => 'live'],
                    ['key' => 'occupancy_alerts', 'label' => 'Occupancy alerts', 'status' => 'live'],
                    ['key' => 'outage_alerts', 'label' => 'Outage alerts', 'status' => 'live'],
                    ['key' => 'business_homepage', 'label' => 'Business customer homepage (menu, offers)', 'status' => 'live'],
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
                'meta_title' => 'TenaFi | Africa’s guest relationship platform',
                'meta_description' => 'One WiFi platform for short-term rentals, local businesses, events and weddings: branded guest WiFi, a guest list you own, Google reviews and campaigns, all managed for you.',
            ],
        ],
        'hero' => [
            'title' => 'Hero',
            'bg' => 'gray',
            'content' => [
                'badge' => 'Africa’s guest relationship platform',
                'title' => 'Turn your existing WiFi into '.$hl('growth.'),
                'subtitle' => 'One all-in-one WiFi platform for <strong>short-term rentals</strong>, <strong>local businesses</strong> and <strong>events</strong>.',
                'body' => 'Guests log in, join your list, review you and come back. We install it and run it for you.',
                'cta_primary' => 'Book my free trial',
                'cta_primary_url' => '#trial',
                'cta_secondary' => 'See how it works',
                'cta_secondary_url' => '#how',
                'note' => '',
                // One link per audience, under the headline.
                // TODO(events page): point the events link at /events once it exists.
                'audiences' => [
                    ['label' => 'Short-term rentals', 'href' => '/hosts'],
                    ['label' => 'Cafés, salons, shops & more', 'href' => '/business'],
                    ['label' => 'Events, weddings & venues', 'href' => '/#events'],
                ],
                // "Get a call back" (shown while Admin -> Settings has the call-back form on).
                // Options: value|Label; values are hosts, business or events.
                'callback_title' => 'Get a call back',
                'callback_options' => "hosts|I run rentals\nbusiness|I own a business\nevents|I’m planning an event",
                'callback_button' => 'Call me back',
                'callback_note' => 'We’ll call or WhatsApp you about the free trial.',
                'callback_link_label' => 'Or book your slot now',
                'callback_link' => '#trial',
                'callback_thanks' => 'Thanks! We’ll call or WhatsApp you within one working day.',
                // {price} is the cheapest plan on the live /hosts pricing section.
                'price_line' => 'Free for December. From {price} a month after that.',
                'price_link_label' => 'See pricing',
                'price_link' => '#pricing',
                'ticks' => "Installed for you\nNo payment to book\nCancel with 30 days’ notice",
                'show_slots' => '1',
                // "image" (the hero photo, the default) or "splash" (a drawn WiFi page and floating cards).
                'visual' => 'splash',
                'visual_screen' => "color: #4A3123\nbrand: Kahawa House | Café · Westlands\nheadline: Karibu! Free WiFi.\ntiles: ☕ View menu | 📶 Free WiFi | 🎂 Birthday treat\nFirst name\n+254 Phone number\nBirthday (day and month)\noptin: Send me offers, news and a birthday treat",
                'visual_cards' => [
                    ['icon' => '👋', 'title' => 'Welcome back, Amina', 'text' => '4th visit · opted in to offers'],
                    ['icon' => 'G', 'title' => 'New Google review', 'text' => 'Request sent at 16:05, reviewed at 16:40'],
                    ['icon' => '↺', 'title' => 'Sarah booked direct', 'text' => 'Her 2nd stay · 0% commission'],
                ],
            ],
            'media' => ['main_image' => $img('Tena-Hero-1.jpg')],
        ],
        'partners' => [
            'title' => 'Trusted by',
            'bg' => 'white',
            'content' => [
                'title' => 'Trusted by hosts and local businesses',
                'subtitle' => 'Every client gets a WiFi page in their own brand.',
                // Upload each client's WiFi page screenshot under Media (one slot per row);
                // rows without one show a placeholder. kind: hosts | business.
                'partners' => [
                    ['name' => 'Halo Studios', 'url' => '', 'kind' => 'business', 'type_area' => 'Studio · Nairobi'],
                    ['name' => 'Zero Sips', 'url' => '', 'kind' => 'business', 'type_area' => 'Bottle shop · Nairobi'],
                    ['name' => 'Georgio Lani Pharmacy', 'url' => '', 'kind' => 'business', 'type_area' => 'Pharmacy · Nairobi'],
                    ['name' => 'Stay Awhile Rentals', 'url' => 'https://stayawhilerentals.com', 'kind' => 'hosts', 'type_area' => 'Serviced apartments · Nairobi'],
                    ['name' => 'Luxury Hideaway', 'url' => '', 'kind' => 'hosts', 'type_area' => 'Villa · Kenya'],
                ],
            ],
        ],
        'path_cards' => [
            'title' => 'One platform, three kinds of places',
            'content' => [
                'anchor' => 'choose',
                'title' => 'One platform, three kinds of places',
                'subtitle' => 'Wherever people use your WiFi, TenaFi turns them into guests who come back.',
                // tags: comma-separated. mockup + screen draw the card's picture (see Mockup.jsx).
                'cards' => [
                    ['label' => '01 · Short-term rentals', 'title' => 'Fill more nights with guests who’ve stayed before.', 'description' => 'Build a guest list of the whole party, not just the booker, and bring them back to book direct.', 'cta' => 'Grow your occupancy', 'href' => '/hosts', 'event' => 'path_card_hosts', 'tags' => 'Apartments, Villas, Guest houses, Boutique hotels', 'mockup' => 'chat', 'screen' => "head: The Loft Nairobi | WhatsApp\nmsg: Thanks for staying with us, Sarah! Planning your next trip? Book direct and save 10% with code KARIBU10. | Book direct | A week after checkout | dark\ncolor: #0F4C45"],
                    ['label' => '02 · Local businesses', 'title' => 'More Google reviews. More regulars.', 'description' => 'Every customer who connects gets a review request later that day, then offers that bring them back.', 'cta' => 'Get more reviews', 'href' => '/business', 'event' => 'path_card_business', 'tags' => 'Cafés, Restaurants, Salons & spas, Barbers, Gyms, Clinics, Shops', 'mockup' => 'rating', 'screen' => "name: Kahawa House\nscore: 4.5\nmeta: 54 reviews · +12 this month\nrow: BO | Review request sent | Same day"],
                    // TODO(events page): href /events once it exists.
                    ['label' => '03 · Events & venues', 'title' => 'Know who came, not just who bought the ticket.', 'description' => 'From weddings and launches to stadiums: every guest who connects joins your list, with a sponsor-branded sign-in.', 'cta' => 'Plan your event WiFi', 'href' => '/#events', 'event' => 'path_card_events', 'tags' => 'Weddings, Launches, Conferences, Concerts, Festivals, Stadiums', 'mockup' => 'attendees', 'screen' => "head: Kona Design Week\nrow: JM | Joy M. · Day 2 | Opted in\nrow: KO | Kevin O. · Day 1, 2 | Opted in\nrow: AN | Amani N. · Day 2 | Opted in"],
                ],
            ],
            'media' => [
                'card_0_image' => $img('Tena-Welcome-Divine-1.jpg'),
                'card_1_image' => $img('Clients-view.jpg'),
            ],
        ],
        'comparison__problem' => [
            'title' => 'The gap',
            'content' => [
                'anchor' => 'problem',
                // "versus": two panels, the highlighted one dark with ticks, the other with crosses; stats below.
                'style' => 'versus',
                'badge' => 'The gap',
                'title' => 'Your WiFi meets every guest. Then they leave as strangers.',
                'subtitle' => 'Bookings sit with the OTA, tickets name only the buyer, and QR review cards wait for a scan. The WiFi login is the one moment everyone connects.',
                'note' => '',
                'sources' => 'Sources: Airbtics, Nairobi median occupancy 44% (Feb 2025 to Jan 2026). BrightLocal, Local Consumer Review Survey 2026. Typical OTA guest and host fees.',
                'columns' => [
                    ['label' => 'Guest WiFi today', 'stat' => '', 'stat_label' => '', 'points' => "A password on a card and a router name nobody remembers\nYou don’t know who connected, or how often they come back\nReviews depend on someone scanning a QR code on the spot\nPast guests rebook through the OTA, or not at all", 'text' => '', 'highlight' => '0'],
                    ['label' => 'With TenaFi', 'stat' => '', 'stat_label' => '', 'points' => "A WiFi page in your brand, with no password to hunt for\nEvery guest who opts in joins a list you own, with consent recorded\nA one-tap Google review request goes to every guest later that day\nWhatsApp, SMS and email campaigns bring them back, sent for you", 'text' => '', 'highlight' => '1'],
                ],
                'stats' => [
                    ['value' => '56%', 'label' => 'of nights go unbooked in a typical Nairobi Airbnb'],
                    ['value' => '47%', 'label' => 'of consumers skip a business with fewer than 20 reviews'],
                    ['value' => '~15%', 'label' => 'commission when repeat guests rebook through the OTA'],
                ],
            ],
        ],
        'features__plays' => [
            'title' => 'All in one',
            'bg' => 'gray',
            'content' => [
                'anchor' => 'all-in-one',
                'badge' => 'All in one',
                'title' => 'One device, one WiFi page, one team running it for you.',
                'subtitle' => 'Everything you need to turn a WiFi login into a relationship, without another app to learn.',
                'items' => [
                    ['step' => '01 · Connect', 'icon' => 'fas fa-wifi', 'title' => 'Branded WiFi login', 'description' => 'Your logo, colours and welcome offer on the first screen every guest sees.', 'feature' => ''],
                    ['step' => '02 · Capture', 'icon' => 'fas fa-address-book', 'title' => 'A guest list you own', 'description' => 'Names, phones and birthdays, with consent, in your TenaFi CRM. Not the platform’s.', 'feature' => ''],
                    ['step' => '03 · Review', 'icon' => 'fas fa-star', 'title' => 'Reviews on autopilot', 'description' => 'A one-tap Google or Airbnb review request to every guest, while the visit is fresh.', 'feature' => ''],
                    ['step' => '04 · Return', 'icon' => 'fas fa-redo', 'title' => 'Campaigns that bring them back', 'description' => 'Birthdays, offers, quiet-season deals and event invites by WhatsApp, SMS and email.', 'feature' => ''],
                ],
            ],
        ],
        'detailed_features__home' => [
            'title' => 'Feature rows',
            'content' => [
                'title' => '',
                'subtitle' => '',
                // Empty: rows show their own button (cta_label / cta_href) or none.
                'cta_text' => '',
                // Each row: mockup + screen draw the picture (see Mockup.jsx); "---" starts another panel.
                'sections' => [
                    [
                        'anchor' => 'wifi-page', 'label' => 'Branded WiFi login', 'heading' => 'WiFi that welcomes them, in your brand.',
                        'description' => 'Guests connect in seconds through a page that looks like you, not like a router.',
                        'bg' => 'white', 'reverse' => '0', 'note' => '', 'cta_label' => '', 'cta_href' => '',
                        'mockup' => 'splash',
                        'screen' => "color: #0F4C45\nbrand: The Loft | Apartments\nheadline: Welcome home.\nFirst name\nEmail address\n---\n@splash\ncolor: #C77B86\nbrand: Glow Studio | Salon & spa\nheadline: Relax. You’re connected.\nFirst name\n+254 Phone\n---\n@splash\ncolor: #3B2C6B\nbrand: Design Week | Nairobi · Day 2\nheadline: Welcome in.\nFirst name\nEmail address\nsponsor: In partnership with your sponsor",
                        'features' => [
                            ['icon' => 'fas fa-check', 'title' => 'Templates for rentals, cafés, salons, barbers, gyms, clinics, shops and events', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'Your logo, colours, menu, house guide or event schedule', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'A welcome offer for joining your list', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'Consent recorded for every guest', 'desc' => '', 'feature' => ''],
                        ],
                    ],
                    [
                        'anchor' => 'reviews', 'label' => 'Reviews', 'heading' => 'Reviews from every guest, not just the ones who scan.',
                        'description' => 'A thank-you with a one-tap review link arrives later that day by WhatsApp, SMS or email. No QR code, no awkward ask at the till.',
                        'bg' => 'gray', 'reverse' => '1', 'cta_label' => '', 'cta_href' => '',
                        // Keep: reviews are never rewarded, filtered or gated.
                        'note' => 'Every guest is asked, and no review is ever rewarded or filtered. That keeps your reviews real and within Google’s and Airbnb’s rules.',
                        'mockup' => 'chat',
                        'screen' => "color: #4A3123\nhead: Kahawa House | WhatsApp · same day\nmsg: Hi Brian, thanks for visiting Kahawa House today! How did we do? It takes 30 seconds to share it on Google. | Review us on Google | 16:05\n---\n@stats\nscore: 4.5\nrow: Reviews at start of month | 42\nrow: New this month | +12\nrow: Rating | 4.4 → 4.5",
                        'features' => [
                            ['icon' => 'fas fa-check', 'title' => 'Google reviews for businesses, Airbnb and Google for rentals', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'Sent automatically while the visit is still fresh', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'More recent reviews help you rank higher on Google Maps', 'desc' => '', 'feature' => ''],
                        ],
                    ],
                    [
                        'anchor' => 'campaigns', 'label' => 'Campaigns', 'heading' => 'Bring them back, again and again.',
                        'description' => '“Tena” means again in Swahili. We write, schedule and send the campaigns, so one visit turns into many.',
                        'bg' => 'white', 'reverse' => '0', 'note' => '', 'cta_label' => '', 'cta_href' => '',
                        'mockup' => 'chat',
                        'screen' => "color: #1E1E1E\nhead: Your campaigns | WhatsApp · SMS · email\nmsg: Happy birthday, Amina! 🎂 Your next coffee is on us this week at Kahawa House. | | 09:00 | | Birthday · automatic\nmsg: Hi Brian, it’s been a while! 2-for-1 pastries before 10am for members, this week only. | | 08:30 | | We miss you · after 30 days\nmsg: Back in Nairobi soon, Sarah? Book The Loft direct and save 10% with code KARIBU10. | Book direct | | dark | Book direct · quiet season",
                        'features' => [
                            ['icon' => 'fas fa-check', 'title' => 'WhatsApp, SMS and email from one guest list', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'Birthdays, win-backs, new offers and quiet-season deals', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'Book-direct offers for rentals, at 0% commission on Tena Direct', 'desc' => '', 'feature' => 'tena_direct'],
                            ['icon' => 'fas fa-check', 'title' => 'A monthly report of what came back', 'desc' => '', 'feature' => 'monthly_report'],
                        ],
                    ],
                    [
                        // Matches ExtrasPaymentService: an STK prompt on the guest's phone, the host told when it's paid,
                        // and pay at the property while M-Pesa isn't set up.
                        'anchor' => 'extras', 'label' => 'Paid by M-Pesa', 'heading' => 'Guests pay for extras straight from the WiFi page.',
                        'description' => 'Your guest page doubles as a digital concierge: house guide, local tips and extras guests can buy during their stay. They tap, get an M-Pesa prompt on their phone, and you’re told when it’s paid.',
                        'bg' => 'gray', 'reverse' => '1', 'cta_label' => '', 'cta_href' => '',
                        'note' => 'Example prices. You set your own.',
                        'mockup' => 'guide',
                        'screen' => "color: #0F4C45\nhead: Welcome to The Loft\nrow: 📖 | House guide | Free\nrow: 🕑 | Late checkout, 2pm | KES 2,000\nrow: 🚕 | Airport transfer | KES 3,500\nrow: 🌙 | Extra night | KES 7,000\n---\n@mpesa\nto: Pay KES 2,000 to The Loft Nairobi?\nok: Paid. Your host has been told.",
                        'features' => [
                            ['icon' => 'fas fa-check', 'title' => 'Late checkout, extra nights, airport transfers, cleaning and welcome packs', 'desc' => '', 'feature' => 'mpesa_extras'],
                            ['icon' => 'fas fa-check', 'title' => 'Offered while guests are already connected and in the mood', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'An M-Pesa prompt on their phone, with no card or app', 'desc' => '', 'feature' => 'mpesa_extras'],
                            ['icon' => 'fas fa-check', 'title' => 'If M-Pesa isn’t set up yet, guests can still request and pay at the property', 'desc' => '', 'feature' => ''],
                        ],
                    ],
                    [
                        // TODO(events page): cta_href /events once it exists.
                        'anchor' => 'events', 'label' => 'Events & venues', 'heading' => 'Every event its own sign-in. Every attendee on your list.',
                        'description' => 'Tickets name the buyer, not the friends they bring. Event WiFi tells you who actually came, and gives sponsors a moment every connected guest sees.',
                        'bg' => 'white', 'reverse' => '0', 'note' => '', 'cta_label' => 'Plan your event WiFi', 'cta_href' => '#trial',
                        'mockup' => 'splash',
                        'screen' => "color: #3B2C6B\nbrand: Kona Design Week | Expo hall · Day 2\nheadline: Free WiFi, all day.\ntiles: 🗓️ Schedule | 🎤 Speakers | 🎁 Sponsor offer\nFirst name\nEmail address\noptin: Send me next year’s early-bird tickets\nsponsor: WiFi by your sponsor\n---\n@attendees\nhead: Attendees · Day 2\nrow: JM | Joy M. | New\nrow: KO | Kevin O. | Returning\nrow: AN | Amani N. | New\ntag: Tagged: Design Week 2026",
                        'features' => [
                            ['icon' => 'fas fa-check', 'title' => 'A WiFi page for each event, from a wedding to a stadium', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'Sponsor logo, question and offer on the first screen', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'Attendees tagged by event, ready for the next on-sale', 'desc' => '', 'feature' => ''],
                            ['icon' => 'fas fa-check', 'title' => 'A thank-you and early-bird invite after the event', 'desc' => '', 'feature' => ''],
                        ],
                    ],
                ],
            ],
        ],
        'how_it_works' => [
            'title' => 'How it works',
            'content' => [
                'anchor' => 'how',
                'title' => 'Set up for you, on the internet you already have.',
                'subtitle' => 'We don’t sell internet. TenaFi plugs into your existing connection, and our team does the rest.',
                'steps' => [
                    ['step' => '1', 'icon' => 'fas fa-wifi', 'title' => 'We install it', 'description' => 'Our team connects the TenaFi device to your router and sets up your branded WiFi.', 'feature' => ''],
                    ['step' => '2', 'icon' => 'fas fa-address-card', 'title' => 'Guests opt in', 'description' => 'They connect through your WiFi page and share their details with consent. No app.', 'feature' => ''],
                    ['step' => '3', 'icon' => 'fas fa-comments', 'title' => 'We follow up', 'description' => 'Review requests and campaigns go out on WhatsApp, SMS and email, managed for you.', 'feature' => ''],
                    ['step' => '4', 'icon' => 'fas fa-chart-line', 'title' => 'They come back', 'description' => 'More reviews, repeat visits and direct bookings, in a monthly report.', 'feature' => ''],
                ],
                // Cards under the steps.
                'cards' => [
                    ['title' => 'The TenaFi device', 'text' => 'A small access point that plugs into your existing router and runs your branded guest WiFi.'],
                    ['title' => 'Fully managed', 'text' => 'We keep it running, update your page and send the campaigns. You see the results.'],
                ],
            ],
        ],
        'comparison_table' => [
            'title' => 'Free WiFi vs TenaFi (table)',
            'bg' => 'gray',
            'content' => [
                'badge' => 'More than free WiFi',
                'title' => 'What free WiFi gives you, and what TenaFi gives you.',
                'subtitle' => 'Same internet connection. What changes is what you know about your guests and what you can do next.',
                'col_label' => 'What you need',
                'col_without' => 'Free WiFi',
                'col_with' => 'With TenaFi',
                'rows' => [
                    ['label' => 'Who connected', 'without' => 'Anonymous devices', 'with' => 'A named guest list, with consent'],
                    ['label' => 'Your brand', 'without' => 'A router name and a password', 'with' => 'A WiFi page in your logo and colours'],
                    ['label' => 'Reviews', 'without' => 'A QR card that waits for a scan', 'with' => 'A one-tap request to every guest'],
                    ['label' => 'Repeat visits', 'without' => 'Hope they come back', 'with' => 'Birthday, win-back and book-direct campaigns'],
                    ['label' => 'Events & sponsors', 'without' => 'One network for every event', 'with' => 'A sign-in, sponsor slot and attendee tag per event'],
                    ['label' => 'Who runs it', 'without' => 'You, on top of everything else', 'with' => 'Our team, with a monthly report'],
                ],
            ],
        ],
        'credibility' => [
            'title' => 'Proof: built by operators',
            'content' => [
                // "dark": the founder quote instead of the photo, and the source line under the numbers.
                'style' => 'dark',
                'badge' => 'Built by operators',
                'title' => 'Built inside a 16x Airbnb Superhost business in Nairobi.',
                'subtitle' => 'TenaFi started at Stay Awhile Rentals. We built the tool we needed to turn guests into regulars, and now run it for hosts, businesses and events across Kenya.',
                'quote' => '“The WiFi login is the one moment every guest connects. We turned it into a relationship.”',
                'quote_name' => 'Glen Mwangi, Founder & CEO',
                'closing_line' => '',
                'tagline' => '',
                'stats' => [
                    ['value' => '16x', 'label' => 'Airbnb Superhost'],
                    ['value' => '1,400+', 'label' => 'reservations hosted'],
                    ['value' => '5,000+', 'label' => 'guest nights'],
                    ['value' => '800+', 'label' => 'guest reviews'],
                ],
                // Keep: these are Stay Awhile's figures, not client results.
                'sources' => 'Figures from Stay Awhile Rentals, TenaFi’s founding portfolio. Client results will be added here as they come in.',
            ],
        ],
        'cta_banner__founding' => [
            'title' => 'Free trial (December) + pricing',
            'content' => [
                'anchor' => 'trial',
                // "trial": yellow, ticks, slot counter and the after-the-trial pricing card.
                'style' => 'trial',
                'badge' => 'Free trial',
                'title' => 'Try TenaFi free for the whole of December.',
                'body' => 'Installs start 16 November, and the rest of November is free too. Then it’s month to month.',
                'note' => '',
                'ticks' => "No payment to book\nInstalled for you\nCancel with 30 days’ notice",
                'show_slots' => '1',
                // Plans come from the live /hosts pricing section, never typed twice.
                'pricing_anchor' => 'pricing',
                'pricing_title' => 'After the trial',
                'pricing_note' => 'A month, per unit for rentals. Pay yearly and get 2 months free. Events are quoted per event.',
                // TODO(events page): /events once it exists.
                'pricing_links' => [
                    ['label' => 'Rental pricing', 'href' => '/hosts#h-pricing'],
                    ['label' => 'Business pricing', 'href' => '/business#b-pricing'],
                    ['label' => 'Events', 'href' => '/#events'],
                ],
                'buttons_title' => 'Book your slot',
                // An href starting "whatsapp:" opens WhatsApp (Site-wide number) with that message.
                // TODO(events page): /events#enquire once it exists.
                'buttons' => [
                    ['label' => 'I run short-term rentals', 'href' => '/hosts#join'],
                    ['label' => 'I own a business', 'href' => '/business#signup'],
                    ['label' => 'I’m planning an event', 'href' => '/#events'],
                    ['label' => 'Questions? WhatsApp us', 'href' => 'whatsapp:Hi TenaFi, I have a question about the free trial.'],
                ],
            ],
        ],
        'faq' => [
            'title' => 'FAQ',
            'content' => [
                'title' => 'Questions, answered.',
                'subtitle' => '',
                'items' => [
                    ['question' => 'Do I need new internet?', 'answer' => 'No. TenaFi plugs into the connection you already have. We install the device and set up your branded WiFi page.'],
                    ['question' => 'Who runs it day to day?', 'answer' => 'We do. We keep the WiFi running, update your page, and write and send your review requests and campaigns. You get a monthly report.'],
                    ['question' => 'Who owns the guest list?', 'answer' => 'You do. Guests opt in with consent on your WiFi page, and the list stays yours.'],
                    ['question' => 'Does it work for events?', 'answer' => 'Yes, from weddings and launch nights to stadiums. Each event gets its own WiFi page and attendee tag, with room for a sponsor. WhatsApp us with your date and expected crowd.'],
                    ['question' => 'What happens after the free trial?', 'answer' => 'You choose a plan and continue month to month, or cancel with 30 days’ notice. Paying yearly gets you 2 months free.'],
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
        'journey' => $journey('hosts'),
        'pricing' => [
            'title' => 'Pricing',
            'bg' => 'gray',
            'content' => [
                'anchor' => 'h-pricing',
                'menu_label' => 'Pricing',
                'title' => 'One price per unit. Everything included.',
                'subtitle' => 'The device, installation, WiFi page, guest list and support. Free for December, then month to month.',
                // TODO(Glen): multi-unit discount from 5 or 10 units? Installation fee after the trial?
                'footnote' => "Free trial: installs from 16 November, and the rest of November and all of December are free. Then month to month; cancel with 30 days\u{2019} notice. Pay yearly and get 2 months free. Multi-unit operators: 20% off from 10 units, 30% off from 50. Extra devices at the same site: KES 1,500 a month each. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi\u{2019}s; we maintain and replace it.",
                'show_contact_form' => '0',
                'show_slots' => '1',
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
                    ['question' => 'How soon will I see results?', 'answer' => 'Reviews, upsells and Instagram follows start in the first weeks. Repeat bookings build as your guest list grows, month after month.'],
                    ['question' => 'Do I need new internet?', 'answer' => 'No. The TenaFi device plugs into your existing router and adds a separate guest network. Your own network stays private.'],
                    ['question' => 'Who owns the guest data?', 'answer' => "You do. Guests opt in on your WiFi page, and every contact is collected with consent under Kenya\u{2019}s Data Protection Act, 2019."],
                    ['question' => 'Do I have to write the messages?', 'answer' => 'No. We write, schedule and send every campaign, and you approve anything that goes out under your name.'],
                    // TODO(Glen): 5 or 10 units for the multi-unit discount?
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
                'menu_label' => 'Free trial',
                'menu_description' => 'Book your free trial. December is on us.',
                'signup_type' => 'host',
                'show_slots' => '1',
                'badge' => 'Free trial',
                'title' => "Let\u{2019}s get started",
                'subtitle' => 'Book a free trial for your short-term rentals. Installs start 16 November; the rest of November and all of December are free.',
                'submit_label' => 'Book my free trial',
                'consent_text' => $consent,
                'success_title' => 'Asante, {firstName}!',
                'success_message' => "Your free trial booking is in. Here\u{2019}s what we have.",
                'next_steps_title' => 'What happens next',
                'next_steps' => "We message you on WhatsApp to book a short setup call.\nWe check your routers and agree install dates, building by building if you have several units.\nYour first guests are captured within 7 days of install.",
                'error_message' => 'Something went wrong. Please try again or WhatsApp us on +254 703 501 597.',
                'price_suffix' => 'per unit / month',
                'discount_field' => 'units',
                // TODO(Glen): 5 or 10 units? Change the hint and the first discount match together.
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
        'journey' => $journey('business'),
        'pricing' => [
            'title' => 'Pricing',
            'content' => [
                'anchor' => 'b-pricing',
                'menu_label' => 'Pricing',
                'title' => 'One price per location. Everything included.',
                'subtitle' => 'The device, installation, WiFi page, customer list and support. Free for December, then month to month.',
                // TODO(Glen): installation fee after the trial?
                'footnote' => "Free trial: installs from 16 November, and the rest of November and all of December are free. Then month to month; cancel with 30 days\u{2019} notice. Pay yearly and get 2 months free. Several locations? We\u{2019}ll quote group pricing. Extra devices at the same site: KES 1,500 a month each. WhatsApp and SMS allowances included; heavy use billed at cost. The device stays TenaFi\u{2019}s; we maintain and replace it.",
                'show_contact_form' => '0',
                'show_slots' => '1',
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
                'menu_label' => 'Free trial',
                'menu_description' => 'Book your free trial. December is on us.',
                'signup_type' => 'business',
                'show_slots' => '1',
                'badge' => 'Free trial',
                'title' => "Let\u{2019}s get started",
                'subtitle' => 'Book a free trial for your business. Installs start 16 November; the rest of November and all of December are free.',
                'submit_label' => 'Book my free trial',
                'consent_text' => $consent,
                'success_title' => 'Asante, {firstName}!',
                'success_message' => "Your free trial booking is in. Here\u{2019}s what we have.",
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
