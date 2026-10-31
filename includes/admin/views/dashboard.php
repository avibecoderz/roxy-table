<?php
/**
 * RoxyTable admin dashboard view.
 *
 * Rendered inside layout.php by Admin_Menu::render_dashboard().
 */

use RoxyTable\Includes\Admin\Table_Repository;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'roxytable' ) );
}

// ── Real statistics from the plugin database ──
$total_tables   = Table_Repository::count_tables();
$published      = Table_Repository::count_tables( [ 'filter' => 'published' ] );
$drafts         = Table_Repository::count_tables( [ 'filter' => 'draft' ] );
$recent_updates = Table_Repository::count_tables( [ 'filter' => 'updated' ] );
$total_rows     = Table_Repository::sum_total_rows();
$recent         = Table_Repository::list_tables( [ 'limit' => 5 ] );

/**
 * Filters the RoxyTable Pro upgrade destination.
 *
 * No dedicated Pro purchase page exists yet, so this defaults to the official
 * WebPress Technologies website. Override it once a purchase page is available:
 * add_filter( 'roxytable_pro_upgrade_url', fn() => 'https://example.com/pro' );
 *
 * @param string $url Upgrade URL.
 */
$pro_upgrade_url = apply_filters( 'roxytable_pro_upgrade_url', 'https://webpress.digital/' );

$tables_url = admin_url( 'admin.php?page=roxytable-tables' );
$import_url = admin_url( 'admin.php?page=roxytable-import' );
$create_url = admin_url( 'admin.php?page=roxytable-create' );
$help_url   = admin_url( 'admin.php?page=roxytable-help' );
$demo_url   = home_url( '/' );

// Rotating accent palette for table icons (matches the reference design).
$icon_palette = [ 'green', 'blue', 'purple', 'orange' ];
$now          = current_time( 'timestamp' );
$standalone   = empty( $rt_layout_active );
?>
<?php if ( $standalone ) : ?>
<div class="rt-dash">
	<!-- ── Dashboard header ── -->
	<header class="rt-dash-header">
		<div class="rt-dash-brand">
			<span class="rt-dash-logo" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
			</span>
			<div>
				<div class="rt-dash-brand-line">
					<span class="rt-dash-brand-name"><?php esc_html_e( 'RoxyTable', 'roxytable' ); ?></span>
					<span class="rt-dash-tagline"><?php esc_html_e( 'Powerful and flexible tables for your WordPress site.', 'roxytable' ); ?></span>
				</div>
				<h1 class="rt-dash-title"><?php esc_html_e( 'Dashboard', 'roxytable' ); ?></h1>
				<p class="rt-dash-subtitle"><?php esc_html_e( 'An overview of your tables, records and recent activity.', 'roxytable' ); ?></p>
			</div>
		</div>

		<form class="rt-dash-search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
			<input type="hidden" name="page" value="roxytable-tables" />
			<label class="screen-reader-text" for="rt-dash-global-search"><?php esc_html_e( 'Search all tables', 'roxytable' ); ?></label>
			<svg class="rt-dash-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" /></svg>
			<input type="search" id="rt-dash-global-search" name="rt_table_search" placeholder="<?php esc_attr_e( 'Search all tables by name, ID or slug…', 'roxytable' ); ?>" />
		</form>
	</header>
<?php endif; ?>

	<div class="rt-dash-grid">

		<!-- ── Main column ── -->
		<div class="rt-dash-main">

			<!-- Statistics cards -->
			<div class="rt-dash-stats">
				<div class="rt-dash-stat rt-accent-blue">
					<span class="rt-dash-stat-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" /></svg>
					</span>
					<div>
						<div class="rt-dash-stat-label"><?php esc_html_e( 'Total Tables', 'roxytable' ); ?></div>
						<div class="rt-dash-stat-value"><?php echo esc_html( number_format_i18n( $total_tables ) ); ?></div>
						<div class="rt-dash-stat-meta">
							<?php
							/* translators: %s: number of tables updated in the last 30 days. */
							echo esc_html( sprintf( _n( '%s updated in the last 30 days', '%s updated in the last 30 days', $recent_updates, 'roxytable' ), number_format_i18n( $recent_updates ) ) );
							?>
						</div>
					</div>
				</div>

				<div class="rt-dash-stat rt-accent-green">
					<span class="rt-dash-stat-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
					</span>
					<div>
						<div class="rt-dash-stat-label"><?php esc_html_e( 'Published', 'roxytable' ); ?></div>
						<div class="rt-dash-stat-value"><?php echo esc_html( number_format_i18n( $published ) ); ?></div>
						<div class="rt-dash-stat-meta"><span class="rt-dash-dot"></span><?php esc_html_e( 'Live on site', 'roxytable' ); ?></div>
					</div>
				</div>

				<div class="rt-dash-stat rt-accent-orange">
					<span class="rt-dash-stat-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
					</span>
					<div>
						<div class="rt-dash-stat-label"><?php esc_html_e( 'Drafts', 'roxytable' ); ?></div>
						<div class="rt-dash-stat-value"><?php echo esc_html( number_format_i18n( $drafts ) ); ?></div>
						<div class="rt-dash-stat-meta"><span class="rt-dash-dot"></span><?php esc_html_e( 'Not visible yet', 'roxytable' ); ?></div>
					</div>
				</div>

				<div class="rt-dash-stat rt-accent-purple">
					<span class="rt-dash-stat-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
					</span>
					<div>
						<div class="rt-dash-stat-label"><?php esc_html_e( 'Total Records', 'roxytable' ); ?></div>
						<div class="rt-dash-stat-value"><?php echo esc_html( number_format_i18n( $total_rows ) ); ?></div>
						<div class="rt-dash-stat-meta"><span class="rt-dash-dot"></span><?php esc_html_e( 'Across all tables', 'roxytable' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Recent tables -->
			<section class="rt-dash-card rt-dash-recent" aria-labelledby="rt-dash-recent-title">
				<div class="rt-dash-recent-head">
					<div class="rt-dash-recent-heading">
						<span class="rt-dash-recent-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
						</span>
						<div>
							<h2 id="rt-dash-recent-title"><?php esc_html_e( 'Recent tables', 'roxytable' ); ?></h2>
							<p><?php esc_html_e( 'Your most recently updated data sets', 'roxytable' ); ?></p>
						</div>
					</div>

					<div class="rt-dash-recent-tools">
						<div class="rt-dash-filter-search">
							<label class="screen-reader-text" for="rt-dash-recent-search"><?php esc_html_e( 'Filter recent tables', 'roxytable' ); ?></label>
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" /></svg>
							<input type="search" id="rt-dash-recent-search" placeholder="<?php esc_attr_e( 'Search tables…', 'roxytable' ); ?>" />
						</div>
						<label class="screen-reader-text" for="rt-dash-status-filter"><?php esc_html_e( 'Filter by status', 'roxytable' ); ?></label>
						<select id="rt-dash-status-filter" class="rt-dash-select">
							<option value="all"><?php esc_html_e( 'All statuses', 'roxytable' ); ?></option>
							<option value="published"><?php esc_html_e( 'Published', 'roxytable' ); ?></option>
							<option value="draft"><?php esc_html_e( 'Draft', 'roxytable' ); ?></option>
						</select>
						<a href="<?php echo esc_url( $create_url ); ?>" class="rt-dash-btn rt-dash-btn-outline"><?php esc_html_e( '+ Create Table', 'roxytable' ); ?></a>
						<a href="<?php echo esc_url( $import_url ); ?>" class="rt-dash-btn rt-dash-btn-primary"><?php esc_html_e( 'Import Excel File', 'roxytable' ); ?></a>
					</div>
				</div>

				<div class="rt-dash-table-scroll">
					<table class="rt-dash-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Table', 'roxytable' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Records', 'roxytable' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Columns', 'roxytable' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'roxytable' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Updated', 'roxytable' ); ?></th>
								<th scope="col" class="rt-dash-col-actions"><?php esc_html_e( 'Actions', 'roxytable' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $recent ) ) : ?>
								<tr>
									<td colspan="6" class="rt-dash-empty"><?php esc_html_e( 'No tables yet. Import one to get started.', 'roxytable' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $recent as $index => $table ) :
									$table_id     = intval( $table['id'] );
									$is_published = 'published' === strtolower( (string) $table['status'] );
									$status_key   = $is_published ? 'published' : 'draft';
									$accent       = $icon_palette[ $index % count( $icon_palette ) ];
									$updated_ts   = strtotime( (string) $table['updated_at'] );
									$edit_url     = admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id );
									$preview_url  = admin_url( 'admin.php?page=roxytable-tables&action=preview&id=' . $table_id );
									$shortcode    = '[roxytable id="' . $table_id . '"]';
									$search_blob  = strtolower( $table['name'] . ' ' . $table['slug'] . ' ' . $table_id );
									?>
									<tr class="rt-dash-row" data-status="<?php echo esc_attr( $status_key ); ?>" data-search="<?php echo esc_attr( $search_blob ); ?>">
										<td>
											<div class="rt-dash-table-cell">
												<div class="rt-table-thumb-wrap rt-table-image-picker rt-dash-thumb rt-accent-<?php echo esc_attr( $accent ); ?>" data-table-id="<?php echo esc_attr( $table_id ); ?>" title="<?php esc_attr_e( 'Click to set or change table image', 'roxytable' ); ?>">
													<?php if ( ! empty( $table['image'] ) ) : ?>
														<img class="rt-table-thumb-img" src="<?php echo esc_url( $table['image'] ); ?>" alt="<?php echo esc_attr( $table['name'] ); ?>" />
													<?php else : ?>
														<div class="rt-table-thumb-default">
															<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm1.5 3v3h4.5V9H4.5zm6 0v3h3V9h-3zm4.5 0v3h4.5V9H15zm4.5 4.5H15v3h3a1.5 1.5 0 001.5-1.5v-1.5zm-6 0h-3v3h3v-3zm-4.5 0H4.5V15A1.5 1.5 0 006 16.5h3v-3z" clip-rule="evenodd" /></svg>
														</div>
													<?php endif; ?>
													<div class="rt-table-thumb-overlay" aria-hidden="true">📷</div>
												</div>
												<div>
													<a class="rt-dash-table-name" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $table['name'] ); ?></a>
													<div class="rt-dash-table-sub">
														<span class="rt-dash-id"><?php echo esc_html( sprintf( 'ID %d', $table_id ) ); ?></span>
														<span>/<?php echo esc_html( $table['slug'] ); ?>/</span>
													</div>
												</div>
											</div>
										</td>
										<td class="rt-dash-num"><?php echo esc_html( number_format_i18n( intval( $table['total_rows'] ) ) ); ?></td>
										<td class="rt-dash-num"><?php echo esc_html( number_format_i18n( intval( $table['total_columns'] ) ) ); ?></td>
										<td>
											<span class="rt-dash-badge rt-dash-badge-<?php echo esc_attr( $status_key ); ?>">
												<?php echo $is_published ? esc_html__( 'Published', 'roxytable' ) : esc_html__( 'Draft', 'roxytable' ); ?>
											</span>
										</td>
										<td class="rt-dash-updated">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
											<?php if ( $updated_ts ) : ?>
												<time datetime="<?php echo esc_attr( gmdate( 'c', $updated_ts ) ); ?>" title="<?php echo esc_attr( $table['updated_at'] ); ?>">
													<?php
													/* translators: %s: human-readable time difference, e.g. "2 days". */
													echo esc_html( sprintf( __( '%s ago', 'roxytable' ), human_time_diff( $updated_ts, $now ) ) );
													?>
												</time>
											<?php else : ?>
												&mdash;
											<?php endif; ?>
										</td>
										<td class="rt-dash-col-actions">
											<div class="rt-dash-actions">
												<a class="rt-dash-btn rt-dash-btn-edit" href="<?php echo esc_url( $edit_url ); ?>">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
													<?php esc_html_e( 'Edit', 'roxytable' ); ?>
												</a>
												<a class="rt-dash-btn rt-dash-btn-ghost" href="<?php echo esc_url( $preview_url ); ?>">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
													<?php esc_html_e( 'Preview', 'roxytable' ); ?>
												</a>
												<div class="rt-dash-menu">
													<button type="button" class="rt-dash-menu-toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo esc_attr( sprintf( __( 'More actions for %s', 'roxytable' ), $table['name'] ) ); ?>">
														<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" /></svg>
													</button>
													<div class="rt-dash-menu-list" role="menu" hidden>
														<button type="button" role="menuitem" class="rt-dash-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode ); ?>"><?php esc_html_e( 'Copy shortcode', 'roxytable' ); ?></button>
														<a role="menuitem" href="<?php echo esc_url( add_query_arg( 'rt_table_search', $table_id, $tables_url ) ); ?>"><?php esc_html_e( 'Open in All Tables', 'roxytable' ); ?></a>
													</div>
												</div>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
								<tr class="rt-dash-no-match" hidden>
									<td colspan="6" class="rt-dash-empty"><?php esc_html_e( 'No recent tables match your filters.', 'roxytable' ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<?php if ( $total_tables > count( $recent ) ) : ?>
					<div class="rt-dash-recent-foot">
						<a href="<?php echo esc_url( $tables_url ); ?>">
							<?php
							/* translators: %s: total number of tables. */
							echo esc_html( sprintf( __( 'View all %s tables →', 'roxytable' ), number_format_i18n( $total_tables ) ) );
							?>
						</a>
					</div>
				<?php endif; ?>
			</section>
		</div>

		<!-- ── Right sidebar (Dashboard only) ── -->
		<aside class="rt-dash-side">
			<div class="rt-dash-card rt-dash-quick">
				<h3>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" /></svg>
					<?php esc_html_e( 'Quick actions', 'roxytable' ); ?>
				</h3>
				<div class="rt-dash-quick-list">
					<a class="rt-dash-btn rt-dash-btn-primary rt-dash-btn-block" href="<?php echo esc_url( $import_url ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
						<?php esc_html_e( 'Import Excel File', 'roxytable' ); ?>
					</a>
					<a class="rt-dash-btn rt-dash-btn-outline rt-dash-btn-block" href="<?php echo esc_url( $create_url ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
						<?php esc_html_e( 'Create New Table', 'roxytable' ); ?>
					</a>
					<a class="rt-dash-btn rt-dash-btn-outline rt-dash-btn-block" href="<?php echo esc_url( $help_url ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
						<?php esc_html_e( 'View Documentation', 'roxytable' ); ?>
					</a>
					<a class="rt-dash-btn rt-dash-btn-outline rt-dash-btn-block" href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener noreferrer">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" /></svg>
						<?php esc_html_e( 'View Demo', 'roxytable' ); ?>
					</a>
				</div>
			</div>

			<div class="rt-dash-card rt-dash-pro">
				<span class="rt-dash-pro-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
				</span>
				<h3><?php esc_html_e( 'RoxyTable Pro', 'roxytable' ); ?></h3>
				<p><?php esc_html_e( 'Unlock more features and supercharge your data management.', 'roxytable' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Advanced table features', 'roxytable' ); ?></li>
					<li><?php esc_html_e( 'More import/export options', 'roxytable' ); ?></li>
					<li><?php esc_html_e( 'Priority support', 'roxytable' ); ?></li>
				</ul>
				<a class="rt-dash-btn rt-dash-btn-outline rt-dash-btn-block rt-dash-pro-btn" href="<?php echo esc_url( $pro_upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Upgrade Now →', 'roxytable' ); ?>
				</a>
			</div>
		</aside>
	</div>
<?php if ( $standalone ) : ?>
</div>
<?php endif; ?>
