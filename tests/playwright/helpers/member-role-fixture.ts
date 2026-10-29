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

const numericFields: Array<keyof Pick<MemberRoleFixture, 'member_user_id' | 'member_id' | 'notice_id'>> = [
	'member_user_id',
	'member_id',
	'notice_id',
];

const stringFields: Array<keyof Pick<MemberRoleFixture, 'notice_title' | 'noticeboard_path'>> = [
	'notice_title',
	'noticeboard_path',
];

const booleanFields: Array<keyof Pick<
	MemberRoleFixture,
	| 'treasurer_checked'
	| 'noticeboard_checked'
	| 'treasurer_office'
	| 'payments_manage'
	| 'notices_manage'
	| 'admin_treasurer'
	| 'admin_payments_manage'
	| 'admin_notices_manage'
	| 'unlinked_treasurer'
>> = [
	'treasurer_checked',
	'noticeboard_checked',
	'treasurer_office',
	'payments_manage',
	'notices_manage',
	'admin_treasurer',
	'admin_payments_manage',
	'admin_notices_manage',
	'unlinked_treasurer',
];

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

export function parseMemberRoleFixtureOutput(output: string): MemberRoleFixture {
	const jsonStart = output.indexOf('{');
	const jsonEnd = output.lastIndexOf('}');

	if (jsonStart === -1 || jsonEnd === -1 || jsonEnd < jsonStart) {
		throw new Error('Member role fixture did not return a JSON payload.');
	}

	let payload: unknown;

	try {
		payload = JSON.parse(output.slice(jsonStart, jsonEnd + 1));
	} catch {
		throw new Error('Member role fixture returned malformed JSON.');
	}

	if (typeof payload !== 'object' || payload === null || Array.isArray(payload)) {
		throw new Error('Member role fixture JSON payload must be an object.');
	}

	for (const field of numericFields) {
		if (!Number.isInteger(payload[field]) || payload[field] <= 0) {
			throw new Error(`Member role fixture payload has an invalid "${field}" field; expected a positive integer.`);
		}
	}

	for (const field of stringFields) {
		if (typeof payload[field] !== 'string' || payload[field] === '') {
			throw new Error(`Member role fixture payload has an invalid "${field}" field; expected a non-empty string.`);
		}
	}

	for (const field of booleanFields) {
		if (typeof payload[field] !== 'boolean') {
			throw new Error(`Member role fixture payload has an invalid "${field}" field; expected a boolean.`);
		}
	}

	return payload as MemberRoleFixture;
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

	return parseMemberRoleFixtureOutput(output);
}