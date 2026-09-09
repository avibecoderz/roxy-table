<?php
ob_start();
include dirname( __DIR__ ) . '/includes/admin/views/tables.php';
$out = ob_get_clean();
$pos = strpos( $out, 'Bulk actions' );
if ( false !== $pos ) {
	echo substr( $out, max( 0, $pos - 200 ), 800 );
} else {
	echo 'Bulk actions marker not found';
}
