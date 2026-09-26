import { expect, test, type Browser, type Page } from '@playwright/test';
import { loadLifecycleRetentionFixture, type LifecycleRetentionFixture } from './helpers/lifecycle-retention-fixture';

const baseURL = process.env.ILUNGU_BASE_URL || 'https://ilungu-club.local';
const portalPath = process.env.ILUNGU_PORTAL_PATH || '/club-management/';
const adminUser = process.env.ILUNGU_ADMIN_USER;
const adminPassword = process.env.ILUNGU_ADMIN_PASSWORD;
const limitedUser = process.env.ILUNGU_LIFECYCLE_LIMITED_USER;
const limitedPassword = process.env.ILUNGU_LIFECYCLE_LIMITED_PASSWORD;
const fixtureEnabled = process.env.ILUNGU_LIFECYCLE_FIXTURE_ENABLED === 'true';

function url(path: string): string {
	return new URL(path, `${baseURL.replace(/\/$/, '')}/`).toString();
}

function lifecycleForm(page: Page) {
	return page.locator('form').filter({ has: page.locator('input[name="tpw_core_delete_data_on_uninstall"]') });
}

async function signIn(page: Page, username: string, password: string): Promise<void> {
	await page.goto(url('/wp-login.php'), { waitUntil: 'domcontentloaded' });
	await page.getByLabel(/username or email address/i).fill(username);
	await page.getByLabel(/^password$/i).fill(password);
	await page.getByRole('button', { name: /log in/i }).click();
	await page.waitForURL(/\/(?:wp-admin\/|club-management\/|member-login\/)/);
}

async function openBackendLifecycle(page: Page): Promise<void> {
	await page.goto(url('/wp-admin/admin.php?page=ilungu-club-settings&tab=lifecycle'), { waitUntil: 'domcontentloaded' });
	await expect(lifecycleForm(page)).toBeVisible();
	await expect(lifecycleForm(page).getByRole('heading', { name: 'Data Retention', exact: true })).toBeVisible();
}

async function openFrontendLifecycle(page: Page): Promise<void> {
	const target = new URL(url(portalPath));
	target.searchParams.set('workspace', 'settings');
	target.searchParams.set('settings-tab', 'lifecycle');
	await page.goto(target.toString(), { waitUntil: 'domcontentloaded' });
	await expect(lifecycleForm(page)).toBeVisible();
	await expect(lifecycleForm(page).getByRole('heading', { name: 'Data Retention', exact: true })).toBeVisible();
}

test.describe.serial('Lifecycle data retention setting', () => {
	test.skip(!fixtureEnabled || !adminUser || !adminPassword || !limitedUser || !limitedPassword || !process.env.ILUNGU_WP_PATH || !process.env.ILUNGU_LOCAL_SHELL, 'Set Local lifecycle fixture, administrator, limited-user, and Local shell variables.');

	let fixture: LifecycleRetentionFixture;
	test.beforeAll(() => {
		fixture = loadLifecycleRetentionFixture('ensure');
		loadLifecycleRetentionFixture('set', null);
	});

	test.afterAll(() => {
		loadLifecycleRetentionFixture('set', '0');
		const cleanup = loadLifecycleRetentionFixture('cleanup');
		expect(cleanup.fixture_user_exists).toBeFalsy();
	});

	test('administrator enables lifecycle cleanup from the frontend', async ({ page }) => {
		await signIn(page, adminUser!, adminPassword!);
		await openFrontendLifecycle(page);
		const form = lifecycleForm(page);
		const checkbox = form.locator('input[name="tpw_core_delete_data_on_uninstall"]');
		await expect(checkbox).not.toBeChecked();
		await expect(form.getByText('Persistent iLungu Club data is retained by default when the plugin is deactivated, updated, or uninstalled.', { exact: true })).toBeVisible();
		await expect(form.getByText('Delete all proven iLungu Club-owned data when the plugin is uninstalled', { exact: true })).toBeVisible();
		await expect(form.getByText(/including Club member records and other Club-owned business data\. Back up or export anything you may need before uninstalling\./i)).toBeVisible();
		await expect(form.getByText(/This cannot be undone\. WordPress users, uploads\/media, shared payment and email infrastructure, consumer or sibling-plugin data, provider-owned records, and ownership-ambiguous data are retained\./i)).toBeVisible();
		await checkbox.check();
		await form.getByRole('button', { name: 'Save Data Retention Setting', exact: true }).click();
		await expect(page.getByText('Settings saved.', { exact: true })).toBeVisible();
		expect(loadLifecycleRetentionFixture('read').option_value).toBe('1');
	});

	test('backend control reflects frontend state and disables it', async ({ page }) => {
		await signIn(page, adminUser!, adminPassword!);
		await openBackendLifecycle(page);
		const form = lifecycleForm(page);
		const checkbox = form.locator('input[name="tpw_core_delete_data_on_uninstall"]');
		await expect(checkbox).toBeChecked();
		await checkbox.uncheck();
		await form.getByRole('button', { name: 'Save Data Retention Setting', exact: true }).click();
		await expect(page.getByText('Settings saved.', { exact: true })).toBeVisible();
		expect(loadLifecycleRetentionFixture('read').option_value).toBe('0');
		await openFrontendLifecycle(page);
		await expect(lifecycleForm(page).locator('input[name="tpw_core_delete_data_on_uninstall"]')).not.toBeChecked();
	});

	test('limited user cannot see or forge lifecycle saves', async ({ browser }) => {
		const adminContext = await browser.newContext({ ignoreHTTPSErrors: true });
		const adminPage = await adminContext.newPage();
		await signIn(adminPage, adminUser!, adminPassword!);
		await openBackendLifecycle(adminPage);
		const adminNonce = await adminPage.locator('input[name="tpw_core_lifecycle_nonce"]').inputValue();

		const limitedContext = await browser.newContext({ ignoreHTTPSErrors: true });
		const limitedPage = await limitedContext.newPage();
		await signIn(limitedPage, limitedUser!, limitedPassword!);
		await limitedPage.goto(url('/wp-admin/admin.php?page=ilungu-club-settings&tab=lifecycle'), { waitUntil: 'domcontentloaded' });
		await expect(limitedPage.locator('input[name="tpw_core_delete_data_on_uninstall"]')).toHaveCount(0);

		const frontendTarget = new URL(url(portalPath));
		frontendTarget.searchParams.set('workspace', 'settings');
		frontendTarget.searchParams.set('settings-tab', 'lifecycle');
		await limitedPage.goto(frontendTarget.toString(), { waitUntil: 'domcontentloaded' });
		await expect(limitedPage.locator('input[name="tpw_core_delete_data_on_uninstall"]')).toHaveCount(0);

		for (const context of ['admin', 'frontend']) {
			const response = await limitedPage.request.post(url('/wp-admin/admin-post.php'), {
				form: {
					action: 'tpw_core_save_lifecycle',
					tpw_core_lifecycle_nonce: adminNonce,
					tpw_core_delete_data_on_uninstall: '1',
					tpw_settings_context: context,
				},
				maxRedirects: 0,
			});
			expect(await response.text()).toMatch(/Permission denied/i);
		}

		expect(loadLifecycleRetentionFixture('read').option_value).toBe('0');
		await limitedContext.close();
		await adminContext.close();
	});
});