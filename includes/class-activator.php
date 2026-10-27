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
		Database::maybe_upgrade();
	}
}
