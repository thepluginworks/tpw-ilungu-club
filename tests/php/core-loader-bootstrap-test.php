<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'TPW_CORE_PATH', dirname( __DIR__, 2 ) . '/' );

$registered_actions    = [];
$registered_filters    = [];
$registered_shortcodes = [];
$dispatched_actions    = [];

class TPW_Loader_Bootstrap_Test_DB {
	public $prefix = 'wp_';

	public function prepare( $query, $value ) {
		return $query;
	}

	public function get_var( $query ) {
		return 'wp_tpw_members';
	}
}

$wpdb = new TPW_Loader_Bootstrap_Test_DB();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $registered_actions;
	$registered_actions[] = [ $hook, $callback, $priority, $accepted_args ];
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $registered_filters;
	$registered_filters[] = [ $hook, $callback, $priority, $accepted_args ];
}

function add_shortcode( $tag, $callback ) {
	global $registered_shortcodes;
	$registered_shortcodes[ $tag ] = $callback;
}

function apply_filters( $hook, $value ) {
	return $value;
}

function do_action( $hook ) {
	global $dispatched_actions;
	$dispatched_actions[] = $hook;
}

function has_action() {
	return false;
}

function did_action() {
	return 0;
}

function is_admin() {
	return false;
}

function plugin_dir_path( $file ) {
	return dirname( $file ) . '/';
}

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) );
}

function assert_true( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

function has_registered_callback( $registrations, $callback ) {
	foreach ( $registrations as $registration ) {
		if ( $registration[1] === $callback ) {
			return true;
		}
	}

	return false;
}

require TPW_CORE_PATH . 'includes/class-tpw-core-system-pages.php';
require TPW_CORE_PATH . 'includes/tpw-core-loader.php';

assert_true( class_exists( 'TPW_Core_Activator', false ), 'Core activator must load.' );
assert_true( class_exists( 'TPW_Core_Deactivator', false ), 'Core deactivator must load.' );
assert_true( class_exists( 'TPW_Core', false ), 'Core runtime must load.' );
assert_true( class_exists( 'TPW_Core_Scheduler', false ), 'Core scheduler must load.' );
assert_true( class_exists( 'TPW_Core_Lifecycle', false ), 'Core lifecycle must load.' );
assert_true( class_exists( 'iLungu_Club_Admin_Menu', false ), 'Club administration bootstrap must load.' );
assert_true( class_exists( 'TPW_Member_Login_Shortcode', false ), 'Member login bootstrap must load.' );
assert_true( isset( $registered_shortcodes['tpw_member_login'] ), 'Member login shortcode must register.' );
assert_true( has_registered_callback( $registered_actions, [ 'iLungu_Club_Admin_Menu', 'redirect_legacy_admin_routes' ] ), 'Legacy Club route redirect must remain registered.' );
assert_true( in_array( 'tpw_module_registered', $dispatched_actions, true ), 'Module registration hook must run during loader bootstrap.' );
assert_true( ! file_exists( TPW_CORE_PATH . 'includes/class-tpw-core-updater.php' ), 'GitHub updater file must remain absent.' );
assert_true( ! class_exists( 'TPW_Core_Updater', false ), 'GitHub updater class must remain absent.' );
assert_true( ! has_registered_callback( $registered_actions, [ 'TPW_Core_Updater', 'inject_update' ] ), 'GitHub updater action must not register.' );
assert_true( ! has_registered_callback( $registered_filters, [ 'TPW_Core_Updater', 'inject_update' ] ), 'GitHub updater filter must not register.' );

assert_true( ! has_registered_callback( $registered_filters, [ 'TPW_Core_Updater', 'plugins_api' ] ), 'GitHub updater plugin-details filter must not register.' );
assert_true( ! has_registered_callback( $registered_actions, [ 'TPW_Core_Updater', 'clear_manifest_cache_on_upgrade' ] ), 'GitHub updater cache-refresh hook must not register.' );
assert_true( ! has_registered_callback( $registered_actions, [ 'TPW_Core_Updater', 'maybe_force_refresh' ] ), 'GitHub updater manual-refresh hook must not register.' );
echo "core loader bootstrap test passed\n";
