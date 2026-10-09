<?php
/**
 * Compiles the Sass sources in sass/ into the minified stylesheets in css/.
 *
 * Uses scssphp (a Composer dev dependency) in place of Ruby Sass. Works with
 * scssphp 1.x (PHP 7.4) and 2.x (PHP 8.1+). Run it with `composer build:css`.
 *
 * @package Post_Kinds
 */

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root = dirname( __DIR__ );
require $root . '/vendor/autoload.php';

// Source file in sass/ => destination file in css/.
$stylesheets = array(
	'main.scss'  => 'kind.min.css',
	'admin.scss' => 'kind.admin.min.css',
);

foreach ( $stylesheets as $source => $destination ) {
	$compiler = new Compiler();
	$compiler->setImportPaths( $root . '/sass' );
	$compiler->setOutputStyle( OutputStyle::COMPRESSED );
	$compiler->setSourceMap( Compiler::SOURCE_MAP_FILE );
	$compiler->setSourceMapOptions(
		array(
			'sourceMapURL'      => $destination . '.map',
			'sourceMapFilename' => $destination,
			'sourceMapBasepath' => $root,
			'sourceRoot'        => '../',
		)
	);

	$result = $compiler->compileString( file_get_contents( $root . '/sass/' . $source ), $root . '/sass/' . $source );

	file_put_contents( $root . '/css/' . $destination, $result->getCss() . "\n" );
	file_put_contents( $root . '/css/' . $destination . '.map', $result->getSourceMap() );
	echo "File \"css/$destination\" created.\n";
}
