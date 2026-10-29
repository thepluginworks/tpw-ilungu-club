<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );

$test_posts = array(
	100 => (object) array(
		'ID'        => 100,
		'post_type' => 'tpw_event',
	),
	101 => (object) array(
		'ID'        => 101,
		'post_type' => 'page',
	),
);

function absint( $value ) {
	return abs( (int) $value );
}

function get_post( $post_id ) {
	global $test_posts;

	return isset( $test_posts[ $post_id ] ) ? $test_posts[ $post_id ] : null;
}

class TPW_Menu_Event_Post_Payload_Contract_Test_DB {
	public $prefix = 'wp_';
	public $event_ids = array( 50 );
	public $menu_ids = array( 70 );
	public $menu = array(
		'id'                => 70,
		'name'              => 'Annual Dinner',
		'description'       => 'Three courses.',
		'number_of_courses' => 3,
		'price'             => '39.50',
	);
	public $courses = array(
		array( 'course_number' => 1, 'course_name' => 'Starter' ),
		array( 'course_number' => 2, 'course_name' => 'Main' ),
		array( 'course_number' => 3, 'course_name' => 'Dessert' ),
	);
	public $choices = array(
		array( 'id' => 301, 'course_number' => 1, 'label' => 'Soup', 'description' => 'Seasonal soup', 'extra_cost' => '0.00' ),
		array( 'id' => 302, 'course_number' => 1, 'label' => 'Prawn cocktail', 'description' => '', 'extra_cost' => '1.50' ),
		array( 'id' => 303, 'course_number' => 2, 'label' => 'Roast beef', 'description' => '', 'extra_cost' => '2.00' ),
		array( 'id' => 304, 'course_number' => 3, 'label' => 'Tart', 'description' => '', 'extra_cost' => '0.00' ),
	);

	public function prepare( $query, ...$arguments ) {
		foreach ( $arguments as $argument ) {
			$query = preg_replace( '/%d/', (string) (int) $argument, $query, 1 );
		}

		return $query;
	}

	public function get_col( $query ) {
		if ( false !== strpos( $query, 'FROM wp_tpw_events' ) ) {
			return $this->event_ids;
		}

		if ( false !== strpos( $query, 'FROM wp_tpw_event_menu_relationship' ) ) {
			return $this->menu_ids;
		}

		return array();
	}

	public function get_var( $query ) {
		if ( false !== strpos( $query, 'FROM wp_tpw_event_menu_relationship' ) ) {
			return empty( $this->menu_ids ) ? null : $this->menu_ids[0];
		}

		return null;
	}

	public function get_row( $query, $output ) {
		unset( $query, $output );

		return $this->menu;
	}

	public function get_results( $query, $output ) {
		unset( $output );

		if ( false !== strpos( $query, 'FROM wp_tpw_menu_courses' ) ) {
			return $this->courses;
		}

		if ( false !== strpos( $query, 'FROM wp_tpw_menu_choices' ) ) {
			return $this->choices;
		}

		return array();
	}
}

function menu_payload_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

function menu_payload_assert_null( $actual, $message ) {
	menu_payload_assert_same( null, $actual, $message );
}

global $wpdb;
$wpdb = new TPW_Menu_Event_Post_Payload_Contract_Test_DB();

require dirname( __DIR__, 2 ) . '/modules/menus/class-tpw-menus.php';

$payload = tpw_core_get_event_menu_payload_for_post( 100 );
menu_payload_assert_same( 100, $payload['event_post_id'], 'The payload must preserve the WordPress event post ID.' );
menu_payload_assert_same( 50, $payload['event_id'], 'The payload must resolve the iLungu Events domain event ID.' );
menu_payload_assert_same( 70, $payload['menu']['id'], 'The payload must return the linked Club menu.' );
menu_payload_assert_same( array( 1, 2, 3 ), array_keys( $payload['courses'] ), 'Courses must remain ordered by course number.' );
menu_payload_assert_same( array( 301, 302 ), array_column( $payload['courses'][1]['choices'], 'id' ), 'Dish choice IDs must be stable and ordered by their source IDs.' );
menu_payload_assert_same( 1.5, $payload['courses'][1]['choices'][1]['extra_cost'], 'Dish additional costs must remain floats.' );
menu_payload_assert_same( 2.0, $payload['courses'][2]['choices'][0]['extra_cost'], 'Additional costs must remain attached to their dish.' );

$legacy_payload = TPW_Menus::get_menu_payload( 50 );
menu_payload_assert_same( false, isset( $legacy_payload['courses'][1]['choices'][0]['id'] ), 'The existing menu payload shape must remain unchanged.' );
menu_payload_assert_same( $legacy_payload, tpw_core_get_menu_payload( 50 ), 'The existing global menu payload wrapper must remain unchanged.' );

menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 0 ), 'An invalid event post ID must return null.' );
menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 101 ), 'A non-event post must return null.' );

$wpdb->event_ids = array();
menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 100 ), 'A missing Events domain row must return null.' );
$wpdb->event_ids = array( 50 );

$wpdb->menu_ids = array();
menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 100 ), 'A missing or deleted menu relationship must return null.' );
$wpdb->menu_ids = array( 70, 71 );
menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 100 ), 'Ambiguous menu relationships must return null.' );
$wpdb->menu_ids = array( 70 );

$wpdb->menu = null;
menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 100 ), 'A deleted menu must return null.' );
$wpdb->menu = array(
	'id'                => 70,
	'name'              => 'Annual Dinner',
	'description'       => 'Three courses.',
	'number_of_courses' => 3,
	'price'             => '39.50',
);

$wpdb->courses = array(
	array( 'course_number' => 1, 'course_name' => 'Starter' ),
	array( 'course_number' => 3, 'course_name' => 'Dessert' ),
);
menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 100 ), 'A menu with missing courses must return null.' );
$wpdb->courses = array(
	array( 'course_number' => 1, 'course_name' => 'Starter' ),
	array( 'course_number' => 2, 'course_name' => 'Main' ),
	array( 'course_number' => 3, 'course_name' => 'Dessert' ),
);

$wpdb->choices = array(
	array( 'id' => 301, 'course_number' => 1, 'label' => 'Soup', 'description' => '', 'extra_cost' => '0.00' ),
	array( 'id' => 303, 'course_number' => 2, 'label' => 'Roast beef', 'description' => '', 'extra_cost' => '2.00' ),
);
menu_payload_assert_null( tpw_core_get_event_menu_payload_for_post( 100 ), 'A menu with an empty course must return null.' );

echo "menu event post payload contract tests passed\n";
