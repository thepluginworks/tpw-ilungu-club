<?php
/**
 * Regression coverage for shared email branding and UI-theme compatibility.
 */

define( 'ABSPATH', __DIR__ . '/' );

$options = array(
	'tpw_brand_primary_color' => '#123456',
	'tpw_ui_theme_settings'   => array(
		'font_family'    => 'Inter, sans-serif',
		'btn_bg'         => '#112233',
		'btn_text'       => '#fefefe',
		'accent_color'   => '#445566',
		'font_weight'    => '700',
		'text_transform' => 'uppercase',
		'letter_spacing' => '0.08em',
		'text_shadow'    => '1px 1px #000000',
	),
);

function add_action() {}

function add_filter() {}

function has_action() {
	return false;
}

function get_option( $key, $fallback = false ) {
	global $options;

	return isset( $options[ $key ] ) ? $options[ $key ] : $fallback;
}

function update_option( $key, $value ) {
	global $options;

	$options[ $key ] = $value;
}

function wp_unslash( $value ) {
	return $value;
}

function sanitize_hex_color( $color ) {
	if ( ! is_string( $color ) || ! preg_match( '/^#(?:[A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
		return null;
	}

	return strtolower( $color );
}

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, $args );
}

function assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $label . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/includes/admin-functions.php';
require dirname( __DIR__, 2 ) . '/includes/tpw-core-settings.php';

tpw_core_update_email_header_primary_color( array( 'brand_primary_color' => '#ABCDEF' ) );
assert_same( '#abcdef', get_option( 'tpw_brand_primary_color' ), 'A valid email header colour must be saved.' );

tpw_core_update_email_header_primary_color( array( 'brand_primary_color' => 'not-a-colour' ) );
assert_same( '#abcdef', get_option( 'tpw_brand_primary_color' ), 'An invalid email header colour must preserve the stored colour.' );

tpw_core_update_email_header_primary_color( array() );
assert_same( '#abcdef', get_option( 'tpw_brand_primary_color' ), 'Missing email header colour input must preserve the stored colour.' );

$theme = tpw_core_get_ui_theme_settings();
assert_same( 'Inter, sans-serif', $theme['font_family'], 'The stored UI font family must be displayed.' );
assert_same( '#112233', $theme['btn_bg'], 'The stored UI button background must be displayed.' );
assert_same( '#445566', $theme['accent_color'], 'The stored UI accent colour must be displayed.' );

$style_attr = tpw_core_build_ui_theme_style_attr();
foreach (
	array(
		'--tpw-font-family: Inter, sans-serif',
		'--tpw-btn-bg: #112233',
		'--tpw-btn-text: #fefefe',
		'--tpw-accent-color: #445566',
		'--tpw-font-weight: 700',
		'--tpw-text-transform: uppercase',
		'--tpw-letter-spacing: 0.08em',
		'--tpw-text-shadow: 1px 1px #000000',
	) as $expected_property
) {
	if ( false === strpos( $style_attr, $expected_property ) ) {
		fwrite( STDERR, "Missing UI theme style property: {$expected_property}\n" );
		exit( 1 );
	}
}

echo "branding settings compatibility test passed\n";
