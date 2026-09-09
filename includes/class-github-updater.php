<?php
namespace RoxyTable\Includes;

/**
 * Connect RoxyTable to WordPress' native plugin update system.
 */
class Github_Updater {

	const REPOSITORY = 'avibecoderz/roxy-table';
	const API_URL    = 'https://api.github.com/repos/avibecoderz/roxy-table/releases/latest';

	/**
	 * Register the native WordPress update filters.
	 */
	public function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_information' ], 20, 3 );
		add_filter( 'upgrader_source_selection', [ $this, 'fix_extracted_directory' ], 10, 4 );
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

		$release = $this->get_latest_release();
		if ( ! $release || ! version_compare( $release['version'], ROXYTABLE_VERSION, '>' ) ) {
			return $transient;
		}

		$transient->response[ $this->plugin_basename() ] = (object) [
			'slug'        => 'roxytable',
			'plugin'      => $this->plugin_basename(),
			'new_version' => $release['version'],
			'url'         => $release['html_url'],
			'package'     => $release['package'],
		];

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
			'slug'           => 'roxytable',
			'version'        => $release['version'],
			'author'         => '<a href="https://abdulrahmansulaiman.netlify.app">oralbitstechnology</a>',
			'homepage'       => $release['html_url'],
			'requires'       => '7.4',
			'last_updated'   => $release['published_at'],
			'sections'       => [
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
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename() ) {
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
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			self::API_URL,
			[
				'timeout' => 10,
				'headers' => [ 'Accept' => 'application/vnd.github+json' ],
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
			// Use GitHub's stable tag archive URL instead of the API zipball redirect.
			'package'      => 'https://github.com/' . self::REPOSITORY . '/archive/refs/tags/' . rawurlencode( (string) $data['tag_name'] ) . '.zip',
			'html_url'     => ! empty( $data['html_url'] ) ? esc_url_raw( $data['html_url'] ) : 'https://github.com/' . self::REPOSITORY,
			'published_at' => ! empty( $data['published_at'] ) ? sanitize_text_field( $data['published_at'] ) : '',
			'body'         => ! empty( $data['body'] ) ? (string) $data['body'] : '',
		];
		if ( '' === $release['package'] ) {
			return false;
		}

		set_site_transient( 'roxytable_github_release', $release, 6 * HOUR_IN_SECONDS );
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
