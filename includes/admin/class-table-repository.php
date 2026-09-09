<?php
namespace RoxyTable\Includes\Admin;

/**
 * Persist imported table data to the database.
 *
 * All write operations go through this class so that the import pipeline
 * stays decoupled from the DB schema.
 */
class Table_Repository {

	/**
	 * Chunk size for bulk row inserts.
	 */
	const ROW_CHUNK_SIZE = 500;

	/**
	 * Allowed column data types (used by the editor's Columns tab).
	 */
	const COLUMN_TYPES = [ 'text', 'number', 'email', 'date', 'url', 'boolean' ];

	/**
	 * Insert a table record into roxy_tables.
	 *
	 * Generates a unique slug from the provided name if a collision is found.
	 *
	 * @param array $meta {
	 *     @type string $name         Table display name (required).
	 *     @type string $slug         Preferred slug (auto-generated from name if empty).
	 *     @type string $description  Optional description.
	 *     @type string $status       'draft' or 'published'.
	 *     @type int    $total_rows   Total data rows (not counting header).
	 *     @type int    $total_cols   Total column count.
	 *     @type array  $settings     Associative array of table settings (serialised as JSON).
	 * }
	 * @return int|\WP_Error Inserted table ID on success, WP_Error on failure.
	 */
	public static function insert_table( array $meta ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'roxy_tables';

		$slug = self::generate_unique_slug(
			! empty( $meta['slug'] ) ? $meta['slug'] : $meta['name']
		);

		$now = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table_name,
			[
				'name'          => sanitize_text_field( $meta['name'] ),
				'slug'          => $slug,
				'description'   => isset( $meta['description'] ) ? sanitize_textarea_field( $meta['description'] ) : '',
				'status'        => in_array( $meta['status'] ?? 'draft', [ 'draft', 'published' ], true ) ? $meta['status'] : 'draft',
				'total_rows'    => intval( $meta['total_rows'] ?? 0 ),
				'total_columns' => intval( $meta['total_cols'] ?? 0 ),
				'settings'      => wp_json_encode( $meta['settings'] ?? [] ),
				'created_at'    => $now,
				'updated_at'    => $now,
			],
			[ '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			return new \WP_Error(
				'roxytable_db_error',
				/* translators: %s: database error message */
				sprintf( __( 'Database error while inserting table: %s', 'roxytable' ), $wpdb->last_error )
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * List tables for the admin table browser.
	 *
	 * @param array $args {
	 *     @type string $search Search term for name/slug/id.
	 *     @type string $filter all|published|draft|updated.
	 *     @type int    $limit Number of rows.
	 *     @type int    $offset Offset for pagination.
	 * }
	 * @return array
	 */
	public static function list_tables( array $args = [] ) : array {
		global $wpdb;

		$search = isset( $args['search'] ) ? trim( sanitize_text_field( wp_unslash( $args['search'] ) ) ) : '';
		$filter = isset( $args['filter'] ) ? sanitize_key( wp_unslash( $args['filter'] ) ) : 'all';
		$limit = max( 1, intval( $args['limit'] ?? 20 ) );
		$offset = max( 0, intval( $args['offset'] ?? 0 ) );

		$table_name = $wpdb->prefix . 'roxy_tables';
		$where = [];
		$params = [];

		if ( '' !== $search ) {
			$where[] = '(id = %d OR name LIKE %s OR slug LIKE %s)';
			$params[] = absint( $search );
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( 'published' === $filter ) {
			$where[] = 'status = %s';
			$params[] = 'published';
		} elseif ( 'draft' === $filter ) {
			$where[] = 'status = %s';
			$params[] = 'draft';
		} elseif ( 'updated' === $filter ) {
			$where[] = 'updated_at >= %s';
			$params[] = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		}

		$sql = "SELECT id, name, slug, description, status, total_rows, total_columns, updated_at FROM {$table_name}";
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}
		$sql .= ' ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		$query = $wpdb->prepare( $sql, $params );
		return $wpdb->get_results( $query, \ARRAY_A ) ?: [];
	}

	/**
	 * Count tables matching the admin list filters.
	 *
	 * @param array $args See list_tables().
	 * @return int
	 */
	public static function count_tables( array $args = [] ) : int {
		global $wpdb;

		$search = isset( $args['search'] ) ? trim( sanitize_text_field( wp_unslash( $args['search'] ) ) ) : '';
		$filter = isset( $args['filter'] ) ? sanitize_key( wp_unslash( $args['filter'] ) ) : 'all';
		$table_name = $wpdb->prefix . 'roxy_tables';
		$where = [];
		$params = [];

		if ( '' !== $search ) {
			$where[] = '(id = %d OR name LIKE %s OR slug LIKE %s)';
			$params[] = absint( $search );
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( 'published' === $filter ) {
			$where[] = 'status = %s';
			$params[] = 'published';
		} elseif ( 'draft' === $filter ) {
			$where[] = 'status = %s';
			$params[] = 'draft';
		} elseif ( 'updated' === $filter ) {
			$where[] = 'updated_at >= %s';
			$params[] = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		}

		$sql = "SELECT COUNT(*) FROM {$table_name}";
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$query = $wpdb->prepare( $sql, $params );
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Delete a table and cascade to its rows/columns.
	 *
	 * @param int $table_id Table ID.
	 * @return bool
	 */
	public static function delete_table( int $table_id ) : bool {
		global $wpdb;

		if ( $table_id < 1 ) {
			return false;
		}

		$table_name = $wpdb->prefix . 'roxy_tables';
		$columns_table = $wpdb->prefix . 'roxy_columns';
		$rows_table = $wpdb->prefix . 'roxy_rows';

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$rows_table} WHERE table_id = %d", $table_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$columns_table} WHERE table_id = %d", $table_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id = %d", $table_id ) );

		return true;
	}

	/**
	 * Get rows for CSV export of selected tables.
	 *
	 * @param array $table_ids Table IDs.
	 * @return array
	 */
	public static function get_export_rows( array $table_ids ) : array {
		global $wpdb;

		$table_ids = array_map( 'intval', array_filter( $table_ids, 'is_numeric' ) );
		if ( empty( $table_ids ) ) {
			return [];
		}

		$table_name = $wpdb->prefix . 'roxy_tables';
		$placeholders = implode( ', ', array_fill( 0, count( $table_ids ), '%d' ) );
		$query = $wpdb->prepare(
			"SELECT id, name, slug, description, status, total_rows, total_columns, updated_at FROM {$table_name} WHERE id IN ({$placeholders}) ORDER BY id ASC",
			$table_ids
		);

		return $wpdb->get_results( $query, \ARRAY_A ) ?: [];
	}

	/**
	 * Bulk update status for selected tables.
	 *
	 * @param array $table_ids Table IDs.
	 * @param string $status New status.
	 * @return int Number updated.
	 */
	public static function bulk_update_status( array $table_ids, string $status ) : int {
		global $wpdb;

		$table_ids = array_map( 'intval', array_filter( $table_ids, 'is_numeric' ) );
		if ( empty( $table_ids ) ) {
			return 0;
		}

		$status = in_array( $status, [ 'draft', 'published' ], true ) ? $status : 'draft';

		$table_name = $wpdb->prefix . 'roxy_tables';
		$in = implode( ', ', array_fill( 0, count( $table_ids ), '%d' ) );
		$query = $wpdb->prepare( "UPDATE {$table_name} SET status = %s, updated_at = %s WHERE id IN ({$in})", array_merge( [ $status ], [ current_time( 'mysql' ) ], $table_ids ) );
		$wpdb->query( $query );

		return count( $table_ids );
	}

	/**
	 * Bulk delete selected tables.
	 *
	 * @param array $table_ids Table IDs.
	 * @return int Number deleted.
	 */
	public static function bulk_delete( array $table_ids ) : int {
		global $wpdb;

		$table_ids = array_map( 'intval', array_filter( $table_ids, 'is_numeric' ) );
		if ( empty( $table_ids ) ) {
			return 0;
		}

		foreach ( $table_ids as $table_id ) {
			self::delete_table( $table_id );
		}

		return count( $table_ids );
	}

	/**
	 * Bulk-insert column definitions for a table.
	 *
	 * @param int   $table_id    Target table ID.
	 * @param array $headers     Ordered array of column header strings.
	 * @param bool  $auto_detect Whether to run type detection on header names.
	 * @return void
	 */
	public static function insert_columns( int $table_id, array $headers, bool $auto_detect = true ) {
		global $wpdb;

		$columns_table = $wpdb->prefix . 'roxy_columns';

		foreach ( $headers as $position => $name ) {
			$col_name = sanitize_text_field( (string) $name );
			if ( '' === $col_name ) {
				// Assign a default name for blank headers using Excel-style column letters
				$col_name = 'Column ' . self::int_to_column_letter( $position + 1 );
			}

			$type = $auto_detect ? self::detect_type( $col_name ) : 'text';

			$wpdb->insert(
				$columns_table,
				[
					'table_id' => $table_id,
					'position' => $position,
					'name'     => $col_name,
					'type'     => $type,
					'visible'     => 1,
					'searchable'  => 1,
					'sortable'    => 1,
					'filterable'  => 1,
					'settings'    => null,
				],
				[ '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s' ]
			);
		}
	}

	/**
	 * Bulk-insert data rows using chunked prepared statements.
	 *
	 * Each row is stored as a JSON-encoded array of cell values in the
	 * `data` column, positionally matching the column order.
	 *
	 * @param int   $table_id   Target table ID.
	 * @param array $rows       Array of rows; each row is an array of cell values.
	 * @param int   $chunk_size Number of rows per INSERT batch.
	 * @return int Total rows inserted.
	 */
	public static function insert_rows( int $table_id, array $rows, int $chunk_size = self::ROW_CHUNK_SIZE ) : int {
		global $wpdb;

		$rows_table    = $wpdb->prefix . 'roxy_rows';
		$total_inserted = 0;
		$chunks         = array_chunk( $rows, $chunk_size );

		foreach ( $chunks as $chunk ) {
			// Build multi-row INSERT with a single $wpdb->query() call per chunk
			$placeholders = [];
			$values       = [];
			$position     = $total_inserted;

			foreach ( $chunk as $row_data ) {
				// Sanitize each cell value
				$sanitized = array_map( function( $cell ) {
					return sanitize_text_field( (string) $cell );
				}, (array) $row_data );

				$placeholders[] = '(%d, %d, %s)';
				$values[]       = $table_id;
				$values[]       = $position;
				$values[]       = wp_json_encode( $sanitized );
				$position++;
			}

			$sql = "INSERT INTO {$rows_table} (table_id, position, data) VALUES "
				. implode( ', ', $placeholders );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built above
			$wpdb->query( $wpdb->prepare( $sql, $values ) );

			$total_inserted += count( $chunk );
		}

		return $total_inserted;
	}

	/**
	 * Update total_rows and total_columns on the parent table record.
	 *
	 * Called after rows have been inserted so counts are accurate.
	 *
	 * @param int $table_id   Table ID.
	 * @param int $total_rows Actual row count.
	 * @param int $total_cols Actual column count.
	 */
	public static function update_counts( int $table_id, int $total_rows, int $total_cols ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'roxy_tables',
			[
				'total_rows'    => $total_rows,
				'total_columns' => $total_cols,
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $table_id ],
			[ '%d', '%d', '%s' ],
			[ '%d' ]
		);
	}

	// =========================================================================
	// Editor: read helpers
	// =========================================================================

	/**
	 * Fetch a single table record by ID.
	 *
	 * @param int $id Table ID.
	 * @return array|null Associative row or null when not found.
	 */
	public static function get_table( int $id ) : ?array {
		global $wpdb;

		if ( $id < 1 ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'roxy_tables';
		$row        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ),
			\ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Fetch a table's columns in display (position) order.
	 *
	 * @param int $table_id Table ID.
	 * @return array List of column rows.
	 */
	public static function get_columns( int $table_id ) : array {
		global $wpdb;

		$columns_table = $wpdb->prefix . 'roxy_columns';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$columns_table} WHERE table_id = %d ORDER BY position ASC, id ASC",
				$table_id
			),
			\ARRAY_A
		) ?: [];
	}

	/**
	 * Fetch a page of rows for a table, decoding each row's positional data array.
	 *
	 * Search performs an approximate match against the raw JSON text of the
	 * `data` column (documented limitation — it does not parse individual cells).
	 *
	 * @param int    $table_id Table ID.
	 * @param int    $limit    Page size.
	 * @param int    $offset   Row offset.
	 * @param string $search   Optional search term.
	 * @return array List of rows shaped as [ 'id', 'position', 'cells' => [...] ].
	 */
	public static function get_rows( int $table_id, int $limit = 50, int $offset = 0, string $search = '' ) : array {
		global $wpdb;

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$limit      = max( 1, $limit );
		$offset     = max( 0, $offset );

		$where  = 'table_id = %d';
		$params = [ $table_id ];

		if ( '' !== $search ) {
			$where   .= ' AND data LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$sql      = "SELECT id, position, data FROM {$rows_table} WHERE {$where} ORDER BY position ASC, id ASC LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ), \ARRAY_A ) ?: [];

		foreach ( $results as &$row ) {
			$decoded      = json_decode( (string) $row['data'], true );
			$row['cells'] = is_array( $decoded ) ? array_values( $decoded ) : [];
			unset( $row['data'] );
		}
		unset( $row );

		return $results;
	}

	/**
	 * Count rows for a table, honouring the same search term as get_rows().
	 *
	 * @param int    $table_id Table ID.
	 * @param string $search   Optional search term.
	 * @return int
	 */
	public static function count_rows( int $table_id, string $search = '' ) : int {
		global $wpdb;

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$where      = 'table_id = %d';
		$params     = [ $table_id ];

		if ( '' !== $search ) {
			$where   .= ' AND data LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$rows_table} WHERE {$where}", $params )
		);
	}

	/**
	 * Assemble headers + decoded rows for a per-table CSV export.
	 *
	 * @param int $table_id Table ID.
	 * @return array [ 'headers' => string[], 'rows' => array[] ].
	 */
	public static function get_table_data_for_export( int $table_id ) : array {
		global $wpdb;

		$columns = self::get_columns( $table_id );
		$headers = array_map( static fn( $c ) => (string) $c['name'], $columns );

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$rows       = [];
		$offset     = 0;

		do {
			$batch = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT data FROM {$rows_table} WHERE table_id = %d ORDER BY position ASC, id ASC LIMIT %d OFFSET %d",
					$table_id,
					self::ROW_CHUNK_SIZE,
					$offset
				),
				\ARRAY_A
			) ?: [];

			foreach ( $batch as $r ) {
				$cells  = json_decode( (string) $r['data'], true );
				$rows[] = is_array( $cells ) ? array_values( $cells ) : [];
			}

			$offset += self::ROW_CHUNK_SIZE;
		} while ( count( $batch ) === self::ROW_CHUNK_SIZE );

		return [ 'headers' => $headers, 'rows' => $rows ];
	}

	// =========================================================================
	// Editor: row write operations
	// =========================================================================

	/**
	 * Persist edited cell values for a set of rows.
	 *
	 * Each row is updated by its own prepared statement, constrained by table_id
	 * so a caller can never overwrite another table's rows.
	 *
	 * @param int   $table_id Table ID (tenant guard).
	 * @param array $edits    Map of row_id => array of cell values.
	 * @return int Number of rows written.
	 */
	public static function update_rows_data( int $table_id, array $edits ) : int {
		global $wpdb;

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$written    = 0;

		foreach ( $edits as $row_id => $cells ) {
			$row_id = (int) $row_id;
			if ( $row_id < 1 ) {
				continue;
			}

			$sanitized = array_map(
				static fn( $cell ) => wp_kses_post( (string) $cell ),
				array_values( (array) $cells )
			);

			$result = $wpdb->update(
				$rows_table,
				[ 'data' => wp_json_encode( $sanitized ) ],
				[ 'id' => $row_id, 'table_id' => $table_id ],
				[ '%s' ],
				[ '%d', '%d' ]
			);

			if ( false !== $result ) {
				$written++;
			}
		}

		if ( $written > 0 ) {
			self::touch( $table_id );
		}

		return $written;
	}

	/**
	 * Insert a blank row at a given position, shifting later rows down.
	 *
	 * @param int $table_id  Table ID.
	 * @param int $position  Zero-based insert position (rows >= this shift +1).
	 * @param int $col_count Number of columns (blank cell count).
	 * @return int New row ID.
	 */
	public static function insert_blank_row( int $table_id, int $position, int $col_count ) : int {
		global $wpdb;

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$position   = max( 0, $position );
		$col_count  = max( 1, $col_count );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$rows_table} SET position = position + 1 WHERE table_id = %d AND position >= %d",
				$table_id,
				$position
			)
		);

		$wpdb->insert(
			$rows_table,
			[
				'table_id' => $table_id,
				'position' => $position,
				'data'     => wp_json_encode( array_fill( 0, $col_count, '' ) ),
			],
			[ '%d', '%d', '%s' ]
		);

		$new_id = (int) $wpdb->insert_id;
		self::sync_row_count( $table_id );

		return $new_id;
	}

	/**
	 * Delete rows by ID, constrained to a single table.
	 *
	 * @param int   $table_id Table ID (tenant guard).
	 * @param array $ids      Row IDs.
	 * @return int Number of rows deleted.
	 */
	public static function delete_rows( int $table_id, array $ids ) : int {
		global $wpdb;

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$ids        = array_values( array_filter( array_map( 'intval', $ids ), static fn( $i ) => $i > 0 ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$deleted      = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$rows_table} WHERE table_id = %d AND id IN ({$placeholders})",
				array_merge( [ $table_id ], $ids )
			)
		);

		self::sync_row_count( $table_id );

		return (int) $deleted;
	}

	// =========================================================================
	// Editor: column write operations
	// =========================================================================

	/**
	 * Save the full ordered set of columns for a table.
	 *
	 * The incoming array is authoritative for display order: each element's
	 * array index becomes the column's new `position`. When the order changes
	 * relative to what is stored, every row's positional `data` array is remapped
	 * to preserve the cell-to-column alignment invariant.
	 *
	 * @param int   $table_id Table ID.
	 * @param array $cols     Ordered list of columns; each: { id, name, type, visible, searchable, sortable, filterable }.
	 * @return array The refreshed column list (post-save).
	 */
	public static function save_columns( int $table_id, array $cols ) : array {
		global $wpdb;

		$columns_table = $wpdb->prefix . 'roxy_columns';

		// Authoritative current positions, keyed by column id.
		$current       = self::get_columns( $table_id );
		$id_to_old_pos = [];
		foreach ( $current as $c ) {
			$id_to_old_pos[ (int) $c['id'] ] = (int) $c['position'];
		}

		$old_to_new = [];

		foreach ( array_values( $cols ) as $new_pos => $col ) {
			$col_id = isset( $col['id'] ) ? (int) $col['id'] : 0;
			if ( $col_id < 1 || ! isset( $id_to_old_pos[ $col_id ] ) ) {
				continue; // Ignore unknown / foreign columns.
			}

			$name = sanitize_text_field( (string) ( $col['name'] ?? '' ) );
			if ( '' === $name ) {
				$name = 'Column ' . self::int_to_column_letter( $new_pos + 1 );
			}

			$type = in_array( $col['type'] ?? 'text', self::COLUMN_TYPES, true ) ? $col['type'] : 'text';

			$wpdb->update(
				$columns_table,
				[
					'name'       => $name,
					'type'       => $type,
					'position'   => $new_pos,
					'visible'    => ! empty( $col['visible'] ) ? 1 : 0,
					'searchable' => ! empty( $col['searchable'] ) ? 1 : 0,
					'sortable'   => ! empty( $col['sortable'] ) ? 1 : 0,
					'filterable' => ! empty( $col['filterable'] ) ? 1 : 0,
				],
				[ 'id' => $col_id, 'table_id' => $table_id ],
				[ '%s', '%s', '%d', '%d', '%d', '%d', '%d' ],
				[ '%d', '%d' ]
			);

			$old_to_new[ $id_to_old_pos[ $col_id ] ] = $new_pos;
		}

		// Remap row data only when the order actually changed.
		$reordered = false;
		foreach ( $old_to_new as $old => $new ) {
			if ( $old !== $new ) {
				$reordered = true;
				break;
			}
		}
		if ( $reordered ) {
			self::remap_row_data( $table_id, $old_to_new );
		}

		self::touch( $table_id, [ 'total_columns' => count( $current ) ] );

		return self::get_columns( $table_id );
	}

	/**
	 * Apply a column permutation to every row's positional data array.
	 *
	 * @param int   $table_id   Table ID.
	 * @param array $old_to_new Map of old column index => new column index.
	 * @return void
	 */
	public static function remap_row_data( int $table_id, array $old_to_new ) : void {
		global $wpdb;

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$offset     = 0;

		do {
			$batch = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, data FROM {$rows_table} WHERE table_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
					$table_id,
					self::ROW_CHUNK_SIZE,
					$offset
				),
				\ARRAY_A
			) ?: [];

			foreach ( $batch as $row ) {
				$cells = json_decode( (string) $row['data'], true );
				if ( ! is_array( $cells ) ) {
					continue;
				}
				$cells = array_values( $cells );

				$new_cells = [];
				foreach ( $old_to_new as $old_i => $new_i ) {
					$new_cells[ $new_i ] = $cells[ $old_i ] ?? '';
				}
				ksort( $new_cells );

				$wpdb->update(
					$rows_table,
					[ 'data' => wp_json_encode( array_values( $new_cells ) ) ],
					[ 'id' => (int) $row['id'] ],
					[ '%s' ],
					[ '%d' ]
				);
			}

			$offset += self::ROW_CHUNK_SIZE;
		} while ( count( $batch ) === self::ROW_CHUNK_SIZE );
	}

	// =========================================================================
	// Editor: table meta / settings / lifecycle
	// =========================================================================

	/**
	 * Update editable table metadata (name / slug / description / status).
	 *
	 * Only provided keys are written. A provided slug is passed through
	 * uniqueness resolution excluding the current table.
	 *
	 * @param int   $table_id Table ID.
	 * @param array $meta     Partial metadata.
	 * @return true|\WP_Error
	 */
	public static function update_table_meta( int $table_id, array $meta ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'roxy_tables';
		$data       = [];
		$fmt        = [];

		if ( isset( $meta['name'] ) ) {
			$name = sanitize_text_field( $meta['name'] );
			if ( '' === $name ) {
				return new \WP_Error( 'roxytable_invalid', __( 'Table name is required.', 'roxytable' ) );
			}
			$data['name'] = $name;
			$fmt[]        = '%s';
		}

		if ( isset( $meta['slug'] ) ) {
			$base         = '' !== $meta['slug'] ? $meta['slug'] : ( $meta['name'] ?? 'table' );
			$data['slug'] = self::generate_unique_slug( $base, $table_id );
			$fmt[]        = '%s';
		}

		if ( isset( $meta['description'] ) ) {
			$data['description'] = sanitize_textarea_field( $meta['description'] );
			$fmt[]               = '%s';
		}

		if ( isset( $meta['status'] ) ) {
			$data['status'] = in_array( $meta['status'], [ 'draft', 'published' ], true ) ? $meta['status'] : 'draft';
			$fmt[]          = '%s';
		}

		if ( empty( $data ) ) {
			return true;
		}

		$data['updated_at'] = current_time( 'mysql' );
		$fmt[]              = '%s';

		$result = $wpdb->update( $table_name, $data, [ 'id' => $table_id ], $fmt, [ '%d' ] );
		if ( false === $result ) {
			return new \WP_Error( 'roxytable_db_error', $wpdb->last_error );
		}

		return true;
	}

	/**
	 * Regenerate and persist a fresh unique slug from the table's current name.
	 *
	 * @param int $table_id Table ID.
	 * @return string The new slug (empty string when the table is missing).
	 */
	public static function regenerate_slug( int $table_id ) : string {
		global $wpdb;

		$table = self::get_table( $table_id );
		if ( ! $table ) {
			return '';
		}

		$slug = self::generate_unique_slug( $table['name'], $table_id );

		$wpdb->update(
			$wpdb->prefix . 'roxy_tables',
			[ 'slug' => $slug, 'updated_at' => current_time( 'mysql' ) ],
			[ 'id' => $table_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		return $slug;
	}

	/**
	 * Deep-merge a partial settings array into the stored settings JSON.
	 *
	 * List-shaped values (e.g. filter rules) are replaced wholesale rather than
	 * index-merged, so removing an item works as expected.
	 *
	 * @param int   $table_id Table ID.
	 * @param array $partial  Partial settings to merge in.
	 * @return array The merged settings.
	 */
	public static function update_table_settings( int $table_id, array $partial ) : array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'roxy_tables';
		$current    = $wpdb->get_var( $wpdb->prepare( "SELECT settings FROM {$table_name} WHERE id = %d", $table_id ) );
		$settings   = json_decode( (string) $current, true );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$merged = self::deep_merge( $settings, $partial );

		$wpdb->update(
			$table_name,
			[ 'settings' => wp_json_encode( $merged ), 'updated_at' => current_time( 'mysql' ) ],
			[ 'id' => $table_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		return $merged;
	}

	/**
	 * Duplicate a table, including its columns and all rows (chunked).
	 *
	 * @param int $table_id Source table ID.
	 * @return int|\WP_Error New table ID or error.
	 */
	public static function duplicate_table( int $table_id ) {
		global $wpdb;

		$src = self::get_table( $table_id );
		if ( ! $src ) {
			return new \WP_Error( 'roxytable_not_found', __( 'Table not found.', 'roxytable' ) );
		}

		$settings = json_decode( (string) $src['settings'], true );

		$new_id = self::insert_table( [
			'name'        => $src['name'] . ' (copy)',
			'slug'        => '', // Force a fresh unique slug.
			'description' => $src['description'],
			'status'      => 'draft',
			'total_rows'  => 0,
			'total_cols'  => (int) $src['total_columns'],
			'settings'    => is_array( $settings ) ? $settings : [],
		] );

		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		// Copy columns verbatim.
		$columns_table = $wpdb->prefix . 'roxy_columns';
		foreach ( self::get_columns( $table_id ) as $col ) {
			$wpdb->insert(
				$columns_table,
				[
					'table_id'   => $new_id,
					'position'   => (int) $col['position'],
					'name'       => $col['name'],
					'type'       => $col['type'],
					'visible'    => (int) $col['visible'],
					'searchable' => (int) $col['searchable'],
					'sortable'   => (int) $col['sortable'],
					'filterable' => (int) $col['filterable'],
					'settings'   => $col['settings'],
				],
				[ '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s' ]
			);
		}

		// Copy rows in chunks.
		$rows_table = $wpdb->prefix . 'roxy_rows';
		$offset     = 0;
		$total      = 0;

		do {
			$batch = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT position, data FROM {$rows_table} WHERE table_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
					$table_id,
					self::ROW_CHUNK_SIZE,
					$offset
				),
				\ARRAY_A
			) ?: [];

			if ( ! empty( $batch ) ) {
				$placeholders = [];
				$values       = [];
				foreach ( $batch as $r ) {
					$placeholders[] = '(%d, %d, %s)';
					$values[]       = $new_id;
					$values[]       = (int) $r['position'];
					$values[]       = $r['data'];
				}
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$rows_table} (table_id, position, data) VALUES " . implode( ', ', $placeholders ),
						$values
					)
				);
				$total += count( $batch );
			}

			$offset += self::ROW_CHUNK_SIZE;
		} while ( count( $batch ) === self::ROW_CHUNK_SIZE );

		self::update_counts( $new_id, $total, (int) $src['total_columns'] );

		return $new_id;
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Recompute a table's total_rows from the rows table and stamp updated_at.
	 *
	 * @param int $table_id Table ID.
	 * @return void
	 */
	private static function sync_row_count( int $table_id ) : void {
		global $wpdb;

		$rows_table = $wpdb->prefix . 'roxy_rows';
		$count      = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$rows_table} WHERE table_id = %d", $table_id )
		);

		$wpdb->update(
			$wpdb->prefix . 'roxy_tables',
			[ 'total_rows' => $count, 'updated_at' => current_time( 'mysql' ) ],
			[ 'id' => $table_id ],
			[ '%d', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Stamp updated_at on a table, optionally writing extra integer/string fields.
	 *
	 * @param int   $table_id Table ID.
	 * @param array $extra    Optional extra column => value pairs (int or string).
	 * @return void
	 */
	private static function touch( int $table_id, array $extra = [] ) : void {
		global $wpdb;

		$data = array_merge( $extra, [ 'updated_at' => current_time( 'mysql' ) ] );
		$fmt  = [];
		foreach ( $data as $value ) {
			$fmt[] = is_int( $value ) ? '%d' : '%s';
		}

		$wpdb->update(
			$wpdb->prefix . 'roxy_tables',
			$data,
			[ 'id' => $table_id ],
			$fmt,
			[ '%d' ]
		);
	}

	/**
	 * Recursively merge associative arrays; lists are replaced, not merged.
	 *
	 * @param array $base     Base array.
	 * @param array $override Values to merge in.
	 * @return array
	 */
	private static function deep_merge( array $base, array $override ) : array {
		foreach ( $override as $key => $value ) {
			if (
				is_array( $value )
				&& isset( $base[ $key ] )
				&& is_array( $base[ $key ] )
				&& self::is_assoc( $value )
			) {
				$base[ $key ] = self::deep_merge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}

	/**
	 * Determine whether an array is associative (not a 0..n-1 list).
	 *
	 * @param array $arr Array to test.
	 * @return bool
	 */
	private static function is_assoc( array $arr ) : bool {
		if ( [] === $arr ) {
			return false;
		}
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	/**
	 * Generate a slug that doesn't already exist in roxy_tables.
	 *
	 * @param string $raw        Raw string to slugify.
	 * @param int    $exclude_id Table ID to ignore during the collision check (0 = none).
	 * @return string Unique slug.
	 */
	private static function generate_unique_slug( string $raw, int $exclude_id = 0 ) : string {
		global $wpdb;

		$base_slug = sanitize_title( $raw );
		if ( '' === $base_slug ) {
			$base_slug = 'table';
		}

		$table_name = $wpdb->prefix . 'roxy_tables';
		$slug       = $base_slug;
		$suffix     = 2;

		while ( true ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} WHERE slug = %s AND id != %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$slug,
					$exclude_id
				)
			);

			if ( null === $exists ) {
				break; // Slug is free
			}

			$slug = $base_slug . '-' . $suffix;
			$suffix++;
		}

		return $slug;
	}

	/**
	 * Heuristic column type detection based on the header label.
	 *
	 * Returns 'number', 'date', or 'text'.
	 *
	 * @param string $header Column header label.
	 * @return string Detected type.
	 */
	private static function detect_type( string $header ) : string {
		$lower = strtolower( $header );

		$date_keywords   = [ 'date', 'time', 'created', 'updated', 'modified', 'born', 'expiry', 'expire', 'start', 'end' ];
		$number_keywords = [ 'id', 'count', 'qty', 'quantity', 'price', 'amount', 'total', 'number', 'num', 'age', 'year', 'rate', 'score', 'rank', 'salary', 'revenue', 'cost' ];

		foreach ( $date_keywords as $kw ) {
			if ( str_contains( $lower, $kw ) ) {
				return 'date';
			}
		}

		foreach ( $number_keywords as $kw ) {
			if ( str_contains( $lower, $kw ) ) {
				return 'number';
			}
		}

		return 'text';
	}

	/**
	 * Convert a 1-based column index to an Excel-style letter (A, B … Z, AA …).
	 *
	 * @param int $index 1-based column index.
	 * @return string Column letter(s).
	 */
	private static function int_to_column_letter( int $index ) : string {
		$letter = '';
		while ( $index > 0 ) {
			$index--;
			$letter = chr( 65 + ( $index % 26 ) ) . $letter;
			$index  = (int) floor( $index / 26 );
		}
		return $letter;
	}
}
