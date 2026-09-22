# iLungu Club Playwright Tests

The maintained iLungu Club browser suite is
`tests/playwright/smoke/ilungu-branding-smoke.spec.ts`.

The canonical Local target for every maintained Club browser and fixture test is
`https://ilungu-club.local`. Set `ILUNGU_BASE_URL` to that HTTPS origin and keep
`ILUNGU_WP_PATH` and `ILUNGU_LOCAL_SHELL` pointed at the matching Local site.
Do not substitute a different Local site unless a tracked cross-plugin test
explicitly documents it.

The contribution contract suite is
`tests/playwright/club-administration-contributions.spec.ts`. It requires the
Local-only WordPress test bootstrap to define `ILUNGU_CLUB_PLAYWRIGHT_TESTING`
and require `tests/playwright/fixtures/club-administration-contributions.php`.
The fixture is request-scoped: it registers synthetic contributions only when
the spec supplies its test query flag, creates no persistent state, and does
not load outside that explicit Local test bootstrap.

Run the current smoke suite with:

```sh
npm run test:ilungu-smoke
```

`tests/playwright/playwright.config.ts` sets `testDir` to `tests/playwright` and
matches the smoke and contribution-contract specs. This keeps the maintained
existing-install, authenticated-admin, portal/workspace, and fresh-install
fixture checks separate from historical diagnostics.

## Member-role permission fixture

`member-role-permissions.spec.ts` is a Local-only, rerun-safe role contract suite.
It creates or reuses one fictitious linked member and one idempotent Noticeboard
record through `fixtures/member-role-permissions.php`, then performs Treasurer
and Noticeboard Admin transitions through the rendered member editor.

Set these local-only variables in `.env.local`:

```sh
ILUNGU_MEMBER_USER=ilungu-playwright-permission-member
ILUNGU_MEMBER_PASSWORD=local-only-password
ILUNGU_WP_PATH=/path/to/local/wordpress/root
ILUNGU_LOCAL_SHELL=/path/to/local/site-shell.sh
ILUNGU_ROLE_FIXTURE_ENABLED=true
```

Run it with:

```sh
npm run test:ilungu-member-role-permissions
```

The fixture is WP-CLI-only and must run against the approved Local site. It does
not reset office flags directly: Playwright restores Treasurer and Noticeboard
Admin to unchecked through the rendered Club member editor at the end of each
scenario. The fixture command is run through `ILUNGU_LOCAL_SHELL`, which loads
the site's Local runtime before invoking WP-CLI. This stateful suite runs in
Chromium only so its single reusable fixture cannot be mutated concurrently by
multiple browser projects.

Use `.env.example` as the placeholder reference. Put local site credentials only
in `.env.local`; it is ignored. Generated `test-results`, Playwright reports, and
blob reports are also ignored. The full `tests/playwright` directory is excluded
from public release packages.

## Historical diagnostics

The original May 2026 FlexiClub dashboard and payment investigation scripts were
removed from the working tree because they contained hard-coded local login
details, targeted a retired local site, and provided no unique maintained
coverage. They remain available in Git history for forensic reference only; do
not restore or run them as regression tests.