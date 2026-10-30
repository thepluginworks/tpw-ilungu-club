import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { defineConfig } from '@playwright/test';

const minimumRuntime = '1' === process.env.TPW_MINIMUM_RUNTIME;
const localEnvironmentFile = join(__dirname, minimumRuntime ? '.env.minimum.local' : '.env.local');

if (minimumRuntime && !existsSync(localEnvironmentFile)) {
	throw new Error('TPW_MINIMUM_RUNTIME=1 requires the ignored tests/playwright/.env.minimum.local configuration file.');
}

if (existsSync(localEnvironmentFile)) {
	for (const line of readFileSync(localEnvironmentFile, 'utf8').split(/\r?\n/)) {
		const trimmedLine = line.trim();
		if (!trimmedLine || trimmedLine.startsWith('#')) {
			continue;
		}

		const separator = trimmedLine.indexOf('=');
		if (separator === -1) {
			continue;
		}

		const name = trimmedLine.slice(0, separator).trim();
		const value = trimmedLine.slice(separator + 1).trim();
		if (name && process.env[name] === undefined) {
			process.env[name] = value;
		}
	}
}

if (minimumRuntime && process.env.ILUNGU_ADMIN_PASSWORD === undefined && process.env.TPW_MINIMUM_ADMIN_PASSWORD !== undefined) {
	process.env.ILUNGU_ADMIN_PASSWORD = process.env.TPW_MINIMUM_ADMIN_PASSWORD;
}

const baseURL = process.env.ILUNGU_BASE_URL || 'https://ilungu-club.local';

if (minimumRuntime && !process.env.ILUNGU_ADMIN_PASSWORD) {
	throw new Error('TPW_MINIMUM_RUNTIME=1 requires TPW_MINIMUM_ADMIN_PASSWORD in the local environment.');
}
const minimumBaseURL = 'https://tpw-minimum.local';

if (minimumRuntime && minimumBaseURL !== baseURL.replace(/\/$/, '')) {
	throw new Error(`TPW_MINIMUM_RUNTIME=1 must target ${minimumBaseURL}; received ${baseURL}.`);
}

const outputDir = minimumRuntime
	? join(__dirname, 'results', 'minimum-runtime')
	: join(__dirname, 'test-results');
const reporter = minimumRuntime
	? [['list'], ['html', { outputFolder: join(__dirname, 'reports', 'minimum-runtime'), open: 'never' }]]
	: [['list']];

export default defineConfig({
	testDir: __dirname,
	testMatch: [
		'smoke/ilungu-branding-smoke.spec.ts',
		'club-administration-contributions.spec.ts',
		'member-role-fixture.spec.ts',
		'member-role-permissions.spec.ts',
		'lifecycle-retention.spec.ts',
	],
	timeout: 45_000,
	retries: 1,
	use: {
		baseURL,
		ignoreHTTPSErrors: true,
		screenshot: 'only-on-failure',
		trace: 'on-first-retry',
	},
	outputDir,
	reporter,
	projects: [
		{ name: 'chromium', use: { browserName: 'chromium' } },
		{ name: 'firefox', use: { browserName: 'firefox' } },
		{ name: 'webkit', use: { browserName: 'webkit' } },
	],
});
