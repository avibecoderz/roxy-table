<?php
use RoxyTable\Includes\Admin\Table_Repository;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'roxytable' ) );
}

$table_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
if ( $table_id < 1 ) {
	wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
	exit;
}

$table = Table_Repository::get_table( $table_id );
if ( ! $table ) {
	wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
	exit;
}

$columns          = Table_Repository::get_columns( $table_id );
$rows             = Table_Repository::get_rows( $table_id, max( 1, (int) ( $table['total_rows'] ?? 1 ) ), 0 );
$filter_stats     = Table_Repository::get_column_statistics( $table_id );
$table_settings   = is_array( $table['settings'] ?? null )
	? $table['settings']
	: ( json_decode( (string) ( $table['settings'] ?? '' ), true ) ?: [] );
$current_template = ! empty( $table_settings['template'] ) ? $table_settings['template'] : '';

$parse_opts_saved = isset( $table_settings['parse_options'] ) && is_array( $table_settings['parse_options'] )
	? $table_settings['parse_options']
	: [];

$opt_first_row   = isset( $parse_opts_saved['first_row_headers'] ) ? (bool) $parse_opts_saved['first_row_headers'] : ( isset( $table_settings['first_row_headers'] ) ? (bool) $table_settings['first_row_headers'] : true );
$opt_auto_detect = isset( $parse_opts_saved['auto_detect_types'] ) ? (bool) $parse_opts_saved['auto_detect_types'] : ( isset( $table_settings['auto_detect_types'] ) ? (bool) $table_settings['auto_detect_types'] : true );
$opt_remove_rows = isset( $parse_opts_saved['remove_empty_rows'] ) ? (bool) $parse_opts_saved['remove_empty_rows'] : ( isset( $table_settings['remove_empty_rows'] ) ? (bool) $table_settings['remove_empty_rows'] : true );
$opt_remove_cols = isset( $parse_opts_saved['remove_empty_cols'] ) ? (bool) $parse_opts_saved['remove_empty_cols'] : ( isset( $table_settings['remove_empty_cols'] ) ? (bool) $table_settings['remove_empty_cols'] : true );

$feat_search       = isset( $table_settings['search'] ) ? (bool) $table_settings['search'] : true;
$feat_sheet_filt   = isset( $table_settings['sheet_filtering'] ) ? (bool) $table_settings['sheet_filtering'] : true;
$feat_table_filt   = isset( $table_settings['table_filtering'] ) ? (bool) $table_settings['table_filtering'] : true;
$feat_sort         = isset( $table_settings['sort'] ) ? (bool) $table_settings['sort'] : true;
$feat_pagination   = isset( $table_settings['pagination'] ) ? (bool) $table_settings['pagination'] : true;
$feat_visibility   = isset( $table_settings['visibility'] ) ? (bool) $table_settings['visibility'] : true;
$feat_export       = isset( $table_settings['export'] ) ? (bool) $table_settings['export'] : false;
$rows_per_page     = intval( $table_settings['rows_per_page'] ?? 25 );
$rows_per_page     = 0 === $rows_per_page ? 0 : min( 500, max( 5, $rows_per_page ) );
$custom_css        = isset( $table_settings['custom_css'] ) ? (string) $table_settings['custom_css'] : '';
$custom_js         = isset( $table_settings['custom_js'] ) ? (string) $table_settings['custom_js'] : '';
$custom_js_enabled = isset( $table_settings['custom_js_enabled'] ) ? ! empty( $table_settings['custom_js_enabled'] ) : true;
$can_edit_js       = current_user_can( 'manage_options' ) && ( ! function_exists( 'current_user_can' ) || current_user_can( 'unfiltered_html' ) );
$server_side       = ! empty( $table_settings['server_side'] );
$saved_filter_columns = isset( $table_settings['filter_columns'] ) && is_array( $table_settings['filter_columns'] )
	? array_map( 'intval', $table_settings['filter_columns'] )
	: null;
?>
<div class="roxytable-edit-container" style="max-width: 880px; margin: 0 auto;">
	<div style="margin-bottom:24px;">
		<h2 style="font-size:1.5rem;font-weight:700;margin:0;color:var(--text-main);"><?php esc_html_e( 'Edit Table', 'roxytable' ); ?></h2>
		<p style="margin:4px 0 0 0;color:var(--text-muted);font-size:0.875rem;">
			<?php esc_html_e( 'Update the table metadata and settings below.', 'roxytable' ); ?>
		</p>
	</div>

	<div class="roxytable-card">
		<form id="rt-edit-table-meta-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>">
			<?php wp_nonce_field( 'roxytable_save_table', 'roxytable_save_table_nonce' ); ?>
			<input type="hidden" name="roxytable_save_table" value="1" />
			<input type="hidden" name="rt_table_id" value="<?php echo esc_attr( (string) intval( $table_id ) ); ?>" />
			<input type="hidden" name="rt_has_options" value="1" />

			<div style="display:flex;flex-direction:column;gap:18px;">
				<div>
					<label class="rt-label" for="rt-table-name"><?php esc_html_e( 'Table Name', 'roxytable' ); ?></label>
					<input id="rt-table-name" name="rt_table_name" type="text" class="rt-field" value="<?php echo esc_attr( $table['name'] ); ?>" required maxlength="255" />
				</div>

				<div>
					<label class="rt-label" for="rt-table-slug"><?php esc_html_e( 'Slug', 'roxytable' ); ?></label>
					<input id="rt-table-slug" name="rt_table_slug" type="text" class="rt-field" value="<?php echo esc_attr( $table['slug'] ); ?>" maxlength="255" pattern="[a-z0-9\-]+" />
				</div>

				<div>
					<label class="rt-label" for="rt-table-desc"><?php esc_html_e( 'Description', 'roxytable' ); ?></label>
					<textarea id="rt-table-desc" name="rt_table_description" class="rt-field" rows="3"><?php echo esc_textarea( $table['description'] ); ?></textarea>
				</div>

				<div>
					<label class="rt-label" for="rt-table-status"><?php esc_html_e( 'Status', 'roxytable' ); ?></label>
					<select id="rt-table-status" name="rt_table_status" class="rt-field" style="max-width:240px;">
						<option value="published" <?php selected( 'published', $table['status'] ); ?>><?php esc_html_e( 'Published', 'roxytable' ); ?></option>
						<option value="draft" <?php selected( 'draft', $table['status'] ); ?>><?php esc_html_e( 'Draft', 'roxytable' ); ?></option>
					</select>
				</div>

				<div>
					<label class="rt-label" for="rt-table-template"><?php esc_html_e( 'Frontend Template', 'roxytable' ); ?></label>
					<select id="rt-table-template" name="rt_table_template" class="rt-field" style="max-width:300px;">
						<option value="" <?php selected( '', $current_template ); ?>><?php esc_html_e( 'Default table style', 'roxytable' ); ?></option>
						<option value="indigo" <?php selected( 'indigo', $current_template ); ?>><?php esc_html_e( 'Indigo Clean', 'roxytable' ); ?></option>
						<option value="obsidian" <?php selected( 'obsidian', $current_template ); ?>><?php esc_html_e( 'Dark Minimal', 'roxytable' ); ?></option>
						<option value="emerald" <?php selected( 'emerald', $current_template ); ?>><?php esc_html_e( 'Emerald Grid', 'roxytable' ); ?></option>
					</select>
				</div>

				<!-- ── Table Options & Features Section ── -->
				<div class="rt-options-panel">
					<div class="rt-options-header">
						<div>
							<h4 style="margin:0; font-size:1.05rem; font-weight:700; color:var(--text-main);"><?php esc_html_e( 'Table Options & Features', 'roxytable' ); ?></h4>
							<p style="margin:3px 0 0; font-size:0.8125rem; color:var(--text-muted);"><?php esc_html_e( 'Configure parsing rules and interactive frontend features for this table.', 'roxytable' ); ?></p>
						</div>
						<button type="button" id="rt-toggle-options-btn" class="rt-options-toggle-btn" aria-expanded="true" aria-controls="rt-table-options-section">
							<svg class="rt-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
							</svg>
							<span id="rt-toggle-options-text"><?php esc_html_e( 'Hide Options', 'roxytable' ); ?></span>
						</button>
					</div>

					<div id="rt-table-options-section" class="rt-options-container" data-table-id="<?php echo esc_attr( (string) intval( $table_id ) ); ?>">
						<!-- ── 2. Parse Options ── -->
						<div class="rt-options-block">
							<h5 class="rt-section-heading"><?php esc_html_e( '2. Parse Options', 'roxytable' ); ?></h5>
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px 28px;">
								<label class="rt-checkbox-label">
									<input type="checkbox" name="rt_first_row_headers" value="1" <?php checked( $opt_first_row, true ); ?> />
									<span><?php esc_html_e( 'First row contains headers', 'roxytable' ); ?></span>
								</label>
								<label class="rt-checkbox-label">
									<input type="checkbox" name="rt_auto_detect_types" value="1" <?php checked( $opt_auto_detect, true ); ?> />
									<span><?php esc_html_e( 'Auto-detect column types', 'roxytable' ); ?></span>
								</label>
								<label class="rt-checkbox-label">
									<input type="checkbox" name="rt_remove_empty_rows" value="1" <?php checked( $opt_remove_rows, true ); ?> />
									<span><?php esc_html_e( 'Remove empty rows', 'roxytable' ); ?></span>
								</label>
								<label class="rt-checkbox-label">
									<input type="checkbox" name="rt_remove_empty_cols" value="1" <?php checked( $opt_remove_cols, true ); ?> />
									<span><?php esc_html_e( 'Remove empty columns', 'roxytable' ); ?></span>
								</label>
							</div>
						</div>

						<!-- ── 3. Frontend Features ── -->
						<div class="rt-options-block">
							<h5 class="rt-section-heading"><?php esc_html_e( '3. Frontend Features', 'roxytable' ); ?></h5>
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 28px;">
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_search" value="1" <?php checked( $feat_search, true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Enable search', 'roxytable' ); ?></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_sheet_filter" value="1" <?php checked( $feat_sheet_filt, true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Sheet-based filtering', 'roxytable' ); ?></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_table_filter" value="1" <?php checked( $feat_table_filt, true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Table-based filtering', 'roxytable' ); ?></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_sort" value="1" <?php checked( $feat_sort, true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Enable sort', 'roxytable' ); ?></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_pagination" value="1" <?php checked( $feat_pagination, true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Enable pagination', 'roxytable' ); ?></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_visibility" value="1" <?php checked( $feat_visibility, true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Column visibility toggle', 'roxytable' ); ?></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_export" value="1" <?php checked( $feat_export, true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Enable export (CSV)', 'roxytable' ); ?></span>
								</label>
							</div>
							<div style="max-width:200px;margin-top:16px;">
								<label class="rt-label" for="rt-edit-rows-per-page"><?php esc_html_e( 'Rows per page', 'roxytable' ); ?></label>
								<select id="rt-edit-rows-per-page" name="rt_rows_per_page" class="rt-field">
									<option value="25" <?php selected( 25, $rows_per_page ); ?>>25</option>
									<option value="50" <?php selected( 50, $rows_per_page ); ?>>50</option>
									<option value="100" <?php selected( 100, $rows_per_page ); ?>>100</option>
									<option value="250" <?php selected( 250, $rows_per_page ); ?>>250</option>
									<option value="500" <?php selected( 500, $rows_per_page ); ?>>500</option>
									<option value="0" <?php selected( 0, $rows_per_page ); ?>><?php esc_html_e( 'All rows', 'roxytable' ); ?></option>
								</select>
							</div>
							<fieldset style="margin-top:20px;border:0;padding:0;">
								<legend class="rt-label"><?php esc_html_e( 'Frontend filter columns', 'roxytable' ); ?></legend>
								<p class="rt-hint"><?php esc_html_e( 'Choose which columns visitors can filter. Every table column is available.', 'roxytable' ); ?></p>
								<input type="hidden" name="rt_filter_columns_configured" value="1" />
								<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;max-height:260px;overflow:auto;padding:8px;border:1px solid var(--border-color);border-radius:4px;">
									<?php
									foreach ( $columns as $column_index => $column ) :
										$column_stats    = $filter_stats[ $column_index ] ?? [];
										$column_name     = strtolower( trim( $column['name'] ) );
										$is_serial       = in_array( $column_name, [ '#', 's/n', 'sn', 's.n.', 's.n', 'serial', 'serial no', 'serial number', 'no.', 'no', 'index', 'sl', 'sl.no', 'sl. no.' ], true );
										$default_filter  = ! $is_serial && count( $column_stats['categories'] ?? [] ) > 1 && count( $column_stats['categories'] ?? [] ) <= 300;
										$filter_selected = null === $saved_filter_columns ? $default_filter : in_array( $column_index, $saved_filter_columns, true );
										?>
										<label class="rt-checkbox-label">
											<input type="checkbox" name="rt_filter_columns[]" value="<?php echo esc_attr( (string) (int) $column_index ); ?>" <?php checked( $filter_selected, true ); ?> />
											<span><?php echo esc_html( $column['name'] ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</fieldset>
						</div>

						<!-- ── 4. Advanced Settings (collapsible) ── -->
						<details id="rt-advanced-section" class="rt-advanced-details" open>
							<summary class="rt-advanced-summary">
								<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
								</svg>
								<?php esc_html_e( 'Advanced Settings', 'roxytable' ); ?>
								<svg class="rt-chevron" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;margin-left:auto;flex-shrink:0;transition:transform 0.2s;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
								</svg>
							</summary>
							<div class="rt-advanced-body">
								<div class="rt-field-group">
									<label class="rt-label" for="rt-custom-css"><?php esc_html_e( 'Custom CSS', 'roxytable' ); ?></label>
									<textarea id="rt-custom-css" name="rt_custom_css" class="rt-field rt-code-field" rows="4"
										placeholder="<?php echo esc_attr( "/* Your custom CSS here */\n.roxytable-table th { background: #1e293b; }" ); ?>"><?php echo esc_textarea( $custom_css ); ?></textarea>
									<p class="rt-hint"><?php esc_html_e( 'CSS scoped to this table only. Will be injected on pages containing the shortcode.', 'roxytable' ); ?></p>
								</div>

								<div class="rt-field-group" style="margin-top: 8px; padding-top: 14px; border-top: 1px solid var(--border-color);">
									<div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
										<label class="rt-label" for="rt-custom-js" style="margin: 0;"><?php esc_html_e( 'Custom JavaScript', 'roxytable' ); ?></label>
										<label class="rt-toggle-label">
											<span class="rt-toggle-switch">
												<input type="checkbox" id="rt-custom-js-enabled" name="rt_custom_js_enabled" value="1" <?php checked( $custom_js_enabled, true ); ?> <?php echo ! $can_edit_js ? 'disabled="disabled"' : ''; ?> />
												<span class="rt-toggle-slider"></span>
											</span>
											<span class="rt-toggle-text"><?php esc_html_e( 'Enable Custom JavaScript', 'roxytable' ); ?></span>
										</label>
									</div>
									<textarea id="rt-custom-js" name="rt_custom_js" class="rt-field rt-code-field" rows="5"
										placeholder="<?php echo esc_attr( "// Your custom JavaScript here\n// Available variables: table, container, tableId, settings\nconsole.log('RoxyTable loaded:', tableId);" ); ?>"
										<?php echo ! $can_edit_js ? 'readonly="readonly"' : ''; ?>><?php echo esc_textarea( $custom_js ); ?></textarea>
									<p class="rt-hint"><?php esc_html_e( 'Add custom JavaScript to customize table behavior and appearance on the frontend. Executed after the table HTML is rendered (variables available: table, container, tableId, settings). Does not execute in the WordPress admin area.', 'roxytable' ); ?></p>
									<?php if ( ! $can_edit_js ) : ?>
										<p class="rt-hint" style="color: #b45309;"><?php esc_html_e( 'Your account requires the unfiltered_html capability to modify Custom JavaScript.', 'roxytable' ); ?></p>
									<?php endif; ?>
								</div>

								<label class="rt-checkbox-label" style="margin-top: 8px;">
									<input type="checkbox" name="rt_server_side" value="1" <?php checked( $server_side, true ); ?> />
									<span><?php esc_html_e( 'Enable server-side processing (recommended for tables over 10,000 rows)', 'roxytable' ); ?></span>
								</label>
							</div>
						</details>
					</div>
				</div>

				<div style="display:flex;gap:12px;margin-top:6px;">
					<button type="submit" class="roxytable-btn roxytable-btn-primary"><?php esc_html_e( 'Save Changes', 'roxytable' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="roxytable-btn roxytable-btn-secondary"><?php esc_html_e( 'Cancel', 'roxytable' ); ?></a>
				</div>
			</div>
		</form>
	</div>

	<div style="margin-top:18px;">
		<?php if ( isset( $_GET['settings_saved'] ) && '1' === $_GET['settings_saved'] ) : ?>
			<div class="notice notice-success" style="margin:0 0 16px;padding:10px 12px;"><p style="margin:0;"><?php esc_html_e( 'Table settings saved successfully.', 'roxytable' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['template_created'] ) && '1' === $_GET['template_created'] ) : ?>
			<div class="notice notice-success" style="margin:0 0 16px;padding:10px 12px;"><p style="margin:0;"><?php esc_html_e( 'Template applied. Add your data, then publish the table when it is ready.', 'roxytable' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['data_updated'] ) && '1' === $_GET['data_updated'] ) : ?>
			<div class="notice notice-success" style="margin:0 0 16px;padding:10px 12px;"><p style="margin:0;"><?php esc_html_e( 'Table data updated successfully.', 'roxytable' ); ?></p></div>
		<?php endif; ?>

		<h4 class="rt-section-heading"><?php esc_html_e( 'Table Data', 'roxytable' ); ?></h4>
		<p class="rt-hint" style="margin:0 0 10px;"><?php esc_html_e( 'To make an institution clickable, enter a link in its cell, for example: <a href="https://coren-eac.org">KHADAB UNIVERISITY KANO</a>', 'roxytable' ); ?></p>
		<div class="roxytable-card" style="margin-top:10px;">
			<form id="rt-edit-table-data-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id ) ); ?>">
				<?php wp_nonce_field( 'roxytable_save_data', 'roxytable_save_data_nonce' ); ?>
				<input type="hidden" name="roxytable_save_data" value="1" />
				<input type="hidden" name="rt_data_table_id" value="<?php echo esc_attr( (string) $table_id ); ?>" />
				<div style="overflow-x:auto;">
					<table class="roxytable-table" style="width:100%;border-collapse:collapse;">
						<thead>
							<tr style="background:#f8fafc;">
								<th style="padding:8px;text-align:left;">#</th>
								<?php foreach ( $columns as $column ) : ?>
									<th style="padding:8px;text-align:left;min-width:180px;">
										<input type="text" class="rt-field" name="rt_column_names[<?php echo esc_attr( (string) (int) $column['id'] ); ?>]" value="<?php echo esc_attr( $column['name'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Column %s name', 'roxytable' ), $column['name'] ) ); ?>" />
									</th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="<?php echo esc_attr( (string) ( count( $columns ) + 1 ) ); ?>" style="padding:20px;text-align:center;color:var(--text-muted);"><?php esc_html_e( 'No rows found.', 'roxytable' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $rows as $row_index => $row ) : ?>
									<tr>
										<td data-label="#" style="padding:8px;white-space:nowrap;"><?php echo esc_html( (string) ( (int) $row_index + 1 ) ); ?></td>
										<?php foreach ( $columns as $column_index => $column ) : ?>
											<td data-label="<?php echo esc_attr( $column['name'] ); ?>" style="padding:8px;min-width:180px;">
												<input type="text" class="rt-field" name="rt_rows[<?php echo esc_attr( (string) (int) $row['id'] ); ?>][<?php echo esc_attr( (string) (int) $column_index ); ?>]" value="<?php echo esc_attr( $row['cells'][ $column_index ] ?? '' ); ?>" aria-label="<?php echo esc_attr( $column['name'] ); ?>" />
											</td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				<div style="margin-top:16px;display:flex;justify-content:flex-end;">
					<button type="submit" class="roxytable-btn roxytable-btn-primary"><?php esc_html_e( 'Save Table Data', 'roxytable' ); ?></button>
				</div>
			</form>
		</div>

		<h4 class="rt-section-heading"><?php esc_html_e( 'Columns', 'roxytable' ); ?></h4>
		<div class="roxytable-card" style="margin-top:10px;">
			<table style="width:100%;border-collapse:collapse;">
				<thead>
					<tr style="background:#f8fafc;">
						<th style="padding:8px;text-align:left;"><?php esc_html_e( 'Position', 'roxytable' ); ?></th>
						<th style="padding:8px;text-align:left;"><?php esc_html_e( 'Name', 'roxytable' ); ?></th>
						<th style="padding:8px;text-align:left;"><?php esc_html_e( 'Type', 'roxytable' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $columns as $col ) : ?>
						<tr>
							<td style="padding:8px;"><?php echo esc_html( (string) intval( $col['position'] ) ); ?></td>
							<td style="padding:8px;"><?php echo esc_html( $col['name'] ); ?></td>
							<td style="padding:8px;"><?php echo esc_html( $col['type'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
