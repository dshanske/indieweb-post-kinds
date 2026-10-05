<?php
/**
 * Class autoloader for Parse This.
 *
 * @package Parse_This
 */

defined( 'ABSPATH' ) || exit;

/*
 * Maps classes in the ParseThis namespace to files in this directory, for
 * example ParseThis\MF2_Utils to class-mf2-utils.php.
 */
spl_autoload_register(
	function ( $class ) {
		$prefix = 'ParseThis\\';
		if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
			return;
		}
		$name = substr( $class, strlen( $prefix ) );
		$file = __DIR__ . '/class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// Deprecated global names still used by other plugins.
require_once __DIR__ . '/aliases.php';
