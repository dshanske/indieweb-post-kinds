<?php
/**
 * Gathers Data for Link Previews
 *
 * Parses Arbitrary URLs
 */
add_action( 'init' , array( 'Link_Preview', 'init' ) );
add_action( 'wp_ajax_kind_test', 'kind_ajaxtest' );

function kind_ajaxtest() {
	$response = array( 'result' => 'successful' );
	wp_send_json( $response );
}


class Link_Preview {
	public static function init() {
		add_action( 'wp_ajax_kind_urlfetch', array( 'Link_Preview', 'urlfetch' ) );
	}

	/**
	 * Retrieves the body of a URL for parsing.
	 *
	 * @param string $url A valid URL.
	 */
	private static function fetch($url) {
		global $wp_version;
		if ( ! isset( $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
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
		$metadata = self::metaparse( $content );
		$mf2data = self::mf2parse( $content, $url );
		$data = array_merge( $metadata, $mf2data );
		$data = array_filter( $data );
		// If Publication is Not Set, use the domain name instead
		$data['publication'] = ifset( $data['publication'] ) ?: self::pretty_domain_name( $url );
		// If Name is Not Set Use Title Tag
		if ( ! isset( $data['name'] ) ) {
			preg_match( '/<title>(.+)<\/title>/i', $content, $match );
			$data['name'] = trim( $match[1] );
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

	// Give a Proper Name for Set Sites
	public static function pretty_domain_name( $url ) {
		switch ( $url ) {
			case 'twitter.com':
			return  _( 'Twitter', 'Post kinds' );
			break;
			default:
			return extract_domain_name( $url );
		}
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
		$data = array();
		$host = extract_domain_name( $url );
		switch ( $host ) {
			case 'twitter.com':
				$parsed = Mf2\Shim\parseTwitter( $content, $url );
				break;
			default:
				$parsed = Mf2\parse( $content, $url );
		}
		if ( mf2_cleaner::isMicroformatCollection( $parsed ) ) {
			$entries = mf2_cleaner::findMicroformatsByType( $parsed, 'h-entry' );
			if ( $entries ) {
				$entry = $entries[0];
				if ( mf2_cleaner::isMicroformat( $entry ) ) {
					foreach ( $entry['properties'] as $key => $value ) {
						$data[$key] = mf2_cleaner::getPlaintext( $entry, $key );
					}
					$data['published'] = mf2_cleaner::getPublished( $entry );
					$data['updated'] = mf2_cleaner::getUpdated( $entry );
						  $data['name'] = mf2_cleaner::getPlaintext( $entry, 'name' );
					$data['content'] = mf2_cleaner::getHtml( $entry, 'content' );
					$data['summary'] = mf2_cleaner::getHtml( $entry, 'summary' );
					$data['name'] = trim( preg_replace( '/https?:\/\/([^ ]+|$)/', '', $data['name'] ) );
					$author = mf2_cleaner::getAuthor( $entry );
					if ( $author ) {
							$data['author'] = array();
						foreach ( $author['properties'] as $key => $value ) {
							$data['author'][$key] = mf2_cleaner::getPlaintext( $author, $key );
						}
							$data['author'] = array_filter( $data['author'] );
					}
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

  public static function get_meta_tags( $source_content ) {
    if ( ! $source_content ) {
      return null;
    }
    $meta = array();
    if ( preg_match_all( '/<meta [^>]+>/', $source_content, $matches ) ) {
      $items = $matches[0];

      foreach ( $items as $value ) {
        if ( preg_match( '/(property|name)="([^"]+)"[^>]+content="([^"]+)"/', $value, $new_matches ) ) {
          $meta_name  = $new_matches[2];
          $meta_value = $new_matches[3];

          // Sanity check. $key is usually things like 'title', 'description', 'keywords', etc.
          if ( strlen( $meta_name ) > 100 ) {
            continue;
          }
          $meta[$meta_name] = $meta_value;
        }
      }
    }
    return $meta;
  }

	/**
	 * Parses marked up HTML using OGP or other meta tags.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function metaparse($content) {
		$meta = self::get_meta_tags( $content );
		$data = array();
		$data['name'] = ifset( $meta['og:title'] ) ?: ifset( $meta['twitter:title'] ) ?: ifset( $meta['og:music:song'] );
		$data['summary'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] ) ?: ifset( $meta['description']);
		$data['site'] = ifset( $meta['og:site'] ) ?: ifset( $meta['twitter:site'] );
		if ( array_key_exists( 'author', $meta ) ) {
			$data['author'] = array();
			$data['author']['name'] = $meta['author'];
		}
		$data['featured'] = ifset( $meta['og:image'] ) ?: ifset( $meta['twitter:image'] );
		$data['publication'] = ifset( $meta['og:site_name'] ) ?: ifset( $meta['og:music:album'] );
		$data['published'] = ifset( $meta['og:article:published_time'] ) ?: ifset( $meta['pdate'] ) ?: ifset( $meta['og:article:published'] ) ?: ifset( $meta['og:music:release_date'] ) ?: ifset( $meta['og:video:release_date'] );
		$metatags = ifset( $meta['article:tag'] ) ?: ifset( $meta['og:video:tag'] );
		$tags = array();
		if ( is_array( $metatags ) ) {
			foreach ( $metatags as $tag ) {
				$tags[] = str_replace( ',', ' -', $tag );
			}
			$tags = array_filter( $tags );
		}
		$data['tags'] = ifset( $data['tags'] ) ?: implode( ',' ,$tags );
		// Extended Parameters
		$data['audio'] = ifset( $meta['og:audio'] );
		$data['video'] = ifset( $meta['og:video'] );
		$data['duration'] = ifset( $meta['music:duration'] ) ?: ifset( $meta['video:duration'] );
		$data['type'] = ifset( $meta['og:type'] );
		$data['meta'] = $meta;
		return array_filter( $data );
	}
}
?>
