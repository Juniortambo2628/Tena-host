# TenaFi (Tena-host)

TenaFi is a guest relationship platform for short-term rentals, hotels and local
businesses in Africa: guests log in to a property's WiFi through a branded splash
page, and hosts use the dashboard to see guests, run WhatsApp/SMS/email campaigns,
sell extras (M-Pesa) and collect reviews. The public marketing site, the host and
admin dashboards, and the guest WiFi portal all live in this repository.

Two people work on it: **Kevin** (developer, owns the repository) and **Glen**
(founder and CEO, non-technical, works through Claude Code). Both use Claude Code
in this repository, so this file is the shared rulebook. When a request and this
file disagree, follow this file and say so.

## Merging is deploying

Every merge to `master` deploys to production automatically once CI passes
(`.github/workflows/deploy.yml`). There is no staging. So:

- Never push to `master`. Work on a branch and open a pull request.
- Use the branch your Claude Code session assigns (usually `claude/...`); if
  it assigns none, name it after the change (`glen/<short-topic>` for Glen's).
- One request per pull request. Small pull requests are reviewed and merged
  faster and are easy to undo.
- Never merge your own pull request from Glen's side; Kevin's side reviews and
  merges (see `.claude/skills/steward/SKILL.md`).

## Before you start

1. `git fetch origin && git checkout -b <branch> origin/master` — always start
   from the latest `master`.
2. Check open pull requests and recent commits that touch the same pages or
   files. If someone is already changing them, stop and say so, so the two
   changes can be coordinated instead of colliding.
3. Restate the request in plain language: what will change, what will stay the
   same, which pages are affected. Ask when something is unclear.

## Before you open a pull request

Run these and fix what fails (CI runs the same checks and blocks the merge):

```bash
composer install && npm ci          # first time only
vendor/bin/pint                     # formats PHP; CI fails on unformatted PHP
php artisan test                    # the Pest suite; all tests must pass
npm run build                       # must build (client and SSR)
```

- Add or update a Pest test in `tests/Feature/` when you change behaviour
  (what a page shows, what a form saves, who can see what).
- Look at the change in a browser on desktop and at phone width (390px), and put
  screenshots in the pull request.
- Fill in the pull request template, including the risk level.

## High-risk areas — mark the pull request "Needs Kevin"

Changes here are allowed, but say so before building, and Kevin approves them
before merge:

- Payments and billing: M-Pesa, Paystack/Stripe (Cashier), `app/Services/Billing`,
  `MpesaService`, `ExtrasPaymentService`, `SubscriptionService`.
- Login and security: `app/Http/Controllers/Auth`, 2FA, passkeys, middleware
  (`app/Http/Middleware`), who can see what (host/admin/staff scoping).
- The database: anything in `database/migrations`. Never edit an old migration;
  add a new one.
- The guest WiFi portal and splash pages: `resources/views/portal`,
  `WifiPortalController`, `app/Services/Unifi`. Guests see this before they are
  online, so it must stay self-contained (no CDN scripts or fonts).
- Emails and messaging: `resources/views/emails`, `resources/js/Emails`,
  `app/Services/Messaging`, campaign sending.
- Configuration and deployment: `config/`, `routes/`, `.github/`, `scripts/`,
  `composer.json` / `package.json` (new packages), `public/index.php`.
- Never put secrets in code or messages: no API keys, passwords, or values from
  `.env` / `.env.production`.

Content, wording, layout and styling of pages and dashboard screens
(`resources/js`, `resources/css`) are low risk.

## New features: ship them switched off

Ambitious ideas are welcome. Build a small first version, and when it changes what
hosts or guests see, put it behind an admin on/off setting so it can be tried
before it is turned on. The pattern already exists:
`Setting::getValue('mpesa_extras_enabled', true)` (see `app/Models/Setting.php`
and `Admin/Settings`). Start new switches **off**.

## How the frontend is built (reuse, don't reinvent)

Laravel 12 + Inertia 2 + React 18, Tailwind CSS v4, Vite. Pages live in
`resources/js/Pages`, shared pieces in `resources/js/Components`.

- **Layout:** every page under `Pages/Host`, `Admin`, `Staff` and `Profile` gets
  the persistent `DashboardLayout` automatically (`resources/js/app.jsx`). Do not
  wrap a page in a layout yourself. Public CMS pages use `PublicLayout`.
- **Page heading:** `DashboardHero` (or `PageShell`, which adds `<Head>`), with
  `breadcrumbs` and `actions`. Actions that navigate take `href` (rendered as a
  prefetching link), not `onClick={() => router.get(...)}`.
- **Lists:** `Components/Dashboard/DataTable` (server pagination, search,
  selection, card view on phones). `ResponsiveTable` only where its row-action
  menu and detail modal are needed.
- **Empty lists:** `Components/Dashboard/EmptyState` with a specific message
  ("No guests yet") and an action when there is one.
- **Confirmations:** `useConfirm()` from `@/hooks/useConfirm` and render
  `{ConfirmDialogEl}`. Never `window.confirm()`.
- **Dialogs:** `Components/Modal` (accessible base), `GlassModal` (titled),
  `Dashboard/TabbedModal` (forms with tabs).
- **Buttons and cards:** `Dashboard/PillButton`, `Dashboard/GlassCard`,
  `Dashboard/StatusBadge`; dashboard tab bars use `Dashboard/ViewSwitcher`.
- **Messages:** `notify.success/error` from `@/Components/Toast`.
- **Host vs business wording:** wrap user-facing words in `useTerms()` / `<T>`
  so business accounts read "Customers" instead of "Guests".
- **Phones:** every dashboard page must work at 390px wide without sideways
  scrolling; the sidebar becomes a drawer below 1024px.

## Styling rules

- Colours come from the theme in `resources/css/theme.css`: `primary` (brand
  yellow), `dark`, `muted`, `canvas`, ... Write `bg-primary`, `text-dark`,
  `border-primary/30`, never `bg-[#FFD300]`. JavaScript that needs a plain colour
  (charts) uses `BRAND_COLORS` from `resources/js/constants.js`.
- Nothing smaller than `text-xs` (12px).
- Component stylesheets sit next to their component and start with
  `@reference "<relative path>/css/theme.css";` so theme classes work in
  `@apply`. Class names follow the component (`data-table__card`).
- No Bootstrap: it was removed; don't add it or another CSS framework.

## Public website content

Most public page text, images, FAQs and plans are edited in the admin dashboard
(Public Pages), not in code. Change code only when the page's structure or a
new section is needed; the page blueprint is `app/Services/Cms/PageBlueprint.php`.
