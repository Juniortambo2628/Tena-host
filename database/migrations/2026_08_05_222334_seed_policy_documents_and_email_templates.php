<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->seedPolicies();
        $this->seedEmailTemplates();
    }

    public function down(): void
    {
        DB::table('policy_documents')->where('slug', 'like', '%')->delete();
        DB::table('settings')->where('group', 'email_templates')->delete();
    }

    private function seedPolicies(): void
    {
        $now = now()->toDateTimeString();

        $policies = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'description' => 'How we collect, use, and protect your personal information.',
                'type' => 'privacy_policy',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01 00:00:00',
                'last_reviewed_at' => $now,
                'last_reviewed_by' => 'System',
                'created_at' => $now,
                'updated_at' => $now,
                'content' => '<h2>1. Introduction</h2><p>Welcome to Tena Host ("we," "our," or "us"). We are committed to protecting your personal information and your right to privacy.</p><h2>2. Information We Collect</h2><p>We collect information that you provide directly to us, including: Account Information, Property Information, Payment Information, and Communications.</p><h2>3. How We Use Your Information</h2><p>We use the information to provide, maintain, and improve our services, process transactions, send notifications, respond to inquiries, and protect against fraud.</p><h2>4. Information Sharing</h2><p>We do not sell your personal information. We may share with service providers, payment processors, and law enforcement when required.</p><h2>5. Data Security</h2><p>We implement appropriate technical and organizational measures to protect your personal information.</p><h2>6. Your Rights</h2><p>You have the right to access, correct, request deletion of your information, and opt out of marketing communications.</p><h2>7. Contact Us</h2><p>If you have questions, contact us at <strong>privacy@tena.host</strong>.</p>',
            ],
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'description' => 'The rules and guidelines for using the Tena platform.',
                'type' => 'terms_of_service',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01 00:00:00',
                'last_reviewed_at' => $now,
                'last_reviewed_by' => 'System',
                'created_at' => $now,
                'updated_at' => $now,
                'content' => '<h2>1. Acceptance of Terms</h2><p>By accessing or using the Tena Host platform, you agree to be bound by these Terms of Service.</p><h2>2. User Responsibilities</h2><p>You agree to provide accurate information, maintain account security, not use the platform illegally, and comply with all applicable laws.</p><h2>3. Host Obligations</h2><p>Hosts agree to maintain accurate listings, respond to inquiries, honor reservations, and maintain safe properties.</p><h2>4. Subscriptions and Payments</h2><p>Host subscriptions are billed monthly via Paystack or M-Pesa.</p><h2>5. Intellectual Property</h2><p>All content on Tena Host is protected by copyright laws.</p><h2>6. Limitation of Liability</h2><p>Tena Host shall not be liable for indirect, incidental, or punitive damages.</p><h2>7. Termination</h2><p>We may suspend or terminate your account for violation of these terms.</p><h2>8. Contact</h2><p>Contact us at <strong>legal@tena.host</strong>.</p>',
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Cookie Policy',
                'description' => 'How we use cookies and similar tracking technologies.',
                'type' => 'cookie_policy',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01 00:00:00',
                'last_reviewed_at' => $now,
                'last_reviewed_by' => 'System',
                'created_at' => $now,
                'updated_at' => $now,
                'content' => '<h2>1. What Are Cookies</h2><p>Cookies are small text files stored on your device when you visit our website.</p><h2>2. Types of Cookies We Use</h2><p>Essential Cookies, Analytics Cookies, and Preference Cookies.</p><h2>3. Managing Cookies</h2><p>You can control cookies through your browser settings.</p><h2>4. Third-Party Cookies</h2><p>We may use Google Analytics and Paystack.</p><h2>5. Contact</h2><p>Contact us at <strong>privacy@tena.host</strong>.</p>',
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund Policy',
                'description' => 'Our refund and cancellation terms for subscriptions.',
                'type' => 'refund_policy',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01 00:00:00',
                'last_reviewed_at' => $now,
                'last_reviewed_by' => 'System',
                'created_at' => $now,
                'updated_at' => $now,
                'content' => '<h2>1. Subscription Refunds</h2><p>We offer a 14-day money-back guarantee for new subscriptions.</p><h2>2. How to Request a Refund</h2><p>Contact <strong>billing@tena.host</strong> with your account email, reason, and date of subscription.</p><h2>3. Processing Time</h2><p>Refunds are processed within 5-10 business days.</p><h2>4. Contact</h2><p>For billing inquiries, contact <strong>billing@tena.host</strong>.</p>',
            ],
            [
                'slug' => 'acceptable-use-policy',
                'title' => 'Acceptable Use Policy',
                'description' => 'Guidelines for acceptable behavior on the platform.',
                'type' => 'acceptable_use',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01 00:00:00',
                'last_reviewed_at' => $now,
                'last_reviewed_by' => 'System',
                'created_at' => $now,
                'updated_at' => $now,
                'content' => '<h2>1. Prohibited Activities</h2><p>You agree not to use the platform for unlawful purposes, post false content, harass others, or distribute malware.</p><h2>2. Content Standards</h2><p>All content must be accurate, not infringe IP rights, and comply with laws.</p><h2>3. Enforcement</h2><p>We may remove content, suspend accounts, or report illegal activities.</p><h2>4. Reporting</h2><p>Report violations to <strong>safety@tena.host</strong>.</p>',
            ],
            [
                'slug' => 'data-processing-agreement',
                'title' => 'Data Processing Agreement',
                'description' => 'How we process personal data on behalf of our users.',
                'type' => 'data_processing',
                'version' => '1.0',
                'is_published' => false,
                'effective_date' => '2026-02-01 00:00:00',
                'last_reviewed_at' => $now,
                'last_reviewed_by' => 'System',
                'created_at' => $now,
                'updated_at' => $now,
                'content' => '<h2>1. Overview</h2><p>This DPA describes how Tena Host processes personal data on behalf of our users.</p><h2>2. Scope</h2><p>Applies to all personal data processed through the platform.</p><h2>3. Principles</h2><p>Lawfulness, Purpose Limitation, Data Minimization, Accuracy, Storage Limitation, Security.</p><h2>4. Sub-processors</h2><p>Paystack, M-Pesa, Cloud hosting providers.</p><h2>5. Contact</h2><p>Contact our DPO at <strong>dpo@tena.host</strong>.</p>',
            ],
        ];

        foreach ($policies as $policy) {
            DB::table('policy_documents')->updateOrInsert(
                ['slug' => $policy['slug']],
                $policy
            );
        }
    }

    private function seedEmailTemplates(): void
    {
        $templates = [
            ['key' => 'welcome_email_heading', 'value' => 'Welcome to Tena Host!', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'welcome_email_body', 'value' => 'Thank you for joining Tena Host. We are excited to have you on board!', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'receipt_email_heading', 'value' => 'Your Payment Receipt', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'receipt_email_body', 'value' => 'Thank you for your payment. Here is your receipt.', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'forgot_password_email_heading', 'value' => 'Reset Your Password', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'forgot_password_email_body', 'value' => 'Click the link below to reset your password.', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'waitlist_confirmation_subject', 'value' => "You're on the Tena waitlist!", 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'waitlist_confirmation_heading', 'value' => "You're on the list!", 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'waitlist_confirmation_body', 'value' => '', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'waitlist_welcome_subject', 'value' => 'Welcome to the Tena Family!', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'waitlist_welcome_heading', 'value' => 'Welcome to the Tena Family!', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'waitlist_welcome_body', 'value' => '', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'email_primary_color', 'value' => '#1a1a2e', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'email_accent_color', 'value' => '#FFD300', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'site_name', 'value' => 'Tena Host', 'group' => 'general', 'type' => 'string'],
            ['key' => 'business_address', 'value' => 'Nairobi, Kenya', 'group' => 'general', 'type' => 'string'],
            ['key' => 'logo_url', 'value' => '/legacy/assets/Tena-logo-square.jpg', 'group' => 'branding', 'type' => 'string'],
        ];

        foreach ($templates as $t) {
            DB::table('settings')->updateOrInsert(
                ['key' => $t['key']],
                array_merge($t, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
};
