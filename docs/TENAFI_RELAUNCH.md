# TenaFi relaunch: public pages on the existing stack

Glen's handoff (`tenafi-website-handoff.zip`, spec dated 2026-10-02) shipped static `index.html`, `hosts.html` and `business.html`.
We kept our Laravel + Inertia/React stack instead and rebuilt those pages as CMS-driven pages.
The copy, sources and sign-up questions are taken from his files. The design is built from our reusable section components.

## Pages

| URL | Sections (in order; `#anchors` match Glen's) |
| --- | --- |
| `/` | hero, path_cards `#choose`, comparison__problem `#problem`, how_it_works `#how`, cta_banner__founding |
| `/hosts` | hero, stats__problem `#h-problem`, features__outcomes, how_it_works `#h-how`, stats__commission, comparison__party `#h-party`, detailed_features `#h-product`, features__protect `#h-protect`, credibility, pricing `#h-pricing`, cta_banner__founding, faq, cta_banner__crosssell, signup `#join` |
| `/business` | hero, features__why, comparison__qr `#b-qr`, features__industries, how_it_works `#b-how`, detailed_features `#b-product`, stats__reviews `#b-reviews`, pricing `#b-pricing`, cta_banner__founding, faq, cta_banner__crosssell, signup `#signup` |
| `/privacy`, `/terms` | Admin → Policies, in the same layout |
| Site-wide (not routable) | seo (defaults + og:image), header (logo, default nav), footer, feature_status |

Everything is editable in **Admin → Public Pages**, with one tab per page. The defaults live in `database/blueprints/public_pages.php`.

### Section types (all reusable on any page)

| Type | What it is |
| --- | --- |
| `hero` | Badge, headline, subtitle, optional body and trust note, two CTAs |
| `path_cards` | Audience chooser cards (tracked clicks) |
| `stats` | Headline numbers with sources, optional illustration and caption |
| `comparison` | Side-by-side panels with stat, points and text; one can be highlighted |
| `features` | Icon card grid with optional step labels and per-card "Coming soon" |
| `how_it_works` | Step cards |
| `detailed_features` | Image + text blocks (product mockups) |
| `credibility` | Stay Awhile story and stats |
| `pricing` | Plan cards; plan buttons link to `#join?plan=<id>` |
| `cta_banner` | Founding 20 and cross-links between audiences |
| `faq` | Question and answer accordion |
| `signup` | The CMS-defined sign-up form |

To use one type twice on a page, add a variant suffix (`stats__problem`, `stats__commission`).

### Navigation

Every page shows the same three header links (Site-wide → Header): Short-term rentals, Business owners and How it works.
Hovering over or keyboard-focusing an audience link opens a **megamenu** of that page's sections.

The megamenu is built from the sections themselves: any section with an `anchor` and a `menu_label` becomes an item.
Its description is the optional `menu_description`, falling back to the section title.
Renaming, hiding or reordering a section updates the menu automatically, so there is no separate menu to maintain.

### Editing conventions

- **Multi-line fields** (comparison points, plan features, next steps) hold one item per line.
  End a line with `[[feature_key]]` to show a "Coming soon" badge until that feature is live.
- **Feature rows and steps** also take a `feature` key for the same badge.
- **Select options** are `value|Label`, e.g. `10-49|10 to 49`, or just `Label`.

## Sign-up (matches `SIGNUP-FIELDS.md`)

- Both forms post to `POST /api/signups` with `type` (`host` or `business`) and the spec's camelCase keys.
  Host fields: `firstName`, `lastName`, `phone`, `email`, `units`, `area`, `platforms[]`, `superhost`, `isp`, `plan`.
  Business fields add `role`, `businessName`, `businessType`, `locations`, `customersPerDay` and `googleProfile`
  (and have no `units`, `platforms` or `superhost`).
  The form also sends `estimatedPriceKES`, `consentText`, `submittedAt` and `source`.
  Only the "Live" fields are included; the "Proposed" ones are left out until Glen confirms them.
- **Steps**: 1 "Your details", 2 "Your rentals" / "Your business", then a confirmation step.
  The confirmation shows a summary, the estimated price and the next steps.
  It only appears after the server has saved the record. If saving fails, the form stays on step 2 and shows Glen's error message.
- **Phone** is required and shown with a fixed +254 prefix. It is stored in E.164 (`+254712345678`);
  formats like `0712…`, `712…`, `254…` and `+254 …` are all accepted.
  **Email is optional.** A repeat application from the same number updates the earlier record instead of duplicating it.
- **Plans**: the plan cards take their prices from the page's pricing section.
  Hosts get the live multi-unit discount (10–49 units = 20% off, 50+ = 30% off; editable under signup → discounts).
  `#join?plan=growth` pre-selects a plan.
- **Storage**: answers with a dedicated column go there: name, phone, email, business name, `area` → `location`, and units.
  Everything else goes to `registrations.answers` (JSON).
  The consent wording is stored from the CMS, never taken from the client, along with the timestamp and IP (Kenya Data Protection Act, 2019).
- **Alerts**:
  - Email to Settings → "Sign-up alert emails". This is pre-filled with glen@tena.host, as the spec asks.
  - An optional JSON webhook, which you can point at Zapier, Make or Twilio to reach WhatsApp.
  - An admin dashboard notification.
  - The applicant confirmation email, when they gave an email address.
- **Existing waitlist**: those rows are already in the same table as `type = host`.

## Feature status (checked against the code on Oct 7)

Glen's pages badge occupancy alerts, outage alerts and PMS sync as "Coming soon". His README asks us to remove the badge
from anything that already works, and to badge anything else on the pages that isn't built yet.

| Feature | Status | Evidence |
| --- | --- | --- |
| WiFi login guest capture | live | `WifiPortalController`, `GuestCaptureService`. First name and WhatsApp number required, email optional; consent wording and timestamp stored per guest; optional marketing opt-in; one-tap reconnect for returning devices |
| WhatsApp and SMS messaging | live once configured | `App\Services\Messaging\Messenger` with Africa's Talking SMS and the WhatsApp Cloud API. Campaigns can be Email, WhatsApp or SMS; WhatsApp falls back to SMS. Needs the `.env` keys below |
| Thank-you and review requests | live | `ReviewRequestService`, `reviews:send` (hourly). Per property: Google review link, on/off, delay and message (Properties → Edit). Each guest is asked once after check-out or their last WiFi visit; `/r/{token}` counts clicks |
| Billing in KES (Basic, Starter, Growth) | live | `config/billing.php`, `PlanPricing`. Per-unit price, 20%/30% multi-unit discounts, extra devices, quarterly 5% off, yearly 2 months free. M-Pesa STK first, card via Paystack in KES. The server always computes the amount, and each payment extends the plan by its cycle |
| Sign-up → account onboarding | live | Admin → Sign-ups → "Create account" (`SignupConversionService`). Creates the host account (email optional; phone-only users sign in with their number), a first property, and the plan and units they picked, then sends a 7-day signed invite by email and/or WhatsApp. Run it again to re-send the invite |
| Business owners in the dashboard | live | `users.account_type` (`host` or `business`), set from the sign-up type or Admin → Users → "Business owner". Same dashboard, with "customers" and "locations" wording (`Components/Dashboard/Terms.jsx`) and a visit-based review message. Admin → Hosts shows type and plan |
| Guest homepage (house guide, local tips) | live | `GuestPortalController`, `Guest/Guidebook` |
| PMS / channel manager sync | **live (badge removed)** | Beds24, Cloudbeds and Hostaway drivers, `SyncPmsGuests`, PMS webhook. Confirm it works in production. |
| Paid extras by M-Pesa | coming soon | M-Pesa is used for host billing only; guest orders don't take payment |
| Monthly report | **live** | `MonthlyReportService` (`reports:monthly`, 1st of the month at 08:00 Nairobi): new and returning guests, campaign messages, review requests and opens. Sent by email plus a WhatsApp summary to Starter and Growth (to everyone while billing is off). Direct bookings get added once Tena Direct exists |
| Occupancy alerts | **live** | `PropertyMonitorService` (`alerts:check`, every 5 min): more distinct guests in 12 hours than the property's limit alerts the host on the dashboard and WhatsApp/SMS, at most once a day. Rental hosts only |
| Outage alerts | **live** | `alerts:check`: AP status from the UniFi controller (`stat/device`), else last seen. Offline more than 10 min alerts the host once, then a recovery notice |
| Business customer homepage | coming soon | not built (the portal is property-centric) |
| Tena Direct page | coming soon | not built |
| Free / VIP WiFi tiers | coming soon | not built |

Flip any of these under Site-wide → Feature status, and every badge on every page updates.

## Glen's go-live checklist

1. Pages at `/`, `/hosts`, `/business`, with 301s from old URLs to `/hosts`: **done** (`config/public_pages.php`)
2. Live features / "Coming soon" badges: **done** (table above)
3. One endpoint for both forms, with the confirmation only shown after a successful save, and consent wording and timestamp stored: **done**
4. Notify Glen on every new sign-up: **done** by email; WhatsApp works through the webhook setting.
5. Existing waitlist migrated as `type = host`: **done** (same table)
6. LOGIN → `/login`: **done**
7. og:image and twitter tags: **done**. Upload the new TenaFi logo under Site-wide → Header (logo) and Site-wide → SEO (og_image).
8. Analytics: **done**.
   Events: `path_card_hosts`, `path_card_business`, `join_click`, `signup_step_1`, `signup_step_2`, `signup_submit`, `signup_success`.
   They are counted daily in `analytics` and pushed to `window.dataLayer` for GA4/GTM.
   Admin → Overview → Signups charts a 30-day funnel per audience from these events: path card clicks, Join, step 1 and step 2 done, then sign-ups saved, accounts created and paying (`FunnelReport`). It also charts monthly sign-ups by type. Active plans are under Revenue.
9. Privacy Policy and Terms linked in the footers: **done** (`/privacy`, `/terms`)

## Differences from the static handoff

- Glen's HTML mockups (the WiFi login card, the guest homepage, WhatsApp and report "phones", the occupancy calendar)
  are image slots in the CMS here: detailed_features images, and stats__problem → "Illustration".
  Export them from his HTML or design files and upload them. Keep the "Illustration only" caption.
- The old homepage's partners carousel and ROI calculator aren't in Glen's design.
  They are switched off, not deleted (Admin → Public Pages → Short-term rental operators).
- The brand font (Inter) and colours (ink #1E1E1E, muted #5B6170) follow his brand reference.

## Still needed before launch

- Billing switches on automatically once M-Pesa (`MPESA_CONSUMER_KEY`) or Paystack keys are set (Admin → Settings → billing "auto"). Prices live in `config/billing.php`; keep them in step with the CMS pricing sections. The Paystack account must accept KES.

- Add the scheduler cron on the server: `* * * * * php artisan schedule:run`. Review requests depend on it.

- Messaging credentials in production `.env`:
  - `SMS_DRIVER=africastalking`, `AFRICASTALKING_USERNAME`, `AFRICASTALKING_API_KEY`, and an approved sender ID in `AFRICASTALKING_FROM`
  - `WHATSAPP_DRIVER=whatsapp_cloud`, `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`
  - `WHATSAPP_TEMPLATE`: a Meta-approved template whose body is just `{{1}}`. Business-started WhatsApp messages need one.
- Campaigns only reach WiFi guests who ticked "Send me offers". Guests added by hand or by PMS sync have no consent record and are included, so the host is responsible for those.

- The app now uses a TenaFi wordmark (`public/brand/`, built from Glen's yellow logo pill) via `App\Support\Brand`. Admin → Settings → site name and logo override it everywhere: dashboard, auth pages, guest portal, captive portal, emails and favicon. Upload the official logo there when it's final, plus the og:image and the product mockups above.
- The seeded Privacy, Terms and DPA documents still say "Tena Host" and use tena.host addresses. Replace them in Admin → Policies with the TenaFi versions.
- Publish the Privacy and Terms policies in Admin → Policies. Unpublished ones return 404.
- Confirm PMS sync works in production, or set it back to "coming soon".
- `public/index.php` points at the production `tena-core` layout. Confirm it matches the tena-fi.com host.
