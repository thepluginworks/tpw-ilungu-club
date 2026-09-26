<?php
/**
 * WordPress PHPUnit bootstrap for iLungu Club integration tests.
 *
 * Local database and WordPress paths belong in tests/php/.env.local.
 */

$environment_file = __DIR__ . '/.env.local';
if ( is_readable( $environment_file ) ) {
	$environment_lines = file( $environment_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	foreach ( (array) $environment_lines as $environment_line ) {
		if ( 0 === strpos( ltrim( $environment_line ), '#' ) || false === strpos( $environment_line, '=' ) ) {
			continue;
		}

		list( $name, $value ) = explode( '=', $environment_line, 2 );
		$name               = trim( $name );
		$value              = trim( $value );
		if (
			2 <= strlen( $value )
			&& (
				( '"' === $value[0] && '"' === substr( $value, -1 ) )
				|| ( "'" === $value[0] && "'" === substr( $value, -1 ) )
			)
		) {
			$value = substr( $value, 1, -1 );
		}
		if ( '' !== $name && false === getenv( $name ) ) {
			putenv( $name . '=' . $value );
			$_ENV[ $name ] = $value;
		}
	}
}

$wordpress_dir = getenv( 'TPW_TEST_WORDPRESS_DIR' );
$database_name = getenv( 'TPW_TEST_DB_NAME' );
$tests_dir     = __DIR__ . '/vendor/wp-phpunit/wp-phpunit';
$polyfills_dir = __DIR__ . '/vendor/yoast/phpunit-polyfills';

if ( ! is_string( $wordpress_dir ) || '' === $wordpress_dir || ! is_dir( $wordpress_dir ) ) {
	fwrite( STDERR, "TPW_TEST_WORDPRESS_DIR must point to a local WordPress core directory.\n" );
	exit( 1 );
}

if ( ! is_string( $database_name ) || '' === $database_name ) {
	fwrite( STDERR, "TPW_TEST_DB_NAME must name a dedicated disposable test database.\n" );
	exit( 1 );
}

if ( ! is_dir( $tests_dir ) || ! is_file( $tests_dir . '/includes/bootstrap.php' ) ) {
	fwrite( STDERR, "Install test dependencies with composer install from tests/php.\n" );
	exit( 1 );
}

if ( ! is_dir( $polyfills_dir ) ) {
	fwrite( STDERR, "Install PHPUnit Polyfills with composer install from tests/php.\n" );
	exit( 1 );
}

putenv( 'WP_TESTS_DIR=' . $tests_dir );

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills_dir );
}

require_once $tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function() {
		require dirname( __DIR__, 2 ) . '/ilungu-club.php';
	}
);

require_once $tests_dir . '/includes/bootstrap.php';