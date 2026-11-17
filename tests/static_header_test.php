<?php
/**
 * Test to verify that table headers are static and sticky headers are removed.
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

echo "=== Verifying Static Table Headers ===\n";

// 1. Check admin.css
$admin_css = file_get_contents( ROXYTABLE_PATH . 'assets/css/admin.css' );
assert_test(
	'admin.css sets position: static !important on table headers',
	strpos( $admin_css, 'position: static !important;' ) !== false
);
assert_test(
	'admin.css contains no sticky top offset definitions',
	strpos( $admin_css, '--rt-sticky-top-offset' ) === false
);
assert_test(
	'admin.css contains no position: sticky',
	strpos( $admin_css, 'position: sticky' ) === false && strpos( $admin_css, 'position: -webkit-sticky' ) === false
);

// 2. Check class-frontend.php
$frontend_php = file_get_contents( ROXYTABLE_PATH . 'includes/class-frontend.php' );
assert_test(
	'class-frontend.php contains no rt-has-sticky-header',
	strpos( $frontend_php, 'rt-has-sticky-header' ) === false
);
assert_test(
	'class-frontend.php keeps standard overflow-x:auto',
	strpos( $frontend_php, 'style="max-width:100%;overflow-x:auto;"' ) !== false
);

// 3. Check frontend.js
$frontend_js = file_get_contents( ROXYTABLE_PATH . 'assets/js/frontend.js' );
assert_test(
	'frontend.js contains no setupStickyHeader',
	strpos( $frontend_js, 'setupStickyHeader' ) === false
);
assert_test(
	'frontend.js contains no rt-has-sticky-header',
	strpos( $frontend_js, 'rt-has-sticky-header' ) === false
);

// 4. Check preview.php
$preview_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/views/preview.php' );
assert_test(
	'preview.php contains no rt-has-sticky-header',
	strpos( $preview_php, 'rt-has-sticky-header' ) === false
);
assert_test(
	'preview.php maintains standard responsive scroll wrapper',
	strpos( $preview_php, '<div style="overflow-x:auto;">' ) !== false
);

// 5. Check edit.php & import.php toggles removed
$edit_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/views/edit.php' );
assert_test(
	'edit.php contains no rt_feat_sticky_header toggle',
	strpos( $edit_php, 'rt_feat_sticky_header' ) === false
);
$import_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/views/import.php' );
assert_test(
	'import.php contains no rt_feat_sticky_header toggle',
	strpos( $import_php, 'rt_feat_sticky_header' ) === false
);

if ( $failures === 0 ) {
	echo "=== All Static Table Header Tests Passed! ===\n";
	exit( 0 );
} else {
	echo "=== $failures Test(s) Failed! ===\n";
	exit( 1 );
}
