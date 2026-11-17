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
$watermark_url     = isset( $table_settings['watermark_url'] ) ? (string) $table_settings['watermark_url'] : '';
$watermark_opacity = isset( $table_settings['watermark_opacity'] ) ? floatval( $table_settings['watermark_opacity'] ) : 0.15;
$watermark_size    = isset( $table_settings['watermark_size'] ) ? sanitize_key( $table_settings['watermark_size'] ) : 'contain';
$custom_css        = isset( $table_settings['custom_css'] ) ? (string) $table_settings['custom_css'] : '';
$custom_js         = isset( $table_settings['custom_js'] ) ? (string) $table_settings['custom_js'] : '';
$custom_js_enabled = isset( $table_settings['custom_js_enabled'] ) ? ! empty( $table_settings['custom_js_enabled'] ) : true;
$is_pro            = ( ! class_exists( '\RoxyTable\Includes\License' ) || \RoxyTable\Includes\License::is_pro() );
$can_edit_js       = $is_pro && current_user_can( 'manage_options' ) && ( ! function_exists( 'current_user_can' ) || current_user_can( 'unfiltered_html' ) );
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
						<option value="obsidian" <?php selected( 'obsidian', $current_template ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?>><?php echo esc_html( __( 'Dark Minimal', 'roxytable' ) . ( ! $is_pro ? ' (PRO)' : '' ) ); ?></option>
						<option value="emerald" <?php selected( 'emerald', $current_template ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?>><?php echo esc_html( __( 'Emerald Grid', 'roxytable' ) . ( ! $is_pro ? ' (PRO)' : '' ) ); ?></option>
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
								<label class="rt-checkbox-label" for="rt-first-row-headers">
									<input type="checkbox" id="rt-first-row-headers" name="rt_first_row_headers" value="1" <?php checked( $opt_first_row, true ); ?> />
									<span><?php esc_html_e( 'First row contains headers', 'roxytable' ); ?></span>
								</label>
								<label class="rt-checkbox-label" for="rt-auto-detect-types">
									<input type="checkbox" id="rt-auto-detect-types" name="rt_auto_detect_types" value="1" <?php checked( $opt_auto_detect, true ); ?> />
									<span><?php esc_html_e( 'Auto-detect column types', 'roxytable' ); ?></span>
								</label>
								<label class="rt-checkbox-label" for="rt-remove-empty-rows">
									<input type="checkbox" id="rt-remove-empty-rows" name="rt_remove_empty_rows" value="1" <?php checked( $opt_remove_rows, true ); ?> />
									<span><?php esc_html_e( 'Remove empty rows', 'roxytable' ); ?></span>
								</label>
								<label class="rt-checkbox-label" for="rt-remove-empty-cols">
									<input type="checkbox" id="rt-remove-empty-cols" name="rt_remove_empty_cols" value="1" <?php checked( $opt_remove_cols, true ); ?> />
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
										<input type="checkbox" name="rt_feat_sheet_filter" value="1" <?php checked( $is_pro && $feat_sheet_filt, true ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Sheet-based filtering', 'roxytable' ); ?> <span class="rt-pro-badge">PRO</span></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_table_filter" value="1" <?php checked( $is_pro && $feat_table_filt, true ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Table-based filtering', 'roxytable' ); ?> <span class="rt-pro-badge">PRO</span></span>
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
										<input type="checkbox" name="rt_feat_visibility" value="1" <?php checked( $is_pro && $feat_visibility, true ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Column visibility toggle', 'roxytable' ); ?> <span class="rt-pro-badge">PRO</span></span>
								</label>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="rt_feat_export" value="1" <?php checked( $is_pro && $feat_export, true ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php esc_html_e( 'Enable export (CSV)', 'roxytable' ); ?> <span class="rt-pro-badge">PRO</span></span>
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
							<fieldset style="margin-top:20px;border:0;padding:0;<?php echo ! $is_pro ? 'opacity:0.65;' : ''; ?>">
								<legend class="rt-label"><?php esc_html_e( 'Frontend filter columns', 'roxytable' ); ?> <span class="rt-pro-badge">PRO</span></legend>
								<p class="rt-hint">
									<?php esc_html_e( 'Choose which columns visitors can filter. Every table column is available.', 'roxytable' ); ?>
									<?php if ( ! $is_pro ) : ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-settings' ) ); ?>" style="font-weight:600;margin-left:4px;"><?php esc_html_e( 'Activate Pro to unlock →', 'roxytable' ); ?></a>
									<?php endif; ?>
								</p>
								<?php if ( $is_pro ) : ?>
									<input type="hidden" name="rt_filter_columns_configured" value="1" />
								<?php endif; ?>
								<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;max-height:260px;overflow:auto;padding:8px;border:1px solid var(--border-color);border-radius:4px;">
									<?php
									foreach ( $columns as $column_index => $column ) :
										$column_stats    = $filter_stats[ $column_index ] ?? [];
										$column_name     = strtolower( trim( $column['name'] ) );
										$is_serial       = in_array( $column_name, [ '#', 's/n', 'sn', 's.n.', 's.n', 'serial', 'serial no', 'serial number', 'no.', 'no', 'index', 'sl', 'sl.no', 'sl. no.' ], true );
										$default_filter  = count( $column_stats['categories'] ?? [] ) > 1 && count( $column_stats['categories'] ?? [] ) <= 300;
										$filter_selected = null === $saved_filter_columns ? $default_filter : in_array( $column_index, $saved_filter_columns, true );
										?>
										<label class="rt-checkbox-label">
											<input type="checkbox" name="rt_filter_columns[]" value="<?php echo esc_attr( (string) (int) $column_index ); ?>" <?php checked( $is_pro && $filter_selected, true ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?> />
											<span><?php echo esc_html( $column['name'] ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</fieldset>
						</div>

						<!-- ── 4. Table Watermark ── -->
						<div class="rt-options-block rt-watermark-card" id="rt-watermark-options">
							<div class="rt-watermark-header">
								<div>
									<h5 class="rt-section-heading" style="margin-bottom:4px;border-bottom:none;padding-bottom:0;">
										<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;margin-right:6px;vertical-align:-2px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
										</svg>
										<?php esc_html_e( '4. Table Watermark', 'roxytable' ); ?>
									</h5>
									<p class="rt-hint" style="margin:0;">
										<?php esc_html_e( 'Upload or select an organization logo, seal, or emblem to display as a background watermark on this table.', 'roxytable' ); ?>
									</p>
								</div>
								<div id="rt-watermark-status-badge" class="rt-watermark-badge <?php echo $watermark_url ? 'is-active' : 'is-empty'; ?>">
									<span class="rt-watermark-badge-dot"></span>
									<span id="rt-watermark-status-text" class="rt-watermark-badge-text"><?php echo $watermark_url ? esc_html__( 'Active', 'roxytable' ) : esc_html__( 'No Watermark Set', 'roxytable' ); ?></span>
								</div>
							</div>

							<div class="rt-watermark-layout">
								<!-- Left Column: Media Uploader & Preview Card -->
								<div class="rt-watermark-media-col">
									<div id="rt-watermark-preview-box" class="rt-watermark-preview-box<?php echo $watermark_url ? ' has-image' : ''; ?>" role="button" tabindex="0" title="<?php esc_attr_e( 'Click to choose or change watermark image', 'roxytable' ); ?>">
										<?php if ( $watermark_url ) : ?>
											<img id="rt-watermark-preview-img" class="rt-watermark-preview-img" src="<?php echo esc_url( $watermark_url ); ?>" alt="<?php esc_attr_e( 'Watermark Preview', 'roxytable' ); ?>" />
											<div class="rt-watermark-hover-overlay">
												<svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
													<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
												</svg>
												<span><?php esc_html_e( 'Change Image', 'roxytable' ); ?></span>
											</div>
										<?php else : ?>
											<div id="rt-watermark-placeholder" class="rt-watermark-preview-placeholder">
												<div class="rt-watermark-icon-wrap">
													<svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
														<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
													</svg>
												</div>
												<span class="rt-watermark-empty-title"><?php esc_html_e( 'Select Watermark', 'roxytable' ); ?></span>
												<span class="rt-watermark-empty-sub"><?php esc_html_e( 'PNG, SVG, JPG, WebP', 'roxytable' ); ?></span>
											</div>
										<?php endif; ?>
									</div>
									<div class="rt-watermark-btn-row">
										<button type="button" id="rt-select-watermark-btn" class="roxytable-btn roxytable-btn-secondary rt-btn-watermark-choose">
											<svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
											</svg>
											<span id="rt-select-btn-text"><?php echo $watermark_url ? esc_html__( 'Change Image', 'roxytable' ) : esc_html__( 'Select Image', 'roxytable' ); ?></span>
										</button>
										<button type="button" id="rt-remove-watermark-btn" class="roxytable-btn roxytable-btn-secondary rt-btn-watermark-remove" style="<?php echo $watermark_url ? '' : 'display:none;'; ?>">
											<svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
											</svg>
											<?php esc_html_e( 'Remove', 'roxytable' ); ?>
										</button>
									</div>
									<input type="hidden" id="rt-watermark-url" name="rt_watermark_url" value="<?php echo esc_attr( $watermark_url ); ?>" />
								</div>

								<!-- Right Column: Settings, Presets & Live Table Simulation -->
								<div class="rt-watermark-controls-col">
									<div class="rt-watermark-control-group">
										<div class="rt-watermark-label-row">
											<label class="rt-label" for="rt-watermark-opacity-range">
												<?php esc_html_e( 'Watermark Opacity', 'roxytable' ); ?>
											</label>
											<span id="rt-watermark-opacity-val" class="rt-watermark-pill"><?php echo esc_html( (string) round( $watermark_opacity * 100 ) . '%' ); ?></span>
										</div>
										<div class="rt-watermark-slider-wrap">
											<input type="range" id="rt-watermark-opacity-range" name="rt_watermark_opacity" class="rt-slider rt-watermark-slider" min="0.02" max="0.80" step="0.01" value="<?php echo esc_attr( (string) $watermark_opacity ); ?>" />
										</div>
										<div class="rt-watermark-presets-row">
											<span class="rt-presets-label"><?php esc_html_e( 'Quick presets:', 'roxytable' ); ?></span>
											<button type="button" class="rt-preset-chip" data-opacity="0.06">6% <?php esc_html_e( 'Faint', 'roxytable' ); ?></button>
											<button type="button" class="rt-preset-chip" data-opacity="0.12">12% <?php esc_html_e( 'Subtle', 'roxytable' ); ?></button>
											<button type="button" class="rt-preset-chip" data-opacity="0.20">20% <?php esc_html_e( 'Balanced', 'roxytable' ); ?></button>
											<button type="button" class="rt-preset-chip" data-opacity="0.35">35% <?php esc_html_e( 'Distinct', 'roxytable' ); ?></button>
										</div>
										<p class="rt-hint" style="margin-top:6px;">
											<?php esc_html_e( 'Recommended: 10% – 25% for high readability behind table text.', 'roxytable' ); ?>
										</p>
									</div>

									<div class="rt-watermark-control-group" style="margin-top:14px;">
										<label class="rt-label" for="rt-watermark-size">
											<?php esc_html_e( 'Watermark Size', 'roxytable' ); ?>
										</label>
										<div style="max-width:320px;margin-top:4px;">
											<select id="rt-watermark-size" name="rt_watermark_size" class="rt-field">
												<option value="contain" <?php selected( 'contain', $watermark_size ); ?>><?php esc_html_e( 'Auto-Contain (Recommended)', 'roxytable' ); ?></option>
												<option value="small" <?php selected( 'small', $watermark_size ); ?>><?php esc_html_e( 'Small (180px)', 'roxytable' ); ?></option>
												<option value="medium" <?php selected( 'medium', $watermark_size ); ?>><?php esc_html_e( 'Medium (320px)', 'roxytable' ); ?></option>
												<option value="large" <?php selected( 'large', $watermark_size ); ?>><?php esc_html_e( 'Large (480px)', 'roxytable' ); ?></option>
												<option value="cover" <?php selected( 'cover', $watermark_size ); ?>><?php esc_html_e( 'Cover Full Table', 'roxytable' ); ?></option>
											</select>
										</div>
									</div>

									<!-- Live Mini Table Simulator -->
									<div class="rt-watermark-simulator-wrap">
										<div class="rt-watermark-simulator-head">
											<span class="rt-sim-title"><?php esc_html_e( 'Live Table Simulation', 'roxytable' ); ?></span>
											<span class="rt-sim-hint"><?php esc_html_e( 'Real-time preview over table rows', 'roxytable' ); ?></span>
										</div>
										<div class="rt-sim-table-card">
											<div id="rt-watermark-sim-overlay" class="rt-watermark-sim-overlay" style="<?php echo $watermark_url ? 'background-image:url(\'' . esc_url( $watermark_url ) . '\');opacity:' . esc_attr( (string) $watermark_opacity ) . ';' : 'display:none;'; ?>"></div>
											<div class="rt-sim-table-content">
												<div class="rt-sim-row rt-sim-head-row">
													<div class="rt-sim-col" style="width:20%;"><span>S/N</span></div>
													<div class="rt-sim-col" style="width:45%;"><span>University / Item</span></div>
													<div class="rt-sim-col" style="width:35%;"><span>Status</span></div>
												</div>
												<div class="rt-sim-row">
													<div class="rt-sim-col" style="width:20%;"><div class="rt-sim-bar" style="width:18px;"></div></div>
													<div class="rt-sim-col" style="width:45%;"><div class="rt-sim-bar" style="width:110px;"></div></div>
													<div class="rt-sim-col" style="width:35%;"><div class="rt-sim-pill rt-sim-green"></div></div>
												</div>
												<div class="rt-sim-row">
													<div class="rt-sim-col" style="width:20%;"><div class="rt-sim-bar" style="width:22px;"></div></div>
													<div class="rt-sim-col" style="width:45%;"><div class="rt-sim-bar" style="width:90px;"></div></div>
													<div class="rt-sim-col" style="width:35%;"><div class="rt-sim-pill rt-sim-blue"></div></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- ── 5. Advanced Settings (collapsible) ── -->
						<details id="rt-advanced-section" class="rt-advanced-details" open>
							<summary class="rt-advanced-summary">
								<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
								</svg>
								<?php esc_html_e( '5. Advanced Settings', 'roxytable' ); ?> <span class="rt-pro-badge">PRO</span>
								<svg class="rt-chevron" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;margin-left:auto;flex-shrink:0;transition:transform 0.2s;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
								</svg>
							</summary>
							<div class="rt-advanced-body">
								<?php if ( ! $is_pro ) : ?>
									<div class="rt-pro-locked-banner">
										<div>
											<strong><?php esc_html_e( 'RoxyTable Pro Feature:', 'roxytable' ); ?></strong>
											<?php esc_html_e( 'Scoped Custom CSS, Custom JavaScript automation, and Server-Side AJAX processing require an active RoxyTable Pro annual license.', 'roxytable' ); ?>
										</div>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-settings' ) ); ?>" class="rt-dash-btn rt-dash-btn-primary" style="white-space: nowrap;">
											<?php esc_html_e( 'Activate Pro License →', 'roxytable' ); ?>
										</a>
									</div>
								<?php endif; ?>
								<div class="rt-field-group">
									<label class="rt-label" for="rt-custom-css"><?php esc_html_e( 'Custom CSS', 'roxytable' ); ?></label>
									<textarea id="rt-custom-css" name="rt_custom_css" class="rt-field rt-code-field" rows="4"
										placeholder="<?php echo esc_attr( "/* Your custom CSS here */\n.roxytable-table th { background: #1e293b; }" ); ?>" <?php echo ! $is_pro ? 'readonly="readonly"' : ''; ?>><?php echo esc_textarea( $custom_css ); ?></textarea>
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
									<input type="checkbox" name="rt_server_side" value="1" <?php checked( $is_pro && $server_side, true ); ?> <?php echo ! $is_pro ? 'disabled="disabled"' : ''; ?> />
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
