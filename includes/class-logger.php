<?php
namespace RoxyTable\Includes;

/**
 * Central Logger for RoxyTable.
 * Handles event logging to PHP error_log and an in-database log buffer for administrative review.
 */
class Logger {

	const OPTION_KEY = 'roxytable_recent_logs';
	const MAX_LOGS   = 100;

	const LEVEL_DEBUG   = 'DEBUG';
	const LEVEL_INFO    = 'INFO';
	const LEVEL_WARNING = 'WARNING';
	const LEVEL_ERROR   = 'ERROR';

	/**
	 * Log a message with a specific severity level and context.
	 *
	 * @param string $level   Log level (DEBUG, INFO, WARNING, ERROR).
	 * @param string $message Descriptive log message.
	 * @param array  $context Additional contextual data (e.g. file size, mime, user ID).
	 */
	public static function log( string $level, string $message, array $context = [] ) {
		$level = strtoupper( $level );
		$entry = [
			'timestamp' => current_time( 'mysql' ),
			'time_unix' => time(),
			'level'     => $level,
			'message'   => $message,
			'context'   => self::sanitize_context( $context ),
			'user_id'   => get_current_user_id(),
		];

		// Format context for error_log
		$context_str = ! empty( $entry['context'] ) ? ' ' . wp_json_encode( $entry['context'] ) : '';
		$log_line    = sprintf( '[RoxyTable][%s] %s%s', $level, $message, $context_str );

		// Always write WARNING and ERROR to error_log. Write DEBUG and INFO if WP_DEBUG is true.
		if ( in_array( $level, [ self::LEVEL_WARNING, self::LEVEL_ERROR ], true ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			error_log( $log_line ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		// Store in option buffer for retrieval
		self::append_to_buffer( $entry );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public static function info( string $message, array $context = [] ) {
		self::log( self::LEVEL_INFO, $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public static function warning( string $message, array $context = [] ) {
		self::log( self::LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public static function error( string $message, array $context = [] ) {
		self::log( self::LEVEL_ERROR, $message, $context );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public static function debug( string $message, array $context = [] ) {
		self::log( self::LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Retrieve recent log entries.
	 *
	 * @param int $limit Max entries to return.
	 * @return array
	 */
	public static function get_recent_logs( int $limit = 50 ) : array {
		$logs = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $logs ) ) {
			return [];
		}
		return array_slice( array_reverse( $logs ), 0, $limit );
	}

	/**
	 * Clear all stored logs.
	 */
	public static function clear_logs() {
		delete_option( self::OPTION_KEY );
	}

	/**
	 * Append entry to in-database log buffer with rotation.
	 *
	 * @param array $entry
	 */
	private static function append_to_buffer( array $entry ) {
		$logs = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $logs ) ) {
			$logs = [];
		}

		$logs[] = $entry;

		// Keep only the most recent logs
		if ( count( $logs ) > self::MAX_LOGS ) {
			$logs = array_slice( $logs, -self::MAX_LOGS );
		}

		update_option( self::OPTION_KEY, $logs, false );
	}

	/**
	 * Sanitize context array to prevent storing large objects or sensitive secrets.
	 *
	 * @param array $context
	 * @return array
	 */
	private static function sanitize_context( array $context ) : array {
		$sanitized = [];
		foreach ( $context as $k => $v ) {
			if ( is_string( $v ) || is_numeric( $v ) || is_bool( $v ) || is_null( $v ) ) {
				$sanitized[ $k ] = $v;
			} elseif ( is_array( $v ) ) {
				$sanitized[ $k ] = self::sanitize_context( $v );
			} elseif ( is_object( $v ) && method_exists( $v, '__toString' ) ) {
				$sanitized[ $k ] = (string) $v;
			} elseif ( is_object( $v ) ) {
				$sanitized[ $k ] = get_class( $v );
			}
		}
		return $sanitized;
	}
}
