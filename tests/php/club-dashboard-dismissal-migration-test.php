<?php

define( 'ABSPATH', __DIR__ . '/' );

$user_meta = array();

function get_current_user_id() {
	return 42;
}

function get_user_meta( $user_id, $key ) {
	global $user_meta;
	return isset( $user_meta[ $user_id ][ $key ] ) ? $user_meta[ $user_id ][ $key ] : '';
}

function update_user_meta( $user_id, $key, $value ) {
	global $user_meta;
	$user_meta[ $user_id ][ $key ] = $value;
	return true;
}

function metadata_exists( $type, $user_id, $key ) {
	global $user_meta;
	return isset( $user_meta[ $user_id ] ) && array_key_exists( $key, $user_meta[ $user_id ] );
}

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/includes/class-tpw-flexiclub-admin-menu.php';

$method = new ReflectionMethod( 'iLungu_Club_Admin_Menu', 'is_dashboard_setup_banner_dismissed' );
$method->setAccessible( true );

$user_meta = array(
	42 => array(
		'tpw_flexiclub_dashboard_setup_dismissed' => '1',
	),
);
assert_same( true, $method->invoke( null ), 'Legacy-only dismissal state must remain readable.' );
assert_same( '1', $user_meta[42]['ilungu_club_dashboard_setup_dismissed'], 'Legacy-only state must be written to the canonical key.' );
assert_same( '1', $user_meta[42]['ilungu_club_dashboard_setup_migrated'], 'Legacy migration must be marked complete.' );

$user_meta = array(
	42 => array(
		'ilungu_club_dashboard_setup_dismissed'     => '',
		'tpw_flexiclub_dashboard_setup_dismissed' => '1',
	),
);
assert_same( false, $method->invoke( null ), 'An explicit canonical value must win over stale legacy state.' );
assert_same( '', $user_meta[42]['ilungu_club_dashboard_setup_dismissed'], 'Canonical state must not be overwritten by legacy state.' );

$user_meta = array(
	42 => array(
		'ilungu_club_dashboard_setup_migrated'    => '1',
		'tpw_flexiclub_dashboard_setup_dismissed' => '1',
	),
);
assert_same( false, $method->invoke( null ), 'A completed canonical migration must prevent stale legacy resurrection.' );

$user_meta = array(
	42 => array(
		'tpw_flexiclub_dashboard_setup_dismissed' => '1',
	),
);
assert_same( true, $method->invoke( null ), 'The initial migration must apply legacy dismissal state.' );
$first_state = $user_meta;
assert_same( true, $method->invoke( null ), 'Repeated migration checks must preserve canonical dismissal state.' );
assert_same( $first_state, $user_meta, 'Repeated migration checks must be idempotent.' );

echo "club dashboard dismissal migration tests passed\n";