<?php
namespace RoxyTable\Includes;

/**
 * Fired during plugin activation.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates custom DB tables and stores metadata.
	 */
	public static function activate() {
		// Create/update the database tables
		Database::create_tables();

		// Save the database/plugin version
		update_option( 'roxytable_db_version', ROXYTABLE_VERSION );
	}
}
