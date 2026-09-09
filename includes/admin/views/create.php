<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-create-container" style="max-width: 800px; margin: 0 auto;">
	<div style="margin-bottom: 24px;">
		<h2 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'Create New Table', 'roxytable' ); ?></h2>
		<p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.875rem;"><?php _e( 'Start with a blank table, add columns and data manually.', 'roxytable' ); ?></p>
	</div>

	<div class="roxytable-card">
		<div class="roxytable-card-header">
			<h4 class="roxytable-card-title"><?php _e( 'Table Configuration', 'roxytable' ); ?></h4>
		</div>
		<form method="post" style="display: flex; flex-direction: column; gap: 20px;">
			<div>
				<label style="display: block; font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 6px;"><?php _e( 'Table Name', 'roxytable' ); ?></label>
				<input type="text" placeholder="e.g. Employee Directory" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); font-size: 0.875rem; outline: none; transition: border-color 0.2s;" />
			</div>
			<div>
				<label style="display: block; font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 6px;"><?php _e( 'Description', 'roxytable' ); ?></label>
				<textarea placeholder="e.g. Contact directory for all internal department staff." rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); font-size: 0.875rem; outline: none; resize: vertical;"></textarea>
			</div>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1-fraction)); gap: 16px;">
				<div>
					<label style="display: block; font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 6px;"><?php _e( 'Initial Columns Count', 'roxytable' ); ?></label>
					<input type="number" min="1" max="50" value="5" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); font-size: 0.875rem; outline: none;" />
				</div>
				<div>
					<label style="display: block; font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 6px;"><?php _e( 'Initial Rows Count', 'roxytable' ); ?></label>
					<input type="number" min="1" max="100" value="10" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-lg); font-size: 0.875rem; outline: none;" />
				</div>
			</div>
			<div style="margin-top: 8px;">
				<button type="submit" class="roxytable-btn roxytable-btn-primary"><?php _e( 'Create Table', 'roxytable' ); ?></button>
			</div>
		</form>
	</div>
</div>
