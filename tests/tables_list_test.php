<?php
/**
 * Regression test for the All Tables admin list view.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

define( 'ROXYTABLE_VERSION', '1.0.0' );

define( 'ROXYTABLE_URL', 'http://example.test/' );

define( 'ROXYTABLE_PATH', dirname( __DIR__ ) . '/' );

function __($text, $domain = null) { return $text; }
function _e($text, $domain = null) { echo $text; }
function esc_html($text) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr($text) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url($url) { return $url; }
function admin_url($path = '') { return $path; }
function wp_nonce_url($url, $action = -1) { return $url; }
function checked($checked, $current = true, $echo = true) { $result = ( $checked == $current ) ? ' checked="checked"' : ''; if ( $echo ) { echo $result; } return $result; }
function esc_attr_e($text, $domain = null) { echo esc_attr( $text ); }
function wp_verify_nonce($nonce, $action) { return true; }
function wp_create_nonce($action) { return 'nonce'; }
function wp_nonce_field($action, $name = '_wpnonce', $referer = true, $echo = true) { if ( $echo ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce" />'; } }
function sanitize_text_field($text) { return $text; }
function sanitize_key($text) { return $text; }
function sanitize_title($text) { return $text; }
function current_user_can($cap) { return true; }
function absint($value) { return (int) $value; }
function number_format_i18n($number, $decimals = 0) { return (string) $number; }
function wp_unslash($value) { return $value; }
function esc_html__($text, $domain = null) { return $text; }
function selected($selected, $current = true, $echo = true) { $result = $selected === $current ? ' selected="selected"' : ''; if ( $echo ) { echo $result; } return $result; }
function add_query_arg($args, $uri = '') { return $uri . ( strpos( $uri, '?' ) === false ? '?' : '&' ) . http_build_query( $args ); }
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

spl_autoload_register( function ( $class ) {
	$prefix = 'RoxyTable\\Includes\\Admin\\';
	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}
	$relative = substr( $class, strlen( $prefix ) );
	$path     = dirname( __DIR__ ) . '/includes/admin/class-' . str_replace( '_', '-', strtolower( $relative ) ) . '.php';
	if ( file_exists( $path ) ) {
		require $path;
	}
} );

global $wpdb;
$wpdb = new class {
	public $prefix = 'wp_';
	public $last_error = '';
	public function prepare( $query, ...$args ) {
		return $query;
	}
	public function esc_like( $text ) {
		return addslashes( $text );
	}
	public function get_results( $query, $output = 'ARRAY_A' ) {
		return [];
	}
	public function get_var( $query ) {
		return 0;
	}
	public function query( $query ) {
		return true;
	}
};

$tables = [];
$search_query = '';
$active_filter = 'all';
$pagination_links = '<div class="pagination">1</div>';
$bulk_status = '';

$view_source = file_get_contents( dirname( __DIR__ ) . '/includes/admin/views/tables.php' );

if ( strpos( $view_source, 'name="rt_table_search"' ) === false ) {
	fwrite( STDERR, "Expected search input in tables view.\n" );
	exit( 1 );
}

if ( strpos( $view_source, 'Bulk actions' ) === false ) {
	fwrite( STDERR, "Expected bulk action bar in tables view.\n" );
	exit( 1 );
}

if ( strpos( $view_source, 'rt-table-row-actions' ) === false ) {
	fwrite( STDERR, "Expected row action container in tables view.\n" );
	exit( 1 );
}

echo "Tables view rendered with search, filters, and bulk actions.\n";
