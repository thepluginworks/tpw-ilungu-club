<?php
/**
 * Fired during plugin deactivation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TPW_Core_Deactivator {
	public static function deactivate() {
		if ( ! class_exists( 'TPW_Core_Lifecycle' ) && defined( 'TPW_CORE_PATH' ) ) {
			require_once TPW_CORE_PATH . 'includes/class-tpw-core-lifecycle.php';
		}

		if ( class_exists( 'TPW_Core_Lifecycle' ) ) {
			TPW_Core_Lifecycle::deactivate();
		}
	}
}
