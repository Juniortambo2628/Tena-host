<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use App\Models\LandingMedia;
use App\Models\LandingSection;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed if no landing data exists yet (preserves CMS edits on re-deploy)
        if (LandingSection::count() > 0) {
            return;
        }

        // Hero Section
        $hero = LandingSection::create([
            'section_key' => 'hero',
            'title' => 'Hero',
            'badge' => 'Built by Superhosts — For Superhosts',
            'bg' => 'gray',
            'sort_order' => 1,
        ]);

        LandingContent::insert([
            ['section_id' => $hero->id, 'content_key' => 'title', 'value' => 'Stop Losing <span class="text-[#FFD300]">20% to OTAs</span>. Take Control of Your Bookings.', 'type' => 'html', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $hero->id, 'content_key' => 'subtitle', 'value' => '<strong>Tena</strong> — built by Superhosts for Superhosts. Grow your guest list, boost repeat bookings, and save on commission — all from your WiFi.', 'type' => 'html', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $hero->id, 'content_key' => 'cta_primary', 'value' => 'Join the Waitlist Today', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $hero->id, 'content_key' => 'cta_secondary', 'value' => 'Learn How It Works', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $hero->id, 'content_key' => 'cta_secondary_url', 'value' => '#how-it-works', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
        ]);

        LandingMedia::insert([
            ['section_id' => $hero->id, 'media_key' => 'main_image', 'original_path' => '/legacy/img/hero-slider-1.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 0, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Hero Features
        $heroFeatures = [
            ['media_key' => 'feature_1', 'icon' => 'fa fa-list-alt', 'title' => 'Build Your Guest List', 'description' => 'Capture guest contact details automatically through your WiFi network.', 'image' => '/legacy/assets/Tena-Landing/Step-1-Connect.jpg'],
            ['media_key' => 'feature_2', 'icon' => 'fa fa-percent', 'title' => 'Reduce OTA Commissions', 'description' => 'Save up to 20% by converting OTA guests to direct bookers.', 'image' => '/legacy/assets/Tena-Landing/Step-2-Data-Collection.jpg'],
            ['media_key' => 'feature_3', 'icon' => 'fa fa-sync', 'title' => 'Increase Repeat Bookings', 'description' => 'Build lasting relationships with guests for future stays.', 'image' => '/legacy/assets/Tena-Landing/Step-3-Remarket.jpg'],
            ['media_key' => 'feature_4', 'icon' => 'fa fa-rocket', 'title' => 'Easy Setup & Deployment', 'description' => 'Get up and running in minutes with our simple installation.', 'image' => '/legacy/assets/Tena-Landing/Branded-Splash-Page.jpg'],
        ];

        foreach ($heroFeatures as $i => $feat) {
            LandingContent::insert([
                ['section_id' => $hero->id, 'content_key' => "features.{$i}.icon", 'value' => $feat['icon'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $hero->id, 'content_key' => "features.{$i}.title", 'value' => $feat['title'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $hero->id, 'content_key' => "features.{$i}.description", 'value' => $feat['description'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ]);
            LandingMedia::insert([
                ['section_id' => $hero->id, 'media_key' => "feature_{$i}_image", 'original_path' => $feat['image'], 'mime_type' => 'image/jpeg', 'file_size' => 0, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Features Section
        $features = LandingSection::create([
            'section_key' => 'features',
            'title' => 'Why Hosts Choose Tena',
            'bg' => 'white',
            'sort_order' => 2,
        ]);

        $featureItems = [
            ['icon' => 'fas fa-wifi', 'title' => 'WiFi Splash Pages', 'description' => 'Guest-facing splash pages with your branding to collect emails and phone numbers.'],
            ['icon' => 'fas fa-users', 'title' => 'Guest Data Collection', 'description' => 'Collect guest emails and phone numbers securely during WiFi onboarding.'],
            ['icon' => 'fas fa-bullhorn', 'title' => 'Remarketing Tools', 'description' => 'Auto-send review reminders and rebook direct via SMS and Email campaigns.'],
            ['icon' => 'fas fa-chart-line', 'title' => 'Analytics Dashboard', 'description' => 'View guest captures, campaign performance and bookings in one place.'],
            ['icon' => 'fas fa-plug', 'title' => 'PMS Integrations', 'description' => 'Integrate with PMS and channel managers to keep bookings in sync.'],
            ['icon' => 'fas fa-shield-alt', 'title' => 'Privacy & Compliance', 'description' => 'We follow best practices for guest consent and data protection.'],
        ];

        foreach ($featureItems as $i => $item) {
            LandingContent::insert([
                ['section_id' => $features->id, 'content_key' => "items.{$i}.icon", 'value' => $item['icon'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $features->id, 'content_key' => "items.{$i}.title", 'value' => $item['title'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $features->id, 'content_key' => "items.{$i}.description", 'value' => $item['description'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // How It Works Section
        $howItWorks = LandingSection::create([
            'section_key' => 'how_it_works',
            'title' => 'How Tena Works',
            'subtitle' => 'Four simple steps to capture guest data, engage them with your brand, and drive direct bookings.',
            'bg' => 'white',
            'sort_order' => 3,
        ]);

        $steps = [
            ['step' => '1', 'icon' => 'fas fa-wifi', 'title' => 'Guest Connects', 'description' => 'Guest connects to WiFi & data is captured through a branded splash page.', 'image' => '/legacy/assets/Tena-Landing/Step-1-Connect.jpg'],
            ['step' => '2', 'icon' => 'fas fa-home', 'title' => 'Guest Homepage', 'description' => 'A customized landing page is shown with native upsell storefronts & guidebooks.', 'image' => '/legacy/assets/Tena-Landing/Step-2-Data-Collection.jpg'],
            ['step' => '3', 'icon' => 'fas fa-envelope-open-text', 'title' => 'Automated Msgs', 'description' => 'Guest automatically receives welcome email, SMS reviews, & stay anniversaries.', 'image' => '/legacy/assets/Tena-Landing/Step-3-Remarket.jpg'],
            ['step' => '4', 'icon' => 'fas fa-calendar-check', 'title' => 'Direct Rebooking', 'description' => 'Guest books direct for next trip and keeps coming back.', 'image' => '/legacy/assets/Tena-Landing/Branded-Splash-Page.jpg'],
        ];

        foreach ($steps as $i => $step) {
            LandingContent::insert([
                ['section_id' => $howItWorks->id, 'content_key' => "steps.{$i}.step", 'value' => $step['step'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $howItWorks->id, 'content_key' => "steps.{$i}.icon", 'value' => $step['icon'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $howItWorks->id, 'content_key' => "steps.{$i}.title", 'value' => $step['title'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $howItWorks->id, 'content_key' => "steps.{$i}.description", 'value' => $step['description'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ]);
            LandingMedia::insert([
                ['section_id' => $howItWorks->id, 'media_key' => "step_{$i}_image", 'original_path' => $step['image'], 'mime_type' => 'image/jpeg', 'file_size' => 0, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Problem Section
        $problem = LandingSection::create([
            'section_key' => 'problem',
            'title' => 'OTA Commissions Are Costing You',
            'badge' => 'Did you know?',
            'bg' => 'white',
            'sort_order' => 4,
        ]);

        LandingContent::insert([
            ['section_id' => $problem->id, 'content_key' => 'description', 'value' => 'OTAs (Online Travel Agencies) can take up to 20% of your booking revenue — and you lose control of the guest relationship. When guests book through OTAs, you miss an opportunity to collect their contact details, making it difficult to rebook them directly. Tena aims to bridge this gap by providing an avenue for hosts to collect guest contact details.', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $problemImages = [
            '/legacy/assets/Tena-Landing/Problem-1.jpg',
            '/legacy/assets/Tena-Landing/Problem-2.jpg',
            '/legacy/assets/Tena-Landing/Problem-3.jpg',
        ];

        foreach ($problemImages as $i => $img) {
            LandingMedia::insert([
                ['section_id' => $problem->id, 'media_key' => "image_{$i}", 'original_path' => $img, 'mime_type' => 'image/jpeg', 'file_size' => 0, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // ROI Calculator Section
        $roi = LandingSection::create([
            'section_key' => 'roi_calculator',
            'title' => 'Return on Investment Calculator',
            'subtitle' => 'Answer a few simple questions to calculate how much more you could be earning with direct bookings through Tena.',
            'bg' => 'gray',
            'sort_order' => 5,
        ]);

        // Pricing Section
        $pricing = LandingSection::create([
            'section_key' => 'pricing',
            'title' => 'Transparent Pricing',
            'subtitle' => 'Simple, predictable pricing so you can scale direct bookings without surprises.',
            'bg' => 'gray',
            'sort_order' => 6,
        ]);

        $plans = [
            ['label' => 'Monthly Subscription', 'price' => '$10', 'unit' => '/ listing / month', 'description' => 'Includes guest data collection, analytics dashboard, and marketing tools (SMS & Email).', 'cta' => 'Join Waitlist', 'variant' => 'dark'],
            ['label' => 'Device Cost', 'price' => '$150', 'unit' => 'one-time', 'description' => 'One-time WiFi hardware cost to run the splash pages and capture guests on-site.', 'cta' => 'Get Early Access', 'variant' => 'outline'],
            ['label' => 'Founding Host Bundle', 'price' => '$45', 'unit' => '/ month', 'description' => 'Pay monthly ($79/month for first 6 months) — drops to $49/month after the device is paid off. Founding hosts get 1 month free.', 'cta' => 'Claim Founding Offer', 'variant' => 'dark'],
        ];

        foreach ($plans as $i => $plan) {
            foreach ($plan as $key => $value) {
                LandingContent::insert([
                    ['section_id' => $pricing->id, 'content_key' => "plans.{$i}.{$key}", 'value' => $value, 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }

        LandingContent::insert([
            ['section_id' => $pricing->id, 'content_key' => 'cta_label', 'value' => 'Become a Founding Host', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $pricing->id, 'content_key' => 'cta_text', 'value' => 'Join the Tena waitlist for priority access before public launch.<br/><br/>We\'re inviting only our first 100 hosts to join the Founding Host Program.<br/><br/>As a Founding Host, you\'ll receive:<br/><br/><strong>*</strong> 3 months free on the Tena platform<br/><strong>*</strong> Priority onboarding and dedicated support<br/><strong>*</strong> Early access to new features<br/><strong>*</strong> The opportunity to receive a complimentary Tena device<br/><br/>Built by Superhosts, for Superhosts, Tena helps you capture every guest—not just the booker—build lasting guest relationships, and drive more direct bookings beyond the OTAs.<br/><br/>Applications are now open. Once all 100 Founding Host spots are filled, the program will close.', 'type' => 'html', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $pricing->id, 'content_key' => 'cta_button', 'value' => 'Join the Waitlist Now', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $pricing->id, 'content_key' => 'footer_text', 'value' => 'Questions? Email', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $pricing->id, 'content_key' => 'footer_email', 'value' => 'info@tena.host', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Detailed Features Section
        $detailed = LandingSection::create([
            'section_key' => 'detailed_features',
            'title' => 'Detailed Features',
            'bg' => 'white',
            'sort_order' => 7,
        ]);

        $detailSections = [
            ['id' => 'data-collection', 'label' => 'Guest Data Collection', 'heading' => 'Maximize on Collecting Information from Not Only the Booker but Every Guest', 'description' => 'Transform your WiFi into a powerful data collection tool. Capture contact details from every guest who connects, not just the person who made the booking.', 'image' => '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg', 'bg' => 'white', 'features' => [['icon' => 'fas fa-wifi', 'title' => 'Branded WiFi Splash Pages', 'desc' => 'Customize your WiFi login page with your property branding'], ['icon' => 'fas fa-envelope', 'title' => 'Automatically Verified Emails', 'desc' => 'Collect verified email addresses through our integrated verification'], ['icon' => 'fas fa-shield-alt', 'title' => 'GDPR Compliant Collection', 'desc' => 'Ensure guest agreement to terms and conditions']]],
            ['id' => 'email-marketing', 'label' => 'Tena Email Marketing', 'heading' => 'Send Pre-Built Emails That Drive Bookings in Seconds', 'description' => 'Streamline your email marketing with our pre-designed templates and automated campaigns.', 'image' => '/legacy/assets/Tena-Landing/Tena-Features-1.jpg', 'bg' => 'gray', 'reverse' => true, 'features' => [['icon' => 'fas fa-mouse-pointer', 'title' => 'Create with a Click', 'desc' => 'Use pre-generated content blocks to build professional emails'], ['icon' => 'fas fa-plug', 'title' => 'PMS Integration', 'desc' => 'Sync property details and guest data automatically'], ['icon' => 'fas fa-user-plus', 'title' => 'Pre-Arrival Data Collection', 'desc' => 'Collect guest info through custom landing pages']]],
            ['id' => 'guest-homepage', 'label' => 'Guest Homepage', 'heading' => 'Provide Upsell & Rental Information from One Dynamic Page', 'description' => 'Create a centralized hub for your guests with all rental information, upselling opportunities, and property resources.', 'image' => '/legacy/assets/Tena-Landing/Tena-Portrait-1.jpg', 'bg' => 'white', 'features' => [['icon' => 'fas fa-shopping-cart', 'title' => 'Upsell Amenities', 'desc' => 'Generate additional income through integrated partners'], ['icon' => 'fas fa-key', 'title' => 'Property Information', 'desc' => 'Easy access to guidebooks and contact info'], ['icon' => 'fas fa-comments', 'title' => 'Meet Guests', 'desc' => 'Engage guests via multiple communication channels']]],
            ['id' => 'sms-marketing', 'label' => 'Tena SMS Marketing', 'heading' => 'Engage Guests With Text Marketing & Review Screening', 'description' => 'Leverage the power of SMS marketing with high open rates to drive bookings, collect reviews, and increase revenue.', 'image' => '/legacy/assets/Tena-Landing/Tena-Welcome-Divine-1.jpg', 'bg' => 'gray', 'reverse' => true, 'features' => [['icon' => 'fas fa-star', 'title' => 'Rate & Review Campaigns', 'desc' => 'Automate review collection and screen for 5-star reviews'], ['icon' => 'fas fa-wifi', 'title' => 'WiFi Welcome Messages', 'desc' => 'Send personalized welcome texts'], ['icon' => 'fas fa-comments', 'title' => 'Group Messages', 'desc' => 'Send targeted marketing messages to past guests']]],
            ['id' => 'wifi-monitoring', 'label' => 'WiFi Monitoring & Protection', 'heading' => 'Reduce WiFi Issues & Protect Your Property', 'description' => 'Deploy enterprise-grade WiFi 6 mesh networks. Ensure reliable connectivity while protecting your investment.', 'image' => '/legacy/assets/Tena-Landing/Clients-view.jpg', 'bg' => 'white', 'features' => [['icon' => 'fas fa-plug', 'title' => 'Plug & Play Set-Up', 'desc' => 'Arrives ready to plug into your router'], ['icon' => 'fas fa-wifi', 'title' => 'Remote Outage Alerts', 'desc' => 'Monitor networks from one screen'], ['icon' => 'fas fa-user-shield', 'title' => 'Occupancy Alerting', 'desc' => 'Get alerted if guest count exceeds booking']]],
        ];

        foreach ($detailSections as $i => $section) {
            LandingContent::insert([
                ['section_id' => $detailed->id, 'content_key' => "sections.{$i}.label", 'value' => $section['label'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $detailed->id, 'content_key' => "sections.{$i}.heading", 'value' => $section['heading'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $detailed->id, 'content_key' => "sections.{$i}.description", 'value' => $section['description'], 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $detailed->id, 'content_key' => "sections.{$i}.bg", 'value' => $section['bg'] ?? 'white', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $detailed->id, 'content_key' => "sections.{$i}.reverse", 'value' => isset($section['reverse']) ? '1' : '0', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
                ['section_id' => $detailed->id, 'content_key' => "sections.{$i}.features", 'value' => json_encode($section['features']), 'type' => 'json', 'created_at' => now(), 'updated_at' => now()],
            ]);

            LandingMedia::insert([
                ['section_id' => $detailed->id, 'media_key' => "section_{$i}_image", 'original_path' => $section['image'], 'mime_type' => 'image/jpeg', 'file_size' => 0, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Media Showcase Section
        $showcase = LandingSection::create([
            'section_key' => 'media_showcase',
            'title' => 'Media Showcase',
            'bg' => 'white',
            'sort_order' => 8,
        ]);

        LandingContent::insert([
            ['section_id' => $showcase->id, 'content_key' => 'heading', 'value' => 'See Tena in Action', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $showcase->id, 'content_key' => 'description', 'value' => 'Experience how Tena transforms your WiFi into a powerful guest engagement platform.', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['section_id' => $showcase->id, 'content_key' => 'media_type', 'value' => 'video', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
        ]);

        LandingMedia::insert([
            ['section_id' => $showcase->id, 'media_key' => 'showcase_media', 'original_path' => '/legacy/assets/Tena-Landing/Step-1-Connect.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 0, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
