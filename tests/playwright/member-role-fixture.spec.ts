import { expect, test } from '@playwright/test';
import { parseMemberRoleFixtureOutput, type MemberRoleFixture } from './helpers/member-role-fixture';

function validFixturePayload(): MemberRoleFixture {
	return {
		member_user_id: 1,
		member_id: 2,
		notice_id: 3,
		notice_title: 'Permission fixture',
		noticeboard_path: '/noticeboard/',
		treasurer_checked: false,
		noticeboard_checked: false,
		treasurer_office: false,
		payments_manage: true,
		notices_manage: false,
		admin_treasurer: false,
		admin_payments_manage: true,
		admin_notices_manage: true,
		unlinked_treasurer: false,
	};
}

test.describe('Member role fixture payload parser', () => {
	test('accepts a valid fixture payload', () => {
		const payload = validFixturePayload();

		expect(parseMemberRoleFixtureOutput(JSON.stringify(payload))).toEqual(payload);
	});

	test('accepts a valid fixture payload after PHP startup warnings', () => {
		const payload = validFixturePayload();

		expect(parseMemberRoleFixtureOutput(`Warning: PHP Startup issue\n${JSON.stringify(payload)}`)).toEqual(payload);
	});

	test('rejects output without a fixture payload', () => {
		expect(() => parseMemberRoleFixtureOutput('Warning: PHP Startup issue')).toThrow('did not return a JSON payload');
	});

	test('rejects malformed fixture JSON', () => {
		expect(() => parseMemberRoleFixtureOutput('{"member_id":}')).toThrow('returned malformed JSON');
	});

	test('rejects a payload with a missing required field', () => {
		const payload = validFixturePayload();
		delete (payload as Partial<MemberRoleFixture>).member_id;

		expect(() => parseMemberRoleFixtureOutput(JSON.stringify(payload))).toThrow('invalid "member_id" field');
	});

	test('rejects a payload with an incorrect field type', () => {
		const payload = { ...validFixturePayload(), notice_id: '3' };

		expect(() => parseMemberRoleFixtureOutput(JSON.stringify(payload))).toThrow('invalid "notice_id" field');
	});
});
