<?php
use RoxyTable\Includes\Admin\Table_Repository;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$table_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$table = $table_id ? Table_Repository::get_table( $table_id ) : null;
if ( ! $table ) {
    wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
    exit;
}

$columns = Table_Repository::get_columns( $table_id );
?>
<div class="roxytable-edit-container" style="max-width: 880px; margin: 0 auto;">
    <div style="margin-bottom:24px;">
        <h2 style="font-size:1.5rem;font-weight:700;margin:0;color:var(--text-main);"><?php _e( 'Edit Table', 'roxytable' ); ?></h2>
        <p style="margin:4px 0 0 0;color:var(--text-muted);font-size:0.875rem;">
            <?php _e( 'Update the table metadata and settings below.', 'roxytable' ); ?>
        </p>
    </div>

    <div class="roxytable-card">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>">
            <?php wp_nonce_field( 'roxytable_save_table', 'roxytable_save_table_nonce' ); ?>
            <input type="hidden" name="roxytable_save_table" value="1" />
            <input type="hidden" name="rt_table_id" value="<?php echo intval( $table_id ); ?>" />

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

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="roxytable-btn roxytable-btn-primary"><?php _e( 'Save Changes', 'roxytable' ); ?></button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="roxytable-btn roxytable-btn-secondary"><?php _e( 'Cancel', 'roxytable' ); ?></a>
                </div>
            </div>
        </form>
    </div>

    <div style="margin-top:18px;">
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
