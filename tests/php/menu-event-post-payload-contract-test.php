<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'TPW_CORE_PATH', dirname( __DIR__, 2 ) . '/' );

$test_posts = array(
	100 => (object) array(
		'ID'        => 100,
		'post_type' => 'tpw_event',
	),
	102 => (object) array(
		'ID'        => 102,
		'post_type' => 'tpw_event',
	),
	103 => (object) array(
		'ID'        => 103,
		'post_type' => 'tpw_event',
	),
	104 => (object) array(
		'ID'        => 104,
		'post_type' => 'tpw_event',
	),
	105 => (object) array(
		'ID'        => 105,
		'post_type' => 'tpw_event',
	),
	106 => (object) array(
		'ID'        => 106,
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

function locate_template( $template ) {
	unset( $template );

	return '';
}

function __( $text, $domain = null ) {
	unset( $domain );

	return $text;
}

function esc_html__( $text, $domain = null ) {
	unset( $domain );

	return $text;
}

function esc_attr__( $text, $domain = null ) {
	unset( $domain );

	return $text;
}

function esc_html_e( $text, $domain = null ) {
	unset( $domain );

	echo $text;
}

function esc_attr_e( $text, $domain = null ) {
	unset( $domain );

	echo $text;
}

function esc_html( $text ) {
	return (string) $text;
}

function esc_attr( $text ) {
	return (string) $text;
}

function wp_kses_post( $text ) {
	return (string) $text;
}

function apply_filters( $hook, $value ) {
	unset( $hook );

	return $value;
}

function did_action( $hook ) {
	unset( $hook );

	return 0;
}

function add_action( $hook, $callback ) {
	unset( $hook, $callback );
}

function tpw_core_get_currency_symbol() {
	return '£';
}

function ilungu_tickets_is_event_ticketed( $post_id ) {
	return in_array( (int) $post_id, array( 100, 102, 103, 104, 105, 106 ), true );
}

class TPW_Menu_Event_Post_Payload_Contract_Test_DB {
	public $prefix = 'wp_';
	public $event_ids = array( 50 );
	public $event_post_ids = array( 50 => 100 );
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
		if ( false !== strpos( $query, 'SELECT post_id FROM wp_tpw_events WHERE event_id = 50' ) ) {
			return $this->event_post_ids[50];
		}

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

function menu_payload_assert_contains( $needle, $haystack, $message ) {
	if ( false === strpos( $haystack, $needle ) ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

function menu_payload_assert_not_contains( $needle, $haystack, $message ) {
	if ( false !== strpos( $haystack, $needle ) ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

function menu_payload_reset_rendered_events() {
	$rendered_event_ids = new ReflectionProperty( 'TPW_Menus', 'rendered_event_ids' );
	$rendered_event_ids->setValue( null, array() );
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

$tickets_context = new ReflectionMethod( 'TPW_Menus', 'get_tickets_menu_presentation' );
menu_payload_assert_same( 'none', $tickets_context->invoke( null, array( 'event_id' => 50, 'post_id' => 100 ) ), 'A Tickets event must fail closed when the Tickets presentation helper is unavailable.' );
menu_payload_assert_same( null, $tickets_context->invoke( null, array( 'event_id' => 50, 'post_id' => 999 ) ), 'A menu relationship alone must not select Tickets presentation.' );

eval(
	'function ilungu_tickets_get_event_menu_presentation( $post_id ) {
		global $test_tickets_menu_presentations;
		return isset( $test_tickets_menu_presentations[ $post_id ] ) ? $test_tickets_menu_presentations[ $post_id ] : null;
	}'
);
$test_tickets_menu_presentations = array(
	100 => 'selectable_courses',
	102 => 'fixed_menu',
	103 => 'none',
	104 => 'selectable_courses',
	105 => 'invalid',
	106 => 'selectable_courses',
);

menu_payload_assert_same( 'selectable_courses', $tickets_context->invoke( null, array( 'event_id' => 50, 'post_id' => 100 ) ), 'Tickets two- or three-course Dining must use selectable_courses.' );
menu_payload_assert_same( 'selectable_courses', $tickets_context->invoke( null, 50 ), 'The legacy Events domain ID must resolve to the Tickets event post ID.' );
menu_payload_assert_same( 'fixed_menu', $tickets_context->invoke( null, array( 'event_id' => 51, 'post_id' => 102 ) ), 'Tickets fixed-menu events must use fixed_menu.' );
menu_payload_assert_same( 'none', $tickets_context->invoke( null, array( 'event_id' => 52, 'post_id' => 103 ) ), 'Tickets none mode must not render a Dining trigger.' );
menu_payload_assert_same( 'selectable_courses', $tickets_context->invoke( null, array( 'event_id' => 53, 'post_id' => 104 ) ), 'Mixed Tickets types with Dining must use selectable_courses.' );
menu_payload_assert_same( 'none', $tickets_context->invoke( null, array( 'event_id' => 54, 'post_id' => 105 ) ), 'Invalid Tickets helper values must fail closed.' );

$event_id                     = 50;
$tpw_menu_modal_presentation  = 'tickets_selectable_courses';
ob_start();
require dirname( __DIR__, 2 ) . '/modules/menus/templates/menu-modal.php';
$tickets_modal = ob_get_clean();
menu_payload_assert_not_contains( 'Price:', $tickets_modal, 'Tickets presentation must suppress the Club menu base price.' );
menu_payload_assert_not_contains( '39.50', $tickets_modal, 'Tickets presentation must not display the Club menu base price value.' );
menu_payload_assert_contains( 'Supplement: £1.50', $tickets_modal, 'Tickets presentation must display non-zero dish supplements.' );
menu_payload_assert_contains( 'Supplement: £2.00', $tickets_modal, 'Tickets presentation must display every applicable dish supplement.' );

$tpw_menu_modal_presentation = 'tickets_fixed_menu';
ob_start();
require dirname( __DIR__, 2 ) . '/modules/menus/templates/menu-modal.php';
$fixed_menu_modal = ob_get_clean();
menu_payload_assert_not_contains( 'Price:', $fixed_menu_modal, 'Tickets fixed-menu presentation must suppress the Club menu base price.' );
menu_payload_assert_not_contains( '39.50', $fixed_menu_modal, 'Tickets fixed-menu presentation must not display the Club menu base price value.' );
menu_payload_assert_not_contains( 'Supplement:', $fixed_menu_modal, 'Tickets fixed-menu presentation must not present dish supplements as selectable charges.' );

$tpw_menu_modal_presentation = 'default';
ob_start();
require dirname( __DIR__, 2 ) . '/modules/menus/templates/menu-modal.php';
$default_modal = ob_get_clean();
menu_payload_assert_contains( 'Price:', $default_modal, 'The default presentation must preserve the existing menu price.' );
menu_payload_assert_contains( '£39.50', $default_modal, 'The default presentation must preserve the existing menu price format.' );
menu_payload_assert_not_contains( 'Supplement:', $default_modal, 'The default presentation must not add Tickets supplements.' );

menu_payload_reset_rendered_events();
ob_start();
TPW_Menus::hook_event_details_after_meta( array( 'event_id' => 50, 'post_id' => 100 ) );
TPW_Menus::hook_after_event_content( 50 );
$tickets_hook_output = ob_get_clean();
menu_payload_assert_same( 1, substr_count( $tickets_hook_output, 'tpw-menu-modal-trigger' ), 'Tickets details and legacy hooks must render exactly one View Menu trigger.' );
menu_payload_assert_not_contains( 'Price:', $tickets_hook_output, 'The Tickets hook-rendered modal must suppress the Club menu base price.' );
menu_payload_assert_contains( 'Supplement: £1.50', $tickets_hook_output, 'The Tickets hook-rendered modal must display dish supplements.' );

menu_payload_reset_rendered_events();
ob_start();
TPW_Menus::hook_event_details_after_meta( array( 'event_id' => 51, 'post_id' => 102 ) );
TPW_Menus::hook_after_event_content( array( 'event_id' => 51, 'post_id' => 102 ) );
$fixed_menu_hook_output = ob_get_clean();
menu_payload_assert_same( 1, substr_count( $fixed_menu_hook_output, 'tpw-menu-modal-trigger' ), 'Fixed-menu Tickets events must render exactly one details-panel trigger.' );
menu_payload_assert_not_contains( 'Price:', $fixed_menu_hook_output, 'Fixed-menu Tickets hooks must suppress the Club menu base price.' );
menu_payload_assert_not_contains( 'Supplement:', $fixed_menu_hook_output, 'Fixed-menu Tickets hooks must not display supplements.' );

menu_payload_reset_rendered_events();
ob_start();
TPW_Menus::hook_event_details_after_meta( array( 'event_id' => 52, 'post_id' => 103 ) );
TPW_Menus::hook_after_event_content( array( 'event_id' => 52, 'post_id' => 103 ) );
$none_hook_output = ob_get_clean();
menu_payload_assert_same( 0, substr_count( $none_hook_output, 'tpw-menu-modal-trigger' ), 'Tickets none mode must not render from either event hook.' );

menu_payload_reset_rendered_events();
ob_start();
TPW_Menus::hook_event_details_after_meta( array( 'event_id' => 55, 'post_id' => 106 ) );
TPW_Menus::hook_after_event_content( array( 'event_id' => 55, 'post_id' => 106 ) );
$mixed_hook_output = ob_get_clean();
menu_payload_assert_same( 1, substr_count( $mixed_hook_output, 'tpw-menu-modal-trigger' ), 'Mixed Tickets types must render one selectable-course trigger.' );

menu_payload_reset_rendered_events();
ob_start();
TPW_Menus::hook_event_details_after_meta( array( 'event_id' => 56, 'post_id' => 105 ) );
TPW_Menus::hook_after_event_content( array( 'event_id' => 56, 'post_id' => 105 ) );
$invalid_hook_output = ob_get_clean();
menu_payload_assert_same( 0, substr_count( $invalid_hook_output, 'tpw-menu-modal-trigger' ), 'Invalid Tickets presentation values must fail closed without a trigger.' );

menu_payload_reset_rendered_events();
ob_start();
TPW_Menus::hook_event_details_after_meta( array( 'event_id' => 57, 'post_id' => 101 ) );
TPW_Menus::hook_after_event_content( array( 'event_id' => 57, 'post_id' => 101 ) );
$lodge_hook_output = ob_get_clean();
menu_payload_assert_same( 1, substr_count( $lodge_hook_output, 'tpw-menu-modal-trigger' ), 'Non-Tickets events must preserve the default single-trigger rendering.' );
menu_payload_assert_contains( 'Price:', $lodge_hook_output, 'Non-Tickets events must preserve default Club menu pricing.' );
menu_payload_assert_not_contains( 'Supplement:', $lodge_hook_output, 'Non-Tickets events must preserve default Club menu choices.' );

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
