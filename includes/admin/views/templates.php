<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-templates-container">
	<div class="rt-dash-card rt-dash-recent" style="margin-bottom: 20px;">
		<div class="rt-dash-recent-head" style="border-bottom: 0;">
			<div class="rt-dash-recent-heading">
				<span class="rt-dash-recent-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z" /></svg>
				</span>
				<div>
					<h2><?php _e( 'Design Templates', 'roxytable' ); ?></h2>
					<p><?php _e( 'Start with a pre-styled theme layout for your frontend tables', 'roxytable' ); ?></p>
				</div>
			</div>
			<div class="rt-dash-recent-tools">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-tables' ) ); ?>" class="rt-dash-btn rt-dash-btn-outline"><?php _e( 'All Tables', 'roxytable' ); ?></a>
			</div>
		</div>
	</div>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
		<!-- Template Card 1: Modern Indigo -->
		<div class="roxytable-card rt-dash-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 0;">
			<div style="height: 140px; background: linear-gradient(135deg, #3b82f6 0%, #1d5bf0 100%); display: flex; align-items: center; justify-content: center; color: #ffffff;">
				<div style="font-weight: 700; font-size: 1.25rem;"><?php _e( 'Modern Indigo', 'roxytable' ); ?></div>
			</div>
			<div style="padding: 20px;">
				<h4 style="font-size: 1rem; font-weight: 700; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'Indigo Clean', 'roxytable' ); ?></h4>
				<p style="color: var(--text-muted); font-size: 0.825rem; margin: 0 0 16px 0; line-height: 1.5;"><?php _e( 'Modern clean borders, rounded layout, colored header cells and smooth pagination indicators.', 'roxytable' ); ?></p>
				<form method="post" class="rt-template-form">
					<?php wp_nonce_field( 'roxytable_use_template', 'roxytable_template_nonce' ); ?>
					<button type="submit" name="roxytable_use_template" value="indigo" class="rt-dash-btn rt-dash-btn-outline rt-dash-btn-block"><?php _e( 'Use Template', 'roxytable' ); ?></button>
				</form>
			</div>
		</div>

		<!-- Template Card 2: Minimalist Dark -->
		<div class="roxytable-card rt-dash-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 0;">
			<div style="height: 140px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); display: flex; align-items: center; justify-content: center; color: #ffffff;">
				<div style="font-weight: 700; font-size: 1.25rem;"><?php _e( 'Sleek Obsidian', 'roxytable' ); ?></div>
			</div>
			<div style="padding: 20px;">
				<h4 style="font-size: 1rem; font-weight: 700; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'Dark Minimal', 'roxytable' ); ?></h4>
				<p style="color: var(--text-muted); font-size: 0.825rem; margin: 0 0 16px 0; line-height: 1.5;"><?php _e( 'A gorgeous sleek obsidian header, thin divider borders, perfect for dark mode sites.', 'roxytable' ); ?></p>
				<form method="post" class="rt-template-form">
					<?php wp_nonce_field( 'roxytable_use_template', 'roxytable_template_nonce' ); ?>
					<button type="submit" name="roxytable_use_template" value="obsidian" class="rt-dash-btn rt-dash-btn-outline rt-dash-btn-block"><?php _e( 'Use Template', 'roxytable' ); ?></button>
				</form>
			</div>
		</div>

		<!-- Template Card 3: Forest Emerald -->
		<div class="roxytable-card rt-dash-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 0;">
			<div style="height: 140px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; color: #ffffff;">
				<div style="font-weight: 700; font-size: 1.25rem;"><?php _e( 'Forest Emerald', 'roxytable' ); ?></div>
			</div>
			<div style="padding: 20px;">
				<h4 style="font-size: 1rem; font-weight: 700; margin: 0 0 6px 0; color: var(--text-main);"><?php _e( 'Emerald Grid', 'roxytable' ); ?></h4>
				<p style="color: var(--text-muted); font-size: 0.825rem; margin: 0 0 16px 0; line-height: 1.5;"><?php _e( 'Fresh corporate look featuring emerald accents, zebra striping, and clean typography layouts.', 'roxytable' ); ?></p>
				<form method="post" class="rt-template-form">
					<?php wp_nonce_field( 'roxytable_use_template', 'roxytable_template_nonce' ); ?>
					<button type="submit" name="roxytable_use_template" value="emerald" class="rt-dash-btn rt-dash-btn-outline rt-dash-btn-block"><?php _e( 'Use Template', 'roxytable' ); ?></button>
				</form>
			</div>
		</div>
	</div>
</div>
