# TenaFi relaunch: public pages on the existing stack

Glen's handoff (Oct 5) shipped static `index.html` / `hosts.html` / `business.html`.
We kept our Laravel + Inertia/React stack instead and rebuilt those pages as
CMS-driven pages, reusing the existing section components.

## Pages

| URL | CMS page | Sections (in order) |
| --- | --- | --- |
| `/` | Main landing page | seo, hero, path_cards (`#paths`), problem, how_it_works |
| `/hosts` | Short-term rental operators | seo, hero, problem (44% stat), how_it_works, detailed_features, credibility, partners, pricing, signup (`#join`), roi_calculator (off) |
| `/business` | Business owners | seo, hero, problem, how_it_works, detailed_features, pricing, signup (`#signup`) |
| `/privacy`, `/terms` | Admin → Policies | rendered in the same public layout |
| — | Site-wide (not routable) | seo defaults + og:image, header (logo, nav), footer, plans, feature_status |

Every section's text and images can be edited in **Admin → Public Pages**, with one tab per page.
The defaults live in `database/blueprints/public_pages.php`.

## How it fits together

- **DB**: `landing_pages` is new, and `landing_sections.page_id` is unique per `(page_id, section_key)`.
  Sign-ups reuse `registrations`, which gets new columns: `type` (host|business), `business_name`, `answers` (JSON),
  `consent_text`, `consented_at`, `consent_ip` and `source_page`. Existing waitlist rows become `type = host` automatically.
- **Blueprint sync** (`App\Services\Cms\PageBlueprint`): additive by default, so it never overwrites CMS edits.
  The one-off relaunch migration also rewrites the old homepage copy (now `/hosts`).
  It keeps admin-uploaded media but swaps the `/legacy/` placeholder images.
- **One renderer**: `Pages/Public/Page.jsx` maps `section_key` to a component, inside `Layouts/PublicLayout.jsx`.
- **Plans** are defined once (Site-wide → Plans) and shared by every pricing section.
- **"Coming soon" badges**: each feature row has a `feature` key, and its status is set in Site-wide → Feature status.
  Flipping one status updates every page.
- **Sign-up**: both forms post to `POST /api/signups`. The questions are defined in the CMS (`steps.{i}.fields`),
  and the server validates against that same definition (`SignupFormSchema`).
  The consent wording is stored from the CMS, never taken from the client.
  The confirmation message only shows after the server has saved the record.
- **Alerts** (`SignupAlertService`): an email to Settings → "Sign-up alert emails" (falls back to the support email),
  an optional JSON webhook (use Zapier, Make or Twilio to forward it to WhatsApp), an admin dashboard notification,
  and the applicant confirmation email.
- **Analytics**: `track()` pushes to `window.dataLayer` (GA4/GTM) and to `POST /api/track`, which keeps daily counters
  in `analytics` as `public.<event>:<page>`.
  Events tracked: `path_card_hosts`, `path_card_business`, `join_click`, `signup_step_N`, `signup_submit`, `signup_success`.
- **SEO**: `app.blade.php` renders the title, description, canonical, og:* and twitter:* tags server-side
  from each page's `seo` section, falling back to the site-wide `seo` section.
- **Redirects**: `config/public_pages.php` (301s for old URLs → `/hosts`).

## Feature status (checked against the code on Oct 7)

| Feature | Status | Evidence |
| --- | --- | --- |
| Guest homepage / house guide | live | `GuestPortalController`, `Guest/Guidebook` |
| PMS / channel manager sync | live | Beds24, Cloudbeds and Hostaway drivers, `SyncPmsGuests`, PMS webhook |
| M-Pesa extras on guest homepage | coming soon | M-Pesa is used for host billing only; guest orders don't take payment |
| Monthly report | coming soon | not built |
| Occupancy alerts | coming soon | only `occupancy_threshold` and dashboard rate exist; no alerting |
| Outage alerts | coming soon | not built |
| Business customer homepage | coming soon | not built (the portal is property-centric) |

## Glen's checklist

1. Pages at `/`, `/hosts`, `/business`, with 301s from old URLs: **done**
2. Confirm live features / "Coming soon" badges: **done** (table above; editable in CMS)
3. One sign-up endpoint with `type`; confirmation only after save; consent wording and timestamp stored: **done**
4. Email alert on every sign-up: **done**. WhatsApp goes through the webhook setting.
5. Existing waitlist moved into the sign-ups table as `type = host`: **done** (same table, new columns)
6. LOGIN → real login page: **done** (`/login`, editable in the header section)
7. og:image / social tags: **done**. Upload the new TenaFi logo under Site-wide → Header (logo) and Site-wide → SEO (og_image).
8. Analytics: **done** (first-party counters, plus dataLayer events once GA4/GTM is installed)
9. Privacy Policy and Terms pages linked in the footers: **done** (`/privacy`, `/terms`, from Admin → Policies)

## Still needed before launch

- **Copy check against Glen's zip.** The preview links and zip could not be opened from this environment,
  so the seeded copy and sign-up questions were written from his email. Diff them against `hosts.html`,
  `business.html` and `SIGNUP-FIELDS.md` and adjust in the CMS (no deploy needed).
- **Brand assets.** Most images in `/legacy/assets` still show the old "Tena" wordmark.
  Upload TenaFi versions in the CMS media slots.
- Set **Settings → Sign-up alert emails** (and the webhook, if WhatsApp alerts are wanted).
- Publish the privacy and terms policies (Admin → Policies). Unpublished ones return 404.
- `public/index.php` points at the production `tena-core` layout. Confirm this matches the `tena-fi.com` host.
