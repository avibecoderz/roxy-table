<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : 'roxytable';

$page_titles = [
	'roxytable'           => __( 'Dashboard', 'roxytable' ),
	'roxytable-tables'    => __( 'All Tables', 'roxytable' ),
	'roxytable-import'    => __( 'Import Excel', 'roxytable' ),
	'roxytable-create'    => __( 'Create Table', 'roxytable' ),
	'roxytable-templates' => __( 'Templates', 'roxytable' ),
	'roxytable-settings'  => __( 'Settings', 'roxytable' ),
	'roxytable-help'      => __( 'Help', 'roxytable' ),
];

$page_title = isset( $page_titles[ $current_page ] ) ? $page_titles[ $current_page ] : __( 'Dashboard', 'roxytable' );
?>
<div class="roxytable-admin-wrap">
	<!-- Main Content Area (sidebar removed to keep WordPress native menu) -->
	<div class="roxytable-main" style="margin-left:0;">
		<!-- Topbar -->
		<header class="roxytable-topbar">
			<div class="roxytable-breadcrumb">
				<span class="breadcrumb-root">RoxyTable</span>
				<span class="breadcrumb-separator">&rarr;</span>
				<span class="breadcrumb-current"><?php echo esc_html( $page_title ); ?></span>
			</div>
			<div class="roxytable-search-wrapper">
				<span class="search-icon">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
					</svg>
				</span>
				<input type="search" placeholder="Search tables..." class="roxytable-global-search">
			</div>
		</header>

		<!-- Page Content Body -->
		<main class="roxytable-content">
			<?php
			if ( ! empty( $view_file ) && file_exists( $view_file ) ) {
				include $view_file;
			}
			?>
		</main>
	</div>
</div>
