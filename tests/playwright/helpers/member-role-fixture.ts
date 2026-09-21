import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { join } from 'node:path';

export type MemberRoleFixture = {
	member_user_id: number;
	member_id: number;
	notice_id: number;
	notice_title: string;
	noticeboard_path: string;
	treasurer_checked: boolean;
	noticeboard_checked: boolean;
	treasurer_office: boolean;
	payments_manage: boolean;
	notices_manage: boolean;
	admin_treasurer: boolean;
	admin_payments_manage: boolean;
	admin_notices_manage: boolean;
	unlinked_treasurer: boolean;
};

const fixturePath = join(__dirname, '..', 'fixtures', 'member-role-permissions.php');

function roleFixtureEnvironment(action: 'ensure' | 'read'): NodeJS.ProcessEnv {
	const wpPath = process.env.ILUNGU_WP_PATH;
	const localShell = process.env.ILUNGU_LOCAL_SHELL;
	if (!wpPath) {
		throw new Error('Set ILUNGU_WP_PATH to the Local WordPress root for member-role permission tests.');
	}
	if (!existsSync(wpPath)) {
		throw new Error(`Configured ILUNGU_WP_PATH does not exist: ${wpPath}`);
	}
	if (!localShell) {
		throw new Error('Set ILUNGU_LOCAL_SHELL to the Local Site Shell script for member-role permission tests.');
	}
	if (!existsSync(localShell)) {
		throw new Error(`Configured ILUNGU_LOCAL_SHELL does not exist: ${localShell}`);
	}

	return {
		...process.env,
		ILUNGU_ROLE_FIXTURE_ACTION: action,
	};
}

export function loadMemberRoleFixture(action: 'ensure' | 'read' = 'read'): MemberRoleFixture {
	const wpPath = process.env.ILUNGU_WP_PATH;
	const localShell = process.env.ILUNGU_LOCAL_SHELL;
	const localWpCommand = [
		`source <(awk '/^[[:space:]]*exec[[:space:]].*\\$SHELL([[:space:]]|$)/ { exit } { print }' "$1") >/dev/null 2>&1`,
		'wp --path="$2" eval-file "$3"',
	].join('; ');
	const output = execFileSync('zsh', ['-c', localWpCommand, '--', localShell!, wpPath!, fixturePath], {
		encoding: 'utf8',
		env: roleFixtureEnvironment(action),
		stdio: ['ignore', 'pipe', 'pipe'],
	}).trim();

	return JSON.parse(output) as MemberRoleFixture;
}