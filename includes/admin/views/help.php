<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-help-container" style="max-width: 800px; margin: 0 auto;">
	<div style="margin-bottom: 24px;">
		<h2 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'Documentation & Help', 'roxytable' ); ?></h2>
		<p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.875rem;"><?php _e( 'Guides, FAQ, and system information to help you manage your tables.', 'roxytable' ); ?></p>
	</div>

	<div class="roxytable-card">
		<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 16px 0; color: var(--text-main);"><?php _e( 'Shortcode Usage', 'roxytable' ); ?></h3>
		<p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">
			<?php _e( 'To display tables on your pages or posts, use one of the following shortcodes:', 'roxytable' ); ?>
		</p>
		<div style="background-color: var(--bg-main); padding: 12px 16px; border-radius: var(--radius-lg); font-family: monospace; font-size: 0.875rem; margin-top: 12px; border: 1px solid var(--border-color); color: var(--text-main);">
			[roxytable id="5"]<br>
			[roxytable slug="employee-directory"]
		</div>
	</div>

	<div class="roxytable-card">
		<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 16px 0; color: var(--text-main);"><?php _e( 'Frequently Asked Questions', 'roxytable' ); ?></h3>
		<div style="display: flex; flex-direction: column; gap: 16px;">
			<div>
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 4px 0; color: var(--text-main);"><?php _e( 'What Excel formats are supported?', 'roxytable' ); ?></h4>
				<p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'You can upload .xlsx, .xls and standard CSV files. Larger sheets containing thousands of rows are fully supported.', 'roxytable' ); ?></p>
			</div>
			<div>
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 4px 0; color: var(--text-main);"><?php _e( 'Can I edit the table data after importing?', 'roxytable' ); ?></h4>
				<p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'Yes! Under All Tables, you can select any table and edit individual rows, add columns or reorder them.', 'roxytable' ); ?></p>
			</div>
		</div>
	</div>
</div>
