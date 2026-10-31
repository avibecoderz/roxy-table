<?php
/**
 * Test validation for the static company profile card.
 */

$plugin_dir = dirname( __DIR__ );
echo "=== Testing Company Profile Card ===\n";

// 1. Verify the dashboard displays the RoxyTable Pro card and no personal profile details.
$dashboard_content = file_get_contents( $plugin_dir . '/includes/admin/views/dashboard.php' );

foreach ( [ 'RoxyTable Pro', 'Advanced table features', 'More import/export options', 'Priority support' ] as $required ) {
	if ( strpos( $dashboard_content, $required ) === false ) {
		fwrite( STDERR, "FAILED: dashboard.php is missing RoxyTable Pro content: {$required}\n" );
		exit( 1 );
	}
}

foreach ( [ 'abdulpassport.jpeg', 'Abdulrahman Sulaiman', 'Live in Nigeria', 'WP Specialist', 'rt-dash-company' ] as $forbidden_content ) {
	if ( strpos( $dashboard_content, $forbidden_content ) !== false ) {
		fwrite( STDERR, "FAILED: dashboard.php still contains removed card content: {$forbidden_content}\n" );
		exit( 1 );
	}
}
echo "PASSED: dashboard.php displays RoxyTable Pro card and no personal/company profile card.\n";

// 2. Verify no upload or edit triggers exist in dashboard.php
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

// 3. Verify admin.js does not contain developer photo uploader triggers
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

// 4. Verify class-admin-menu.php does not return the personal profile image
$admin_menu_content = file_get_contents( $plugin_dir . '/includes/admin/class-admin-menu.php' );
if ( strpos( $admin_menu_content, "'avatar_url' => ROXYTABLE_URL . 'assets/images/abdulpassport.jpeg'" ) !== false ) {
	fwrite( STDERR, "FAILED: class-admin-menu.php still returns the personal profile image\n" );
	exit( 1 );
}
echo "PASSED: class-admin-menu.php does not return the personal profile image.\n";

// 5. Verify roxytable.php and class-github-updater.php display WebPress Technologies branding
$plugin_file_content = file_get_contents( $plugin_dir . '/roxytable.php' );
foreach ( [ 'Author:            WebPress Technologies', 'https://webpress.digital/' ] as $expected_header ) {
	if ( strpos( $plugin_file_content, $expected_header ) === false ) {
		fwrite( STDERR, "FAILED: roxytable.php is missing company header: {$expected_header}\n" );
		exit( 1 );
	}
}

$updater_content = file_get_contents( $plugin_dir . '/includes/class-github-updater.php' );
if ( strpos( $updater_content, 'https://webpress.digital/">WebPress Technologies</a>' ) === false ) {
	fwrite( STDERR, "FAILED: class-github-updater.php is missing WebPress Technologies author link\n" );
	exit( 1 );
}
echo "PASSED: roxytable.php and class-github-updater.php display WebPress Technologies branding.\n";

echo "=== All Company Profile Tests Passed! ===\n";
