<?php
/**
 * Minimal regression test for the import preview view.
 *
 * This exercises the step-3 configuration screen and ensures it can render
 * without fatal errors when PhpSpreadsheet's Coordinate helper is available.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

define( 'ROXYTABLE_VERSION', '1.0.0' );

define( 'ROXYTABLE_URL', 'http://example.test/' );

define( 'ROXYTABLE_PATH', dirname( __DIR__ ) . '/' );

function __($text, $domain = null) {
	return $text;
}

function _e($text, $domain = null) {
	echo $text;
}

function esc_html($text) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr($text) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url($url) {
	return $url;
}

function admin_url($path = '') {
	return $path;
}

function wp_nonce_url($url, $action = -1) {
	return $url;
}

function checked($checked, $current = true, $echo = true) {
	$result = ( $checked == $current ) ? ' checked="checked"' : '';
	if ( $echo ) {
		echo $result;
	}
	return $result;
}

function wp_verify_nonce($nonce, $action) {
	return true;
}

function sanitize_text_field($text) {
	return $text;
}

function sanitize_textarea_field($text) {
	return $text;
}

function sanitize_title($title) {
	return $title;
}

function sanitize_file_name($name) {
	return $name;
}

function wp_strip_all_tags($text) {
	return $text;
}

function current_user_can($cap) {
	return true;
}

function is_admin() {
	return true;
}

function wp_create_nonce($action) {
	return 'nonce';
}

function wp_nonce_field($action, $name = '_wpnonce', $referer = true, $echo = true) {
	if ( $echo ) {
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce" />';
	}
}

function esc_attr_e($text, $domain = null) {
	echo esc_attr( $text );
}

function wp_safe_redirect($location) {
	return;
}

function get_current_user_id() {
	return 1;
}

function set_transient($key, $value, $expire) {
	return true;
}

function get_transient($key) {
	return false;
}

function delete_transient($key) {
	return true;
}

function absint($maybeint) {
	return (int) $maybeint;
}

require dirname( __DIR__ ) . '/vendor/autoload.php';

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

$step = 3;
$preview = [
	'headers'    => [ 'Name', 'Email' ],
	'rows'       => [ [ 'Ada', 'ada@example.com' ] ],
	'sheet_name' => 'Sheet1',
	'total_rows' => 1,
	'total_cols' => 2,
];
$sheet_idx = 0;
$stored = [];

ob_start();
include dirname( __DIR__ ) . '/includes/admin/views/import.php';
$output = ob_get_clean();

if ( empty( $output ) ) {
	fwrite( STDERR, "Import view did not render output.\n" );
	exit( 1 );
}

if ( strpos( $output, 'Configure Import' ) === false ) {
	fwrite( STDERR, "Import view did not contain the configuration form.\n" );
	exit( 1 );
}

echo "Import view rendered successfully.\n";
