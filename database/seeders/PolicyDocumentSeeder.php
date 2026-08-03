<?php

namespace Database\Seeders;

use App\Models\PolicyDocument;
use Illuminate\Database\Seeder;

class PolicyDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'description' => 'How we collect, use, and protect your personal information.',
                'type' => 'privacy_policy',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01',
                'content' => $this->getPrivacyPolicyContent(),
            ],
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'description' => 'The rules and guidelines for using the Tena platform.',
                'type' => 'terms_of_service',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01',
                'content' => $this->getTermsOfServiceContent(),
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Cookie Policy',
                'description' => 'How we use cookies and similar tracking technologies.',
                'type' => 'cookie_policy',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01',
                'content' => $this->getCookiePolicyContent(),
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund Policy',
                'description' => 'Our refund and cancellation terms for subscriptions.',
                'type' => 'refund_policy',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01',
                'content' => $this->getRefundPolicyContent(),
            ],
            [
                'slug' => 'acceptable-use-policy',
                'title' => 'Acceptable Use Policy',
                'description' => 'Guidelines for acceptable behavior on the platform.',
                'type' => 'acceptable_use',
                'version' => '1.0',
                'is_published' => true,
                'effective_date' => '2026-02-01',
                'content' => $this->getAcceptableUseContent(),
            ],
            [
                'slug' => 'data-processing-agreement',
                'title' => 'Data Processing Agreement',
                'description' => 'How we process personal data on behalf of our users.',
                'type' => 'data_processing',
                'version' => '1.0',
                'is_published' => false,
                'effective_date' => '2026-02-01',
                'content' => $this->getDataProcessingContent(),
            ],
        ];

        foreach ($policies as $policy) {
            PolicyDocument::firstOrCreate(
                ['slug' => $policy['slug']],
                array_merge($policy, [
                    'last_reviewed_at' => now(),
                    'last_reviewed_by' => 'System',
                ])
            );
        }
    }

    private function getPrivacyPolicyContent(): string
    {
        return <<<'HTML'
<h2>1. Introduction</h2>
<p>Welcome to Tena Host ("we," "our," or "us"). We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.</p>

<h2>2. Information We Collect</h2>
<p>We collect information that you provide directly to us, including:</p>
<ul>
<li><strong>Account Information:</strong> Name, email address, phone number, and password when you create an account.</li>
<li><strong>Property Information:</strong> Details about properties you list on our platform.</li>
<li><strong>Payment Information:</strong> Billing details processed securely through our payment providers (Stripe and M-Pesa).</li>
<li><strong>Communications:</strong> Messages you send to us or other users through the platform.</li>
</ul>

<h2>3. How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
<li>Provide, maintain, and improve our services</li>
<li>Process transactions and send related information</li>
<li>Send administrative notifications and updates</li>
<li>Respond to your comments and questions</li>
<li>Protect against fraud and unauthorized transactions</li>
</ul>

<h2>4. Information Sharing</h2>
<p>We do not sell your personal information. We may share your information with:</p>
<ul>
<li>Service providers who assist in operating our platform</li>
<li>Payment processors (Stripe, M-Pesa) for transaction processing</li>
<li>Law enforcement when required by law</li>
</ul>

<h2>5. Data Security</h2>
<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h2>6. Your Rights</h2>
<p>You have the right to:</p>
<ul>
<li>Access your personal information</li>
<li>Correct inaccurate information</li>
<li>Request deletion of your information</li>
<li>Opt out of marketing communications</li>
</ul>

<h2>7. Contact Us</h2>
<p>If you have questions about this Privacy Policy, please contact us at <strong>privacy@tena.host</strong>.</p>
HTML;
    }

    private function getTermsOfServiceContent(): string
    {
        return <<<'HTML'
<h2>1. Acceptance of Terms</h2>
<p>By accessing or using the Tena Host platform, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>

<h2>2. User Responsibilities</h2>
<p>As a user of Tena Host, you agree to:</p>
<ul>
<li>Provide accurate and complete information</li>
<li>Maintain the security of your account credentials</li>
<li>Not use the platform for any illegal or unauthorized purpose</li>
<li>Not interfere with or disrupt the platform's functionality</li>
<li>Comply with all applicable laws and regulations</li>
</ul>

<h2>3. Host Obligations</h2>
<p>Hosts using our platform agree to:</p>
<ul>
<li>Maintain accurate property listings</li>
<li>Respond to guest inquiries in a timely manner</li>
<li>Honor confirmed reservations</li>
<li>Maintain properties in a safe and habitable condition</li>
</ul>

<h2>4. Subscriptions and Payments</h2>
<p>Host subscriptions are billed monthly. Payment methods accepted include credit/debit cards (via Stripe) and M-Pesa. All fees are non-refundable except as outlined in our Refund Policy.</p>

<h2>5. Intellectual Property</h2>
<p>All content on the Tena Host platform, including text, graphics, logos, and software, is the property of Tena Host and is protected by copyright laws.</p>

<h2>6. Limitation of Liability</h2>
<p>Tena Host shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the platform.</p>

<h2>7. Termination</h2>
<p>We reserve the right to suspend or terminate your account at any time for violation of these terms or for any other reason at our discretion.</p>

<h2>8. Changes to Terms</h2>
<p>We may update these Terms of Service from time to time. We will notify you of any material changes by posting the new terms on this page.</p>

<h2>9. Contact</h2>
<p>For questions about these Terms, contact us at <strong>legal@tena.host</strong>.</p>
HTML;
    }

    private function getCookiePolicyContent(): string
    {
        return <<<'HTML'
<h2>1. What Are Cookies</h2>
<p>Cookies are small text files that are stored on your device when you visit our website. They help us provide you with a better experience by remembering your preferences and analyzing how you use our platform.</p>

<h2>2. Types of Cookies We Use</h2>
<ul>
<li><strong>Essential Cookies:</strong> Required for the platform to function properly (e.g., authentication, security).</li>
<li><strong>Analytics Cookies:</strong> Help us understand how visitors interact with our platform.</li>
<li><strong>Preference Cookies:</strong> Remember your settings and preferences.</li>
</ul>

<h2>3. Managing Cookies</h2>
<p>You can control and manage cookies through your browser settings. Please note that disabling certain cookies may affect the functionality of our platform.</p>

<h2>4. Third-Party Cookies</h2>
<p>We may use third-party services that place cookies on your device, including:</p>
<ul>
<li>Google Analytics (analytics)</li>
<li>Stripe (payment processing)</li>
</ul>

<h2>5. Updates to This Policy</h2>
<p>We may update this Cookie Policy from time to time. Any changes will be posted on this page with an updated effective date.</p>

<h2>6. Contact</h2>
<p>For questions about our use of cookies, contact us at <strong>privacy@tena.host</strong>.</p>
HTML;
    }

    private function getRefundPolicyContent(): string
    {
        return <<<'HTML'
<h2>1. Subscription Refunds</h2>
<p>Tena Host offers a 14-day money-back guarantee for new subscriptions. If you are not satisfied with our service within the first 14 days of your subscription, you may request a full refund.</p>

<h2>2. How to Request a Refund</h2>
<p>To request a refund, please contact our support team at <strong>billing@tena.host</strong> with:</p>
<ul>
<li>Your account email address</li>
<li>The reason for your refund request</li>
<li>Date of subscription</li>
</ul>

<h2>3. Processing Time</h2>
<p>Refunds are typically processed within 5-10 business days. The refund will be issued to your original payment method.</p>

<h2>4. Exceptions</h2>
<p>Refunds may not be available for:</p>
<ul>
<li>Subscriptions older than 14 days</li>
<li>Accounts terminated for violation of our Terms of Service</li>
<li>Services purchased through third-party providers</li>
</ul>

<h2>5. M-Pesa Payments</h2>
<p>For M-Pesa payments, refunds will be processed to the phone number used for the original transaction.</p>

<h2>6. Contact</h2>
<p>For billing inquiries, contact us at <strong>billing@tena.host</strong>.</p>
HTML;
    }

    private function getAcceptableUseContent(): string
    {
        return <<<'HTML'
<h2>1. Prohibited Activities</h2>
<p>You agree not to:</p>
<ul>
<li>Use the platform for any unlawful purpose</li>
<li>Post false, misleading, or fraudulent content</li>
<li>Harass, abuse, or threaten other users</li>
<li>Attempt to gain unauthorized access to other accounts</li>
<li>Distribute spam, malware, or other harmful content</li>
<li>Violate any applicable laws or regulations</li>
</ul>

<h2>2. Content Standards</h2>
<p>All content posted on Tena Host must:</p>
<ul>
<li>Be accurate and truthful</li>
<li>Not infringe on intellectual property rights</li>
<li>Not contain offensive or inappropriate material</li>
<li>Comply with local and international laws</li>
</ul>

<h2>3. Enforcement</h2>
<p>We reserve the right to:</p>
<ul>
<li>Remove content that violates these standards</li>
<li>Suspend or terminate accounts for repeated violations</li>
<li>Report illegal activities to law enforcement</li>
</ul>

<h2>4. Reporting Violations</h2>
<p>If you encounter content or behavior that violates these standards, please report it to <strong>safety@tena.host</strong>.</p>
HTML;
    }

    private function getDataProcessingContent(): string
    {
        return <<<'HTML'
<h2>1. Overview</h2>
<p>This Data Processing Agreement (DPA) describes how Tena Host processes personal data on behalf of our users when providing our services.</p>

<h2>2. Scope</h2>
<p>This DPA applies to all personal data processed through the Tena Host platform, including:</p>
<ul>
<li>Guest information</li>
<li>Property listings</li>
<li>Transaction records</li>
<li>Communication logs</li>
</ul>

<h2>3. Data Processing Principles</h2>
<p>We process data in accordance with these principles:</p>
<ul>
<li><strong>Lawfulness:</strong> Processing is based on legitimate business purposes</li>
<li><strong>Purpose Limitation:</strong> Data is collected for specified, explicit purposes</li>
<li><strong>Data Minimization:</strong> We only collect data that is necessary</li>
<li><strong>Accuracy:</strong> We take steps to keep data accurate and up to date</li>
<li><strong>Storage Limitation:</strong> Data is retained only as long as necessary</li>
<li><strong>Security:</strong> Appropriate technical and organizational measures are implemented</li>
</ul>

<h2>4. Sub-processors</h2>
<p>We use the following sub-processors:</p>
<ul>
<li>Stripe (payment processing)</li>
<li>M-Pesa (mobile payments)</li>
<li>Cloud hosting providers (data storage)</li>
</ul>

<h2>5. Data Transfers</h2>
<p>Personal data may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place for such transfers.</p>

<h2>6. Contact</h2>
<p>For data protection inquiries, contact our Data Protection Officer at <strong>dpo@tena.host</strong>.</p>
HTML;
    }
}
