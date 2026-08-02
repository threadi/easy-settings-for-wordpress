#!/usr/bin/env php
<?php
/**
 * Validate one or more JSON files against a JSON schema.
 *
 * Usage:
 *   composer validate-schema
 *   composer validate-schema -- path/to/schema.json path/to/data.json [more.json ...]
 *   php bin/validate-schema.php [<schema.json>] <data.json> [<data.json> ...]
 *
 * With no arguments the default schema and example are validated – this is what
 * runs during the release build. A single argument is treated as a data file
 * checked against the default schema.
 *
 * Exit code 0 = all files valid, 1 = at least one invalid or unreadable.
 *
 * @package easy-settings-for-wordpress
 */

declare( strict_types=1 );

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

// locate the composer autoloader (works from repo root and from bin/).
$autoload = null;
foreach ( array( __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php' ) as $candidate ) {
	if ( is_file( $candidate ) ) {
		$autoload = $candidate;
		break;
	}
}
if ( null === $autoload ) {
	fwrite( STDERR, "Composer autoloader not found. Run 'composer install' first.\n" );
	exit( 1 );
}
require $autoload;

// default schema + example used when no data file is given – adjust to your paths.
$default_schema = __DIR__ . '/../settings.schema.json';
$default_data   = array( __DIR__ . '/../docs/example.json' );

// parse CLI arguments (everything after the script name).
$args = array_slice( $argv, 1 );
if ( count( $args ) >= 2 ) {
	$schema_path = array_shift( $args );
	$data_paths  = $args;
} elseif ( count( $args ) === 1 ) {
	// a single argument is a data file checked against the default schema.
	$schema_path = $default_schema;
	$data_paths  = $args;
} else {
	$schema_path = $default_schema;
	$data_paths  = $default_data;
}

/**
 * Read and decode a JSON file (as objects, as opis expects), exiting on error.
 *
 * @param string $path Path to the JSON file.
 *
 * @return mixed The decoded JSON.
 */
$read_json = static function ( string $path ) {
	if ( ! is_file( $path ) ) {
		fwrite( STDERR, sprintf( "File not found: %s\n", $path ) );
		exit( 1 );
	}

	$decoded = json_decode( (string) file_get_contents( $path ) );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		fwrite( STDERR, sprintf( "Invalid JSON in %s: %s\n", $path, json_last_error_msg() ) );
		exit( 1 );
	}

	return $decoded;
};

$schema    = $read_json( $schema_path );
$validator = new Validator();
$formatter = new ErrorFormatter();
$failed    = false;

fwrite( STDOUT, sprintf( "Schema: %s\n", $schema_path ) );

foreach ( $data_paths as $data_path ) {
	$result = $validator->validate( $read_json( $data_path ), $schema );

	if ( $result->isValid() ) {
		fwrite( STDOUT, sprintf( "PASS  %s\n", $data_path ) );
		continue;
	}

	$failed = true;
	fwrite( STDERR, sprintf( "FAIL  %s\n", $data_path ) );

	// print every error keyed by its location in the data.
	foreach ( $formatter->formatKeyed( $result->error() ) as $pointer => $messages ) {
		foreach ( $messages as $message ) {
			fwrite( STDERR, sprintf( "      %s: %s\n", '' === $pointer ? '/' : $pointer, $message ) );
		}
	}
}

exit( $failed ? 1 : 0 );
