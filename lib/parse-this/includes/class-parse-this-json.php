<?php
/**
 * Parse This JSON class.
 */
class Parse_This_JSON extends Parse_This_Base {
	/**
	 * Parses _meta, _images, and _links data from the content.
	 *
	 * @access public
	 */
	public static function parse( $doc, $url, $args ) {
		if ( ! $doc ) {
			return array();
		}
		$xpath = new DOMXPath( $doc );

		$json    = array();
		$content = '';
		foreach ( $xpath->query( "//script[@type='application/json']" ) as $script ) {
			$content  = $script->textContent; // phpcs:ignore
			$json[]  = json_decode( $content, true );
		}
		$json = array_filter( $json );

		$jf2 = array();

		if ( 1 === count( $json ) && wp_is_numeric_array( $json ) ) {
			$json = $json[0];
			if ( array_key_exists( 'props', $json ) ) {
				$props = $json['props'];
				if ( array_key_exists( 'pageProps', $props ) ) {
					$props = $props['pageProps'];
					if ( array_key_exists( 'article', $props ) ) {
						$jf2['type'] = 'entry';
						$jf2['name'] = ifset( $props['article']['title'] );
						if ( array_key_exists( 'meta', $props['article'] ) ) {
							$jf2['published'] = normalize_iso8601( ifset( $props['article']['meta']['date'] ) );
							$jf2['category']  = ifset( $props['article']['meta']['tags'] );
						}
					}
				}
			}
		}
		$jf2 = array_filter( $jf2 );

		if ( WP_DEBUG ) {
			$jf2['_json'] = $json;
		}
		return array_filter( $jf2 );
	}
}
