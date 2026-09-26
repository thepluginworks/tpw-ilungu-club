<?php
/**
 * Local-only fixture for lifecycle data-retention browser coverage.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "This fixture must run through WP-CLI.\n" );
	exit( 1 );
}

if ( 'true' !== getenv( 'ILUNGU_LIFECYCLE_FIXTURE_ENABLED' ) ) {
	fwrite( STDERR, "Set ILUNGU_LIFECYCLE_FIXTURE_ENABLED=true to use the lifecycle fixture.\n" );
	exit( 1 );
}

$action = getenv( 'ILUNGU_LIFECYCLE_FIXTURE_ACTION' );
$action = is_string( $action ) ? sanitize_key( $action ) : '';
if ( ! in_array( $action, array( 'ensure', 'read', 'set', 'cleanup' ), true ) ) {
	fwrite( STDERR, "Set ILUNGU_LIFECYCLE_FIXTURE_ACTION to ensure, read, set, or cleanup.\n" );
	exit( 1 );
}

$login    = getenv( 'ILUNGU_LIFECYCLE_LIMITED_USER' );
$password = getenv( 'ILUNGU_LIFECYCLE_LIMITED_PASSWORD' );
$login    = is_string( $login ) ? sanitize_user( $login, true ) : '';

if ( '' === $login || ! is_string( $password ) || '' === $password ) {
	fwrite( STDERR, "Lifecycle fixture credentials are required.\n" );
	exit( 1 );
}

if ( 'ensure' === $action ) {
	$user = get_user_by( 'login', $login );
	if ( ! $user ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => $password,
				'user_email'   => 'ilungu-playwright-lifecycle@example.test',
				'display_name' => 'iLungu Playwright Lifecycle Limited User',
				'role'         => 'subscriber',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			fwrite( STDERR, $user_id->get_error_message() . "\n" );
			exit( 1 );
		}
		$user = get_user_by( 'id', $user_id );
	}

	if ( ! $user instanceof WP_User ) {
		fwrite( STDERR, "Could not resolve lifecycle fixture user.\n" );
		exit( 1 );
	}

	wp_set_password( $password, (int) $user->ID );
	$user->set_role( 'subscriber' );
	$user->remove_cap( 'manage_options' );
}

if ( 'set' === $action ) {
	$value = getenv( 'ILUNGU_LIFECYCLE_FIXTURE_VALUE' );
	if ( '__DELETE__' === $value ) {
		delete_option( 'tpw_core_delete_data_on_uninstall' );
	} elseif ( is_string( $value ) ) {
		update_option( 'tpw_core_delete_data_on_uninstall', $value );
	}
}

if ( 'cleanup' === $action ) {
	$user = get_user_by( 'login', $login );
	if ( $user instanceof WP_User ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( (int) $user->ID );
	}
}

$stored_value = get_option( 'tpw_core_delete_data_on_uninstall', null );
echo wp_json_encode(
	array(
		'limited_user' => $login,
		'option_value' => is_string( $stored_value ) ? $stored_value : null,
		'fixture_user_exists' => get_user_by( 'login', $login ) instanceof WP_User,
	)
) . "\n";