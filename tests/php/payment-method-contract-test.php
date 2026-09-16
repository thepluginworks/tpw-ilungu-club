<?php

$options = array(
	'tpw_bacs_account_name'   => 'iLungu Club',
	'tpw_bacs_account_number' => '12345678',
	'tpw_bacs_sort_code'      => '12-34-56',
	'tpw_bacs_enabled'        => '1',
);

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) );
}

function get_option( $key, $default = false ) {
	global $options;

	return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

class TPW_Payment_Method_Contract_Test_DB {
	public $prefix = 'wp_';
	public $rows = array(
		'bacs'   => array( 'active' => 1, 'name' => 'Bank Transfer' ),
		'cheque' => array( 'active' => 1, 'name' => 'Cheque' ),
	);

	public function prepare( $query, ...$arguments ) {
		foreach ( $arguments as $argument ) {
			$query = preg_replace( '/%s/', "'" . $argument . "'", $query, 1 );
		}

		return $query;
	}

	public function get_var( $query ) {
		if ( false !== strpos( $query, 'SHOW TABLES LIKE' ) ) {
			return $this->prefix . 'tpw_payment_methods';
		}

		if ( preg_match( "/SHOW COLUMNS.*LIKE '([^']+)'/", $query, $matches ) ) {
			return in_array( $matches[1], array( 'slug', 'active', 'name', 'sort_order' ), true ) ? $matches[1] : null;
		}

		if ( preg_match( "/SELECT active.*WHERE slug = '([^']+)'/", $query, $matches ) ) {
			return isset( $this->rows[ $matches[1] ] ) ? $this->rows[ $matches[1] ]['active'] : null;
		}

		return null;
	}

	public function get_results( $query ) {
		$results = array();

		foreach ( $this->rows as $slug => $row ) {
			if ( 1 === $row['active'] ) {
				$results[] = (object) array(
					'slug' => $slug,
					'name' => $row['name'],
				);
			}
		}

		return $results;
	}
}

function assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $label . "\n" );
		exit( 1 );
	}
}

global $wpdb;
$wpdb = new TPW_Payment_Method_Contract_Test_DB();

require dirname( __DIR__, 2 ) . '/modules/payments/class-tpw-payments-manager.php';

assert_same( true, TPW_Payments_Manager::is_method_usable( 'bacs' ), 'Configured active BACS must be usable.' );
assert_same( array( 'bacs' ), array_map( static function( $method ) { return $method->slug; }, TPW_Payments_Manager::get_usable_methods() ), 'Configured active BACS must be returned by the usable-method list.' );

$wpdb->rows['bacs']['active'] = 0;
assert_same( false, TPW_Payments_Manager::is_method_usable( 'bacs' ), 'An inactive BACS row must override the legacy active option.' );
assert_same( array(), TPW_Payments_Manager::get_usable_methods(), 'Inactive BACS must not be returned by the usable-method list.' );
assert_same( false, TPW_Payments_Manager::is_method_usable( 'cheque' ), 'An unconfigured released method must not be usable.' );
assert_same( false, TPW_Payments_Manager::is_method_usable( 'sumup' ), 'Dormant SumUp must not be usable.' );
assert_same( false, TPW_Payments_Manager::is_method_usable( 'woocommerce' ), 'Dormant WooCommerce must not be usable.' );

$wpdb->rows['bacs']['active'] = 1;
assert_same( true, TPW_Payments_Manager::is_method_usable( 'bacs' ), 'Existing configured active BACS must remain usable.' );

$payment_db_source = file_get_contents( dirname( __DIR__, 2 ) . '/modules/payments/class-tpw-payment-db.php' );
assert_same( true, false !== strpos( $payment_db_source, 'active tinyint(1) DEFAULT 0' ), 'Fresh payment method columns must default inactive.' );
assert_same( true, false !== strpos( $payment_db_source, "'active' => 0," ), 'Fresh payment method rows must default inactive.' );

echo "payment method contract tests passed\n";