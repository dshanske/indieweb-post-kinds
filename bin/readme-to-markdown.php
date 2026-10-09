<?php
/**
 * Converts the WordPress.org readme.txt into readme.md for GitHub.
 *
 * A PHP port of grunt-wp-readme-to-markdown (itself a port of
 * benbalter/WP-Readme-to-Github-Markdown), so the output matches what the
 * Grunt task produced. Run it with `composer build:readme`.
 *
 * Usage: php bin/readme-to-markdown.php [source] [destination]
 *
 * @package Post_Kinds
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$source         = isset( $argv[1] ) ? $argv[1] : 'readme.txt';
$destination    = isset( $argv[2] ) ? $argv[2] : 'readme.md';
$screenshot_url = '/.wordpress-org/{screenshot}.png';

$readme = file_get_contents( $source );
if ( false === $readme ) {
	fwrite( STDERR, "Cannot read $source\n" );
	exit( 1 );
}

// Headings: === Name === becomes # Name #, == Section == becomes ## Section ##, = Sub = becomes ### Sub ###.
$readme = preg_replace( '/^=([^=]+)=*?[\s ]*?$/im', '###$1###', $readme );
$readme = preg_replace( '/^==([^=]+)==*?[\s ]*?$/im', '##$1##', $readme );
$readme = preg_replace( '/^===([^=]+)===*?[\s ]*?$/im', '#$1#', $readme );

// Header fields (Contributors, Tags, ...) become bold labels with a line break.
if ( preg_match( '/([^#]*)(?:\n##|$)/m', $readme, $header ) ) {
	$fields = preg_replace( '/^([^:\r\n*]{1}[^:\r\n#\]\[]+): (.+)/im', '**$1:** $2  ', $header[1] );
	$readme = substr_replace( $readme, $fields, strpos( $readme, $header[1] ), strlen( $header[1] ) );
}

// Link contributors to their WordPress.org profiles.
if ( preg_match( '/(\*\*Contributors:\*\* )(.+)/m', $readme, $contributors ) ) {
	$profiles = array();
	foreach ( explode( ',', $contributors[2] ) as $name ) {
		$name       = trim( $name );
		$profiles[] = '[' . $name . '](https://profiles.wordpress.org/' . $name . ')';
	}
	$readme = substr_replace( $readme, $contributors[1] . implode( ', ', $profiles ) . '  ', strpos( $readme, $contributors[0] ), strlen( $contributors[0] ) );
}

// Screenshots: a numbered list becomes headings with images.
if ( preg_match( '/^#([^#]+)#[\s ]*?$/im', $readme ) && preg_match( '/## Screenshots ##([^#]*)/im', $readme, $screenshots ) ) {
	preg_match_all( '/^[0-9]+\. (.*)/im', $screenshots[1], $items, PREG_SET_ORDER );
	foreach ( $items as $i => $item ) {
		$number = $i + 1;
		$url    = str_replace( '{screenshot}', 'screenshot-' . $number, $screenshot_url );
		$readme = substr_replace( $readme, '### ' . $number . '. ' . $item[1] . " ###\n![" . $item[1] . '](' . $url . ")\n", strpos( $readme, $item[0] ), strlen( $item[0] ) );
	}
}

// Code blocks fenced with lone backticks become indented blocks.
$readme = preg_replace_callback(
	'/^`$[\n\r]+([^`]*)[\n\r]+^`$/m',
	function ( $codeblock ) {
		return "\n\t" . implode( "\n\t", explode( "\n", $codeblock[1] ) ) . "\n";
	},
	$readme
);

if ( false === file_put_contents( $destination, $readme ) ) {
	fwrite( STDERR, "Cannot write $destination\n" );
	exit( 1 );
}
echo "File \"$destination\" created.\n";
