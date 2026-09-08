<?php
/**
 * WordPress integration coverage for Gallery System Page provisioning.
 *
 * Run from a WordPress PHPUnit bootstrap after the iLungu Club plugin is loaded.
 */

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	return;
}

class TPW_Gallery_System_Page_Menu_Preservation_Test extends WP_UnitTestCase {
	private function get_menu_snapshot( $menu_id ) {
		$items    = wp_get_nav_menu_items( $menu_id, array( 'update_post_term_cache' => false ) );
		$items    = is_array( $items ) ? $items : array();
		$snapshot = array();

		foreach ( $items as $item ) {
			$snapshot[] = array(
				'id'      => (int) $item->ID,
				'title'   => (string) $item->title,
				'url'     => (string) $item->url,
				'order'   => (int) $item->menu_order,
				'parent'  => (int) $item->menu_item_parent,
				'managed' => (string) get_post_meta( $item->ID, '_tpw_member_menu_default_key', true ),
			);
		}

		return $snapshot;
	}

	public function test_reactivation_preserves_an_assigned_custom_members_menu() {
		$this->assertTrue( class_exists( 'TPW_Core_Activator' ) );
		$this->assertTrue( class_exists( 'TPW_Core_Deactivator' ) );

		$menu_id   = wp_create_nav_menu( 'Custom Members Menu' );
		$parent_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'    => 'Custom Parent',
				'menu-item-url'      => home_url( '/custom-parent/' ),
				'menu-item-status'   => 'publish',
				'menu-item-type'     => 'custom',
				'menu-item-position' => 3,
			)
		);
		$child_id  = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'Custom Child',
				'menu-item-url'       => home_url( '/custom-child/' ),
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
				'menu-item-position'  => 7,
				'menu-item-parent-id' => $parent_id,
			)
		);
		$this->assertGreaterThan( 0, $child_id );

		$locations = array(
			'primary'         => $menu_id,
			'tpw_member_menu' => $menu_id,
		);
		set_theme_mod( 'nav_menu_locations', $locations );
		$before = $this->get_menu_snapshot( $menu_id );

		TPW_Core_Deactivator::deactivate();
		TPW_Core_Activator::activate();

		$this->assertSame( $locations, get_nav_menu_locations() );
		$this->assertSame( $before, $this->get_menu_snapshot( $menu_id ) );
		$this->assertNotContains( 'noticeboard', wp_list_pluck( $this->get_menu_snapshot( $menu_id ), 'managed' ) );
	}

	public function test_gallery_system_page_provisioning_does_not_mutate_menu_state() {
		$this->assertTrue( class_exists( 'TPW_Core_System_Pages' ) );
		$menu_id = wp_create_nav_menu( 'Gallery Preservation Menu' );
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Custom Link',
				'menu-item-url'    => home_url( '/custom-link/' ),
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			)
		);
		$this->assertGreaterThan( 0, $item_id );

		$locations = array( 'tpw_member_menu' => $menu_id );
		set_theme_mod( 'nav_menu_locations', $locations );
		$before = $this->get_menu_snapshot( $menu_id );

		TPW_Core_System_Pages::register_page(
			'gallery',
			array(
				'title'     => 'Gallery',
				'shortcode' => '[tpw_gallery_index]',
				'plugin'    => 'tpw-core',
				'required'  => 0,
			)
		);
		$page_id = TPW_Core_System_Pages::ensure_page( 'gallery' );

		$this->assertGreaterThan( 0, $page_id );
		$this->assertSame( '[tpw_gallery_index]', get_post_field( 'post_content', $page_id ) );
		$this->assertSame( $locations, get_nav_menu_locations() );
		$this->assertSame( $before, $this->get_menu_snapshot( $menu_id ) );
	}
}
