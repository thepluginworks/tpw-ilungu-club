<?php
/**
 * WordPress integration coverage for iLungu Club lifecycle retention.
 */

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	return;
}

class TPW_Core_Lifecycle_Retention_Test extends WP_UnitTestCase {
	private $templates_table;
	private $payments_table;
	private $created_payments_table = false;
	private $owned_page_id;
	private $conflicting_page_id;

	public function set_up() {
		parent::set_up();

		global $wpdb;
		$this->templates_table = $wpdb->prefix . 'tpw_email_templates';
		$this->payments_table  = $wpdb->prefix . 'tpw_rsvp_payments';

		$wpdb->query( "DROP TABLE IF EXISTS {$this->templates_table}" );
		$wpdb->query( "CREATE TABLE {$this->templates_table} (template_key VARCHAR(191) NOT NULL, template_group VARCHAR(191) NOT NULL, payload TEXT NULL, PRIMARY KEY (template_key)) {$wpdb->get_charset_collate()}" );

		$this->created_payments_table = ! $this->table_exists( $this->payments_table );
		if ( $this->created_payments_table ) {
			$wpdb->query( "CREATE TABLE {$this->payments_table} (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, reference_code VARCHAR(191) NOT NULL, PRIMARY KEY (id)) {$wpdb->get_charset_collate()}" );
		}

		delete_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION );
		delete_option( 'tpw_core_branding' );
		delete_option( 'tpw_members_settings' );
		delete_option( 'flexievent_settings' );
		delete_option( 'tpw_core_system_pages' );
	}

	public function tear_down() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$this->templates_table}" );
		if ( $this->created_payments_table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$this->payments_table}" );
		}

		foreach ( array( $this->owned_page_id, $this->conflicting_page_id ) as $page_id ) {
			if ( $page_id ) {
				wp_delete_post( $page_id, true );
			}
		}

		delete_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION );
		delete_option( 'tpw_core_branding' );
		delete_option( 'tpw_members_settings' );
		delete_option( 'flexievent_settings' );
		delete_option( 'tpw_core_system_pages' );
		wp_clear_scheduled_hook( 'tpw_email_logs_cleanup' );
		wp_clear_scheduled_hook( 'tpw_control_backfill_checksums' );
		wp_clear_scheduled_hook( 'tpw_control_checksum_backfill' );

		parent::tear_down();
	}

	public function test_uninstall_consent_is_strict() {
		$this->assertFalse( TPW_Core_Lifecycle::is_delete_data_on_uninstall_enabled() );

		foreach ( array( '0', 'true', 'yes', ' 1', '1 ' ) as $value ) {
			update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, $value );
			$this->assertFalse( TPW_Core_Lifecycle::is_delete_data_on_uninstall_enabled() );
		}

		update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, '1' );
		$this->assertTrue( TPW_Core_Lifecycle::is_delete_data_on_uninstall_enabled() );
	}

	public function test_uninstall_without_consent_preserves_representative_data() {
		$this->create_mixed_fixtures();

		$this->assertFalse( TPW_Core_Lifecycle::uninstall() );
		$this->assertSame( 'core-branding', get_option( 'tpw_core_branding' ) );
		$this->assertSame( 'member-settings', get_option( 'tpw_members_settings' ) );
		$this->assertSame( 'event-settings', get_option( 'flexievent_settings' ) );
		$this->assertSame( 3, $this->template_count() );
		$this->assertSame( 1, $this->payment_count() );
		$this->assertCount( 2, get_option( 'tpw_core_system_pages' ) );
	}

	public function test_opted_in_uninstall_removes_only_exact_club_owned_data() {
		$this->create_mixed_fixtures();
		update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, '1' );

		$this->assertTrue( TPW_Core_Lifecycle::uninstall() );
		$this->assertFalse( get_option( 'tpw_core_branding', false ) );
		$this->assertSame( 'member-settings', get_option( 'tpw_members_settings' ) );
		$this->assertSame( 'event-settings', get_option( 'flexievent_settings' ) );
		$this->assertSame( 1, $this->template_count( 'lodge_rsvp_confirmation' ) );
		$this->assertSame( 1, $this->payment_count() );
		$this->assertNull( get_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, null ) );

		$mappings = get_option( 'tpw_core_system_pages' );
		$this->assertArrayNotHasKey( 'member-login', $mappings );
		$this->assertArrayHasKey( 'logs', $mappings );
		$this->assertNotFalse( get_post( $this->owned_page_id ) );
		$this->assertNotFalse( get_post( $this->conflicting_page_id ) );

		update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, '1' );
		$this->assertTrue( TPW_Core_Lifecycle::uninstall() );
		$this->assertSame( 1, $this->template_count( 'lodge_rsvp_confirmation' ) );
		$this->assertSame( 1, $this->payment_count() );
	}

	public function test_deactivation_only_unschedules_exact_native_club_jobs() {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'tpw_email_logs_cleanup' );
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'tpw_control_backfill_checksums' );
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'tpw_control_checksum_backfill' );
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'consumer_retained_job' );

		TPW_Core_Lifecycle::deactivate();

		$this->assertFalse( wp_next_scheduled( 'tpw_email_logs_cleanup' ) );
		$this->assertFalse( wp_next_scheduled( 'tpw_control_backfill_checksums' ) );
		$this->assertFalse( wp_next_scheduled( 'tpw_control_checksum_backfill' ) );
		$this->assertNotFalse( wp_next_scheduled( 'consumer_retained_job' ) );
		wp_clear_scheduled_hook( 'consumer_retained_job' );
	}

	private function create_mixed_fixtures() {
		global $wpdb;

		update_option( 'tpw_core_branding', 'core-branding' );
		update_option( 'tpw_members_settings', 'member-settings' );
		update_option( 'flexievent_settings', 'event-settings' );
		$wpdb->insert( $this->templates_table, array( 'template_key' => 'member_new_wp_user_created', 'template_group' => 'members', 'payload' => 'core' ) );
		$wpdb->insert( $this->templates_table, array( 'template_key' => 'member_password_setup', 'template_group' => 'members', 'payload' => 'core' ) );
		$wpdb->insert( $this->templates_table, array( 'template_key' => 'lodge_rsvp_confirmation', 'template_group' => 'members', 'payload' => 'consumer' ) );
		$wpdb->insert( $this->payments_table, array( 'reference_code' => 'preserve-payment-row' ) );

		$this->owned_page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Lifecycle Core Page',
			)
		);
		update_post_meta( $this->owned_page_id, '_tpw_system_page_slug', 'member-login' );
		update_post_meta( $this->owned_page_id, '_tpw_system_page_plugin', 'tpw-core' );

		$this->conflicting_page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Lifecycle Consumer Page',
			)
		);
		update_post_meta( $this->conflicting_page_id, '_tpw_system_page_slug', 'logs' );
		update_post_meta( $this->conflicting_page_id, '_tpw_system_page_plugin', 'lodge-rsvp' );

		update_option(
			'tpw_core_system_pages',
			array(
				'member-login' => array( 'wp_page_id' => $this->owned_page_id ),
				'logs'         => array( 'wp_page_id' => $this->conflicting_page_id ),
			)
		);
	}

	private function table_exists( $table_name ) {
		global $wpdb;

		$previous_suppress_errors = $wpdb->suppress_errors( true );
		$columns                  = $wpdb->get_col( "DESCRIBE {$table_name}", 0 );
		$wpdb->suppress_errors( $previous_suppress_errors );

		return ! empty( $columns );
	}

	private function template_count( $template_key = '' ) {
		global $wpdb;

		if ( '' === $template_key ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->templates_table}" );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->templates_table} WHERE template_key = %s", $template_key ) );
	}

	private function payment_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->payments_table} WHERE reference_code = 'preserve-payment-row'" );
	}
}