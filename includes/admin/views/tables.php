<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$per_page = 20;
$search_query = isset( $_GET['rt_table_search'] ) ? sanitize_text_field( wp_unslash( $_GET['rt_table_search'] ) ) : '';
$active_filter = isset( $_GET['rt_table_filter'] ) ? sanitize_key( wp_unslash( $_GET['rt_table_filter'] ) ) : 'all';
$page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
$offset = ( $page - 1 ) * $per_page;

$valid_filters = [ 'all', 'published', 'draft', 'updated' ];
if ( ! in_array( $active_filter, $valid_filters, true ) ) {
	$active_filter = 'all';
}

$tables = \RoxyTable\Includes\Admin\Table_Repository::list_tables( [
	'search' => $search_query,
	'filter' => $active_filter,
	'limit' => $per_page,
	'offset' => $offset,
] );

$total_tables = \RoxyTable\Includes\Admin\Table_Repository::count_tables( [
	'search' => $search_query,
	'filter' => $active_filter,
] );

$total_pages = max( 1, (int) ceil( $total_tables / $per_page ) );
$base_url = admin_url( 'admin.php?page=roxytable-tables' );
?>
<div class="roxytable-tables-container">
	<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; flex-wrap: wrap;">
		<div>
			<h2 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'All Tables', 'roxytable' ); ?></h2>
			<p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.875rem;"><?php _e( 'View and manage all your imported/created tables.', 'roxytable' ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="roxytable-btn roxytable-btn-primary">
			<?php _e( 'Import Excel', 'roxytable' ); ?>
		</a>
	</div>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-bottom: 16px;">
		<input type="hidden" name="page" value="roxytable-tables" />
		<div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
			<div style="flex: 1 1 260px; min-width: 220px;">
				<input type="search" id="rt-table-search" name="rt_table_search" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search tables by name, ID or slug', 'roxytable' ); ?>" class="rt-field" style="width: 100%;" />
			</div>
			<div>
				<select name="rt_table_filter" class="rt-field">
					<option value="all" <?php selected( 'all', $active_filter ); ?>><?php _e( 'All', 'roxytable' ); ?></option>
					<option value="published" <?php selected( 'published', $active_filter ); ?>><?php _e( 'Published', 'roxytable' ); ?></option>
					<option value="draft" <?php selected( 'draft', $active_filter ); ?>><?php _e( 'Draft', 'roxytable' ); ?></option>
					<option value="updated" <?php selected( 'updated', $active_filter ); ?>><?php _e( 'Recently Updated', 'roxytable' ); ?></option>
				</select>
			</div>
			<button type="submit" class="roxytable-btn roxytable-btn-secondary"><?php _e( 'Filter', 'roxytable' ); ?></button>
		</div>
	</form>

	<?php if ( empty( $tables ) ) : ?>
		<div class="roxytable-card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 24px; text-align: center;">
			<div style="width: 64px; height: 64px; background-color: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
				<svg xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
			</svg>
			</div>
			<h3 style="font-size: 1.25rem; font-weight: 600; margin: 0; color: var(--text-main);"><?php _e( 'No tables found', 'roxytable' ); ?></h3>
			<p style="color: var(--text-muted); font-size: 0.875rem; max-width: 380px; margin: 8px 0 24px 0;">
				<?php echo $search_query ? __( 'No tables match your current search. Try a different keyword or reset the filter.', 'roxytable' ) : __( 'You have not uploaded or created any tables yet. Drop an Excel file (.xlsx, .xls, or .csv) to get started.', 'roxytable' ); ?>
			</p>
			<div style="display: flex; gap: 12px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="roxytable-btn roxytable-btn-primary">
					<?php _e( 'Import Excel file', 'roxytable' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-create' ) ); ?>" class="roxytable-btn roxytable-btn-secondary">
					<?php _e( 'Create blank table', 'roxytable' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="roxytable-card" style="padding: 0; overflow: hidden;">
			<?php wp_nonce_field( 'roxytable_bulk_tables', 'roxytable_bulk_tables_nonce' ); ?>
			<div class="rt-bulk-actions" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border-color); background: rgba(248,250,252,0.5); flex-wrap: wrap; gap: 12px;">
				<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
					<label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; color: var(--text-main);">
						<input type="checkbox" id="rt-select-all-tables" />
						<span><?php _e( 'Select all', 'roxytable' ); ?></span>
					</label>
					<span style="color: var(--text-muted); font-size: 0.875rem;"><?php printf( __( '%s tables', 'roxytable' ), number_format_i18n( $total_tables ) ); ?></span>
				</div>
				<div style="display: flex; gap: 8px; flex-wrap: wrap;">
					<select name="rt_bulk_action" class="rt-field" style="min-width: 160px;">
						<option value=""><?php _e( 'Bulk actions', 'roxytable' ); ?></option>
						<option value="export_csv"><?php _e( 'Export (CSV)', 'roxytable' ); ?></option>
						<option value="change_status"><?php _e( 'Change Status', 'roxytable' ); ?></option>
						<option value="delete"><?php _e( 'Delete', 'roxytable' ); ?></option>
					</select>
					<select name="rt_bulk_status" class="rt-field" style="min-width: 140px;">
						<option value="published"><?php _e( 'Published', 'roxytable' ); ?></option>
						<option value="draft"><?php _e( 'Draft', 'roxytable' ); ?></option>
					</select>
					<button type="submit" class="roxytable-btn roxytable-btn-secondary" name="roxytable_tables_bulk_action" value="1"><?php _e( 'Apply', 'roxytable' ); ?></button>
				</div>
			</div>

			<div style="overflow-x: auto;">
				<table style="width: 100%; border-collapse: collapse; min-width: 980px;">
					<thead style="background: #f8fafc;">
						<tr>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); width: 32px;"></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Name', 'roxytable' ); ?></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Description', 'roxytable' ); ?></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Records', 'roxytable' ); ?></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Columns', 'roxytable' ); ?></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Status', 'roxytable' ); ?></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Updated', 'roxytable' ); ?></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Shortcode', 'roxytable' ); ?></th>
							<th style="padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color);"><?php _e( 'Actions', 'roxytable' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tables as $table ) : ?>
							<tr style="border-bottom: 1px solid var(--border-color);">
								<td style="padding: 12px 16px;"><input type="checkbox" name="rt_table_ids[]" value="<?php echo intval( $table['id'] ); ?>" /></td>
								<td style="padding: 12px 16px;">
									<div style="font-weight: 700; color: var(--text-main);"><?php echo esc_html( $table['name'] ); ?></div>
									<div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">ID <?php echo intval( $table['id'] ); ?> · /<?php echo esc_html( $table['slug'] ); ?>/</div>
								</td>
								<td style="padding: 12px 16px; color: var(--text-muted); max-width: 220px;"><?php echo esc_html( $table['description'] ); ?></td>
								<td style="padding: 12px 16px; font-weight: 600; color: var(--text-main);"><?php echo number_format_i18n( intval( $table['total_rows'] ) ); ?></td>
								<td style="padding: 12px 16px; font-weight: 600; color: var(--text-main);"><?php echo number_format_i18n( intval( $table['total_columns'] ) ); ?></td>
								<td style="padding: 12px 16px;"><span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: <?php echo 'published' === $table['status'] ? '#ecfdf5' : '#fffbeb'; ?>; color: <?php echo 'published' === $table['status'] ? '#10b981' : '#f59e0b'; ?>;"><?php echo 'published' === $table['status'] ? __( 'Published', 'roxytable' ) : __( 'Draft', 'roxytable' ); ?></span></td>
								<td style="padding: 12px 16px; color: var(--text-muted);"><?php echo esc_html( $table['updated_at'] ); ?></td>
								<td style="padding: 12px 16px;">
									<button type="button" class="roxytable-btn roxytable-btn-secondary rt-copy-shortcode" data-shortcode="[roxytable id=&quot;<?php echo intval( $table['id'] ); ?>&quot;]" style="padding: 6px 10px; font-size: 0.8125rem;"><?php _e( 'Copy', 'roxytable' ); ?></button>
								</td>
								<td style="padding: 12px 16px;">
									<div class="rt-table-row-actions" style="display: flex; gap: 6px; flex-wrap: wrap;">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . intval( $table['id'] ) ) ); ?>" class="roxytable-btn roxytable-btn-secondary" style="padding: 6px 10px; font-size: 0.8125rem;"><?php _e( 'Edit', 'roxytable' ); ?></a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=preview&id=' . intval( $table['id'] ) ) ); ?>" class="roxytable-btn roxytable-btn-secondary" style="padding: 6px 10px; font-size: 0.8125rem;"><?php _e( 'Preview', 'roxytable' ); ?></a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=shortcode&id=' . intval( $table['id'] ) ) ); ?>" class="roxytable-btn roxytable-btn-secondary rt-action-shortcode" data-shortcode="<?php echo esc_attr( '[roxytable id="' . intval( $table['id'] ) . '"]' ); ?>" style="padding: 6px 10px; font-size: 0.8125rem;" role="button"><?php _e( 'Shortcode', 'roxytable' ); ?></a>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=roxytable-tables&action=delete&id=' . intval( $table['id'] ) ), 'roxytable_delete_table_' . intval( $table['id'] ) ) ); ?>" class="roxytable-btn roxytable-btn-secondary rt-action-delete" data-confirm="<?php echo esc_attr__( 'Delete this table and all its rows and columns?', 'roxytable' ); ?>" style="padding: 6px 10px; font-size: 0.8125rem; color: #dc2626;">
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
					<a href="<?php echo esc_url( add_query_arg( [ 'paged' => $i, 'rt_table_search' => $search_query, 'rt_table_filter' => $active_filter ], $base_url ) ); ?>" class="roxytable-btn roxytable-btn-secondary" style="min-width: 40px; justify-content: center; <?php echo $page === $i ? 'background: var(--primary); color: #fff;' : ''; ?>"><?php echo intval( $i ); ?></a>
				<?php endfor; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
