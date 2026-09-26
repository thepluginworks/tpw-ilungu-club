<?php
/**
 * Isolated lifecycle uninstall and reinstall coverage.
 */

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	return;
}

class TPW_Core_Lifecycle_Reinstall_Test extends WP_UnitTestCase {
	private $system_page_ids_before = array();
	private $member_id;
	private $gallery_id;
	private $upload_page_id;
	private $signup_token;
	private $notice_id;
	private $core_page_id;
	private $provider_page_id;
	private $user_id;
	private $attachment_id;
	private $attachment_path;
	private $action_scheduler_action_id;

	public function set_up() {
		parent::set_up();

		$this->system_page_ids_before = $this->core_system_page_ids();
		$this->drop_disposable_tpw_tables();
		$this->delete_disposable_tpw_options();
		wp_clear_scheduled_hook( 'tpw_email_logs_cleanup' );
		wp_clear_scheduled_hook( 'lifecycle_reinstall_consumer_hook' );
		TPW_Core_Activator::activate();
		TPW_Control_Upload_Pages::ensure_tables();
	}

	public function tear_down() {
		foreach ( array( $this->notice_id, $this->core_page_id, $this->provider_page_id ) as $post_id ) {
			if ( $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}
		if ( $this->attachment_id ) {
			wp_delete_attachment( $this->attachment_id, true );
		}
		if ( $this->attachment_path && file_exists( $this->attachment_path ) ) {
			unlink( $this->attachment_path );
		}
		if ( $this->user_id ) {
			wp_delete_user( $this->user_id );
		}

		foreach ( array_diff( $this->core_system_page_ids(), $this->system_page_ids_before ) as $page_id ) {
			wp_delete_post( $page_id, true );
		}
		wp_clear_scheduled_hook( 'tpw_email_logs_cleanup' );
		wp_clear_scheduled_hook( 'lifecycle_reinstall_consumer_hook' );
		if ( $this->action_scheduler_action_id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'actionscheduler_actions', array( 'action_id' => $this->action_scheduler_action_id ) );
		}
		$this->drop_disposable_tpw_tables();
		$this->delete_disposable_tpw_options();

		parent::tear_down();
	}

	public function test_consent_off_uninstall_reinstall_preserves_club_and_protected_data() {
		$this->create_sentinels();
		$payment_method_count = $this->payment_method_count();

		$this->assertFalse( TPW_Core_Lifecycle::uninstall() );
		$this->assert_club_sentinels_present();
		$this->assert_protected_sentinels_present();

		TPW_Core_Activator::activate();
		TPW_Core_Activator::activate();
		TPW_Control_Upload_Pages::ensure_tables();

		$this->assert_club_sentinels_present();
		$this->assert_protected_sentinels_present();
		$this->assertSame( $payment_method_count, $this->payment_method_count() );
		$this->assert_activation_state();
	}

	public function test_consent_on_uninstall_reinstall_removes_club_and_preserves_protected_data() {
		$this->create_sentinels();
		$payment_method_count = $this->payment_method_count();
		update_option( TPW_Core_Lifecycle::DELETE_DATA_OPTION, '1' );

		$this->assertTrue( TPW_Core_Lifecycle::uninstall() );
		$this->assert_club_sentinels_absent();
		$this->assert_protected_sentinels_present();

		TPW_Core_Activator::activate();
		TPW_Core_Activator::activate();
		TPW_Control_Upload_Pages::ensure_tables();

		$this->assertFalse( TPW_Core_Lifecycle::is_delete_data_on_uninstall_enabled() );
		$this->assert_club_sentinels_absent();
		$this->assert_protected_sentinels_present();
		$this->assertSame( $payment_method_count, $this->payment_method_count() );
		$this->assert_activation_state();
	}

	private function create_sentinels() {
		global $wpdb;

		$now = current_time( 'mysql' );
		$wpdb->insert( $wpdb->prefix . 'tpw_members', array( 'society_id' => 1, 'first_name' => 'Lifecycle', 'surname' => 'Sentinel', 'email' => 'lifecycle-member@example.test', 'status' => 'Active' ) );
		$this->member_id = (int) $wpdb->insert_id;
		$wpdb->insert( $wpdb->prefix . 'tpw_galleries', array( 'title' => 'Lifecycle Gallery', 'slug' => 'lifecycle-gallery-sentinel', 'created_at' => $now ) );
		$this->gallery_id = (int) $wpdb->insert_id;
		$wpdb->insert( $wpdb->prefix . 'tpw_upload_pages', array( 'slug' => 'lifecycle-upload-page-sentinel', 'title' => 'Lifecycle Upload Page' ) );
		$this->upload_page_id = (int) $wpdb->insert_id;
		$this->signup_token = hash( 'sha256', 'lifecycle-reinstall-' . wp_generate_uuid4() );
		$wpdb->insert( $wpdb->prefix . 'tpw_signup_attempts', array( 'public_token' => $this->signup_token, 'flow_key' => 'members_join', 'plugin_key' => 'tpw-core', 'status' => 'draft', 'email' => 'lifecycle-signup@example.test', 'created_at' => $now, 'updated_at' => $now ) );
		$wpdb->insert( $wpdb->prefix . 'tpw_signup_attempts', array( 'public_token' => hash( 'sha256', 'consumer-' . wp_generate_uuid4() ), 'flow_key' => 'rsvp', 'plugin_key' => 'lodge-rsvp', 'status' => 'draft', 'email' => 'consumer-signup@example.test', 'created_at' => $now, 'updated_at' => $now ) );
		$wpdb->insert( $wpdb->prefix . 'tpw_email_templates', array( 'template_key' => 'lodge_reinstall_sentinel', 'template_group' => 'lodge', 'template_label' => 'Lodge sentinel' ) );
		$wpdb->insert( $wpdb->prefix . 'tpw_rsvp_payments', array( 'submission_id' => 987654, 'amount' => '12.34', 'payment_reference' => 'lifecycle-payment-sentinel' ) );
		update_option( 'flexievent_settings', array( 'lifecycle_sentinel' => 'retain' ) );
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'lifecycle_reinstall_consumer_hook' );
		$this->action_scheduler_action_id = as_schedule_single_action( time() + HOUR_IN_SECONDS, 'lifecycle_reinstall_action_scheduler_hook' );

		$this->notice_id = wp_insert_post( array( 'post_type' => 'tpw_notice', 'post_status' => 'publish', 'post_title' => 'Lifecycle Reinstall Notice' ) );
		$this->core_page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Lifecycle Core Page' ) );
		update_post_meta( $this->core_page_id, '_tpw_system_page_slug', 'member-login' );
		update_post_meta( $this->core_page_id, '_tpw_system_page_plugin', 'tpw-core' );
		$this->provider_page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Lifecycle Provider Page' ) );
		update_post_meta( $this->provider_page_id, '_tpw_system_page_slug', 'logs' );
		update_post_meta( $this->provider_page_id, '_tpw_system_page_plugin', 'lodge-rsvp' );
		update_option( 'tpw_core_system_pages', array( 'member-login' => array( 'wp_page_id' => $this->core_page_id ), 'logs' => array( 'wp_page_id' => $this->provider_page_id ) ) );

		$this->user_id = wp_create_user( 'lifecycle-reinstall-user', 'lifecycle-password', 'lifecycle-user@example.test' );
		$user = get_user_by( 'id', $this->user_id );
		$user->set_role( 'subscriber' );
		add_user_meta( $this->user_id, 'lifecycle_role_sentinel', 'retain' );
		$upload = wp_upload_bits( 'lifecycle-reinstall-sentinel.txt', null, 'lifecycle media sentinel' );
		$this->attachment_path = $upload['file'];
		$this->attachment_id = wp_insert_attachment( array( 'post_mime_type' => 'text/plain', 'post_title' => 'Lifecycle Media Sentinel', 'post_status' => 'inherit' ), $this->attachment_path );
	}

	private function assert_club_sentinels_present() {
		$this->assertSame( 1, $this->row_count( 'tpw_members', 'id', $this->member_id ) );
		$this->assertSame( 1, $this->row_count( 'tpw_galleries', 'gallery_id', $this->gallery_id ) );
		$this->assertSame( 1, $this->row_count( 'tpw_upload_pages', 'id', $this->upload_page_id ) );
		$this->assertSame( 1, $this->signup_count( $this->signup_token ) );
		$this->assertNotNull( get_post( $this->notice_id ) );
		$this->assertNotNull( get_post( $this->core_page_id ) );
	}

	private function assert_club_sentinels_absent() {
		$this->assertSame( 0, $this->row_count( 'tpw_members', 'id', $this->member_id ) );
		$this->assertSame( 0, $this->row_count( 'tpw_galleries', 'gallery_id', $this->gallery_id ) );
		$this->assertSame( 0, $this->row_count( 'tpw_upload_pages', 'id', $this->upload_page_id ) );
		$this->assertSame( 0, $this->signup_count( $this->signup_token ) );
		$this->assertNull( get_post( $this->notice_id ) );
		$this->assertNull( get_post( $this->core_page_id ) );
	}

	private function assert_protected_sentinels_present() {
		global $wpdb;

		$this->assertNotNull( get_userdata( $this->user_id ) );
		$this->assertTrue( user_can( $this->user_id, 'read' ) );
		$this->assertSame( 'retain', get_user_meta( $this->user_id, 'lifecycle_role_sentinel', true ) );
		$this->assertNotNull( get_post( $this->attachment_id ) );
		$this->assertFileExists( $this->attachment_path );
		$this->assertSame( 'retain', get_option( 'flexievent_settings' )['lifecycle_sentinel'] );
		$this->assertSame( 1, $this->template_count( 'lodge_reinstall_sentinel' ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tpw_rsvp_payments WHERE payment_reference = 'lifecycle-payment-sentinel'" ) );
		$this->assertSame( 1, $this->signup_count_by_plugin( 'lodge-rsvp' ) );
		$this->assertNotNull( get_post( $this->provider_page_id ) );
		$this->assertArrayHasKey( 'logs', get_option( 'tpw_core_system_pages' ) );
		$this->assertNotFalse( wp_next_scheduled( 'lifecycle_reinstall_consumer_hook' ) );
		$this->assertTrue( $this->table_exists( 'tpw_email_logs' ) );
		$this->assertTrue( $this->table_exists( 'tpw_email_queue' ) );
		$this->assertTrue( $this->table_exists( 'actionscheduler_actions' ) );
		$this->assertSame( 1, $this->row_count( 'actionscheduler_actions', 'action_id', $this->action_scheduler_action_id ) );
	}

	private function assert_activation_state() {
		foreach ( array( 'tpw_members', 'tpw_galleries', 'tpw_upload_pages', 'tpw_email_templates', 'tpw_email_logs', 'tpw_email_queue', 'tpw_payment_methods', 'tpw_rsvp_payments', 'tpw_signup_attempts' ) as $table_suffix ) {
			$this->assertTrue( $this->table_exists( $table_suffix ), $table_suffix . ' was not retained.' );
		}
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
		return get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_tpw_system_page_plugin', 'meta_value' => 'tpw-core' ) );
	}

	private function row_count( $table_suffix, $column, $value ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}{$table_suffix} WHERE {$column} = %d", $value ) );
	}

	private function signup_count( $token ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tpw_signup_attempts WHERE public_token = %s", $token ) );
	}

	private function signup_count_by_plugin( $plugin_key ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tpw_signup_attempts WHERE plugin_key = %s", $plugin_key ) );
	}

	private function template_count( $template_key ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tpw_email_templates WHERE template_key = %s", $template_key ) );
	}

	private function payment_method_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tpw_payment_methods" );
	}

	private function table_exists( $table_suffix ) {
		global $wpdb;

		$table_name = $wpdb->prefix . $table_suffix;
		$previous_suppress_errors = $wpdb->suppress_errors( true );
		$columns = $wpdb->get_col( "DESCRIBE {$table_name}", 0 );
		$wpdb->suppress_errors( $previous_suppress_errors );

		return ! empty( $columns );
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