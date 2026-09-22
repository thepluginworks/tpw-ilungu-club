<?php

define( 'ABSPATH', __DIR__ . '/' );

$contributions = array(
	array(
		'key'        => 'tpw-flexiticket-admin',
		'contexts'   => array( 'admin' ),
		'capability' => 'manage_options',
		'overview_card' => array(
			'title'          => 'Legacy tickets',
			'primary_action' => array( 'label' => 'Manage', 'url' => 'https://example.test/legacy' ),
		),
	),
	array(
		'key'         => 'ilungu-tickets-admin',
		'legacy_keys' => array( 'tpw-flexiticket-admin' ),
		'contexts'    => array( 'admin' ),
		'capability'  => 'manage_options',
		'overview_card' => array(
			'title'          => 'Canonical tickets',
			'primary_action' => array( 'label' => 'Manage', 'url' => 'https://example.test/canonical' ),
		),
	),
	array(
		'key'        => 'unrelated',
		'contexts'   => array( 'admin' ),
		'capability' => 'manage_options',
		'overview_card' => array(
			'title'          => 'Unrelated',
			'primary_action' => array( 'label' => 'Open', 'url' => 'https://example.test/unrelated' ),
		),
	),
	array(
		'key'        => 'same-key-duplicate',
		'contexts'   => array( 'admin' ),
		'capability' => 'manage_options',
		'overview_card' => array(
			'title'          => 'First duplicate',
			'primary_action' => array( 'label' => 'Open', 'url' => 'https://example.test/first-duplicate' ),
		),
	),
	array(
		'key'        => 'same-key-duplicate',
		'contexts'   => array( 'admin' ),
		'capability' => 'manage_options',
		'overview_card' => array(
			'title'          => 'Second duplicate',
			'primary_action' => array( 'label' => 'Open', 'url' => 'https://example.test/second-duplicate' ),
		),
	),
);

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) );
}

function sanitize_text_field( $value ) {
	return (string) $value;
}

function sanitize_html_class( $value ) {
	return (string) $value;
}

function esc_url_raw( $value ) {
	return (string) $value;
}

function apply_filters( $hook, $value ) {
	global $contributions;
	return 'tpw_core_club_administration_contributions' === $hook ? $contributions : $value;
}

function current_user_can( $capability ) {
	return 'manage_options' === $capability;
}

function assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $label . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/includes/class-tpw-flexiclub-admin-menu.php';

assert_same( true, class_exists( 'iLungu_Club_Admin_Menu', false ), 'The canonical Club controller must load.' );
assert_same( true, class_exists( 'TPW_FlexiClub_Admin_Menu', false ), 'The legacy Club controller alias must remain available.' );

$method = new ReflectionMethod( 'iLungu_Club_Admin_Menu', 'get_club_administration_contributions' );
$resolved = $method->invoke( null, 'admin' );

assert_same( array( 'ilungu-tickets-admin', 'unrelated', 'same-key-duplicate' ), array_keys( $resolved ), 'Canonical and legacy contribution identities must resolve to one logical contribution.' );
assert_same( 'Canonical tickets', $resolved['ilungu-tickets-admin']['overview_card']['title'], 'The canonical contribution must be primary when both forms are present.' );
assert_same( 'Unrelated', $resolved['unrelated']['overview_card']['title'], 'Unrelated contribution keys must remain unaffected.' );
assert_same( 'First duplicate', $resolved['same-key-duplicate']['overview_card']['title'], 'The first valid identical contribution key must remain primary.' );

echo "club administration contribution alias tests passed\n";