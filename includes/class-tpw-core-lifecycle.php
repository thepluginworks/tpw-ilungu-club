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

	const MEMBER_OPTIONS = array(
		'tpw_members_settings',
		'tpw_members_allow_deletion',
		'tpw_default_member_status',
		'tpw_members_enable_households',
		'tpw_member_change_notify_email',
		'tpw_members_default_view',
		'tpw_members_default_per_page',
		'tpw_members_default_per_page_card',
		'tpw_members_show_adult_family_on_primary_profile',
		'tpw_members_use_photos',
		'tpw_members_enable_advanced_search',
		'tpw_member_editable_fields',
		'tpw_member_viewable_fields',
		'tpw_member_profile_photo_mode',
		'tpw_member_profile_page_id',
		'tpw_member_searchable_fields',
		'tpw_member_field_download',
		'tpw_member_field_sections',
		'tpw_conditional_field',
		'tpw_conditional_fields',
		'tpw_enable_signup_debug',
		'tpw_gallery_db_version',
		'tpw_control_files_migrated_to_registry',
		'tpw_control_upload_pages_schema_repair_issue',
	);

	const MEMBER_TABLES = array(
		'tpw_members_household_member',
		'tpw_members_household',
		'tpw_member_field_visibility',
		'tpw_member_meta',
		'tpw_field_settings',
		'tpw_members',
	);

	const GALLERY_TABLES = array(
		'tpw_gallery_images',
		'tpw_galleries',
		'tpw_gallery_categories',
	);

	const UPLOAD_PAGE_TABLES = array(
		'tpw_upload_pages_files',
		'tpw_upload_categories',
		'tpw_upload_pages',
		'tpw_upload_files',
		'tpw_files',
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
		self::delete_member_data();
		self::delete_gallery_data();
		self::delete_upload_page_data();
		self::delete_core_signup_attempts();
		self::delete_noticeboard_data();
		self::delete_owned_system_pages();
		delete_option( self::DELETE_DATA_OPTION );

		return true;
	}

	/**
	 * Delete only confirmed Club configuration options.
	 *
	 * @return void
	 */
	private static function delete_removable_options() {
		foreach ( array_merge( self::REMOVABLE_OPTIONS, self::MEMBER_OPTIONS ) as $option_name ) {
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
	 * Delete pages and mappings only when a mapped page carries complete Core ownership metadata.
	 *
	 * @return void
	 */
	private static function delete_owned_system_pages() {
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

			if ( wp_delete_post( $page_id, true ) ) {
				unset( $mappings[ $slug ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( 'tpw_core_system_pages', $mappings );
		}
	}

	/**
	 * Delete records stored solely by the Club member module, not WordPress users or user meta.
	 *
	 * @return void
	 */
	private static function delete_member_data() {
		self::delete_all_rows_from_tables( self::MEMBER_TABLES );
	}

	/**
	 * Delete Gallery records while preserving WordPress attachments and uploaded files.
	 *
	 * @return void
	 */
	private static function delete_gallery_data() {
		self::delete_all_rows_from_tables( self::GALLERY_TABLES );
	}

	/**
	 * Delete Club Control Upload Pages records while retaining the additive schemas.
	 *
	 * @return void
	 */
	private static function delete_upload_page_data() {
		self::delete_all_rows_from_tables( self::UPLOAD_PAGE_TABLES );
	}

	/**
	 * Delete only signup attempts explicitly owned by the Core member-join flow.
	 *
	 * @return void
	 */
	private static function delete_core_signup_attempts() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'tpw_signup_attempts';
		if ( self::table_exists( $table_name ) ) {
			$wpdb->delete( $table_name, array( 'plugin_key' => 'tpw-core' ), array( '%s' ) );
		}
	}

	/**
	 * Delete the Core-owned Noticeboard post type and its exclusive taxonomy terms.
	 *
	 * @return void
	 */
	private static function delete_noticeboard_data() {
		global $wpdb;

		$notice_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
				'tpw_notice'
			)
		);

		foreach ( $notice_ids as $notice_id ) {
			wp_delete_post( $notice_id, true );
		}

		if ( ! taxonomy_exists( 'tpw_notice_category' ) ) {
			register_taxonomy( 'tpw_notice_category', array( 'tpw_notice' ) );
		}

		$term_ids = get_terms(
			array(
				'taxonomy'   => 'tpw_notice_category',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( is_wp_error( $term_ids ) ) {
			return;
		}

		foreach ( $term_ids as $term_id ) {
			wp_delete_term( $term_id, 'tpw_notice_category' );
		}
	}

	/**
	 * Delete every row from an explicitly Core-owned table when its schema exists.
	 *
	 * @param string[] $table_suffixes Trusted table suffixes.
	 * @return void
	 */
	private static function delete_all_rows_from_tables( $table_suffixes ) {
		global $wpdb;

		foreach ( $table_suffixes as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			if ( self::table_exists( $table_name ) ) {
				$wpdb->query( "DELETE FROM {$table_name}" );
			}
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