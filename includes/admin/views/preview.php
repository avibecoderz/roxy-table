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
$rows = Table_Repository::get_rows( $table_id, 50, 0 );

?>
<div class="roxytable-preview-admin" style="max-width:1200px;margin:0 auto;">
    <div style="margin-bottom:12px;">
        <h1 style="font-size:1.75rem;margin:0;color:var(--text-main);font-weight:700;"><?php echo esc_html( $table['name'] ); ?></h1>
        <?php if ( ! empty( $table['description'] ) ) : ?>
            <p style="margin:6px 0 0;color:var(--text-muted);"><?php echo esc_html( $table['description'] ); ?></p>
        <?php endif; ?>
        <?php if ( isset( $table['status'] ) && 'draft' === $table['status'] ) : ?>
            <div style="margin-top:8px;color:#92400e;background:#fff7ed;padding:8px;border-radius:6px;border:1px solid #f3e6d9;">
                <?php _e( 'Note: This table is currently saved as a draft and will not appear on the public site.', 'roxytable' ); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="roxytable-card" style="padding:12px 16px 16px;">
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
            <input id="roxytable-preview-search" type="search" placeholder="Search <?php echo esc_attr( $table['name'] ); ?>..." style="flex:1 1 320px;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);" />
            <div id="roxytable-preview-filters" style="display:flex;gap:12px;flex-wrap:wrap;"></div>
            <button class="roxytable-btn roxytable-btn-secondary" style="white-space:nowrap;"><?php _e( 'Export', 'roxytable' ); ?></button>
        </div>

        <div style="overflow-x:auto;">
            <table id="roxytable-preview-table" class="roxytable-preview-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <?php foreach ( $columns as $col ) : ?>
                            <th style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid var(--border-color);text-align:left;font-size:0.8125rem;color:var(--text-muted);"><?php echo esc_html( $col['name'] ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                        <tr><td colspan="<?php echo count( $columns ); ?>" style="padding:24px;text-align:center;color:var(--text-muted);"><?php _e( 'No rows found.', 'roxytable' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $rows as $r ) : ?>
                            <tr class="rt-preview-row" style="border-bottom:1px solid var(--border-color);">
                                <?php foreach ( $r['cells'] as $cell ) : 
                                    $cell_text = trim( (string) $cell );
                                    $lc = strtolower( $cell_text );
                                    $is_status = false;
                                    $status_class = '';
                                    if ( in_array( $lc, [ 'passed', 'pass', 'passed' ], true ) ) { $is_status = true; $status_class = 'rt-status-pass'; }
                                    if ( in_array( $lc, [ 'referred', 'failed', 'fail' ], true ) ) { $is_status = true; $status_class = 'rt-status-refer'; }
                                ?>
                                    <td style="padding:12px 14px;">
                                        <?php if ( $is_status ) : ?>
                                            <span class="rt-status-pill <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $cell_text ); ?></span>
                                        <?php else : ?>
                                            <?php echo esc_html( $cell_text ); ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;">
            <div style="color:var(--text-muted);font-size:0.875rem;">Showing <?php echo count( $rows ); ?> of <?php echo intval( $table['total_rows'] ?? 0 ); ?> records</div>
            <div class="rt-pagination" style="display:flex;gap:8px;align-items:center;">
                <button class="roxytable-btn roxytable-btn-secondary" disabled>&lt; Prev</button>
                <button class="roxytable-btn roxytable-btn-secondary">1</button>
                <button class="roxytable-btn roxytable-btn-secondary">2</button>
                <button class="roxytable-btn roxytable-btn-secondary">Next &gt;</button>
            </div>
        </div>
    </div>
</div>
