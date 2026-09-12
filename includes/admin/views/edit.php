<?php
use RoxyTable\Includes\Admin\Table_Repository;

// Prevent direct access
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

$columns = Table_Repository::get_columns( $table_id );
$rows = Table_Repository::get_rows( $table_id, max( 1, (int) ( $table['total_rows'] ?? 1 ) ), 0 );
$table_settings = is_array( $table['settings'] ?? null )
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

$feat_search     = isset( $table_settings['search'] ) ? (bool) $table_settings['search'] : true;
$feat_sheet_filt = isset( $table_settings['sheet_filtering'] ) ? (bool) $table_settings['sheet_filtering'] : true;
$feat_table_filt = isset( $table_settings['table_filtering'] ) ? (bool) $table_settings['table_filtering'] : true;
$feat_sort       = isset( $table_settings['sort'] ) ? (bool) $table_settings['sort'] : true;
$feat_pagination = isset( $table_settings['pagination'] ) ? (bool) $table_settings['pagination'] : true;
$feat_visibility = isset( $table_settings['visibility'] ) ? (bool) $table_settings['visibility'] : true;
$feat_export     = isset( $table_settings['export'] ) ? (bool) $table_settings['export'] : false;
?>
<div class="roxytable-edit-container" style="max-width: 880px; margin: 0 auto;">
    <div style="margin-bottom:24px;">
        <h2 style="font-size:1.5rem;font-weight:700;margin:0;color:var(--text-main);"><?php _e( 'Edit Table', 'roxytable' ); ?></h2>
        <p style="margin:4px 0 0 0;color:var(--text-muted);font-size:0.875rem;">
            <?php _e( 'Update the table metadata and settings below.', 'roxytable' ); ?>
        </p>
    </div>

    <div class="roxytable-card">
        <form id="rt-edit-table-meta-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>">
            <?php wp_nonce_field( 'roxytable_save_table', 'roxytable_save_table_nonce' ); ?>
            <input type="hidden" name="roxytable_save_table" value="1" />
            <input type="hidden" name="rt_table_id" value="<?php echo intval( $table_id ); ?>" />
            <input type="hidden" name="rt_has_options" value="1" />

            <div style="display:flex;flex-direction:column;gap:18px;">
                <div>
                    <label class="rt-label" for="rt-table-name"><?php _e( 'Table Name', 'roxytable' ); ?></label>
                    <input id="rt-table-name" name="rt_table_name" type="text" class="rt-field" value="<?php echo esc_attr( $table['name'] ); ?>" required maxlength="255" />
                </div>

                <div>
                    <label class="rt-label" for="rt-table-slug"><?php _e( 'Slug', 'roxytable' ); ?></label>
                    <input id="rt-table-slug" name="rt_table_slug" type="text" class="rt-field" value="<?php echo esc_attr( $table['slug'] ); ?>" maxlength="255" pattern="[a-z0-9\-]+" />
                </div>

                <div>
                    <label class="rt-label" for="rt-table-desc"><?php _e( 'Description', 'roxytable' ); ?></label>
                    <textarea id="rt-table-desc" name="rt_table_description" class="rt-field" rows="3"><?php echo esc_textarea( $table['description'] ); ?></textarea>
                </div>

                <div>
                    <label class="rt-label" for="rt-table-status"><?php _e( 'Status', 'roxytable' ); ?></label>
                    <select id="rt-table-status" name="rt_table_status" class="rt-field" style="max-width:240px;">
                        <option value="published" <?php selected( 'published', $table['status'] ); ?>><?php _e( 'Published', 'roxytable' ); ?></option>
                        <option value="draft" <?php selected( 'draft', $table['status'] ); ?>><?php _e( 'Draft', 'roxytable' ); ?></option>
                    </select>
                </div>

                <div>
                    <label class="rt-label" for="rt-table-template"><?php _e( 'Frontend Template', 'roxytable' ); ?></label>
                    <select id="rt-table-template" name="rt_table_template" class="rt-field" style="max-width:300px;">
                        <option value="" <?php selected( '', $current_template ); ?>><?php _e( 'Default table style', 'roxytable' ); ?></option>
                        <option value="indigo" <?php selected( 'indigo', $current_template ); ?>><?php _e( 'Indigo Clean', 'roxytable' ); ?></option>
                        <option value="obsidian" <?php selected( 'obsidian', $current_template ); ?>><?php _e( 'Dark Minimal', 'roxytable' ); ?></option>
                        <option value="emerald" <?php selected( 'emerald', $current_template ); ?>><?php _e( 'Emerald Grid', 'roxytable' ); ?></option>
                    </select>
                </div>

                <!-- ── Table Options & Features Section ── -->
                <div class="rt-options-panel">
                    <div class="rt-options-header">
                        <div>
                            <h4 style="margin:0; font-size:1.05rem; font-weight:700; color:var(--text-main);"><?php _e( 'Table Options & Features', 'roxytable' ); ?></h4>
                            <p style="margin:3px 0 0; font-size:0.8125rem; color:var(--text-muted);"><?php _e( 'Configure parsing rules and interactive frontend features for this table.', 'roxytable' ); ?></p>
                        </div>
                        <button type="button" id="rt-toggle-options-btn" class="rt-options-toggle-btn" aria-expanded="true" aria-controls="rt-table-options-section">
                            <svg class="rt-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                            <span id="rt-toggle-options-text"><?php _e( 'Hide Options', 'roxytable' ); ?></span>
                        </button>
                    </div>

                    <div id="rt-table-options-section" class="rt-options-container" data-table-id="<?php echo intval( $table_id ); ?>">
                        <!-- ── 2. Parse Options ── -->
                        <div class="rt-options-block">
                            <h5 class="rt-section-heading"><?php _e( '2. Parse Options', 'roxytable' ); ?></h5>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px 28px;">
                                <label class="rt-checkbox-label">
                                    <input type="checkbox" name="rt_first_row_headers" value="1" <?php checked( $opt_first_row, true ); ?> />
                                    <span><?php _e( 'First row contains headers', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-checkbox-label">
                                    <input type="checkbox" name="rt_auto_detect_types" value="1" <?php checked( $opt_auto_detect, true ); ?> />
                                    <span><?php _e( 'Auto-detect column types', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-checkbox-label">
                                    <input type="checkbox" name="rt_remove_empty_rows" value="1" <?php checked( $opt_remove_rows, true ); ?> />
                                    <span><?php _e( 'Remove empty rows', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-checkbox-label">
                                    <input type="checkbox" name="rt_remove_empty_cols" value="1" <?php checked( $opt_remove_cols, true ); ?> />
                                    <span><?php _e( 'Remove empty columns', 'roxytable' ); ?></span>
                                </label>
                            </div>
                        </div>

                        <!-- ── 3. Frontend Features ── -->
                        <div class="rt-options-block">
                            <h5 class="rt-section-heading"><?php _e( '3. Frontend Features', 'roxytable' ); ?></h5>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 28px;">
                                <label class="rt-toggle-label">
                                    <span class="rt-toggle-switch">
                                        <input type="checkbox" name="rt_feat_search" value="1" <?php checked( $feat_search, true ); ?> />
                                        <span class="rt-toggle-slider"></span>
                                    </span>
                                    <span class="rt-toggle-text"><?php _e( 'Enable search', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-toggle-label">
                                    <span class="rt-toggle-switch">
                                        <input type="checkbox" name="rt_feat_sheet_filter" value="1" <?php checked( $feat_sheet_filt, true ); ?> />
                                        <span class="rt-toggle-slider"></span>
                                    </span>
                                    <span class="rt-toggle-text"><?php _e( 'Sheet-based filtering', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-toggle-label">
                                    <span class="rt-toggle-switch">
                                        <input type="checkbox" name="rt_feat_table_filter" value="1" <?php checked( $feat_table_filt, true ); ?> />
                                        <span class="rt-toggle-slider"></span>
                                    </span>
                                    <span class="rt-toggle-text"><?php _e( 'Table-based filtering', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-toggle-label">
                                    <span class="rt-toggle-switch">
                                        <input type="checkbox" name="rt_feat_sort" value="1" <?php checked( $feat_sort, true ); ?> />
                                        <span class="rt-toggle-slider"></span>
                                    </span>
                                    <span class="rt-toggle-text"><?php _e( 'Enable sort', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-toggle-label">
                                    <span class="rt-toggle-switch">
                                        <input type="checkbox" name="rt_feat_pagination" value="1" <?php checked( $feat_pagination, true ); ?> />
                                        <span class="rt-toggle-slider"></span>
                                    </span>
                                    <span class="rt-toggle-text"><?php _e( 'Enable pagination', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-toggle-label">
                                    <span class="rt-toggle-switch">
                                        <input type="checkbox" name="rt_feat_visibility" value="1" <?php checked( $feat_visibility, true ); ?> />
                                        <span class="rt-toggle-slider"></span>
                                    </span>
                                    <span class="rt-toggle-text"><?php _e( 'Column visibility toggle', 'roxytable' ); ?></span>
                                </label>
                                <label class="rt-toggle-label">
                                    <span class="rt-toggle-switch">
                                        <input type="checkbox" name="rt_feat_export" value="1" <?php checked( $feat_export, true ); ?> />
                                        <span class="rt-toggle-slider"></span>
                                    </span>
                                    <span class="rt-toggle-text"><?php _e( 'Enable export (CSV)', 'roxytable' ); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:6px;">
                    <button type="submit" class="roxytable-btn roxytable-btn-primary"><?php _e( 'Save Changes', 'roxytable' ); ?></button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="roxytable-btn roxytable-btn-secondary"><?php _e( 'Cancel', 'roxytable' ); ?></a>
                </div>
            </div>
        </form>
    </div>

    <div style="margin-top:18px;">
        <?php if ( isset( $_GET['template_created'] ) && '1' === $_GET['template_created'] ) : ?>
            <div class="notice notice-success" style="margin:0 0 16px;padding:10px 12px;"><p style="margin:0;"><?php _e( 'Template applied. Add your data, then publish the table when it is ready.', 'roxytable' ); ?></p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['data_updated'] ) && '1' === $_GET['data_updated'] ) : ?>
            <div class="notice notice-success" style="margin:0 0 16px;padding:10px 12px;"><p style="margin:0;"><?php _e( 'Table data updated successfully.', 'roxytable' ); ?></p></div>
        <?php endif; ?>

        <h4 class="rt-section-heading"><?php _e( 'Table Data', 'roxytable' ); ?></h4>
		<p class="rt-hint" style="margin:0 0 10px;"><?php _e( 'To make an institution clickable, enter a link in its cell, for example: <a href="https://coren-eac.org">KHADAB UNIVERISITY KANO</a>', 'roxytable' ); ?></p>
        <div class="roxytable-card" style="margin-top:10px;">
            <form id="rt-edit-table-data-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id ) ); ?>">
                <?php wp_nonce_field( 'roxytable_save_data', 'roxytable_save_data_nonce' ); ?>
                <input type="hidden" name="roxytable_save_data" value="1" />
                <input type="hidden" name="rt_data_table_id" value="<?php echo esc_attr( $table_id ); ?>" />
                <div style="overflow-x:auto;">
                    <table class="roxytable-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="padding:8px;text-align:left;">#</th>
                                <?php foreach ( $columns as $column ) : ?>
                                    <th style="padding:8px;text-align:left;min-width:180px;">
                                        <input type="text" class="rt-field" name="rt_column_names[<?php echo (int) $column['id']; ?>]" value="<?php echo esc_attr( $column['name'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Column %s name', 'roxytable' ), $column['name'] ) ); ?>" />
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $rows ) ) : ?>
                                <tr><td colspan="<?php echo count( $columns ) + 1; ?>" style="padding:20px;text-align:center;color:var(--text-muted);"><?php _e( 'No rows found.', 'roxytable' ); ?></td></tr>
                            <?php else : ?>
                                <?php foreach ( $rows as $row_index => $row ) : ?>
                                    <tr>
                                        <td data-label="#" style="padding:8px;white-space:nowrap;"><?php echo (int) $row_index + 1; ?></td>
                                        <?php foreach ( $columns as $column_index => $column ) : ?>
                                            <td data-label="<?php echo esc_attr( $column['name'] ); ?>" style="padding:8px;min-width:180px;">
                                                <input type="text" class="rt-field" name="rt_rows[<?php echo (int) $row['id']; ?>][<?php echo (int) $column_index; ?>]" value="<?php echo esc_attr( $row['cells'][ $column_index ] ?? '' ); ?>" aria-label="<?php echo esc_attr( $column['name'] ); ?>" />
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                    <button type="submit" class="roxytable-btn roxytable-btn-primary"><?php _e( 'Save Table Data', 'roxytable' ); ?></button>
                </div>
            </form>
        </div>

        <h4 class="rt-section-heading"><?php _e( 'Columns', 'roxytable' ); ?></h4>
        <div class="roxytable-card" style="margin-top:10px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:8px;text-align:left;"><?php _e( 'Position', 'roxytable' ); ?></th>
                        <th style="padding:8px;text-align:left;"><?php _e( 'Name', 'roxytable' ); ?></th>
                        <th style="padding:8px;text-align:left;"><?php _e( 'Type', 'roxytable' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $columns as $col ) : ?>
                        <tr>
                            <td style="padding:8px;"><?php echo intval( $col['position'] ); ?></td>
                            <td style="padding:8px;"><?php echo esc_html( $col['name'] ); ?></td>
                            <td style="padding:8px;"><?php echo esc_html( $col['type'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
