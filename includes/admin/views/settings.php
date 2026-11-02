<?php
/**
 * RoxyTable Settings & Single-Site Pro License administration view.
 *
 * @package RoxyTable
 */

use RoxyTable\Includes\License;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'roxytable' ) );
}

if ( ! class_exists( '\RoxyTable\Includes\License' ) && defined( 'ROXYTABLE_PATH' ) && file_exists( ROXYTABLE_PATH . 'includes/class-license.php' ) ) {
	require_once ROXYTABLE_PATH . 'includes/class-license.php';
}

$license_data   = License::get_license_data();
$is_pro         = License::is_pro();
$is_vendor_mode = License::is_vendor_mode();
$current_domain = License::get_site_domain();
$license_notice = get_transient( 'roxytable_license_notice_' . get_current_user_id() );
if ( $license_notice ) {
	delete_transient( 'roxytable_license_notice_' . get_current_user_id() );
}

$generated_client = null;
if ( $is_vendor_mode ) {
	$generated_client = get_transient( 'roxytable_generated_client_key_' . get_current_user_id() );
	if ( $generated_client ) {
		delete_transient( 'roxytable_generated_client_key_' . get_current_user_id() );
	}
}

$global_settings = get_option(
	'roxytable_global_settings',
	[
		'default_rows_per_page' => 25,
		'csv_delimiter'         => ',',
		'enable_zebra'          => true,
		'enable_borders'        => true,
	]
);
if ( ! is_array( $global_settings ) ) {
	$global_settings = [];
}

$sample_annual_key   = $is_vendor_mode ? License::generate_key( gmdate( 'Ymd', strtotime( '+1 year' ) ), $current_domain ) : '';
$sample_two_year_key = $is_vendor_mode ? License::generate_key( gmdate( 'Ymd', strtotime( '+2 years' ) ), $current_domain ) : '';
$sample_lifetime_key = $is_vendor_mode ? License::generate_key( 'LIFETIME', $current_domain ) : '';
$icon_bg_style       = $is_pro ? 'background: rgba(16, 185, 129, 0.12); color: #059669;' : 'background: rgba(59, 130, 246, 0.12); color: #2563eb;';
?>
<div class="roxytable-settings-container" style="display: flex; flex-direction: column; gap: 24px;">

	<?php if ( is_array( $license_notice ) && ! empty( $license_notice['message'] ) ) : ?>
		<?php $notice_class = 'success' === ( $license_notice['type'] ?? '' ) ? 'notice-success' : 'notice-error'; ?>
		<div class="notice <?php echo esc_attr( $notice_class ); ?>" style="margin: 0; padding: 12px 16px; border-radius: 8px;">
			<p style="margin: 0; font-weight: 500;"><?php echo esc_html( (string) $license_notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<!-- ── License & Subscription Card ── -->
	<div class="roxytable-card rt-dash-card rt-dash-recent" style="padding: 0; overflow: hidden;">
		<div class="rt-dash-recent-head" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
			<div class="rt-dash-recent-heading">
				<span class="rt-dash-recent-icon" aria-hidden="true" style="<?php echo esc_attr( $icon_bg_style ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
				</span>
				<div>
					<h2><?php esc_html_e( 'RoxyTable Pro License (Single-Site)', 'roxytable' ); ?></h2>
					<p><?php esc_html_e( 'Each license key is cryptographically bound to a single website domain and cannot be reused on another site.', 'roxytable' ); ?></p>
				</div>
			</div>
			<div>
				<?php if ( $is_pro ) : ?>
					<span class="rt-license-badge rt-license-badge-active">
						<span class="rt-license-dot"></span>
						<?php esc_html_e( 'PRO ACTIVE (1 SITE)', 'roxytable' ); ?>
					</span>
				<?php elseif ( 'expired' === $license_data['status'] ) : ?>
					<span class="rt-license-badge rt-license-badge-expired">
						<span class="rt-license-dot"></span>
						<?php esc_html_e( 'LICENSE EXPIRED', 'roxytable' ); ?>
					</span>
				<?php elseif ( 'domain_mismatch' === $license_data['status'] ) : ?>
					<span class="rt-license-badge rt-license-badge-expired">
						<span class="rt-license-dot"></span>
						<?php esc_html_e( 'DOMAIN MISMATCH', 'roxytable' ); ?>
					</span>
				<?php else : ?>
					<span class="rt-license-badge rt-license-badge-free">
						<?php esc_html_e( 'FREE VERSION', 'roxytable' ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<div style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
			<?php if ( $is_pro ) : ?>
				<div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
					<div>
						<div style="font-weight: 600; color: #166534; font-size: 0.95rem;">
							<?php esc_html_e( 'Your single-site RoxyTable Pro license is active.', 'roxytable' ); ?>
						</div>
						<div style="color: #15803d; font-size: 0.85rem; margin-top: 4px;">
							<strong><?php esc_html_e( 'Authorized Site:', 'roxytable' ); ?></strong>
							<code><?php echo esc_html( $current_domain ); ?></code>
							&nbsp;&bull;&nbsp;
							<strong><?php esc_html_e( 'License Key:', 'roxytable' ); ?></strong>
							<code><?php echo esc_html( License::mask_key( $license_data['key'] ) ); ?></code>
							&nbsp;&bull;&nbsp;
							<?php if ( 'lifetime' === $license_data['expires_at'] ) : ?>
								<strong><?php esc_html_e( 'Plan:', 'roxytable' ); ?></strong> <?php esc_html_e( 'Lifetime Single-Site License', 'roxytable' ); ?>
							<?php else : ?>
								<strong><?php esc_html_e( 'Renews / Expires:', 'roxytable' ); ?></strong>
								<?php echo esc_html( $license_data['expires_at'] ); ?>
								<?php if ( null !== $license_data['days_remaining'] ) : ?>
									<?php $days_left_count = max( 0, (int) $license_data['days_remaining'] ); ?>
									(<?php
									echo esc_html(
										sprintf(
											/* translators: %d: number of days remaining on the annual license. */
											_n( '%d day remaining', '%d days remaining', $days_left_count, 'roxytable' ),
											$days_left_count
										)
									);
									?>)
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-settings' ) ); ?>" style="margin: 0;">
						<?php wp_nonce_field( 'roxytable_license_action', 'roxytable_license_nonce' ); ?>
						<input type="hidden" name="roxytable_license_action" value="deactivate" />
						<button type="submit" class="rt-dash-btn rt-dash-btn-outline" style="color: #dc2626; border-color: #fecaca;">
							<?php esc_html_e( 'Deactivate License', 'roxytable' ); ?>
						</button>
					</form>
				</div>
			<?php else : ?>
				<?php if ( 'expired' === $license_data['status'] ) : ?>
					<div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 18px; color: #991b1b; font-size: 0.875rem;">
						<strong><?php esc_html_e( 'Annual License Expired:', 'roxytable' ); ?></strong>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: expiration date */
								__( 'Your RoxyTable Pro license expired on %s. Please enter a renewed annual license key below to restore Pro features.', 'roxytable' ),
								$license_data['expires_at']
							)
						);
						?>
					</div>
				<?php elseif ( 'domain_mismatch' === $license_data['status'] ) : ?>
					<div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 18px; color: #991b1b; font-size: 0.875rem;">
						<strong><?php esc_html_e( 'Single-Site License Restriction:', 'roxytable' ); ?></strong>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: current site domain */
								__( 'The stored license key is bound to a different website and cannot be used on %s. Please enter a license key issued for this domain.', 'roxytable' ),
								$current_domain
							)
						);
						?>
					</div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-settings' ) ); ?>" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
					<?php wp_nonce_field( 'roxytable_license_action', 'roxytable_license_nonce' ); ?>
					<input type="hidden" name="roxytable_license_action" value="activate" />
					<div style="flex: 1 1 360px;">
						<label class="rt-label" for="rt-license-key">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: current site domain */
									__( 'Enter Pro License Key for %s', 'roxytable' ),
									$current_domain
								)
							);
							?>
						</label>
						<input
							id="rt-license-key"
							name="rt_license_key"
							type="text"
							class="rt-field"
							placeholder="ROXY-PRO-YYYYMMDD-XXXXXX-XXXXXXXX"
							value="<?php echo esc_attr( $license_data['key'] ); ?>"
							maxlength="64"
							pattern="[A-Za-z0-9\-]+"
							autocomplete="off"
							spellcheck="false"
							style="font-family: monospace; letter-spacing: 0.03em;"
							required
						/>
					</div>
					<div>
						<button type="submit" class="rt-dash-btn rt-dash-btn-primary" style="height: 40px;">
							<?php esc_html_e( 'Activate Pro License', 'roxytable' ); ?>
						</button>
					</div>
				</form>
			<?php endif; ?>

			<!-- Free vs Pro Feature Matrix -->
			<div style="border-top: 1px solid var(--border-color); padding-top: 18px;">
				<h5 class="rt-section-heading" style="margin-bottom: 12px;"><?php esc_html_e( 'Free vs. Pro Plan Comparison', 'roxytable' ); ?></h5>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
					<div style="padding: 16px; border: 1px solid var(--border-color); border-radius: 10px; background: #f8fafc;">
						<div style="font-weight: 700; color: var(--text-main); margin-bottom: 8px;"><?php esc_html_e( 'Free Plan Includes', 'roxytable' ); ?></div>
						<ul style="margin: 0; padding-left: 18px; color: var(--text-muted); font-size: 0.85rem; line-height: 1.7;">
							<li>
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: maximum tables in Free plan, 2: maximum rows per table in Free plan. */
										__( 'Up to %1$d tables & %2$d rows per table', 'roxytable' ),
										License::FREE_MAX_TABLES,
										License::FREE_MAX_ROWS
									)
								);
								?>
							</li>
							<li><?php esc_html_e( 'Basic search bar, column sorting & pagination', 'roxytable' ); ?></li>
							<li><?php esc_html_e( 'Default & Indigo Clean table templates', 'roxytable' ); ?></li>
						</ul>
					</div>
					<div style="padding: 16px; border: 1px solid #bfdbfe; border-radius: 10px; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);">
						<div style="font-weight: 700; color: #1e40af; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
							<?php esc_html_e( 'Pro Annual Plan Unlocks (Single-Site)', 'roxytable' ); ?>
							<span class="rt-pro-badge">PRO</span>
						</div>
						<ul style="margin: 0; padding-left: 18px; color: #1e3a8a; font-size: 0.85rem; line-height: 1.7;">
							<li><?php esc_html_e( 'Unlimited tables & unlimited rows per spreadsheet', 'roxytable' ); ?></li>
							<li><?php esc_html_e( 'Custom JavaScript Engine (live date/expiry calculations & DOM automation)', 'roxytable' ); ?></li>
							<li><?php esc_html_e( 'Scoped Custom CSS per table', 'roxytable' ); ?></li>
							<li><?php esc_html_e( 'Frontend Filter Columns, Sheet-based filter rules, Column visibility toggle & CSV Export', 'roxytable' ); ?></li>
							<li><?php esc_html_e( 'Premium Templates (Dark Minimal & Emerald Grid) + Server-Side AJAX (10,000+ rows)', 'roxytable' ); ?></li>
						</ul>
					</div>
				</div>
			</div>

			<!-- Vendor Key Generator (only rendered when ROXYTABLE_VENDOR_MODE is enabled in wp-config.php) -->
			<?php if ( $is_vendor_mode ) : ?>
			<details style="border-top: 1px dashed var(--border-color); padding-top: 14px;" <?php echo is_array( $generated_client ) ? 'open' : ''; ?>>
				<summary style="cursor: pointer; font-size: 0.8125rem; font-weight: 600; color: var(--text-muted);">
					<?php esc_html_e( 'Developer / Vendor Tool: Generate Single-Site Client License Keys', 'roxytable' ); ?>
				</summary>
				<div style="margin-top: 12px; padding: 16px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.8125rem; display: flex; flex-direction: column; gap: 14px;">
					<p style="margin: 0; color: var(--text-muted);">
						<?php esc_html_e( 'Enter your client\'s website domain below to generate a license key locked exclusively to that domain. It will not work on any other website:', 'roxytable' ); ?>
					</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-settings' ) ); ?>" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
						<?php wp_nonce_field( 'roxytable_license_action', 'roxytable_license_nonce' ); ?>
						<input type="hidden" name="roxytable_license_action" value="generate_client_key" />
						<div style="flex: 1 1 260px;">
							<label class="rt-label" for="rt-client-domain"><?php esc_html_e( 'Client Website Domain or URL', 'roxytable' ); ?></label>
							<?php $gen_duration = is_array( $generated_client ) && isset( $generated_client['duration'] ) ? (string) $generated_client['duration'] : '1year'; ?>
							<input
								id="rt-client-domain"
								name="rt_client_domain"
								type="text"
								class="rt-field"
								placeholder="e.g. clientwebsite.com or https://coren-eac.org"
								value="<?php echo esc_attr( is_array( $generated_client ) && isset( $generated_client['domain'] ) ? (string) $generated_client['domain'] : '' ); ?>"
								maxlength="255"
								autocomplete="off"
								spellcheck="false"
								required
							/>
						</div>
						<div style="min-width: 170px;">
							<label class="rt-label" for="rt-client-duration"><?php esc_html_e( 'Subscription Term', 'roxytable' ); ?></label>
							<select id="rt-client-duration" name="rt_client_duration" class="rt-field">
								<option value="1year" <?php selected( '1year', $gen_duration ); ?>><?php esc_html_e( '1 Year (Annual)', 'roxytable' ); ?></option>
								<option value="2years" <?php selected( '2years', $gen_duration ); ?>><?php esc_html_e( '2 Years', 'roxytable' ); ?></option>
								<option value="lifetime" <?php selected( 'lifetime', $gen_duration ); ?>><?php esc_html_e( 'Lifetime', 'roxytable' ); ?></option>
							</select>
						</div>
						<div>
							<button type="submit" class="rt-dash-btn rt-dash-btn-primary" style="height: 38px;">
								<?php esc_html_e( 'Generate Single-Site Key', 'roxytable' ); ?>
							</button>
						</div>
					</form>

					<?php if ( is_array( $generated_client ) && ! empty( $generated_client['key'] ) ) : ?>
						<div style="padding: 12px 14px; background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; color: #1e3a8a;">
							<div style="font-weight: 700; margin-bottom: 4px;">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: client domain, 2: expiration date */
										__( 'Generated Single-Site Key for "%1$s" (Expires: %2$s):', 'roxytable' ),
										(string) $generated_client['domain'],
										(string) $generated_client['expires']
									)
								);
								?>
							</div>
							<code style="user-select: all; display: inline-block; padding: 6px 10px; background: #fff; border: 1px solid #60a5fa; border-radius: 4px; font-size: 0.9rem; font-weight: 700; color: #1d4ed8;"><?php echo esc_html( (string) $generated_client['key'] ); ?></code>
						</div>
					<?php endif; ?>

					<div style="border-top: 1px solid var(--border-color); padding-top: 10px; display: grid; gap: 6px;">
						<div style="font-weight: 600; color: var(--text-main);">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: current site domain */
									__( 'Keys Bound to This Current Site (%s):', 'roxytable' ),
									$current_domain
								)
							);
							?>
						</div>
						<div>
							<strong><?php esc_html_e( '1-Year Key:', 'roxytable' ); ?></strong>
							<code style="user-select: all; margin-left: 6px; padding: 2px 8px; background: #fff; border: 1px solid #cbd5e1; border-radius: 4px;"><?php echo esc_html( $sample_annual_key ); ?></code>
						</div>
						<div>
							<strong><?php esc_html_e( '2-Year Key:', 'roxytable' ); ?></strong>
							<code style="user-select: all; margin-left: 6px; padding: 2px 8px; background: #fff; border: 1px solid #cbd5e1; border-radius: 4px;"><?php echo esc_html( $sample_two_year_key ); ?></code>
						</div>
						<div>
							<strong><?php esc_html_e( 'Lifetime Key:', 'roxytable' ); ?></strong>
							<code style="user-select: all; margin-left: 6px; padding: 2px 8px; background: #fff; border: 1px solid #cbd5e1; border-radius: 4px;"><?php echo esc_html( $sample_lifetime_key ); ?></code>
						</div>
					</div>
				</div>
			</details>
			<?php endif; ?>
		</div>
	</div>

	<!-- ── Global Defaults Card ── -->
	<div class="roxytable-card rt-dash-card rt-dash-recent" style="padding: 0; overflow: hidden;">
		<div class="rt-dash-recent-head">
			<div class="rt-dash-recent-heading">
				<span class="rt-dash-recent-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257.124 1.075l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
				</span>
				<div>
					<h2><?php esc_html_e( 'Global Settings', 'roxytable' ); ?></h2>
					<p><?php esc_html_e( 'Configure default behavior for RoxyTable frontend tables and CSV parsing', 'roxytable' ); ?></p>
				</div>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=roxytable-settings' ) ); ?>" style="padding: 24px; display: flex; flex-direction: column; gap: 28px;">
			<?php wp_nonce_field( 'roxytable_save_global_settings', 'roxytable_global_settings_nonce' ); ?>
			<input type="hidden" name="roxytable_save_global_settings" value="1" />

			<div>
				<h5 class="rt-section-heading"><?php esc_html_e( '1. General Defaults', 'roxytable' ); ?></h5>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 280px)); gap: 20px;">
					<div>
						<label class="rt-label" for="rt-setting-rows"><?php esc_html_e( 'Default Rows Per Page', 'roxytable' ); ?></label>
						<input id="rt-setting-rows" name="rt_default_rows_per_page" type="number" class="rt-field" min="5" max="500" value="<?php echo esc_attr( (string) intval( $global_settings['default_rows_per_page'] ?? 25 ) ); ?>" />
					</div>
					<div>
						<label class="rt-label" for="rt-setting-delim"><?php esc_html_e( 'CSV Delimiter Character', 'roxytable' ); ?></label>
						<input id="rt-setting-delim" name="rt_csv_delimiter" type="text" class="rt-field" maxlength="1" value="<?php echo esc_attr( (string) ( $global_settings['csv_delimiter'] ?? ',' ) ); ?>" style="max-width: 100px; text-align: center;" />
					</div>
				</div>
			</div>

			<div>
				<h5 class="rt-section-heading"><?php esc_html_e( '2. Visual Defaults', 'roxytable' ); ?></h5>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
					<label class="rt-toggle-label">
						<span class="rt-toggle-switch">
							<input type="checkbox" id="enable_zebra" name="rt_enable_zebra" value="1" <?php checked( ! empty( $global_settings['enable_zebra'] ), true ); ?> />
							<span class="rt-toggle-slider"></span>
						</span>
						<span class="rt-toggle-text"><?php esc_html_e( 'Enable Row Zebra Striping', 'roxytable' ); ?></span>
					</label>
					<label class="rt-toggle-label">
						<span class="rt-toggle-switch">
							<input type="checkbox" id="enable_borders" name="rt_enable_borders" value="1" <?php checked( ! empty( $global_settings['enable_borders'] ), true ); ?> />
							<span class="rt-toggle-slider"></span>
						</span>
						<span class="rt-toggle-text"><?php esc_html_e( 'Show Vertical Gridlines', 'roxytable' ); ?></span>
					</label>
				</div>
			</div>

			<div style="padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
				<button type="submit" class="rt-dash-btn rt-dash-btn-primary"><?php esc_html_e( 'Save Settings', 'roxytable' ); ?></button>
			</div>
		</form>
	</div>
</div>
