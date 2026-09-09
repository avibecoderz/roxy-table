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
    }

    public function register_shortcode() {
        add_shortcode( 'roxytable', [ $this, 'shortcode_handler' ] );
    }

    public function enqueue_assets() {
        // Lightweight: reuse admin CSS for basic table styling so shortcode renders nicely.
        wp_enqueue_style( 'roxytable-frontend-css', ROXYTABLE_URL . 'assets/css/admin.css', [], ROXYTABLE_VERSION );
        wp_enqueue_script( 'roxytable-frontend-js', ROXYTABLE_URL . 'assets/js/frontend.js', [], ROXYTABLE_VERSION, true );
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

        // Only show published tables to regular visitors. Allow administrators
        // to see a helpful notice when previewing a draft table.
        if ( empty( $table['status'] ) || 'published' !== $table['status'] ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<div class="roxytable-embed-error">' . esc_html__( 'This table is not published (preview only for administrators).', 'roxytable' ) . '</div>';
            }
            // Silent for public users — do not render draft tables.
            return '';
        }

        $columns = Table_Repository::get_columns( $table_id );
        $rows = Table_Repository::get_rows( $table_id, 50, 0 );

        // Build a minimal HTML table.
        $html = '<div class="roxytable-embed">';
        $html .= '<table class="roxytable-table" style="width:100%;border-collapse:collapse;">';
        $html .= '<thead><tr>';
        foreach ( $columns as $col ) {
            $html .= '<th style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:left;">' . esc_html( $col['name'] ) . '</th>';
        }
        $html .= '</tr></thead>';

        $html .= '<tbody>';
        foreach ( $rows as $r ) {
            $html .= '<tr style="border-bottom:1px solid #f1f5f9;">';
            $cells = $r['cells'] ?? [];
            foreach ( $cells as $cell ) {
                $html .= '<td style="padding:8px;">' . esc_html( $cell ) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}

return new Frontend();
