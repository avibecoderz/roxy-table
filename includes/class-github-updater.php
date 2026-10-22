<?php
namespace RoxyTable\Includes;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connect RoxyTable to WordPress' native plugin update system.
 */
class Github_Updater {

	const REPOSITORY = 'avibecoderz/roxy-table';
	const API_URL    = 'https://api.github.com/repos/avibecoderz/roxy-table/releases/latest';

	/**
	 * Register the native WordPress update filters and action links.
	 */
	public function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_information' ], 20, 3 );
		add_filter( 'upgrader_source_selection', [ $this, 'fix_extracted_directory' ], 10, 4 );
		add_filter( 'plugin_action_links_' . $this->plugin_basename(), [ $this, 'add_action_links' ] );
		add_filter( 'http_request_args', [ $this, 'authorize_download_request' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'handle_manual_update_check' ] );
		add_action( 'admin_notices', [ $this, 'display_update_check_notice' ] );
	}

	/**
	 * Add "Check for updates" link on the WordPress Plugins screen.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		if ( ! is_array( $links ) ) {
			$links = [];
		}

		$check_url = wp_nonce_url(
			add_query_arg( 'roxytable_check_update', '1', admin_url( 'plugins.php' ) ),
			'roxytable_check_update_nonce'
		);

		$links['check_update'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $check_url ),
			esc_html__( 'Check for updates', 'roxytable' )
		);

		return $links;
	}

	/**
	 * Display visual admin notice after manual update check.
	 */
	public function display_update_check_notice() {
		if ( ! isset( $_GET['roxytable_updated_checked'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$release         = $this->get_latest_release();
		$current_version = defined( 'ROXYTABLE_VERSION' ) ? ROXYTABLE_VERSION : '1.0.0';

		if ( $release && version_compare( $release['version'], $current_version, '>' ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><strong>RoxyTable:</strong> <?php printf( esc_html__( 'A new version (%s) is available! You can update directly from the list below.', 'roxytable' ), esc_html( $release['version'] ) ); ?></p>
			</div>
			<?php
		} else {
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong>RoxyTable:</strong> <?php printf( esc_html__( 'Update check complete. You are currently running the latest version (%s).', 'roxytable' ), esc_html( $current_version ) ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Inject authorization header into package zip download request for private repositories.
	 *
	 * @param array  $args Request arguments.
	 * @param string $url  Request URL.
	 * @return array
	 */
	public function authorize_download_request( $args, $url ) {
		if ( false !== strpos( $url, 'github.com/' . self::REPOSITORY ) || false !== strpos( $url, 'codeload.github.com/' . self::REPOSITORY ) ) {
			$token = defined( 'ROXYTABLE_GITHUB_TOKEN' ) ? ROXYTABLE_GITHUB_TOKEN : apply_filters( 'roxytable_github_token', '' );
			$token = sanitize_text_field( trim( (string) $token ) );

			if ( '' !== $token ) {
				if ( ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
					$args['headers'] = [];
				}
				$args['headers']['Authorization'] = 'Bearer ' . $token;
			}
		}
		return $args;
	}

	/**
	 * Handle manual update check request triggered by admin user.
	 */
	public function handle_manual_update_check() {
		if ( ! isset( $_GET['roxytable_check_update'] ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'roxytable_check_update_nonce' );

		$this->purge_cache();

		if ( function_exists( 'delete_site_transient' ) ) {
			delete_site_transient( 'update_plugins' );
		}
		if ( function_exists( 'wp_clean_plugins_cache' ) ) {
			wp_clean_plugins_cache();
		}

		wp_safe_redirect( add_query_arg( 'roxytable_updated_checked', '1', admin_url( 'plugins.php' ) ) );
		exit;
	}

	/**
	 * Purge local GitHub update cache.
	 */
	public function purge_cache() {
		delete_site_transient( 'roxytable_github_release' );  // legacy key — clear it too
		delete_site_transient( 'roxytable_release_v2' );
	}

	/**
	 * Add a GitHub release to WordPress' normal plugin update list.
	 *
	 * @param object $transient WordPress plugin update transient.
	 * @return object
	 */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}

		$current_version = defined( 'ROXYTABLE_VERSION' ) ? ROXYTABLE_VERSION : '1.0.0';
		if ( ! empty( $transient->checked[ $this->plugin_basename() ] ) ) {
			$current_version = $transient->checked[ $this->plugin_basename() ];
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$plugin_dir = dirname( $this->plugin_basename() );
		$slug       = ( ! empty( $plugin_dir ) && '.' !== $plugin_dir ) ? $plugin_dir : 'roxytable';

		$wp_version = $this->get_wordpress_version();

		$item = (object) [
			'id'            => 'roxytable',
			'slug'          => $slug,
			'plugin'        => $this->plugin_basename(),
			'new_version'   => $release['version'],
			'url'           => $release['html_url'],
			'package'       => $release['package'],
			'requires'      => $wp_version,
			'requires_php'  => '6.0',
			'tested'        => $wp_version,
			'compatibility' => new \stdClass(),
		];

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = [];
		}
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = [];
		}

		if ( version_compare( $release['version'], $current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename() ] = $item;
			unset( $transient->no_update[ $this->plugin_basename() ] );
		} else {
			$transient->no_update[ $this->plugin_basename() ] = $item;
			unset( $transient->response[ $this->plugin_basename() ] );
		}

		return $transient;
	}

	/**
	 * Provide release details for the "View version details" link.
	 *
	 * @param false|object $result Existing plugin information result.
	 * @param string       $action API action.
	 * @param object       $args API request arguments.
	 * @return false|object
	 */
	public function plugin_information( $result, $action, $args ) {
		$plugin_dir  = dirname( $this->plugin_basename() );
		$valid_slugs = [ 'roxytable', 'roxy-table' ];
		if ( ! empty( $plugin_dir ) && '.' !== $plugin_dir ) {
			$valid_slugs[] = $plugin_dir;
		}

		if ( 'plugin_information' !== $action || empty( $args->slug ) || ! in_array( $args->slug, $valid_slugs, true ) ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$wp_version = $this->get_wordpress_version();

		return (object) [
			'name'          => 'RoxyTable',
			'slug'          => $args->slug,
			'version'       => $release['version'],
			'author'        => '<a href="https://abdulrahmansulaiman.netlify.app">oralbitstechnology</a>',
			'homepage'      => $release['html_url'],
			'requires'      => $wp_version,
			'requires_php'  => '6.0',
			'tested'        => $wp_version,
			'last_updated'  => $release['published_at'],
			'sections'      => [
				'description' => 'Import Excel and CSV files and display them as searchable, sortable, paginated tables.',
				'changelog'   => nl2br( esc_html( $release['body'] ) ),
			],
			'download_link' => $release['package'],
		];
	}

	/**
	 * Rename GitHub's extracted archive directory to the installed plugin slug.
	 *
	 * @param string $source Extracted source directory.
	 * @param string $remote_source Temporary remote source directory.
	 * @param object $upgrader WordPress upgrader instance.
	 * @param array  $hook_extra Upgrader context.
	 * @return string|\WP_Error
	 */
	public function fix_extracted_directory( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['plugin'] ) || $this->plugin_basename() !== $hook_extra['plugin'] ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			return new \WP_Error( 'roxytable_update_filesystem', __( 'Could not access the WordPress filesystem for the RoxyTable update.', 'roxytable' ) );
		}

		$plugin_dir  = dirname( $hook_extra['plugin'] );
		$target_name = ( ! empty( $plugin_dir ) && '.' !== $plugin_dir ) ? $plugin_dir : dirname( $this->plugin_basename() );
		if ( empty( $target_name ) || '.' === $target_name ) {
			$target_name = 'roxy-table';
		}

		$target = trailingslashit( $remote_source ) . $target_name;
		if ( trailingslashit( $source ) === trailingslashit( $target ) ) {
			return $source;
		}

		if ( $wp_filesystem->exists( $target ) ) {
			$wp_filesystem->delete( $target, true );
		}

		if ( ! $wp_filesystem->move( $source, $target, true ) ) {
			return new \WP_Error( 'roxytable_update_directory', __( 'Could not prepare the RoxyTable update directory.', 'roxytable' ) );
		}

		return $target;
	}

	/**
	 * Get and cache the latest published GitHub release.
	 *
	 * A failed request returns false so a temporary GitHub outage cannot break
	 * the plugin or WordPress admin pages.
	 *
	 * @return array|false
	 */
	private function get_latest_release() {
		$version = defined( 'ROXYTABLE_VERSION' ) ? ROXYTABLE_VERSION : '1.0.0';

		$cached = get_site_transient( 'roxytable_release_v2' );
		if ( is_array( $cached ) && ! empty( $cached['version'] ) && version_compare( $cached['version'], $version, '>' ) ) {
			return $cached;
		}

		$headers = [
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'RoxyTable-WordPress-Plugin/' . $version,
		];

		$token = defined( 'ROXYTABLE_GITHUB_TOKEN' ) ? ROXYTABLE_GITHUB_TOKEN : apply_filters( 'roxytable_github_token', '' );
		$token = sanitize_text_field( trim( (string) $token ) );
		if ( '' !== $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		// First, try GitHub's /releases/latest endpoint.
		$response = wp_remote_get(
			self::API_URL,
			[
				'timeout' => 10,
				'headers' => $headers,
			]
		);

		$release = false;
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data    = json_decode( wp_remote_retrieve_body( $response ), true );
			$release = $this->parse_release_data( $data );
		}

		// If /releases/latest returned no valid release, or a version not newer than the current version,
		// query the full /releases list to find the highest non-draft, non-prerelease version.
		if ( ! $release || version_compare( $release['version'], $version, '<=' ) ) {
			$releases_response = wp_remote_get(
				'https://api.github.com/repos/' . self::REPOSITORY . '/releases',
				[
					'timeout' => 10,
					'headers' => $headers,
				]
			);

			if ( ! is_wp_error( $releases_response ) && 200 === wp_remote_retrieve_response_code( $releases_response ) ) {
				$releases_data = json_decode( wp_remote_retrieve_body( $releases_response ), true );
				if ( is_array( $releases_data ) ) {
					$highest_release = null;
					foreach ( $releases_data as $item ) {
						if ( ! empty( $item['draft'] ) || ! empty( $item['prerelease'] ) ) {
							continue;
						}
						$parsed = $this->parse_release_data( $item );
						if ( ! $parsed ) {
							continue;
						}
						if ( null === $highest_release || version_compare( $parsed['version'], $highest_release['version'], '>' ) ) {
							$highest_release = $parsed;
						}
					}
					if ( $highest_release ) {
						$release = $highest_release;
					}
				}
			}
		}

		if ( ! $release ) {
			return false;
		}

		$cache_duration = defined( 'HOUR_IN_SECONDS' ) ? 6 * HOUR_IN_SECONDS : 21600;
		set_site_transient( 'roxytable_release_v2', $release, $cache_duration );
		return $release;
	}

	/**
	 * Parse raw GitHub release item into normalized release structure.
	 *
	 * @param mixed $data Raw release data from GitHub API.
	 * @return array|false
	 */
	private function parse_release_data( $data ) {
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return false;
		}

		$tag = ltrim( (string) $data['tag_name'], 'vV' );
		if ( '' === $tag || ! preg_match( '/^\d+(?:\.\d+){1,3}(?:-[0-9A-Za-z.-]+)?$/', $tag ) ) {
			return false;
		}

		$package_url = 'https://github.com/' . self::REPOSITORY . '/archive/refs/tags/' . rawurlencode( (string) $data['tag_name'] ) . '.zip';

		return [
			'version'      => $tag,
			'package'      => $package_url,
			'html_url'     => ! empty( $data['html_url'] ) ? esc_url_raw( $data['html_url'] ) : 'https://github.com/' . self::REPOSITORY,
			'published_at' => ! empty( $data['published_at'] ) ? sanitize_text_field( $data['published_at'] ) : '',
			'body'         => ! empty( $data['body'] ) ? (string) $data['body'] : '',
		];
	}

	/**
	 * Return the plugin basename used by WordPress update APIs.
	 *
	 * @return string
	 */
	private function plugin_basename() {
		return plugin_basename( ROXYTABLE_PLUGIN_FILE );
	}

	/**
	 * Retrieve the currently installed WordPress version dynamically.
	 *
	 * Uses WordPress core's built-in get_bloginfo('version') function
	 * with graceful fallback to the $wp_version global.
	 *
	 * @return string Current WordPress version.
	 */
	public function get_wordpress_version() {
		if ( function_exists( 'get_bloginfo' ) ) {
			$version = get_bloginfo( 'version' );
			if ( ! empty( $version ) && is_string( $version ) ) {
				$clean = preg_replace( '/-[0-9A-Za-z.-]+$/', '', trim( $version ) );
				if ( ! empty( $clean ) ) {
					return $clean;
				}
				return trim( $version );
			}
		}

		global $wp_version;
		if ( ! empty( $wp_version ) && is_string( $wp_version ) ) {
			$clean = preg_replace( '/-[0-9A-Za-z.-]+$/', '', trim( $wp_version ) );
			return ! empty( $clean ) ? $clean : trim( $wp_version );
		}

		return '5.6';
	}

	/**
	 * Validate compatibility with the current WordPress environment.
	 *
	 * @param string|null $required_wp Optional required WP version.
	 * @return bool True if compatible.
	 */
	public function is_wordpress_compatible( $required_wp = null ) {
		$current_wp = $this->get_wordpress_version();
		if ( empty( $required_wp ) ) {
			return true;
		}
		return version_compare( $current_wp, $required_wp, '>=' );
	}
}

