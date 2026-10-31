<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-create-container">
	<div class="roxytable-card rt-dash-card rt-dash-recent" style="padding: 0; overflow: hidden;">
		<div class="rt-dash-recent-head">
			<div class="rt-dash-recent-heading">
				<span class="rt-dash-recent-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
				</span>
				<div>
					<h2><?php _e( 'Create New Table', 'roxytable' ); ?></h2>
					<p><?php _e( 'Start with a blank table, add columns and data manually', 'roxytable' ); ?></p>
				</div>
			</div>
			<div class="rt-dash-recent-tools">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="rt-dash-btn rt-dash-btn-outline"><?php _e( 'Import Excel File', 'roxytable' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="rt-dash-btn rt-dash-btn-outline"><?php _e( 'All Tables', 'roxytable' ); ?></a>
			</div>
		</div>

		<form method="post" style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
			<div>
				<label class="rt-label" for="rt-create-table-name"><?php _e( 'Table Name', 'roxytable' ); ?></label>
				<input id="rt-create-table-name" name="rt_table_name" type="text" class="rt-field" placeholder="<?php esc_attr_e( 'e.g. Employee Directory', 'roxytable' ); ?>" required />
			</div>
			<div>
				<label class="rt-label" for="rt-create-table-desc"><?php _e( 'Description', 'roxytable' ); ?></label>
				<textarea id="rt-create-table-desc" name="rt_table_description" class="rt-field" placeholder="<?php esc_attr_e( 'e.g. Contact directory for all internal department staff.', 'roxytable' ); ?>" rows="4"></textarea>
			</div>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
				<div>
					<label class="rt-label" for="rt-create-cols"><?php _e( 'Initial Columns Count', 'roxytable' ); ?></label>
					<input id="rt-create-cols" name="rt_initial_cols" type="number" class="rt-field" min="1" max="50" value="5" />
				</div>
				<div>
					<label class="rt-label" for="rt-create-rows"><?php _e( 'Initial Rows Count', 'roxytable' ); ?></label>
					<input id="rt-create-rows" name="rt_initial_rows" type="number" class="rt-field" min="1" max="100" value="10" />
				</div>
			</div>
			<div style="padding-top: 8px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="rt-dash-btn rt-dash-btn-outline"><?php _e( 'Cancel', 'roxytable' ); ?></a>
				<button type="submit" class="rt-dash-btn rt-dash-btn-primary">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
					<?php _e( 'Create Table', 'roxytable' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>
