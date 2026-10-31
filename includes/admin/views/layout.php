<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'roxytable';
$current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$global_search  = isset( $_GET['rt_table_search'] ) ? sanitize_text_field( wp_unslash( $_GET['rt_table_search'] ) ) : '';

$page_meta = [
	'roxytable'           => [
		'title'    => __( 'Dashboard', 'roxytable' ),
		'subtitle' => __( 'An overview of your tables, records and recent activity.', 'roxytable' ),
	],
	'roxytable-tables'    => [
		'title'    => __( 'All Tables', 'roxytable' ),
		'subtitle' => __( 'View, search, filter and manage all of your tables.', 'roxytable' ),
	],
	'roxytable-import'    => [
		'title'    => __( 'Import Excel or CSV', 'roxytable' ),
		'subtitle' => __( 'Upload spreadsheets and convert them into interactive WordPress tables.', 'roxytable' ),
	],
	'roxytable-create'    => [
		'title'    => __( 'Create Table', 'roxytable' ),
		'subtitle' => __( 'Start with a blank table and configure columns and rows manually.', 'roxytable' ),
	],
	'roxytable-templates' => [
		'title'    => __( 'Design Templates', 'roxytable' ),
		'subtitle' => __( 'Choose a pre-styled theme layout for your frontend tables.', 'roxytable' ),
	],
	'roxytable-settings'  => [
		'title'    => __( 'Settings', 'roxytable' ),
		'subtitle' => __( 'Configure global defaults for RoxyTable frontend tables and data handling.', 'roxytable' ),
	],
	'roxytable-help'      => [
		'title'    => __( 'Documentation & Help', 'roxytable' ),
		'subtitle' => __( 'Guides, shortcode reference, FAQs and direct support.', 'roxytable' ),
	],
];

if ( 'roxytable-tables' === $current_page && 'edit' === $current_action ) {
	$active_meta = [
		'title'    => __( 'Edit Table', 'roxytable' ),
		'subtitle' => __( 'Update table metadata, frontend options and cell data.', 'roxytable' ),
	];
} elseif ( 'roxytable-tables' === $current_page && 'preview' === $current_action ) {
	$active_meta = [
		'title'    => __( 'Preview Table', 'roxytable' ),
		'subtitle' => __( 'Live preview of how this table renders on the frontend.', 'roxytable' ),
	];
} else {
	$active_meta = isset( $page_meta[ $current_page ] ) ? $page_meta[ $current_page ] : $page_meta['roxytable'];
}
?>
<div class="roxytable-admin-wrap">
	<!-- Main Content Area (uses native WordPress admin menu) -->
	<div class="roxytable-main" style="margin-left:0;">
		<!-- Page Content Body -->
		<main class="roxytable-content">
			<div class="rt-dash">
				<!-- Shared Branded Header across all RoxyTable pages -->
				<header class="rt-dash-header rt-global-header">
					<div class="rt-dash-brand">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable' ) ); ?>" class="rt-dash-logo" aria-label="<?php esc_attr_e( 'RoxyTable Dashboard', 'roxytable' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
						</a>
						<div>
							<div class="rt-dash-brand-line">
								<span class="rt-dash-brand-name"><?php esc_html_e( 'RoxyTable', 'roxytable' ); ?></span>
								<span class="rt-dash-tagline"><?php esc_html_e( 'Powerful and flexible tables for your WordPress site.', 'roxytable' ); ?></span>
							</div>
							<h1 class="rt-dash-title"><?php echo esc_html( $active_meta['title'] ); ?></h1>
							<p class="rt-dash-subtitle"><?php echo esc_html( $active_meta['subtitle'] ); ?></p>
						</div>
					</div>

					<form class="rt-dash-search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
						<input type="hidden" name="page" value="roxytable-tables" />
						<label class="screen-reader-text" for="rt-dash-global-search"><?php esc_html_e( 'Search all tables', 'roxytable' ); ?></label>
						<svg class="rt-dash-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" /></svg>
						<input type="search" id="rt-dash-global-search" name="rt_table_search" value="<?php echo esc_attr( $global_search ); ?>" placeholder="<?php esc_attr_e( 'Search tables…', 'roxytable' ); ?>" class="roxytable-global-search" />
					</form>
				</header>

				<?php
				$rt_layout_active = true;
				if ( ! empty( $view_file ) && file_exists( $view_file ) ) {
					include $view_file;
				}
				?>
			</div>
		</main>
	</div>
</div>
