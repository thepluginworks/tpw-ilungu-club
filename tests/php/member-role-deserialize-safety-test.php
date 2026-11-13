<?php

$stored_caps = array();
$updated_caps = array();

class TPW_Member_Roles_Deserialize_Test_DB {
	public $prefix = 'wp_';
}

$wpdb = new TPW_Member_Roles_Deserialize_Test_DB();

function get_user_meta( $user_id, $meta_key, $single ) {
	global $stored_caps;

	return isset( $stored_caps[ $user_id ] ) ? $stored_caps[ $user_id ] : '';
}

function update_user_meta( $user_id, $meta_key, $value ) {
	global $updated_caps;

	$updated_caps[ $user_id ] = $value;
}

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/modules/members/includes/class-tpw-member-roles.php';

$stored_caps[1] = serialize( array( 'editor' => true ) );
TPW_Member_Roles::ensure_member_cap( 1 );
assert_same(
	array( 'editor' => true, 'member' => true ),
	$updated_caps[1],
	'A legacy serialized capability array must preserve existing roles.'
);

$stored_caps[2] = serialize( new stdClass() );
TPW_Member_Roles::ensure_member_cap( 2 );
assert_same(
	array( 'member' => true ),
	$updated_caps[2],
	'A serialized object must not be accepted as capabilities.'
);

echo "member role deserialization safety test passed\n";
