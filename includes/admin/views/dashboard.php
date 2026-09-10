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

			<div class="roxytable-card rt-recent-tables-card" style="padding:0;overflow:hidden;">
				<div class="rt-recent-tables-header">
					<div>
						<h3 class="roxytable-card-title" style="margin:0;font-size:1.15rem;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;">
							<span>📊</span> <?php _e( 'Recent tables', 'roxytable' ); ?>
						</h3>
						<p class="roxytable-card-description" style="margin:4px 0 0 0;font-size:0.84rem;color:var(--text-muted);"><?php _e( 'Your most recently updated data sets', 'roxytable' ); ?></p>
					</div>
					<div style="display:flex;gap:8px;align-items:center;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-create' ) ); ?>" class="roxytable-btn roxytable-btn-secondary" style="font-weight:600;"><?php _e( '+ Create Table', 'roxytable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="roxytable-btn roxytable-btn-primary" style="font-weight:600;"><?php _e( 'Import Excel File', 'roxytable' ); ?></a>
					</div>
				</div>

				<div style="overflow-x:auto;">
					<table class="rt-recent-tables-table">
						<thead>
							<tr>
								<th><?php _e( 'Table', 'roxytable' ); ?></th>
								<th><?php _e( 'Records', 'roxytable' ); ?></th>
								<th><?php _e( 'Columns', 'roxytable' ); ?></th>
								<th><?php _e( 'Status', 'roxytable' ); ?></th>
								<th><?php _e( 'Updated', 'roxytable' ); ?></th>
								<th style="text-align:right;"><?php _e( 'Actions', 'roxytable' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $recent ) ) : ?>
								<tr><td colspan="6" style="padding:28px;text-align:center;color:var(--text-muted);font-style:italic;"><?php _e( 'No tables yet. Import one to get started.', 'roxytable' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $recent as $table ) : ?>
									<tr>
										<td>
											<div style="display:flex;align-items:center;gap:12px;">
												<div class="rt-table-thumb-wrap rt-table-image-picker" data-table-id="<?php echo intval( $table['id'] ); ?>" title="<?php esc_attr_e( 'Click to set or change table image', 'roxytable' ); ?>">
													<?php if ( ! empty( $table['image'] ) ) : ?>
														<img class="rt-table-thumb-img" src="<?php echo esc_url( $table['image'] ); ?>" alt="<?php echo esc_attr( $table['name'] ); ?>" />
													<?php else : ?>
														<div class="rt-table-thumb-default">
															<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:#10b981;">
																<path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm3-1.5A1.5 1.5 0 004.5 6v12A1.5 1.5 0 006 19.5h12a1.5 1.5 0 0018 4.5H6z" clip-rule="evenodd" />
																<path d="M7.5 7.5h9v1.5h-9V7.5zm0 3.75h9v1.5h-9v-1.5zm0 3.75h6v1.5h-6v-1.5z" />
															</svg>
														</div>
													<?php endif; ?>
													<div class="rt-table-thumb-overlay" aria-hidden="true">📷</div>
												</div>
												<div>
													<div style="font-weight:700;font-size:0.95rem;color:#0f172a;"><?php echo esc_html( $table['name'] ); ?></div>
													<div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;display:flex;align-items:center;gap:6px;">
														<span style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-family:monospace;font-weight:600;color:#475569;">ID <?php echo intval( $table['id'] ); ?></span>
														<span>/<?php echo esc_html( $table['slug'] ); ?>/</span>
													</div>
												</div>
											</div>
										</td>
										<td style="font-weight:600;color:#0f172a;"><?php echo number_format_i18n( intval( $table['total_rows'] ) ); ?></td>
										<td style="font-weight:600;color:#0f172a;"><?php echo number_format_i18n( intval( $table['total_columns'] ) ); ?></td>
										<td>
											<span class="<?php echo 'published' === strtolower( $table['status'] ) ? 'rt-badge-status-published' : 'rt-badge-status-draft'; ?>">
												<?php echo esc_html( ucfirst( $table['status'] ) ); ?>
											</span>
										</td>
										<td style="color:#64748b;font-size:0.875rem;"><?php echo esc_html( human_time_diff( strtotime( $table['updated_at'] ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
										<td style="text-align:right;">
											<div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
												<a class="rt-btn-action-icon" href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . intval( $table['id'] ) ) ); ?>"><?php _e( 'Edit', 'roxytable' ); ?></a>
												<a class="rt-btn-action-icon" href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables&action=preview&id=' . intval( $table['id'] ) ) ); ?>"><?php _e( 'Preview', 'roxytable' ); ?></a>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
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

			<?php
			$static_avatar_url = ROXYTABLE_URL . 'assets/images/abdulpassport.jpeg';
			$fallback_img      = ROXYTABLE_URL . 'assets/images/developer-avatar.png';
			$dev_name          = 'Abdulrahman Sulaiman';
			$dev_title         = 'WordPress Developer with 4+ years working Experience';
			$dev_location      = 'Live in Nigeria';
			?>
			<div class="roxytable-card rt-dev-card">
				<div class="rt-dev-card-banner"></div>
				<div class="rt-dev-card-body">
					<div class="rt-dev-avatar-wrapper">
						<img id="rt-dev-avatar-img" class="rt-dev-avatar" src="<?php echo esc_url( $static_avatar_url ); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_img ); ?>';" alt="<?php echo esc_attr( $dev_name ); ?>" />
					</div>
					<div class="rt-dev-name-wrap">
						<span class="rt-dev-name"><?php echo esc_html( $dev_name ); ?></span>
						<span class="rt-dev-badge-verified" title="<?php esc_attr_e( 'Verified Plugin Author', 'roxytable' ); ?>">✓</span>
					</div>
					<div class="rt-dev-title"><?php echo esc_html( $dev_title ); ?></div>
					<div class="rt-dev-meta">
						<span class="rt-dev-pill">⚡ WP Specialist</span>
						<span class="rt-dev-pill">📍 <?php echo esc_html( $dev_location ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
