<?php

define( 'ABSPATH', __DIR__ . '/' );

function is_admin() {
	return true;
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) );
}

function wp_unslash( $value ) {
	return $value;
}

function sanitize_text_field( $value ) {
	return trim( $value );
}

function admin_url( $path ) {
	return 'https://example.test/wp-admin/' . $path;
}

function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args );
}

function wp_safe_redirect( $location ) {
	$expected = 'https://example.test/wp-admin/admin.php?page=ilungu-club-dashboard&workspace=logs';
	if ( $expected !== $location ) {
		fwrite( STDERR, "Unexpected legacy route destination: {$location}\n" );
		exit( 1 );
	}

	echo "club admin route redirect test passed\n";
}

function add_action() {}

function add_filter() {}

require dirname( __DIR__, 2 ) . '/includes/class-tpw-flexiclub-admin-menu.php';

$_GET = [
	'page'      => 'tpw-flexiclub-dashboard',
	'workspace' => 'logs',
];

iLungu_Club_Admin_Menu::redirect_legacy_admin_routes();

fwrite( STDERR, "Legacy route did not redirect.\n" );
exit( 1 );