<?php
/** Local-only WP-CLI translation test bootstrap. */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || getenv( 'ILUNGU_BASE_URL' ) !== 'https://ilungu-club.local' ) {
	throw new RuntimeException( 'Translation test requires the configured Club Local site.' );
}

$translation_dir = getenv( 'TPW_TRANSLATION_TEST_DIR' );
if ( ! is_string( $translation_dir ) || ! is_dir( $translation_dir ) || realpath( $translation_dir ) !== $translation_dir ) {
	throw new RuntimeException( 'A real isolated translation fixture directory is required.' );
}
if ( defined( 'WP_LANG_DIR' ) ) {
	throw new RuntimeException( 'WP_LANG_DIR is already defined; refusing to alter installed catalogues.' );
}
define( 'WP_LANG_DIR', $translation_dir );

$GLOBALS['tpw_translation_test_warnings'] = array();
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		if ( 0 !== ( error_reporting() & $severity ) && preg_match( '/(?:l10n|translation|textdomain)/i', $file . ' ' . $message ) ) {
			$GLOBALS['tpw_translation_test_warnings'][] = $message . ' (' . basename( $file ) . ':' . $line . ')';
		}
		return false;
	}
);

$isolated_name = getenv( 'TPW_TRANSLATION_DB_NAME' );
if ( false !== $isolated_name && '' !== $isolated_name ) {
	$user = getenv( 'TPW_TRANSLATION_DB_USER' );
	$password = getenv( 'TPW_TRANSLATION_DB_PASSWORD' );
	$host = getenv( 'TPW_TRANSLATION_DB_HOST' );
	if ( ! preg_match( '/^ilungu_translation_[a-f0-9]{12}$/', $isolated_name ) || ! preg_match( '/^tpwtr_[a-f0-9]{12}$/', $user ) || ! $password || ! $host ) {
		throw new RuntimeException( 'Invalid isolated database configuration.' );
	}
	mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
	$parts = explode( ':', $host, 2 );
	$connection = new mysqli( $parts[0], $user, $password, $isolated_name, 0, $parts[1] ?? null );
	$identity = $connection->query( 'SELECT DATABASE(), CURRENT_USER()' )->fetch_row();
	if ( $identity[0] !== $isolated_name || $identity[1] !== $user . '@localhost' ) {
		throw new RuntimeException( 'Isolated database identity mismatch.' );
	}
	$grants = $connection->query( 'SHOW GRANTS' );
	$scoped_grants = 0;
	while ( $row = $grants->fetch_row() ) {
		if ( preg_match( '/^GRANT USAGE ON \*\.\*/', $row[0] ) ) continue;
		if ( ! str_contains( $row[0], 'ON `' . $isolated_name . '`.*' ) || str_contains( $row[0], 'GRANT OPTION' ) ) throw new RuntimeException( 'Unexpected database grant.' );
		++$scoped_grants;
	}
	if ( 1 !== $scoped_grants ) throw new RuntimeException( 'Unexpected database grant count.' );
	try {
		$connection->query( 'SELECT 1 FROM `local`.`wp_options` LIMIT 1' );
		throw new RuntimeException( 'Original Club database is accessible.' );
	} catch ( mysqli_sql_exception $denied ) {
		if ( ! in_array( $denied->getCode(), array( 1044, 1142 ), true ) ) throw $denied;
	}
	$connection->close();
	$root = getenv( 'TPW_TRANSLATION_WP_ROOT' );
	$runner_path = WP_CLI::get_runner()->config['path'] ?? '';
	if ( ! $root || realpath( $root ) !== realpath( $runner_path ) || ! is_file( $root . '/wp-config.php' ) ) {
		throw new RuntimeException( 'Isolated WordPress configuration path mismatch.' );
	}
	foreach ( array( 'DB_NAME' => $isolated_name, 'DB_USER' => $user, 'DB_PASSWORD' => $password, 'DB_HOST' => $host ) as $constant => $value ) {
		if ( defined( $constant ) ) throw new RuntimeException( 'Database configuration was set before isolation verification.' );
		define( $constant, $value );
	}
	WP_CLI::log( 'PASS: isolated database account cannot access original Club database' );
	WP_CLI::add_wp_hook( 'option_active_plugins', static function () { return array( 'tpw-ilungu-club/ilungu-club.php' ); } );
}

WP_CLI::add_wp_hook(
	'query',
	static function ( $query ) use ( $isolated_name ) {
		if ( ! $isolated_name && ! preg_match( '/^\s*(?:SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $query ) ) {
			throw new RuntimeException( 'Blocked a non-read-only database query during translation testing.' );
		}
		return $query;
	},
	PHP_INT_MIN
);
WP_CLI::add_wp_hook(
	'get_available_languages',
	static function ( $languages ) {
		return array_merge( $languages, array( 'zz_ZZ', 'zz_ZY', 'zz_ZX' ) );
	}
);
WP_CLI::add_wp_hook(
	'doing_it_wrong_run',
	static function ( $function, $message ) {
		if ( preg_match( '/tpw-ilungu-club|tpw-core/i', $message ) ) {
			$GLOBALS['tpw_translation_test_warnings'][] = $function . ': ' . $message;
		}
	}, 10, 2
);
