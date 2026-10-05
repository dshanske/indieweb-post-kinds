<?php
/**
 * JSON class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts entry data from application/json script blocks in HTML pages.
 *
 * Currently recognizes the Next.js page-props structure
 * (props.pageProps.article) used by some publishing platforms.
 *
 * @since 2.0.0
 */
class JSON extends Base {
	/**
	 * Parses embedded application/json script blocks into jf2.
	 *
	 * @since 2.0.0
	 *
	 * @param DOMDocument|null $doc  Parsed HTML document.
	 * @param string           $url  URL of the page.
	 * @param array            $args Parse arguments (see Parser::parse()). Unused.
	 * @return array jf2 properties found (type, name, published, category). When
	 *               $args['debug'] is set, the decoded JSON is included under '_json'.
	 */
	public static function parse( $doc, $url, $args ) {
		if ( ! $doc ) {
			return array();
		}
		$xpath = new \DOMXPath( $doc );

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
						$jf2['name'] = $props['article']['title'] ?? null;
						if ( array_key_exists( 'meta', $props['article'] ) ) {
							$jf2['published'] = normalize_iso8601( $props['article']['meta']['date'] ?? null );
							$jf2['category']  = $props['article']['meta']['tags'] ?? null;
						}
					}
				}
			}
		}
		$jf2 = array_filter( $jf2 );

		if ( ! empty( $args['debug'] ) ) {
			$jf2['_json'] = $json;
		}
		return array_filter( $jf2 );
	}
}
