<?php

define( 'ABSPATH', __DIR__ . '/' );

$registered_actions = [];

function is_admin() {
	return false;
}

function add_action( $hook, $callback, $priority = 10 ) {
	global $registered_actions;
	$registered_actions[] = [ $hook, $callback, $priority ];
}

function add_filter() {}

function assert_true( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/includes/class-tpw-flexiclub-admin-menu.php';

iLungu_Club_Admin_Menu::init();

$redirect_registered = false;
foreach ( $registered_actions as $registration ) {
	if ( 'admin_init' === $registration[0] && [ 'iLungu_Club_Admin_Menu', 'redirect_legacy_admin_routes' ] === $registration[1] && 1 === $registration[2] ) {
		$redirect_registered = true;
	}
}

assert_true( $redirect_registered, 'Legacy Club route redirects must register on early admin_init even when bootstrap has no admin context.' );
echo "club admin route bootstrap test passed\n";