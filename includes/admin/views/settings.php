<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-settings-container" style="max-width: 800px; margin: 0 auto;">
	<div style="margin-bottom: 24px;">
		<h2 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'Settings', 'roxytable' ); ?></h2>
		<p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.875rem;"><?php _e( 'Configure global settings for RoxyTable frontend tables and data handling.', 'roxytable' ); ?></p>
	</div>

	<div class="roxytable-card">
		<form method="post" style="display: flex; flex-direction: column; gap: 24px;">
			<!-- Setting Section 1 -->
			<div>
				<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); color: var(--text-main);"><?php _e( 'General Settings', 'roxytable' ); ?></h3>
				<div style="display: flex; flex-direction: column; gap: 16px;">
					<div>
						<label style="display: block; font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 6px;"><?php _e( 'Default Rows Per Page', 'roxytable' ); ?></label>
						<input type="number" min="5" max="100" value="10" style="width: 120px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); font-size: 0.875rem; outline: none;" />
					</div>
					<div>
						<label style="display: block; font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 6px;"><?php _e( 'CSV Delimiter Character', 'roxytable' ); ?></label>
						<input type="text" maxlength="1" value="," style="width: 80px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); font-size: 0.875rem; text-align: center; outline: none;" />
					</div>
				</div>
			</div>

			<!-- Setting Section 2 -->
			<div>
				<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); color: var(--text-main);"><?php _e( 'Visual Settings', 'roxytable' ); ?></h3>
				<div style="display: flex; flex-direction: column; gap: 16px;">
					<div style="display: flex; align-items: center; gap: 12px;">
						<input type="checkbox" id="enable_zebra" checked style="width: 16px; height: 16px;" />
						<label for="enable_zebra" style="font-size: 0.875rem; font-weight: 500; color: var(--text-main);"><?php _e( 'Enable Row Zebra Striping', 'roxytable' ); ?></label>
					</div>
					<div style="display: flex; align-items: center; gap: 12px;">
						<input type="checkbox" id="enable_borders" checked style="width: 16px; height: 16px;" />
						<label for="enable_borders" style="font-size: 0.875rem; font-weight: 500; color: var(--text-main);"><?php _e( 'Show Vertical Gridlines', 'roxytable' ); ?></label>
					</div>
				</div>
			</div>

			<div>
				<button type="submit" class="roxytable-btn roxytable-btn-primary"><?php _e( 'Save Settings', 'roxytable' ); ?></button>
			</div>
		</form>
	</div>
</div>
