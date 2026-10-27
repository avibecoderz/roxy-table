<?php
namespace RoxyTable\Includes\Admin;

use RoxyTable\Includes\Logger;

/**
 * Register admin menu and load page assets.
 */
class Admin_Menu {

	/**
	 * The hooks registered for RoxyTable admin screens.
	 *
	 * @var array
	 */
	private $page_hooks = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_module_type' ], 10, 3 );
		add_action( 'admin_init', [ $this, 'process_import_post' ] );

		// Register native WordPress AJAX endpoints
		add_action( 'wp_ajax_roxytable_save_data', [ $this, 'ajax_save_data' ] );
		add_action( 'wp_ajax_roxytable_save_table', [ $this, 'ajax_save_table' ] );
		add_action( 'wp_ajax_roxytable_delete_table', [ $this, 'ajax_delete_table' ] );
		add_action( 'wp_ajax_roxytable_bulk_tables', [ $this, 'ajax_bulk_tables' ] );
		add_action( 'wp_ajax_roxytable_use_template', [ $this, 'ajax_use_template' ] );
		add_action( 'wp_ajax_roxytable_upload_file', [ $this, 'ajax_upload_file' ] );
		add_action( 'wp_ajax_roxytable_confirm_import', [ $this, 'ajax_confirm_import' ] );
		add_action( 'wp_ajax_roxytable_save_profile', [ $this, 'ajax_save_profile' ] );
		add_action( 'wp_ajax_roxytable_save_table_image', [ $this, 'ajax_save_table_image' ] );
	}

	/**
	 * Process file upload requests before headers are sent.
	 */
	public function process_import_post() {
		// Do not run during AJAX requests - AJAX actions are handled by dedicated wp_ajax_* endpoints
		if ( wp_doing_ajax() || ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Create a new draft table from a design template.
		if ( isset( $_POST['roxytable_use_template'] ) ) {
			if ( ! isset( $_POST['roxytable_template_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['roxytable_template_nonce'] ), 'roxytable_use_template' ) ) {
				wp_die( __( 'Security check failed.', 'roxytable' ) );
			}

			$template = sanitize_key( wp_unslash( $_POST['roxytable_use_template'] ) );
			$templates = [
				'indigo'  => [ 'name' => 'Indigo Clean Table', 'columns' => [ 'Institution', 'Website', 'Description' ] ],
				'obsidian' => [ 'name' => 'Dark Minimal Table', 'columns' => [ 'Name', 'Value', 'Description' ] ],
				'emerald' => [ 'name' => 'Emerald Grid Table', 'columns' => [ 'Name', 'Category', 'Description' ] ],
			];
			if ( ! isset( $templates[ $template ] ) ) {
				wp_die( __( 'Invalid template selected.', 'roxytable' ) );
			}

			$definition = $templates[ $template ];
			$table_id = \RoxyTable\Includes\Admin\Table_Repository::insert_table( [
				'name'        => $definition['name'],
				'description' => __( 'Created from a RoxyTable design template.', 'roxytable' ),
				'status'      => 'draft',
				'total_rows'  => 1,
				'total_cols'  => count( $definition['columns'] ),
				'settings'    => [ 'template' => $template, 'pagination' => true, 'rows_per_page' => 25 ],
			] );
			if ( is_wp_error( $table_id ) ) {
				wp_die( esc_html( $table_id->get_error_message() ) );
			}

			\RoxyTable\Includes\Admin\Table_Repository::insert_columns( $table_id, $definition['columns'], false );
			\RoxyTable\Includes\Admin\Table_Repository::insert_rows( $table_id, [ array_fill( 0, count( $definition['columns'] ), '' ) ] );
			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id . '&template_created=1' ) );
			exit;
		}

		// Handle Step 1 -> Step 2 transition (Upload file)
		if ( isset( $_POST['roxytable_upload_action'] ) && 'upload' === $_POST['roxytable_upload_action'] ) {
			$upload_result = \RoxyTable\Includes\Admin\Import_Handler::handle_upload();
			if ( true === $upload_result ) {
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import&step=2' ) );
				exit;
			} else {
				$err_msg = is_wp_error( $upload_result ) ? $upload_result->get_error_message() : (string) $upload_result;
				set_transient( 'roxytable_upload_error_' . get_current_user_id(), $err_msg, 30 );
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import&step=1' ) );
				exit;
			}
		}

		// Handle Step 3 → Confirm Import (persist to DB)
		if ( isset( $_POST['roxytable_import_action'] ) && 'confirm_import' === $_POST['roxytable_import_action'] ) {
			$result = $this->handle_confirm_import();
			if ( is_string( $result ) ) {
				// Error — bounce back to step 3 with message
				$sheet_idx = isset( $_POST['rt_sheet_index'] ) ? intval( $_POST['rt_sheet_index'] ) : 0;
				set_transient( 'roxytable_upload_error_' . get_current_user_id(), $result, 30 );
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import&step=3&sheet_index=' . $sheet_idx . '&_wpnonce=' . wp_create_nonce( 'roxytable_pick_worksheet' ) ) );
				exit;
			}
			// $result is an int (new table ID) — redirect to success screen
			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import&import_success=1' ) );
			exit;
		}

		// Handle Reset / Clear transient
		if ( isset( $_GET['roxytable_import_clear'] ) && '1' === $_GET['roxytable_import_clear'] ) {
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'roxytable_clear_import' ) ) {
				\RoxyTable\Includes\Admin\Import_Handler::clear_stored_data();
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import' ) );
				exit;
			}
		}

		// Handle Save Table data edits (column names and row cells)
		if ( isset( $_POST['roxytable_save_data'] ) ) {
			if ( ! isset( $_POST['roxytable_save_data_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['roxytable_save_data_nonce'] ), 'roxytable_save_data' ) ) {
				wp_die( __( 'Security check failed.', 'roxytable' ) );
			}

			$table_id = isset( $_POST['rt_data_table_id'] ) ? absint( $_POST['rt_data_table_id'] ) : 0;
			if ( $table_id < 1 ) {
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
				exit;
			}

			$columns = \RoxyTable\Includes\Admin\Table_Repository::get_columns( $table_id );
			$posted_names = isset( $_POST['rt_column_names'] ) ? (array) wp_unslash( $_POST['rt_column_names'] ) : [];
			$columns_to_save = [];
			foreach ( $columns as $column ) {
				$column_id = (int) $column['id'];
				$columns_to_save[] = [
					'id'         => $column_id,
					'name'       => $posted_names[ $column_id ] ?? $column['name'],
					'type'       => $column['type'],
					'visible'    => $column['visible'],
					'searchable' => $column['searchable'],
					'sortable'   => $column['sortable'],
					'filterable' => $column['filterable'],
				];
			}
			\RoxyTable\Includes\Admin\Table_Repository::save_columns( $table_id, $columns_to_save );

			$posted_rows = isset( $_POST['rt_rows'] ) ? (array) wp_unslash( $_POST['rt_rows'] ) : [];
			\RoxyTable\Includes\Admin\Table_Repository::update_rows_data( $table_id, $posted_rows );

			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id . '&data_updated=1' ) );
			exit;
		}

		// Handle Save Table edits (from tables edit form)
		if ( isset( $_POST['roxytable_save_table'] ) ) {
			if ( ! isset( $_POST['roxytable_save_table_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['roxytable_save_table_nonce'] ) ), 'roxytable_save_table' ) ) {
				wp_die( __( 'Security check failed.', 'roxytable' ) );
			}

			$table_id = isset( $_POST['rt_table_id'] ) ? intval( $_POST['rt_table_id'] ) : 0;
			if ( $table_id < 1 ) {
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
				exit;
			}

			$meta = [];
			if ( isset( $_POST['rt_table_name'] ) ) {
				$meta['name'] = sanitize_text_field( wp_unslash( $_POST['rt_table_name'] ) );
			}
			if ( isset( $_POST['rt_table_slug'] ) ) {
				$meta['slug'] = sanitize_title( wp_unslash( $_POST['rt_table_slug'] ) );
			}
			if ( isset( $_POST['rt_table_description'] ) ) {
				$meta['description'] = sanitize_textarea_field( wp_unslash( $_POST['rt_table_description'] ) );
			}
			if ( isset( $_POST['rt_table_status'] ) ) {
				$meta['status'] = in_array( $_POST['rt_table_status'], [ 'draft', 'published' ], true ) ? sanitize_key( wp_unslash( $_POST['rt_table_status'] ) ) : 'draft';
			}

			$result = \RoxyTable\Includes\Admin\Table_Repository::update_table_meta( $table_id, $meta );

			$settings_partial = [];
			$template = isset( $_POST['rt_table_template'] ) ? sanitize_key( wp_unslash( $_POST['rt_table_template'] ) ) : '';
			$allowed_templates = [ '', 'indigo', 'obsidian', 'emerald' ];
			if ( in_array( $template, $allowed_templates, true ) ) {
				$settings_partial['template'] = $template;
			}

			if ( isset( $_POST['rt_has_options'] ) ) {
				$settings_partial['search']          = ! empty( $_POST['rt_feat_search'] );
				$settings_partial['sheet_filtering'] = ! empty( $_POST['rt_feat_sheet_filter'] );
				$settings_partial['table_filtering'] = ! empty( $_POST['rt_feat_table_filter'] );
				$settings_partial['sort']            = ! empty( $_POST['rt_feat_sort'] );
				$settings_partial['pagination']      = ! empty( $_POST['rt_feat_pagination'] );
				$settings_partial['visibility']      = ! empty( $_POST['rt_feat_visibility'] );
				$settings_partial['export']          = ! empty( $_POST['rt_feat_export'] );
				$rows_per_page = intval( $_POST['rt_rows_per_page'] ?? 25 );
				$settings_partial['rows_per_page'] = 0 === $rows_per_page ? 0 : min( 500, max( 5, $rows_per_page ) );
				if ( isset( $_POST['rt_filter_columns_configured'] ) ) {
					$column_count = count( \RoxyTable\Includes\Admin\Table_Repository::get_columns( $table_id ) );
					$filter_columns = isset( $_POST['rt_filter_columns'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['rt_filter_columns'] ) ) : [];
					$filter_columns = array_values( array_unique( array_filter( $filter_columns, static function( $index ) use ( $column_count ) {
						return $index < $column_count;
					} ) ) );
					$settings_partial['filter_columns'] = $filter_columns;
				}

				$settings_partial['parse_options'] = [
					'first_row_headers' => ! empty( $_POST['rt_first_row_headers'] ),
					'auto_detect_types' => ! empty( $_POST['rt_auto_detect_types'] ),
					'remove_empty_rows' => ! empty( $_POST['rt_remove_empty_rows'] ),
					'remove_empty_cols' => ! empty( $_POST['rt_remove_empty_cols'] ),
				];
			}

			if ( ! empty( $settings_partial ) ) {
				\RoxyTable\Includes\Admin\Table_Repository::update_table_settings( $table_id, $settings_partial );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id . '&settings_saved=1' ) );
			exit;
		}

		// Handle delete action before any output is sent
		if ( isset( $_GET['page'] ) && 'roxytable-tables' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['action'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$table_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
			if ( $table_id && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'roxytable_delete_table_' . $table_id ) ) {
				\RoxyTable\Includes\Admin\Table_Repository::delete_table( $table_id );
			}
			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
			exit;
		}

		// Handle bulk actions before any output is sent
		if ( isset( $_POST['roxytable_tables_bulk_action'] ) ) {
			if ( ! isset( $_POST['roxytable_bulk_tables_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['roxytable_bulk_tables_nonce'] ) ), 'roxytable_bulk_tables' ) ) {
				wp_die( __( 'Security check failed.', 'roxytable' ) );
			}

			$table_ids = isset( $_POST['rt_table_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['rt_table_ids'] ) ) : [];
			$bulk_action = isset( $_POST['rt_bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['rt_bulk_action'] ) ) : '';
			$bulk_status = isset( $_POST['rt_bulk_status'] ) ? sanitize_key( wp_unslash( $_POST['rt_bulk_status'] ) ) : 'draft';

			if ( 'delete' === $bulk_action ) {
				\RoxyTable\Includes\Admin\Table_Repository::bulk_delete( $table_ids );
			} elseif ( 'change_status' === $bulk_action ) {
			\RoxyTable\Includes\Admin\Table_Repository::bulk_update_status( $table_ids, $bulk_status );
			} elseif ( 'export_csv' === $bulk_action ) {
				$this->send_tables_csv( \RoxyTable\Includes\Admin\Table_Repository::get_export_rows( $table_ids ) );
				exit;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
			exit;
		}
	}

	/**
	 * Handle the confirm-import POST submission.
	 *
	 * Validates the nonce, collects form values, reads full spreadsheet data,
	 * persists rows/columns, stores success data in a transient, and returns
	 * the new table ID on success or an error string on failure.
	 *
	 * @return int|string New table ID, or error message string.
	 */
	private function handle_confirm_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return __( 'You do not have permission to perform this action.', 'roxytable' );
		}

		if ( ! isset( $_POST['roxytable_confirm_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['roxytable_confirm_nonce'] ), 'roxytable_confirm_import' ) ) {
			return __( 'Security check failed. Please try again.', 'roxytable' );
		}

		$sheet_index = isset( $_POST['rt_sheet_index'] ) ? intval( $_POST['rt_sheet_index'] ) : 0;

		// ── Sanitise form fields ──────────────────────────────────────────────
		$table_name   = sanitize_text_field( $_POST['rt_table_name'] ?? '' );
		$table_slug   = sanitize_title( $_POST['rt_table_slug'] ?? '' );
		$description  = sanitize_textarea_field( $_POST['rt_table_description'] ?? '' );
		$status       = in_array( $_POST['rt_table_status'] ?? 'draft', [ 'draft', 'published' ], true )
			? $_POST['rt_table_status'] : 'draft';
		$rows_per_page   = intval( $_POST['rt_rows_per_page'] ?? 25 );
		$rows_per_page   = 0 === $rows_per_page ? 0 : min( 500, max( 5, $rows_per_page ) );
		$custom_css      = wp_strip_all_tags( $_POST['rt_custom_css'] ?? '' );
		$server_side     = ! empty( $_POST['rt_server_side'] );

		$parse_options = [
			'first_row_headers'  => ! empty( $_POST['rt_first_row_headers'] ),
			'auto_detect_types'  => ! empty( $_POST['rt_auto_detect_types'] ),
			'remove_empty_rows'  => ! empty( $_POST['rt_remove_empty_rows'] ),
			'remove_empty_cols'  => ! empty( $_POST['rt_remove_empty_cols'] ),
		];

		$worksheets = \RoxyTable\Includes\Admin\Import_Handler::get_worksheets();

		$settings = [
			'search'          => ! empty( $_POST['rt_feat_search'] ),
			'sheet_filtering' => ! isset( $_POST['rt_feat_sheet_filter'] ) || ! empty( $_POST['rt_feat_sheet_filter'] ),
			'table_filtering' => ! isset( $_POST['rt_feat_table_filter'] ) || ! empty( $_POST['rt_feat_table_filter'] ),
			'sort'            => ! empty( $_POST['rt_feat_sort'] ),
			'pagination'      => ! empty( $_POST['rt_feat_pagination'] ),
			'visibility'      => ! empty( $_POST['rt_feat_visibility'] ),
			'export'          => ! empty( $_POST['rt_feat_export'] ),
			'rows_per_page'   => $rows_per_page,
			'custom_css'      => $custom_css,
			'server_side'     => $server_side,
			'sheets'          => is_array( $worksheets ) ? $worksheets : [],
		];

		if ( '' === $table_name ) {
			return __( 'Table name is required.', 'roxytable' );
		}

		// ── Read full spreadsheet data ────────────────────────────────────────
		$full_data = \RoxyTable\Includes\Admin\Import_Handler::get_full_data( $sheet_index, $parse_options );
		if ( is_string( $full_data ) ) {
			return $full_data; // Error message
		}

		// ── Persist to DB ─────────────────────────────────────────────────────
		$table_id = \RoxyTable\Includes\Admin\Table_Repository::insert_table( [
			'name'        => $table_name,
			'slug'        => $table_slug,
			'description' => $description,
			'status'      => $status,
			'total_rows'  => $full_data['total_rows'],
			'total_cols'  => $full_data['total_cols'],
			'settings'    => $settings,
		] );

		if ( is_wp_error( $table_id ) ) {
			return $table_id->get_error_message();
		}

		\RoxyTable\Includes\Admin\Table_Repository::insert_columns(
			$table_id,
			$full_data['headers'],
			$parse_options['auto_detect_types']
		);

		$rows_inserted = \RoxyTable\Includes\Admin\Table_Repository::insert_rows(
			$table_id,
			$full_data['rows']
		);

		// Update accurate counts after insert
		\RoxyTable\Includes\Admin\Table_Repository::update_counts(
			$table_id,
			$rows_inserted,
			count( $full_data['headers'] )
		);

		// Clean up the temp file
		\RoxyTable\Includes\Admin\Import_Handler::clear_stored_data();

		// Store success data for the success screen transient
		set_transient(
			'roxytable_import_success_' . get_current_user_id(),
			[
				'id'          => $table_id,
				'name'        => $table_name,
				'description' => $description,
				'status'      => $status,
				'rows'        => $rows_inserted,
				'cols'        => count( $full_data['headers'] ),
			],
			MINUTE_IN_SECONDS * 5
		);

		return $table_id;
	}

	/**
	 * Register the top-level menu and all submenus.
	 */
	public function register_menu() {
		// Custom SVG icon base64 encoded (20x20 table icon, color-inheriting)
		$icon_svg = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0iY3VycmVudENvbG9yIj48cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0yIDMuNzVBLjc1Ljc1IDAgMDAyLjc1IDNoMTQuNWEuNzUuNzUgMCAwMS43NS43NXYxMi41YS43NS43NSAwIDAxLS43NS43NUgyLjc1YS43NS43NSAwIDAxLS43NS0uNzVWMy43NXptMS41IDEuNzV2Mi41aDN2LTIuNWgtM3ptNC41IDB2Mi41aDMuNXYtMi41SDh6bTUgMHYyLjVoMy41di0yLjVIMTN6bTMuNSA0aC0zLjV2Mi41aDMuNXYtMi41em0tNSAwSDh2Mi41aDMuNXYtMi41em0tNSAwaC0zdjIuNWgzdi0yLjV6bS0zIDR2Mi41aDN2LTIuNWgtM3ptNC41IDB2Mi41aDMuNXYtMi41SDh6bTUgMHYyLjVoMy41di0yLjVIMTN6IiBjbGlwLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=';

		// Register main top level page
		$main_hook = add_menu_page(
			__( 'RoxyTable', 'roxytable' ),
			__( 'RoxyTable', 'roxytable' ),
			'manage_options',
			'roxytable',
			[ $this, 'render_dashboard' ],
			$icon_svg,
			30
		);
		$this->page_hooks[] = $main_hook;

		// Submenus mapping
		$submenus = [
			[
				'parent'     => 'roxytable',
				'page_title' => __( 'Dashboard', 'roxytable' ),
				'menu_title' => __( 'Dashboard', 'roxytable' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'roxytable',
				'callback'   => [ $this, 'render_dashboard' ],
			],
			[
				'parent'     => 'roxytable',
				'page_title' => __( 'All Tables', 'roxytable' ),
				'menu_title' => __( 'All Tables', 'roxytable' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'roxytable-tables',
				'callback'   => [ $this, 'render_tables' ],
			],
			[
				'parent'     => 'roxytable',
				'page_title' => __( 'Import', 'roxytable' ),
				'menu_title' => __( 'Import', 'roxytable' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'roxytable-import',
				'callback'   => [ $this, 'render_import' ],
			],
			[
				'parent'     => 'roxytable',
				'page_title' => __( 'Create Table', 'roxytable' ),
				'menu_title' => __( 'Create', 'roxytable' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'roxytable-create',
				'callback'   => [ $this, 'render_create' ],
			],
			[
				'parent'     => 'roxytable',
				'page_title' => __( 'Templates', 'roxytable' ),
				'menu_title' => __( 'Templates', 'roxytable' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'roxytable-templates',
				'callback'   => [ $this, 'render_templates' ],
			],
			[
				'parent'     => 'roxytable',
				'page_title' => __( 'Settings', 'roxytable' ),
				'menu_title' => __( 'Settings', 'roxytable' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'roxytable-settings',
				'callback'   => [ $this, 'render_settings' ],
			],
			[
				'parent'     => 'roxytable',
				'page_title' => __( 'Help', 'roxytable' ),
				'menu_title' => __( 'Help', 'roxytable' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'roxytable-help',
				'callback'   => [ $this, 'render_help' ],
			],
		];

		foreach ( $submenus as $submenu ) {
			$sub_hook = add_submenu_page(
				$submenu['parent'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['menu_slug'],
				$submenu['callback']
			);
			if ( $sub_hook ) {
				$this->page_hooks[] = $sub_hook;
			}
		}
	}

	/**
	 * Enqueue assets only on RoxyTable screens.
	 *
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		// Load only on our registered pages
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		// Enqueue WordPress native media library scripts & modal
		wp_enqueue_media();

		// Enqueue Google Font Inter
		wp_enqueue_style( 'roxytable-google-font-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', [], ROXYTABLE_VERSION );

		// Enqueue custom design system stylesheet with automatic cache busting.
		$admin_css = ROXYTABLE_PATH . 'assets/css/admin.css';
		wp_enqueue_style( 'roxytable-admin-css', ROXYTABLE_URL . 'assets/css/admin.css', [], file_exists( $admin_css ) ? filemtime( $admin_css ) : ROXYTABLE_VERSION );

		// Enqueue the admin module with automatic cache busting after JS fixes.
		$admin_js = ROXYTABLE_PATH . 'assets/js/admin.js';
		wp_enqueue_script( 'roxytable-admin-js', ROXYTABLE_URL . 'assets/js/admin.js', [], file_exists( $admin_js ) ? filemtime( $admin_js ) : ROXYTABLE_VERSION, true );
		wp_localize_script( 'roxytable-admin-js', 'roxyAdminData', [
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'max_upload_size'           => wp_max_upload_size(),
			'max_upload_size_formatted' => size_format( wp_max_upload_size() ),
			'allowed_extensions'        => [ 'xlsx', 'xls', 'csv' ],
			'nonces'   => [
				'save_data'        => wp_create_nonce( 'roxytable_save_data' ),
				'save_table'       => wp_create_nonce( 'roxytable_save_table' ),
				'delete_table'     => wp_create_nonce( 'roxytable_delete_table' ),
				'bulk_tables'      => wp_create_nonce( 'roxytable_bulk_tables' ),
				'use_template'     => wp_create_nonce( 'roxytable_use_template' ),
				'upload_file'      => wp_create_nonce( 'roxytable_file_upload' ),
				'confirm_import'   => wp_create_nonce( 'roxytable_confirm_import' ),
				'save_profile'     => wp_create_nonce( 'roxytable_save_profile' ),
				'save_table_image' => wp_create_nonce( 'roxytable_save_table_image' ),
			],
			'i18n'     => [
				'saving'            => __( 'Saving...', 'roxytable' ),
				'saved'             => __( 'Saved successfully!', 'roxytable' ),
				'deleting'          => __( 'Deleting...', 'roxytable' ),
				'deleted'           => __( 'Deleted successfully!', 'roxytable' ),
				'uploading'         => __( 'Uploading file...', 'roxytable' ),
				'error'             => __( 'An error occurred. Please try again.', 'roxytable' ),
				'network_error'     => __( 'Network error: Unable to connect to server. Please check your internet connection.', 'roxytable' ),
				'permission_error'  => __( 'Access Denied: You do not have permission to upload files or your session expired.', 'roxytable' ),
				'file_too_large'    => sprintf( __( 'File size exceeds maximum upload limit of %s.', 'roxytable' ), size_format( wp_max_upload_size() ) ),
				'invalid_file_type' => __( 'Invalid file format. Please select a valid .xlsx, .xls, or .csv file.', 'roxytable' ),
				'no_file_selected'  => __( 'Please select a spreadsheet file to upload.', 'roxytable' ),
			],
		] );

		// Enqueue the frontend interactive table module for admin preview screens
		$frontend_js = ROXYTABLE_PATH . 'assets/js/frontend.js';
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
	 * Enable native ES Module loading for the admin JS script.
	 */
	public function add_module_type( $tag, $handle, $src ) {
		if ( 'roxytable-admin-js' === $handle ) {
			return '<script type="module" src="' . esc_url( $src ) . '" id="' . esc_attr( $handle ) . '-js"></script>';
		}
		return $tag;
	}

	/**
	 * Helper function to return SVG icon markup for sidebar items.
	 */
	public static function get_nav_icon( $slug ) {
		switch ( $slug ) {
			case 'roxytable': // Dashboard
				return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>';
			case 'roxytable-tables': // All Tables
				return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m0-1.125c-.621 0-1.125.504-1.125 1.125m0-1.125v-1.5c0-.621.504-1.125 1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75 0v-1.5m-9.75 1.5H3.375m9.75 0v-1.5m0 1.5H12m0-1.5a1.125 1.125 0 00-1.125-1.125H3.375m9.75 0h7.5c.621 0 1.125.504 1.125 1.125m0-6v-1.5m0 1.5h-7.5a1.125 1.125 0 00-1.125 1.125v1.5m8.625-3h-7.5M12 11.25H3.375m0 3h7.5m-7.5-6h7.5m-7.5-3h7.5" /></svg>';
			case 'roxytable-import': // Import
				return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>';
			case 'roxytable-create': // Create
				return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
			case 'roxytable-templates': // Templates
				return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-1.756A2.25 2.25 0 0119.5 9v.878m0 0a2.247 2.247 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122" /></svg>';
			case 'roxytable-settings': // Settings
				return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
			case 'roxytable-help': // Help
				return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>';
			default:
				return '';
		}
	}

	/**
	 * Render the Dashboard View.
	 */
	public function render_dashboard() {
		$view_file = ROXYTABLE_PATH . 'includes/admin/views/dashboard.php';
		include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
	}

	/**
	 * Render the All Tables View.
	 */
	public function render_tables() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'roxytable' ) );
		}

		// Handle action=edit → render edit view
		if ( isset( $_GET['action'] ) && 'edit' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$table_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
			if ( $table_id < 1 ) {
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
				exit;
			}

			$view_file = ROXYTABLE_PATH . 'includes/admin/views/edit.php';
			include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
			return;
		}

		// Handle action=preview → render preview view
		if ( isset( $_GET['action'] ) && 'preview' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
			$table_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
			if ( $table_id < 1 ) {
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
				exit;
			}

			$view_file = ROXYTABLE_PATH . 'includes/admin/views/preview.php';
			include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
			return;
		}

		if ( isset( $_POST['roxytable_tables_bulk_action'] ) ) {
			if ( ! isset( $_POST['roxytable_bulk_tables_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['roxytable_bulk_tables_nonce'] ) ), 'roxytable_bulk_tables' ) ) {
				wp_die( __( 'Security check failed.', 'roxytable' ) );
			}

			$table_ids = isset( $_POST['rt_table_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['rt_table_ids'] ) ) : [];
			$bulk_action = isset( $_POST['rt_bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['rt_bulk_action'] ) ) : '';
			$bulk_status = isset( $_POST['rt_bulk_status'] ) ? sanitize_key( wp_unslash( $_POST['rt_bulk_status'] ) ) : 'draft';

			if ( 'delete' === $bulk_action ) {
				\RoxyTable\Includes\Admin\Table_Repository::bulk_delete( $table_ids );
			} elseif ( 'change_status' === $bulk_action ) {
				\RoxyTable\Includes\Admin\Table_Repository::bulk_update_status( $table_ids, $bulk_status );
			} elseif ( 'export_csv' === $bulk_action ) {
				$this->send_tables_csv( \RoxyTable\Includes\Admin\Table_Repository::get_export_rows( $table_ids ) );
				exit;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-tables' ) );
			exit;
		}

		$view_file = ROXYTABLE_PATH . 'includes/admin/views/tables.php';
		include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
	}

	/**
	 * Send selected table metadata as a CSV download.
	 *
	 * @param array $rows Export rows from the repository.
	 */
	private function send_tables_csv( array $rows ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="roxy-tables-export.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, [ __( 'ID', 'roxytable' ), __( 'Name', 'roxytable' ), __( 'Slug', 'roxytable' ), __( 'Description', 'roxytable' ), __( 'Status', 'roxytable' ), __( 'Records', 'roxytable' ), __( 'Columns', 'roxytable' ), __( 'Updated', 'roxytable' ) ] );

		foreach ( $rows as $row ) {
			fputcsv( $output, [
				(int) $row['id'],
				(string) ( $row['name'] ?? '' ),
				(string) ( $row['slug'] ?? '' ),
				(string) ( $row['description'] ?? '' ),
				(string) ( $row['status'] ?? '' ),
				(int) ( $row['total_rows'] ?? 0 ),
				(int) ( $row['total_columns'] ?? 0 ),
				(string) ( $row['updated_at'] ?? '' ),
			] );
		}

		fclose( $output );
	}

	/**
	 * Render the Import View (or Success Screen).
	 */
	public function render_import() {
		// ── Success screen ────────────────────────────────────────────────────
		if ( isset( $_GET['import_success'] ) && '1' === $_GET['import_success'] ) {
			$transient_key = 'roxytable_import_success_' . get_current_user_id();
			$success_data  = get_transient( $transient_key );

			if ( $success_data ) {
				delete_transient( $transient_key );
				$view_file = ROXYTABLE_PATH . 'includes/admin/views/import-success.php';
				include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
				return;
			}

			// Transient already consumed or missing — fall through to step 1
			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import' ) );
			exit;
		}

		// ── Wizard steps 1-3 ─────────────────────────────────────────────────
		$step = isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1;

		// If transient data doesn't exist, force step 1
		$stored = \RoxyTable\Includes\Admin\Import_Handler::get_stored_data();
		if ( ! $stored && $step > 1 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import&step=1' ) );
			exit;
		}

		$upload_error = get_transient( 'roxytable_upload_error_' . get_current_user_id() );
		if ( $upload_error ) {
			delete_transient( 'roxytable_upload_error_' . get_current_user_id() );
		}

		$worksheets = [];
		$preview    = [];
		$sheet_idx  = null;

		if ( 2 === $step ) {
			$worksheets = \RoxyTable\Includes\Admin\Import_Handler::get_worksheets();
			if ( is_string( $worksheets ) ) {
				$upload_error = $worksheets;
				$step = 1;
			}
		} elseif ( 3 === $step ) {
			// Verify nonce before loading preview
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'roxytable_pick_worksheet' ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=roxytable-import&step=2' ) );
				exit;
			}
			$sheet_idx = isset( $_GET['sheet_index'] ) ? intval( $_GET['sheet_index'] ) : 0;
			$preview   = \RoxyTable\Includes\Admin\Import_Handler::get_preview( $sheet_idx );
			if ( is_string( $preview ) ) {
				$upload_error = $preview;
				$step = 2;
				$worksheets = \RoxyTable\Includes\Admin\Import_Handler::get_worksheets();
			}
		}

		$view_file = ROXYTABLE_PATH . 'includes/admin/views/import.php';
		include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
	}

	/**
	 * Render the Create View.
	 */
	public function render_create() {
		$view_file = ROXYTABLE_PATH . 'includes/admin/views/create.php';
		include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
	}

	/**
	 * Render the Templates View.
	 */
	public function render_templates() {
		$view_file = ROXYTABLE_PATH . 'includes/admin/views/templates.php';
		include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
	}

	/**
	 * Render the Settings View.
	 */
	public function render_settings() {
		$view_file = ROXYTABLE_PATH . 'includes/admin/views/settings.php';
		include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
	}

	/**
	 * Render the Help View.
	 */
	public function render_help() {
		$view_file = ROXYTABLE_PATH . 'includes/admin/views/help.php';
		include ROXYTABLE_PATH . 'includes/admin/views/layout.php';
	}

	// =========================================================================
	// Native WordPress AJAX Endpoints
	// =========================================================================

	/**
	 * AJAX endpoint: Save Table Data (column names & row cell values).
	 */
	public function ajax_save_data() {
		Logger::info( 'AJAX save table data initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_save_data' ) ) {
			Logger::error( 'Save table data security check failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Save table data failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied.', 'roxytable' ),
			], 403 );
			return;
		}

		$table_id = isset( $_POST['rt_data_table_id'] ) ? absint( $_POST['rt_data_table_id'] ) : ( isset( $_REQUEST['rt_data_table_id'] ) ? absint( $_REQUEST['rt_data_table_id'] ) : 0 );
		if ( $table_id < 1 ) {
			Logger::error( 'Save table data failed: Invalid table ID ' . $table_id );
			wp_send_json_error( [
				'code'    => 'invalid_table_id',
				'message' => __( 'Invalid table ID.', 'roxytable' ),
			], 400 );
			return;
		}

		$table = Table_Repository::get_table( $table_id );
		if ( ! $table ) {
			Logger::error( 'Save table data failed: Table not found ' . $table_id );
			wp_send_json_error( [
				'code'    => 'table_not_found',
				'message' => __( 'Table not found.', 'roxytable' ),
			], 404 );
			return;
		}

		$columns = Table_Repository::get_columns( $table_id );
		$posted_names = isset( $_POST['rt_column_names'] ) ? (array) wp_unslash( $_POST['rt_column_names'] ) : ( isset( $_REQUEST['rt_column_names'] ) ? (array) wp_unslash( $_REQUEST['rt_column_names'] ) : [] );
		$columns_to_save = [];
		foreach ( $columns as $column ) {
			$column_id = (int) $column['id'];
			$columns_to_save[] = [
				'id'         => $column_id,
				'name'       => $posted_names[ $column_id ] ?? $column['name'],
				'type'       => $column['type'],
				'visible'    => $column['visible'],
				'searchable' => $column['searchable'],
				'sortable'   => $column['sortable'],
				'filterable' => $column['filterable'],
			];
		}
		Table_Repository::save_columns( $table_id, $columns_to_save );

		$posted_rows = isset( $_POST['rt_rows'] ) ? (array) wp_unslash( $_POST['rt_rows'] ) : ( isset( $_REQUEST['rt_rows'] ) ? (array) wp_unslash( $_REQUEST['rt_rows'] ) : [] );
		$written = Table_Repository::update_rows_data( $table_id, $posted_rows );

		Logger::info( 'Table data saved successfully.', [
			'table_id'     => $table_id,
			'rows_written' => $written,
		] );

		wp_send_json_success( [
			'message'  => sprintf( __( 'Saved %d rows successfully!', 'roxytable' ), $written ),
			'table_id' => $table_id,
		] );
		return;
	}

	/**
	 * AJAX endpoint: Save Table Metadata (Name, Slug, Description, Status, Template).
	 */
	public function ajax_save_table() {
		Logger::info( 'AJAX save table metadata initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) && isset( $_REQUEST['roxytable_save_table_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['roxytable_save_table_nonce'] ) );
		}
		if ( empty( $nonce ) && isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		}
		if ( empty( $nonce ) && isset( $_POST['roxytable_save_table_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['roxytable_save_table_nonce'] ) );
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_save_table' ) ) {
			Logger::error( 'Save table metadata security check failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Save table metadata failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied.', 'roxytable' ),
			], 403 );
			return;
		}

		$table_id = isset( $_POST['rt_table_id'] ) ? absint( $_POST['rt_table_id'] ) : ( isset( $_REQUEST['rt_table_id'] ) ? absint( $_REQUEST['rt_table_id'] ) : 0 );
		if ( $table_id < 1 ) {
			Logger::error( 'Save table metadata failed: Invalid table ID ' . $table_id );
			wp_send_json_error( [
				'code'    => 'invalid_table_id',
				'message' => __( 'Invalid table ID.', 'roxytable' ),
			], 400 );
			return;
		}

		$table = Table_Repository::get_table( $table_id );
		if ( ! $table ) {
			Logger::error( 'Save table metadata failed: Table not found ' . $table_id );
			wp_send_json_error( [
				'code'    => 'table_not_found',
				'message' => __( 'Table not found.', 'roxytable' ),
			], 404 );
			return;
		}

		$meta = [];
		if ( isset( $_POST['rt_table_name'] ) || isset( $_REQUEST['rt_table_name'] ) ) {
			$raw_name = $_POST['rt_table_name'] ?? $_REQUEST['rt_table_name'];
			$meta['name'] = sanitize_text_field( wp_unslash( $raw_name ) );
		}
		if ( isset( $_POST['rt_table_slug'] ) || isset( $_REQUEST['rt_table_slug'] ) ) {
			$raw_slug = $_POST['rt_table_slug'] ?? $_REQUEST['rt_table_slug'];
			$meta['slug'] = sanitize_title( wp_unslash( $raw_slug ) );
		}
		if ( isset( $_POST['rt_table_description'] ) || isset( $_REQUEST['rt_table_description'] ) ) {
			$raw_desc = $_POST['rt_table_description'] ?? $_REQUEST['rt_table_description'];
			$meta['description'] = sanitize_textarea_field( wp_unslash( $raw_desc ) );
		}
		if ( isset( $_POST['rt_table_status'] ) || isset( $_REQUEST['rt_table_status'] ) ) {
			$raw_status = $_POST['rt_table_status'] ?? $_REQUEST['rt_table_status'];
			$meta['status'] = in_array( $raw_status, [ 'draft', 'published' ], true ) ? sanitize_key( wp_unslash( $raw_status ) ) : 'draft';
		}

		$result = Table_Repository::update_table_meta( $table_id, $meta );
		if ( is_wp_error( $result ) ) {
			Logger::error( 'Save table metadata failed: ' . $result->get_error_message() );
			wp_send_json_error( [
				'code'    => $result->get_error_code(),
				'message' => $result->get_error_message(),
			], 400 );
			return;
		}

		$settings_partial = [];
		$raw_template = $_POST['rt_table_template'] ?? ( $_REQUEST['rt_table_template'] ?? null );
		if ( null !== $raw_template ) {
			$template = sanitize_key( wp_unslash( $raw_template ) );
			$allowed_templates = [ '', 'indigo', 'obsidian', 'emerald' ];
			if ( in_array( $template, $allowed_templates, true ) ) {
				$settings_partial['template'] = $template;
			}
		}

		if ( isset( $_POST['rt_has_options'] ) || isset( $_REQUEST['rt_has_options'] ) ) {
			$source = ! empty( $_POST ) ? $_POST : $_REQUEST;
			$settings_partial['search']          = ! empty( $source['rt_feat_search'] );
			$settings_partial['sheet_filtering'] = ! empty( $source['rt_feat_sheet_filter'] );
			$settings_partial['table_filtering'] = ! empty( $source['rt_feat_table_filter'] );
			$settings_partial['sort']            = ! empty( $source['rt_feat_sort'] );
			$settings_partial['pagination']      = ! empty( $source['rt_feat_pagination'] );
			$settings_partial['visibility']      = ! empty( $source['rt_feat_visibility'] );
			$settings_partial['export']          = ! empty( $source['rt_feat_export'] );
			$rows_per_page = intval( $source['rt_rows_per_page'] ?? 25 );
			$settings_partial['rows_per_page'] = 0 === $rows_per_page ? 0 : min( 500, max( 5, $rows_per_page ) );

			if ( isset( $source['rt_filter_columns_configured'] ) ) {
				$column_count = count( Table_Repository::get_columns( $table_id ) );
				$filter_columns = isset( $source['rt_filter_columns'] ) ? array_map( 'absint', (array) wp_unslash( $source['rt_filter_columns'] ) ) : [];
				$settings_partial['filter_columns'] = array_values( array_unique( array_filter( $filter_columns, static function( $index ) use ( $column_count ) {
					return $index < $column_count;
				} ) ) );
			}

			$settings_partial['parse_options'] = [
				'first_row_headers' => ! empty( $source['rt_first_row_headers'] ),
				'auto_detect_types' => ! empty( $source['rt_auto_detect_types'] ),
				'remove_empty_rows' => ! empty( $source['rt_remove_empty_rows'] ),
				'remove_empty_cols' => ! empty( $source['rt_remove_empty_cols'] ),
			];
		}

		if ( ! empty( $settings_partial ) ) {
			Table_Repository::update_table_settings( $table_id, $settings_partial );
		}

		$updated_table = Table_Repository::get_table( $table_id );

		Logger::info( 'Table settings updated successfully.', [
			'table_id' => $table_id,
		] );

		wp_send_json_success( [
			'message' => __( 'Table settings updated successfully!', 'roxytable' ),
			'table'   => $updated_table,
		] );
		return;
	}

	/**
	 * AJAX endpoint: Delete single table.
	 */
	public function ajax_delete_table() {
		Logger::info( 'AJAX delete table initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$table_id = isset( $_POST['table_id'] ) ? absint( $_POST['table_id'] ) : ( isset( $_REQUEST['table_id'] ) ? absint( $_REQUEST['table_id'] ) : 0 );
		if ( $table_id < 1 ) {
			Logger::error( 'Delete table failed: Invalid table ID ' . $table_id );
			wp_send_json_error( [
				'code'    => 'invalid_table_id',
				'message' => __( 'Invalid table ID.', 'roxytable' ),
			], 400 );
			return;
		}

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) || ( ! wp_verify_nonce( $nonce, 'roxytable_delete_table_' . $table_id ) && ! wp_verify_nonce( $nonce, 'roxytable_delete_table' ) ) ) {
			Logger::error( 'Delete table security check failed: Invalid nonce for table ' . $table_id );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Delete table failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied.', 'roxytable' ),
			], 403 );
			return;
		}

		$table = Table_Repository::get_table( $table_id );
		if ( ! $table ) {
			Logger::error( 'Delete table failed: Table not found ' . $table_id );
			wp_send_json_error( [
				'code'    => 'table_not_found',
				'message' => __( 'Table not found.', 'roxytable' ),
			], 404 );
			return;
		}

		$success = Table_Repository::delete_table( $table_id );
		if ( ! $success ) {
			Logger::error( 'Delete table failed in database for table ID ' . $table_id );
			wp_send_json_error( [
				'code'    => 'delete_failed',
				'message' => __( 'Failed to delete table.', 'roxytable' ),
			], 500 );
			return;
		}

		Logger::info( 'Table deleted successfully.', [
			'table_id' => $table_id,
		] );

		wp_send_json_success( [
			'message'  => __( 'Table deleted successfully!', 'roxytable' ),
			'table_id' => $table_id,
		] );
		return;
	}

	/**
	 * AJAX endpoint: Bulk actions (Delete / Change status).
	 */
	public function ajax_bulk_tables() {
		Logger::info( 'AJAX bulk tables action initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_bulk_tables' ) ) {
			Logger::error( 'Bulk tables security check failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Bulk tables failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied.', 'roxytable' ),
			], 403 );
			return;
		}

		$raw_ids     = $_POST['rt_table_ids'] ?? ( $_REQUEST['rt_table_ids'] ?? [] );
		$table_ids   = array_filter( array_map( 'absint', (array) wp_unslash( $raw_ids ) ) );
		$raw_action  = $_POST['rt_bulk_action'] ?? ( $_REQUEST['rt_bulk_action'] ?? '' );
		$bulk_action = sanitize_key( wp_unslash( $raw_action ) );
		$raw_status  = $_POST['rt_bulk_status'] ?? ( $_REQUEST['rt_bulk_status'] ?? 'draft' );
		$bulk_status = sanitize_key( wp_unslash( $raw_status ) );

		if ( empty( $table_ids ) ) {
			Logger::error( 'Bulk tables failed: No tables selected.' );
			wp_send_json_error( [
				'code'    => 'no_tables_selected',
				'message' => __( 'No tables selected.', 'roxytable' ),
			], 400 );
			return;
		}

		if ( 'delete' === $bulk_action ) {
			$count = Table_Repository::bulk_delete( $table_ids );
			Logger::info( 'Bulk delete completed successfully.', [
				'count'     => $count,
				'table_ids' => $table_ids,
			] );
			wp_send_json_success( [
				'message'   => sprintf( __( '%d tables deleted.', 'roxytable' ), $count ),
				'action'    => 'delete',
				'table_ids' => $table_ids,
			] );
			return;
		} elseif ( 'change_status' === $bulk_action ) {
			if ( ! in_array( $bulk_status, [ 'draft', 'published' ], true ) ) {
				$bulk_status = 'draft';
			}
			$count = Table_Repository::bulk_update_status( $table_ids, $bulk_status );
			Logger::info( 'Bulk status update completed successfully.', [
				'count'     => $count,
				'status'    => $bulk_status,
				'table_ids' => $table_ids,
			] );
			wp_send_json_success( [
				'message'   => sprintf( __( '%d tables updated to %s.', 'roxytable' ), $count, $bulk_status ),
				'action'    => 'status',
				'status'    => $bulk_status,
				'table_ids' => $table_ids,
			] );
			return;
		}

		Logger::error( 'Bulk tables failed: Invalid bulk action "' . $bulk_action . '"' );
		wp_send_json_error( [
			'code'    => 'invalid_bulk_action',
			'message' => __( 'Invalid bulk action.', 'roxytable' ),
		], 400 );
		return;
	}

	/**
	 * AJAX endpoint: Create table from template.
	 */
	public function ajax_use_template() {
		Logger::info( 'AJAX use template initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_use_template' ) ) {
			Logger::error( 'Use template security check failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Use template failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied.', 'roxytable' ),
			], 403 );
			return;
		}

		$raw_tpl  = $_POST['template'] ?? ( $_REQUEST['template'] ?? '' );
		$template = sanitize_key( wp_unslash( $raw_tpl ) );
		$templates = [
			'indigo'   => [ 'name' => 'Indigo Clean Table', 'columns' => [ 'Institution', 'Website', 'Description' ] ],
			'obsidian' => [ 'name' => 'Dark Minimal Table', 'columns' => [ 'Name', 'Value', 'Description' ] ],
			'emerald'  => [ 'name' => 'Emerald Grid Table', 'columns' => [ 'Name', 'Category', 'Description' ] ],
		];

		if ( ! isset( $templates[ $template ] ) ) {
			Logger::error( 'Use template failed: Invalid template key "' . $template . '"' );
			wp_send_json_error( [
				'code'    => 'invalid_template',
				'message' => __( 'Invalid template.', 'roxytable' ),
			], 400 );
			return;
		}

		$definition = $templates[ $template ];
		$table_id   = Table_Repository::insert_table( [
			'name'        => $definition['name'],
			'description' => __( 'Created from a RoxyTable design template.', 'roxytable' ),
			'status'      => 'draft',
			'total_rows'  => 1,
			'total_cols'  => count( $definition['columns'] ),
			'settings'    => [ 'template' => $template, 'pagination' => true, 'rows_per_page' => 25 ],
		] );

		if ( is_wp_error( $table_id ) ) {
			Logger::error( 'Use template failed to insert table: ' . $table_id->get_error_message() );
			wp_send_json_error( [
				'code'    => $table_id->get_error_code(),
				'message' => $table_id->get_error_message(),
			], 400 );
			return;
		}

		Table_Repository::insert_columns( $table_id, $definition['columns'], false );
		Table_Repository::insert_rows( $table_id, [ array_fill( 0, count( $definition['columns'] ), '' ) ] );

		Logger::info( 'Template table created successfully.', [
			'table_id' => $table_id,
			'template' => $template,
		] );

		wp_send_json_success( [
			'message'      => __( 'Template table created successfully!', 'roxytable' ),
			'table_id'     => $table_id,
			'redirect_url' => admin_url( 'admin.php?page=roxytable-tables&action=edit&id=' . $table_id . '&template_created=1' ),
		] );
		return;
	}

	/**
	 * AJAX endpoint: File upload (Step 1 -> Step 2 transition).
	 */
	public function ajax_upload_file() {
		Logger::info( 'AJAX upload request initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) && isset( $_REQUEST['roxytable_upload_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['roxytable_upload_nonce'] ) );
		}
		if ( empty( $nonce ) && isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		}
		if ( empty( $nonce ) && isset( $_POST['roxytable_upload_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['roxytable_upload_nonce'] ) );
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_file_upload' ) ) {
			Logger::error( 'AJAX upload security check failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page and try again.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'AJAX upload failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied: You do not have permission to upload files.', 'roxytable' ),
			], 403 );
			return;
		}

		$upload_result = Import_Handler::handle_upload();
		if ( is_wp_error( $upload_result ) ) {
			$err_code = $upload_result->get_error_code();
			$status_code = 400;
			if ( 'permission_denied' === $err_code || 'security_check_failed' === $err_code ) {
				$status_code = 403;
			} elseif ( 'file_too_large' === $err_code ) {
				$status_code = 413;
			}

			wp_send_json_error( [
				'code'    => $err_code,
				'message' => $upload_result->get_error_message(),
			], $status_code );
			return;
		} elseif ( true !== $upload_result ) {
			$msg = is_string( $upload_result ) ? $upload_result : __( 'File upload failed.', 'roxytable' );
			wp_send_json_error( [
				'code'    => 'upload_failed',
				'message' => $msg,
			], 400 );
			return;
		}

		$worksheets = Import_Handler::get_worksheets();
		if ( is_wp_error( $worksheets ) ) {
			wp_send_json_error( [
				'code'    => $worksheets->get_error_code(),
				'message' => $worksheets->get_error_message(),
			], 422 );
			return;
		} elseif ( is_string( $worksheets ) ) {
			wp_send_json_error( [
				'code'    => 'worksheet_parse_error',
				'message' => $worksheets,
			], 422 );
			return;
		}

		Logger::info( 'AJAX upload completed successfully.', [
			'worksheets_count' => count( $worksheets ),
		] );

		wp_send_json_success( [
			'message'    => __( 'File uploaded successfully!', 'roxytable' ),
			'worksheets' => $worksheets,
			'step'       => 2,
		] );
		return;
	}

	/**
	 * AJAX endpoint: Confirm import (Step 3 -> Persist to DB).
	 */
	public function ajax_confirm_import() {
		Logger::info( 'AJAX confirm import initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) && isset( $_REQUEST['roxytable_confirm_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['roxytable_confirm_nonce'] ) );
		}
		if ( empty( $nonce ) && isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		}
		if ( empty( $nonce ) && isset( $_POST['roxytable_confirm_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['roxytable_confirm_nonce'] ) );
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_confirm_import' ) ) {
			Logger::error( 'AJAX confirm import failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'AJAX confirm import failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied: You do not have permission to import tables.', 'roxytable' ),
			], 403 );
			return;
		}

		$result = $this->handle_confirm_import();
		if ( is_wp_error( $result ) ) {
			Logger::error( 'Confirm import failed: ' . $result->get_error_message() );
			wp_send_json_error( [
				'code'    => $result->get_error_code(),
				'message' => $result->get_error_message(),
			], 400 );
			return;
		} elseif ( is_string( $result ) ) {
			Logger::error( 'Confirm import failed: ' . $result );
			wp_send_json_error( [
				'code'    => 'import_failed',
				'message' => $result,
			], 400 );
			return;
		}

		Logger::info( 'Table successfully imported into database.', [
			'table_id' => $result,
		] );

		wp_send_json_success( [
			'message'      => __( 'Table imported successfully!', 'roxytable' ),
			'table_id'     => $result,
			'redirect_url' => admin_url( 'admin.php?page=roxytable-import&import_success=1' ),
		] );
		return;
	}

	/**
	 * AJAX endpoint: Save developer profile settings.
	 */
	public function ajax_save_profile() {
		Logger::info( 'AJAX save profile initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_save_profile' ) ) {
			Logger::error( 'Save profile security check failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Save profile failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( isset( $_POST['name'] ) || isset( $_REQUEST['name'] ) ) {
			$val = $_POST['name'] ?? $_REQUEST['name'];
			update_option( 'roxytable_dev_name', sanitize_text_field( wp_unslash( $val ) ) );
		}
		if ( isset( $_POST['title'] ) || isset( $_REQUEST['title'] ) ) {
			$val = $_POST['title'] ?? $_REQUEST['title'];
			update_option( 'roxytable_dev_title', sanitize_text_field( wp_unslash( $val ) ) );
		}
		if ( isset( $_POST['location'] ) || isset( $_REQUEST['location'] ) ) {
			$val = $_POST['location'] ?? $_REQUEST['location'];
			update_option( 'roxytable_dev_location', sanitize_text_field( wp_unslash( $val ) ) );
		}

		Logger::info( 'Developer profile updated successfully.' );

		wp_send_json_success( [
			'message'    => __( 'Profile updated successfully!', 'roxytable' ),
			'avatar_url' => ROXYTABLE_URL . 'assets/images/abdulpassport.jpeg',
			'name'       => get_option( 'roxytable_dev_name', 'Abdulrahman Sulaiman' ),
			'title'      => get_option( 'roxytable_dev_title', 'WordPress Developer with 4+ years working Experience' ),
			'location'   => get_option( 'roxytable_dev_location', 'Live in Nigeria' ),
		] );
		return;
	}

	/**
	 * AJAX endpoint: Save table image thumbnail URL.
	 */
	public function ajax_save_table_image() {
		Logger::info( 'AJAX save table image initiated.', [
			'user_id' => get_current_user_id(),
		] );

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'roxytable_save_table_image' ) ) {
			Logger::error( 'Save table image security check failed: Invalid nonce.' );
			wp_send_json_error( [
				'code'    => 'security_check_failed',
				'message' => __( 'Security verification failed or session expired. Please refresh the page.', 'roxytable' ),
			], 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			Logger::error( 'Save table image failed: User lacks manage_options capability.' );
			wp_send_json_error( [
				'code'    => 'permission_denied',
				'message' => __( 'Permission denied.', 'roxytable' ),
			], 403 );
			return;
		}

		$table_id  = isset( $_POST['table_id'] ) ? absint( $_POST['table_id'] ) : ( isset( $_REQUEST['table_id'] ) ? absint( $_REQUEST['table_id'] ) : 0 );
		$raw_image = $_POST['image_url'] ?? ( $_REQUEST['image_url'] ?? '' );
		$image_url = esc_url_raw( wp_unslash( $raw_image ) );
		$raw_att   = $_POST['attachment_id'] ?? ( $_REQUEST['attachment_id'] ?? 0 );
		$att_id    = absint( $raw_att );

		if ( $table_id < 1 ) {
			Logger::error( 'Save table image failed: Invalid table ID ' . $table_id );
			wp_send_json_error( [
				'code'    => 'invalid_table_id',
				'message' => __( 'Invalid table ID.', 'roxytable' ),
			], 400 );
			return;
		}

		$table = Table_Repository::get_table( $table_id );
		if ( ! $table ) {
			Logger::error( 'Save table image failed: Table not found ' . $table_id );
			wp_send_json_error( [
				'code'    => 'table_not_found',
				'message' => __( 'Table not found.', 'roxytable' ),
			], 404 );
			return;
		}

		$settings_update = [ 'image' => $image_url ];
		if ( $att_id > 0 ) {
			$settings_update['image_id'] = $att_id;
		}
		Table_Repository::update_table_settings( $table_id, $settings_update );

		Logger::info( 'Table thumbnail image updated successfully.', [
			'table_id'      => $table_id,
			'image_url'     => $image_url,
			'attachment_id' => $att_id,
		] );

		wp_send_json_success( [
			'message'       => __( 'Table thumbnail image updated successfully!', 'roxytable' ),
			'table_id'      => $table_id,
			'image_url'     => $image_url,
			'attachment_id' => $att_id,
		] );
		return;
	}
}
