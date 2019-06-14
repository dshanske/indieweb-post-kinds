<?php

// autoloader
spl_autoload_register( 'pt_html5_autoload' );

if ( ! class_exists( 'Masterminds\\HTML5' ) ) {
	trigger_error( 'Autoloader not registered properly', E_USER_ERROR );
}

function pt_html5_autoload( $class ) {
	//change this to your root namespace
	$prefix = 'Masterminds';
	//make sure this is the directory with your classes
	$base_dir = __DIR__;
	$len      = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}
	$name = substr( $class, $len );
	$file           = __DIR__ . str_replace( '\\', '/', $name ) . '.php';
	if ( file_exists( $file ) ) {
		require $file;
	}
}
