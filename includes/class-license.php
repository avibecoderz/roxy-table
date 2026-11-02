<?php
/**
 * License management, single-site domain binding, and Free vs. Pro feature gating for RoxyTable.
 *
 * @package RoxyTable
 */

namespace RoxyTable\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages RoxyTable Free vs. Pro licensing, single-site domain enforcement, annual expiration checks, and feature gating.
 */
class License {

	/**
	 * Option key used to store license state in wp_options.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'roxytable_license_data';

	/**
	 * Maximum number of tables allowed in the Free version.
	 *
	 * @var int
	 */
	public const FREE_MAX_TABLES = 5;

	/**
	 * Maximum number of rows per table allowed in the Free version.
	 *
	 * @var int
	 */
	public const FREE_MAX_ROWS = 250;

	/**
	 * Internal signing secret used to verify single-site annual license keys.
	 *
	 * @var string
	 */
	private const SIGNING_SECRET = 'roxytable_pro_annual_secret_v1_9f8e7d6c5b4a';

	/**
	 * Normalize a site URL or domain into a canonical host/path identifier for single-site binding.
	 *
	 * Examples:
	 * - "https://www.ClientSite.com/ sub/" -> "clientsite.com"
	 * - "http://localhost/roxytable/"      -> "localhost/roxytable"
	 *
	 * @param string $raw_domain_or_url Domain or full URL.
	 * @return string Normalized lowercase site identifier.
	 */
	public static function normalize_domain( string $raw_domain_or_url ): string {
		$input = function_exists( 'wp_unslash' ) ? wp_unslash( $raw_domain_or_url ) : stripslashes( $raw_domain_or_url );
		if ( function_exists( 'sanitize_text_field' ) ) {
			$input = sanitize_text_field( (string) $input );
		}
		$input = substr( strtolower( trim( (string) $input ) ), 0, 255 );
		if ( '' === $input ) {
			return 'localhost';
		}

		if ( false === strpos( $input, '://' ) ) {
			$input = 'http://' . ltrim( $input, '/' );
		}

		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $input ) : parse_url( $input );
		$host  = isset( $parts['host'] ) ? strtolower( trim( (string) $parts['host'] ) ) : '';
		$path  = isset( $parts['path'] ) ? trim( (string) $parts['path'], '/' ) : '';

		if ( 0 === strpos( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}

		if ( '' === $host ) {
			$host = 'localhost';
		}

		// For localhost / IP-based installs, include the first path segment (subdirectory) so separate local WP installs remain distinct.
		if ( in_array( $host, [ 'localhost', '127.0.0.1', '::1' ], true ) && '' !== $path ) {
			$segments = explode( '/', $path );
			$first    = preg_replace( '/[^a-z0-9\-_]/', '', (string) ( $segments[0] ?? '' ) );
			if ( '' !== $first && 'wp-admin' !== $first ) {
				return $host . '/' . $first;
			}
		}

		$clean_host = trim( (string) preg_replace( '/[^a-z0-9\.\-_]/', '', $host ), '.' );
		return '' !== $clean_host ? $clean_host : 'localhost';
	}

	/**
	 * Get the current WordPress installation's normalized site domain.
	 *
	 * @return string Normalized site domain for the active WordPress site.
	 */
	public static function get_site_domain(): string {
		if ( function_exists( 'home_url' ) ) {
			return self::normalize_domain( (string) home_url() );
		}
		if ( function_exists( 'get_option' ) ) {
			$home = (string) get_option( 'home', '' );
			if ( '' !== $home ) {
				return self::normalize_domain( $home );
			}
			$siteurl = (string) get_option( 'siteurl', '' );
			if ( '' !== $siteurl ) {
				return self::normalize_domain( $siteurl );
			}
		}
		if ( defined( 'WP_HOME' ) && is_string( WP_HOME ) && '' !== WP_HOME ) {
			return self::normalize_domain( WP_HOME );
		}
		return 'localhost/roxytable';
	}

	/**
	 * Compute the 6-character uppercase domain hash for a normalized site domain.
	 *
	 * @param string $normalized_domain Normalized domain string.
	 * @return string 6-character hex hash.
	 */
	public static function get_domain_hash( string $normalized_domain ): string {
		$domain = self::normalize_domain( $normalized_domain );
		return strtoupper( substr( hash_hmac( 'sha256', 'ROXY-SITE|' . $domain, self::SIGNING_SECRET ), 0, 6 ) );
	}

	/**
	 * Sanitize a raw license key string.
	 *
	 * @param string $raw_key Raw license key input.
	 * @return string Uppercase sanitized key (only A-Z, 0-9, and hyphens, max 64 chars).
	 */
	public static function sanitize_key_string( string $raw_key ): string {
		$key = function_exists( 'wp_unslash' ) ? wp_unslash( $raw_key ) : stripslashes( $raw_key );
		if ( function_exists( 'sanitize_text_field' ) ) {
			$key = sanitize_text_field( (string) $key );
		}
		$key = strtoupper( trim( (string) $key ) );
		$key = (string) preg_replace( '/[^A-Z0-9\-]/', '', $key );
		return substr( $key, 0, 64 );
	}

	/**
	 * Generate a single-site bound RoxyTable Pro license key for a specific site domain and expiration date.
	 *
	 * Format: ROXY-PRO-YYYYMMDD-DOMHASH-XXXXXXXX (or ROXY-PRO-LIFETIME-DOMHASH-XXXXXXXX)
	 *
	 * @param string $expires_ymd Expiration date in YYYYMMDD format, or 'LIFETIME'. Defaults to +1 year from today.
	 * @param string $site_domain Target client domain/URL. Defaults to the current site's domain.
	 * @return string Signed single-site license key.
	 */
	public static function generate_key( string $expires_ymd = '', string $site_domain = '' ): string {
		$expires_ymd = self::sanitize_key_string( $expires_ymd );
		if ( '' === $expires_ymd ) {
			$expires_ymd = gmdate( 'Ymd', strtotime( '+1 year' ) );
		}
		if ( 'LIFETIME' !== $expires_ymd && ! preg_match( '/^\d{8}$/', $expires_ymd ) ) {
			$expires_ymd = gmdate( 'Ymd', strtotime( '+1 year' ) );
		}

		$normalized_domain = '' !== trim( $site_domain )
			? self::normalize_domain( $site_domain )
			: self::get_site_domain();

		$dom_hash = self::get_domain_hash( $normalized_domain );
		$sig      = strtoupper(
			substr(
				hash_hmac( 'sha256', 'ROXY-PRO|' . $expires_ymd . '|' . $dom_hash . '|' . $normalized_domain, self::SIGNING_SECRET ),
				0,
				8
			)
		);

		return 'ROXY-PRO-' . $expires_ymd . '-' . $dom_hash . '-' . $sig;
	}

	/**
	 * Validate a license key against a specific site domain and return parsed metadata.
	 *
	 * @param string $raw_key     Raw license key entered by the user.
	 * @param string $site_domain Optional domain to verify against (defaults to current site domain).
	 * @return array{valid: bool, status: string, key: string, domain: string, expires_at: string, message: string}
	 */
	public static function verify_key( string $raw_key, string $site_domain = '' ): array {
		$key            = self::sanitize_key_string( $raw_key );
		$current_domain = '' !== trim( $site_domain )
			? self::normalize_domain( $site_domain )
			: self::get_site_domain();

		if ( '' === $key ) {
			return [
				'valid'      => false,
				'status'     => 'inactive',
				'key'        => '',
				'domain'     => $current_domain,
				'expires_at' => '',
				'message'    => __( 'Please enter a license key.', 'roxytable' ),
			];
		}

		// Allow custom external validation via filter with strict return normalization.
		if ( function_exists( 'apply_filters' ) ) {
			$custom = apply_filters( 'roxytable_verify_license_key', null, $key, $current_domain );
			if ( is_array( $custom ) && isset( $custom['valid'] ) ) {
				$allowed_statuses = [ 'active', 'expired', 'domain_mismatch', 'invalid', 'inactive' ];
				$raw_status       = isset( $custom['status'] ) ? strtolower( trim( (string) $custom['status'] ) ) : 'invalid';
				return [
					'valid'      => (bool) $custom['valid'],
					'status'     => in_array( $raw_status, $allowed_statuses, true ) ? $raw_status : 'invalid',
					'key'        => isset( $custom['key'] ) ? self::sanitize_key_string( (string) $custom['key'] ) : $key,
					'domain'     => $current_domain,
					'expires_at' => isset( $custom['expires_at'] ) ? preg_replace( '/[^a-zA-Z0-9\-]/', '', (string) $custom['expires_at'] ) : '',
					'message'    => isset( $custom['message'] ) && function_exists( 'sanitize_text_field' )
						? sanitize_text_field( (string) $custom['message'] )
						: (string) ( $custom['message'] ?? '' ),
				];
			}
		}

		// Match single-site signed key format: ROXY-PRO-YYYYMMDD-DOMHASH-XXXXXXXX or ROXY-PRO-LIFETIME-DOMHASH-XXXXXXXX.
		if ( preg_match( '/^ROXY-PRO-(LIFETIME|\d{8})-([A-F0-9]{6})-([A-F0-9]{8})$/', $key, $matches ) ) {
			$date_part     = $matches[1];
			$key_dom_hash  = $matches[2];
			$sig_part      = $matches[3];
			$expected_dom  = self::get_domain_hash( $current_domain );

			// 1. Verify that this license key was issued for THIS site's domain.
			if ( ! hash_equals( $expected_dom, $key_dom_hash ) ) {
				return [
					'valid'      => false,
					'status'     => 'domain_mismatch',
					'key'        => $key,
					'domain'     => $current_domain,
					'expires_at' => '',
					'message'    => sprintf(
						/* translators: %s: current site domain */
						__( 'This license key is bound to another website and cannot be used on this site (%s). Each RoxyTable Pro license is valid for one site only.', 'roxytable' ),
						$current_domain
					),
				];
			}

			// 2. Verify the HMAC signature across the expiration date, domain hash, and current site domain.
			$expected_sig = strtoupper(
				substr(
					hash_hmac( 'sha256', 'ROXY-PRO|' . $date_part . '|' . $key_dom_hash . '|' . $current_domain, self::SIGNING_SECRET ),
					0,
					8
				)
			);

			if ( hash_equals( $expected_sig, $sig_part ) ) {
				if ( 'LIFETIME' === $date_part ) {
					return [
						'valid'      => true,
						'status'     => 'active',
						'key'        => $key,
						'domain'     => $current_domain,
						'expires_at' => 'lifetime',
						'message'    => sprintf(
							/* translators: %s: activated site domain */
							__( 'Pro Lifetime License activated for %s.', 'roxytable' ),
							$current_domain
						),
					];
				}

				$year  = (int) substr( $date_part, 0, 4 );
				$month = (int) substr( $date_part, 4, 2 );
				$day   = (int) substr( $date_part, 6, 2 );

				if ( checkdate( $month, $day, $year ) ) {
					$formatted_date = sprintf( '%04d-%02d-%02d', $year, $month, $day );
					$today_ymd      = gmdate( 'Ymd' );

					if ( $date_part < $today_ymd ) {
						return [
							'valid'      => false,
							'status'     => 'expired',
							'key'        => $key,
							'domain'     => $current_domain,
							'expires_at' => $formatted_date,
							'message'    => sprintf(
								/* translators: %s: expiration date */
								__( 'This annual Pro license expired on %s. Please renew your annual subscription.', 'roxytable' ),
								$formatted_date
							),
						];
					}

					return [
						'valid'      => true,
						'status'     => 'active',
						'key'        => $key,
						'domain'     => $current_domain,
						'expires_at' => $formatted_date,
						'message'    => sprintf(
							/* translators: 1: site domain, 2: expiration date */
							__( 'RoxyTable Pro license activated for %1$s! Valid through %2$s.', 'roxytable' ),
							$current_domain,
							$formatted_date
						),
					];
				}
			}
		}

		return [
			'valid'      => false,
			'status'     => 'invalid',
			'key'        => $key,
			'domain'     => $current_domain,
			'expires_at' => '',
			'message'    => __( 'Invalid license key. Please check your RoxyTable Pro single-site license key and try again.', 'roxytable' ),
		];
	}

	/**
	 * Retrieve stored license information from the database and verify it against the current site domain.
	 *
	 * @return array{status: string, key: string, domain: string, expires_at: string, activated_at: string, days_remaining: int|null}
	 */
	public static function get_license_data(): array {
		$current_domain = self::get_site_domain();
		$defaults       = [
			'status'         => 'inactive',
			'key'            => '',
			'domain'         => $current_domain,
			'expires_at'     => '',
			'activated_at'   => '',
			'days_remaining' => null,
		];

		if ( ! function_exists( 'get_option' ) ) {
			return $defaults;
		}

		$saved = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $saved ) || empty( $saved['key'] ) ) {
			return $defaults;
		}

		// Verify stored key against current_domain; if database was cloned to another domain, verification will fail with domain_mismatch.
		$verification = self::verify_key( (string) $saved['key'], $current_domain );
		$days_left    = null;

		if ( ! empty( $verification['expires_at'] ) && 'lifetime' !== $verification['expires_at'] ) {
			$exp_ts    = strtotime( $verification['expires_at'] . ' 23:59:59 UTC' );
			$now_ts    = time();
			$days_left = false !== $exp_ts ? (int) ceil( ( $exp_ts - $now_ts ) / 86400 ) : null;
		}

		$activated_at = isset( $saved['activated_at'] ) ? preg_replace( '/[^0-9\-\s:]/', '', (string) $saved['activated_at'] ) : '';

		return [
			'status'         => $verification['status'],
			'key'            => $verification['key'],
			'domain'         => $current_domain,
			'expires_at'     => $verification['expires_at'],
			'activated_at'   => (string) $activated_at,
			'days_remaining' => $days_left,
		];
	}

	/**
	 * Check whether Vendor Mode is enabled for generating client license keys.
	 *
	 * Only active when the ROXYTABLE_VENDOR_MODE constant is explicitly set to true
	 * in wp-config.php (outside the plugin directory) and the user is an administrator.
	 * This ensures clients never see or access the license key generator.
	 *
	 * @return bool True if vendor key generation UI and endpoint are allowed.
	 */
	public static function is_vendor_mode(): bool {
		return defined( 'ROXYTABLE_VENDOR_MODE' )
			&& true === ROXYTABLE_VENDOR_MODE
			&& ( ! function_exists( 'current_user_can' ) || current_user_can( 'manage_options' ) );
	}

	/**
	 * Check whether RoxyTable Pro is currently active, bound to this site, and not expired.
	 *
	 * @return bool True if Pro license is active for this site.
	 */
	public static function is_pro(): bool {
		if ( defined( 'ROXYTABLE_PRO_ACTIVE' ) ) {
			return (bool) ROXYTABLE_PRO_ACTIVE;
		}

		// In unit test environments without WordPress options table, default to true unless overridden.
		if ( ! function_exists( 'get_option' ) ) {
			return true;
		}

		$data   = self::get_license_data();
		$is_pro = ( 'active' === $data['status'] );

		if ( function_exists( 'apply_filters' ) ) {
			$is_pro = (bool) apply_filters( 'roxytable_is_pro', $is_pro, $data );
		}

		return $is_pro;
	}

	/**
	 * Activate and persist a single-site license key.
	 *
	 * @param string $raw_key Raw license key.
	 * @return array{valid: bool, status: string, key: string, domain: string, expires_at: string, message: string}
	 */
	public static function activate( string $raw_key ): array {
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
			return [
				'valid'      => false,
				'status'     => 'invalid',
				'key'        => '',
				'domain'     => self::get_site_domain(),
				'expires_at' => '',
				'message'    => __( 'You do not have permission to manage licenses.', 'roxytable' ),
			];
		}

		$result = self::verify_key( $raw_key );
		if ( $result['valid'] && function_exists( 'update_option' ) ) {
			update_option(
				self::OPTION_KEY,
				[
					'key'          => $result['key'],
					'domain'       => $result['domain'],
					'status'       => 'active',
					'expires_at'   => $result['expires_at'],
					'activated_at' => gmdate( 'Y-m-d H:i:s' ),
				],
				false
			);
		}
		return $result;
	}

	/**
	 * Deactivate the current license key.
	 */
	public static function deactivate(): void {
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( function_exists( 'delete_option' ) ) {
			delete_option( self::OPTION_KEY );
		}
	}

	/**
	 * Mask a license key for safe display in the admin UI (e.g. ROXY-PRO-20271102-9B4E1A-****82D1).
	 *
	 * @param string $key Full license key.
	 * @return string Masked key.
	 */
	public static function mask_key( string $key ): string {
		$key = self::sanitize_key_string( $key );
		if ( strlen( $key ) <= 8 ) {
			return $key;
		}
		return substr( $key, 0, -8 ) . '****' . substr( $key, -4 );
	}

	/**
	 * Enforce Free-tier constraints on a table settings array when Pro is not active.
	 *
	 * @param array $settings Table settings array.
	 * @return array Filtered table settings array.
	 */
	public static function enforce_settings_gate( array $settings ): array {
		if ( self::is_pro() ) {
			return $settings;
		}

		// Lock Pro-only templates to default or indigo.
		if ( isset( $settings['template'] ) && in_array( $settings['template'], [ 'obsidian', 'emerald' ], true ) ) {
			$settings['template'] = '';
		}

		// Lock Pro-only frontend features (including Frontend filter columns & dropdown filtering).
		$settings['sheet_filtering']   = false;
		$settings['table_filtering']   = false;
		$settings['filter_columns']    = [];
		$settings['visibility']        = false;
		$settings['export']            = false;
		$settings['server_side']       = false;
		$settings['custom_css']        = '';
		$settings['custom_js']         = '';
		$settings['custom_js_enabled'] = false;

		return $settings;
	}
}
