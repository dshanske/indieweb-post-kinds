<?php

// autoloader
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'Masterminds';
		$base_dir = __DIR__;
		$len      = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$name = substr( $class, $len );
		$file = __DIR__ . str_replace( '\\', '/', $name ) . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);
