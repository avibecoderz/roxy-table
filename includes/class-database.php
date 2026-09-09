<?php
namespace RoxyTable\Includes;

/**
 * Handle custom database setup and schema updates.
 */
class Database {

	/**
	 * Get prefix for RoxyTable tables.
	 *
	 * @return string
	 */
	public static function get_prefix() {
		global $wpdb;
		return $wpdb->prefix;
	}

	/**
	 * Create or update the custom database tables.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = self::get_prefix();

		$tables_table  = $prefix . 'roxy_tables';
		$columns_table = $prefix . 'roxy_columns';
		$rows_table    = $prefix . 'roxy_rows';

		// dbDelta requires exact styling:
		// - PRIMARY KEY must be followed by two spaces before properties.
		// - KEY must be followed by two spaces.
		// - Each column definition must be on its own line.
		$sql = "CREATE TABLE $tables_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text DEFAULT NULL,
			status varchar(50) NOT NULL DEFAULT 'active',
			total_rows int(11) NOT NULL DEFAULT 0,
			total_columns int(11) NOT NULL DEFAULT 0,
			settings longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY slug  (slug)
		) ENGINE=InnoDB $charset_collate;

		CREATE TABLE $columns_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			table_id bigint(20) unsigned NOT NULL,
			position int(11) NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL,
			type varchar(50) NOT NULL DEFAULT 'text',
			visible tinyint(1) NOT NULL DEFAULT 1,
			searchable tinyint(1) NOT NULL DEFAULT 1,
			sortable tinyint(1) NOT NULL DEFAULT 1,
			filterable tinyint(1) NOT NULL DEFAULT 1,
			settings longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY table_id  (table_id)
		) ENGINE=InnoDB $charset_collate;

		CREATE TABLE $rows_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			table_id bigint(20) unsigned NOT NULL,
			position int(11) NOT NULL DEFAULT 0,
			data longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY table_id  (table_id)
		) ENGINE=InnoDB $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop all custom tables.
	 */
	public static function drop_tables() {
		global $wpdb;
		$prefix = self::get_prefix();

		// Drop tables in reverse order of foreign key relationships if any,
		// rows first, then columns, then tables.
		$tables = [
			$prefix . 'roxy_rows',
			$prefix . 'roxy_columns',
			$prefix . 'roxy_tables',
		];

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}
}
