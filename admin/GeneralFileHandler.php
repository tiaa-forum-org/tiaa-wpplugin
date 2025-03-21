<?php
/**
 * Handles secure file downloads and CSV generation via WordPress admin-post requests.
 *
 * This method processes secure file requests by verifying nonce, identifying
 * the requested file type or database table for CSV generation, and outputting
 * the appropriate content for download. If the request is invalid or an error occurs,
 * it sends the appropriate HTTP status code and error message.
 *
 * Supported download types:
 * - `log` : Static log files (e.g., for audit purposes).
 * - `csv` : Dynamically generated CSV files from database tables.
 *
 * If the `type` query parameter is invalid or missing, or if required data is not provided,
 * the server responds with an appropriate `400 Bad Request` or other error status code.
 *
 * Headers are configured to facilitate browser-triggered downloads with
 * `Content-Disposition: attachment`.
 *
 * @since 0.0.3
 *
 * @return void
 * @throws \Exception If a server-side error occurs.
 *
 * @package TIAAPlugin\Admin
 * @author Lew Grothe
 * @author TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 */
namespace TIAAPlugin\Admin;

use TIAAPlugin\lib\PluginUtil;
/**
 * GeneralFileHandler class to handle secure file delivery and CSV creation.
 *
 * This class validates and processes admin-post requests to securely serve static
 * or dynamically-generated content for download. It sets appropriate headers,
 * checks permissions, and handles errors gracefully, ensuring reliability and security.
 *
 * @since 0.0.3
 * @package TIAAPlugin\Admin
 */
class GeneralFileHandler {
	use PluginUtil;
	/**
	 * Handles file downloads securely by processing requests via admin-post.
	 *
	 * This method verifies user permissions and the nonce before serving requested
	 * files. It determines the file type (`log` for static files or `csv` for dynamic
	 * CSV generation) and manages output accordingly. If errors occur (e.g., invalid
	 * file type, missing parameters), it terminates execution with the appropriate
	 * HTTP status code and error message.
	 *
	 * Operations:
	 * - Serves static files like logs.
	 * - Generates and serves CSV files dynamically from a database table.
	 *
	 * @since 0.0.3
	 *
	 * @return void Outputs the requested file or an error response and exits.
	 */
	public static function tiaa_serve_file() : void {
		self::log_debug( 'tiaa_serve_file() called...' );
		// Start output buffering to prevent premature headers
		ob_start();

		try {
			// Verify nonce and authorize user
			if ( ! isset( $_GET['_wpnonce'] )
			     || ! wp_verify_nonce( $_GET['_wpnonce'], 'admin_post_tiaa_secure_file' ) ) {
				header( 'HTTP/1.1 403 Forbidden' );
				echo json_encode( [ 'error' => 'Unauthorized request.' ] );
				exit;
			}

			// Get file path or dynamic data type (file-based or generated content)
			$file_type = $_GET['type'] ?? '';
			switch ( $file_type ) {
				// Serve pre-existing log files
				case 'log':
					$file_path = LogSettings::get_log_file();
					if ( ! is_readable( $file_path )) {
						header( 'HTTP/1.1 400 Bad Request' );
						echo json_encode( [ 'error' => 'Missing log file for export.' ] );
						exit;
					}
					$file_name = basename( $file_path );
					self::output_file( $file_path, 'text/plain', $file_name );
					break;

				// Handle dynamically generating CSV files (e.g. database tables)
				case 'csv':
					$table_name = $_GET['table'] ?? '';
					if ( empty( $table_name ) ) {
						header( 'HTTP/1.1 400 Bad Request' );
						echo json_encode( [ 'error' => 'Missing table name for export.' ] );
						exit;
					}
					self::output_csv_from_database( $table_name );
					break;

				// Default: unsupported type
				default:
					header( 'HTTP/1.1 400 Bad Request' );
					echo json_encode( [ 'error' => 'Invalid file type.' ] );
					exit;
			}
		} catch ( \Exception $e ) {
			// Handle potential server-side errors
			header( 'HTTP/1.1 500 Internal Server Error' );
			echo json_encode( [ 'error' => $e->getMessage() ] );
		} finally {
			// Ensure no leftover output
			ob_end_clean();
		}
		exit;
	}
	/**
	 * Serves static files securely by validating and delivering the file.
	 *
	 * This method sends appropriate HTTP headers to facilitate download and securely
	 * outputs the file's content for browser-based downloads. If the file does not exist,
	 * it returns a `404 Not Found` status.
	 *
	 * Usage:
	 * - Intended for delivering log files or other static resources.
	 *
	 * @param string $file_path     Absolute path to the file to serve.
	 * @param string $content_type  MIME type of the file (e.g., text/plain).
	 * @param string $file_name     File name for the `Content-Disposition` header.
	 *
	 * @return void Outputs the file content or an error response and exits.
	 *@since 0.0.3
	 *
	 */
	private static function output_file( string $file_path, string $content_type, string $file_name ) : void{
		if ( ! file_exists( $file_path ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			echo json_encode( [ 'error' => 'File not found.' ] );
			exit;
		}

		// Serve file with appropriate headers
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_name ) . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		// Clean buffer and serve file directly
		ob_clean();
		flush();
		readfile( $file_path );
	}

	/**
	 * Dynamically generates and serves a CSV file from the specified database table.
	 *
	 * This method queries the specified database table (`$table_name`) and outputs
	 * its content as a CSV file for download. It sets proper HTTP headers for the
	 * CSV file format and triggers a download in the browser. If the table is invalid
	 * or data retrieval fails, it terminates with an error response.
	 *
	 * Typical usage:
	 * - Downloadable reports or data exports from WordPress database tables.
	 *
	 * @param string $table_name The name of the database table to export as CSV.
	 *
	 * @since 0.0.3
	 *
	 * @return void Outputs the CSV file or an error response and exits.
	 */
	private static function output_csv_from_database( string $table_name ) : void {
		global $wpdb;
		$full_table_name = $wpdb->prefix . $table_name;
		$results = $wpdb->get_results( "SELECT * FROM $full_table_name", ARRAY_A );
		if ( empty( $results )) {
			header( 'HTTP/1.1 404 Not Found' );
			echo json_encode( [ 'error' => 'No data found for this table.' ] );
			exit;
		}

		// Set headers for CSV output
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $table_name . '.csv"' );

		// Generate CSV content
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array_keys( $results[0] ) ); // Header row
		foreach ( $results as $row ) {
			fputcsv( $output, $row );
		}
		fclose( $output );
		exit;
	}
}
