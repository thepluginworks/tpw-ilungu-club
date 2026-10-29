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
	200 => (object) array(
		'ID'        => 200,
		'post_type' => 'tpw_event',
	),
);

function absint( $value ) {
	return abs( (int) $value );
}

function get_post( $post_id ) {
	global $test_posts;

	return isset( $test_posts[ $post_id ] ) ? $test_posts[ $post_id ] : null;
}

function current_time( $type ) {
	unset( $type );

	return '2026-10-29 15:00:00';
}

class TPW_Event_Menu_Assignment_Contract_Test_DB {
	public $prefix = 'wp_';
	public $event_ids = array(
		100 => array( 50 ),
		200 => array( 60 ),
	);
	public $menu_ids = array( 70, 71 );
	public $relationships = array();
	public $queries = array();
	public $lock_succeeds = true;
	public $unlock_succeeds = true;
	public $last_error = '';

	public function prepare( $query, ...$arguments ) {
		foreach ( $arguments as $argument ) {
			$query = preg_replace( '/%d/', (string) (int) $argument, $query, 1 );
		}

		return $query;
	}

	public function get_col( $query ) {
		if ( preg_match( '/FROM wp_tpw_events\s+WHERE post_id = (\d+)/', $query, $matches ) ) {
			return isset( $this->event_ids[ (int) $matches[1] ] ) ? $this->event_ids[ (int) $matches[1] ] : array();
		}

		return array();
	}

	public function get_var( $query ) {
		if ( preg_match( '/FROM wp_tpw_menus\s+WHERE id = (\d+)/', $query, $matches ) ) {
			$menu_id = (int) $matches[1];

			return in_array( $menu_id, $this->menu_ids, true ) ? $menu_id : null;
		}

		return null;
	}

	public function query( $query ) {
		$this->queries[] = $query;

		if ( 0 === strpos( $query, 'LOCK TABLES ' ) ) {
			return $this->lock_succeeds ? 0 : false;
		}

		if ( 'UNLOCK TABLES' === $query ) {
			return $this->unlock_succeeds ? 0 : false;
		}

		return false;
	}

	public function get_results( $query, $output ) {
		unset( $output );

		if ( preg_match( '/FROM wp_tpw_event_menu_relationship\s+WHERE event_id = (\d+)/', $query, $matches ) ) {
			$event_id = (int) $matches[1];

			return array_values(
				array_filter(
					$this->relationships,
					static function( $relationship ) use ( $event_id ) {
						return $event_id === $relationship['event_id'];
					}
				)
			);
		}

		return array();
	}

	public function insert( $table, $data, $format ) {
		unset( $table, $format );

		$this->relationships[] = array(
			'id'       => count( $this->relationships ) + 1,
			'event_id' => $data['event_id'],
			'menu_id'  => $data['menu_id'],
		);

		return 1;
	}

	public function update( $table, $data, $where, $format, $where_format ) {
		unset( $table, $format, $where_format );

		foreach ( $this->relationships as $index => $relationship ) {
			if ( $where['id'] === $relationship['id'] && $where['event_id'] === $relationship['event_id'] ) {
				$this->relationships[ $index ]['menu_id'] = $data['menu_id'];

				return 1;
			}
		}

		return 0;
	}
}

function event_menu_assignment_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

function event_menu_assignment_reset( $relationships = array() ) {
	global $wpdb;

	$wpdb = new TPW_Event_Menu_Assignment_Contract_Test_DB();
	$wpdb->relationships = $relationships;
}

global $wpdb;
event_menu_assignment_reset();

require dirname( __DIR__, 2 ) . '/modules/menus/class-tpw-event-menu-rel.php';

$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 100, 70 );
event_menu_assignment_assert_same( true, $result['success'], 'A valid first assignment must succeed.' );
event_menu_assignment_assert_same( 'created', $result['code'], 'A first assignment must report creation.' );
event_menu_assignment_assert_same( array( array( 'id' => 1, 'event_id' => 50, 'menu_id' => 70 ) ), $wpdb->relationships, 'A first assignment must create one relationship for its Events-domain ID.' );

$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 100, 71 );
event_menu_assignment_assert_same( true, $result['success'], 'Changing an existing event menu must succeed.' );
event_menu_assignment_assert_same( 'updated', $result['code'], 'Changing an existing event menu must report an update.' );
event_menu_assignment_assert_same( array( array( 'id' => 1, 'event_id' => 50, 'menu_id' => 71 ) ), $wpdb->relationships, 'Changing an event menu must replace only its existing relationship.' );

$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 200, 71 );
event_menu_assignment_assert_same( true, $result['success'], 'The same menu must be reusable by another event.' );
event_menu_assignment_assert_same( array( array( 'id' => 1, 'event_id' => 50, 'menu_id' => 71 ), array( 'id' => 2, 'event_id' => 60, 'menu_id' => 71 ) ), $wpdb->relationships, 'Reusing a menu must not modify another event relationship.' );

event_menu_assignment_reset(
	array(
		array( 'id' => 1, 'event_id' => 50, 'menu_id' => 70 ),
		array( 'id' => 2, 'event_id' => 50, 'menu_id' => 71 ),
	)
);
$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 100, 70 );
event_menu_assignment_assert_same( false, $result['success'], 'Ambiguous event relationships must be rejected.' );
event_menu_assignment_assert_same( 'ambiguous_relationship', $result['code'], 'Ambiguous event relationships must return an explicit failure code.' );
event_menu_assignment_assert_same( 2, count( $wpdb->relationships ), 'Ambiguous relationships must not be deleted or changed.' );

event_menu_assignment_reset();
$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 999, 70 );
event_menu_assignment_assert_same( 'invalid_event_post', $result['code'], 'A missing event post must be rejected.' );
$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 101, 70 );
event_menu_assignment_assert_same( 'invalid_event_post', $result['code'], 'A non-event post must be rejected.' );
$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 100, 999 );
event_menu_assignment_assert_same( 'invalid_menu', $result['code'], 'A missing Club menu must be rejected.' );

event_menu_assignment_reset();
$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( 100, 70 );
event_menu_assignment_assert_same( true, $result['success'], 'A valid assignment must succeed while protected by a table lock.' );
event_menu_assignment_assert_same( array( 'LOCK TABLES wp_tpw_event_menu_relationship WRITE', 'UNLOCK TABLES' ), $wpdb->queries, 'Concurrent assignment protection must lock the relationship table for the complete check-and-write operation.' );

echo "event menu assignment contract tests passed\n";
