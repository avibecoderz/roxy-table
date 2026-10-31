<?php
namespace RoxyTable\Includes;

use RoxyTable\Includes\Admin\Table_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {

	public function __construct() {
		add_action( 'init', [ $this, 'register_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Register AJAX endpoints for client-side AJAX fallback.
		add_action( 'wp_ajax_roxytable_filter_rows', [ $this, 'ajax_filter_rows' ] );
		add_action( 'wp_ajax_nopriv_roxytable_filter_rows', [ $this, 'ajax_filter_rows' ] );
	}

	public function register_shortcode() {
		add_shortcode( 'roxytable', [ $this, 'shortcode_handler' ] );
	}

	/**
	 * Register frontend assets and enqueue them only when a page displays a RoxyTable.
	 */
	public function enqueue_assets() {
		$frontend_css = ROXYTABLE_PATH . 'assets/css/admin.css';
		$frontend_js  = ROXYTABLE_PATH . 'assets/js/frontend.js';

		wp_register_style(
			'roxytable-frontend-css',
			ROXYTABLE_URL . 'assets/css/admin.css',
			[],
			file_exists( $frontend_css ) ? filemtime( $frontend_css ) : ROXYTABLE_VERSION
		);
		wp_register_script(
			'roxytable-frontend-js',
			ROXYTABLE_URL . 'assets/js/frontend.js',
			[],
			file_exists( $frontend_js ) ? filemtime( $frontend_js ) : ROXYTABLE_VERSION,
			true
		);

		wp_localize_script( 'roxytable-frontend-js', 'roxyTableData', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'roxytable_filter_nonce' ),
			'is_admin' => is_admin(),
			'i18n'     => [
				'search'       => __( 'Search...', 'roxytable' ),
				'all'          => __( 'All', 'roxytable' ),
				'add_rule'     => __( '+ Add Rule', 'roxytable' ),
				'sheet_filter' => __( 'Sheet Filter Rules', 'roxytable' ),
				'reset'        => __( 'Reset All', 'roxytable' ),
				'export'       => __( 'Export CSV', 'roxytable' ),
				'loading'      => __( 'Loading...', 'roxytable' ),
			],
		] );

		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( (string) $post->post_content, 'roxytable' ) ) {
			wp_enqueue_style( 'roxytable-frontend-css' );
			wp_enqueue_script( 'roxytable-frontend-js' );
		}
	}

	/**
	 * AJAX endpoint for server-side / large dataset filtering.
	 */
	public function ajax_filter_rows() {
		check_ajax_referer( 'roxytable_filter_nonce', 'nonce' );

		$table_id = isset( $_POST['table_id'] ) ? absint( wp_unslash( $_POST['table_id'] ) ) : 0;
		if ( $table_id < 1 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid table ID.', 'roxytable' ) ] );
		}

		$table = Table_Repository::get_table( $table_id );
		if ( ! $table ) {
			wp_send_json_error( [ 'message' => __( 'Table not found.', 'roxytable' ) ] );
		}

		if ( empty( $table['status'] ) || 'published' !== $table['status'] ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Access denied.', 'roxytable' ) ] );
			}
		}

		$args = [
			'limit'       => isset( $_POST['limit'] ) ? intval( wp_unslash( $_POST['limit'] ) ) : -1,
			'offset'      => isset( $_POST['offset'] ) ? max( 0, intval( wp_unslash( $_POST['offset'] ) ) ) : 0,
			'search'      => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'sort_col'    => isset( $_POST['sort_col'] ) ? intval( wp_unslash( $_POST['sort_col'] ) ) : -1,
			'sort_dir'    => isset( $_POST['sort_dir'] ) ? sanitize_key( wp_unslash( $_POST['sort_dir'] ) ) : 'asc',
			'rule_match'  => isset( $_POST['rule_match'] ) && 'or' === strtolower( sanitize_text_field( wp_unslash( $_POST['rule_match'] ) ) ) ? 'or' : 'and',
			'categorical' => [],
			'range'       => [],
			'sheet_rules' => [],
		];

		if ( ! empty( $_POST['categorical'] ) && is_array( $_POST['categorical'] ) ) {
			foreach ( $_POST['categorical'] as $col_i => $vals ) {
				$clean_col  = intval( $col_i );
				$clean_vals = is_array( $vals )
					? array_map( 'sanitize_text_field', wp_unslash( $vals ) )
					: [ sanitize_text_field( wp_unslash( $vals ) ) ];
				$args['categorical'][ $clean_col ] = array_filter( $clean_vals, static fn( $v ) => '' !== $v );
			}
		}

		if ( ! empty( $_POST['range'] ) && is_array( $_POST['range'] ) ) {
			foreach ( $_POST['range'] as $col_i => $bounds ) {
				$clean_col = intval( $col_i );
				if ( is_array( $bounds ) ) {
					$args['range'][ $clean_col ] = [
						'min' => isset( $bounds['min'] ) && '' !== $bounds['min'] ? floatval( wp_unslash( $bounds['min'] ) ) : '',
						'max' => isset( $bounds['max'] ) && '' !== $bounds['max'] ? floatval( wp_unslash( $bounds['max'] ) ) : '',
					];
				}
			}
		}

		if ( ! empty( $_POST['sheet_rules'] ) && is_array( $_POST['sheet_rules'] ) ) {
			foreach ( $_POST['sheet_rules'] as $rule ) {
				if ( is_array( $rule ) && isset( $rule['col'], $rule['op'] ) ) {
					$args['sheet_rules'][] = [
						'col'  => intval( $rule['col'] ),
						'op'   => sanitize_key( wp_unslash( $rule['op'] ) ),
						'val'  => isset( $rule['val'] ) ? sanitize_text_field( wp_unslash( $rule['val'] ) ) : '',
						'val2' => isset( $rule['val2'] ) ? sanitize_text_field( wp_unslash( $rule['val2'] ) ) : '',
					];
				}
			}
		}

		$result = Table_Repository::get_filtered_rows( $table_id, $args );

		wp_send_json_success( $result );
	}

	/**
	 * Shortcode handler: [roxytable id="123"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_handler( $atts ) {
		$atts     = shortcode_atts( [ 'id' => 0 ], $atts, 'roxytable' );
		$table_id = intval( $atts['id'] );
		if ( $table_id < 1 ) {
			return '<div class="roxytable-embed-error">' . esc_html__( 'Invalid table ID.', 'roxytable' ) . '</div>';
		}

		$table = Table_Repository::get_table( $table_id );
		if ( ! $table ) {
			return '<div class="roxytable-embed-error">' . esc_html__( 'Table not found.', 'roxytable' ) . '</div>';
		}

		if ( empty( $table['status'] ) || 'published' !== $table['status'] ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<div class="roxytable-embed-error">' . esc_html__( 'This table is not published (preview only for administrators).', 'roxytable' ) . '</div>';
			}
			return '';
		}

		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style( 'roxytable-frontend-css' );
		}
		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script( 'roxytable-frontend-js' );
		}

		$columns    = Table_Repository::get_columns( $table_id );
		$total_rows = Table_Repository::count_rows( $table_id );
		$rows       = Table_Repository::get_rows( $table_id, max( 1, $total_rows ), 0 );
		$stats      = Table_Repository::get_column_statistics( $table_id );

		$settings = is_array( $table['settings'] ?? null )
			? $table['settings']
			: ( json_decode( (string) ( $table['settings'] ?? '' ), true ) ?: [] );
		$template       = ! empty( $settings['template'] ) ? sanitize_html_class( $settings['template'] ) : '';
		$template_class = $template ? ' roxytable-template-' . $template : '';

		$sheets = isset( $settings['sheets'] ) && is_array( $settings['sheets'] ) ? $settings['sheets'] : [];

		// Prepare settings payload for frontend consumption; strip custom_js in admin or when disabled.
		$frontend_settings = $settings;
		$js_enabled        = ! is_admin() && ( ! isset( $settings['custom_js_enabled'] ) || ! empty( $settings['custom_js_enabled'] ) ) && ! empty( $settings['custom_js'] );
		if ( $js_enabled ) {
			$frontend_settings['custom_js_enabled'] = true;
			$frontend_settings['custom_js']         = self::sanitize_custom_js( (string) $settings['custom_js'] );
		} else {
			$frontend_settings['custom_js_enabled'] = false;
			unset( $frontend_settings['custom_js'] );
		}

		$html = '<div class="roxytable-embed' . esc_attr( $template_class ) . '"'
			. ' data-table-id="' . esc_attr( $table_id ) . '"'
			. ' data-total-rows="' . esc_attr( $total_rows ) . '"'
			. ' data-nonce="' . esc_attr( wp_create_nonce( 'roxytable_filter_nonce' ) ) . '"'
			. ' data-stats="' . esc_attr( wp_json_encode( $stats ) ) . '"'
			. ' data-sheets="' . esc_attr( wp_json_encode( $sheets ) ) . '"'
			. ' data-settings="' . esc_attr( wp_json_encode( $frontend_settings ) ) . '"'
			. ' style="max-width:100%;overflow-x:auto;">';

		if ( ! empty( $settings['custom_css'] ) ) {
			$scoped_css = self::format_scoped_css( $table_id, (string) $settings['custom_css'] );
			if ( '' !== $scoped_css ) {
				$html .= '<style class="roxytable-custom-css" data-table-id="' . esc_attr( $table_id ) . '">' . $scoped_css . '</style>';
			}
		}

		$html .= '<table class="roxytable-table" style="width:100%;border-collapse:collapse;">';
		$html .= '<thead><tr>';
		foreach ( $columns as $col ) {
			$html .= '<th style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:left;">' . esc_html( $col['name'] ) . '</th>';
		}
		$html .= '</tr></thead>';

		$html .= self::render_tbody_html( $rows, $columns );

		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Sanitize raw custom JavaScript while preserving valid JS syntax.
	 *
	 * Strips null bytes and non-printable control characters, removes outer `<script>` wrapper tags
	 * if accidentally pasted by the user, and neutralizes `</script>` tag breakout sequences.
	 *
	 * @param string $raw_js Raw user-entered JavaScript string.
	 * @return string Sanitized JavaScript string.
	 */
	public static function sanitize_custom_js( string $raw_js ) : string {
		$js = function_exists( 'wp_unslash' ) ? wp_unslash( $raw_js ) : stripslashes( $raw_js );

		// Remove null bytes and non-printable control characters (except tab, LF, CR).
		$js = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', (string) $js );

		// Strip <script> and </script> wrapper tags if the user included them.
		$js = preg_replace( '#<\s*/?\s*script\b[^>]*>#is', '', (string) $js );

		// Prevent premature closing of any surrounding <script> context.
		$js = preg_replace( '#<\s*/\s*script#i', '<\\/script', (string) $js );

		return trim( (string) $js );
	}

	/**
	 * Sanitize raw custom CSS while preserving valid CSS selectors and combinators.
	 *
	 * Strips control characters, HTML/style/script breakout vectors, `@import` rules,
	 * and dangerous CSS declarations (`expression()`, `javascript:`, `vbscript:`, `-moz-binding`, `behavior:`).
	 *
	 * @param string $raw_css Raw user-entered CSS string.
	 * @return string Sanitized CSS string.
	 */
	public static function sanitize_custom_css( string $raw_css ) : string {
		$css = function_exists( 'wp_unslash' ) ? wp_unslash( $raw_css ) : stripslashes( $raw_css );

		// Remove null bytes and non-printable control characters (except tab, LF, CR).
		$css = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', (string) $css );

		// Strip any <style>...</style> or <script>...</script> blocks and remove all '<' characters to prevent </style> tag breakout.
		$css = preg_replace( '#<\s*(?:style|script)\b[^>]*>.*?<\s*/\s*(?:style|script)\s*>#is', '', (string) $css );
		$css = preg_replace( '#<\s*/?\s*(?:style|script)[^>]*>#i', '', (string) $css );
		$css = str_replace( '<', '', (string) $css );

		// Strip external @import rules and drop any declaration containing legacy/dangerous CSS execution vectors.
		$css = preg_replace( '/@import\b[^;]*;?/i', '', (string) $css );
		$css = preg_replace( '/[a-zA-Z\-]+\s*:[^;\{\}]*(?:expression\s*\(|javascript\s*:|vbscript\s*:|data\s*:\s*text\/html|-moz-binding|behavior\s*:)[^;\{\}]*;?/i', '', (string) $css );

		return trim( (string) $css );
	}

	/**
	 * Format and scope custom CSS rules to a specific table instance.
	 *
	 * @param int    $table_id Table ID to scope rules to.
	 * @param string $raw_css  Raw or stored custom CSS string.
	 * @return string Scoped, sanitized CSS ready for `<style>` output.
	 */
	public static function format_scoped_css( int $table_id, string $raw_css ) : string {
		$css       = self::sanitize_custom_css( $raw_css );
		$clean_css = trim( (string) preg_replace( '#/\*.*?\*/#s', '', $css ) );
		if ( '' === $clean_css || $table_id < 1 ) {
			return '';
		}

		$scope = '.roxytable-embed[data-table-id="' . absint( $table_id ) . '"]';

		$format_declarations = static function ( string $declarations ) : string {
			$parts = explode( ';', $declarations );
			$out   = [];
			foreach ( $parts as $part ) {
				$part = trim( $part );
				if ( '' === $part || false === strpos( $part, ':' ) ) {
					continue;
				}
				list( $prop, $val ) = array_map( 'trim', explode( ':', $part, 2 ) );
				if ( '' === $prop || '' === $val ) {
					continue;
				}
				// Allow only standard CSS property names or CSS custom properties (--var).
				if ( ! preg_match( '/^(?:--[a-zA-Z0-9\-_]+|[a-zA-Z\-]+)$/', $prop ) ) {
					continue;
				}
				// Reject values containing braces, angle brackets, unbalanced parens, or dangerous tokens.
				if (
					substr_count( $val, '(' ) !== substr_count( $val, ')' ) ||
					preg_match( '/[<>\{\}]|(?:expression\s*\(|javascript\s*:|vbscript\s*:|data\s*:\s*text\/html|-moz-binding|behavior\s*:)/i', $val )
				) {
					continue;
				}
				$decl = $prop . ': ' . $val;
				if ( false === stripos( $val, '!important' ) ) {
					$decl .= ' !important';
				}
				$out[] = $decl;
			}
			return ! empty( $out ) ? implode( '; ', $out ) . ';' : '';
		};

		// If the user provided bare CSS declarations without selector braces, scope to the table.
		if ( false === strpos( $clean_css, '{' ) ) {
			$formatted = $format_declarations( $clean_css );
			return '' !== $formatted ? $scope . ' .roxytable-table { ' . $formatted . ' }' : '';
		}

		$scoped = preg_replace_callback(
			'/([^{}@]+)\{([^{}]*)\}/s',
			static function ( array $matches ) use ( $scope, $format_declarations ) : string {
				$raw_selectors = explode( ',', $matches[1] );
				$selectors     = [];
				foreach ( $raw_selectors as $sel ) {
					$sel = trim( $sel );
					if ( '' === $sel ) {
						continue;
					}
					// Allow only safe CSS selector characters.
					if ( ! preg_match( '/^[a-zA-Z0-9_\-\.\#\:\s\>\+\~\*\[\]\=\"\'\(\)\^\|\/\$]+$/', $sel ) ) {
						continue;
					}
					if ( 0 === strpos( $sel, '.roxytable-embed[data-table-id' ) ) {
						$selectors[] = $sel;
					} elseif (
						'.roxytable-embed' === $sel ||
						0 === strpos( $sel, '.roxytable-embed ' ) ||
						0 === strpos( $sel, '.roxytable-embed:' ) ||
						0 === strpos( $sel, '.roxytable-embed.' )
					) {
						$selectors[] = $scope . substr( $sel, strlen( '.roxytable-embed' ) );
					} else {
						$selectors[] = $scope . ' ' . $sel;
					}
				}
				$formatted_decls = $format_declarations( $matches[2] );
				if ( empty( $selectors ) || '' === $formatted_decls ) {
					return '';
				}
				return implode( ', ', $selectors ) . ' { ' . $formatted_decls . ' }';
			},
			$clean_css
		);

		return trim( (string) $scoped );
	}

	/**
	 * Render a structurally consistent table body from stored row data.
	 *
	 * Merged spreadsheet placeholders are rendered as empty cells so every row
	 * retains one cell per column when frontend filtering removes other rows.
	 *
	 * @param array $rows    Stored table rows.
	 * @param array $columns Stored table columns.
	 * @return string HTML <tbody> content.
	 */
	public static function render_tbody_html( array $rows, array $columns ) : string {
		$matrix = [];
		foreach ( $rows as $r ) {
			$matrix[] = $r['cells'] ?? [];
		}

		$row_count = count( $matrix );
		$col_count = count( $columns );
		$html      = '<tbody>';

		for ( $r = 0; $r < $row_count; $r++ ) {
			$html .= '<tr style="border-bottom:1px solid #f1f5f9;">';
			for ( $c = 0; $c < $col_count; $c++ ) {
				$cell_value = (string) ( $matrix[ $r ][ $c ] ?? '' );

				if ( '#rowspan#' === $cell_value || '#colspan#' === $cell_value ) {
					$cell_value = '';
				}

				$label = $columns[ $c ]['name'] ?? '';
				$html .= '<td data-label="' . esc_attr( $label ) . '" style="padding:8px;">' . wp_kses_post( $cell_value ) . '</td>';
			}
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		return $html;
	}
}

return new Frontend();
