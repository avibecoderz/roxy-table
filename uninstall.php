<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Drops custom tables and cleans up settings options.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Verify capability to execute uninstall actions.
if ( ! current_user_can( 'manage_options' ) ) {
	exit;
}

global $wpdb;

// Drop custom database tables.
$tables = [
	$wpdb->prefix . 'roxy_rows',
	$wpdb->prefix . 'roxy_columns',
	$wpdb->prefix . 'roxy_tables',
];

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS $table" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// Delete plugin metadata/options.
delete_option( 'roxytable_db_version' );
