<?php
namespace RoxyTable\Includes;

/**
 * The core plugin class.
 */
class RoxyTable {

	/**
	 * The single instance of the class.
	 *
	 * @var RoxyTable|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the single instance of the class.
	 *
	 * @return RoxyTable
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Initialization code.
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		Database::maybe_upgrade();

		if ( is_admin() ) {
			new \RoxyTable\Includes\Admin\Admin_Menu();
		}
		if ( class_exists( '\\RoxyTable\\Includes\\Frontend' ) ) {
			new \RoxyTable\Includes\Frontend();
		}
	}
}
