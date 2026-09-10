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
		if ( false !== strpos( $url, 'github.com/' . self::REPOSITORY ) ) {
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

		wp_safe_redirect( add_query_arg( 'roxytable_updated_checked', '1', admin_url( 'plugins.php' ) ) );
		exit;
	}

	/**
	 * Purge local GitHub update cache.
	 */
	public function purge_cache() {
		delete_site_transient( 'roxytable_github_release' );
	}

	/**
	 * Add a GitHub release to WordPress' normal plugin update list.
	 *
	 * @param object $transient WordPress plugin update transient.
	 * @return object
	 */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$current_version = defined( 'ROXYTABLE_VERSION' ) ? ROXYTABLE_VERSION : '1.0.0';
		$release         = $this->get_latest_release();

		if ( ! $release ) {
			return $transient;
		}

		$item = (object) [
			'id'          => 'roxytable',
			'slug'        => 'roxytable',
			'plugin'      => $this->plugin_basename(),
			'new_version' => $release['version'],
			'url'         => $release['html_url'],
			'package'     => $release['package'],
		];

		if ( version_compare( $release['version'], $current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename() ] = $item;
		} else {
			$transient->no_update[ $this->plugin_basename() ] = $item;
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
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'roxytable' !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) [
			'name'          => 'RoxyTable',
			'slug'          => 'roxytable',
			'version'       => $release['version'],
			'author'        => '<a href="https://abdulrahmansulaiman.netlify.app">oralbitstechnology</a>',
			'homepage'      => $release['html_url'],
			'requires'      => '7.4',
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

		$target = trailingslashit( $remote_source ) . 'roxytable';
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
		$cached = get_site_transient( 'roxytable_github_release' );
		if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
			return $cached;
		}

		$version = defined( 'ROXYTABLE_VERSION' ) ? ROXYTABLE_VERSION : '1.0.0';
		$headers = [
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'RoxyTable-WordPress-Plugin/' . $version,
		];

		$token = defined( 'ROXYTABLE_GITHUB_TOKEN' ) ? ROXYTABLE_GITHUB_TOKEN : apply_filters( 'roxytable_github_token', '' );
		$token = sanitize_text_field( trim( (string) $token ) );
		if ( '' !== $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get(
			self::API_URL,
			[
				'timeout' => 10,
				'headers' => $headers,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$tag  = isset( $data['tag_name'] ) ? ltrim( (string) $data['tag_name'], 'vV' ) : '';
		if ( '' === $tag || ! preg_match( '/^\d+(?:\.\d+){1,3}(?:-[0-9A-Za-z.-]+)?$/', $tag ) ) {
			return false;
		}

		$release = [
			'version'      => $tag,
			'package'      => 'https://github.com/' . self::REPOSITORY . '/archive/refs/tags/' . rawurlencode( (string) $data['tag_name'] ) . '.zip',
			'html_url'     => ! empty( $data['html_url'] ) ? esc_url_raw( $data['html_url'] ) : 'https://github.com/' . self::REPOSITORY,
			'published_at' => ! empty( $data['published_at'] ) ? sanitize_text_field( $data['published_at'] ) : '',
			'body'         => ! empty( $data['body'] ) ? (string) $data['body'] : '',
		];

		if ( '' === $release['package'] ) {
			return false;
		}

		$cache_duration = defined( 'HOUR_IN_SECONDS' ) ? 6 * HOUR_IN_SECONDS : 21600;
		set_site_transient( 'roxytable_github_release', $release, $cache_duration );
		return $release;
	}

	/**
	 * Return the plugin basename used by WordPress update APIs.
	 *
	 * @return string
	 */
	private function plugin_basename() {
		return plugin_basename( ROXYTABLE_PLUGIN_FILE );
	}
}

