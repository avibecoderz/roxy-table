<?php
namespace RoxyTable\Includes;

/**
 * Fired during plugin deactivation.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Performs cleanup tasks on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
