import { expect, test, type Page } from '@playwright/test';

const baseURL = process.env.ILUNGU_BASE_URL || 'https://ilungu-club.local';
const portalPath = process.env.ILUNGU_PORTAL_PATH || '/club-management/';
const adminUser = process.env.ILUNGU_ADMIN_USER;
const adminPassword = process.env.ILUNGU_ADMIN_PASSWORD;
const fixtureEnabled = process.env.ILUNGU_CLUB_CONTRIBUTIONS_FIXTURE === 'true';

function url(path: string, contribution = false): string {
	const target = new URL(path, `${baseURL.replace(/\/$/, '')}/`);
	if (contribution) {
		target.searchParams.set('tpw_club_playwright_contributions', '1');
	}
	return target.toString();
}

async function signInAsAdmin(page: Page): Promise<void> {
	await page.goto(url('/wp-login.php'), { waitUntil: 'domcontentloaded' });
	await page.getByLabel(/username or email address/i).fill(adminUser!);
	await page.getByLabel(/^password$/i).fill(adminPassword!);
	await page.getByRole('button', { name: /log in/i }).click();
	await page.waitForURL(/\/wp-admin\//);
}

async function enableContributions(page: Page): Promise<void> {
	await page.context().addCookies([
		{
			name: 'tpw_club_playwright_contributions',
			value: '1',
			url: baseURL,
		},
	]);
}

async function disableContributions(page: Page): Promise<void> {
	await page.context().clearCookies({ name: 'tpw_club_playwright_contributions' });
}

async function openPortal(page: Page, contribution = false, workspace = ''): Promise<void> {
	const target = new URL(url(portalPath, contribution));
	if (workspace) {
		target.searchParams.set('workspace', workspace);
	}
	await page.goto(target.toString(), { waitUntil: 'domcontentloaded' });
	await expect(page.locator('.ilungu-club-dashboard')).toBeVisible();
}

async function openDashboard(page: Page, contribution = false): Promise<void> {
	const response = await page.goto(url('/wp-admin/admin.php?page=ilungu-club-dashboard', contribution), { waitUntil: 'domcontentloaded' });
	expect(response?.ok(), 'iLungu Club wp-admin dashboard must load').toBeTruthy();
}

test.describe('Club administration contributions', () => {
	test.skip(!fixtureEnabled || !adminUser || !adminPassword, 'Set the Local-only fixture and Playwright credentials before running this spec.');

	test('baseline dashboard and workspace behavior remains unchanged without a contribution', async ({ page }) => {
		await signInAsAdmin(page);
		await disableContributions(page);
		await openPortal(page);
		await expect(page.getByRole('heading', { name: 'Manage Members', exact: true })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Quick Actions', exact: true })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Extend iLungu Club', exact: true })).toBeVisible();
		await expect(page.getByText('Synthetic Club Tool', { exact: true })).toHaveCount(0);
		await openPortal(page, false, 'settings');
		await expect(page.getByText('Synthetic Club Frontend Workspace', { exact: true })).toHaveCount(0);
		await openDashboard(page);
		await expect(page.getByRole('heading', { name: 'Members', exact: true })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Quick Actions', exact: true })).toBeVisible();
		await expect(page.getByText('Synthetic Club Tool', { exact: true })).toHaveCount(0);
	});

	test('emits canonical Club workspace URLs and keeps child navigation compact', async ({ page }) => {
		await signInAsAdmin(page);
		await disableContributions(page);
		await openPortal(page);

		await expect(page.locator('h1.wp-block-post-title')).toHaveCount(1);
		await expect(page.locator('h1.wp-block-post-title')).toBeHidden();
		await expect(page.locator('.ilungu-club-dashboard__portal--dashboard')).toBeVisible();
		await expect(page.getByText('iLungu Club navigation', { exact: true })).toHaveCount(0);
		await expect(page.locator('.ilungu-club-dashboard__portal a[href*="/flexiclub/"]')).toHaveCount(0);
		await expect(page.locator('.ilungu-club-dashboard--frontend')).toHaveCSS('max-width', 'none');
		const portalBounds = await page.locator('.ilungu-club-dashboard__portal').boundingBox();
		const viewport = page.viewportSize();
		expect(portalBounds).not.toBeNull();
		expect(viewport).not.toBeNull();
		expect(portalBounds!.x).toBeGreaterThan(0);
		expect(viewport!.width - portalBounds!.x - portalBounds!.width).toBeGreaterThan(0);
		await expect(page.locator('a[href*="/club-management/?workspace=system-pages"]').first()).toBeVisible();
		await expect(page.locator('a[href*="/club-management/?workspace=settings&settings-tab=payment-methods"]').first()).toBeVisible();
		await expect(page.locator('a[href*="/club-management/?workspace=settings"]').first()).toBeVisible();
		await expect(page.locator('a[href*="/club-management/?workspace=logs"]').first()).toBeVisible();

		await openPortal(page, false, 'settings');
		await expect(page.locator('h1.wp-block-post-title')).toHaveCount(1);
		await expect(page.locator('h1.wp-block-post-title')).toBeHidden();
		const navigation = page.locator('.ilungu-club-dashboard__portal-sidebar-shell');
		await expect(navigation).toBeVisible();
		await navigation.getByText('iLungu Club navigation', { exact: true }).click();
		await expect(navigation).toHaveAttribute('open', '');
		const inactiveWorkspace = navigation.getByRole('link', { name: 'Dashboard Home', exact: true });
		const activeWorkspace = navigation.getByRole('link', { name: 'Settings', exact: true });
		await expect(inactiveWorkspace).toHaveClass(/\bbutton\b/);
		await expect(activeWorkspace).toHaveClass(/\bbutton-primary\b/);
		expect(await activeWorkspace.evaluate((element) => getComputedStyle(element).boxShadow)).not.toBe(
			await inactiveWorkspace.evaluate((element) => getComputedStyle(element).boxShadow)
		);
		await expect(navigation.locator('.ilungu-club-dashboard__portal-brand-card')).toHaveCount(0);
		await expect(navigation.getByText('Getting Started', { exact: true })).toHaveCount(0);
		await expect(navigation.getByText('Club Overview', { exact: true })).toHaveCount(0);
		await expect(navigation.getByText('Quick Actions', { exact: true })).toHaveCount(0);
		await expect(navigation.getByText('On This Page', { exact: true })).toHaveCount(0);
	});

	test('does not suppress titles on unrelated WordPress pages', async ({ page }) => {
		await signInAsAdmin(page);
		await page.goto(url('/'), { waitUntil: 'domcontentloaded' });
		await expect(page.locator('h1.wp-block-heading').filter({ hasText: /^Blog$/ })).toBeVisible();
	});

	test('uses one Lodge Meetings navigation action for the contribution RSVP destination', async ({ page }) => {
		await signInAsAdmin(page);
		await enableContributions(page);
		await openPortal(page, true, 'settings');
		const navigation = page.locator('.ilungu-club-dashboard__portal-sidebar-shell');
		await navigation.getByText('iLungu Club navigation', { exact: true }).click();

		const lodgeLink = navigation.getByRole('link', { name: 'Lodge Meetings', exact: true });
		await expect(lodgeLink).toHaveCount(1);
		await expect(lodgeLink).toHaveAttribute('href', /\/rsvp_submissions\/$/);
		await expect(navigation.getByRole('link', { name: /Lodge Meetings/i })).toHaveCount(1);
		await expect(navigation.getByRole('link', { name: /ilungu-lodge-meetings/i })).toHaveCount(0);
	});

	test('renders authorized Overview contributions with isolated contexts and deterministic duplicate handling', async ({ page }) => {
		await signInAsAdmin(page);
		await enableContributions(page);
		await openPortal(page, true);
		await expect(page.getByRole('heading', { name: 'Synthetic Club Tool', exact: true })).toHaveCount(1);
		await expect(page.getByRole('link', { name: 'Manage Synthetic Tool', exact: true })).toBeVisible();
		await expect(page.getByRole('link', { name: 'Synthetic Settings', exact: true })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Manage Members', exact: true })).toBeVisible();
		await expect(page.getByRole('heading', { name: 'Synthetic Frontend Only', exact: true })).toHaveCount(1);
		await expect(page.getByText('Synthetic Admin Only', { exact: true })).toHaveCount(0);
		await expect(page.getByRole('heading', { name: 'Synthetic Duplicate First', exact: true })).toHaveCount(1);
		await expect(page.getByText('Synthetic Duplicate Second', { exact: true })).toHaveCount(0);
		await expect(page.getByRole('heading', { name: 'Synthetic Canonical Alias', exact: true })).toHaveCount(1);
		await expect(page.getByText('Synthetic Legacy Alias', { exact: true })).toHaveCount(0);
		await expect(page.getByRole('heading', { name: 'Synthetic Valid After Malformed', exact: true })).toHaveCount(1);
		await expect(page.getByText('Malformed Synthetic Contribution', { exact: true })).toHaveCount(0);
		await expect(page.getByText('Unsupported Context Contribution', { exact: true })).toHaveCount(0);
		await expect(page.getByText('Unauthorized Synthetic Contribution', { exact: true })).toHaveCount(0);
		await expect(page.getByRole('link', { name: 'Manage Synthetic Tool', exact: true })).toHaveAttribute(
			'href',
			/\/club-management\/\?workspace=synthetic-club&view=settings&tab=rsvp/
		);
		await expect(page.getByRole('link', { name: 'Manage Lodge Meetings', exact: true })).toHaveAttribute(
			'href',
			/\/rsvp_submissions\/$/
		);

		await openDashboard(page, true);
		await expect(page.getByRole('heading', { name: 'Synthetic Club Tool', exact: true })).toHaveCount(1);
		await expect(page.getByRole('heading', { name: 'Synthetic Admin Only', exact: true })).toHaveCount(1);
		await expect(page.getByRole('heading', { name: 'Synthetic Canonical Alias', exact: true })).toHaveCount(1);
		await expect(page.getByText('Synthetic Legacy Alias', { exact: true })).toHaveCount(0);
		await expect(page.getByText('Synthetic Frontend Only', { exact: true })).toHaveCount(0);
	});

	test('augments an active catalogue card without creating a duplicate', async ({ page }) => {
		await signInAsAdmin(page);
		await enableContributions(page);
		await openPortal(page, true);
		const eventCards = page.locator('.tpw-flexiclub-dashboard__extend-card').filter({ has: page.getByRole('heading', { name: 'iLungu Events', exact: true }) });
		await expect(eventCards).toHaveCount(1);
		await expect(eventCards.getByRole('link', { name: 'Manage Synthetic Events', exact: true })).toBeVisible();
		await expect(eventCards.getByRole('link', { name: 'Synthetic Event Settings', exact: true })).toBeVisible();
		await disableContributions(page);
		await openPortal(page);
		await expect(page.getByRole('link', { name: 'Manage Synthetic Events', exact: true })).toHaveCount(0);
	});

	test('renders authorized frontend and wp-admin workspaces without a new top-level menu', async ({ page }) => {
		await signInAsAdmin(page);
		await enableContributions(page);
		await openPortal(page, true, 'synthetic-club');
		await expect(page.getByRole('heading', { name: 'Synthetic Club Frontend Workspace', exact: true })).toBeVisible();

		await openDashboard(page, true);
		const submenu = page.locator('#toplevel_page_ilungu-club-dashboard .wp-submenu a').filter({ hasText: /^Synthetic Club Workspace$/ });
		await expect(submenu).toHaveCount(1);
		await expect(page.locator('#adminmenu > li > a .wp-menu-name').filter({ hasText: /^Synthetic Club Workspace$/ })).toHaveCount(0);
		const href = await submenu.getAttribute('href');
		expect(href).toBeTruthy();
		const response = await page.goto(new URL(href!, page.url()).toString(), { waitUntil: 'domcontentloaded' });
		expect(response?.ok(), 'Synthetic wp-admin workspace must load').toBeTruthy();
		await expect(page.getByRole('heading', { name: 'Synthetic Club Admin Workspace', exact: true })).toBeVisible();
	});

	test('redirects a legacy Club dashboard bookmark to the canonical route', async ({ page }) => {
		await signInAsAdmin(page);
		await page.goto(url('/wp-admin/admin.php?page=tpw-flexiclub-dashboard&workspace=logs'), { waitUntil: 'domcontentloaded' });
		await expect(page).toHaveURL(/page=ilungu-club-dashboard/);
		await expect(page).toHaveURL(/workspace=logs/);
	});

	test('does not expose authorized contributions to logged-out visitors', async ({ page }) => {
		await enableContributions(page);
		await openPortal(page, true);
		await expect(page.getByText('Synthetic Club Tool', { exact: true })).toHaveCount(0);
		await expect(page.getByText('Synthetic Club Workspace', { exact: true })).toHaveCount(0);
		await disableContributions(page);
	});
});