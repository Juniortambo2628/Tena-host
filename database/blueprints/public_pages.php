<?php

/*
|--------------------------------------------------------------------------
| TenaFi public pages blueprint
|--------------------------------------------------------------------------
|
| Default sections, copy and media slots for every public page. Synced into
| landing_pages / landing_sections / landing_content / landing_media by
| App\Services\Cms\PageBlueprint, after which the admin CMS owns the text.
|
| Conventions (same as the CMS editor):
|   - a top-level list of rows flattens to "name.{i}.field" keys
|     (e.g. steps.0.title) and gets its own tab in the editor;
|   - a list nested inside a row is stored as a JSON string
|     (e.g. sections.0.features, steps.0.fields).
|
| Section order on the page follows array order. Feature rows that carry a
| "feature" key show a "Coming soon" badge until that key is marked "live"
| under Site-wide -> Feature status.
|
*/

$img = fn (string $file) => "/legacy/assets/Tena-Landing/{$file}";
$hl = fn (string $text) => '<span class="text-[#FFD300]">'.$text.'</span>';

$contactFields = [
    ['key' => 'first_name', 'label' => 'First name', 'type' => 'text', 'required' => true, 'options' => [], 'placeholder' => 'Wanjiru'],
    ['key' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'required' => true, 'options' => [], 'placeholder' => 'Kamau'],
    ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'options' => [], 'placeholder' => 'you@example.com'],
    ['key' => 'phone', 'label' => 'Phone / WhatsApp', 'type' => 'tel', 'required' => true, 'options' => [], 'placeholder' => '+254 7XX XXX XXX'],
];

$planInterest = ['key' => 'plan_interest', 'label' => 'Which plan interests you?', 'type' => 'select', 'required' => false, 'options' => ['Basic', 'Starter', 'Growth', 'Not sure yet'], 'placeholder' => ''];
$referral = ['key' => 'referral_source', 'label' => 'How did you hear about TenaFi?', 'type' => 'select', 'required' => false, 'options' => ['Referral', 'WhatsApp', 'Instagram', 'LinkedIn', 'Google', 'Event', 'Other'], 'placeholder' => ''];

$consent = 'I agree that TenaFi may store the details above and contact me by email, phone or WhatsApp about my application. I have read the Privacy Policy and Terms.';

$connectSteps = fn (array $copy) => array_map(fn ($row, $i) => [
    'step' => (string) ($i + 1),
    'icon' => $row[0],
    'title' => $row[1],
    'description' => $row[2],
], $copy, array_keys($copy));

$stepImages = [
    'step_0_image' => $img('Step-1-Connect.jpg'),
    'step_1_image' => $img('Step-2-Data-Collection.jpg'),
    'step_2_image' => $img('Step-3-Remarket.jpg'),
    'step_3_image' => $img('Branded-Splash-Page.jpg'),
];

// Placeholders until TenaFi-branded imagery is uploaded in the CMS. (The
// old Problem-*.jpg graphics hard-code "OTAs take 20%", which the relaunch
// dropped.)
$problemImages = [
    'image_0' => $img('Tena-Welcome-Divine-1.jpg'),
    'image_1' => $img('Tena-Portrait-1.jpg'),
    'image_2' => $img('Tena-Features-1.jpg'),
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
                'meta_title' => "TenaFi | Africa's guest relationship platform",
                'meta_description' => 'TenaFi turns the WiFi you already have into growth: capture every guest, stay in touch, and bring them back.',
            ],
            'media' => ['og_image' => '/legacy/assets/Tena-logo-square.jpg'],
        ],
        'header' => [
            'title' => 'Header & navigation',
            'content' => [
                'login_label' => 'Login',
                'login_url' => '/login',
                'join_label' => 'Join',
                'links' => [
                    ['label' => 'For hosts', 'href' => '/hosts'],
                    ['label' => 'For businesses', 'href' => '/business'],
                    ['label' => 'How it works', 'href' => '#how-it-works'],
                ],
            ],
            'media' => ['logo' => '/legacy/assets/Tena-logo-square.jpg'],
        ],
        'footer' => [
            'title' => 'Footer',
            'content' => [
                'description' => "Africa's guest relationship platform. Turn your existing WiFi into growth.",
                'tagline' => 'Own the Guest. Build the Relationship.',
                'contact_email' => 'info@tena-fi.com',
                'location' => 'Nairobi, Kenya',
                'copyright' => 'TenaFi. All rights reserved.',
                'links' => [
                    ['label' => 'Home', 'href' => '/'],
                    ['label' => 'For hosts', 'href' => '/hosts'],
                    ['label' => 'For businesses', 'href' => '/business'],
                    ['label' => 'Privacy Policy', 'href' => '/privacy'],
                    ['label' => 'Terms', 'href' => '/terms'],
                    ['label' => 'Login', 'href' => '/login'],
                ],
            ],
        ],
        'plans' => [
            'title' => 'Plans (shared by every pricing section)',
            'content' => [
                'currency_note' => 'Prices in KES, billed monthly per location.',
                'plans' => [
                    ['label' => 'Basic', 'price' => 'KES 3,000', 'unit' => '/ month', 'description' => 'WiFi guest capture with a branded login page, a growing contact list and consent records.', 'cta' => 'Join', 'variant' => 'outline'],
                    ['label' => 'Starter', 'price' => 'KES 4,500', 'unit' => '/ month', 'description' => 'Everything in Basic, plus automated welcome and return-visit messages and your guest homepage.', 'cta' => 'Join', 'variant' => 'dark'],
                    ['label' => 'Growth', 'price' => 'KES 6,000', 'unit' => '/ month', 'description' => 'Everything in Starter, plus campaigns to past guests, the monthly report and priority support.', 'cta' => 'Join', 'variant' => 'outline'],
                ],
            ],
        ],
        'feature_status' => [
            'title' => 'Feature status ("Coming soon" badges)',
            'content' => [
                'badge_label' => 'Coming soon',
                'items' => [
                    ['key' => 'guest_homepage', 'label' => 'Guest homepage (house guide)', 'status' => 'live'],
                    ['key' => 'pms_sync', 'label' => 'PMS / channel manager sync', 'status' => 'live'],
                    ['key' => 'mpesa_extras', 'label' => 'M-Pesa extras on the guest homepage', 'status' => 'coming_soon'],
                    ['key' => 'monthly_report', 'label' => 'Monthly report', 'status' => 'coming_soon'],
                    ['key' => 'occupancy_alerts', 'label' => 'Occupancy alerts', 'status' => 'coming_soon'],
                    ['key' => 'outage_alerts', 'label' => 'WiFi outage alerts', 'status' => 'coming_soon'],
                    ['key' => 'business_homepage', 'label' => 'Business customer homepage', 'status' => 'coming_soon'],
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
                'meta_title' => "TenaFi | Africa's guest relationship platform",
                'meta_description' => 'Turn your existing WiFi into growth. TenaFi helps short-term rental operators fill more nights and local businesses earn more Google reviews.',
            ],
        ],
        'hero' => [
            'title' => 'Hero',
            'bg' => 'gray',
            'content' => [
                'badge' => "Africa's guest relationship platform",
                'title' => 'Turn your existing WiFi into '.$hl('growth').'.',
                'subtitle' => 'Every guest who connects to your WiFi becomes someone you can thank, invite back and learn from. No new internet, no new hardware to manage.',
                'cta_primary' => 'I run short-term rentals',
                'cta_primary_url' => '/hosts',
                'cta_secondary' => 'I own a business',
                'cta_secondary_url' => '/business',
            ],
            'media' => ['main_image' => $img('Tena-Hero-1.jpg')],
        ],
        'path_cards' => [
            'title' => 'Choose your path',
            'content' => [
                'anchor' => 'paths',
                'title' => 'Which one sounds like you?',
                'subtitle' => 'Same WiFi, two different jobs. Pick yours.',
                'cards' => [
                    ['label' => 'Short-term rental operators', 'title' => 'Higher occupancy', 'description' => 'Turn every guest, not just the booker, into a direct relationship and fill more of your quiet nights.', 'cta' => 'For hosts', 'href' => '/hosts', 'event' => 'path_card_hosts'],
                    ['label' => 'Business owners', 'title' => 'More Google reviews', 'description' => 'Cafés, salons, clinics, gyms: ask every customer for a review at the right moment and bring them back.', 'cta' => 'For businesses', 'href' => '/business', 'event' => 'path_card_business'],
                ],
            ],
            'media' => [
                'card_0_image' => $img('Tena-Welcome-Divine-1.jpg'),
                'card_1_image' => $img('Clients-view.jpg'),
            ],
        ],
        'problem' => [
            'title' => 'The problem',
            'content' => [
                'badge' => 'The problem',
                'title' => 'Your WiFi reaches every guest. It just forgets them.',
                'description' => 'Guests connect, use the internet and leave. You never learn who they were, so you can\'t thank them, invite them back or ask for a review. The relationship belongs to the booking platform, or to nobody.',
            ],
            'media' => $problemImages,
        ],
        'how_it_works' => [
            'title' => 'How it works',
            'content' => [
                'title' => 'How TenaFi works',
                'subtitle' => 'Four steps, on the WiFi you already have.',
                'steps' => $connectSteps([
                    ['fas fa-wifi', 'Connect', 'We plug into your existing WiFi. Guests see your branded login page.'],
                    ['fas fa-address-card', 'Capture', 'Every guest leaves a verified contact, with consent, in seconds.'],
                    ['fas fa-comments', 'Engage', 'Automatic welcome, thank-you and review messages go out for you.'],
                    ['fas fa-chart-line', 'Grow', 'Returning guests, more reviews and a clear monthly picture of it all.'],
                ]),
            ],
            'media' => $stepImages,
        ],
    ],

    // ------------------------------------------------------------------
    // Short-term rental operators: /hosts
    // ------------------------------------------------------------------
    'hosts' => [
        'seo' => [
            'title' => 'SEO & social',
            'content' => [
                'meta_title' => 'TenaFi for hosts | Turn your existing WiFi into higher occupancy',
                'meta_description' => 'Capture every guest, not just the booker, and turn them into direct, repeat stays. Built by Superhosts in Nairobi.',
            ],
        ],
        'hero' => [
            'title' => 'Hero',
            'bg' => 'gray',
            'content' => [
                'badge' => 'For short-term rental operators',
                'title' => 'Turn your existing WiFi into '.$hl('higher occupancy').'.',
                'subtitle' => 'TenaFi captures every guest who connects, not just the booker, and keeps them coming back to book direct.',
                'cta_primary' => 'Join the Founding 20',
                'cta_primary_url' => '#join',
                'cta_secondary' => 'See how it works',
                'cta_secondary_url' => '#how-it-works',
            ],
            'media' => ['main_image' => $img('Tena-Hero-1.jpg')],
        ],
        'problem' => [
            'title' => 'The occupancy problem',
            'content' => [
                'badge' => 'The occupancy problem',
                'title' => 'More than half your nights sit empty.',
                'description' => 'Median short-term rental occupancy in Nairobi is 44%. Every past guest is a future booking, but once they check out you have no way to reach them.',
                'stat_value' => '44%',
                'stat_label' => 'Median short-term rental occupancy, Nairobi',
                'stat_source' => 'Airbtics',
            ],
            'media' => $problemImages,
        ],
        'how_it_works' => [
            'title' => 'How it works',
            'content' => [
                'title' => 'How TenaFi works for hosts',
                'subtitle' => 'Connect, Capture, Engage, Grow.',
                'steps' => $connectSteps([
                    ['fas fa-wifi', 'Connect', 'We plug into your existing router. Guests log in through your branded page.'],
                    ['fas fa-address-card', 'Capture', 'Every guest leaves a verified contact, not just the person who booked.'],
                    ['fas fa-comments', 'Engage', 'Welcome messages, a guest homepage and return-stay offers go out for you.'],
                    ['fas fa-calendar-check', 'Grow', 'Past guests book direct, and quiet months fill up.'],
                ]),
            ],
            'media' => $stepImages,
        ],
        'detailed_features' => [
            'title' => 'Features',
            'content' => [
                'cta_text' => 'Join the Founding 20',
                'sections' => [
                    [
                        'label' => 'Every guest, not just the booker',
                        'heading' => 'A group of four books once. TenaFi meets all four.',
                        'description' => 'Booking platforms give you one name. Your WiFi sees every guest. TenaFi turns each connection into a verified, consented contact you own.',
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-wifi', 'title' => 'Branded WiFi login', 'desc' => 'Your logo and welcome on the page guests see first.', 'feature' => ''],
                            ['icon' => 'fas fa-envelope', 'title' => 'Verified contacts', 'desc' => 'Emails and phone numbers that actually reach people.', 'feature' => ''],
                            ['icon' => 'fas fa-shield-alt', 'title' => 'Consent built in', 'desc' => 'Every contact comes with a clear, recorded opt-in.', 'feature' => ''],
                        ],
                    ],
                    [
                        'label' => 'Guest homepage',
                        'heading' => 'Your house guide, extras and local tips on one page.',
                        'description' => 'After login, guests land on your own homepage: WiFi details, house rules, check-out steps and things to do nearby.',
                        'bg' => 'gray', 'reverse' => '1',
                        'features' => [
                            ['icon' => 'fas fa-book-open', 'title' => 'House guide', 'desc' => 'Answers before guests have to message you.', 'feature' => 'guest_homepage'],
                            ['icon' => 'fas fa-mobile-alt', 'title' => 'M-Pesa extras', 'desc' => 'Sell late check-out, airport pickups and more.', 'feature' => 'mpesa_extras'],
                            ['icon' => 'fas fa-map-marker-alt', 'title' => 'Local recommendations', 'desc' => 'Your favourite spots, shared with every guest.', 'feature' => 'guest_homepage'],
                        ],
                    ],
                    [
                        'label' => 'Messages & monthly report',
                        'heading' => 'Stay in touch automatically, and see what it earns you.',
                        'description' => 'Welcome messages, thank-yous and return-stay offers go out on time. Each month you get a simple report of guests captured and bookings won back.',
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-comment-dots', 'title' => 'Welcome & thank-you messages', 'desc' => 'Sent by SMS and email at the right moment.', 'feature' => ''],
                            ['icon' => 'fas fa-redo', 'title' => 'Return-stay offers', 'desc' => 'Invite past guests back for your quiet dates.', 'feature' => ''],
                            ['icon' => 'fas fa-file-alt', 'title' => 'Monthly report', 'desc' => 'Guests captured, messages sent, bookings won.', 'feature' => 'monthly_report'],
                        ],
                    ],
                    [
                        'label' => 'Property protection',
                        'heading' => 'Know what is happening at your property.',
                        'description' => 'Alerts when more devices connect than your booking allows, when the WiFi goes down, and bookings kept in sync with your PMS.',
                        'bg' => 'gray', 'reverse' => '1',
                        'features' => [
                            ['icon' => 'fas fa-user-shield', 'title' => 'Occupancy alerts', 'desc' => 'Get alerted if more guests connect than booked.', 'feature' => 'occupancy_alerts'],
                            ['icon' => 'fas fa-exclamation-triangle', 'title' => 'Outage alerts', 'desc' => 'Hear about WiFi problems before your guests do.', 'feature' => 'outage_alerts'],
                            ['icon' => 'fas fa-sync', 'title' => 'PMS / channel manager sync', 'desc' => 'Bookings and guest names kept in sync.', 'feature' => 'pms_sync'],
                        ],
                    ],
                ],
            ],
            'media' => [
                'section_0_image' => $img('Branded-Splash-Page.jpg'),
                'section_1_image' => $img('Tena-Portrait-1.jpg'),
                'section_2_image' => $img('Tena-Features-1.jpg'),
                'section_3_image' => $img('Clients-view.jpg'),
            ],
        ],
        'credibility' => [
            'title' => 'Built by Superhosts',
            'bg' => 'gray',
            'content' => [
                'badge' => 'The experience behind TenaFi: Stay Awhile Rentals',
                'title' => 'Built by Superhosts. Built for Superhosts.',
                'subtitle' => 'TenaFi was born from Stay Awhile Rentals, a real short-term rental business. After hosting thousands of guests we saw how hard it is to build a relationship beyond the booking platform. That experience became TenaFi.',
                'closing_line' => "We lived the problem. Now we're building the solution for hosts across Africa.",
                'tagline' => 'Own the Guest. Build the Relationship.',
                'stats' => [
                    ['value' => '16×', 'label' => 'Superhost'],
                    ['value' => '1,400+', 'label' => 'Reservations'],
                    ['value' => '5,000+', 'label' => 'Guest Nights'],
                    ['value' => '750+', 'label' => 'Guest Reviews'],
                    ['value' => '4.9/5', 'label' => 'Guest Rating'],
                ],
            ],
            'media' => [
                'main_image' => $img('Tena-Hero-1.jpg'),
                'stay_awhile_logo' => '/legacy/assets/Tena-logo-square.jpg',
            ],
        ],
        'partners' => ['keep' => true, 'title' => 'Partners'],
        'pricing' => [
            'title' => 'Pricing',
            'bg' => 'gray',
            'content' => [
                'title' => 'Simple monthly plans',
                'subtitle' => 'No hardware to buy. Cancel any time.',
                'show_contact_form' => '0',
                'cta_label' => 'Founding 20',
                'cta_headline' => 'Be one of the first 20 TenaFi hosts.',
                'cta_intro' => "We're onboarding 20 founding hosts personally before public launch.",
                'cta_closing' => 'Once 20 spots are filled, the founding offer closes.',
                'cta_button' => 'Apply now',
                'perks' => [
                    ['symbol' => 'sparkles', 'label' => 'Founding pricing', 'text' => 'locked in while you stay'],
                    ['symbol' => 'zap', 'label' => 'Hands-on setup', 'text' => 'we install and configure it with you'],
                    ['symbol' => 'award', 'label' => 'Early access', 'text' => 'to new features as they ship'],
                    ['symbol' => 'clock', 'label' => 'Direct line', 'text' => 'to the team building TenaFi'],
                ],
            ],
        ],
        'signup' => [
            'title' => 'Sign-up (#join)',
            'content' => [
                'anchor' => 'join',
                'signup_type' => 'host',
                'badge' => 'Founding 20',
                'title' => 'Apply to join TenaFi',
                'subtitle' => 'Three quick steps. We reply within two working days.',
                'submit_label' => 'Send application',
                'consent_text' => $consent,
                'success_title' => "Thanks, you're in the queue.",
                'success_message' => "We've received your application and will be in touch on WhatsApp or email shortly.",
                'steps' => [
                    ['title' => 'About you', 'description' => 'So we know who to talk to.', 'fields' => $contactFields],
                    ['title' => 'Your properties', 'description' => 'A rough picture is fine.', 'fields' => [
                        ['key' => 'units', 'label' => 'How many units do you run?', 'type' => 'select', 'required' => true, 'options' => ['1', '2-5', '6-20', '21+'], 'placeholder' => ''],
                        ['key' => 'property_type', 'label' => 'Property type', 'type' => 'select', 'required' => true, 'options' => ['Apartment', 'House / villa', 'Serviced apartments', 'Boutique hotel', 'Other'], 'placeholder' => ''],
                        ['key' => 'location', 'label' => 'Area / city', 'type' => 'text', 'required' => true, 'options' => [], 'placeholder' => 'e.g. Kilimani, Nairobi'],
                        ['key' => 'primary_platform', 'label' => 'Where do most bookings come from?', 'type' => 'select', 'required' => true, 'options' => ['Airbnb', 'Booking.com', 'Both / several', 'Mostly direct'], 'placeholder' => ''],
                    ]],
                    ['title' => 'Your goals', 'description' => 'Helps us tailor your setup.', 'fields' => [
                        ['key' => 'biggest_challenge', 'label' => 'What would help most?', 'type' => 'select', 'required' => true, 'options' => ['Filling quiet nights', 'More repeat guests', 'Fewer platform fees', 'Better guest communication', 'Other'], 'placeholder' => ''],
                        $planInterest,
                        $referral,
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
                'meta_title' => 'TenaFi for business | Turn your existing WiFi into more Google reviews',
                'meta_description' => 'Cafés, salons, clinics and gyms: turn customers who use your WiFi into Google reviews and repeat visits.',
            ],
        ],
        'hero' => [
            'title' => 'Hero',
            'bg' => 'gray',
            'content' => [
                'badge' => 'For cafés, salons, clinics, gyms and more',
                'title' => 'Turn your existing WiFi into '.$hl('more Google reviews').'.',
                'subtitle' => 'Customers already use your WiFi. TenaFi asks every one of them for a review at the right moment, and brings them back.',
                'cta_primary' => 'Sign up',
                'cta_primary_url' => '#signup',
                'cta_secondary' => 'See how it works',
                'cta_secondary_url' => '#how-it-works',
            ],
            'media' => ['main_image' => $img('Clients-view.jpg')],
        ],
        'problem' => [
            'title' => 'The review problem',
            'content' => [
                'badge' => 'The problem',
                'title' => 'Happy customers leave without leaving a review.',
                'description' => 'Most satisfied customers never think to review you, so a few unhappy voices shape what new customers see on Google. And once they walk out, you have no way to invite them back.',
            ],
            'media' => $problemImages,
        ],
        'how_it_works' => [
            'title' => 'How it works',
            'content' => [
                'title' => 'How TenaFi works for businesses',
                'subtitle' => 'Connect, Capture, Engage, Grow.',
                'steps' => $connectSteps([
                    ['fas fa-wifi', 'Connect', 'We plug into your existing WiFi. Customers log in through your branded page.'],
                    ['fas fa-address-card', 'Capture', 'Each customer leaves a verified contact, with consent.'],
                    ['fas fa-star', 'Engage', 'A friendly review request goes to every customer after their visit.'],
                    ['fas fa-chart-line', 'Grow', 'More reviews, more repeat visits, and a monthly report to prove it.'],
                ]),
            ],
            'media' => $stepImages,
        ],
        'detailed_features' => [
            'title' => 'Features',
            'content' => [
                'cta_text' => 'Sign up',
                'sections' => [
                    [
                        'label' => 'Google reviews',
                        'heading' => 'Ask every customer, not just the regulars.',
                        'description' => "Every customer who connects gets a short, friendly request with a direct link to your Google review page. We ask everyone the same way, as Google's guidelines require.",
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-star', 'title' => 'Review requests', 'desc' => 'Sent by SMS or email after the visit.', 'feature' => ''],
                            ['icon' => 'fas fa-link', 'title' => 'One-tap review link', 'desc' => 'Straight to your Google Business Profile.', 'feature' => ''],
                            ['icon' => 'fas fa-shield-alt', 'title' => 'Consent built in', 'desc' => 'Every contact comes with a recorded opt-in.', 'feature' => ''],
                        ],
                    ],
                    [
                        'label' => 'Customer homepage',
                        'heading' => 'Your menu, offers and booking link after login.',
                        'description' => 'Customers land on your own page after connecting: what you offer, this week\'s specials and how to book again.',
                        'bg' => 'gray', 'reverse' => '1',
                        'features' => [
                            ['icon' => 'fas fa-store', 'title' => 'Branded customer homepage', 'desc' => 'Your offers in front of every customer.', 'feature' => 'business_homepage'],
                        ],
                    ],
                    [
                        'label' => 'Repeat visits & monthly report',
                        'heading' => 'Bring customers back, and see it working.',
                        'description' => 'Send win-back offers to past customers and get a simple monthly report of contacts captured, reviews requested and customers returning.',
                        'bg' => 'white', 'reverse' => '0',
                        'features' => [
                            ['icon' => 'fas fa-redo', 'title' => 'Win-back messages', 'desc' => 'Offers to customers you have not seen in a while.', 'feature' => ''],
                            ['icon' => 'fas fa-file-alt', 'title' => 'Monthly report', 'desc' => 'Contacts, reviews requested, repeat visits.', 'feature' => 'monthly_report'],
                            ['icon' => 'fas fa-exclamation-triangle', 'title' => 'Outage alerts', 'desc' => 'Know when the WiFi is down.', 'feature' => 'outage_alerts'],
                        ],
                    ],
                ],
            ],
            'media' => [
                'section_0_image' => $img('Branded-Splash-Page.jpg'),
                'section_1_image' => $img('Tena-Welcome-Divine.jpg'),
                'section_2_image' => $img('Tena-Features-1.jpg'),
            ],
        ],
        'pricing' => [
            'title' => 'Pricing',
            'bg' => 'gray',
            'content' => [
                'title' => 'Simple monthly plans',
                'subtitle' => 'No hardware to buy. Cancel any time.',
                'show_contact_form' => '0',
                'cta_label' => 'Founding 20',
                'cta_headline' => 'Be one of the first 20 TenaFi businesses.',
                'cta_intro' => "We're onboarding 20 founding businesses personally before public launch.",
                'cta_closing' => 'Once 20 spots are filled, the founding offer closes.',
                'cta_button' => 'Sign up now',
                'perks' => [
                    ['symbol' => 'sparkles', 'label' => 'Founding pricing', 'text' => 'locked in while you stay'],
                    ['symbol' => 'zap', 'label' => 'Hands-on setup', 'text' => 'we install and configure it with you'],
                    ['symbol' => 'award', 'label' => 'Early access', 'text' => 'to new features as they ship'],
                    ['symbol' => 'clock', 'label' => 'Direct line', 'text' => 'to the team building TenaFi'],
                ],
            ],
        ],
        'signup' => [
            'title' => 'Sign-up (#signup)',
            'content' => [
                'anchor' => 'signup',
                'signup_type' => 'business',
                'badge' => 'Founding 20',
                'title' => 'Sign up your business',
                'subtitle' => 'Three quick steps. We reply within two working days.',
                'submit_label' => 'Send sign-up',
                'consent_text' => $consent,
                'success_title' => 'Thanks, we have your details.',
                'success_message' => "We'll be in touch on WhatsApp or email to arrange setup.",
                'steps' => [
                    ['title' => 'About you', 'description' => 'So we know who to talk to.', 'fields' => $contactFields],
                    ['title' => 'Your business', 'description' => 'A rough picture is fine.', 'fields' => [
                        ['key' => 'business_name', 'label' => 'Business name', 'type' => 'text', 'required' => true, 'options' => [], 'placeholder' => ''],
                        ['key' => 'business_type', 'label' => 'Type of business', 'type' => 'select', 'required' => true, 'options' => ['Café / restaurant', 'Salon / barber / spa', 'Clinic', 'Gym / fitness', 'Retail', 'Other'], 'placeholder' => ''],
                        ['key' => 'location', 'label' => 'Area / city', 'type' => 'text', 'required' => true, 'options' => [], 'placeholder' => 'e.g. Westlands, Nairobi'],
                        ['key' => 'branches', 'label' => 'Number of locations', 'type' => 'select', 'required' => true, 'options' => ['1', '2-5', '6+'], 'placeholder' => ''],
                    ]],
                    ['title' => 'Your goals', 'description' => 'Helps us tailor your setup.', 'fields' => [
                        ['key' => 'google_reviews', 'label' => 'Google reviews today', 'type' => 'select', 'required' => false, 'options' => ['None yet', 'Under 20', '20-100', '100+'], 'placeholder' => ''],
                        ['key' => 'biggest_challenge', 'label' => 'What would help most?', 'type' => 'select', 'required' => true, 'options' => ['More Google reviews', 'More repeat customers', 'A customer contact list', 'Other'], 'placeholder' => ''],
                        $planInterest,
                        $referral,
                    ]],
                ],
            ],
        ],
    ],
];
