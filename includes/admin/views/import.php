<?php
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure local vars are initialized (set by class-admin-menu.php)
$step         = isset( $step ) ? $step : 1;
$upload_error = isset( $upload_error ) ? $upload_error : null;
$worksheets   = isset( $worksheets ) ? $worksheets : [];
$preview      = isset( $preview ) ? $preview : [];
$sheet_idx    = isset( $sheet_idx ) ? $sheet_idx : null;
$stored       = \RoxyTable\Includes\Admin\Import_Handler::get_stored_data();
?>
<div class="roxytable-import-container" style="max-width: 1000px; margin: 0 auto;">
	
	<!-- Header -->
	<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
		<div>
			<h2 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'Import Excel or CSV', 'roxytable' ); ?></h2>
			<p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.875rem;"><?php _e( 'Upload and parse your table spreadsheets.', 'roxytable' ); ?></p>
		</div>
		<?php if ( $stored ) : ?>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=roxytable-import&roxytable_import_clear=1' ), 'roxytable_clear_import' ) ); ?>" class="roxytable-btn roxytable-btn-secondary" style="color: #dc2626; border-color: #fca5a5;">
				<?php _e( 'Cancel & Clear File', 'roxytable' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<!-- Step Progress Indicator -->
	<div class="roxytable-steps-indicator" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; background: #ffffff; padding: 16px 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
		<div class="roxytable-step <?php echo ( 1 === $step ) ? 'active' : ''; ?> <?php echo ( $step > 1 ) ? 'completed' : ''; ?>" style="display: flex; align-items: center; gap: 8px;">
			<span class="step-num" style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600; border: 2px solid <?php echo ( 1 === $step ) ? 'var(--primary)' : ( ( $step > 1 ) ? '#10b981' : 'var(--text-muted)' ); ?>; color: <?php echo ( 1 === $step ) ? 'var(--primary)' : ( ( $step > 1 ) ? '#ffffff' : 'var(--text-muted)' ); ?>; background-color: <?php echo ( $step > 1 ) ? '#10b981' : 'transparent'; ?>;">
				<?php echo ( $step > 1 ) ? '✓' : '1'; ?>
			</span>
			<span class="step-label" style="font-size: 0.875rem; font-weight: 600; color: <?php echo ( 1 === $step ) ? 'var(--text-main)' : ( ( $step > 1 ) ? '#10b981' : 'var(--text-muted)' ); ?>;"><?php _e( 'Upload File', 'roxytable' ); ?></span>
		</div>
		<div style="flex-grow: 1; height: 2px; background-color: <?php echo ( $step > 1 ) ? '#10b981' : 'var(--border-color)'; ?>; margin: 0 16px;"></div>
		<div class="roxytable-step <?php echo ( 2 === $step ) ? 'active' : ''; ?> <?php echo ( $step > 2 ) ? 'completed' : ''; ?>" style="display: flex; align-items: center; gap: 8px;">
			<span class="step-num" style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600; border: 2px solid <?php echo ( 2 === $step ) ? 'var(--primary)' : ( ( $step > 2 ) ? '#10b981' : 'var(--text-muted)' ); ?>; color: <?php echo ( 2 === $step ) ? 'var(--primary)' : ( ( $step > 2 ) ? '#ffffff' : 'var(--text-muted)' ); ?>; background-color: <?php echo ( $step > 2 ) ? '#10b981' : 'transparent'; ?>;">
				<?php echo ( $step > 2 ) ? '✓' : '2'; ?>
			</span>
			<span class="step-label" style="font-size: 0.875rem; font-weight: 600; color: <?php echo ( 2 === $step ) ? 'var(--text-main)' : ( ( $step > 2 ) ? '#10b981' : 'var(--text-muted)' ); ?>;"><?php _e( 'Select Worksheet', 'roxytable' ); ?></span>
		</div>
		<div style="flex-grow: 1; height: 2px; background-color: <?php echo ( $step > 2 ) ? '#10b981' : 'var(--border-color)'; ?>; margin: 0 16px;"></div>
		<div class="roxytable-step <?php echo ( 3 === $step ) ? 'active' : ''; ?>" style="display: flex; align-items: center; gap: 8px;">
			<span class="step-num" style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600; border: 2px solid <?php echo ( 3 === $step ) ? 'var(--primary)' : 'var(--text-muted)' ; ?>; color: <?php echo ( 3 === $step ) ? 'var(--primary)' : 'var(--text-muted)'; ?>;">3</span>
			<span class="step-label" style="font-size: 0.875rem; font-weight: 600; color: <?php echo ( 3 === $step ) ? 'var(--text-main)' : 'var(--text-muted)'; ?>;"><?php _e( 'Preview Data', 'roxytable' ); ?></span>
		</div>
	</div>

	<!-- Error notice -->
	<?php if ( $upload_error ) : ?>
		<div class="roxytable-alert-danger" style="background-color: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 16px; border-radius: var(--radius-lg); margin-bottom: 24px; font-size: 0.875rem; font-weight: 500;">
			<div style="display: flex; align-items: center; gap: 8px;">
				<svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; color: #ef4444; flex-shrink: 0;" viewBox="0 0 20 20" fill="currentColor">
					<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
				</svg>
				<span><?php echo esc_html( $upload_error ); ?></span>
			</div>
		</div>
	<?php endif; ?>

	<!-- Step 1: Upload File -->
	<?php if ( 1 === $step ) : ?>
		<div class="roxytable-card" style="padding: 0;">
			<form id="roxytable-upload-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>">
				<?php wp_nonce_field( 'roxytable_file_upload', 'roxytable_upload_nonce' ); ?>
				<input type="hidden" name="roxytable_upload_action" value="upload" />
				
				<div class="roxytable-drag-drop-zone" id="roxytable-dropzone" style="padding: 60px 40px; border: 2px dashed var(--border-color); background-color: rgba(248, 250, 252, 0.5); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 280px; cursor: pointer; text-align: center; border-radius: var(--radius-xl); transition: all 0.2s ease;">
					<input type="file" name="roxytable_file" id="roxytable-file-input" accept=".xlsx,.xls,.csv" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" />
					
					<div style="width: 64px; height: 64px; background-color: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
						<svg xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
						</svg>
					</div>
					<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0; color: var(--text-main);"><?php _e( 'Drag and drop Excel or CSV file here', 'roxytable' ); ?></h3>
					<p style="color: var(--text-muted); font-size: 0.875rem; margin: 6px 0 20px 0;"><?php _e( 'Or click to select file from your computer (.xlsx, .xls, .csv)', 'roxytable' ); ?></p>
					
					<div id="roxytable-file-info" style="display: none; margin-bottom: 20px; background-color: var(--primary-light); color: var(--primary); font-weight: 600; font-size: 0.875rem; padding: 8px 16px; border-radius: var(--radius-lg);"></div>
					
					<label for="roxytable-file-input" id="roxytable-browse-btn" class="roxytable-btn roxytable-btn-secondary" tabindex="0" role="button"><?php _e( 'Browse Files', 'roxytable' ); ?></label>
				</div>
				
				<div style="padding: 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
					<button type="submit" id="roxytable-upload-submit" class="roxytable-btn roxytable-btn-primary" style="opacity: 1;" ><?php _e( 'Continue to Worksheets', 'roxytable' ); ?></button>
				</div>
			</form>
		</div>
	<?php endif; ?>

	<!-- Step 2: Select Worksheet -->
	<?php if ( 2 === $step ) : ?>
		<div style="margin-bottom: 24px;">
			<p style="margin: 0; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">
				<?php echo sprintf( __( 'Uploaded file: <strong>%s</strong>', 'roxytable' ), esc_html( $stored['file_name'] ) ); ?>
			</p>
		</div>
		
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
			<?php foreach ( $worksheets as $sheet ) : 
				$picker_url = wp_nonce_url(
					admin_url( 'admin.php?page=roxytable-import&step=3&sheet_index=' . intval( $sheet['index'] ) ),
					'roxytable_pick_worksheet'
				);
				?>
				<div class="roxytable-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; border-color: var(--border-color); transition: all 0.2s ease;">
					<div>
						<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
							<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0; color: var(--text-main);"><?php echo esc_html( $sheet['name'] ); ?></h3>
							<span style="font-size: 0.75rem; font-weight: 600; background-color: var(--primary-light); color: var(--primary); padding: 2px 8px; border-radius: 9999px;">
								Index #<?php echo $sheet['index']; ?>
							</span>
						</div>
						<div style="display: flex; gap: 24px; margin-bottom: 20px;">
							<div>
								<div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase;"><?php _e( 'Rows', 'roxytable' ); ?></div>
								<div style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-top: 2px;"><?php echo number_format( $sheet['rows'] ); ?></div>
							</div>
							<div>
								<div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase;"><?php _e( 'Columns', 'roxytable' ); ?></div>
								<div style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-top: 2px;"><?php echo number_format( $sheet['cols'] ); ?></div>
							</div>
						</div>
					</div>
					<a href="<?php echo esc_url( $picker_url ); ?>" class="roxytable-btn roxytable-btn-primary" style="width: 100%;">
						<?php _e( 'Select Worksheet & Preview', 'roxytable' ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- Step 3: Preview Data -->
	<?php if ( 3 === $step && ! empty( $preview ) ) : 
		// Scan for missing column header names
		$missing_headers = [];
		foreach ( $preview['headers'] as $i => $hdr ) {
			if ( empty( trim( $hdr ) ) ) {
				$missing_headers[] = Coordinate::stringFromColumnIndex( $i + 1 );
			}
		}
		?>
		
		<div style="margin-bottom: 24px;">
			<p style="margin: 0; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">
				<?php echo sprintf( __( 'Worksheet: <strong>%s</strong> (Total rows: %s, Columns: %s)', 'roxytable' ), esc_html( $preview['sheet_name'] ), number_format( $preview['total_rows'] ), number_format( $preview['total_cols'] ) ); ?>
			</p>
		</div>

		<?php if ( ! empty( $missing_headers ) ) : ?>
			<div class="roxytable-alert-warning" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; color: #92400e; padding: 16px; border-radius: var(--radius-lg); margin-bottom: 24px; font-size: 0.875rem; font-weight: 500;">
				<div style="display: flex; align-items: flex-start; gap: 8px;">
					<svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; color: #f59e0b; flex-shrink: 0; margin-top: 2px;" viewBox="0 0 20 20" fill="currentColor">
						<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625l6.28-10.875zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
					</svg>
					<div>
						<strong><?php _e( 'Warning: Missing Column Headers', 'roxytable' ); ?></strong>
						<p style="margin: 4px 0 0 0; font-size: 0.825rem;"><?php echo sprintf( __( 'Columns without headers were detected (Column letters: %s). WordPress will assign default names for these.', 'roxytable' ), implode( ', ', $missing_headers ) ); ?></p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="roxytable-card" style="padding: 0;">
			<!-- Preview toolbar -->
			<div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background-color: rgba(248, 250, 252, 0.5); border-top-left-radius: var(--radius-xl); border-top-right-radius: var(--radius-xl); flex-wrap: wrap; gap: 16px;">
				<h4 style="font-size: 1rem; font-weight: 600; margin: 0; color: var(--text-main);"><?php _e( 'Previewing First 10 Rows', 'roxytable' ); ?></h4>
				
				<div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
					<!-- Search Box -->
					<div style="position: relative; width: 220px;">
						<span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); display: flex; align-items: center; width: 14px; height: 14px;">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" />
							</svg>
						</span>
						<input type="text" id="roxytable-preview-search" placeholder="Search preview..." style="width: 100%; padding: 6px 12px 6px 30px; font-size: 0.8125rem; border: 1px solid var(--border-color); border-radius: var(--radius-lg); outline: none; background-color: #ffffff;" />
					</div>
					
					<!-- Zoom buttons -->
					<div style="display: flex; border: 1px solid var(--border-color); border-radius: var(--radius-lg); background-color: #ffffff; overflow: hidden;">
						<button type="button" id="roxytable-zoom-out" style="border: none; padding: 6px 12px; font-size: 0.8125rem; background: transparent; cursor: pointer; border-right: 1px solid var(--border-color); font-weight: 600; color: var(--text-muted); hover:background-color:#f1f5f9;">A-</button>
						<button type="button" id="roxytable-zoom-reset" style="border: none; padding: 6px 12px; font-size: 0.8125rem; background: transparent; cursor: pointer; border-right: 1px solid var(--border-color); font-weight: 600; color: var(--text-muted);">Reset</button>
						<button type="button" id="roxytable-zoom-in" style="border: none; padding: 6px 12px; font-size: 0.8125rem; background: transparent; cursor: pointer; font-weight: 600; color: var(--text-muted);">A+</button>
					</div>
				</div>
			</div>

			<!-- Preview Grid -->
			<div class="roxytable-preview-wrapper" style="overflow-x: auto; max-width: 100%;">
				<table class="roxytable-preview-table roxytable-zoom-md" id="roxytable-preview-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
					<thead>
						<tr style="background-color: #f8fafc; border-bottom: 2px solid var(--border-color);">
							<?php foreach ( $preview['headers'] as $index => $hdr ) : 
								$label = ! empty( trim( $hdr ) ) ? $hdr : sprintf( __( 'Column %s', 'roxytable' ), Coordinate::stringFromColumnIndex( $index + 1 ) );
								$display_name = esc_html( $label );
								$is_warning = empty( trim( $hdr ) ) ? 'style="color:#d97706; background-color:#fffbeb;"' : '';
								?>
								<th <?php echo $is_warning; ?> style="padding: 12px 16px; font-weight: 600; border-right: 1px solid var(--border-color);">
									<?php echo $display_name; ?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $preview['rows'] ) ) : ?>
							<?php foreach ( $preview['rows'] as $r_index => $row_cells ) : ?>
								<tr style="border-bottom: 1px solid var(--border-color);">
									<?php foreach ( $row_cells as $c_index => $cell ) :
										$cell_label = ! empty( trim( $preview['headers'][ $c_index ] ?? '' ) ) ? $preview['headers'][ $c_index ] : sprintf( __( 'Column %s', 'roxytable' ), Coordinate::stringFromColumnIndex( $c_index + 1 ) );
									?>
										<td data-label="<?php echo esc_attr( $cell_label ); ?>" style="padding: 12px 16px; border-right: 1px solid var(--border-color);">
											<?php echo esc_html( $cell ); ?>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="<?php echo count( $preview['headers'] ); ?>" style="text-align: center; padding: 24px; color: var(--text-muted);">
									<?php _e( 'No rows found in this sheet.', 'roxytable' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Configuration Form -->
		<div class="roxytable-card rt-config-form-card" style="margin-top: 24px; padding: 0;">
			<form id="rt-confirm-import-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import' ) ); ?>">
				<?php wp_nonce_field( 'roxytable_confirm_import', 'roxytable_confirm_nonce' ); ?>
				<input type="hidden" name="roxytable_import_action" value="confirm_import" />
				<input type="hidden" name="rt_sheet_index" value="<?php echo intval( $sheet_idx ); ?>" />

				<!-- Form header -->
				<div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); background-color: rgba(248,250,252,0.5); border-radius: var(--radius-xl) var(--radius-xl) 0 0;">
					<h4 style="font-size: 1rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'Configure Import', 'roxytable' ); ?></h4>
					<p style="font-size: 0.8125rem; color: var(--text-muted); margin: 4px 0 0;"><?php _e( 'Set up your table before saving it.', 'roxytable' ); ?></p>
				</div>

				<div style="padding: 24px; display: flex; flex-direction: column; gap: 28px;">

					<!-- ── Section 1: Table Identity ── -->
					<div>
						<h5 class="rt-section-heading"><?php _e( '1. Table Identity', 'roxytable' ); ?></h5>
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">

							<div class="rt-field-group" style="grid-column: 1 / -1;">
								<label class="rt-label" for="rt-table-name"><?php _e( 'Table Name', 'roxytable' ); ?> <span style="color:#ef4444;">*</span></label>
								<input type="text" id="rt-table-name" name="rt_table_name" class="rt-field"
									placeholder="<?php esc_attr_e( 'e.g. Employee Directory', 'roxytable' ); ?>"
									value="<?php echo esc_attr( $preview['sheet_name'] ?? '' ); ?>"
									required maxlength="255" />
							</div>

							<div class="rt-field-group">
								<label class="rt-label" for="rt-table-slug"><?php _e( 'Slug', 'roxytable' ); ?></label>
								<input type="text" id="rt-table-slug" name="rt_table_slug" class="rt-field"
									placeholder="<?php esc_attr_e( 'auto-generated', 'roxytable' ); ?>"
									maxlength="255"
									pattern="[a-z0-9\-]+"
									title="<?php esc_attr_e( 'Lowercase letters, numbers, hyphens only.', 'roxytable' ); ?>" />
								<p class="rt-hint"><?php _e( 'URL-safe identifier. Auto-generated from name. Must be unique.', 'roxytable' ); ?></p>
							</div>

							<div class="rt-field-group">
								<label class="rt-label" for="rt-table-status"><?php _e( 'Status', 'roxytable' ); ?></label>
								<select id="rt-table-status" name="rt_table_status" class="rt-field">
									<option value="published"><?php _e( 'Published', 'roxytable' ); ?></option>
									<option value="draft"><?php _e( 'Draft', 'roxytable' ); ?></option>
								</select>
							</div>

							<div class="rt-field-group" style="grid-column: 1 / -1;">
								<label class="rt-label" for="rt-table-desc"><?php _e( 'Description', 'roxytable' ); ?></label>
								<textarea id="rt-table-desc" name="rt_table_description" class="rt-field" rows="2"
									maxlength="1000"
									placeholder="<?php esc_attr_e( 'Optional. What does this table contain?', 'roxytable' ); ?>"></textarea>
							</div>

						</div>
					</div>

					<!-- ── Section 2: Parse Options ── -->
					<div>
						<h5 class="rt-section-heading"><?php _e( '2. Parse Options', 'roxytable' ); ?></h5>
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
							<?php
							$parse_opts = [
								'rt_first_row_headers' => [ 'label' => __( 'First row contains headers', 'roxytable' ), 'default' => true ],
								'rt_auto_detect_types' => [ 'label' => __( 'Auto-detect column types', 'roxytable' ),   'default' => true ],
								'rt_remove_empty_rows' => [ 'label' => __( 'Remove empty rows', 'roxytable' ),           'default' => true ],
								'rt_remove_empty_cols' => [ 'label' => __( 'Remove empty columns', 'roxytable' ),        'default' => true ],
							];
							foreach ( $parse_opts as $field_name => $opt ) : ?>
								<label class="rt-checkbox-label">
									<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="1" <?php checked( $opt['default'], true ); ?> />
									<span><?php echo esc_html( $opt['label'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- ── Section 3: Frontend Features ── -->
					<div>
						<h5 class="rt-section-heading"><?php _e( '3. Frontend Features', 'roxytable' ); ?></h5>
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
							<?php
							$feature_toggles = [
								'rt_feat_search'     => [ 'label' => __( 'Enable search', 'roxytable' ),           'default' => true ],
								'rt_feat_sort'       => [ 'label' => __( 'Enable sort', 'roxytable' ),             'default' => true ],
								'rt_feat_pagination' => [ 'label' => __( 'Enable pagination', 'roxytable' ),       'default' => true ],
								'rt_feat_visibility' => [ 'label' => __( 'Column visibility toggle', 'roxytable' ),'default' => true ],
								'rt_feat_export'     => [ 'label' => __( 'Enable export (CSV)', 'roxytable' ),    'default' => false ],
							];
							foreach ( $feature_toggles as $field_name => $feat ) : ?>
								<label class="rt-toggle-label">
									<span class="rt-toggle-switch">
										<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="1" <?php checked( $feat['default'], true ); ?> />
										<span class="rt-toggle-slider"></span>
									</span>
									<span class="rt-toggle-text"><?php echo esc_html( $feat['label'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<div class="rt-field-group" style="max-width: 200px;">
							<label class="rt-label" for="rt-rows-per-page"><?php _e( 'Rows per page', 'roxytable' ); ?></label>
							<input type="number" id="rt-rows-per-page" name="rt_rows_per_page" class="rt-field"
								min="5" max="500" value="25" />
						</div>
					</div>

					<!-- ── Section 4: Advanced (collapsible) ── -->
					<details id="rt-advanced-section" class="rt-advanced-details">
						<summary class="rt-advanced-summary">
							<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
								<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
							</svg>
							<?php _e( 'Advanced Settings', 'roxytable' ); ?>
							<svg class="rt-chevron" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;margin-left:auto;flex-shrink:0;transition:transform 0.2s;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
							</svg>
						</summary>
						<div class="rt-advanced-body">
							<div class="rt-field-group">
								<label class="rt-label" for="rt-custom-css"><?php _e( 'Custom CSS', 'roxytable' ); ?></label>
								<textarea id="rt-custom-css" name="rt_custom_css" class="rt-field rt-code-field" rows="4"
									placeholder="<?php esc_attr_e( '/* Your custom CSS here */\n.roxytable-table th { background: #1e293b; }', 'roxytable' ); ?>"></textarea>
								<p class="rt-hint"><?php _e( 'CSS scoped to this table only. Will be injected on pages containing the shortcode.', 'roxytable' ); ?></p>
							</div>
							<label class="rt-checkbox-label" style="margin-top: 8px;">
								<input type="checkbox" name="rt_server_side" value="1" />
								<span><?php _e( 'Enable server-side processing (recommended for tables over 10,000 rows)', 'roxytable' ); ?></span>
							</label>
						</div>
					</details>

				</div><!-- /padding wrapper -->

				<!-- Form footer actions -->
				<div style="padding: 20px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 var(--radius-xl) var(--radius-xl); background-color: rgba(248,250,252,0.5);">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-import&step=2' ) ); ?>" class="roxytable-btn roxytable-btn-secondary">
						&larr; <?php _e( 'Back to Worksheets', 'roxytable' ); ?>
					</a>
					<button type="submit" id="rt-submit-import" class="roxytable-btn roxytable-btn-primary" style="background-color: #10b981; min-width: 180px;">
						<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;margin-right:6px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
						</svg>
						<?php _e( 'Save Table', 'roxytable' ); ?>
					</button>
				</div>

			</form>
		</div>
	<?php endif; ?>
</div>

