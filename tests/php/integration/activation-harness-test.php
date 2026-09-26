<?php
/**
 * Fresh activation coverage for the disposable WordPress PHPUnit database.
 */

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	return;
}

class TPW_Core_Activation_Harness_Test extends WP_UnitTestCase {
	private $system_page_ids_before = array();

	public function set_up() {
		parent::set_up();

		$this->system_page_ids_before = $this->core_system_page_ids();
		$this->drop_disposable_tpw_tables();
		$this->delete_disposable_tpw_options();
		wp_clear_scheduled_hook( 'tpw_email_logs_cleanup' );
	}

	public function tear_down() {
		foreach ( array_diff( $this->core_system_page_ids(), $this->system_page_ids_before ) as $page_id ) {
			wp_delete_post( $page_id, true );
		}

		wp_clear_scheduled_hook( 'tpw_email_logs_cleanup' );
		$this->drop_disposable_tpw_tables();
		$this->delete_disposable_tpw_options();

		parent::tear_down();
	}

	public function test_fresh_activation_creates_required_tables_and_is_idempotent() {
		global $wpdb;

		TPW_Core_Activator::activate();
		$payment_method_count = $this->payment_method_count();
		$wpdb->insert(
			$wpdb->prefix . 'tpw_members',
			array(
				'society_id' => 1,
				'first_name' => 'Activation',
				'surname'    => 'Sentinel',
				'email'      => 'activation-sentinel@example.test',
				'status'     => 'Active',
			)
		);
		$member_id = (int) $wpdb->insert_id;

		TPW_Core_Activator::activate();

		foreach ( array( 'tpw_members', 'tpw_menus', 'tpw_menu_choices', 'tpw_menu_courses', 'tpw_email_templates', 'tpw_email_logs', 'tpw_email_queue', 'tpw_payment_methods', 'tpw_rsvp_payments', 'tpw_signup_attempts', 'tpw_galleries' ) as $table_suffix ) {
			$this->assertTrue( $this->table_exists( $table_suffix ), $table_suffix . ' was not created.' );
		}
		$this->assertSame( $payment_method_count, $this->payment_method_count() );
		$this->assertSame( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tpw_members WHERE id = %d", $member_id ) ) );
		$this->assertSame( 1, $this->scheduled_hook_count( 'tpw_email_logs_cleanup' ) );
	}

	private function drop_disposable_tpw_tables() {
		global $wpdb;

		$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'tpw\_%' ) );
		foreach ( array_reverse( $tables ) as $table_name ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		}
	}

	private function delete_disposable_tpw_options() {
		global $wpdb;

		$options = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'tpw\_%' ) );
		foreach ( $options as $option_name ) {
			delete_option( $option_name );
		}
	}

	private function core_system_page_ids() {
		return get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_tpw_system_page_plugin',
				'meta_value'     => 'tpw-core',
			)
		);
	}

	private function table_exists( $table_suffix ) {
		global $wpdb;

		$table_name = $wpdb->prefix . $table_suffix;
		$previous_suppress_errors = $wpdb->suppress_errors( true );
		$columns                  = $wpdb->get_col( "DESCRIBE {$table_name}", 0 );
		$wpdb->suppress_errors( $previous_suppress_errors );

		return ! empty( $columns );
	}

	private function payment_method_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tpw_payment_methods" );
	}

	private function scheduled_hook_count( $hook ) {
		$count = 0;
		foreach ( _get_cron_array() as $events ) {
			if ( isset( $events[ $hook ] ) ) {
				$count += count( $events[ $hook ] );
			}
		}

		return $count;
	}
}