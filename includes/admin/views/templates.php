<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="roxytable-templates-container">
	<div style="margin-bottom: 24px;">
		<h2 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text-main);"><?php _e( 'Design Templates', 'roxytable' ); ?></h2>
		<p style="margin: 4px 0 0 0; color: var(--text-muted); font-size: 0.875rem;"><?php _e( 'Start with a pre-styled theme layout for your frontend tables.', 'roxytable' ); ?></p>
	</div>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
		<!-- Template Card 1: Modern Slate -->
		<div class="roxytable-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 0;">
			<div style="height: 140px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); display: flex; align-items: center; justify-content: center; color: #ffffff;">
				<div style="font-weight: 700; font-size: 1.25rem;"><?php _e( 'Modern Indigo', 'roxytable' ); ?></div>
			</div>
			<div style="padding: 20px;">
				<h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 4px 0; color: var(--text-main);"><?php _e( 'Indigo Clean', 'roxytable' ); ?></h4>
				<p style="color: var(--text-muted); font-size: 0.825rem; margin: 0 0 16px 0;"><?php _e( 'Modern clean borders, rounded layout, colored header cells and smooth pagination indicators.', 'roxytable' ); ?></p>
				<button class="roxytable-btn roxytable-btn-secondary" style="width: 100%;"><?php _e( 'Use Template', 'roxytable' ); ?></button>
			</div>
		</div>

		<!-- Template Card 2: Minimalist Dark -->
		<div class="roxytable-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 0;">
			<div style="height: 140px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); display: flex; align-items: center; justify-content: center; color: #ffffff;">
				<div style="font-weight: 700; font-size: 1.25rem;"><?php _e( 'Sleek Obsidian', 'roxytable' ); ?></div>
			</div>
			<div style="padding: 20px;">
				<h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 4px 0; color: var(--text-main);"><?php _e( 'Dark Minimal', 'roxytable' ); ?></h4>
				<p style="color: var(--text-muted); font-size: 0.825rem; margin: 0 0 16px 0;"><?php _e( 'A gorgeous sleek obsidian header, thin divider borders, perfect for dark mode sites.', 'roxytable' ); ?></p>
				<button class="roxytable-btn roxytable-btn-secondary" style="width: 100%;"><?php _e( 'Use Template', 'roxytable' ); ?></button>
			</div>
		</div>

		<!-- Template Card 3: Emerald Emerald -->
		<div class="roxytable-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 0;">
			<div style="height: 140px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; color: #ffffff;">
				<div style="font-weight: 700; font-size: 1.25rem;"><?php _e( 'Forest Emerald', 'roxytable' ); ?></div>
			</div>
			<div style="padding: 20px;">
				<h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 4px 0; color: var(--text-main);"><?php _e( 'Emerald Grid', 'roxytable' ); ?></h4>
				<p style="color: var(--text-muted); font-size: 0.825rem; margin: 0 0 16px 0;"><?php _e( 'Fresh corporate look featuring emerald accents, zebra striping, and clean typography layouts.', 'roxytable' ); ?></p>
				<button class="roxytable-btn roxytable-btn-secondary" style="width: 100%;"><?php _e( 'Use Template', 'roxytable' ); ?></button>
			</div>
		</div>
	</div>
</div>
