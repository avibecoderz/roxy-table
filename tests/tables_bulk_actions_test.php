<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        return trim( strtolower( str_replace( [ '-', '_' ], '', (string) $key ) ) );
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $value ) {
        return max( 0, intval( $value ) );
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type ) {
        return '2024-01-01 00:00:00';
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) {
        return trim( (string) $value );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $value ) {
        return trim( (string) $value );
    }
}

if ( ! function_exists( 'sanitize_title' ) ) {
    function sanitize_title( $value ) {
        return strtolower( preg_replace( '/[^a-z0-9]+/', '-', (string) $value ) );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $value ) {
        return json_encode( $value );
    }
}

if ( ! function_exists( 'wp_json_decode' ) ) {
    function wp_json_decode( $value, $assoc = false ) {
        return json_decode( $value, $assoc );
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return false;
    }
}

if ( ! function_exists( 'esc_sql' ) ) {
    function esc_sql( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'addslashes_gpc' ) ) {
    function addslashes_gpc( $value ) {
        return $value;
    }
}

if ( ! isset( $wpdb ) ) {
    $wpdb = new class {
        public $prefix = 'wp_';
        public $last_error = '';

        public function prepare( $query, $args = [] ) {
            return $query;
        }

        public function insert( $table, $data, $format = null ) {
            return true;
        }

        public function update( $table, $data, $where, $format = null, $where_format = null ) {
            return true;
        }

        public function query( $query ) {
            return true;
        }

        public function get_results( $query, $output = ARRAY_A ) {
            return [];
        }

        public function get_var( $query ) {
            return null;
        }

        public function esc_like( $text ) {
            return $text;
        }

        public $insert_id = 0;
    };
}

class TablesBulkActionsTest {
    public function run() {
        require_once dirname(__DIR__) . '/includes/admin/class-table-repository.php';

        $repo = new \RoxyTable\Includes\Admin\Table_Repository();
        $result = $repo::bulk_update_status([], 'published');
        if (0 !== $result) {
            throw new Exception('Expected empty ID list to return 0');
        }

        $result = $repo::bulk_update_status([1, 'bad', 2], 'invalid');
        if (2 !== $result) {
            throw new Exception('Expected invalid statuses to be normalized and counted');
        }

        echo "Bulk action validation passed\n";
    }
}

$test = new TablesBulkActionsTest();
$test->run();
