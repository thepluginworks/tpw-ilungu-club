<?php

define( 'ABSPATH', __DIR__ . '/' );

function sanitize_key( $value ) {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) );
}

function sanitize_text_field( $value ) {
	return (string) $value;
}

class TPW_Email_Templates_DB {
	public static function get_override( $template_key ) {
		return 'ticket-confirmation' === $template_key
			? array(
				'subject_override' => 'Custom ticket subject',
				'body_override'    => 'Custom ticket body',
				'use_logo'         => 0,
			)
			: null;
	}
}

function assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $label . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/modules/email/class-tpw-email-template-registry.php';
require dirname( __DIR__, 2 ) . '/modules/email/class-tpw-email-template-manager.php';

$template = array(
	'key'              => 'ticket-confirmation',
	'group'            => 'ilungu-tickets',
	'legacy_groups'    => array( 'tpw-flexiticket' ),
	'label'            => 'Ticket confirmation',
	'default_subject'  => 'Default subject',
	'default_body'     => 'Default body',
	'editable_subject' => true,
	'editable_body'    => true,
);

TPW_Email_Template_Registry::register_template( $template );
TPW_Email_Template_Registry::register_template( $template );

assert_same( array( 'ilungu-tickets', 'tpw-flexiticket' ), TPW_Email_Template_Registry::get_group_identities( 'tpw-flexiticket' ), 'Legacy group must resolve to its canonical group.' );
assert_same( array( 'ticket-confirmation' ), array_keys( TPW_Email_Template_Registry::get_by_group( 'tpw-flexiticket' ) ), 'Legacy group lookup must find the canonical registration.' );
assert_same( 1, count( TPW_Email_Template_Registry::all() ), 'Repeated canonical registration must not duplicate the template.' );
assert_same( array( 'ticket-confirmation' ), array_map( static function( $template ) { return $template['key']; }, TPW_Email_Template_Registry::all_grouped()['ilungu-tickets'] ), 'Canonical group must remain authoritative in admin grouping.' );
assert_same( array( 'subject' => 'Custom ticket subject', 'body' => 'Custom ticket body', 'use_logo' => false ), TPW_Email_Template_Manager::get_rendered_template( 'ticket-confirmation' ), 'Existing customized subject and body must remain intact.' );

echo "email template group compatibility tests passed\n";