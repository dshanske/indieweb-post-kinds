<?php
/**
 * Twitter class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Builds jf2 for individual posts on X (formerly Twitter) from its public
 * oEmbed endpoint.
 *
 * @since 1.0.0
 */
class Twitter extends Base {
	/**
	 * Parses a post URL on x.com or twitter.com into jf2 using publish.x.com/oembed.
	 *
	 * Only status URLs are handled. Hashtags become categories, mentioned
	 * accounts become category URLs, and the tweet's own permalink text is used
	 * as the published date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url  URL of the tweet.
	 * @param array  $args Parse arguments (see Parser::parse()). Unused.
	 * @return array jf2 properties for the tweet, or an empty array if $url is
	 *               not a status URL or the oEmbed request fails.
	 */
	public static function parse( $url, $args ) {
		if ( false === strpos( $url, 'status' ) ) {
			return array();
		}

		$url      = add_query_arg( 'url', $url, 'https://publish.x.com/oembed' );
		$response = pt_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$oembed = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $oembed ) ) {
			return array();
		}
		$jf2 = array();
		if ( array_key_exists( 'url', $oembed ) ) {
			$jf2['url'] = $oembed['url'];
		}
		if ( array_key_exists( 'html', $oembed ) ) {
			$html = $oembed['html'];
			$dom  = pt_load_domdocument( $html );
			$html = explode( '&mdash;', $html );
			$html = $html[0];
			$text = wp_strip_all_tags( $html );
			$text = explode( '&mdash;', $text );
			$text = $text[0];

			$links    = $dom->getElementsByTagName( 'a' );
			$names    = array();
			$category = array();
			foreach ( $links as $link ) {
					$key   = wp_strip_all_tags( $link->nodeValue ); // phpcs:ignore
					$value = $link->getAttribute( 'href' );
					$parse = wp_parse_url( $value );
				if ( '' === $key || ! is_array( $parse ) ) {
					continue;
				}
					unset( $parse['query'] );
					$value = build_url( $parse );
				if ( '#' === $key[0] ) {
					$category[] = str_replace( '#', '', $key );
				} elseif ( '@' === $key[0] ) {
					$category[] = $value;
				} elseif ( isset( $jf2['url'] ) && $jf2['url'] === $value ) {
					$jf2['published'] = normalize_iso8601( $key );
				} else {
					$names[ wp_strip_all_tags( $key ) ] = normalize_url( $value ); // phpcs:ignore
				}
			}
			$jf2['links']    = $names;
			$jf2['category'] = $category;
			$jf2['content']  = array(
				'html'  => Parser::clean_content( $html, array( 'blockquote' => array() ) ),
				'value' => $text,
			);
			$jf2['summary']  = $jf2['content']['html'];
		}
		$jf2['author']      = array_filter(
			array(
				'type' => 'card',
				'name' => $oembed['author_name'] ?? null,
				'url'  => $oembed['author_url'] ?? null,
			)
		);
		$jf2['publication'] = 'Twitter';
		if ( ! empty( $args['debug'] ) ) {
			$jf2['_ombed'] = $oembed;
		}

		return array_filter( $jf2 );
	}
}
