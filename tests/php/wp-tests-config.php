<?php
/**
 * WordPress PHPUnit database configuration.
 *
 * Values are supplied only through tests/php/.env.local or the process environment.
 */

define( 'DB_NAME', getenv( 'TPW_TEST_DB_NAME' ) ?: '' );
define( 'DB_USER', getenv( 'TPW_TEST_DB_USER' ) ?: '' );
define( 'DB_PASSWORD', getenv( 'TPW_TEST_DB_PASSWORD' ) ?: '' );
define( 'DB_HOST', getenv( 'TPW_TEST_DB_HOST' ) ?: '' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = getenv( 'TPW_TEST_TABLE_PREFIX' ) ?: 'wptests_';

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'iLungu Club PHPUnit' );
define( 'WP_PHP_BINARY', PHP_BINARY );
define( 'ABSPATH', rtrim( (string) getenv( 'TPW_TEST_WORDPRESS_DIR' ), '/\\' ) . '/' );