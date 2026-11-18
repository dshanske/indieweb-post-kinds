<?php
/**
 * Summarizes a Clover coverage report and enforces a minimum line coverage.
 *
 * Usage: php bin/coverage-check.php <clover.xml> <minimum-percent>
 *
 * Prints a per-file table of line coverage, lowest first, and the total. When
 * GITHUB_STEP_SUMMARY is set (GitHub Actions), the table is also written there
 * as Markdown. Exits with status 1 if the total is below the minimum, or if the
 * report can't be read.
 *
 * This is a development tool. It is not loaded by WordPress and is not shipped
 * (bin/ is export-ignored).
 *
 * @package Post_Kinds
 * @since 4.0.2
 */

'cli' === PHP_SAPI || exit;

if ( $argc < 3 || ! is_numeric( $argv[2] ) ) {
	fwrite( STDERR, "Usage: php bin/coverage-check.php <clover.xml> <minimum-percent>\n" );
	exit( 1 );
}

$report  = $argv[1];
$minimum = (float) $argv[2];

if ( ! is_readable( $report ) ) {
	fwrite( STDERR, "Coverage report not found: {$report}\n" );
	exit( 1 );
}

$xml = simplexml_load_file( $report );
if ( false === $xml ) {
	fwrite( STDERR, "Coverage report could not be parsed: {$report}\n" );
	exit( 1 );
}

$root  = realpath( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR;
$files = array();
foreach ( $xml->xpath( '//file' ) as $file ) {
	$metrics    = $file->metrics;
	$statements = (int) $metrics['statements'];
	$covered    = (int) $metrics['coveredstatements'];
	$name       = (string) $file['name'];
	if ( 0 === strpos( $name, $root ) ) {
		$name = substr( $name, strlen( $root ) );
	}
	$files[] = array(
		'name'       => $name,
		'covered'    => $covered,
		'statements' => $statements,
		'percent'    => $statements ? 100 * $covered / $statements : 100.0,
	);
}

usort(
	$files,
	function ( $a, $b ) {
		return $a['percent'] <=> $b['percent'] ?: strcmp( $a['name'], $b['name'] );
	}
);

$covered    = array_sum( array_column( $files, 'covered' ) );
$statements = array_sum( array_column( $files, 'statements' ) );
$total      = $statements ? 100 * $covered / $statements : 100.0;
$passed     = round( $total, 2 ) >= $minimum;

$width = max( array_map( 'strlen', array_merge( array( 'TOTAL' ), array_column( $files, 'name' ) ) ) );
foreach ( $files as $file ) {
	printf( "%6.1f%%  %-{$width}s  %d/%d\n", $file['percent'], $file['name'], $file['covered'], $file['statements'] );
}
printf( "%6.2f%%  %-{$width}s  %d/%d (minimum %.2f%%)\n", $total, 'TOTAL', $covered, $statements, $minimum );

$summary = getenv( 'GITHUB_STEP_SUMMARY' );
if ( $summary ) {
	$md  = sprintf( "## Code coverage: %.2f%% of lines %s\n\n", $total, $passed ? '✅' : '❌' );
	$md .= sprintf( "Minimum: %.2f%%. %d of %d lines covered.\n\n", $minimum, $covered, $statements );
	$md .= "| File | Lines | Coverage |\n|---|---:|---:|\n";
	foreach ( $files as $file ) {
		$md .= sprintf( "| `%s` | %d / %d | %.1f%% |\n", $file['name'], $file['covered'], $file['statements'], $file['percent'] );
	}
	file_put_contents( $summary, $md, FILE_APPEND );
}

if ( ! $passed ) {
	fprintf( STDERR, "Line coverage %.2f%% is below the minimum of %.2f%%.\n", $total, $minimum );
	exit( 1 );
}
