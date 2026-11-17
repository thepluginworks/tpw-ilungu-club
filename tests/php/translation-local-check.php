<?php
/** Executed by WP-CLI against the configured Club Local runtime. */

function tpw_translation_check( $label, $actual, $expected ) {
	if ( $actual !== $expected ) {
		WP_CLI::error( $label . ': expected ' . wp_json_encode( $expected ) . ', got ' . wp_json_encode( $actual ) );
	}
	WP_CLI::log( 'PASS: ' . $label );
}

if ( untrailingslashit( home_url() ) !== 'https://ilungu-club.local' ) {
	WP_CLI::error( 'Unexpected Local site URL.' );
}
if ( ! defined( 'TPW_CORE_PATH' ) || realpath( TPW_CORE_PATH ) !== realpath( dirname( __DIR__, 2 ) ) ) {
	WP_CLI::error( 'The active Club plugin is not this working copy.' );
}
if ( ! current_user_can( 'manage_options' ) ) {
	WP_CLI::error( 'The configured administrator account is required.' );
}
if ( ! function_exists( 'tpw_member_profile_get_builtin_sections' ) ) {
	WP_CLI::error( 'Member profile integration is unavailable.' );
}
WP_CLI::log( 'PASS: Club bootstrap, configured administrator and Local working copy' );

$original_locale = determine_locale();
foreach ( array(
	array( 'zz_ZZ', 'Canonical admin', 'Canonical profile', 'Legacy admin', 'canonical precedence' ),
	array( 'zz_ZY', 'Legacy admin', 'Legacy profile', 'Legacy admin', 'legacy fallback' ),
	array( 'zz_ZX', 'Manage Members', 'My Profile', 'Manage Members', 'source fallback' ),
) as $case ) {
	list( $locale, $admin, $member, $legacy, $label ) = $case;
	tpw_translation_check( $label . ' locale switch', switch_to_locale( $locale ), true );
	tpw_translation_check( $label . ' administrator string', __( 'Manage Members', 'tpw-ilungu-club' ), $admin );
	$sections = tpw_member_profile_get_builtin_sections();
	tpw_translation_check( $label . ' member profile label', $sections['profile']['label'], $member );
	tpw_translation_check( $label . ' legacy consumer domain', __( 'Manage Members', 'tpw-core' ), $legacy );
	tpw_translation_check( $label . ' locale restore', restore_previous_locale(), $original_locale );
}

tpw_translation_check( 'translation-related PHP warnings', $GLOBALS['tpw_translation_test_warnings'], array() );
WP_CLI::log( getenv( 'TPW_TRANSLATION_DB_NAME' ) ? 'PASS: bootstrap used isolated database credentials' : 'PASS: no non-read-only database queries permitted during bootstrap or assertions' );
