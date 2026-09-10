<?php
/**
 * Test validation for the hardcoded static developer info asset.
 */

$plugin_dir = dirname( __DIR__ );
$image_path = $plugin_dir . '/assets/images/abdulpassport.jpeg';

echo "=== Testing Static Developer Info Asset ===\n";

// 1. Verify file exists and is readable
if ( ! file_exists( $image_path ) ) {
	fwrite( STDERR, "FAILED: Image file does not exist at {$image_path}\n" );
	exit( 1 );
}

$file_size = filesize( $image_path );
if ( $file_size < 1000 ) {
	fwrite( STDERR, "FAILED: Image file is suspiciously small ({$file_size} bytes)\n" );
	exit( 1 );
}
echo "PASSED: abdulpassport.jpeg exists ({$file_size} bytes).\n";

// 2. Verify dashboard.php template references the static asset
$dashboard_content = file_get_contents( $plugin_dir . '/includes/admin/views/dashboard.php' );

if ( strpos( $dashboard_content, 'assets/images/abdulpassport.jpeg' ) === false ) {
	fwrite( STDERR, "FAILED: dashboard.php does not reference assets/images/abdulpassport.jpeg\n" );
	exit( 1 );
}
echo "PASSED: dashboard.php references assets/images/abdulpassport.jpeg directly.\n";

// 3. Verify no upload or edit triggers exist in dashboard.php
$forbidden_in_dashboard = [
	'rt-change-photo-btn',
	'rt-edit-dev-profile-btn',
	'rt-dev-avatar-overlay',
	'Change Photo',
	'Click to change photo',
];

foreach ( $forbidden_in_dashboard as $needle ) {
	if ( strpos( $dashboard_content, $needle ) !== false ) {
		fwrite( STDERR, "FAILED: dashboard.php still contains forbidden edit control: {$needle}\n" );
		exit( 1 );
	}
}
echo "PASSED: dashboard.php contains no dynamic edit or upload controls for the developer photo.\n";

// 4. Verify admin.js does not contain developer photo uploader triggers
$js_content = file_get_contents( $plugin_dir . '/assets/js/admin.js' );
$forbidden_in_js = [
	'rt-change-photo-btn',
	'rt-edit-dev-profile-btn',
	'Select Developer Profile Image',
];

foreach ( $forbidden_in_js as $needle ) {
	if ( strpos( $js_content, $needle ) !== false ) {
		fwrite( STDERR, "FAILED: admin.js still contains developer photo uploader handler: {$needle}\n" );
		exit( 1 );
	}
}
echo "PASSED: admin.js has no developer photo uploader triggers.\n";

// 5. Verify class-admin-menu.php does not allow modifying the dev avatar option
$admin_menu_content = file_get_contents( $plugin_dir . '/includes/admin/class-admin-menu.php' );
if ( strpos( $admin_menu_content, "update_option( 'roxytable_dev_avatar'" ) !== false ) {
	fwrite( STDERR, "FAILED: class-admin-menu.php still allows updating roxytable_dev_avatar option\n" );
	exit( 1 );
}
echo "PASSED: class-admin-menu.php does not allow updating developer avatar option.\n";

echo "=== All Static Developer Info Asset Tests Passed! ===\n";
