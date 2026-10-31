<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$per_page      = 20;
$search_query  = isset( $_GET['rt_table_search'] ) ? sanitize_text_field( wp_unslash( $_GET['rt_table_search'] ) ) : '';
$active_filter = isset( $_GET['rt_table_filter'] ) ? sanitize_key( wp_unslash( $_GET['rt_table_filter'] ) ) : 'all';
$page          = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
$offset        = ( $page - 1 ) * $per_page;

$valid_filters = [ 'all', 'published', 'draft', 'updated' ];
if ( ! in_array( $active_filter, $valid_filters, true ) ) {
	$active_filter = 'all';
}

$tables = \RoxyTable\Includes\Admin\Table_Repository::list_tables( [
	'search' => $search_query,
	'filter' => $active_filter,
	'limit'  => $per_page,
	'offset' => $offset,
] );

$total_tables = \RoxyTable\Includes\Admin\Table_Repository::count_tables( [
	'search' => $search_query,
	'filter' => $active_filter,
] );

$total_pages  = max( 1, (int) ceil( $total_tables / $per_page ) );
$base_url     = admin_url( 'admin.php?page=roxytable-tables' );
$icon_palette = [ 'green', 'blue', 'purple', 'orange' ];
$now          = function_exists( 'current_time' ) ? current_time( 'timestamp' ) : time();
?>
<div class="roxytable-tables-container">

	<!-- Filter & Action Toolbar Card -->
	<div class="rt-dash-card rt-dash-recent" style="margin-bottom: 20px;">
		<div class="rt-dash-recent-head" style="border-bottom: 0;">
			<div class="rt-dash-recent-heading">
				<span class="rt-dash-recent-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
				</span>
				<div>
					<h2><?php _e( 'All Tables', 'roxytable' ); ?></h2>
					<p><?php _e( 'View and manage all your imported and created tables', 'roxytable' ); ?></p>
				</div>
			</div>

			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="rt-dash-recent-tools" style="margin: 0;">
				<input type="hidden" name="page" value="roxytable-tables" />
				<div class="rt-dash-filter-search" style="width: 240px;">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" /></svg>
					<input type="search" id="rt-table-search" name="rt_table_search" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search tables by name, ID or slug', 'roxytable' ); ?>" class="rt-field" />
				</div>
				<select name="rt_table_filter" class="rt-dash-select rt-field">
					<option value="all" <?php selected( 'all', $active_filter ); ?>><?php _e( 'All', 'roxytable' ); ?></option>
					<option value="published" <?php selected( 'published', $active_filter ); ?>><?php _e( 'Published', 'roxytable' ); ?></option>
					<option value="draft" <?php selected( 'draft', $active_filter ); ?>><?php _e( 'Draft', 'roxytable' ); ?></option>
					<option value="updated" <?php selected( 'updated', $active_filter ); ?>><?php _e( 'Recently Updated', 'roxytable' ); ?></option>
				</select>
				<button type="submit" class="rt-dash-btn rt-dash-btn-outline"><?php _e( 'Filter', 'roxytable' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-create' ) ); ?>" class="rt-dash-btn rt-dash-btn-outline"><?php _e( '+ Create Table', 'roxytable' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="rt-dash-btn rt-dash-btn-primary"><?php _e( 'Import Excel File', 'roxytable' ); ?></a>
			</form>
		</div>
	</div>

	<?php if ( empty( $tables ) ) : ?>
		<div class="roxytable-card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 24px; text-align: center;">
			<div style="width: 64px; height: 64px; background-color: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
				<svg xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
				</svg>
			</div>
			<h3 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'No tables found', 'roxytable' ); ?></h3>
			<p style="color: var(--text-muted); font-size: 0.875rem; max-width: 380px; margin: 8px 0 24px 0;">
				<?php echo $search_query ? __( 'No tables match your current search. Try a different keyword or reset the filter.', 'roxytable' ) : __( 'You have not uploaded or created any tables yet. Drop an Excel file (.xlsx, .xls, or .csv) to get started.', 'roxytable' ); ?>
			</p>
			<div style="display: flex; gap: 12px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="rt-dash-btn rt-dash-btn-primary">
					<?php _e( 'Import Excel File', 'roxytable' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-create' ) ); ?>" class="rt-dash-btn rt-dash-btn-outline">
					<?php _e( 'Create Blank Table', 'roxytable' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<form id="rt-tables-bulk-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="roxytable-card rt-dash-card rt-dash-recent" style="padding: 0; overflow: hidden;">
			<?php wp_nonce_field( 'roxytable_bulk_tables', 'roxytable_bulk_tables_nonce' ); ?>
			<div class="rt-bulk-actions" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: #ffffff; flex-wrap: wrap; gap: 12px;">
				<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
					<label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; color: var(--text-main);">
						<input type="checkbox" id="rt-select-all-tables" />
						<span><?php _e( 'Select all', 'roxytable' ); ?></span>
					</label>
					<span style="color: var(--text-muted); font-size: 0.875rem;"><?php printf( __( '%s tables', 'roxytable' ), number_format_i18n( $total_tables ) ); ?></span>
				</div>
				<div style="display: flex; gap: 8px; flex-wrap: wrap;">
					<select name="rt_bulk_action" class="rt-dash-select rt-field" style="min-width: 160px;">
						<option value=""><?php _e( 'Bulk actions', 'roxytable' ); ?></option>
						<option value="export_csv"><?php _e( 'Export (CSV)', 'roxytable' ); ?></option>
						<option value="change_status"><?php _e( 'Change Status', 'roxytable' ); ?></option>
						<option value="delete"><?php _e( 'Delete', 'roxytable' ); ?></option>
					</select>
					<select name="rt_bulk_status" class="rt-dash-select rt-field" style="min-width: 140px;">
						<option value="published"><?php _e( 'Published', 'roxytable' ); ?></option>
						<option value="draft"><?php _e( 'Draft', 'roxytable' ); ?></option>
					</select>
					<button type="submit" class="rt-dash-btn rt-dash-btn-outline" name="roxytable_tables_bulk_action" value="1"><?php _e( 'Apply', 'roxytable' ); ?></button>
				</div>
			</div>

			<div class="rt-dash-table-scroll">
				<table class="rt-dash-table" style="min-width: 980px;">
					<thead>
						<tr>
							<th style="width: 32px;"></th>
							<th><?php _e( 'Table', 'roxytable' ); ?></th>
							<th><?php _e( 'Description', 'roxytable' ); ?></th>
							<th><?php _e( 'Records', 'roxytable' ); ?></th>
							<th><?php _e( 'Columns', 'roxytable' ); ?></th>
							<th><?php _e( 'Status', 'roxytable' ); ?></th>
							<th><?php _e( 'Updated', 'roxytable' ); ?></th>
							<th><?php _e( 'Shortcode', 'roxytable' ); ?></th>
							<th class="rt-dash-col-actions"><?php _e( 'Actions', 'roxytable' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tables as $index => $table ) :
							$table_id     = intval( $table['id'] );
							$is_published = 'published' === strtolower( (string) $table['status'] );
							$status_key   = $is_published ? 'published' : 'draft';
							$accent       = $icon_palette[ $index % count( $icon_palette ) ];
							$updated_ts   = strtotime( (string) $table['updated_at'] );
							?>
							<tr class="rt-dash-row">
								<td><input type="checkbox" name="rt_table_ids[]" value="<?php echo $table_id; ?>" /></td>
								<td>
									<div class="rt-dash-table-cell">
										<div class="rt-table-thumb-wrap rt-table-image-picker rt-dash-thumb rt-accent-<?php echo esc_attr( $accent ); ?>" data-table-id="<?php echo $table_id; ?>" title="<?php esc_attr_e( 'Click to set or change table image', 'roxytable' ); ?>">
											<?php if ( ! empty( $table['image'] ) ) : ?>
												<img class="rt-table-thumb-img" src="<?php echo esc_url( $table['image'] ); ?>" alt="<?php echo esc_attr( $table['name'] ); ?>" />
											<?php else : ?>
												<div class="rt-table-thumb-default">
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
														<path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm1.5 3v3h4.5V9H4.5zm6 0v3h3V9h-3zm4.5 0v3h4.5V9H15zm4.5 4.5H15v3h3a1.5 1.5 0 001.5-1.5v-1.5zm-6 0h-3v3h3v-3zm-4.5 0H4.5V15A1.5 1.5 0 006 16.5h3v-3z" clip-rule="evenodd" />
													</svg>
												</div>
											<?php endif; ?>
											<div class="rt-table-thumb-overlay" aria-hidden="true">📷</div>
										</div>
										<div>
											<a class="rt-dash-table-name" href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id ) ); ?>"><?php echo esc_html( $table['name'] ); ?></a>
											<div class="rt-dash-table-sub">
												<span class="rt-dash-id">ID <?php echo $table_id; ?></span>
												<span>/<?php echo esc_html( $table['slug'] ); ?>/</span>
											</div>
										</div>
									</div>
								</td>
								<td style="color: var(--text-muted); max-width: 220px;"><?php echo esc_html( $table['description'] ); ?></td>
								<td class="rt-dash-num"><?php echo number_format_i18n( intval( $table['total_rows'] ) ); ?></td>
								<td class="rt-dash-num"><?php echo number_format_i18n( intval( $table['total_columns'] ) ); ?></td>
								<td>
									<span class="rt-dash-badge rt-dash-badge-<?php echo esc_attr( $status_key ); ?> <?php echo $is_published ? 'rt-badge-status-published' : 'rt-badge-status-draft'; ?>">
										<?php echo $is_published ? __( 'Published', 'roxytable' ) : __( 'Draft', 'roxytable' ); ?>
									</span>
								</td>
								<td class="rt-dash-updated">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
									<?php if ( $updated_ts && function_exists( 'human_time_diff' ) ) : ?>
										<time datetime="<?php echo esc_attr( gmdate( 'c', $updated_ts ) ); ?>" title="<?php echo esc_attr( $table['updated_at'] ); ?>">
											<?php echo esc_html( human_time_diff( $updated_ts, $now ) . ' ago' ); ?>
										</time>
									<?php else : ?>
										<?php echo esc_html( $table['updated_at'] ); ?>
									<?php endif; ?>
								</td>
								<td>
									<button type="button" class="rt-dash-btn rt-dash-btn-ghost rt-copy-shortcode" data-shortcode="[roxytable id=&quot;<?php echo $table_id; ?>&quot;]"><?php _e( 'Copy', 'roxytable' ); ?></button>
								</td>
								<td class="rt-dash-col-actions">
									<div class="rt-table-row-actions rt-dash-actions">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id . '#rt-table-options-section' ) ); ?>" class="rt-dash-btn rt-dash-btn-edit rt-action-edit" data-table-id="<?php echo $table_id; ?>">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
											<?php _e( 'Edit', 'roxytable' ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=preview&id=' . $table_id ) ); ?>" class="rt-dash-btn rt-dash-btn-ghost">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
											<?php _e( 'Preview', 'roxytable' ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=shortcode&id=' . $table_id ) ); ?>" class="rt-dash-btn rt-dash-btn-ghost rt-action-shortcode" data-shortcode="<?php echo esc_attr( '[roxytable id="' . $table_id . '"]' ); ?>" role="button"><?php _e( 'Shortcode', 'roxytable' ); ?></a>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=roxytable-tables&action=delete&id=' . $table_id ), 'roxytable_delete_table_' . $table_id ) ); ?>" class="rt-dash-btn rt-dash-btn-ghost rt-action-delete" data-confirm="<?php echo esc_attr__( 'Delete this table and all its rows and columns?', 'roxytable' ); ?>" style="color: #dc2626; border-color: #fecaca;">
											<?php _e( 'Delete', 'roxytable' ); ?>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</form>
	<?php endif; ?>

	<?php if ( $total_pages > 1 ) : ?>
		<div style="display: flex; justify-content: center; margin-top: 24px;">
			<div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
				<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( [ 'paged' => $i, 'rt_table_search' => $search_query, 'rt_table_filter' => $active_filter ], $base_url ) ); ?>" class="rt-dash-btn <?php echo $page === $i ? 'rt-dash-btn-primary' : 'rt-dash-btn-outline'; ?>" style="min-width: 40px; justify-content: center;"><?php echo intval( $i ); ?></a>
				<?php endfor; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
