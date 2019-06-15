<?php

// autoloader for Parse This
spl_autoload_register(
	function ( $class ) {
		$base_dir = trailingslashit( __DIR__ );
		$bases    = array( 'Parse_This', 'MF2' );
		foreach ( $bases as $base ) {
			if ( strncmp( $class, $base, strlen( $base ) ) === 0 ) {
				$filename = 'class-' . strtolower( str_replace( '_', '-', $class ) );
				$file     = $base_dir . $filename . '.php';
				if ( file_exists( $file ) ) {
					require $file;
				}
			}
		}
	}
);
