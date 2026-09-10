<?php
/**
 * Plugin Name:       RoxyTable
 * Plugin URI:        https://example.com/roxytable
 * Description:       Import Excel (.xlsx/.xls/.csv) files and display them as searchable, sortable, paginated HTML tables.
 * Version:           1.0.2
 * Author:            Oralbitstechnology
 * Author URI:        https://abdulrahmansulaiman.netlify.app
 * License:           GPL-2.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       roxytable
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define core constants.
define( 'ROXYTABLE_VERSION', '1.0.2' );
define( 'ROXYTABLE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ROXYTABLE_URL', plugin_dir_url( __FILE__ ) );
define( 'ROXYTABLE_PLUGIN_FILE', __FILE__ );

/**
 * Check for Composer dependencies.
 */
function roxytable_composer_check() {
	if ( ! file_exists( ROXYTABLE_PATH . 'vendor/autoload.php' ) ) {
		add_action( 'admin_notices', function() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php _e( 'RoxyTable requires Composer dependencies. Please run <code>composer install</code> in the plugin directory.', 'roxytable' ); ?></p>
			</div>
			<?php
		} );
		return false;
	}
	return true;
}

$composer_loaded = roxytable_composer_check();
if ( $composer_loaded ) {
	require_once ROXYTABLE_PATH . 'vendor/autoload.php';
}

/**
 * Autoloader for RoxyTable internal classes.
 * Maps namespaced classes to WordPress file naming conventions:
 * e.g., RoxyTable\Includes\Activator -> includes/class-activator.php
 */
spl_autoload_register( function( $class ) {
	// Project namespace prefix
	$prefix = 'RoxyTable\\';

	// Base directory for the namespace prefix (plugin root)
	$base_dir = ROXYTABLE_PATH;

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	// Get the relative class name
	$relative_class = substr( $class, $len );

	// Split by backslash to separate folders and class name
	$parts = explode( '\\', $relative_class );
	$class_name = array_pop( $parts );

	// Convert underscores to hyphens
	$class_name = str_replace( '_', '-', $class_name );
	// Convert CamelCase to hyphenated (e.g., RoxyTable to Roxy-Table)
	$class_name = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_name );

	// Convert to lowercase and prefix class prefix
	$file_name = 'class-' . strtolower( $class_name ) . '.php';

	// Reconstruct directory path in lowercase
	$sub_dir = '';
	if ( ! empty( $parts ) ) {
		$sub_dir = strtolower( implode( '/', $parts ) ) . '/';
	}

	$file = $base_dir . $sub_dir . $file_name;

	// If the file exists, require it
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * The code that runs during plugin activation.
 */
function activate_roxytable() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Make sure class is autoloadable
	if ( class_exists( 'RoxyTable\\Includes\\Activator' ) ) {
		RoxyTable\Includes\Activator::activate();
	}
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_roxytable() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( class_exists( 'RoxyTable\\Includes\\Deactivator' ) ) {
		RoxyTable\Includes\Deactivator::deactivate();
	}
}

register_activation_hook( __FILE__, 'activate_roxytable' );
register_deactivation_hook( __FILE__, 'deactivate_roxytable' );

/**
 * Begins execution of the plugin.
 */
function run_roxytable() {
	// Register GitHub release updates through WordPress' native updater.
	new RoxyTable\Includes\Github_Updater();

	if ( class_exists( 'RoxyTable\\Includes\\RoxyTable' ) ) {
		$plugin = RoxyTable\Includes\RoxyTable::get_instance();
		$plugin->run();
	}
}
run_roxytable();
