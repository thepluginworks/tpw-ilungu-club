<?php

define( 'ABSPATH', __DIR__ . '/' );

$items = array(
	array(
		'key'            => 'flexigolf-fixtures',
		'provider'       => 'flexigolf',
		'title'          => 'Legacy Golf',
		'fallback_slug'  => 'golf-fixtures',
		'requires_login' => true,
	),
	array(
		'key'              => 'ilungu-golf-fixtures',
		'legacy_keys'      => array( 'flexigolf-fixtures' ),
		'provider'         => 'ilungu-golf',
		'legacy_providers' => array( 'flexigolf' ),
		'title'            => 'Canonical Golf',
		'fallback_slug'    => 'golf-fixtures',
		'requires_login'   => true,
	),
);

function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function sanitize_title( $value ) { return sanitize_key( $value ); }
function __( $value ) { return $value; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function site_url( $path = '' ) { return 'https://example.test' . $path; }
function apply_filters( $hook, $value ) { global $items; return 'tpw_core/member_menu_items' === $hook ? $items : $value; }
function add_action() {}
function add_filter() {}
function has_action() { return false; }
function did_action() { return 0; }
function assert_same( $expected, $actual, $label ) { if ( $expected !== $actual ) { fwrite( STDERR, $label . "\n" ); exit( 1 ); } }

require dirname( __DIR__, 2 ) . '/includes/tpw-core-settings.php';

$registered = tpw_core_get_member_menu_registered_items();
assert_same( array( 'ilungu-golf-fixtures' ), array_map( static function( $item ) { return $item['key']; }, $registered ), 'Canonical and legacy menu registrations must resolve to one item.' );
assert_same( 'Canonical Golf', $registered[0]['title'], 'The canonical menu registration must be primary.' );
assert_same( array( 'flexigolf-fixtures' ), $registered[0]['legacy_keys'], 'The canonical menu item must retain its legacy key for repair matching.' );

echo "member menu alias tests passed\n";