<?php
/**
 * Integration and regression unit test suite for Github_Updater.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'ROXYTABLE_VERSION' ) ) {
	define( 'ROXYTABLE_VERSION', '1.0.6' );
}
if ( ! defined( 'ROXYTABLE_PLUGIN_FILE' ) ) {
	define( 'ROXYTABLE_PLUGIN_FILE', dirname( __DIR__ ) . '/roxytable.php' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

// Mock WordPress functions & global state.
$GLOBALS['wp_filters'] = [];
$GLOBALS['wp_transients'] = [];
$GLOBALS['wp_remote_get_mock'] = null;

function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wp_filters'][ $hook_name ][] = $callback;
	return true;
}

function apply_filters( $hook_name, $value, ...$args ) {
	if ( ! empty( $GLOBALS['wp_filters'][ $hook_name ] ) ) {
		foreach ( $GLOBALS['wp_filters'][ $hook_name ] as $callback ) {
			$value = call_user_func( $callback, $value, ...$args );
		}
	}
	return $value;
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $hook_name, $callback, $priority, $accepted_args );
}

function plugin_basename( $file ) {
	return 'roxytable/roxytable.php';
}

function get_site_transient( $transient ) {
	return isset( $GLOBALS['wp_transients'][ $transient ] ) ? $GLOBALS['wp_transients'][ $transient ] : false;
}

function set_site_transient( $transient, $value, $expiration = 0 ) {
	$GLOBALS['wp_transients'][ $transient ] = $value;
	return true;
}

function delete_site_transient( $transient ) {
	unset( $GLOBALS['wp_transients'][ $transient ] );
	return true;
}

function wp_remote_get( $url, $args = [] ) {
	if ( is_callable( $GLOBALS['wp_remote_get_mock'] ) ) {
		return call_user_func( $GLOBALS['wp_remote_get_mock'], $url, $args );
	}
	return [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [
			'tag_name'     => 'v1.0.6',
			'html_url'     => 'https://github.com/avibecoderz/roxy-table/releases/tag/v1.0.6',
			'published_at' => '2026-10-22T12:00:00Z',
			'body'         => 'New features and bug fixes in 1.0.6',
		] ),
	];
}

function is_wp_error( $thing ) {
	return false;
}

function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['response']['code'] ) ? $response['response']['code'] : 200;
}

function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

function esc_url_raw( $url ) { return $url; }
function esc_url( $url ) { return $url; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = null ) { return $text; }
function __( $text, $domain = null ) { return $text; }
function sanitize_text_field( $text ) { return trim( (string) $text ); }

function add_query_arg( $key_or_array, $val_or_uri = '', $uri = '' ) {
	if ( is_array( $key_or_array ) ) {
		$uri  = $val_or_uri;
		$args = $key_or_array;
	} else {
		$args = [ $key_or_array => $val_or_uri ];
	}
	return $uri . ( strpos( $uri, '?' ) === false ? '?' : '&' ) . http_build_query( $args );
}

function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
	return add_query_arg( $name, 'mock_nonce', $actionurl );
}

function admin_url( $path = '' ) {
	return 'http://example.test/wp-admin/' . $path;
}

function trailingslashit( $string ) {
	return rtrim( $string, '/\\' ) . '/';
}

require_once dirname( __DIR__ ) . '/includes/class-github-updater.php';

use RoxyTable\Includes\Github_Updater;

echo "=== Running Github_Updater Tests ===\n";

// Test 1: Instantiation & Hooks
$updater = new Github_Updater();
if ( empty( $GLOBALS['wp_filters']['pre_set_site_transient_update_plugins'] ) ) {
	fwrite( STDERR, "FAILED: pre_set_site_transient_update_plugins hook not registered.\n" );
	exit( 1 );
}
echo "PASSED: Hooks registered successfully.\n";

// Test 2: Action Links
$links = $updater->add_action_links( [] );
if ( ! isset( $links['check_update'] ) || strpos( $links['check_update'], 'Check for updates' ) === false ) {
	fwrite( STDERR, "FAILED: 'Check for updates' link missing or invalid.\n" );
	exit( 1 );
}
echo "PASSED: Action links created successfully.\n";

// Test 3: Version Comparison (No Update when release version <= current)
delete_site_transient( 'roxytable_github_release' );
$GLOBALS['wp_remote_get_mock'] = function ( $url, $args ) {
	return [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [
			'tag_name'     => 'v1.0.6', // Same as ROXYTABLE_VERSION
			'html_url'     => 'https://github.com/avibecoderz/roxy-table/releases/tag/v1.0.6',
			'published_at' => '2026-10-22T12:00:00Z',
			'body'         => 'Version 1.0.6 release',
		] ),
	];
};

$transient = (object) [ 'checked' => [ 'roxytable/roxytable.php' => '1.0.6' ], 'response' => [], 'no_update' => [] ];
$result    = $updater->check_for_update( $transient );
if ( ! empty( $result->response['roxytable/roxytable.php'] ) ) {
	fwrite( STDERR, "FAILED: Update incorrectly reported for equal version 1.0.6.\n" );
	exit( 1 );
}
if ( empty( $result->no_update['roxytable/roxytable.php'] ) ) {
	fwrite( STDERR, "FAILED: no_update transient item not populated for equal version 1.0.6.\n" );
	exit( 1 );
}
echo "PASSED: Equal version 1.0.6 correctly ignored and recorded in no_update.\n";

// Test 4: Version Comparison (Update triggered when release version > current)
delete_site_transient( 'roxytable_github_release' );
$last_request_headers = [];
$GLOBALS['wp_remote_get_mock'] = function ( $url, $args ) use ( &$last_request_headers ) {
	$last_request_headers = isset( $args['headers'] ) ? $args['headers'] : [];
	return [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [
			'tag_name'     => 'v1.0.7', // Higher than 1.0.6
			'html_url'     => 'https://github.com/avibecoderz/roxy-table/releases/tag/v1.0.7',
			'published_at' => '2026-10-22T12:00:00Z',
			'body'         => 'Version 1.0.7 release notes',
		] ),
	];
};

$result = $updater->check_for_update( $transient );
if ( empty( $result->response['roxytable/roxytable.php'] ) ) {
	fwrite( STDERR, "FAILED: Update not reported for higher version 1.0.7.\n" );
	exit( 1 );
}
echo "PASSED: Higher version 1.0.7 successfully triggers update notice.\n";

$update_item = $result->response['roxytable/roxytable.php'];
if ( $update_item->new_version !== '1.0.7' ) {
	fwrite( STDERR, "FAILED: new_version expected '1.0.7', got '{$update_item->new_version}'.\n" );
	exit( 1 );
}
echo "PASSED: Higher version 1.0.7 successfully triggers update notice.\n";

// Test 5: Check User-Agent and Authorization Headers
if ( empty( $last_request_headers['User-Agent'] ) || strpos( $last_request_headers['User-Agent'], 'RoxyTable-WordPress-Plugin/' ) === false ) {
	fwrite( STDERR, "FAILED: User-Agent header missing or invalid.\n" );
	exit( 1 );
}
echo "PASSED: User-Agent header correctly attached.\n";

// Test 6: Token Authorization for API and Package Download
define( 'ROXYTABLE_GITHUB_TOKEN', 'ghp_testtoken12345' );
delete_site_transient( 'roxytable_github_release' );
$updater->check_for_update( $transient );
if ( empty( $last_request_headers['Authorization'] ) || $last_request_headers['Authorization'] !== 'Bearer ghp_testtoken12345' ) {
	fwrite( STDERR, "FAILED: Authorization header missing or invalid with token.\n" );
	exit( 1 );
}
echo "PASSED: Secure authorization token header correctly sent for API requests.\n";

$download_req = $updater->authorize_download_request( [ 'headers' => [] ], 'https://github.com/avibecoderz/roxy-table/archive/refs/tags/v1.0.5.zip' );
if ( empty( $download_req['headers']['Authorization'] ) || $download_req['headers']['Authorization'] !== 'Bearer ghp_testtoken12345' ) {
	fwrite( STDERR, "FAILED: Authorization header missing in package download request.\n" );
	exit( 1 );
}
echo "PASSED: Secure authorization token injected into package download request.\n";

// Test 7: Plugin Information modal payload
$args = (object) [ 'slug' => 'roxytable' ];
$info = $updater->plugin_information( false, 'plugin_information', $args );
if ( ! is_object( $info ) || $info->version !== '1.0.7' || empty( $info->sections['changelog'] ) ) {
	fwrite( STDERR, "FAILED: plugin_information response incomplete or incorrect.\n" );
	exit( 1 );
}
echo "PASSED: Plugin details modal information correctly populated.\n";

// Test 8: Purge Cache
$updater->purge_cache();
if ( get_site_transient( 'roxytable_github_release' ) !== false ) {
	fwrite( STDERR, "FAILED: Cache purge did not clear site transient.\n" );
	exit( 1 );
}
echo "PASSED: Cache purge successfully cleared site transient.\n";

// Test 9: fix_extracted_directory dynamically matches plugin folder name
global $wp_filesystem;
$wp_filesystem = new class {
	public $deleted = [];
	public $moved = [];
	public function exists( $path ) { return true; }
	public function delete( $path, $recursive = false ) { $this->deleted[] = $path; return true; }
	public function move( $source, $target, $overwrite = false ) { $this->moved[] = [ 'from' => $source, 'to' => $target ]; return true; }
};

$source = '/tmp/upgrade/roxy-table-1.0.6';
$remote_source = '/tmp/upgrade/';

// Case A: Plugin installed in 'roxytable/roxytable.php'
$hook_extra = [ 'plugin' => 'roxytable/roxytable.php' ];
$res = $updater->fix_extracted_directory( $source, $remote_source, null, $hook_extra );
if ( rtrim( $res, '/\\' ) !== '/tmp/upgrade/roxytable' ) {
	fwrite( STDERR, "FAILED: Expected target directory to be '/tmp/upgrade/roxytable', got '$res'.\n" );
	exit( 1 );
}
echo "PASSED: Directory extraction correctly preserved 'roxytable' folder name.\n";

// Test 10: Fallback to /releases when /releases/latest is not the latest release
delete_site_transient( 'roxytable_github_release' );
$GLOBALS['wp_remote_get_mock'] = function( $url, $args ) {
	if ( strpos( $url, '/releases/latest' ) !== false ) {
		return [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [
				'tag_name'     => 'v1.0.4',
				'html_url'     => 'https://github.com/avibecoderz/roxy-table/releases/tag/v1.0.4',
				'published_at' => '2026-09-10T12:00:00Z',
				'body'         => 'Older release',
			] )
		];
	}
	if ( strpos( $url, '/releases' ) !== false ) {
		return [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [
				[
					'tag_name'     => 'v1.0.7',
					'html_url'     => 'https://github.com/avibecoderz/roxy-table/releases/tag/v1.0.7',
					'published_at' => '2026-10-22T12:00:00Z',
					'body'         => 'v1.0.7 release',
					'draft'        => false,
					'prerelease'   => false,
				],
				[
					'tag_name'     => 'v1.0.4',
					'html_url'     => 'https://github.com/avibecoderz/roxy-table/releases/tag/v1.0.4',
					'published_at' => '2026-09-10T12:00:00Z',
					'body'         => 'v1.0.4 release',
					'draft'        => false,
					'prerelease'   => false,
				]
			] )
		];
	}
	return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
};

$transient = (object) [ 'checked' => [ 'roxytable/roxytable.php' => '1.0.6' ], 'response' => [], 'no_update' => [] ];
$result = $updater->check_for_update( $transient );
if ( empty( $result->response['roxytable/roxytable.php'] ) || $result->response['roxytable/roxytable.php']->new_version !== '1.0.7' ) {
	fwrite( STDERR, "FAILED: Fallback to /releases failed to detect v1.0.7 when /releases/latest was stuck.\n" );
	exit( 1 );
}
echo "PASSED: Fallback to /releases successfully detected highest version when /releases/latest was desynced.\n";

// Test 11: Plugin Information accepts 'roxy-table' slug as well
$args_dashed = (object) [ 'slug' => 'roxy-table' ];
$info_dashed = $updater->plugin_information( false, 'plugin_information', $args_dashed );
if ( ! is_object( $info_dashed ) || empty( $info_dashed->version ) ) {
	fwrite( STDERR, "FAILED: plugin_information rejected 'roxy-table' slug.\n" );
	exit( 1 );
}
echo "PASSED: plugin_information accepts both 'roxy-table' and 'roxytable' slugs.\n";

echo "=== All Github_Updater Tests Passed Successfully! ===\n";
