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

		// Register AJAX endpoints for client-side AJAX fallback
		add_action( 'wp_ajax_roxytable_filter_rows', [ $this, 'ajax_filter_rows' ] );
		add_action( 'wp_ajax_nopriv_roxytable_filter_rows', [ $this, 'ajax_filter_rows' ] );
	}

	public function register_shortcode() {
		add_shortcode( 'roxytable', [ $this, 'shortcode_handler' ] );
	}

	public function enqueue_assets() {
		$frontend_css = ROXYTABLE_PATH . 'assets/css/admin.css';
		$frontend_js  = ROXYTABLE_PATH . 'assets/js/frontend.js';
		wp_enqueue_style( 'roxytable-frontend-css', ROXYTABLE_URL . 'assets/css/admin.css', [], file_exists( $frontend_css ) ? filemtime( $frontend_css ) : ROXYTABLE_VERSION );
		wp_enqueue_script( 'roxytable-frontend-js', ROXYTABLE_URL . 'assets/js/frontend.js', [], file_exists( $frontend_js ) ? filemtime( $frontend_js ) : ROXYTABLE_VERSION, true );

		wp_localize_script( 'roxytable-frontend-js', 'roxyTableData', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'roxytable_filter_nonce' ),
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
	}

	/**
	 * AJAX endpoint for server-side / large dataset filtering.
	 */
	public function ajax_filter_rows() {
		check_ajax_referer( 'roxytable_filter_nonce', 'nonce' );

		$table_id = isset( $_POST['table_id'] ) ? absint( $_POST['table_id'] ) : 0;
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
			'limit'       => isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : -1,
			'offset'      => isset( $_POST['offset'] ) ? max( 0, intval( $_POST['offset'] ) ) : 0,
			'search'      => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'sort_col'    => isset( $_POST['sort_col'] ) ? intval( $_POST['sort_col'] ) : -1,
			'sort_dir'    => isset( $_POST['sort_dir'] ) ? sanitize_key( $_POST['sort_dir'] ) : 'asc',
			'rule_match'  => isset( $_POST['rule_match'] ) && strtolower( $_POST['rule_match'] ) === 'or' ? 'or' : 'and',
			'categorical' => [],
			'range'       => [],
			'sheet_rules' => [],
		];

		if ( ! empty( $_POST['categorical'] ) && is_array( $_POST['categorical'] ) ) {
			foreach ( $_POST['categorical'] as $col_i => $vals ) {
				$clean_col = intval( $col_i );
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
						'min' => isset( $bounds['min'] ) && '' !== $bounds['min'] ? floatval( $bounds['min'] ) : '',
						'max' => isset( $bounds['max'] ) && '' !== $bounds['max'] ? floatval( $bounds['max'] ) : '',
					];
				}
			}
		}

		if ( ! empty( $_POST['sheet_rules'] ) && is_array( $_POST['sheet_rules'] ) ) {
			foreach ( $_POST['sheet_rules'] as $rule ) {
				if ( is_array( $rule ) && isset( $rule['col'], $rule['op'] ) ) {
					$args['sheet_rules'][] = [
						'col'  => intval( $rule['col'] ),
						'op'   => sanitize_key( $rule['op'] ),
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
	 * @param array $atts
	 * @return string HTML
	 */
	public function shortcode_handler( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'roxytable' );
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

		$html = '<div class="roxytable-embed' . esc_attr( $template_class ) . '"'
			. ' data-table-id="' . esc_attr( $table_id ) . '"'
			. ' data-total-rows="' . esc_attr( $total_rows ) . '"'
			. ' data-nonce="' . esc_attr( wp_create_nonce( 'roxytable_filter_nonce' ) ) . '"'
			. ' data-stats="' . esc_attr( wp_json_encode( $stats ) ) . '"'
			. ' data-sheets="' . esc_attr( wp_json_encode( $sheets ) ) . '"'
			. ' data-settings="' . esc_attr( wp_json_encode( $settings ) ) . '"'
			. ' style="max-width:100%;overflow-x:auto;">';

		if ( ! empty( $settings['custom_css'] ) ) {
			$html .= '<style>' . wp_strip_all_tags( $settings['custom_css'] ) . '</style>';
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
	 * Render a structurally consistent table body from stored row data.
	 *
	 * Merged spreadsheet placeholders are rendered as empty cells so every row
	 * retains one cell per column when frontend filtering removes other rows.
	 *
	 * @param array $rows
	 * @param array $columns
	 * @return string HTML <tbody> content.
	 */
	public static function render_tbody_html( array $rows, array $columns ) : string {
		$matrix = [];
		foreach ( $rows as $r ) {
			$matrix[] = $r['cells'] ?? [];
		}

		$rowCount = count( $matrix );
		$colCount = count( $columns );
		$html = '<tbody>';

		for ( $r = 0; $r < $rowCount; $r++ ) {
			$html .= '<tr style="border-bottom:1px solid #f1f5f9;">';
			for ( $c = 0; $c < $colCount; $c++ ) {
				$cellValue = (string) ( $matrix[ $r ][ $c ] ?? '' );

				if ( '#rowspan#' === $cellValue || '#colspan#' === $cellValue ) {
					$cellValue = '';
				}

				$label = $columns[ $c ]['name'] ?? '';
				$html .= '<td data-label="' . esc_attr( $label ) . '" style="padding:8px;">' . wp_kses_post( $cellValue ) . '</td>';
			}
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		return $html;
	}
}

return new Frontend();
