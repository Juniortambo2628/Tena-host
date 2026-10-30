## What changed
<!-- In plain language: what will people see or be able to do differently? Which pages? -->

## Why
<!-- The request or bug this answers. -->

## Screenshots
<!-- Before and after, on desktop and at phone width (390px). -->

## How to check it
<!-- Steps someone can follow on the live site after merge. -->

## Risk
<!-- Tick one. See "High-risk areas" in CLAUDE.md. -->
- [ ] Low: content, wording, layout or styling only
- [ ] Needs Kevin: touches payments, login/security, the database, the WiFi portal, emails, config, routes, packages or deployment

## Checks
- [ ] Started from the latest `master`; no other open pull request changes the same pages
- [ ] `vendor/bin/pint`, `php artisan test` and `npm run build` pass
- [ ] Works at phone width without sideways scrolling
- [ ] New features that change what hosts or guests see ship switched off behind an admin setting
