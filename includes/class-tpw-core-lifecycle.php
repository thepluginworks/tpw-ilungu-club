<?php
/**
 * iLungu Club lifecycle and data-retention service.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TPW_Core_Lifecycle {
	const DELETE_DATA_OPTION = 'tpw_core_delete_data_on_uninstall';

	const EMAIL_TEMPLATE_KEYS = array(
		'member_new_wp_user_created',
		'member_password_setup',
	);

	const REMOVABLE_OPTIONS = array(
		'tpw_core_branding',
		'tpw_ui_theme_settings',
		'tpw_heading_styles',
		'tpw_brand_title',
		'tpw_core_default_login_page',
		'tpw_login_redirect_page_id',
		'tpw_member_menu_location',
		'tpw_core_rewrite_flushed_v1',
		'tpw_core_rewrite_flushed_v2',
		'tpw_core_member_menu_seeded',
		'tpw_core_member_menu_defaults_seeded_v2',
		'tpw_core_profile_page_seeded',
	);

	const CORE_SYSTEM_PAGE_SLUGS = array(
		'member-login',
		'my-profile',
		'manage-members',
		'noticeboard',
		'club-management',
		'logs',
		'menu-management',
		'archival-system',
		'tpw-control',
	);

	/**
	 * Consent is enabled only by the exact stored compatibility value.
	 *
	 * @return bool
	 */
	public static function is_delete_data_on_uninstall_enabled() {
		return '1' === get_option( self::DELETE_DATA_OPTION, '0' );
	}

	/**
	 * Persist the canonical current-site uninstall consent value.
	 *
	 * @param bool $enabled Whether cleanup is enabled.
	 * @return bool
	 */
	public static function set_delete_data_on_uninstall_enabled( $enabled ) {
		return update_option( self::DELETE_DATA_OPTION, true === $enabled ? '1' : '0' );
	}

	/**
	 * Remove only future jobs that are known to be Club-owned.
	 *
	 * @return void
	 */
	public static function deactivate() {
		if ( class_exists( 'TPW_Core_Scheduler' ) ) {
			TPW_Core_Scheduler::unschedule( 'tpw_email_queue_reconcile', array(), 'tpw-email' );
		}

		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( 'tpw_email_logs_cleanup' );
			wp_clear_scheduled_hook( 'tpw_control_backfill_checksums' );
			wp_clear_scheduled_hook( 'tpw_control_checksum_backfill' );
		}
	}

	/**
	 * Run opted-in uninstall cleanup.
	 *
	 * @return bool True only when opted-in cleanup ran.
	 */
	public static function uninstall() {
		if ( ! self::is_delete_data_on_uninstall_enabled() ) {
			return false;
		}

		self::delete_removable_options();
		self::delete_core_email_template_overrides();
		self::remove_owned_system_page_mappings();
		delete_option( self::DELETE_DATA_OPTION );

		return true;
	}

	/**
	 * Delete only confirmed Club configuration options.
	 *
	 * @return void
	 */
	private static function delete_removable_options() {
		foreach ( self::REMOVABLE_OPTIONS as $option_name ) {
			delete_option( $option_name );
		}
	}

	/**
	 * Delete only exact Core template overrides from the shared table.
	 *
	 * @return void
	 */
	private static function delete_core_email_template_overrides() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'tpw_email_templates';
		if ( ! self::table_exists( $table_name ) ) {
			return;
		}

		foreach ( self::EMAIL_TEMPLATE_KEYS as $template_key ) {
			$wpdb->delete( $table_name, array( 'template_key' => $template_key ), array( '%s' ) );
		}
	}

	/**
	 * Remove mappings only when a mapped page carries complete Core ownership metadata.
	 * Pages are deliberately retained for safe reactivation and reinstall.
	 *
	 * @return void
	 */
	private static function remove_owned_system_page_mappings() {
		$mappings = get_option( 'tpw_core_system_pages', array() );
		if ( ! is_array( $mappings ) ) {
			return;
		}

		$changed = false;
		foreach ( self::CORE_SYSTEM_PAGE_SLUGS as $slug ) {
			if ( ! isset( $mappings[ $slug ] ) || ! is_array( $mappings[ $slug ] ) ) {
				continue;
			}

			$page_id = isset( $mappings[ $slug ]['wp_page_id'] ) ? absint( $mappings[ $slug ]['wp_page_id'] ) : 0;
			if ( 0 >= $page_id ) {
				continue;
			}

			$page = get_post( $page_id );
			if ( ! ( $page instanceof WP_Post ) || 'page' !== $page->post_type ) {
				continue;
			}

			$stored_slug = sanitize_key( (string) get_post_meta( $page_id, '_tpw_system_page_slug', true ) );
			$provider    = sanitize_key( (string) get_post_meta( $page_id, '_tpw_system_page_plugin', true ) );
			if ( $slug !== $stored_slug || 'tpw-core' !== $provider ) {
				continue;
			}

			unset( $mappings[ $slug ] );
			$changed = true;
		}

		if ( $changed ) {
			update_option( 'tpw_core_system_pages', $mappings );
		}
	}

	/**
	 * Check a trusted plugin table without exposing a missing-table warning.
	 *
	 * @param string $table_name Trusted table name.
	 * @return bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		$previous_suppress_errors = $wpdb->suppress_errors( true );
		$columns                  = $wpdb->get_col( "DESCRIBE {$table_name}", 0 );
		$wpdb->suppress_errors( $previous_suppress_errors );

		return ! empty( $columns );
	}
}