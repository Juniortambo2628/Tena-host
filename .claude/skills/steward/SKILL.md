---
name: steward
description: How pull requests to master are reviewed and merged in this repository, especially Glen's (glen/* branches). Use when reviewing, fixing or merging a pull request here.
---

# Reviewing and merging pull requests

Merging to `master` deploys to production (see CLAUDE.md, "Merging is
deploying"). Kevin owns the repository; Glen opens pull requests through Claude
Code and is not technical, so everything written on his pull requests is in
plain language.

## Review every pull request

1. Check out the head branch and read the whole diff against `master`.
2. Run the checks from CLAUDE.md: `vendor/bin/pint --test`, `php artisan test`,
   `npm run build`.
3. For anything users see, open the changed pages in a browser at desktop and
   390px width, and compare with the screenshots in the pull request.
4. Hold the diff to CLAUDE.md: shared components reused (DataTable, EmptyState,
   useConfirm, DashboardHero, ...), theme colours and nothing below `text-xs`,
   no layout wrapping, no secrets, new user-facing features behind an admin
   setting that starts off.
5. Look for regressions outside the stated change: other pages using an edited
   shared component, scoping (a host must only ever see their own data), and
   anything removed.
6. Check for other open pull requests touching the same files; the later one
   merges `master` in before merge.

## Decide

- **Low risk** — every changed file is outside the paths in
  `.github/CODEOWNERS`: merge when CI is green on the latest commit, the pull
  request is mergeable, and the review found nothing blocking.
- **Needs Kevin** — any changed file is under a CODEOWNERS path, or the change
  touches payments, login/security, scoping, the database, the WiFi portal,
  emails or deployment however it is filed: do not merge. Post the review, tell
  Kevin what it changes and what could break, and merge only after Kevin says
  so.
- **Not ready** — CI red, a merge conflict, a failing check above, or a
  regression: do not merge. Small mechanical fixes (formatting, a test the
  change broke, a missing import) may be pushed as new commits on the branch;
  anything that changes what the feature does goes back to Glen as a plain
  comment saying what to change and why.

Merge with a merge commit (the repository's convention). Never force-push or
rewrite Glen's branch.

## Talking to Glen

- Write review comments in plain language: what you saw, why it matters, what to
  change. No jargon without a one-line explanation.
- Ask for a split when a pull request mixes unrelated changes.
- After merging, say in one or two lines what is now live and where to see it.
