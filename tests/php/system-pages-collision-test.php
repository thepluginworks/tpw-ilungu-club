<?php

define( 'ABSPATH', __DIR__ . '/' );

class WP_Post {
	public $ID;
	public $post_type = 'page';
	public $post_status = 'publish';
	public $post_name = '';
	public $post_title = '';
	public $post_content = '';
}

class WP_Error {
	private $code;
	public function __construct( $code ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}

$pages = array();
$meta = array();
$options = array();

function __( $text ) { return $text; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function get_option( $key, $default = false ) { global $options; return isset( $options[ $key ] ) ? $options[ $key ] : $default; }
function update_option( $key, $value ) { global $options; $options[ $key ] = $value; return true; }
function get_post( $id ) { global $pages; return isset( $pages[ $id ] ) ? $pages[ $id ] : null; }
function get_page_by_path( $slug ) { global $pages; foreach ( $pages as $page ) { if ( $page->post_name === $slug ) return $page; } return null; }
function get_post_meta( $id, $key ) { global $meta; return isset( $meta[ $id ][ $key ] ) ? $meta[ $id ][ $key ] : ''; }
function update_post_meta( $id, $key, $value ) { global $meta; $meta[ $id ][ $key ] = $value; }
function wp_insert_post( $args ) { global $pages; $page = new WP_Post(); $page->ID = count( $pages ) + 1; $page->post_name = $args['post_name']; $page->post_content = $args['post_content']; $pages[ $page->ID ] = $page; return $page->ID; }
function wp_update_post( $args ) { global $pages; $pages[ $args['ID'] ]->post_status = $args['post_status']; return $args['ID']; }
function wp_delete_post( $id ) { global $pages; unset( $pages[ $id ] ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function apply_filters( $hook, $value ) { return $value; }
function add_filter() {}
function add_action() {}
function has_action() { return false; }
function is_admin() { return false; }
function is_user_logged_in() { return false; }

require dirname( __DIR__, 2 ) . '/includes/class-tpw-core-system-pages.php';

function assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $label . "\n" );
		exit( 1 );
	}
}

TPW_Core_System_Pages::register_page( 'free-page', array( 'title' => 'Free', 'shortcode' => '[free]', 'plugin' => 'tpw-core' ) );
$free_id = TPW_Core_System_Pages::ensure_page( 'free-page' );
assert_same( 1, $free_id, 'Free canonical slug must create a page.' );
assert_same( $free_id, TPW_Core_System_Pages::ensure_page( 'free-page' ), 'Owned System Page must be reused.' );
assert_same( $free_id, TPW_Core_System_Pages::recreate_page( 'free-page' ), 'Recreate must reuse the correct managed page.' );

$unrelated = new WP_Post();
$unrelated->ID = 2;
$unrelated->post_name = 'occupied-page';
$unrelated->post_content = 'Unrelated content';
$pages[ 2 ] = $unrelated;
TPW_Core_System_Pages::register_page( 'occupied-page', array( 'title' => 'Occupied', 'shortcode' => '[occupied]', 'plugin' => 'tpw-core' ) );
$conflict = TPW_Core_System_Pages::recreate_page( 'occupied-page' );
assert_same( true, is_wp_error( $conflict ), 'An unrelated occupied slug must return a conflict.' );
assert_same( 'tpw_system_page_slug_conflict', $conflict->get_error_code(), 'Conflict must use the canonical error code.' );
assert_same( 'Unrelated content', $pages[ 2 ]->post_content, 'Conflict must not mutate the unrelated page.' );

TPW_Core_System_Pages::register_page( 'gallery', array( 'title' => 'Gallery', 'shortcode' => '[tpw_gallery_index]', 'plugin' => 'tpw-core' ) );
$gallery_free_id = TPW_Core_System_Pages::ensure_page( 'gallery' );
assert_same( 3, $gallery_free_id, 'A free Gallery path must create the registered System Page.' );
assert_same( '[tpw_gallery_index]', $pages[ $gallery_free_id ]->post_content, 'The Gallery System Page must contain the Gallery index shortcode.' );
unset( $pages[ $gallery_free_id ] );

$gallery_page = new WP_Post();
$gallery_page->ID = 4;
$gallery_page->post_name = 'gallery';
$gallery_page->post_title = 'Site-owned Gallery title';
$gallery_page->post_status = 'draft';
$gallery_page->post_content = 'Site-owned Gallery content';
$pages[ 4 ] = $gallery_page;
$gallery_result = TPW_Core_System_Pages::recreate_page( 'gallery' );
assert_same( true, is_wp_error( $gallery_result ), 'An occupied Gallery path must return a conflict.' );
assert_same( 'tpw_system_page_slug_conflict', $gallery_result->get_error_code(), 'Gallery conflicts must use the canonical error code.' );
assert_same( 'gallery', $pages[ 4 ]->post_name, 'Gallery provisioning must not rename a site-owned page.' );
assert_same( 'Site-owned Gallery title', $pages[ 4 ]->post_title, 'Gallery provisioning must not rename a site-owned title.' );
assert_same( 'draft', $pages[ 4 ]->post_status, 'Gallery provisioning must not publish a site-owned draft.' );
assert_same( 'Site-owned Gallery content', $pages[ 4 ]->post_content, 'Gallery provisioning must not mutate site-owned content.' );
assert_same( 0, TPW_Core_System_Pages::get_page_id( 'gallery' ), 'A site-owned Gallery page must not be adopted into the registry.' );

echo "system-pages collision tests passed\n";