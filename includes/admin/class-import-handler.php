<?php
namespace RoxyTable\Includes\Admin;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

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
	 * @return string|true True on success, error message on failure.
	 */
	public static function handle_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return __( 'You do not have permission to upload files.', 'roxytable' );
		}

		if ( ! isset( $_POST['roxytable_upload_nonce'] ) || ! wp_verify_nonce( $_POST['roxytable_upload_nonce'], 'roxytable_file_upload' ) ) {
			return __( 'Security check failed. Please try again.', 'roxytable' );
		}

		if ( empty( $_FILES['roxytable_file']['name'] ) ) {
			return __( 'Please select a file to upload.', 'roxytable' );
		}

		$file = $_FILES['roxytable_file'];

		// Verify file extension
		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$allowed_exts = [ 'xlsx', 'xls', 'csv' ];
		if ( ! in_array( $ext, $allowed_exts, true ) ) {
			return __( 'Invalid file extension. Only .xlsx, .xls, and .csv files are supported.', 'roxytable' );
		}

		// Check server file size limits
		$max_upload = wp_max_upload_size();
		if ( $file['size'] > $max_upload ) {
			return sprintf( __( 'File size exceeds maximum upload limit of %s.', 'roxytable' ), size_format( $max_upload ) );
		}

		// Handle WordPress upload
		$overrides = [
			'test_form' => false,
			'mimes'     => [
				'xls'  => 'application/vnd.ms-excel',
				'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'csv'  => 'text/csv',
			],
		];

		// Include WordPress file helpers if not loaded
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload = wp_handle_upload( $file, $overrides );

		if ( isset( $upload['error'] ) ) {
			return $upload['error'];
		}

		// Clear previous transient and old files first
		self::clear_stored_data();

		// Save the new file path in transient
		self::set_stored_data( [
			'file_path' => $upload['file'],
			'file_name' => sanitize_file_name( $file['name'] ),
			'mime_type' => $upload['type'],
			'uploaded'  => time(),
		] );

		return true;
	}

	/**
	 * Get worksheet metadata list from the uploaded spreadsheet.
	 *
	 * @return array|string Metadata list or error message.
	 */
	public static function get_worksheets() {
		$data = self::get_stored_data();
		if ( ! $data || empty( $data['file_path'] ) || ! file_exists( $data['file_path'] ) ) {
			return __( 'No uploaded file found. Please upload a file first.', 'roxytable' );
		}

		try {
			$file_path = $data['file_path'];
			$reader = IOFactory::createReaderForFile( $file_path );
			$reader->setReadDataOnly( true );
			
			// Load metadata lists
			$sheet_names = $reader->listWorksheetNames( $file_path );
			$sheets_info = $reader->listWorksheetInfo( $file_path );

			$worksheets = [];
			foreach ( $sheet_names as $index => $name ) {
				$info = isset( $sheets_info[ $index ] ) ? $sheets_info[ $index ] : null;
				$worksheets[] = [
					'index' => $index,
					'name'  => $name,
					'rows'  => $info ? $info['totalRows'] : 0,
					'cols'  => $info ? $info['totalColumns'] : 0,
				];
			}
			return $worksheets;
		} catch ( \Exception $e ) {
			return sprintf( __( 'Error parsing spreadsheet: %s', 'roxytable' ), $e->getMessage() );
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

			// Read preview rows (rows 2 to 11)
			for ( $row = 2; $row <= $preview_rows_count; $row++ ) {
				$row_cells = [];
				for ( $col = 1; $col <= $highest_col_index; $col++ ) {
					$val = $sheet->getCellByColumnAndRow( $col, $row )->getValue();
					$row_cells[] = ( $val !== null ) ? sanitize_text_field( (string) $val ) : '';
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
					$val         = $sheet->getCellByColumnAndRow( $col, $row )->getValue();
					$row_cells[] = ( $val !== null ) ? sanitize_text_field( (string) $val ) : '';
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
