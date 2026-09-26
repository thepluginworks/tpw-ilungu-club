<?php
/**
 * WordPress integration coverage for additive Upload Pages schema repair.
 *
 * Run from a WordPress PHPUnit bootstrap after the iLungu Club plugin is loaded.
 */

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	return;
}

class TPW_Upload_Pages_Schema_Repair_Test extends WP_UnitTestCase {
	private $table_name;

	public function set_up() {
		parent::set_up();

		global $wpdb;
		$this->table_name = $wpdb->prefix . 'tpw_upload_pages_schema_repair_test';
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );
		delete_option( 'tpw_control_upload_pages_schema_repair_issue' );
	}

	public function tear_down() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );
		delete_option( 'tpw_control_upload_pages_schema_repair_issue' );

		parent::tear_down();
	}

	public function test_repair_adds_missing_columns_without_losing_legacy_rows() {
		global $wpdb;

		$wpdb->query(
			"CREATE TABLE {$this->table_name} (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(150) NOT NULL,
				title VARCHAR(255) NOT NULL,
				description MEDIUMTEXT NULL,
				visibility LONGTEXT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY slug_unique (slug)
			) {$wpdb->get_charset_collate()}"
		);
		$wpdb->insert(
			$this->table_name,
			array(
				'slug'  => 'retained-upload-page',
				'title' => 'Retained Upload Page',
			),
			array( '%s', '%s' )
		);
		$row_id = (int) $wpdb->insert_id;

		$this->assertTrue( TPW_Control_Upload_Pages::ensure_pages_table_schema( $this->table_name ) );
		$this->assertSame( $row_id, (int) $wpdb->get_var( "SELECT id FROM {$this->table_name} WHERE slug = 'retained-upload-page'" ) );
		$this->assertSame( 'Retained Upload Page', $wpdb->get_var( "SELECT title FROM {$this->table_name} WHERE id = {$row_id}" ) );

		$columns = $wpdb->get_col( "DESCRIBE {$this->table_name}", 0 );
		$this->assertContains( 'wp_page_id', $columns );
		$this->assertContains( 'layout', $columns );
		$indexes = $wpdb->get_col( "SHOW INDEX FROM {$this->table_name}", 2 );
		$this->assertContains( 'slug_unique', $indexes );
		$this->assertContains( 'wp_page_id', $indexes );

		$this->assertTrue( TPW_Control_Upload_Pages::ensure_pages_table_schema( $this->table_name ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE slug = 'retained-upload-page'" ) );
	}

	public function test_repair_preserves_rows_when_schema_is_already_complete() {
		global $wpdb;

		$this->assertTrue( TPW_Control_Upload_Pages::ensure_pages_table_schema( $this->table_name ) );
		$wpdb->insert(
			$this->table_name,
			array(
				'slug'       => 'complete-upload-page',
				'title'      => 'Complete Upload Page',
				'wp_page_id' => 123,
				'layout'     => 'cards',
			),
			array( '%s', '%s', '%d', '%s' )
		);
		$row_id = (int) $wpdb->insert_id;

		$this->assertTrue( TPW_Control_Upload_Pages::ensure_pages_table_schema( $this->table_name ) );
		$row = $wpdb->get_row( "SELECT id, wp_page_id, layout FROM {$this->table_name} WHERE id = {$row_id}" );
		$this->assertSame( $row_id, (int) $row->id );
		$this->assertSame( 123, (int) $row->wp_page_id );
		$this->assertSame( 'cards', $row->layout );
	}

	public function test_repair_preserves_malformed_identity_schema_without_recreating_it() {
		global $wpdb;

		$wpdb->query(
			"CREATE TABLE {$this->table_name} (
				legacy_reference VARCHAR(150) NOT NULL,
				legacy_payload TEXT NULL
			) {$wpdb->get_charset_collate()}"
		);
		$wpdb->insert(
			$this->table_name,
			array(
				'legacy_reference' => 'preserve-malformed-row',
				'legacy_payload'   => 'Customer data must remain intact',
			),
			array( '%s', '%s' )
		);
		$before_schema = $wpdb->get_var( "SHOW CREATE TABLE {$this->table_name}", 1 );

		$this->assertFalse( TPW_Control_Upload_Pages::ensure_pages_table_schema( $this->table_name ) );
		$this->assertSame( 'missing_identity_columns', get_option( 'tpw_control_upload_pages_schema_repair_issue' ) );
		$this->assertSame( $before_schema, $wpdb->get_var( "SHOW CREATE TABLE {$this->table_name}", 1 ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE legacy_reference = 'preserve-malformed-row'" ) );
		$this->assertSame( 'Customer data must remain intact', $wpdb->get_var( "SELECT legacy_payload FROM {$this->table_name} WHERE legacy_reference = 'preserve-malformed-row'" ) );

		$this->assertFalse( TPW_Control_Upload_Pages::ensure_pages_table_schema( $this->table_name ) );
		$this->assertSame( $before_schema, $wpdb->get_var( "SHOW CREATE TABLE {$this->table_name}", 1 ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE legacy_reference = 'preserve-malformed-row'" ) );
	}

	public function test_runtime_source_contains_no_drop_table_statement() {
		$source = file_get_contents( dirname( __DIR__, 3 ) . '/modules/tpw-control/class-tpw-control-upload-pages.php' );

		$this->assertIsString( $source );
		$this->assertSame( 0, preg_match( '/DROP\\s+TABLE/i', $source ) );
	}
}