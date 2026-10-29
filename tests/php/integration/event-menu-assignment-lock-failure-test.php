<?php
/**
 * Runtime lock-timeout coverage for the Club-owned event-menu assignment API.
 */

use PHPUnit\Framework\TestCase;

class TPW_Event_Menu_Assignment_Lock_Failure_Test extends TestCase {
	private $events_table;
	private $menus_table;
	private $relationships_table;
	private $created_events_table = false;
	private $post_id = 0;
	private $event_id = 0;
	private $menu_id = 0;

	protected function setUp(): void {
		global $wpdb;

		$this->events_table        = $wpdb->prefix . 'tpw_events';
		$this->menus_table         = $wpdb->prefix . 'tpw_menus';
		$this->relationships_table = $wpdb->prefix . 'tpw_event_menu_relationship';
		TPW_Menus_Manager::create_table();
		TPW_Event_Menu_Rel::create_table();

		$existing_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->events_table ) );
		if ( $this->events_table !== $existing_table ) {
			$wpdb->query( "CREATE TABLE {$this->events_table} (event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, post_id BIGINT UNSIGNED NOT NULL, PRIMARY KEY (event_id), KEY post_id (post_id))" );
			$this->created_events_table = true;
		}

		$this->post_id = (int) wp_insert_post(
			array(
				'post_type' => 'tpw_event',
				'post_status' => 'publish',
				'post_title' => 'Event menu lock failure runtime fixture',
			),
			true
		);
		$this->assertGreaterThan( 0, $this->post_id );
		$wpdb->insert( $this->events_table, array( 'post_id' => $this->post_id ), array( '%d' ) );
		$this->event_id = (int) $wpdb->insert_id;
		$wpdb->insert(
			$this->menus_table,
			array(
				'name' => 'Event menu lock failure runtime fixture',
				'number_of_courses' => 1,
				'price' => '0.00',
			)
		);
		$this->menu_id = (int) $wpdb->insert_id;
		$this->assertGreaterThan( 0, $this->event_id );
		$this->assertGreaterThan( 0, $this->menu_id );
	}

	protected function tearDown(): void {
		global $wpdb;

		if ( $this->event_id > 0 ) {
			$wpdb->delete( $this->relationships_table, array( 'event_id' => $this->event_id ), array( '%d' ) );
		}
		if ( $this->post_id > 0 ) {
			$wpdb->delete( $this->events_table, array( 'post_id' => $this->post_id ), array( '%d' ) );
			wp_delete_post( $this->post_id, true );
		}
		if ( $this->menu_id > 0 ) {
			$wpdb->delete( $this->menus_table, array( 'id' => $this->menu_id ), array( '%d' ) );
		}
		if ( $this->created_events_table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$this->events_table}" );
		}
	}

	public function test_assignment_fails_safely_when_relationship_table_lock_cannot_be_acquired(): void {
		global $wpdb;

		$connection = $this->open_secondary_connection();
		$previous_timeout = (int) $wpdb->get_var( 'SELECT @@SESSION.lock_wait_timeout' );
		$result = null;
		$lock_error = '';

		try {
			$this->assertTrue( mysqli_query( $connection, "LOCK TABLES {$this->relationships_table} WRITE" ) );
			$this->assertSame( 0, $wpdb->query( 'SET SESSION lock_wait_timeout = 1' ) );

			$previous_suppression = $wpdb->suppress_errors( true );
			$result = TPW_Event_Menu_Rel::assign_menu_to_event_post( $this->post_id, $this->menu_id );
			$lock_error = $wpdb->last_error;
			$wpdb->suppress_errors( $previous_suppression );
		} finally {
			$wpdb->query( "SET SESSION lock_wait_timeout = {$previous_timeout}" );
			mysqli_query( $connection, 'UNLOCK TABLES' );
			mysqli_close( $connection );
		}

		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'assignment_lock_failed', $result['code'] );
		$this->assertNotSame( '', $lock_error, 'A refused table lock must report the expected database error to wpdb.' );
		$this->assertSame( array(), $this->menu_ids_for_event() );
		$this->assertSame( '1', (string) $wpdb->get_var( 'SELECT 1' ), 'The WordPress database connection must remain usable after lock failure.' );
	}

	private function menu_ids_for_event(): array {
		global $wpdb;

		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT menu_id FROM {$this->relationships_table} WHERE event_id = %d", $this->event_id ) ) );
	}

	private function open_secondary_connection() {
		$host = DB_HOST;
		$socket = null;

		if ( false !== strpos( $host, ':/' ) ) {
			list( $host, $socket ) = explode( ':', $host, 2 );
		}

		$connection = mysqli_init();
		$this->assertTrue( mysqli_real_connect( $connection, $host, DB_USER, DB_PASSWORD, DB_NAME, null, $socket ) );

		return $connection;
	}
}
