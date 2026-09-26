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
	private $owned_notice_id;
	private $trashed_notice_id;
	private $owned_term_id;
	private $created_owned_tables = array();

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
		$this->create_owned_data_tables();
		$this->clear_owned_data_tables();
	}

	public function tear_down() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$this->templates_table}" );
		if ( $this->created_payments_table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$this->payments_table}" );
		}

		foreach ( array( $this->owned_page_id, $this->conflicting_page_id, $this->owned_notice_id, $this->trashed_notice_id ) as $page_id ) {
			if ( $page_id ) {
				wp_delete_post( $page_id, true );
			}
		}
		if ( $this->owned_term_id ) {
			wp_delete_term( $this->owned_term_id, 'tpw_notice_category' );
		}

		delete_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION );
		delete_option( 'tpw_core_branding' );
		delete_option( 'tpw_members_settings' );
		delete_option( 'flexievent_settings' );
		delete_option( 'tpw_core_system_pages' );
		$this->clear_owned_data_tables();
		$this->drop_created_owned_data_tables();
		wp_clear_scheduled_hook( 'tpw_email_logs_cleanup' );
		wp_clear_scheduled_hook( 'tpw_control_backfill_checksums' );
		wp_clear_scheduled_hook( 'tpw_control_checksum_backfill' );

		parent::tear_down();
	}

	public function test_uninstall_consent_is_strict() {
		$this->assertFalse( TPW_Core_Lifecycle::is_delete_data_on_uninstall_enabled() );

		foreach ( array( '0', 'true', 'yes', ' 1', '1 ', 1, true, false, array( '1' ), null ) as $value ) {
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
		$this->assertSame( 1, $this->owned_data_count( 'tpw_members' ) );
		$this->assertSame( 1, $this->signup_attempt_count( 'tpw-core' ) );
		$this->assertSame( 1, $this->signup_attempt_count( 'lodge-rsvp' ) );
		$this->assertNotFalse( get_post( $this->owned_notice_id ) );
		$this->assertCount( 2, get_option( 'tpw_core_system_pages' ) );
	}

	public function test_opted_in_uninstall_removes_all_proven_club_owned_data() {
		$this->create_mixed_fixtures();
		update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, '1' );

		$this->assertTrue( TPW_Core_Lifecycle::uninstall() );
		$this->assertFalse( get_option( 'tpw_core_branding', false ) );
		$this->assertFalse( get_option( 'tpw_members_settings', false ) );
		$this->assertSame( 'event-settings', get_option( 'flexievent_settings' ) );
		$this->assertSame( 1, $this->template_count( 'lodge_rsvp_confirmation' ) );
		$this->assertSame( 1, $this->payment_count() );
		foreach ( TPW_Core_Lifecycle::MEMBER_TABLES as $table_suffix ) {
			$this->assertSame( 0, $this->owned_data_count( $table_suffix ) );
		}
		foreach ( TPW_Core_Lifecycle::GALLERY_TABLES as $table_suffix ) {
			$this->assertSame( 0, $this->owned_data_count( $table_suffix ) );
		}
		foreach ( TPW_Core_Lifecycle::UPLOAD_PAGE_TABLES as $table_suffix ) {
			$this->assertSame( 0, $this->owned_data_count( $table_suffix ) );
		}
		$this->assertSame( 0, $this->signup_attempt_count( 'tpw-core' ) );
		$this->assertSame( 1, $this->signup_attempt_count( 'lodge-rsvp' ) );
		$this->assertNull( get_post( $this->owned_notice_id ) );
		$this->assertNull( get_post( $this->trashed_notice_id ) );
		$this->assertNull( term_exists( $this->owned_term_id, 'tpw_notice_category' ) );
		$this->assertNull( get_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, null ) );

		$mappings = get_option( 'tpw_core_system_pages' );
		$this->assertArrayNotHasKey( 'member-login', $mappings );
		$this->assertArrayHasKey( 'logs', $mappings );
		$this->assertNull( get_post( $this->owned_page_id ) );
		$this->assertNotFalse( get_post( $this->conflicting_page_id ) );

		update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, '1' );
		$this->assertTrue( TPW_Core_Lifecycle::uninstall() );
		$this->assertSame( 1, $this->template_count( 'lodge_rsvp_confirmation' ) );
		$this->assertSame( 1, $this->payment_count() );
	}

	public function test_opted_in_uninstall_deletes_notice_terms_without_plugin_init() {
		$this->create_mixed_fixtures();
		unregister_taxonomy( 'tpw_notice_category' );
		update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, '1' );

		$this->assertTrue( TPW_Core_Lifecycle::uninstall() );
		$this->assertTrue( taxonomy_exists( 'tpw_notice_category' ) );
		$this->assertNull( term_exists( $this->owned_term_id, 'tpw_notice_category' ) );
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
		$this->insert_owned_data_rows();
		$wpdb->insert( $this->templates_table, array( 'template_key' => 'member_new_wp_user_created', 'template_group' => 'members', 'payload' => 'core' ) );
		$wpdb->insert( $this->templates_table, array( 'template_key' => 'member_password_setup', 'template_group' => 'members', 'payload' => 'core' ) );
		$wpdb->insert( $this->templates_table, array( 'template_key' => 'lodge_rsvp_confirmation', 'template_group' => 'members', 'payload' => 'consumer' ) );
		$wpdb->insert( $this->payments_table, array( 'reference_code' => 'preserve-payment-row' ) );
		$signup_table = $wpdb->prefix . 'tpw_signup_attempts';
		$wpdb->insert( $signup_table, array( 'plugin_key' => 'tpw-core' ) );
		$wpdb->insert( $signup_table, array( 'plugin_key' => 'lodge-rsvp' ) );

		if ( ! post_type_exists( 'tpw_notice' ) && class_exists( 'TPW_Noticeboard' ) ) {
			TPW_Noticeboard::register_cpt_and_tax();
		}
		$this->owned_notice_id = wp_insert_post(
			array(
				'post_type'   => 'tpw_notice',
				'post_status' => 'publish',
				'post_title'  => 'Lifecycle Notice',
			)
		);
		$this->trashed_notice_id = wp_insert_post(
			array(
				'post_type'   => 'tpw_notice',
				'post_status' => 'publish',
				'post_title'  => 'Lifecycle Trashed Notice',
			)
		);
		wp_trash_post( $this->trashed_notice_id );
		$notice_term = wp_insert_term( 'Lifecycle Notice Category', 'tpw_notice_category' );
		$this->owned_term_id = is_wp_error( $notice_term ) ? 0 : (int) $notice_term['term_id'];
		if ( $this->owned_term_id ) {
			wp_set_object_terms( $this->owned_notice_id, array( $this->owned_term_id ), 'tpw_notice_category' );
		}

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

	private function create_owned_data_tables() {
		global $wpdb;

		foreach ( array_merge( TPW_Core_Lifecycle::MEMBER_TABLES, TPW_Core_Lifecycle::GALLERY_TABLES, TPW_Core_Lifecycle::UPLOAD_PAGE_TABLES ) as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			if ( ! $this->table_exists( $table_name ) ) {
				$wpdb->query( "CREATE TABLE {$table_name} (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, marker VARCHAR(50) NOT NULL DEFAULT '', PRIMARY KEY (id)) {$wpdb->get_charset_collate()}" );
				$this->created_owned_tables[] = $table_name;
			}
		}

		$signup_table = $wpdb->prefix . 'tpw_signup_attempts';
		if ( ! $this->table_exists( $signup_table ) ) {
			$wpdb->query( "CREATE TABLE {$signup_table} (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, plugin_key VARCHAR(100) NOT NULL, PRIMARY KEY (id)) {$wpdb->get_charset_collate()}" );
			$this->created_owned_tables[] = $signup_table;
		}
	}

	private function clear_owned_data_tables() {
		global $wpdb;

		foreach ( array_merge( TPW_Core_Lifecycle::MEMBER_TABLES, TPW_Core_Lifecycle::GALLERY_TABLES, TPW_Core_Lifecycle::UPLOAD_PAGE_TABLES, array( 'tpw_signup_attempts' ) ) as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			if ( $this->table_exists( $table_name ) ) {
				$wpdb->query( "DELETE FROM {$table_name}" );
			}
		}
	}

	private function drop_created_owned_data_tables() {
		global $wpdb;

		foreach ( array_reverse( $this->created_owned_tables ) as $table_name ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		}
	}

	private function insert_owned_data_rows() {
		global $wpdb;

		foreach ( array_merge( TPW_Core_Lifecycle::MEMBER_TABLES, TPW_Core_Lifecycle::GALLERY_TABLES, TPW_Core_Lifecycle::UPLOAD_PAGE_TABLES ) as $table_suffix ) {
			$wpdb->insert( $wpdb->prefix . $table_suffix, array( 'marker' => 'club-owned' ) );
		}
	}

	private function owned_data_count( $table_suffix ) {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}{$table_suffix} WHERE marker = 'club-owned'" );
	}

	private function signup_attempt_count( $plugin_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'tpw_signup_attempts';
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE plugin_key = %s", $plugin_key ) );
	}
}