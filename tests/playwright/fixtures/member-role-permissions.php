<?php
/**
 * Local-only WP-CLI fixture for maintained member-role permission coverage.
 *
 * Run through the Playwright helper only. It provisions one fictitious linked
 * member and one idempotent Noticeboard record; role transitions stay in the
 * rendered member editor exercised by Playwright.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "This fixture must run through WP-CLI.\n" );
	exit( 1 );
}

if ( 'true' !== getenv( 'ILUNGU_ROLE_FIXTURE_ENABLED' ) ) {
	fwrite( STDERR, "Set ILUNGU_ROLE_FIXTURE_ENABLED=true to use the Local role fixture.\n" );
	exit( 1 );
}

$action = getenv( 'ILUNGU_ROLE_FIXTURE_ACTION' );
$action = is_string( $action ) ? sanitize_key( $action ) : '';

if ( ! in_array( $action, array( 'ensure', 'read' ), true ) ) {
	fwrite( STDERR, "Set ILUNGU_ROLE_FIXTURE_ACTION to ensure or read.\n" );
	exit( 1 );
}

$member_login    = getenv( 'ILUNGU_MEMBER_USER' );
$member_password = getenv( 'ILUNGU_MEMBER_PASSWORD' );
$admin_login     = getenv( 'ILUNGU_ADMIN_USER' );
$member_login    = is_string( $member_login ) ? sanitize_user( $member_login, true ) : '';
$admin_login     = is_string( $admin_login ) ? sanitize_user( $admin_login, true ) : '';

if ( '' === $member_login || '' === $member_password || '' === $admin_login ) {
	fwrite( STDERR, "Member and administrator fixture credentials are required.\n" );
	exit( 1 );
}

$fixture_email = 'ilungu-playwright-permissions@example.test';
$fixture_title = 'iLungu Playwright Permission Fixture Notice';
$member_user  = get_user_by( 'login', $member_login );

if ( ! $member_user ) {
	$user_id = wp_insert_user(
		array(
			'user_login'   => $member_login,
			'user_pass'    => $member_password,
			'user_email'   => $fixture_email,
			'display_name' => 'iLungu Playwright Permission Member',
			'first_name'   => 'iLungu',
			'last_name'    => 'Playwright Permission Member',
			'role'         => 'subscriber',
		)
	);
	if ( is_wp_error( $user_id ) ) {
		fwrite( STDERR, $user_id->get_error_message() . "\n" );
		exit( 1 );
	}
	$member_user = get_user_by( 'id', $user_id );
} else {
	wp_set_password( $member_password, (int) $member_user->ID );
}

if ( ! $member_user instanceof WP_User ) {
	fwrite( STDERR, "Could not resolve the fixture WordPress user.\n" );
	exit( 1 );
}

if ( ! class_exists( 'TPW_Member_Access', false ) ) {
	require_once WP_PLUGIN_DIR . '/tpw-ilungu-club/modules/members/includes/class-tpw-member-access.php';
}

$member = TPW_Member_Access::get_member_by_user_id( (int) $member_user->ID );
if ( ! $member ) {
	$controller = new TPW_Member_Controller();
	$member_id  = $controller->add_member(
		array(
			'user_id'              => (int) $member_user->ID,
			'first_name'           => 'iLungu',
			'surname'              => 'Playwright Permission Member',
			'email'                => $fixture_email,
			'status'               => 'Active',
			'is_treasurer' => 0,
			'is_noticeboard_admin' => 0,
		)
	);
	if ( ! $member_id ) {
		fwrite( STDERR, "Could not create the fixture Club member.\n" );
		exit( 1 );
	}
	$member = $controller->get_member( $member_id );
}

if ( ! $member ) {
	fwrite( STDERR, "Could not resolve the fixture Club member.\n" );
	exit( 1 );
}

$notice = get_page_by_title( $fixture_title, OBJECT, 'tpw_notice' );
if ( ! $notice ) {
	$notice_id = wp_insert_post(
		array(
			'post_type'    => 'tpw_notice',
			'post_status'  => 'publish',
			'post_title'   => $fixture_title,
			'post_content' => 'Reusable Local fixture notice for iLungu Club permission tests.',
			'post_excerpt' => 'Reusable Local permission fixture.',
			'post_author'  => (int) $member_user->ID,
		),
		true
	);
	if ( is_wp_error( $notice_id ) ) {
		fwrite( STDERR, $notice_id->get_error_message() . "\n" );
		exit( 1 );
	}
	$notice = get_post( $notice_id );
}

$admin_user = get_user_by( 'login', $admin_login );
if ( ! $admin_user instanceof WP_User ) {
	fwrite( STDERR, "Could not resolve the configured fixture administrator.\n" );
	exit( 1 );
}


$unlinked_user_id = 99999999;
$payload          = array(
	'member_user_id'        => (int) $member_user->ID,
	'member_id'             => (int) $member->id,
	'notice_id'             => (int) $notice->ID,
	'notice_title'          => $fixture_title,
	'noticeboard_path'      => '/noticeboard/',
	'treasurer_checked'     => 1 === (int) $member->is_treasurer,
	'noticeboard_checked'   => 1 === (int) $member->is_noticeboard_admin,
	'treasurer_office'      => tpw_core_user_can( 'tpw_member_office_treasurer', (int) $member_user->ID ),
	'payments_manage'       => tpw_core_user_can( 'tpw_payments_manage', (int) $member_user->ID ),
	'notices_manage'        => tpw_core_user_can( 'tpw_notices_manage', (int) $member_user->ID ),
	'admin_treasurer'       => tpw_core_user_can( 'tpw_member_office_treasurer', (int) $admin_user->ID ),
	'admin_payments_manage' => tpw_core_user_can( 'tpw_payments_manage', (int) $admin_user->ID ),
	'admin_notices_manage' => tpw_core_user_can( 'tpw_notices_manage', (int) $admin_user->ID ),
	'unlinked_treasurer'    => tpw_core_user_can( 'tpw_member_office_treasurer', $unlinked_user_id ),
);

echo wp_json_encode( $payload ) . "\n";