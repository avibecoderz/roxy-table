<?php
/**
 * Regression test for frontend rendering of merged spreadsheet cells.
 */

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}
function add_shortcode() {}
function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function wp_kses_post( $value ) {
	return $value;
}

require dirname( __DIR__ ) . '/includes/class-frontend.php';

$rows = [
	[ 'cells' => [ 'Merged heading', '#colspan#', 'Value A' ] ],
	[ 'cells' => [ '#rowspan#', 'Value B', 'Value C' ] ],
];
$columns = [
	[ 'name' => 'First' ],
	[ 'name' => 'Second' ],
	[ 'name' => 'Third' ],
];

$html = \RoxyTable\Includes\Frontend::render_tbody_html( $rows, $columns );
$rendered_rows = explode( '<tr ', $html );
array_shift( $rendered_rows );

if ( 2 !== count( $rendered_rows ) ) {
	fwrite( STDERR, "Expected two rendered rows.\n" );
	exit( 1 );
}

foreach ( $rendered_rows as $row_html ) {
	$row_html = substr( $row_html, 0, strpos( $row_html, '</tr>' ) );
	if ( 3 !== substr_count( $row_html, '<td ' ) ) {
		fwrite( STDERR, "Each rendered row must contain one cell per column.\n" );
		exit( 1 );
	}
}

if ( false !== strpos( $html, 'rowspan=' ) || false !== strpos( $html, 'colspan=' ) ) {
	fwrite( STDERR, "Filtered tables must not depend on merged-cell spans.\n" );
	exit( 1 );
}

echo "Frontend merged-cell alignment test passed.\n";