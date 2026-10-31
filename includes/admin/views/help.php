<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-help-container" style="max-width: 960px; margin: 0 auto; padding-bottom: 40px;">
	
	<!-- Header -->
	<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
		<div>
			<h2 style="font-size: 1.65rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'Documentation & User Guide', 'roxytable' ); ?></h2>
			<p style="margin: 6px 0 0 0; color: var(--text-muted); font-size: 0.9rem;"><?php _e( 'Everything you need to know about creating, importing, editing, and publishing tables with RoxyTable.', 'roxytable' ); ?></p>
		</div>
		<a href="mailto:info@webpress.digital" class="roxytable-btn roxytable-btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
			<svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
			</svg>
			<?php _e( 'Get Support', 'roxytable' ); ?>
		</a>
	</div>

	<!-- Need Help Banner / Support Card -->
	<div class="roxytable-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff; border: none; margin-bottom: 24px; padding: 24px 28px;">
		<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
			<div style="max-width: 620px;">
				<div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 10px; border-radius: 9999px; margin-bottom: 12px;">
					<span>●</span> <?php _e( 'Official WebPress Support', 'roxytable' ); ?>
				</div>
				<h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 8px 0; color: #ffffff;"><?php _e( 'Need Assistance with RoxyTable?', 'roxytable' ); ?></h3>
				<p style="font-size: 0.875rem; color: #94a3b8; margin: 0; line-height: 1.6;">
					<?php _e( 'Have questions, need help importing a complex spreadsheet, or experiencing an issue? Our team is available to assist you. Contact us directly via email:', 'roxytable' ); ?>
				</p>
			</div>
			<div>
				<a href="mailto:info@webpress.digital" style="display: inline-flex; align-items: center; gap: 8px; background-color: #10b981; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: var(--radius-lg, 8px); font-weight: 600; font-size: 0.9rem; transition: background-color 0.2s ease;">
					<svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
					</svg>
					info@webpress.digital
				</a>
			</div>
		</div>
	</div>

	<!-- Quick Start Guide (3 Simple Steps) -->
	<div class="roxytable-card" style="margin-bottom: 24px;">
		<h3 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 20px 0; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
			<span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; background: var(--primary-light, #e0e7ff); color: var(--primary, #4f46e5); border-radius: 50%; font-size: 0.85rem; font-weight: 700;">🚀</span>
			<?php _e( 'Quick Start Guide: 3 Easy Steps', 'roxytable' ); ?>
		</h3>

		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">
			<!-- Step 1 -->
			<div style="background-color: var(--bg-main, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px); padding: 20px;">
				<div style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #3b82f6; color: #ffffff; border-radius: 50%; font-weight: 700; font-size: 0.875rem; margin-bottom: 12px;">1</div>
				<h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 8px 0; color: var(--text-main);"><?php _e( 'Prepare & Import File', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
					<?php _e( 'Navigate to <strong>Import Excel</strong>. Drop your <code>.xlsx</code>, <code>.xls</code>, or <code>.csv</code> file. RoxyTable automatically reads all sheets and columns.', 'roxytable' ); ?>
				</p>
			</div>

			<!-- Step 2 -->
			<div style="background-color: var(--bg-main, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px); padding: 20px;">
				<div style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #10b981; color: #ffffff; border-radius: 50%; font-weight: 700; font-size: 0.875rem; margin-bottom: 12px;">2</div>
				<h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 8px 0; color: var(--text-main);"><?php _e( 'Preview & Configure', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
					<?php _e( 'Choose which sheet to import, preview the first 10 rows, set parse options, configure search & pagination, then click <strong>Save Table</strong>.', 'roxytable' ); ?>
				</p>
			</div>

			<!-- Step 3 -->
			<div style="background-color: var(--bg-main, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px); padding: 20px;">
				<div style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #8b5cf6; color: #ffffff; border-radius: 50%; font-weight: 700; font-size: 0.875rem; margin-bottom: 12px;">3</div>
				<h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 8px 0; color: var(--text-main);"><?php _e( 'Embed Anywhere', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
					<?php _e( 'Copy the generated shortcode (e.g. <code>[roxytable id="1"]</code>) and paste it into any WordPress page, post, Elementor widget, or Gutenberg block.', 'roxytable' ); ?>
				</p>
			</div>
		</div>
	</div>

	<!-- Shortcode Usage & Embedding Guide -->
	<div class="roxytable-card" style="margin-bottom: 24px;">
		<h3 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 16px 0; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
			<span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; background: #e0f2fe; color: #0284c7; border-radius: 50%; font-size: 0.85rem; font-weight: 700;">📋</span>
			<?php _e( 'Shortcode & Embedding Reference', 'roxytable' ); ?>
		</h3>
		<p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 16px 0;">
			<?php _e( 'RoxyTable tables can be rendered on any page, post, custom post type, or widget area using WordPress shortcodes:', 'roxytable' ); ?>
		</p>

		<div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px;">
			<div style="background-color: var(--bg-main, #f8fafc); padding: 14px 18px; border-radius: var(--radius-lg, 8px); border: 1px solid var(--border-color, #e2e8f0); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
				<div>
					<div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 4px;"><?php _e( 'Embed by Table ID (Recommended)', 'roxytable' ); ?></div>
					<code style="font-family: monospace; font-size: 0.95rem; color: var(--primary, #4f46e5); background: transparent; padding: 0;">[roxytable id="1"]</code>
				</div>
				<span style="font-size: 0.8rem; color: var(--text-muted);"><?php _e( 'Replaces 1 with your specific Table ID', 'roxytable' ); ?></span>
			</div>

			<div style="background-color: var(--bg-main, #f8fafc); padding: 14px 18px; border-radius: var(--radius-lg, 8px); border: 1px solid var(--border-color, #e2e8f0); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
				<div>
					<div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 4px;"><?php _e( 'Embed by Table Slug', 'roxytable' ); ?></div>
					<code style="font-family: monospace; font-size: 0.95rem; color: var(--primary, #4f46e5); background: transparent; padding: 0;">[roxytable slug="employee-directory"]</code>
				</div>
				<span style="font-size: 0.8rem; color: var(--text-muted);"><?php _e( 'Useful when referencing tables by clean URL slug', 'roxytable' ); ?></span>
			</div>

			<div style="background-color: var(--bg-main, #f8fafc); padding: 14px 18px; border-radius: var(--radius-lg, 8px); border: 1px solid var(--border-color, #e2e8f0); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
				<div>
					<div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 4px;"><?php _e( 'Embed in PHP Theme Templates', 'roxytable' ); ?></div>
					<code style="font-family: monospace; font-size: 0.95rem; color: var(--primary, #4f46e5); background: transparent; padding: 0;">&lt;?php echo do_shortcode( '[roxytable id="1"]' ); ?&gt;</code>
				</div>
				<span style="font-size: 0.8rem; color: var(--text-muted);"><?php _e( 'For direct theme or template integration', 'roxytable' ); ?></span>
			</div>
		</div>

		<div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; background: #f0fdf4; border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 4px;">
			<strong><?php _e( 'Tip:', 'roxytable' ); ?></strong> <?php _e( 'You can copy the shortcode with one click directly from the <strong>All Tables</strong> list view using the "Copy" button.', 'roxytable' ); ?>
		</div>
	</div>

	<!-- Core Features & Configuration Options -->
	<div class="roxytable-card" style="margin-bottom: 24px;">
		<h3 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 16px 0; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
			<span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; background: #fef3c7; color: #d97706; border-radius: 50%; font-size: 0.85rem; font-weight: 700;">⚙️</span>
			<?php _e( 'Key Features & Customization', 'roxytable' ); ?>
		</h3>

		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
			<div style="padding: 16px; border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( '🔍 Instant Live Search', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'Visitors can search through thousands of records in real-time. Matches across all visible columns instantaneously.', 'roxytable' ); ?></p>
			</div>

			<div style="padding: 16px; border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( '⚡ Fast Column Sorting', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'Click any column header to sort alphabetically, numerically, or chronologically in ascending or descending order.', 'roxytable' ); ?></p>
			</div>

			<div style="padding: 16px; border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( '📄 Smart Pagination', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'Break large tables into manageable pages (25, 50, 100, 250, 500, or all rows) to keep your page load fast and clean.', 'roxytable' ); ?></p>
			</div>

			<div style="padding: 16px; border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( '👁️ Column Visibility Toggle', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'Allow visitors to show or hide specific columns dynamically to view only the information relevant to them.', 'roxytable' ); ?></p>
			</div>

			<div style="padding: 16px; border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( '📥 CSV Export Option', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'Enable frontend CSV download so visitors or team members can download filtered table data directly to their devices.', 'roxytable' ); ?></p>
			</div>

			<div style="padding: 16px; border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-lg, 8px);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( '🔗 Clickable Cell Links', 'roxytable' ); ?></h4>
				<p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;"><?php _e( 'You can embed HTML links directly inside table cells (e.g. <code>&lt;a href="https://..."&gt;Link&lt;/a&gt;</code>) and they will render as active clickable links.', 'roxytable' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Frequently Asked Questions -->
	<div class="roxytable-card" style="margin-bottom: 24px;">
		<h3 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 20px 0; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
			<span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; background: #fce7f3; color: #db2777; border-radius: 50%; font-size: 0.85rem; font-weight: 700;">❓</span>
			<?php _e( 'Frequently Asked Questions', 'roxytable' ); ?>
		</h3>

		<div style="display: flex; flex-direction: column; gap: 18px;">
			<div style="padding-bottom: 14px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'What spreadsheet formats are supported?', 'roxytable' ); ?></h4>
				<p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.5;"><?php _e( 'RoxyTable supports modern Excel (<code>.xlsx</code>), legacy Excel (<code>.xls</code>), and Comma Separated Values (<code>.csv</code>). Multi-sheet workbooks are supported, allowing you to select which worksheet to import.', 'roxytable' ); ?></p>
			</div>

			<div style="padding-bottom: 14px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'Can I edit the table rows and column titles after importing?', 'roxytable' ); ?></h4>
				<p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.5;"><?php _e( 'Yes! Go to <strong>RoxyTable &rarr; All Tables</strong>, click <strong>Edit</strong> on any table. You can edit any cell directly, rename column headers, toggle table options, change template styling, and click "Save Table Data".', 'roxytable' ); ?></p>
			</div>

			<div style="padding-bottom: 14px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'Is RoxyTable compatible with page builders (Elementor, Divi, Beaver Builder, Gutenberg)?', 'roxytable' ); ?></h4>
				<p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.5;"><?php _e( 'Yes, 100%. RoxyTable is built on WordPress standard shortcode architecture. Simply add a Shortcode block/widget in your favorite page builder and enter <code>[roxytable id="YOUR_ID"]</code>.', 'roxytable' ); ?></p>
			</div>

			<div style="padding-bottom: 14px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'Are the tables mobile friendly and responsive?', 'roxytable' ); ?></h4>
				<p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.5;"><?php _e( 'Yes. Tables feature horizontal scroll wrappers and adaptive responsive layouts so your tables remain readable on smartphones, tablets, and desktops.', 'roxytable' ); ?></p>
			</div>

			<div>
				<h4 style="font-size: 0.95rem; font-weight: 600; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'How do I reach support if I encounter a bug or need custom work?', 'roxytable' ); ?></h4>
				<p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
					<?php echo sprintf( __( 'You can reach out directly to the developer team at <a href="mailto:%1$s" style="color: var(--primary, #4f46e5); font-weight: 600; text-decoration: underline;">%1$s</a> or visit <a href="%2$s" target="_blank" rel="noopener noreferrer" style="color: var(--primary, #4f46e5); font-weight: 600; text-decoration: underline;">%2$s</a>.', 'roxytable' ), 'info@webpress.digital', 'https://webpress.digital/' ); ?>
				</p>
			</div>
		</div>
	</div>

	<!-- Footer Support Contact Info -->
	<div style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.875rem;">
		<p style="margin: 0 0 8px 0;">
			<?php echo sprintf( __( 'RoxyTable by %s &mdash; Crafted for high performance WordPress websites.', 'roxytable' ), '<strong>WebPress Technologies</strong>' ); ?>
		</p>
		<p style="margin: 0;">
			<?php _e( 'Need direct help? Contact:', 'roxytable' ); ?> 
			<a href="mailto:info@webpress.digital" style="color: var(--primary, #4f46e5); font-weight: 600; text-decoration: none;">info@webpress.digital</a>
			&bull; 
			<a href="https://webpress.digital/" target="_blank" rel="noopener noreferrer" style="color: var(--primary, #4f46e5); font-weight: 600; text-decoration: none;">webpress.digital</a>
		</p>
	</div>

</div>
