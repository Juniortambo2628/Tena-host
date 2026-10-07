<?php

/*
| TenaFi policy documents (relaunch drafts, v2.0). Used by the
| 2026_10_08_000002 migration and PolicyDocumentSeeder. Have them reviewed
| by counsel before relying on them; edit live copies in Admin → Policies.
*/

$contact = '<p>Questions: <strong>hello@tena-fi.com</strong>, or WhatsApp us from tena-fi.com. TenaFi, Nairobi, Kenya.</p>';

return [
    'privacy-policy' => [
        'title' => 'Privacy Policy',
        'description' => 'How TenaFi collects, uses and protects personal data.',
        'content' => '<h2>1. Who we are</h2><p>TenaFi ("we", "us") runs a guest relationship platform for short-term rental operators, hotels and local businesses ("hosts"). Hosts use TenaFi to offer WiFi, stay in touch with guests and customers, and collect reviews. We comply with the Kenya Data Protection Act, 2019.</p>'
            .'<h2>2. Two kinds of data</h2><p><strong>Hosts:</strong> we are the data controller for host accounts: name, email, phone, business details, billing and M-Pesa payment records.</p><p><strong>Guests and customers:</strong> when you log in to a host\'s WiFi, the host is the data controller and TenaFi processes your data on their behalf (see our Data Processing Agreement).</p>'
            .'<h2>3. What we collect at WiFi login</h2><ul><li>First name and WhatsApp/phone number; email if you choose to give it; birthday (day and month) where a business asks and you choose to give it.</li><li>Your device\'s network (MAC) address, so you can reconnect with one tap.</li><li>When you visited, and the exact consent wording you agreed to, with the time.</li><li>Orders and payments you make for extras (amount, M-Pesa receipt).</li><li>Whether you opened or tapped links in messages we send for the host.</li></ul>'
            .'<h2>4. Why we use it</h2><ul><li>To connect you to the WiFi (contract).</li><li>To send messages about your visit, including a thank-you and a review request (your consent at login).</li><li>To send offers and news, only if you ticked the offers box (your consent). You can reply <strong>STOP</strong> to any message to opt out at any time; reply START to opt back in.</li><li>To process payments for extras and subscriptions (contract) and keep financial records (legal obligation).</li><li>To keep the service secure and working (legitimate interest).</li></ul>'
            .'<h2>5. Who we share it with</h2><p>The host whose WiFi you used, and service providers acting for us: Safaricom (M-Pesa), Africa\'s Talking (SMS), Meta (WhatsApp Business), Paystack (card payments), our email and hosting providers, and the host\'s property-management system where they connect one. We do not sell personal data.</p>'
            .'<h2>6. How long we keep it</h2><p>Guest data is kept while the host\'s account is active and for up to 24 months after your last visit, then deleted or anonymised. Payment records are kept for 7 years as tax law requires.</p>'
            .'<h2>7. Your rights</h2><p>You can ask to access, correct or delete your data, object to marketing, or withdraw consent at any time, and you can complain to the Office of the Data Protection Commissioner (odpc.go.ke). For guest data, you can contact the host or us and we will pass your request on.</p>'
            .'<h2>8. Security</h2><p>Data is encrypted in transit, payment credentials are encrypted at rest, and access is limited to people who need it.</p>'
            .'<h2>9. Contact</h2>'.$contact,
    ],
    'terms-of-service' => [
        'title' => 'Terms of Service',
        'description' => 'The terms for hosts using TenaFi.',
        'content' => '<h2>1. Agreement</h2><p>These terms apply to anyone who uses TenaFi as a host (short-term rental operator, hotel or business). By creating an account you agree to them.</p>'
            .'<h2>2. The service</h2><p>TenaFi provides a branded WiFi login, guest and customer lists, review requests, WhatsApp/SMS/email campaigns, reports and related tools. We install and maintain the WiFi device; it remains TenaFi\'s property and must be returned when the subscription ends.</p>'
            .'<h2>3. Plans and payment</h2><p>Plans (Basic, Starter, Growth) are priced per unit or location per month in KES, as shown on our pricing pages, with multi-unit, quarterly and yearly discounts where stated. Payment is by M-Pesa or card in advance for each billing period. If payment lapses, access to the dashboard pauses until it is renewed.</p>'
            .'<h2>4. Your guests\' data</h2><p>You are the data controller for your guests\' and customers\' data and must have a lawful basis to contact them. Our Data Processing Agreement forms part of these terms. Campaigns only reach guests who agreed to receive them, and every campaign carries an opt-out.</p>'
            .'<h2>5. Extras paid by M-Pesa</h2><p>Where guests pay for extras through TenaFi, payments are collected on TenaFi\'s paybill. We pay the amount to you, less our published fee, on the settlement schedule we agree with you. You are responsible for delivering the extras you sell.</p>'
            .'<h2>6. Acceptable use</h2><p>You must follow our Acceptable Use Policy, including WhatsApp\'s and Safaricom\'s rules. We may pause messaging that breaks them.</p>'
            .'<h2>7. Results</h2><p>TenaFi is designed to help raise occupancy, reviews and repeat visits, but results vary and we do not guarantee them.</p>'
            .'<h2>8. Liability</h2><p>To the extent the law allows, our total liability is limited to the fees you paid in the 3 months before the claim, and we are not liable for indirect or consequential loss.</p>'
            .'<h2>9. Ending the service</h2><p>You can cancel at the end of any billing period. We may suspend accounts that break these terms. Kenyan law governs these terms.</p>'
            .'<h2>10. Contact</h2>'.$contact,
    ],
    'cookie-policy' => [
        'title' => 'Cookie Policy',
        'description' => 'How TenaFi uses cookies.',
        'content' => '<h2>1. Essential cookies</h2><p>We use cookies to keep you signed in, protect forms from forgery (CSRF) and remember your cookie choice. The site does not work without them.</p>'
            .'<h2>2. Analytics</h2><p>With your consent, we count visits and sign-up steps on our public pages so we can improve them. If Google Analytics or Tag Manager is enabled, it sets its own cookies.</p>'
            .'<h2>3. WiFi login</h2><p>The WiFi login page does not set tracking cookies; it recognises returning devices by their network address, as described in our Privacy Policy.</p>'
            .'<h2>4. Your choice</h2><p>You can accept or decline non-essential cookies in the banner, and clear cookies in your browser at any time.</p>'
            .'<h2>5. Contact</h2>'.$contact,
    ],
    'refund-policy' => [
        'title' => 'Refund Policy',
        'description' => 'Refunds for TenaFi subscriptions and guest extras.',
        'content' => '<h2>1. Subscriptions</h2><p>Subscriptions are paid in advance and are not refunded for partial periods. If you cancel, the service runs to the end of the period you paid for. If we cannot install or provide the service, we refund what you paid for the period affected.</p>'
            .'<h2>2. Extras paid by guests</h2><p>Extras (late checkout, cleaning, welcome packs and similar) are sold by the host. Refund requests go to the host; where a refund is agreed, we return the M-Pesa payment to the number that paid it, normally within 7 days.</p>'
            .'<h2>3. Mistaken payments</h2><p>If you paid the wrong amount or paid twice, contact us with the M-Pesa receipt and we will correct it.</p>'
            .'<h2>4. Contact</h2>'.$contact,
    ],
    'acceptable-use-policy' => [
        'title' => 'Acceptable Use Policy',
        'description' => 'What hosts may and may not do with TenaFi.',
        'content' => '<h2>1. Messaging</h2><ul><li>Only message guests and customers who agreed to it; offers only to those who opted in.</li><li>Never remove or hide the opt-out, and respect STOP replies.</li><li>Follow WhatsApp Business, Safaricom and Communications Authority of Kenya rules; no spam, misleading offers or prohibited content.</li></ul>'
            .'<h2>2. WiFi</h2><ul><li>Do not use the guest network to monitor guests\' browsing.</li><li>Do not tamper with or move the TenaFi device without telling us.</li></ul>'
            .'<h2>3. Data</h2><ul><li>Do not export guest data to sell it or use it for anything other than your own business.</li><li>Keep your dashboard login private and remove staff who leave.</li></ul>'
            .'<h2>4. Enforcement</h2><p>We may pause campaigns or accounts that break this policy and will tell you why.</p>'
            .'<h2>5. Contact</h2>'.$contact,
    ],
    'data-processing-agreement' => [
        'title' => 'Data Processing Agreement',
        'description' => 'How TenaFi processes guest data for hosts.',
        'content' => '<h2>1. Roles</h2><p>For guest and customer data collected through a host\'s WiFi login, the host is the data controller and TenaFi is the data processor, under the Kenya Data Protection Act, 2019.</p>'
            .'<h2>2. What we process</h2><p>Names, phone numbers, emails, birthdays (optional), device addresses, visit times, consent records, orders and payments, and message engagement, to provide the WiFi login, messaging, review requests, reports and payments.</p>'
            .'<h2>3. Our commitments</h2><ul><li>Process data only on the host\'s instructions as set out in the Terms and the dashboard settings.</li><li>Keep it confidential and secure, and limit staff access.</li><li>Record consent wording and time for every WiFi guest, and honour opt-outs automatically.</li><li>Help the host answer data-subject requests.</li><li>Tell the host without undue delay, and within 72 hours, of a personal-data breach affecting their data.</li><li>Delete or return the data when the host\'s account ends, except where the law requires us to keep it.</li></ul>'
            .'<h2>4. Sub-processors</h2><p>Safaricom (M-Pesa), Africa\'s Talking (SMS), Meta (WhatsApp Business), Paystack (cards), our email and hosting providers, and the host\'s connected PMS. We will tell hosts before adding a new sub-processor.</p>'
            .'<h2>5. Transfers</h2><p>Where a sub-processor stores data outside Kenya, we rely on the safeguards the Act requires.</p>'
            .'<h2>6. Contact</h2>'.$contact,
    ],
];
