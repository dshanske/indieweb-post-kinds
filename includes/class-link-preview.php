<?php
/**
 * Gathers Data for Link Previews
 *
 * Parses Arbitrary URLs
 */

class Link_Preview {
	public static function init() {
		add_action( 'wp_ajax_kind_urlfetch', array( 'Link_Preview', 'urlfetch' ) );
	}

	/**
	 * Returns if valid URL
	 *
	 * @param string $url
	 *
	 * @return boolean
	 */
	public static function is_valid_url($url) {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Retrieves the body of a URL for parsing.
	 *
	 * @param string $url A valid URL.
	 */
	private static function fetch($url) {
		global $wp_version;
		if ( ! isset( $url ) || ! self::is_valid_url( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.' ) );
		}
		$args = array(
			'timeout' => 10,
			'limit_response_size' => 1048576,
			'redirection' => 20,
			// Use an explicit user-agent for Post Kinds
			'user-agent' => 'Post Kinds (WordPress/' . $wp_version . '); ' . get_bloginfo( 'url' ),
		);
		$response = wp_safe_remote_head( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( preg_match( '#(image|audio|video|model)/#is', wp_remote_retrieve_header( $response, 'content-type' ) ) ) {
			return new WP_Error( 'content-type', 'Content Type is Media' );
		}
		$response = wp_safe_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );
		return $body;
	}
	/**
	 * Parses marked up HTML.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function parse ($content, $url) {
		$parsethis = new Parse_This();
		$parsethis->set_source( $content, $url );
		$metadata = $parsethis->meta_to_microformats();
		if ( version_compare( PHP_VERSION, '5.3', '>' ) ) {
			$mf2data = self::mf2parse( $content, $url );
			$data = array_merge( $metadata, $mf2data );
			$data = array_filter( $data );
		} else {
			$data = $metadata;
		}

		if ( ! isset( $data['summary'] ) ) {
			$data['summary'] = substr( $data['content']['text'], 0, 300 );
			if ( 300 < strlen( $data['content']['text'] ) ) {
				$data['summary'] .= '...';
			}
		}
		if ( isset( $data['name'] ) ) {
			if ( isset( $data['summary'] ) ) {
				if ( false !== stripos( $data['summary'], $data['name'] ) ) {
					unset( $data['name'] );
				}
			}
		}

		/**
		 * Parse additionally by plugin.
		 *
		 * @param array $data An array of properties.
		 * @param string $content The content of the retrieved page.
		 * @param string $url Source URL
		 */
		return apply_filters( 'kind_parse_data', $data, $content, $url );
	}

	public static function urlfetch() {
		global $wpdb;
		if ( empty( $_POST['kind_url'] ) ) {
				wp_send_json_error( new WP_Error( 'nourl', __( 'You must specify a URL' ) ) );
		}
		if ( filter_var( $_POST['kind_url'], FILTER_VALIDATE_URL ) === false ) {
				wp_send_json_error( new WP_Error( 'badurl', __( 'Input is not a valid URL' ) ) );
		}

		$content = self::fetch( $_POST['kind_url'] );
		if ( is_wp_error( $content ) ) {
			wp_send_json_error( $response );
		}
		wp_send_json_success( self::parse( $content, $_POST['kind_url'] ) );
	}

	/*
	Parses marked up HTML using MF2.
	*
	* @param string $content HTML marked up content.
	*/
	private static function mf2parse($content, $url) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		switch ( $host ) {
			case 'twitter.com':
			case 'www.twitter.com':
				$parsed = Mf2\Shim\parseTwitter( $content, $url );
				break;
			default:
				$parsed = Mf2\parse( $content, $url );
		}
		if ( ! is_array( $parsed ) ) {
			return array();
		}
		$count = count( $parsed['items'] );
		if ( 0 == $count ) {
			return array();
		}
		if ( 1 == $count ) {
			$item = $parsed['items'][0];
			if ( in_array( 'h-feed', $item['type'] ) ) {
				return array( 'type' => 'feed' );
			}
			if ( in_array( 'h-card', $item['type'] ) ) {
				return self::parse_hcard( $item, $parsed, $url );
			} elseif ( in_array( 'h-entry', $item['type'] ) || in_array( 'h-cite', $item['type'] ) ) {
				return self::parse_hentry( $item, $parsed );
			}
		}
		foreach ( $parsed['items'] as $item ) {
			if ( array_key_exists( 'url', $item['properties'] ) ) {
				$urls = $item['properties']['url'];
				if ( in_array( $url, $urls ) ) {
					if ( in_array( 'h-card', $item['type'] ) ) {
						return self::parse_hcard( $item, $parsed, $url );
					} elseif ( in_array( 'h-entry', $item['type'] ) || in_array( 'h-cite', $item['type'] ) ) {
						return self::parse_hentry( $item, $parsed );
					}
				}
			}
		}
	}

	private static function parse_hentry( $entry, $mf ) {
		// Array Values
		$properties = array( 'category', 'invitee', 'photo','video','audio','syndication','in-reply-to','like-of','repost-of','bookmark-of', 'tag-of' );
		$data = Mf2_Cleaner::get_prop_array( $entry, $properties );
		$data['type'] = 'entry';
		$data['published'] = Mf2_Cleaner::get_published( $entry );
		$data['updated'] = Mf2_Cleaner::get_updated( $entry );
		$properties = array( 'url', 'rsvp', 'featured', 'name' );
		foreach ( $properties as $property ) {
			$data[ $property ] = Mf2_Cleaner::get_plaintext( $entry, $property );
		}
		$data['content'] = Mf2_Cleaner::parse_html_value( $entry, 'content' );
		$data['summary'] = Mf2_Cleaner::get_summary( $entry, $data['content'] );
		if ( isset( $data['name'] ) ) {
			$data['name'] = trim( preg_replace( '/https?:\/\/([^ ]+|$)/', '', $data['name'] ) );
		}
		if ( isset( $mf['rels']['syndication'] ) ) {
			if ( isset( $data['syndication'] ) ) {
				$data['syndication'] = array_unique( array_merge( $data['syndication'], $mf['rels']['syndication'] ) );
			} else {
				$data['syndication'] = $mf['rels']['syndication'];
			}
		}
		$author = Mf2_Cleaner::find_author( $entry, $mf );
		if ( $author ) {
			if ( is_array( $author['type'] ) ) {
				$data['author'] = self::parse_hcard( $author, $mf );
			} else {
				$author = array_filter( $author );
				if ( ! isset( $author['name'] ) && isset( $author['url'] ) ) {
					$content = self::fetch( $author['url'] );
					$parsed = Mf2\parse( $content, $author['url'] );
					$hcard = mf2_cleaner::find_microformat( $parsed, 'h-card' );
					$data['author'] = self::parse_hcard( $hcard, $parsed, $author['url'] );
				} else {
					$data['author'] = $author;
				}
			}
		}
		$data = array_filter( $data );
		if ( array_key_exists( 'name', $data ) ) {
			if ( ! array_key_exists( 'summary', $data ) || ! array_key_exists( 'content', $data ) ) {
				unset( $data['name'] );
			}
		}
		return $data;
	}

	private static function parse_hcard( $hcard, $mf, $authorurl = false ) {
		// If there is a matching author URL, use that one
		$data = array(
			'type' => 'card',
			'name' => null,
			'url' => null,
			'photo' => null,
		);
		$properties = [ 'url','name','photo' ];
		foreach ( $properties as $p ) {
			if ( 'url' == $p && $authorurl ) {
				// If there is a matching author URL, use that one
				$found = false;
				foreach ( $hcard['properties']['url'] as $url ) {
					if ( Mf2_Cleaner::is_url( $url ) ) {
						if ( $url == $authorurl ) {
							$data['url'] = $url;
							$found = true;
						}
					}
				}
				if ( ! $found && Mf2_Cleaner::is_url( $hcard['properties']['url'][0] ) ) {
					$data['url'] = $hcard['properties']['url'][0];
				}
			} else if ( ( $v = Mf2_Cleaner::get_plaintext( $hcard, $p ) ) !== null ) {
				// Make sure the URL property is actually a URL
				if ( 'url' == $p || 'photo' == $p ) {
					if ( Mf2_Cleaner::is_url( $v ) ) {
						$data[ $p ] = $v;
					}
				} else {
					$data[ $p ] = $v;
				}
			}
		}
		return array_filter( $data );
	}
}
?>
