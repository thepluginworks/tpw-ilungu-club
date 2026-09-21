import { expect, test, type Browser, type Page } from '@playwright/test';
import { loadMemberRoleFixture, type MemberRoleFixture } from './helpers/member-role-fixture';

const baseURL = process.env.ILUNGU_BASE_URL || 'http://ilungu-club.local';
const adminUser = process.env.ILUNGU_ADMIN_USER;
const adminPassword = process.env.ILUNGU_ADMIN_PASSWORD;
const memberUser = process.env.ILUNGU_MEMBER_USER;
const memberPassword = process.env.ILUNGU_MEMBER_PASSWORD;
const fixtureEnabled = process.env.ILUNGU_ROLE_FIXTURE_ENABLED === 'true';

function url(path: string): string {
	return new URL(path, `${baseURL.replace(/\/$/, '')}/`).toString();
}

async function signIn(page: Page, username: string, password: string): Promise<void> {
	await page.goto(url('/wp-login.php'), { waitUntil: 'domcontentloaded' });
	await page.getByLabel(/username or email address/i).fill(username);
	await page.getByLabel(/^password$/i).fill(password);
	await page.getByRole('button', { name: /log in/i }).click();
	await page.waitForURL(/\/(?:wp-admin\/|member-login\/|club-management\/|noticeboard\/)/);
}

async function openMemberEditor(page: Page, fixture: MemberRoleFixture): Promise<void> {
	const response = await page.goto(url(`/manage-members/?action=edit_form&id=${fixture.member_id}`), { waitUntil: 'domcontentloaded' });
	expect(response?.ok(), 'Fixture member editor must load').toBeTruthy();
	const memberEditor = page.locator('.tpw-member-form > form');
	await expect(memberEditor.locator('input[name="member_id"]')).toHaveValue(String(fixture.member_id));
}

async function setMemberCheckbox(page: Page, fixture: MemberRoleFixture, field: 'is_treasurer' | 'is_noticeboard_admin', enabled: boolean): Promise<void> {
	await openMemberEditor(page, fixture);
	const memberEditor = page.locator('.tpw-member-form > form');
	const checkbox = memberEditor.locator(`#${field}`);
	if (enabled) {
		await checkbox.check();
	} else {
		await checkbox.uncheck();
	}
	await memberEditor.getByRole('button', { name: 'Save Changes', exact: true }).click();
	await page.waitForLoadState('domcontentloaded');
	await openMemberEditor(page, fixture);
	const reopenedMemberEditor = page.locator('.tpw-member-form > form');
	if (enabled) {
		await expect(reopenedMemberEditor.locator(`#${field}`)).toBeChecked();
	} else {
		await expect(reopenedMemberEditor.locator(`#${field}`)).not.toBeChecked();
	}
}

async function openNoticeboard(page: Page, fixture: MemberRoleFixture): Promise<void> {
	const response = await page.goto(url(fixture.noticeboard_path), { waitUntil: 'domcontentloaded' });
	expect(response?.ok(), 'Noticeboard page must load').toBeTruthy();
}

test.describe.serial('Member role permission contracts', () => {
	test.skip(!fixtureEnabled || !adminUser || !adminPassword || !memberUser || !memberPassword || !process.env.ILUNGU_WP_PATH, 'Set Local role-fixture, administrator, member, and WordPress path variables.');

	let fixture: MemberRoleFixture;

	test.beforeAll(() => {
		fixture = loadMemberRoleFixture('ensure');
	});

	test('Treasurer remains a factual office state independent from payment permission', async ({ page }) => {
		await signIn(page, adminUser!, adminPassword!);
		await setMemberCheckbox(page, fixture, 'is_treasurer', false);
		await setMemberCheckbox(page, fixture, 'is_treasurer', true);

		let runtime = loadMemberRoleFixture();
		expect(runtime.treasurer_checked).toBeTruthy();
		expect(runtime.treasurer_office).toBeTruthy();
		expect(runtime.admin_treasurer).toBeFalsy();
		expect(runtime.admin_payments_manage).toBeTruthy();
		expect(runtime.unlinked_treasurer).toBeFalsy();

		await setMemberCheckbox(page, fixture, 'is_treasurer', false);
		runtime = loadMemberRoleFixture();
		expect(runtime.treasurer_checked).toBeFalsy();
		expect(runtime.treasurer_office).toBeFalsy();
		expect(runtime.admin_payments_manage).toBeTruthy();
	});

	test('Noticeboard Admin matches the rendered notice-management workflow and loses access when removed', async ({ page, browser }) => {
		await signIn(page, adminUser!, adminPassword!);
		await setMemberCheckbox(page, fixture, 'is_noticeboard_admin', false);
		await setMemberCheckbox(page, fixture, 'is_noticeboard_admin', true);
		expect(loadMemberRoleFixture().notices_manage).toBeTruthy();

		const memberContext = await browser.newContext({ ignoreHTTPSErrors: true });
		const memberPage = await memberContext.newPage();
		await signIn(memberPage, memberUser!, memberPassword!);
		await openNoticeboard(memberPage, fixture);
		await expect(memberPage.getByRole('button', { name: 'Add New Notice', exact: true })).toBeVisible();
		const notice = memberPage.locator('.tpw-notice-card').filter({ hasText: fixture.notice_title });
		await expect(notice).toHaveCount(1);
		await notice.getByRole('button', { name: 'Edit', exact: true }).click();
		await memberPage.locator('textarea[name="excerpt"]').fill('Maintained Local permission fixture.');
		await memberPage.getByRole('button', { name: 'Save Notice', exact: true }).click();
		await memberPage.waitForLoadState('domcontentloaded');
		await expect(memberPage.locator('.tpw-notice-card').filter({ hasText: fixture.notice_title })).toHaveCount(1);
		await memberContext.close();

		await setMemberCheckbox(page, fixture, 'is_noticeboard_admin', false);
		const runtime = loadMemberRoleFixture();
		expect(runtime.noticeboard_checked).toBeFalsy();
		expect(runtime.notices_manage).toBeFalsy();
		expect(runtime.admin_notices_manage).toBeTruthy();

		const removedContext = await browser.newContext({ ignoreHTTPSErrors: true });
		const removedPage = await removedContext.newPage();
		await signIn(removedPage, memberUser!, memberPassword!);
		await openNoticeboard(removedPage, fixture);
		await expect(removedPage.getByRole('button', { name: 'Add New Notice', exact: true })).toHaveCount(0);
		await removedContext.close();

		await openNoticeboard(page, fixture);
		await expect(page.getByRole('button', { name: 'Add New Notice', exact: true })).toBeVisible();
	});
});