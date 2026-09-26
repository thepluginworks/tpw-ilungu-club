<?php
/**
 * Uninstall handler for TPW Core.
 *
 * Removes plugin data only if the user opted-in via settings.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-tpw-core-lifecycle.php';

TPW_Core_Lifecycle::uninstall();
