<?php

define( 'ABSPATH', __DIR__ . '/' );

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) );
}

function apply_filters( $hook, $value ) {
	return $value;
}

class TPW_Payment_Source_Compatibility_Test_DB {
	public $prefix = 'wp_';
	public $last_query = '';

	public function prepare( $query, ...$arguments ) {
		$this->last_query = $query;
		return $query;
	}

	public function get_results( $query ) {
		return array(
			(object) array( 'plugin' => 'tpw-flexiticket', 'amount' => '10.00' ),
			(object) array( 'plugin' => 'ilungu-tickets', 'amount' => '20.00' ),
		);
	}
}

function assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $label . "\n" );
		exit( 1 );
	}
}

global $wpdb;
$wpdb = new TPW_Payment_Source_Compatibility_Test_DB();

require dirname( __DIR__, 2 ) . '/modules/payments/class-tpw-payment-source-registry.php';
require dirname( __DIR__, 2 ) . '/modules/payments/class-tpw-payment-logs-admin.php';

TPW_Payment_Source_Registry::register_source_aliases( 'ilungu-tickets', array( 'tpw-flexiticket' ) );

assert_same( 'ilungu-tickets', TPW_Payment_Source_Registry::normalize_source( 'tpw-flexiticket' ), 'Legacy source must normalize to the canonical logical source.' );
assert_same( 'ilungu-tickets', TPW_Payment_Source_Registry::normalize_source( 'ilungu-tickets' ), 'Canonical source must remain canonical.' );
assert_same( array( 'ilungu-tickets', 'tpw-flexiticket' ), TPW_Payment_Source_Registry::get_source_identities( 'tpw-flexiticket' ), 'Source reads must include canonical and legacy stored identities.' );

$rows = TPW_Payment_Logs_Admin::get_page_for_source( 'ilungu-tickets' );
assert_same( 2, count( $rows ), 'Historical and canonical payment-log rows must both remain queryable.' );
assert_same( array( 'ilungu-tickets', 'ilungu-tickets' ), array_map( static function( $row ) { return $row->plugin; }, $rows ), 'Payment-log reader must expose one logical source.' );
assert_same( 30.0, array_sum( array_map( static function( $row ) { return (float) $row->amount; }, $rows ) ), 'Canonical and legacy rows must contribute once each to one logical source total.' );

echo "payment source compatibility tests passed\n";