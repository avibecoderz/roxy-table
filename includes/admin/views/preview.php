<?php
use RoxyTable\Includes\Admin\Table_Repository;
use RoxyTable\Includes\Frontend;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'roxytable' ) );
}

$table_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$table    = $table_id ? Table_Repository::get_table( $table_id ) : null;
if ( ! $table ) {
	wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
	exit;
}

$columns      = Table_Repository::get_columns( $table_id );
$per_page     = 50;
$total_rows   = Table_Repository::count_rows( $table_id );
$total_pages  = max( 1, (int) ceil( $total_rows / $per_page ) );
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
$current_page = min( $current_page, $total_pages );
$rows         = Table_Repository::get_rows( $table_id, $per_page, ( $current_page - 1 ) * $per_page );
$preview_url  = admin_url( 'admin.php?page=roxytable-tables&action=preview&id=' . $table_id . '&paged=' );
$settings     = is_array( $table['settings'] ?? null )
	? $table['settings']
	: ( json_decode( (string) ( $table['settings'] ?? '' ), true ) ?: [] );
$template_slug  = ! empty( $settings['template'] ) ? sanitize_html_class( $settings['template'] ) : '';
$template_class = isset( $template_class ) ? $template_class : ( $template_slug ? ' roxytable-template-' . $template_slug : '' );
$stats          = Table_Repository::get_column_statistics( $table_id );
$sheets         = isset( $settings['sheets'] ) && is_array( $settings['sheets'] ) ? $settings['sheets'] : [];

// Prepare settings for table preview; sanitize Custom JS when enabled.
$preview_settings = $settings;
if ( ( ! isset( $settings['custom_js_enabled'] ) || ! empty( $settings['custom_js_enabled'] ) ) && ! empty( $settings['custom_js'] ) ) {
	$preview_settings['custom_js_enabled'] = true;
	$preview_settings['custom_js']         = Frontend::sanitize_custom_js( (string) $settings['custom_js'] );
} else {
	$preview_settings['custom_js_enabled'] = false;
	unset( $preview_settings['custom_js'] );
}
?>
<div class="roxytable-preview-admin<?php echo esc_attr( $template_class ); ?>" style="max-width:1200px;margin:0 auto;">
	<div style="margin-bottom:12px;">
		<h1 style="font-size:1.75rem;margin:0;color:var(--text-main);font-weight:700;"><?php echo esc_html( $table['name'] ); ?></h1>
		<?php if ( ! empty( $table['description'] ) ) : ?>
			<p style="margin:6px 0 0;color:var(--text-muted);"><?php echo esc_html( $table['description'] ); ?></p>
		<?php endif; ?>
		<?php if ( isset( $table['status'] ) && 'draft' === $table['status'] ) : ?>
			<div style="margin-top:8px;color:#92400e;background:#fff7ed;padding:8px;border-radius:6px;border:1px solid #f3e6d9;">
				<?php esc_html_e( 'Note: This table is currently saved as a draft and will not appear on the public site.', 'roxytable' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="roxytable-card roxytable-embed"
		data-table-id="<?php echo esc_attr( $table_id ); ?>"
		data-total-rows="<?php echo esc_attr( $total_rows ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'roxytable_filter_nonce' ) ); ?>"
		data-stats="<?php echo esc_attr( wp_json_encode( $stats ) ); ?>"
		data-sheets="<?php echo esc_attr( wp_json_encode( $sheets ) ); ?>"
		data-settings="<?php echo esc_attr( wp_json_encode( $preview_settings ) ); ?>"
		style="padding:12px 16px 16px;">

		<?php
		if ( ! empty( $settings['custom_css'] ) ) {
			$scoped_css = Frontend::format_scoped_css( $table_id, (string) $settings['custom_css'] );
			if ( '' !== $scoped_css ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized & validated by Frontend::format_scoped_css().
				echo '<style class="roxytable-custom-css" data-table-id="' . esc_attr( $table_id ) . '">' . $scoped_css . '</style>';
			}
		}
		?>

		<div style="overflow-x:auto;">
			<table id="roxytable-preview-table" class="roxytable-preview-table roxytable-table" style="width:100%;border-collapse:collapse;">
				<thead>
					<tr>
						<?php foreach ( $columns as $col ) : ?>
							<th style="padding:12px 14px;background:#f3f4f6;border-bottom:1px solid var(--border-color);text-align:left;font-size:0.8125rem;color:var(--text-muted);"><?php echo esc_html( $col['name'] ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="<?php echo esc_attr( count( $columns ) ); ?>" style="padding:24px;text-align:center;color:var(--text-muted);"><?php esc_html_e( 'No rows found.', 'roxytable' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $r ) : ?>
							<tr class="rt-preview-row" style="border-bottom:1px solid var(--border-color);">
								<?php
								foreach ( $r['cells'] as $cell_index => $cell ) :
									$cell_text    = trim( wp_strip_all_tags( (string) $cell ) );
									$lc           = strtolower( $cell_text );
									$is_status    = false;
									$status_class = '';
									if ( in_array( $lc, [ 'passed', 'pass' ], true ) ) {
										$is_status    = true;
										$status_class = 'rt-status-pass';
									}
									if ( in_array( $lc, [ 'referred', 'failed', 'fail' ], true ) ) {
										$is_status    = true;
										$status_class = 'rt-status-refer';
									}
									?>
									<td data-label="<?php echo esc_attr( $columns[ $cell_index ]['name'] ?? '' ); ?>" style="padding:12px 14px;">
										<?php if ( $is_status ) : ?>
											<span class="rt-status-pill <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $cell_text ); ?></span>
										<?php else : ?>
											<?php echo wp_kses_post( (string) $cell ); ?>
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
			<div style="color:var(--text-muted);font-size:0.875rem;">
				<?php
				printf(
					/* translators: 1: number of displayed rows, 2: total number of rows */
					esc_html__( 'Showing %1$d of %2$d records', 'roxytable' ),
					count( $rows ),
					(int) $total_rows
				);
				?>
			</div>
			<div class="rt-pagination" style="display:flex;gap:8px;align-items:center;">
				<?php if ( $current_page > 1 ) : ?>
					<a class="roxytable-btn roxytable-btn-secondary" href="<?php echo esc_url( $preview_url . ( $current_page - 1 ) ); ?>">&lt; <?php esc_html_e( 'Prev', 'roxytable' ); ?></a>
				<?php else : ?>
					<button class="roxytable-btn roxytable-btn-secondary" disabled>&lt; <?php esc_html_e( 'Prev', 'roxytable' ); ?></button>
				<?php endif; ?>

				<?php for ( $page = 1; $page <= $total_pages; $page++ ) : ?>
					<?php if ( $page === $current_page ) : ?>
						<button class="roxytable-btn roxytable-btn-secondary" aria-current="page"><?php echo esc_html( (string) $page ); ?></button>
					<?php else : ?>
						<a class="roxytable-btn roxytable-btn-secondary" href="<?php echo esc_url( $preview_url . $page ); ?>"><?php echo esc_html( (string) $page ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>

				<?php if ( $current_page < $total_pages ) : ?>
					<a class="roxytable-btn roxytable-btn-secondary" href="<?php echo esc_url( $preview_url . ( $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'roxytable' ); ?> &gt;</a>
				<?php else : ?>
					<button class="roxytable-btn roxytable-btn-secondary" disabled><?php esc_html_e( 'Next', 'roxytable' ); ?> &gt;</button>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
