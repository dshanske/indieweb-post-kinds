<?php
/**
 * Deprecated global names for code that predates the ParseThis namespace.
 *
 * Post Kinds and Yarns call these names directly. Because the standalone
 * plugin loads before the copies they bundle, defining the names here makes
 * those plugins use the current code rather than their own older copy.
 *
 * Only names used by those plugins are aliased. No deprecation notices are
 * raised, so as not to flood their users' logs.
 *
 * @package Parse_This
 */

defined( 'ABSPATH' ) || exit;

/*
 * Maps the old global class names to their namespaced classes on demand.
 * class_exists( 'REST_Parse_This' ), which Post Kinds uses to decide whether
 * to load its own endpoint, therefore finds the current one.
 */
spl_autoload_register(
	function ( $class ) {
		$aliases = array(
			'Parse_This'           => 'ParseThis\\Parser',
			'Parse_This_Discovery' => 'ParseThis\\Discovery',
			'Parse_This_MF2'       => 'ParseThis\\MF2',
			'Parse_This_MF2_Utils' => 'ParseThis\\MF2_Utils',
			'REST_Parse_This'      => 'ParseThis\\REST_Endpoint',
		);
		if ( isset( $aliases[ $class ] ) ) {
			class_alias( $aliases[ $class ], $class );
		}
	}
);

if ( ! function_exists( 'jf2_to_mf2' ) ) {
	/**
	 * Converts jf2 into microformats2 JSON.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use ParseThis\jf2_to_mf2().
	 *
	 * @param array|mixed $jf2 jf2 object.
	 * @return array|mixed mf2 object.
	 */
	function jf2_to_mf2( $jf2 ) {
		return ParseThis\jf2_to_mf2( $jf2 );
	}
}

if ( ! function_exists( 'mf2_to_jf2' ) ) {
	/**
	 * Converts microformats2 JSON into jf2.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use ParseThis\mf2_to_jf2().
	 *
	 * @param array|mixed $mf2 mf2 object or document.
	 * @return array|mixed jf2.
	 */
	function mf2_to_jf2( $mf2 ) {
		return ParseThis\mf2_to_jf2( $mf2 );
	}
}

if ( ! function_exists( 'post_type_discovery' ) ) {
	/**
	 * Determines the IndieWeb post type of a jf2 or mf2 entry.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use ParseThis\post_type_discovery().
	 *
	 * @param array|mixed $jf2 jf2 or mf2 object.
	 * @return string The post type, or an empty string.
	 */
	function post_type_discovery( $jf2 ) {
		return ParseThis\post_type_discovery( $jf2 );
	}
}

if ( ! function_exists( 'pt_load_domdocument' ) ) {
	/**
	 * Parses HTML into a DOMDocument.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use ParseThis\pt_load_domdocument().
	 *
	 * @param string $content HTML.
	 * @return DOMDocument The parsed document.
	 */
	function pt_load_domdocument( $content ) {
		return ParseThis\pt_load_domdocument( $content );
	}
}

if ( ! function_exists( 'seconds_to_iso8601' ) ) {
	/**
	 * Converts a number of seconds into an ISO 8601 duration.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use ParseThis\seconds_to_iso8601().
	 *
	 * @param int|float $second Duration in seconds.
	 * @return string ISO 8601 duration.
	 */
	function seconds_to_iso8601( $second ) {
		return ParseThis\seconds_to_iso8601( $second );
	}
}
