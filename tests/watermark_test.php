<?php
/**
 * Test for Table Watermark feature.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'ROXYTABLE_PATH', dirname( __DIR__ ) . '/' );
define( 'ROXYTABLE_URL', 'http://localhost/wp-content/plugins/roxy-table/' );
define( 'ROXYTABLE_VERSION', '1.0.13' );

$failures = 0;
function assert_test( $desc, $condition, $info = '' ) {
	global $failures;
	if ( $condition ) {
		echo "PASSED: $desc\n";
	} else {
		$failures++;
		echo "FAILED: $desc (" . (string) $info . ")\n";
	}
}

echo "=== Testing RoxyTable Table Watermark Feature ===\n";

// 1. Verify CSS styles
$admin_css = file_get_contents( ROXYTABLE_PATH . 'assets/css/admin.css' );
assert_test(
	'admin.css defines .rt-has-watermark',
	strpos( $admin_css, '.rt-has-watermark' ) !== false
);
assert_test(
	'admin.css defines .rt-watermark-overlay with pointer-events: none',
	strpos( $admin_css, '.rt-watermark-overlay' ) !== false &&
	strpos( $admin_css, 'pointer-events: none !important;' ) !== false
);
assert_test(
	'admin.css defines .rt-watermark-preview-box',
	strpos( $admin_css, '.rt-watermark-preview-box' ) !== false
);

// 2. Verify Frontend PHP rendering
$frontend_php = file_get_contents( ROXYTABLE_PATH . 'includes/class-frontend.php' );
assert_test(
	'class-frontend.php extracts watermark_url from settings',
	strpos( $frontend_php, "\$watermark_url     = ! empty( \$settings['watermark_url'] )" ) !== false
);
assert_test(
	'class-frontend.php adds rt-has-watermark class to embed',
	strpos( $frontend_php, "\$watermark_class = \$watermark_url ? ' rt-has-watermark' : '';" ) !== false
);
assert_test(
	'class-frontend.php renders .rt-watermark-overlay',
	strpos( $frontend_php, 'class="rt-watermark-overlay"' ) !== false
);

// 3. Verify Preview PHP rendering
$preview_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/views/preview.php' );
assert_test(
	'preview.php extracts watermark_url from settings',
	strpos( $preview_php, "\$watermark_url     = ! empty( \$settings['watermark_url'] )" ) !== false
);
assert_test(
	'preview.php renders .rt-watermark-overlay',
	strpos( $preview_php, 'class="rt-watermark-overlay"' ) !== false
);

// 4. Verify Edit view controls
$edit_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/views/edit.php' );
assert_test(
	'edit.php contains Watermark options block',
	strpos( $edit_php, 'id="rt-watermark-options"' ) !== false
);
assert_test(
	'edit.php contains Watermark preview box',
	strpos( $edit_php, 'id="rt-watermark-preview-box"' ) !== false
);
assert_test(
	'edit.php contains Select Watermark button',
	strpos( $edit_php, 'id="rt-select-watermark-btn"' ) !== false
);
assert_test(
	'edit.php contains Watermark opacity slider',
	strpos( $edit_php, 'id="rt-watermark-opacity-range"' ) !== false
);
assert_test(
	'edit.php contains Watermark size dropdown',
	strpos( $edit_php, 'id="rt-watermark-size"' ) !== false
);

// 5. Verify admin.js Media Uploader integration
$admin_js = file_get_contents( ROXYTABLE_PATH . 'assets/js/admin.js' );
assert_test(
	'admin.js defines initWatermarkControls',
	strpos( $admin_js, 'function initWatermarkControls()' ) !== false
);
assert_test(
	'admin.js attaches WordPress Media Library to watermark picker',
	strpos( $admin_js, "title: 'Select Table Watermark Image'" ) !== false
);

// 6. Verify Settings persistence in class-admin-menu.php
$menu_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/class-admin-menu.php' );
assert_test(
	'class-admin-menu.php saves watermark_url',
	strpos( $menu_php, "\$settings_partial['watermark_url']" ) !== false
);
assert_test(
	'class-admin-menu.php saves watermark_opacity',
	strpos( $menu_php, "\$settings_partial['watermark_opacity']" ) !== false
);
assert_test(
	'class-admin-menu.php saves watermark_size',
	strpos( $menu_php, "\$settings_partial['watermark_size']" ) !== false
);

if ( $failures === 0 ) {
	echo "=== All Watermark Tests Passed Successfully! ===\n";
	exit( 0 );
} else {
	echo "=== $failures Test(s) Failed! ===\n";
	exit( 1 );
}
