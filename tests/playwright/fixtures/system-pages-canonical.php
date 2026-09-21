<?php
/**
 * Local-only fixture for the canonical Club Management System Page smoke test.
 *
 * It migrates only the known legacy FlexiClub fixture page through WordPress and
 * Core APIs, retaining its page ID rather than creating a duplicate page.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'ILUNGU_CLUB_PLAYWRIGHT_TESTING' ) || ! ILUNGU_CLUB_PLAYWRIGHT_TESTING ) {
	return;
}

if (
	( ! isset( $_GET['tpw_club_playwright_system_pages'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['tpw_club_playwright_system_pages'] ) ) )
	&& ( ! isset( $_COOKIE['tpw_club_playwright_system_pages'] ) || '1' !== sanitize_text_field( wp_unslash( $_COOKIE['tpw_club_playwright_system_pages'] ) ) )
) {
	return;
}

add_action(
	'init',
	static function() {
		if ( ! class_exists( 'TPW_Core_System_Pages' ) ) {
			return;
		}

		$overrides      = get_option( 'tpw_core_system_pages', [] );
		$legacy_id      = is_array( $overrides ) && isset( $overrides['flexiclub']['wp_page_id'] ) ? absint( $overrides['flexiclub']['wp_page_id'] ) : 0;
		$legacy_page    = $legacy_id > 0 ? get_post( $legacy_id ) : null;
		$canonical_page = get_page_by_path( 'club-management', OBJECT, 'page' );
		$legacy_page_is_fixture = $legacy_page instanceof WP_Post
			&& 'page' === $legacy_page->post_type
			&& 'flexiclub' === $legacy_page->post_name
			&& 'publish' === $legacy_page->post_status
			&& false !== strpos( (string) $legacy_page->post_content, '[flexiclub]' );

		if (
			$legacy_page_is_fixture
			&& $canonical_page instanceof WP_Post
			&& (int) $canonical_page->ID !== (int) $legacy_page->ID
			&& 'Club Management' === (string) $canonical_page->post_title
			&& '[flexiclub]' === trim( (string) $canonical_page->post_content )
			&& 'club-management' === get_post_meta( $canonical_page->ID, '_tpw_system_page_slug', true )
			&& 'tpw-core' === get_post_meta( $canonical_page->ID, '_tpw_system_page_plugin', true )
		) {
			wp_delete_post( $canonical_page->ID, true );
			$canonical_page = null;
		}

		if ( $legacy_page_is_fixture && ! ( $canonical_page instanceof WP_Post ) ) {
			wp_update_post(
				[
					'ID'         => (int) $legacy_page->ID,
					'post_name'  => 'club-management',
					'post_title' => 'Club Management',
				],
				true
			);
			update_post_meta( $legacy_page->ID, '_tpw_system_page_slug', 'club-management' );
		}

		TPW_Core_System_Pages::ensure_page( 'club-management' );
	},
	99
);