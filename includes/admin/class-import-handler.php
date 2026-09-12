<?php
namespace RoxyTable\Includes\Admin;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use RoxyTable\Includes\Logger;

/**
 * Handle Excel file uploading, validation, and parsing.
 */
class Import_Handler {

	/**
	 * Transient key prefix.
	 */
	const TRANSIENT_PREFIX = 'roxytable_import_';

	/**
	 * Get transient key for current user.
	 *
	 * @return string
	 */
	public static function get_transient_key() {
		return self::TRANSIENT_PREFIX . get_current_user_id();
	}

	/**
	 * Get stored import data from transient.
	 *
	 * @return array|false
	 */
	public static function get_stored_data() {
		$key = self::get_transient_key();
		return get_transient( $key );
	}

	/**
	 * Store import data in transient.
	 *
	 * @param array $data Stored parameters.
	 * @return bool
	 */
	public static function set_stored_data( $data ) {
		$key = self::get_transient_key();
		return set_transient( $key, $data, HOUR_IN_SECONDS );
	}

	/**
	 * Clear stored import data and delete the temporary file.
	 */
	public static function clear_stored_data() {
		$data = self::get_stored_data();
		if ( $data && ! empty( $data['file_path'] ) && file_exists( $data['file_path'] ) ) {
			@unlink( $data['file_path'] );
		}
		delete_transient( self::get_transient_key() );
	}

	/**
	 * Handle file upload securely.
	 *
	 * @return \WP_Error|true True on success, WP_Error on failure.
	 */
	public static function handle_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Upload failed: User does not have manage_options capability.', [
				'user_id' => get_current_user_id(),
			] );
			return new \WP_Error( 'permission_denied', __( 'Permission denied: You do not have permission to upload files.', 'roxytable' ) );
		}

		$nonce = isset( $_POST['roxytable_upload_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['roxytable_upload_nonce'] ) ) : '';
		if ( empty( $nonce ) && isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_file_upload' ) ) {
			Logger::error( 'Upload failed: Security nonce verification failed.', [
				'user_id'          => get_current_user_id(),
				'has_upload_nonce' => isset( $_POST['roxytable_upload_nonce'] ),
				'has_nonce'        => isset( $_POST['nonce'] ),
			] );
			return new \WP_Error( 'security_check_failed', __( 'Security verification failed or session expired. Please refresh the page and try again.', 'roxytable' ) );
		}

		if ( empty( $_FILES['roxytable_file'] ) || ! is_array( $_FILES['roxytable_file'] ) || empty( $_FILES['roxytable_file']['name'] ) ) {
			Logger::warning( 'Upload failed: No file was selected or uploaded.' );
			return new \WP_Error( 'no_file_selected', __( 'Please select a spreadsheet file (.xlsx, .xls, or .csv) to upload.', 'roxytable' ) );
		}

		$file = $_FILES['roxytable_file'];

		// Check PHP native file upload errors
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
			$php_errors = [
				UPLOAD_ERR_INI_SIZE   => sprintf( __( 'The uploaded file exceeds the server limit (upload_max_filesize = %s). Please upload a smaller file or increase server PHP limits.', 'roxytable' ), ini_get( 'upload_max_filesize' ) ),
				UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE limit specified in the form.', 'roxytable' ),
				UPLOAD_ERR_PARTIAL    => __( 'The file upload was interrupted or incomplete. Please check your internet connection and try again.', 'roxytable' ),
				UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded. Please select a file to continue.', 'roxytable' ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Server configuration error: Missing a temporary upload folder on the server.', 'roxytable' ),
				UPLOAD_ERR_CANT_WRITE => __( 'Server error: Failed to write uploaded file to disk. Please check server disk space and folder permissions.', 'roxytable' ),
				UPLOAD_ERR_EXTENSION  => __( 'Upload stopped: A PHP extension on the server blocked the file upload.', 'roxytable' ),
			];

			$err_code = (int) $file['error'];
			$err_msg  = $php_errors[ $err_code ] ?? sprintf( __( 'PHP file upload error (Code: %d).', 'roxytable' ), $err_code );

			Logger::error( 'Upload failed: PHP upload error encountered.', [
				'error_code' => $err_code,
				'message'    => $err_msg,
				'file_name'  => sanitize_file_name( $file['name'] ?? 'unknown' ),
			] );

			return new \WP_Error( 'php_upload_error', $err_msg );
		}

		// Verify file extension
		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$allowed_exts = [ 'xlsx', 'xls', 'csv' ];
		if ( ! in_array( $ext, $allowed_exts, true ) ) {
			$msg = sprintf(
				__( 'Invalid file extension ".%1$s". RoxyTable only supports %2$s files.', 'roxytable' ),
				esc_html( $ext ),
				'<strong>.xlsx, .xls, .csv</strong>'
			);
			Logger::warning( 'Upload failed: Unsupported file extension.', [
				'file_name' => sanitize_file_name( $file['name'] ),
				'extension' => $ext,
			] );
			return new \WP_Error( 'invalid_file_extension', $msg );
		}

		// Check server file size limits
		$max_upload = wp_max_upload_size();
		if ( $file['size'] > $max_upload ) {
			$msg = sprintf(
				__( 'File size (%1$s) exceeds maximum upload limit of %2$s.', 'roxytable' ),
				size_format( $file['size'] ),
				size_format( $max_upload )
			);
			Logger::warning( 'Upload failed: File size exceeds server limit.', [
				'file_name'  => sanitize_file_name( $file['name'] ),
				'file_size'  => $file['size'],
				'max_upload' => $max_upload,
			] );
			return new \WP_Error( 'file_too_large', $msg );
		}

		// Verify WordPress upload directory permissions
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			$msg = sprintf( __( 'Upload directory error: %s. Please check file system permissions for wp-content/uploads.', 'roxytable' ), $upload_dir['error'] );
			Logger::error( 'Upload failed: Uploads directory not writable.', [
				'upload_dir_error' => $upload_dir['error'],
			] );
			return new \WP_Error( 'upload_dir_not_writable', $msg );
		}

		// Handle WordPress upload
		$overrides = [
			'test_form'   => false,
			'test_upload' => false,
			'mimes'       => [
				'xls'  => 'application/vnd.ms-excel',
				'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'csv'  => 'text/csv',
			],
		];

		// Include WordPress file helpers if not loaded
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Temporarily broaden allowed mimes to avoid false positives with CSVs/spreadsheets
		$mime_filter = function( $mimes ) {
			$mimes['csv']  = 'text/csv';
			$mimes['xls']  = 'application/vnd.ms-excel';
			$mimes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			return $mimes;
		};
		add_filter( 'upload_mimes', $mime_filter, 999 );

		$upload = wp_handle_upload( $file, $overrides );

		remove_filter( 'upload_mimes', $mime_filter, 999 );

		if ( isset( $upload['error'] ) ) {
			Logger::error( 'Upload failed: wp_handle_upload returned error.', [
				'wp_error'  => $upload['error'],
				'file_name' => sanitize_file_name( $file['name'] ),
			] );
			return new \WP_Error( 'wp_upload_error', sprintf( __( 'File upload failed: %s', 'roxytable' ), $upload['error'] ) );
		}

		// Clear previous transient and old files first
		self::clear_stored_data();

		// Save the new file path in transient
		self::set_stored_data( [
			'file_path' => $upload['file'],
			'file_name' => sanitize_file_name( $file['name'] ),
			'mime_type' => $upload['type'],
			'file_size' => $file['size'],
			'uploaded'  => time(),
		] );

		Logger::info( 'File successfully uploaded and stored.', [
			'file_name' => sanitize_file_name( $file['name'] ),
			'file_path' => $upload['file'],
			'file_size' => $file['size'],
			'mime_type' => $upload['type'],
			'user_id'   => get_current_user_id(),
		] );

		return true;
	}

	/**
	 * Get worksheet metadata list from the uploaded spreadsheet.
	 *
	 * @return array|\WP_Error Metadata list or WP_Error on failure.
	 */
	public static function get_worksheets() {
		$data = self::get_stored_data();
		if ( ! $data || empty( $data['file_path'] ) || ! file_exists( $data['file_path'] ) ) {
			Logger::error( 'Worksheet retrieval failed: No uploaded file found in transient or file missing from disk.' );
			return new \WP_Error( 'file_not_found', __( 'No uploaded file found on the server. Please upload a file first.', 'roxytable' ) );
		}

		$file_path = $data['file_path'];

		try {
			$reader = IOFactory::createReaderForFile( $file_path );
			$reader->setReadDataOnly( true );
			
			// listWorksheetNames() is not supported by the CSV reader. Worksheet
			// names are included in the metadata returned by listWorksheetInfo().
			$sheets_info = $reader->listWorksheetInfo( $file_path );

			if ( empty( $sheets_info ) || ! is_array( $sheets_info ) ) {
				Logger::warning( 'Spreadsheet parsed but contained no worksheets.', [ 'file_path' => $file_path ] );
				return new \WP_Error( 'empty_spreadsheet', __( 'The uploaded file does not contain any readable worksheets or data.', 'roxytable' ) );
			}

			$worksheets = [];
			foreach ( $sheets_info as $index => $info ) {
				$worksheets[] = [
					'index' => $index,
					'name'  => isset( $info['worksheetName'] ) ? $info['worksheetName'] : sprintf( __( 'Worksheet %d', 'roxytable' ), $index + 1 ),
					'rows'  => $info ? (int) $info['totalRows'] : 0,
					'cols'  => $info ? (int) $info['totalColumns'] : 0,
				];
			}

			Logger::info( 'Worksheets extracted successfully.', [
				'file_name'        => $data['file_name'] ?? basename( $file_path ),
				'worksheets_count' => count( $worksheets ),
			] );

			return $worksheets;
		} catch ( \PhpOffice\PhpSpreadsheet\Reader\Exception $e ) {
			$msg = sprintf( __( 'Spreadsheet reader error: %s. The file may be corrupt or encrypted.', 'roxytable' ), $e->getMessage() );
			Logger::error( 'Spreadsheet reader exception: ' . $e->getMessage(), [ 'file' => $file_path ] );
			return new \WP_Error( 'spreadsheet_reader_error', $msg );
		} catch ( \Throwable $e ) {
			$msg = sprintf( __( 'Error parsing spreadsheet: %s', 'roxytable' ), $e->getMessage() );
			Logger::error( 'General error parsing spreadsheet: ' . $e->getMessage(), [ 'file' => $file_path ] );
			return new \WP_Error( 'spreadsheet_parse_error', $msg );
		}
	}

	/**
	 * Get preview rows from a specific worksheet.
	 *
	 * @param int $sheet_index The index of the worksheet to preview.
	 * @return array|string Preview row data or error message.
	 */
	public static function get_preview( $sheet_index ) {
		$data = self::get_stored_data();
		if ( ! $data || empty( $data['file_path'] ) || ! file_exists( $data['file_path'] ) ) {
			return __( 'No uploaded file found.', 'roxytable' );
		}

		try {
			$file_path = $data['file_path'];
			$reader = IOFactory::createReaderForFile( $file_path );
			$reader->setReadDataOnly( true );

			$spreadsheet = $reader->load( $file_path );
			$sheet = $spreadsheet->getSheet( $sheet_index );

			$highest_row = $sheet->getHighestRow();
			$highest_col = $sheet->getHighestColumn();
			$highest_col_index = Coordinate::columnIndexFromString( $highest_col );

			$preview_rows_count = min( 11, $highest_row ); // Header + up to 10 preview rows
			
			$headers = [];
			$rows    = [];

			// Read headers (row 1)
			for ( $col = 1; $col <= $highest_col_index; $col++ ) {
				$val = $sheet->getCellByColumnAndRow( $col, 1 )->getValue();
				$headers[] = ( $val !== null ) ? sanitize_text_field( (string) $val ) : '';
			}

			$merged_map = self::get_merged_placeholders( $sheet );

			// Read preview rows (rows 2 to 11)
			for ( $row = 2; $row <= $preview_rows_count; $row++ ) {
				$row_cells = [];
				for ( $col = 1; $col <= $highest_col_index; $col++ ) {
					$key = $row . '_' . $col;
					if ( isset( $merged_map[ $key ] ) ) {
						$val = $merged_map[ $key ];
					} else {
						$raw = $sheet->getCellByColumnAndRow( $col, $row )->getValue();
						$val = ( $raw !== null ) ? sanitize_text_field( (string) $raw ) : '';
					}
					$row_cells[] = $val;
				}
				$rows[] = $row_cells;
			}

			return [
				'headers'    => $headers,
				'rows'       => $rows,
				'sheet_name' => $sheet->getTitle(),
				'total_rows' => $highest_row,
				'total_cols' => $highest_col_index,
			];
		} catch ( \Exception $e ) {
			return sprintf( __( 'Error generating preview: %s', 'roxytable' ), $e->getMessage() );
		}
	}

	/**
	 * Build a map of merged cell placeholders for a worksheet.
	 *
	 * Conditional check: Verifies if the origin cell of a merged range is empty.
	 * If the cell is confirmed to be empty, skips #rowspan# / #colspan# injection
	 * entirely and leaves cell content as an empty string ("").
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @return array Map of [ "row_col" => "#rowspan#" | "#colspan#" | "" ]
	 */
	public static function get_merged_placeholders( $sheet ) : array {
		$map = [];
		try {
			$merge_ranges = $sheet->getMergeCells();
			if ( empty( $merge_ranges ) || ! is_array( $merge_ranges ) ) {
				return $map;
			}

			foreach ( $merge_ranges as $range ) {
				$parts = explode( ':', (string) $range );
				if ( count( $parts ) !== 2 ) {
					continue;
				}

				$start_col_str = preg_replace( '/[0-9]/', '', $parts[0] );
				$start_row     = (int) preg_replace( '/[A-Z]/i', '', $parts[0] );
				$end_col_str   = preg_replace( '/[0-9]/', '', $parts[1] );
				$end_row       = (int) preg_replace( '/[A-Z]/i', '', $parts[1] );

				$start_col = Coordinate::columnIndexFromString( $start_col_str );
				$end_col   = Coordinate::columnIndexFromString( $end_col_str );

				// Get origin cell value
				$origin_val = $sheet->getCellByColumnAndRow( $start_col, $start_row )->getValue();
				$is_empty   = ( $origin_val === null || '' === trim( (string) $origin_val ) );

				for ( $r = $start_row; $r <= $end_row; $r++ ) {
					for ( $c = $start_col; $c <= $end_col; $c++ ) {
						if ( $r === $start_row && $c === $start_col ) {
							continue; // Origin cell retains its actual value
						}

						$key = $r . '_' . $c;
						if ( $is_empty ) {
							// CONDITIONAL CHECK: If origin cell is empty, skip #rowspan# injection entirely
							$map[ $key ] = '';
						} else {
							if ( $r > $start_row ) {
								$map[ $key ] = '#rowspan#';
							} elseif ( $c > $start_col ) {
								$map[ $key ] = '#colspan#';
							}
						}
					}
				}
			}
		} catch ( \Throwable $t ) {
			// Ignore if range parsing fails
		}

		return $map;
	}

	/**
	 * Read ALL rows from a worksheet and apply parse options.
	 *
	 * Used during the final import step. Unlike get_preview() this reads every
	 * row and honours the caller-supplied parse flags.
	 *
	 * @param int   $sheet_index   Worksheet index.
	 * @param array $parse_options {
	 *     @type bool $first_row_headers  Treat row 1 as header row. Default true.
	 *     @type bool $auto_detect_types  Run type detection on headers. Default true.
	 *     @type bool $remove_empty_rows  Drop rows where every cell is empty. Default true.
	 *     @type bool $remove_empty_cols  Drop columns where every cell (incl. header) is empty. Default true.
	 * }
	 * @return array|string Parsed data or error string.
	 */
	public static function get_full_data( int $sheet_index, array $parse_options = [] ) {
		$opts = wp_parse_args( $parse_options, [
			'first_row_headers'  => true,
			'auto_detect_types'  => true,
			'remove_empty_rows'  => true,
			'remove_empty_cols'  => true,
		] );

		$data = self::get_stored_data();
		if ( ! $data || empty( $data['file_path'] ) || ! file_exists( $data['file_path'] ) ) {
			return __( 'No uploaded file found. Please upload a file first.', 'roxytable' );
		}

		try {
			$file_path = $data['file_path'];
			$reader    = IOFactory::createReaderForFile( $file_path );
			$reader->setReadDataOnly( true );

			$spreadsheet = $reader->load( $file_path );
			$sheet       = $spreadsheet->getSheet( $sheet_index );

			$highest_row       = $sheet->getHighestRow();
			$highest_col_index = Coordinate::columnIndexFromString( $sheet->getHighestColumn() );
			$merged_map        = self::get_merged_placeholders( $sheet );

			// ----- Read headers -----
			$headers    = [];
			$start_row  = 1;

			if ( $opts['first_row_headers'] ) {
				for ( $col = 1; $col <= $highest_col_index; $col++ ) {
					$val       = $sheet->getCellByColumnAndRow( $col, 1 )->getValue();
					$headers[] = ( $val !== null ) ? sanitize_text_field( (string) $val ) : '';
				}
				$start_row = 2;
			} else {
				// Generate column letters as header names
				for ( $col = 1; $col <= $highest_col_index; $col++ ) {
					$headers[] = 'Column ' . Coordinate::stringFromColumnIndex( $col );
				}
			}

			// ----- Read all data rows -----
			$rows = [];
			for ( $row = $start_row; $row <= $highest_row; $row++ ) {
				$row_cells = [];
				for ( $col = 1; $col <= $highest_col_index; $col++ ) {
					$key = $row . '_' . $col;
					if ( isset( $merged_map[ $key ] ) ) {
						$val = $merged_map[ $key ];
					} else {
						$raw = $sheet->getCellByColumnAndRow( $col, $row )->getValue();
						$val = ( $raw !== null ) ? sanitize_text_field( (string) $raw ) : '';
					}
					$row_cells[] = $val;
				}
				$rows[] = $row_cells;
			}

			// ----- Remove empty rows -----
			if ( $opts['remove_empty_rows'] ) {
				$rows = array_values( array_filter( $rows, function( $row_cells ) {
					foreach ( $row_cells as $cell ) {
						if ( '' !== trim( (string) $cell ) ) {
							return true;
						}
					}
					return false;
				} ) );
			}

			// ----- Remove empty columns -----
			if ( $opts['remove_empty_cols'] ) {
				$col_count     = count( $headers );
				$non_empty_cols = [];

				for ( $c = 0; $c < $col_count; $c++ ) {
					$header_empty = '' === trim( $headers[ $c ] );
					$col_empty    = true;

					if ( ! $header_empty ) {
						$col_empty = false;
					} else {
						// Check if any data row has a value in this column
						foreach ( $rows as $row_cells ) {
							if ( isset( $row_cells[ $c ] ) && '' !== trim( (string) $row_cells[ $c ] ) ) {
								$col_empty = false;
								break;
							}
						}
					}

					if ( ! $col_empty ) {
						$non_empty_cols[] = $c;
					}
				}

				// Filter headers and rows to keep only non-empty columns
				$headers = array_values( array_map( fn( $i ) => $headers[ $i ], $non_empty_cols ) );
				$rows    = array_values( array_map( function( $row_cells ) use ( $non_empty_cols ) {
					return array_values( array_map( fn( $i ) => $row_cells[ $i ] ?? '', $non_empty_cols ) );
				}, $rows ) );
			}

			// Free spreadsheet memory
			$spreadsheet->disconnectWorksheets();
			unset( $spreadsheet );

			return [
				'headers'    => $headers,
				'rows'       => $rows,
				'sheet_name' => $sheet->getTitle(),
				'total_rows' => count( $rows ),
				'total_cols' => count( $headers ),
			];

		} catch ( \Exception $e ) {
			return sprintf( __( 'Error reading spreadsheet: %s', 'roxytable' ), $e->getMessage() );
		}
	}
}
