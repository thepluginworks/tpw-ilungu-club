import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, rmdirSync, unlinkSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';

test('Club Local translation domains and locale switching', () => {
	const baseURL = process.env.ILUNGU_BASE_URL || 'https://ilungu-club.local';
	const wpPath = process.env.ILUNGU_WP_PATH;
	const localShell = process.env.ILUNGU_LOCAL_SHELL;
	const adminUser = process.env.ILUNGU_ADMIN_USER;
	if (new URL(baseURL).origin !== 'https://ilungu-club.local' || !wpPath || !localShell || !adminUser) {
		throw new Error('BLOCKED: Club Local URL, WP-CLI environment and configured administrator are required.');
	}

	const fixtureDir = mkdtempSync(join(__dirname, '.translation-fixture-'));
	const pluginsDir = join(fixtureDir, 'plugins');
	const fixtures = [
		['tpw-ilungu-club-zz_ZZ.l10n.php', "<?php return ['messages' => ['Manage Members' => 'Canonical admin', 'My Profile' => 'Canonical profile']];"],
		['tpw-core-zz_ZZ.l10n.php', "<?php return ['messages' => ['Manage Members' => 'Legacy admin', 'My Profile' => 'Legacy profile']];"],
		['tpw-core-zz_ZY.l10n.php', "<?php return ['messages' => ['Manage Members' => 'Legacy admin', 'My Profile' => 'Legacy profile']];"],
	] as const;
	try {
		mkdirSync(pluginsDir);
		for (const [name, content] of fixtures) writeFileSync(join(pluginsDir, name), content, { flag: 'wx' });
		const preload = resolve(__dirname, '../php/translation-local-preload.php');
		const checks = resolve(__dirname, '../php/translation-local-check.php');
		const command = `source <(awk '/^[[:space:]]*exec[[:space:]].*\\$SHELL([[:space:]]|$)/ { exit } { print }' "$1") >/dev/null 2>&1; wp --path="$2" --require="$3" --user="$5" --url=https://ilungu-club.local eval-file "$4"`;
		const output = execFileSync('zsh', ['-c', command, '--', localShell, wpPath, preload, checks, adminUser], {
			encoding: 'utf8',
			env: { ...process.env, ILUNGU_BASE_URL: 'https://ilungu-club.local', TPW_TRANSLATION_TEST_DIR: fixtureDir },
		});
		console.log(output);
		for (const label of ['canonical precedence', 'legacy fallback', 'source fallback']) {
			for (const check of ['locale switch', 'administrator string', 'member profile label', 'legacy consumer domain', 'locale restore']) {
				expect(output).toContain(`PASS: ${label} ${check}`);
			}
		}
		expect(output).toContain('PASS: translation-related PHP warnings');
	} finally {
		for (const [name] of fixtures) {
			const file = join(pluginsDir, name);
			if (existsSync(file)) unlinkSync(file);
		}
		if (existsSync(pluginsDir)) rmdirSync(pluginsDir);
		if (existsSync(fixtureDir)) rmdirSync(fixtureDir);
	}
	expect(existsSync(fixtureDir), 'synthetic catalogues must be removed').toBe(false);
});
