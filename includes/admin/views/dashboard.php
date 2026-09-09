<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-dashboard-container">
	<?php
	$total_tables = \RoxyTable\Includes\Admin\Table_Repository::count_tables();
	$published = \RoxyTable\Includes\Admin\Table_Repository::count_tables( [ 'filter' => 'published' ] );
	$drafts = \RoxyTable\Includes\Admin\Table_Repository::count_tables( [ 'filter' => 'draft' ] );
	$recent = \RoxyTable\Includes\Admin\Table_Repository::list_tables( [ 'limit' => 5 ] );
	$total_rows = 0;
	foreach ( \RoxyTable\Includes\Admin\Table_Repository::list_tables( [ 'limit' => 100 ] ) as $t ) {
		$total_rows += intval( $t['total_rows'] ?? 0 );
	}
	?>

	<div style="display:flex;gap:24px;align-items:flex-start;">
		<div style="flex:1;">
			<div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;">
				<div class="roxytable-card" style="flex:1;min-width:220px;">
					<div style="font-size:0.875rem;color:var(--text-muted);"><?php _e( 'Total Tables', 'roxytable' ); ?></div>
					<div style="font-size:1.75rem;font-weight:700;margin-top:6px;"><?php echo intval( $total_tables ); ?></div>
					<div style="font-size:0.8125rem;color:var(--text-muted);margin-top:6px;"><?php printf( '+%d this month', 2 ); ?></div>
				</div>
				<div class="roxytable-card" style="flex:1;min-width:220px;">
					<div style="font-size:0.875rem;color:var(--text-muted);"><?php _e( 'Published', 'roxytable' ); ?></div>
					<div style="font-size:1.75rem;font-weight:700;margin-top:6px;"><?php echo intval( $published ); ?></div>
					<div style="font-size:0.8125rem;color:var(--text-muted);margin-top:6px;"><?php _e( 'Live on site', 'roxytable' ); ?></div>
				</div>
				<div class="roxytable-card" style="flex:1;min-width:220px;">
					<div style="font-size:0.875rem;color:var(--text-muted);"><?php _e( 'Drafts', 'roxytable' ); ?></div>
					<div style="font-size:1.75rem;font-weight:700;margin-top:6px;"><?php echo intval( $drafts ); ?></div>
					<div style="font-size:0.8125rem;color:var(--text-muted);margin-top:6px;"><?php _e( 'Not visible yet', 'roxytable' ); ?></div>
				</div>
				<div class="roxytable-card" style="flex:1;min-width:220px;">
					<div style="font-size:0.875rem;color:var(--text-muted);"><?php _e( 'Total Records', 'roxytable' ); ?></div>
					<div style="font-size:1.75rem;font-weight:700;margin-top:6px;"><?php echo number_format_i18n( $total_rows ); ?></div>
					<div style="font-size:0.8125rem;color:var(--text-muted);margin-top:6px;"><?php _e( 'Across all tables', 'roxytable' ); ?></div>
				</div>
			</div>

			<div class="roxytable-card">
				<div class="roxytable-card-header" style="display:flex;align-items:center;justify-content:space-between;">
					<div>
						<h3 class="roxytable-card-title"><?php _e( 'Recent tables', 'roxytable' ); ?></h3>
						<p class="roxytable-card-description"><?php _e( 'Your most recently updated data sets', 'roxytable' ); ?></p>
					</div>
					<div style="display:flex;gap:8px;align-items:center;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-create' ) ); ?>" class="roxytable-btn roxytable-btn-secondary"><?php _e( '+ Create Table', 'roxytable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="roxytable-btn roxytable-btn-primary"><?php _e( 'Import Excel File', 'roxytable' ); ?></a>
					</div>
				</div>

				<table style="width:100%;border-collapse:collapse;margin-top:12px;">
					<thead style="background:#f3f4f6;color:var(--text-muted);text-align:left;">
						<tr>
							<th style="padding:12px 16px;"><?php _e( 'Table', 'roxytable' ); ?></th>
							<th style="padding:12px 16px;"><?php _e( 'Records', 'roxytable' ); ?></th>
							<th style="padding:12px 16px;"><?php _e( 'Columns', 'roxytable' ); ?></th>
							<th style="padding:12px 16px;"><?php _e( 'Status', 'roxytable' ); ?></th>
							<th style="padding:12px 16px;"><?php _e( 'Updated', 'roxytable' ); ?></th>
							<th style="padding:12px 16px; text-align:right;"><?php _e( 'Actions', 'roxytable' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $recent ) ) : ?>
							<tr><td colspan="6" style="padding:18px;text-align:center;color:var(--text-muted);"><?php _e( 'No tables yet. Import one to get started.', 'roxytable' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $recent as $table ) : ?>
								<tr style="border-top:1px solid var(--border-color);">
									<td style="padding:12px 16px;vertical-align:middle;">
										<div style="font-weight:600;"><?php echo esc_html( $table['name'] ); ?></div>
										<div style="font-size:0.8125rem;color:var(--text-muted);margin-top:6px;"><?php echo esc_html( $table['slug'] ); ?></div>
									</td>
									<td style="padding:12px 16px;vertical-align:middle;"><?php echo intval( $table['total_rows'] ); ?></td>
									<td style="padding:12px 16px;vertical-align:middle;"><?php echo intval( $table['total_columns'] ); ?></td>
									<td style="padding:12px 16px;vertical-align:middle;">
										<span style="display:inline-block;padding:6px 10px;border-radius:999px;background:#ecfdf5;color:#065f46;font-weight:600;font-size:0.8125rem;">
											<?php echo esc_html( ucfirst( $table['status'] ) ); ?></span>
									</td>
									<td style="padding:12px 16px;vertical-align:middle;"><?php echo esc_html( human_time_diff( strtotime( $table['updated_at'] ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
									<td style="padding:12px 16px;vertical-align:middle;text-align:right;">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . intval( $table['id'] ) ) ); ?>"><?php _e( 'Edit', 'roxytable' ); ?></a>
										&nbsp;|
										&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=preview&id=' . intval( $table['id'] ) ) ); ?>"><?php _e( 'Preview', 'roxytable' ); ?></a>
										&nbsp;|
										&nbsp;<span class="rt-shortcode-pill" title="<?php esc_attr_e( 'Shortcode', 'roxytable' ); ?>">
											<span class="rt-shortcode-text"><?php echo esc_html( '[roxytable id="' . intval( $table['id'] ) . '"]' ); ?></span>
											<button type="button" class="rt-copy-shortcode" data-shortcode="<?php echo esc_attr( '[roxytable id="' . intval( $table['id'] ) . '"]' ); ?>" aria-label="Copy shortcode">📋</button>
										</span>
										&nbsp;
										<button type="button" class="roxytable-actions-more" aria-label="More actions">⋯</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div style="width:320px;display:flex;flex-direction:column;gap:18px;">
			<div class="roxytable-card">
				<h4 style="margin:0 0 8px 0;"><?php _e( 'Quick actions', 'roxytable' ); ?></h4>
				<div style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
					<a class="roxytable-btn roxytable-btn-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>"><?php _e( 'Import Excel', 'roxytable' ); ?></a>
					<a class="roxytable-btn roxytable-btn-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-create' ) ); ?>"><?php _e( 'Create New Table', 'roxytable' ); ?></a>
					<a class="roxytable-btn roxytable-btn-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-help' ) ); ?>"><?php _e( 'View Documentation', 'roxytable' ); ?></a>
					<a class="roxytable-btn roxytable-btn-secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php _e( 'View Demo', 'roxytable' ); ?></a>
				</div>
			</div>

			<div class="roxytable-card rt-dev-card">
				<div style="display:flex;gap:12px;align-items:center;">
					<img class="rt-dev-avatar" src="https://kommodo.ai/i/ae0thSRDiaVuSVY1jY0I" alt="Abdulrahman Sulaiman" />
					<div>
						<div style="font-weight:700;font-size:1rem;">Abdulrahman Sulaiman</div>
						<div style="color:var(--text-muted);font-size:0.875rem;margin-top:6px;">WordPress Developer with 4+ years working Experience</div>
						<div style="color:var(--text-muted);font-size:0.875rem;margin-top:8px;">Live in Nigeria</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
