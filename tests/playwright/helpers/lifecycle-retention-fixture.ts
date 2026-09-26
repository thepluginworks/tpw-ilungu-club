import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { join } from 'node:path';

export type LifecycleRetentionFixture = {
	limited_user: string;
	option_value: string | null;
	fixture_user_exists: boolean;
};

const fixturePath = join(__dirname, '..', 'fixtures', 'lifecycle-retention.php');

function fixtureEnvironment(action: 'ensure' | 'read' | 'set' | 'cleanup', value?: string | null): NodeJS.ProcessEnv {
	const wpPath = process.env.ILUNGU_WP_PATH;
	const localShell = process.env.ILUNGU_LOCAL_SHELL;
	if (!wpPath || !existsSync(wpPath)) {
		throw new Error('Set ILUNGU_WP_PATH to the approved Local WordPress root for lifecycle tests.');
	}
	if (!localShell || !existsSync(localShell)) {
		throw new Error('Set ILUNGU_LOCAL_SHELL to the matching Local site shell for lifecycle tests.');
	}

	return {
		...process.env,
		ILUNGU_LIFECYCLE_FIXTURE_ACTION: action,
		...(action === 'set' ? { ILUNGU_LIFECYCLE_FIXTURE_VALUE: value === null ? '__DELETE__' : value ?? '' } : {}),
	};
}

export function loadLifecycleRetentionFixture(action: 'ensure' | 'read' | 'set' | 'cleanup' = 'read', value?: string | null): LifecycleRetentionFixture {
	const localWpCommand = [
		`source <(awk '/^[[:space:]]*exec[[:space:]].*\\$SHELL([[:space:]]|$)/ { exit } { print }' "$1") >/dev/null 2>&1`,
		'wp --path="$2" eval-file "$3"',
	].join('; ');
	const output = execFileSync('zsh', ['-c', localWpCommand, '--', process.env.ILUNGU_LOCAL_SHELL!, process.env.ILUNGU_WP_PATH!, fixturePath], {
		encoding: 'utf8',
		env: fixtureEnvironment(action, value),
		stdio: ['ignore', 'pipe', 'pipe'],
	}).trim();

	return JSON.parse(output) as LifecycleRetentionFixture;
}