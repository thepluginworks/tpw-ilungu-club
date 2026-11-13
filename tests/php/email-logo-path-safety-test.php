<?php

$test_root = sys_get_temp_dir() . '/tpw-email-logo-path-safety-' . uniqid( '', true );
$uploads_dir = $test_root . '/uploads';
$outside_dir = $test_root . '/outside';

if ( ! mkdir( $uploads_dir, 0777, true ) || ! mkdir( $outside_dir, 0777, true ) ) {
	fwrite( STDERR, "Unable to create test directories.\n" );
	exit( 1 );
}

file_put_contents( $uploads_dir . '/logo.jpg', 'logo' );
file_put_contents( $outside_dir . '/secret.jpg', 'secret' );
symlink( $outside_dir . '/secret.jpg', $uploads_dir . '/linked-secret.jpg' );

function wp_get_upload_dir() {
	global $uploads_dir;

	return array(
		'baseurl' => 'https://club.test/uploads',
		'basedir' => $uploads_dir,
	);
}

function trailingslashit( $value ) {
	return rtrim( $value, '/' ) . '/';
}

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/modules/email/class-tpw-email-logo-helper.php';

$method = new ReflectionMethod( 'TPW_Email_Logo_Helper', 'resolve_local_path' );
$method->setAccessible( true );

assert_same(
	realpath( $uploads_dir . '/logo.jpg' ),
	$method->invoke( null, 'https://club.test/uploads/logo.jpg' ),
	'A valid upload must remain available.'
);
assert_same(
	'',
	$method->invoke( null, 'https://club.test/uploads.evil/logo.jpg' ),
	'A matching URL prefix outside the uploads URL must be rejected.'
);
assert_same(
	'',
	$method->invoke( null, 'https://club.test/uploads/../outside/secret.jpg' ),
	'A traversal path outside uploads must be rejected.'
);
assert_same(
	'',
	$method->invoke( null, 'https://club.test/uploads/linked-secret.jpg' ),
	'An uploads symlink that escapes the uploads root must be rejected.'
);

unlink( $uploads_dir . '/logo.jpg' );
unlink( $uploads_dir . '/linked-secret.jpg' );
unlink( $outside_dir . '/secret.jpg' );
rmdir( $uploads_dir );
rmdir( $outside_dir );
rmdir( $test_root );

echo "email logo path safety test passed\n";
