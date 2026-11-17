import { expect, test, type Locator, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { join } from 'node:path';

const baseURL = process.env.ILUNGU_BASE_URL || 'https://ilungu-club.local';
const adminUser = process.env.ILUNGU_ADMIN_USER;
const adminPassword = process.env.ILUNGU_ADMIN_PASSWORD;
const portalPath = process.env.ILUNGU_PORTAL_PATH || '/club-management/';
const sortablePath = '/wp-content/plugins/tpw-ilungu-club/assets/third-party/sortablejs/Sortable.js';
const chartPath = '/wp-content/plugins/tpw-ilungu-club/assets/third-party/chartjs/chart.umd.js';
const themePath = '/wp-content/plugins/tpw-ilungu-club/assets/third-party/jquery-ui/jquery-ui.css';

type SortableWindow = Window & { Sortable?: { version: string; get: (element: Element) => unknown } };
type ChartWindow = Window & { Chart?: { version: string; getChart: (canvas: HTMLCanvasElement) => unknown } };

type FieldSnapshot = {
	missing: string[];
	ordered: boolean;
	rows: Array<Record<string, string | null>>;
	checksums: Record<string, string | null>;
};

function fieldSnapshot(): FieldSnapshot {
	const wpPath = process.env.ILUNGU_WP_PATH;
	const localShell = process.env.ILUNGU_LOCAL_SHELL;
	if (!wpPath || !localShell) throw new Error('BLOCKED: ILUNGU_WP_PATH and ILUNGU_LOCAL_SHELL are required for the read-only field-settings safety preflight.');
	const php = `$class = new ReflectionClass("TPW_Member_Fields"); $obj = $class->newInstanceWithoutConstructor(); $fields = $class->getMethod("get_core_fields")->invoke($obj); global $wpdb; $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tpw_field_settings ORDER BY field_key", ARRAY_A); if ($wpdb->last_error) { throw new RuntimeException($wpdb->last_error); } $keyed = array_column($rows, null, "field_key"); $missing = array_diff(array_keys($fields), array_keys($keyed)); $ordered = !isset($keyed["title"], $keyed["dob"]) || (int)$keyed["dob"]["sort_order"] === (int)$keyed["title"]["sort_order"] + 1; $tables = $wpdb->get_results("SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'", ARRAY_N); if ($wpdb->last_error) { throw new RuntimeException($wpdb->last_error); } $checksums = array(); foreach ($tables as $table) { $name = $table[0]; $checksum = $wpdb->get_row("CHECKSUM TABLE " . chr(96) . str_replace(chr(96), chr(96) . chr(96), $name) . chr(96), ARRAY_N); if ($wpdb->last_error || !is_array($checksum) || null === $checksum[1]) { throw new RuntimeException("Checksum unavailable for " . $name); } $checksums[$name] = (string)$checksum[1]; } ksort($checksums); echo "TPW_FIELD_SNAPSHOT:" . wp_json_encode(array("missing" => array_values($missing), "ordered" => $ordered, "rows" => $rows, "checksums" => $checksums));`;
	const command = `source <(awk '/^[[:space:]]*exec[[:space:]].*\\$SHELL([[:space:]]|$)/ { exit } { print }' "$1") >/dev/null 2>&1; wp --path="$2" eval "$3"`;
	const output = execFileSync('zsh', ['-c', command, '--', localShell, wpPath, php], { encoding: 'utf8' });
	const marker = 'TPW_FIELD_SNAPSHOT:';
	const start = output.lastIndexOf(marker);
	if (start < 0) throw new Error('BLOCKED: read-only field-settings safety preflight returned no snapshot.');
	return JSON.parse(output.slice(start + marker.length).trim()) as FieldSnapshot;
}

function compareFieldSnapshots(before: FieldSnapshot, after: FieldSnapshot, allowDecoration: boolean): void {
	const previous = before.rows.filter(row => row.field_key !== 'decoration');
	const current = after.rows.filter(row => row.field_key !== 'decoration');
	expect(current, 'all existing field-setting rows must remain identical').toEqual(previous);
	const changedTables = Object.keys(after.checksums).filter(table => before.checksums[table] !== after.checksums[table]);
	expect(Object.keys(after.checksums), 'database table list must remain unchanged').toEqual(Object.keys(before.checksums));
	const decoration = after.rows.filter(row => row.field_key === 'decoration');
	if (allowDecoration) {
		expect(before.rows.filter(row => row.field_key === 'decoration')).toEqual([]);
		expect(decoration).toHaveLength(1);
		const maxOrder = Math.max(-1, ...before.rows.map(row => Number(row.sort_order)));
		expect(decoration[0]).toMatchObject({ field_key: 'decoration', is_enabled: '1', custom_label: 'Decoration', sort_order: String(maxOrder + 1), field_type: 'varchar(50)' });
		expect(changedTables, 'only field settings may change').toEqual([Object.keys(after.checksums).find(table => table.endsWith('tpw_field_settings'))]);
		console.log(`Authorized initialized setting: ${JSON.stringify(decoration[0])}`);
	} else {
		expect(decoration).toEqual(before.rows.filter(row => row.field_key === 'decoration'));
		expect(changedTables, 'no database tables may change').toEqual([]);
	}
}

async function login(page: Page): Promise<void> {
	if (!adminUser || !adminPassword) throw new Error('Local administrator credentials are required.');
	await page.goto(new URL('/wp-login.php', baseURL).toString());
	await page.getByLabel(/username or email address/i).fill(adminUser);
	await page.getByLabel(/^password$/i).fill(adminPassword);
	await page.getByRole('button', { name: /log in/i }).click();
	await page.waitForURL(/\/wp-admin\//);
	await page.route('**/*', async (route) => {
		if (route.request().method() !== 'GET' && route.request().method() !== 'HEAD') {
			await route.abort('blockedbyclient');
		} else {
			await route.continue();
		}
	});
}

function observe(page: Page) {
	const requests: string[] = [];
	const errors: string[] = [];
	const failedAssets: string[] = [];
	page.on('request', request => {
		if (request.resourceType() === 'script' || request.resourceType() === 'stylesheet') requests.push(request.url());
	});
	page.on('response', response => {
		const request = response.request();
		if ((request.resourceType() === 'script' || request.resourceType() === 'stylesheet') && response.status() >= 400) failedAssets.push(response.url());
	});
	page.on('pageerror', error => errors.push(error.message));
	page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
	return {
		checkAbsent(path: string) {
			expect(requests.filter(url => new URL(url).pathname === path), 'already-loaded library must not be fetched again').toEqual([]);
			expect(requests.filter(url => new URL(url).origin !== new URL(baseURL).origin && !/^https:\/\/(sandbox\.)?web\.squarecdn\.com\//i.test(url)), 'external script or stylesheet requests').toEqual([]);
			expect(failedAssets, 'failed script or stylesheet responses').toEqual([]);
			expect(errors, 'browser errors').toEqual([]);
		},
		check(path: string) {
			const expected = new URL(path, baseURL);
			const matches = requests.filter(url => new URL(url).pathname === expected.pathname);
			expect(matches, `one request for ${path}`).toHaveLength(1);
			expect(new URL(matches[0]).origin).toBe(expected.origin);
			expect(requests.filter(url => new URL(url).origin !== new URL(baseURL).origin && !/^https:\/\/(sandbox\.)?web\.squarecdn\.com\//i.test(url)), 'external script or stylesheet requests').toEqual([]);
			expect(failedAssets, 'failed script or stylesheet responses').toEqual([]);
			expect(errors, 'browser errors').toEqual([]);
			console.log(`Verified local asset: ${matches[0]}`);
		},
	};
}

async function membersPage(page: Page, action: string, tab?: string): Promise<void> {
	const bridge = await page.goto(new URL('/wp-admin/admin.php?page=ilungu-club-manage-members', baseURL).toString());
	expect(bridge?.ok(), 'Manage Members bridge must resolve').toBeTruthy();
	let target = new URL(page.url());
	if (target.pathname.includes('/wp-admin/')) {
		const href = await page.getByRole('link', { name: 'Open Manage Members', exact: true }).getAttribute('href');
		if (!href) throw new Error('BLOCKED: Manage Members frontend link is unavailable.');
		target = new URL(href, baseURL);
	}
	expect(target.origin).toBe(new URL(baseURL).origin);
	target.searchParams.set('action', action);
	if (tab) target.searchParams.set('tab', tab);
	const response = await page.goto(target.toString(), { waitUntil: 'load' });
	expect(response?.ok()).toBeTruthy();
}

async function assertSortable(page: Page, root: string): Promise<void> {
	await expect.poll(() => page.evaluate(selector => {
		const lib = (window as SortableWindow).Sortable;
		const element = document.querySelector(selector);
		return lib && element && lib.get(element) ? lib.version : null;
	}, root)).toBe('1.15.0');
}

async function dragWithoutSaving(page: Page, rows: Locator, handle: string, keys: (rows: Locator) => Promise<string[]>, verify: () => void): Promise<void> {
	expect(await rows.count(), 'at least two existing rows are required').toBeGreaterThanOrEqual(2);
	const before = await keys(rows);
	const source = rows.nth(0).locator(handle).first();
	const target = rows.nth(1).locator(handle).first();
	await source.scrollIntoViewIfNeeded();
	await target.scrollIntoViewIfNeeded();
	const from = await source.boundingBox();
	const to = await target.boundingBox();
	if (!from || !to) throw new Error('Drag handles are not visible.');
	await page.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
	await page.mouse.down();
	await page.mouse.move(to.x + to.width / 2, to.y + to.height - 2, { steps: 15 });
	await page.mouse.up();
	await expect.poll(() => keys(rows), { message: 'drag must reorder visible rows' }).not.toEqual(before);
	verify();
	await page.reload({ waitUntil: 'load' });
	await expect.poll(() => keys(rows), { message: 'reload must restore the unsaved order' }).toEqual(before);
}

const fieldKeys = (rows: Locator) => rows.evaluateAll(elements => elements.map(element => element.querySelector<HTMLInputElement>('input[name$="[key]"]')?.value || ''));
const signupKeys = (rows: Locator) => rows.evaluateAll(elements => elements.map(element => element.getAttribute('data-field-key') || ''));
const menuKeys = (rows: Locator) => rows.evaluateAll(elements => elements.map(element => element.getAttribute('data-id') || ''));

test.beforeEach(async ({ page }) => { await login(page); });

test('Member Fields loads local Sortable and reorders without saving', async ({ page }) => {
	if (new URL(baseURL).origin !== 'https://ilungu-club.local') throw new Error('BLOCKED: this scenario must run on Club Local.');
	const before = fieldSnapshot();
	const allowDecoration = process.env.ILUNGU_ALLOW_DECORATION_INIT === '1' && before.missing.length === 1 && before.missing[0] === 'decoration';
	if (!before.ordered || (before.missing.length && !allowDecoration)) throw new Error(`BLOCKED: loading Field Settings may change unauthorized rows (missing: ${before.missing.join(', ') || 'none'}; DOB order safe: ${before.ordered}).`);
	console.log(`Before: decoration rows=${before.rows.filter(row => row.field_key === 'decoration').length}; database tables=${Object.keys(before.checksums).length}`);
	const evidence = observe(page);
	await membersPage(page, 'field_settings');
	const initialized = fieldSnapshot();
	compareFieldSnapshots(before, initialized, allowDecoration);
	await assertSortable(page, '#sortable-fields');
	await dragWithoutSaving(page, page.locator('#sortable-fields > .tpw-table-row'), '.tpw-sort-handle', fieldKeys, () => evidence.check(sortablePath));
	compareFieldSnapshots(initialized, fieldSnapshot(), false);
});

test('Member Signups loads local Sortable and reorders without saving', async ({ page }) => {
	const evidence = observe(page);
	await membersPage(page, 'settings', 'signups');
	await assertSortable(page, '#tpw-signup-fields-sortable');
	await dragWithoutSaving(page, page.locator('#tpw-signup-fields-sortable > .tpw-table-row'), '.tpw-signups-sort-handle', signupKeys, () => evidence.check(sortablePath));
});

test('Menu Manager conditionally loads local Sortable and reorders without saving', async ({ page }) => {
	const evidence = observe(page);
	const target = new URL(portalPath, baseURL);
	target.searchParams.set('workspace', 'menu-management');
	const response = await page.goto(target.toString(), { waitUntil: 'load' });
	expect(response?.ok()).toBeTruthy();
	await assertSortable(page, '#tpw-mm-root');
	await dragWithoutSaving(page, page.locator('#tpw-mm-root > .tpw-mm-item'), ':scope > .tpw-mm-item__row .tpw-mm-handle', menuKeys, () => evidence.check(sortablePath));
});

test('Menu Manager reuses an existing Sortable without a second load', async ({ page }) => {
	await page.addInitScript({ path: join(__dirname, '../../assets/third-party/sortablejs/Sortable.js') });
	const evidence = observe(page);
	const target = new URL(portalPath, baseURL);
	target.searchParams.set('workspace', 'menu-management');
	const response = await page.goto(target.toString(), { waitUntil: 'load' });
	expect(response?.ok()).toBeTruthy();
	await assertSortable(page, '#tpw-mm-root');
	evidence.checkAbsent(sortablePath);
});

test('active Feedback charts use local Chart.js', async ({ page }) => {
	const evidence = observe(page);
	const response = await page.goto(new URL('/wp-admin/tools.php?page=tpw-feedback-admin', baseURL).toString(), { waitUntil: 'load' });
	expect(response?.ok()).toBeTruthy();
	await expect(page.getByRole('heading', { name: 'RSVP Feedback Overview' })).toBeVisible();
	for (const id of ['easeChart', 'clarityChart', 'timeChart']) {
		const canvas = page.locator(`#${id}`);
		await expect(canvas).toBeVisible();
		await expect.poll(() => canvas.evaluate(element => {
			const chart = (window as ChartWindow).Chart;
			return chart?.getChart(element as HTMLCanvasElement) ? chart.version : null;
		})).toBe('4.4.9');
	}
	evidence.check(chartPath);
});

test('Members datepicker uses the local theme without saving', async ({ page }) => {
	const evidence = observe(page);
	await membersPage(page, 'add');
	const input = page.locator('input.tpw-date').first();
	await expect(input).toBeVisible();
	await input.click();
	await expect(page.locator('#ui-datepicker-div')).toBeVisible();
	await page.locator('#ui-datepicker-div td:not(.ui-datepicker-unselectable) a').first().click();
	await expect(input).not.toHaveValue('');
	evidence.check(themePath);
});
