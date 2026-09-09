<?php
/**
 * Import Success Screen
 *
 * Variables injected by class-admin-menu.php::render_import():
 *   $success_data  array  { id, name, description, status, rows, cols, shortcode }
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$success_data = isset( $success_data ) ? $success_data : [];
$table_id     = absint( $success_data['id'] ?? 0 );
$table_name   = esc_html( $success_data['name'] ?? __( 'Imported Table', 'roxytable' ) );
$description  = esc_html( $success_data['description'] ?? '' );
$status       = esc_html( $success_data['status'] ?? 'draft' );
$rows_saved   = number_format( absint( $success_data['rows'] ?? 0 ) );
$cols_saved   = number_format( absint( $success_data['cols'] ?? 0 ) );
$shortcode    = esc_attr( '[roxytable id="' . $table_id . '"]' );

$status_label = 'published' === $status
	? __( 'Published', 'roxytable' )
	: __( 'Draft', 'roxytable' );

$status_color = 'published' === $status ? '#10b981' : '#f59e0b';
$status_bg    = 'published' === $status ? '#ecfdf5'  : '#fffbeb';
?>
<div class="roxytable-success-container" style="max-width: 680px; margin: 0 auto; text-align: center;">

	<!-- Animated success ring -->
	<div class="rt-success-ring" aria-hidden="true">
		<svg viewBox="0 0 52 52" class="rt-checkmark-svg">
			<circle class="rt-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
			<path class="rt-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
		</svg>
	</div>

	<!-- Headline -->
	<h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 24px 0 8px;">
		<?php _e( 'Import Successful!', 'roxytable' ); ?>
	</h2>
	<p style="color: var(--text-muted); font-size: 0.9375rem; margin: 0 0 32px;">
		<?php
		printf(
			/* translators: %s: table name */
			esc_html__( '"%s" has been saved to your library and is ready to embed.', 'roxytable' ),
			$table_name
		);
		?>
	</p>

	<!-- Stats card -->
	<div class="roxytable-card" style="text-align: left; margin-bottom: 24px; padding: 24px;">
		<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
			<div>
				<div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 4px;">
					<?php _e( 'Table Name', 'roxytable' ); ?>
				</div>
				<div style="font-size: 1.125rem; font-weight: 700; color: var(--text-main);"><?php echo $table_name; ?></div>
				<?php if ( $description ) : ?>
					<div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 4px;"><?php echo $description; ?></div>
				<?php endif; ?>
			</div>
			<span style="display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 9999px; font-size: 0.8125rem; font-weight: 600; background-color: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>;">
				<?php echo $status_label; ?>
			</span>
		</div>

		<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; border-top: 1px solid var(--border-color); padding-top: 16px;">
			<div style="text-align: center;">
				<div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;"><?php _e( 'Rows Saved', 'roxytable' ); ?></div>
				<div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?php echo $rows_saved; ?></div>
			</div>
			<div style="text-align: center; border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">
				<div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;"><?php _e( 'Columns', 'roxytable' ); ?></div>
				<div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?php echo $cols_saved; ?></div>
			</div>
			<div style="text-align: center;">
				<div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;"><?php _e( 'Table ID', 'roxytable' ); ?></div>
				<div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?php echo $table_id; ?></div>
			</div>
		</div>
	</div>

	<!-- Shortcode card -->
	<div class="roxytable-card" style="margin-bottom: 24px; padding: 24px;">
		<div style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
			<?php _e( 'Embed Shortcode', 'roxytable' ); ?>
		</div>
		<div style="display: flex; align-items: center; gap: 12px;">
			<div id="rt-shortcode-pill" style="flex-grow: 1; background-color: #f1f5f9; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 12px 16px; font-family: monospace; font-size: 1rem; font-weight: 700; color: var(--primary); text-align: left; user-select: all; letter-spacing: 0.01em;">
				[roxytable id="<?php echo $table_id; ?>"]
			</div>
			<button type="button" id="rt-copy-shortcode"
				class="roxytable-btn roxytable-btn-secondary"
				data-shortcode="<?php echo $shortcode; ?>"
				style="flex-shrink: 0; gap: 6px; display: inline-flex; align-items: center;"
				title="<?php esc_attr_e( 'Copy shortcode to clipboard', 'roxytable' ); ?>">
				<svg id="rt-copy-icon" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
				</svg>
				<span id="rt-copy-label"><?php _e( 'Copy', 'roxytable' ); ?></span>
			</button>
		</div>
		<p style="font-size: 0.8125rem; color: var(--text-muted); margin: 10px 0 0; text-align: left;">
			<?php _e( 'Paste this shortcode into any page or post to display your table.', 'roxytable' ); ?>
		</p>
	</div>

	<!-- Action buttons -->
	<div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="roxytable-btn roxytable-btn-primary">
			<?php _e( '← View All Tables', 'roxytable' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>" class="roxytable-btn roxytable-btn-secondary">
			<?php _e( 'Import Another File', 'roxytable' ); ?>
		</a>
	</div>

</div>
