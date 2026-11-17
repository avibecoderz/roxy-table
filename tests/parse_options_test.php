<?php
/**
 * Test for Table Parse Options feature (Section 2: Parse Options).
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'ROXYTABLE_PATH', dirname( __DIR__ ) . '/' );
define( 'ROXYTABLE_URL', 'http://localhost/wp-content/plugins/roxy-table/' );
define( 'ROXYTABLE_VERSION', '1.0.13' );

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

require_once ROXYTABLE_PATH . 'includes/admin/class-table-repository.php';

use RoxyTable\Includes\Admin\Table_Repository;

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

echo "=== Testing RoxyTable Section 2: Parse Options Feature ===\n";

// 1. Test is_row_empty
$empty_row1 = [ '', '   ', "\t\n", '' ];
$empty_row2 = [ '#rowspan#', '#colspan#', '' ];
$non_empty_row = [ '', 'Hello World', '' ];

assert_test(
	'is_row_empty detects completely blank cells as empty',
	Table_Repository::is_row_empty( $empty_row1 ) === true
);
assert_test(
	'is_row_empty detects merged placeholder cells without text as empty',
	Table_Repository::is_row_empty( $empty_row2 ) === true
);
assert_test(
	'is_row_empty detects row with text content as non-empty',
	Table_Repository::is_row_empty( $non_empty_row ) === false
);

// 2. Test apply_parse_options with remove_empty_rows
$columns = [
	[ 'id' => 1, 'position' => 0, 'name' => 'Name', 'type' => 'text' ],
	[ 'id' => 2, 'position' => 1, 'name' => 'Score', 'type' => 'number' ],
];
$rows = [
	[ 'id' => 101, 'position' => 0, 'cells' => [ 'Alice', '95' ] ],
	[ 'id' => 102, 'position' => 1, 'cells' => [ '', '   ' ] ], // empty row
	[ 'id' => 103, 'position' => 2, 'cells' => [ 'Bob', '88' ] ],
	[ 'id' => 104, 'position' => 3, 'cells' => [ '#rowspan#', '' ] ], // empty row
];

$res_cleaned = Table_Repository::apply_parse_options( $columns, $rows, [
	'remove_empty_rows' => true,
	'remove_empty_cols' => false,
	'first_row_headers' => true,
] );
assert_test(
	'apply_parse_options drops empty rows when remove_empty_rows is true',
	count( $res_cleaned['rows'] ) === 2 &&
	$res_cleaned['rows'][0]['cells'][0] === 'Alice' &&
	$res_cleaned['rows'][1]['cells'][0] === 'Bob'
);

$res_kept = Table_Repository::apply_parse_options( $columns, $rows, [
	'remove_empty_rows' => false,
	'remove_empty_cols' => false,
	'first_row_headers' => true,
] );
assert_test(
	'apply_parse_options preserves empty rows when remove_empty_rows is false',
	count( $res_kept['rows'] ) === 4
);

// 3. Test apply_parse_options with remove_empty_cols
$columns_with_empty = [
	[ 'id' => 1, 'position' => 0, 'name' => 'Name', 'type' => 'text' ],
	[ 'id' => 2, 'position' => 1, 'name' => 'Column B', 'type' => 'text' ], // empty col with default name
	[ 'id' => 3, 'position' => 2, 'name' => 'Age', 'type' => 'number' ],
];
$rows_with_empty_col = [
	[ 'id' => 201, 'position' => 0, 'cells' => [ 'Alice', '', '25' ] ],
	[ 'id' => 202, 'position' => 1, 'cells' => [ 'Bob', '   ', '30' ] ],
];

$res_col_cleaned = Table_Repository::apply_parse_options( $columns_with_empty, $rows_with_empty_col, [
	'remove_empty_rows' => true,
	'remove_empty_cols' => true,
	'first_row_headers' => true,
] );
assert_test(
	'apply_parse_options drops empty column when remove_empty_cols is true',
	count( $res_col_cleaned['columns'] ) === 2 &&
	$res_col_cleaned['columns'][0]['name'] === 'Name' &&
	$res_col_cleaned['columns'][1]['name'] === 'Age' &&
	$res_col_cleaned['rows'][0]['cells'] === [ 'Alice', '25' ] &&
	$res_col_cleaned['rows'][1]['cells'] === [ 'Bob', '30' ]
);

// 4. Test apply_parse_options with first_row_headers = false
$res_no_headers = Table_Repository::apply_parse_options( $columns, [
	[ 'id' => 301, 'position' => 0, 'cells' => [ 'Alice', '95' ] ],
], [
	'first_row_headers' => false,
	'remove_empty_rows' => true,
	'remove_empty_cols' => false,
] );
assert_test(
	'apply_parse_options generates generic headers Column A, Column B when first_row_headers is false',
	$res_no_headers['columns'][0]['name'] === 'Column A' &&
	$res_no_headers['columns'][1]['name'] === 'Column B'
);
assert_test(
	'apply_parse_options prepends original header names as row 0 in table body when first_row_headers is false',
	count( $res_no_headers['rows'] ) === 2 &&
	$res_no_headers['rows'][0]['cells'] === [ 'Name', 'Score' ] &&
	$res_no_headers['rows'][1]['cells'] === [ 'Alice', '95' ]
);

// 5. Test auto_detect_types = false forces text types
$columns_types = [
	[ 'id' => 1, 'position' => 0, 'name' => 'Date', 'type' => 'date' ],
	[ 'id' => 2, 'position' => 1, 'name' => 'Price', 'type' => 'number' ],
];
$res_no_detect = Table_Repository::apply_parse_options( $columns_types, [], [
	'auto_detect_types' => false,
] );
assert_test(
	'apply_parse_options sets columns to text when auto_detect_types is false',
	$res_no_detect['columns'][0]['type'] === 'text' &&
	$res_no_detect['columns'][1]['type'] === 'text'
);

// 6. Verify class-frontend.php and preview.php call apply_parse_options
$frontend_php = file_get_contents( ROXYTABLE_PATH . 'includes/class-frontend.php' );
assert_test(
	'class-frontend.php calls apply_parse_options',
	strpos( $frontend_php, 'Table_Repository::apply_parse_options' ) !== false
);

$preview_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/views/preview.php' );
assert_test(
	'preview.php calls apply_parse_options',
	strpos( $preview_php, 'Table_Repository::apply_parse_options' ) !== false
);

// 7. Verify class-admin-menu.php calls sync_column_types and clean_empty_rows
$admin_menu_php = file_get_contents( ROXYTABLE_PATH . 'includes/admin/class-admin-menu.php' );
assert_test(
	'class-admin-menu.php synchronizes column types and cleans empty rows on save',
	strpos( $admin_menu_php, 'Table_Repository::sync_column_types' ) !== false &&
	strpos( $admin_menu_php, 'Table_Repository::clean_empty_rows' ) !== false
);

if ( 0 === $failures ) {
	echo "=== All Parse Options Tests Passed Successfully! ===\n";
	exit( 0 );
} else {
	echo "=== $failures Test(s) Failed ===\n";
	exit( 1 );
}
