<?php
/**
 * Parse This Twitter class.
 */
class Parse_This_Twitter extends Parse_This_Base {
	/**
	 *
	 * @access public
	 */
	public static function parse( $url, $args ) {
		if ( false === strpos( $url, 'status' ) ) {
			return array();
		}

		$args     = array(
			'timeout'             => 15,
			'limit_response_size' => 1048576,
			'redirection'         => 5,
			// Use an explicit user-agent for Parse This
			'user_agent'          => 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36 Parse This/WP',
		);
		$url      = add_query_arg( 'url', $url, 'https://publish.twitter.com/oembed' );
		$response = wp_safe_remote_get( $url, $args );
		$oembed   = json_decode( wp_remote_retrieve_body( $response ), true );
		$jf2      = array();
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
					unset( $parse['query'] );
					$value = build_url( $parse );
				if ( '#' === $key[0] ) {
					$category[] = str_replace( '#', '', $key );
				} elseif ( '@' === $key[0] ) {
					$category[] = $value;
				} elseif ( $jf2['url'] === $value ) {
					$published        = new DateTime( $key );
					$jf2['published'] = $published->format( DATE_W3C );
				} else {
					$names[ wp_strip_all_tags( $key ) ] = normalize_url( $value ); // phpcs:ignore
				}
			}
			$jf2['links']    = $names;
			$jf2['category'] = $category;
			$jf2['content']  = array(
				'html'  => Parse_This::clean_content( $html, array( 'blockquote' => array() ) ),
				'value' => $text,
			);
			$jf2['summary']  = $jf2['content']['html'];
		}
		$jf2['author']      = array_filter(
			array(
				'type' => 'card',
				'name' => ifset( $oembed['author_name'] ),
				'url'  => ifset( $oembed['author_url'] ),
			)
		);
		$jf2['publication'] = 'Twitter';
		if ( WP_DEBUG ) {
			$jf2['_ombed'] = $oembed;
		}

		return array_filter( $jf2 );
	}

}
