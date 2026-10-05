<?php
/**
 * RESTAPI class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Reads posts and site data from the WordPress REST API of remote sites.
 *
 * Used when a page advertises its REST API (Link: rel="https://api.w.org/")
 * and an application/json alternate, and when the URL being parsed is itself
 * a REST API URL.
 *
 * @since 1.0.0
 */
class RESTAPI {

	/**
	 * Site index fields read by site_data().
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const SITE_FIELDS = array( 'name', 'url', 'timezone_string', 'gmt_offset', 'description' );
	/**
	 * Returns the rendered form of a REST API field such as title or content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key  Field name.
	 * @param array  $item REST API object.
	 * @return string|null The field's 'rendered' value, or null if absent.
	 */
	public static function get_rendered( $key, $item ) {
		if ( ! array_key_exists( $key, $item ) ) {
			return null;
		}
		if ( array_key_exists( 'rendered', $item[ $key ] ) ) {
			return $item[ $key ]['rendered'];
		}
		return null;
	}

	/**
	 * Encodes data as URL-safe base64 without padding.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data Data to encode.
	 * @return string Encoded data.
	 */
	public static function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Builds the URL of a REST API route, with _embed=1 added.
	 *
	 * Supports both pretty permalinks (/wp-json/...) and plain permalinks
	 * (?rest_route=...).
	 *
	 * @since 2.0.0
	 *
	 * @param string     $rest_url REST API root URL.
	 * @param string     $path     Route, for example /wp/v2/posts.
	 * @param array|null $query    Optional. Query arguments to add. Default null,
	 *                             which adds _embed=1.
	 * @return string|false The route URL, or false if $rest_url is invalid or has a
	 *                      query string without rest_route.
	 */
	public static function get_rest_url( $rest_url, $path, $query = null ) {
		if ( ! wp_http_validate_url( $rest_url ) ) {
			return false;
		}
		$args  = is_array( $query ) ? $query : array( '_embed' => 1 );
		$path  = '/' . ltrim( $path, '/' );
		$query = wp_parse_url( $rest_url, PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			wp_parse_str( $query, $params );
			if ( isset( $params['rest_route'] ) ) {
				return add_query_arg( array_merge( array( 'rest_route' => $path ), $args ), $rest_url );
			}
			return false;
		}

		$rest_url = untrailingslashit( $rest_url );
		return add_query_arg( $args, $rest_url . $path );
	}

	/**
	 * Returns the REST API root for a REST API URL.
	 *
	 * REST API responses don't say where the root is, so it is taken from the
	 * URL: everything up to /wp-json/, or ?rest_route=/ on sites with plain
	 * permalinks.
	 *
	 * @since 2.0.0
	 *
	 * @param string $url A REST API URL, such as a posts collection.
	 * @return string|false The REST API root, or false if $url doesn't show one
	 *                      (for example, a site with a custom REST prefix).
	 */
	public static function get_rest_root( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return false;
		}
		$base = ( isset( $parts['scheme'] ) ? $parts['scheme'] : 'https' ) . '://' . $parts['host'];
		if ( isset( $parts['port'] ) ) {
			$base .= ':' . $parts['port'];
		}
		$path = isset( $parts['path'] ) ? $parts['path'] : '/';
		if ( ! empty( $parts['query'] ) ) {
			wp_parse_str( $parts['query'], $params );
			if ( isset( $params['rest_route'] ) ) {
				return $base . $path . '?rest_route=/';
			}
		}
		$position = strpos( $path, '/wp-json/' );
		if ( false !== $position ) {
			return $base . substr( $path, 0, $position ) . '/wp-json/';
		}
		return false;
	}

	/**
	 * Returns the route part of a REST API URL relative to the API root.
	 *
	 * @since 2.0.0
	 *
	 * @param string $rest_url REST API root URL.
	 * @param string $url      Full REST API URL.
	 * @return string|false The route (for example /wp/v2/posts/5), or false if
	 *                      $rest_url is invalid or a plain-permalink $url has no
	 *                      rest_route.
	 */
	public static function get_rest_path( $rest_url, $url ) {
		if ( ! wp_http_validate_url( $rest_url ) ) {
			return false;
		}
		$query = wp_parse_url( $rest_url, PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			wp_parse_str( $query, $params );
			if ( isset( $params['rest_route'] ) ) {
				// Plain permalinks: the route is in the rest_route parameter of the URL itself.
				wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $url_params );
				return isset( $url_params['rest_route'] ) ? $url_params['rest_route'] : false;
			}
		}
		$path = str_replace( $rest_url, '', $url );
		return '/' . ltrim( $path, '/' );
	}


	/**
	 * Fetches and decodes a REST API route.
	 *
	 * Retries with a browser user agent on 403, 404 and 415 responses.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $rest_url REST API root URL.
	 * @param string     $path     Route to fetch.
	 * @param bool       $cache    Optional. Whether to cache the raw response in a
	 *                             transient for a week. Default false.
	 * @param array|null $query Optional. Query arguments for the request. Default
	 *                         null, which adds _embed=1.
	 * @return array|WP_Error The decoded response. Collection responses are wrapped
	 *                        as array( 'items' => ..., '_total' => ..., '_pages' => ... )
	 *                        from the X-WP-Total headers. WP_Error if the request
	 *                        fails or the response is not application/json.
	 */
	public static function fetch( $rest_url, $path, $cache = false, $query = null ) {
		if ( empty( $rest_url ) || ! $rest_url ) {
			return new \WP_Error( 'no_url', __( 'No URL provided', 'parse-this' ) );
		}

		$url = self::get_rest_url( $rest_url, $path, $query );
		// Transient names are limited to 172 characters, so hash the URL.
		$key = 'pt_rest_' . md5( $url );
		if ( $cache ) {
			$transient = get_transient( $key );
			if ( false !== $transient ) {
				return json_decode( $transient, true );
			}
		}

		$response = pt_remote_get( $url, array(), array( 404, 403, 415 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		// Strip any character set off the content type.
		$ct = explode( ';', $content_type );
		if ( is_array( $ct ) ) {
			$content_type = array_shift( $ct );
		}
		$content_type = trim( $content_type );
		// List of content types we know how to handle.
		if ( 'application/json' !== $content_type ) {
			return new \WP_Error( 'content-type', 'Retrieved incorrect page', array( 'content-type' => $content_type ) );
		}

		$content = wp_remote_retrieve_body( $response );
		if ( $cache ) {
			set_transient( $key, $content, WEEK_IN_SECONDS );
		}

		$content = json_decode( $content, true );

		if ( wp_remote_retrieve_header( $response, 'x-wp-total' ) ) {
			$return           = array();
			$return['_total'] = wp_remote_retrieve_header( $response, 'x-wp-total' );
			$return['_pages'] = wp_remote_retrieve_header( $response, 'x-wp-totalpages' );
			$return['items']  = $content;
			return $return;
		} else {
			return $content;
		}
		return false;
	}

	/**
	 * Converts a REST API response into jf2.
	 *
	 * A single post becomes an entry. The API root becomes a card for the site
	 * when $args['return'] is 'single', or the site's latest posts as a feed when
	 * it is 'feed'.
	 *
	 * @since 2.0.0
	 *
	 * @param array|WP_Error $content  Decoded REST API response.
	 * @param string         $rest_url REST API root URL.
	 * @param array          $args     Parse arguments (see Parser::parse()).
	 * @return array|WP_Error|false jf2 data, the WP_Error passed in or from fetching
	 *                              posts, or false if $content is not recognized.
	 */
	public static function parse( $content, $rest_url, $args ) {
		if ( is_wp_error( $content ) ) {
			return $content;
		}
		if ( ! is_array( $content ) ) {
			return false;
		}
		if ( array_key_exists( 'id', $content ) ) {
			return self::get_post( $content, $rest_url );
			// This is the REST URL itself if it has this.
		} elseif ( array_key_exists( 'namespaces', $content ) ) {
			// Return site data if single otherwise feed data.
			if ( 'single' === $args['return'] ) {
				$return       = array(
					'type' => 'card',
				);
				$timezone     = self::timezone( $content );
				$return['tz'] = $timezone->getName();
				if ( array_key_exists( '_embedded', $content ) ) {
					if ( array_key_exists( 'wp:featuredmedia', $content['_embedded'] ) ) {
						$photo = array();
						foreach ( $content['_embedded']['wp:featuredmedia'] as $media ) {
							$photo[] = $media['source_url'] ?? null;
						}
						$photo = array_unique( $photo );
						if ( 1 === count( $photo ) ) {
							$return['photo'] = array_pop( $photo );
						} else {
							$return['photo'] = array_filter( $photo );
						}
					}
				}
				$return['url']  = $content['url'];
				$return['name'] = $content['name'];
				$return['note'] = $content['description'];
				return $return;
			} else {
				$content = self::fetch( $rest_url, '/wp/v2/posts?_embed=1' );
				if ( is_wp_error( $content ) || ! is_array( $content ) ) {
					return $content;
				}

				$content = self::posts_to_feed( $content, $rest_url, $args );
				return $content;
			}
		}
		return false;
	}

	/**
	 * Returns the embedded author of a REST API post as a jf2 card.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item REST API post requested with _embed.
	 * @return array|null Card with name, url, note, photo and me, or null if no
	 *                    author is embedded or it is an error object.
	 */
	public static function get_author( $item ) {
		if ( ! isset( $item['_embedded']['author'][0] ) || ! is_array( $item['_embedded']['author'][0] ) ) {
			return null;
		}
		$author = $item['_embedded']['author'][0];
		if ( array_key_exists( 'code', $author ) ) {
			return null;
		}
		$avatar_urls = $author['avatar_urls'] ?? null;
		$avatar_urls = is_array( $avatar_urls ) ? end( $avatar_urls ) : null;
		$return      = array(
			'type'  => 'card',
			'name'  => $author['name'] ?? null,
			'url'   => $author['url'] ?? null,
			'note'  => $author['description'] ?? null,
			'photo' => $avatar_urls,
			'me'    => $author['me'] ?? null,
		);
		return array_filter( $return );
	}

	/**
	 * Converts a REST API user object into a jf2 card.
	 *
	 * @since 1.0.0
	 *
	 * @param array $json REST API user object.
	 * @return array Card with name, url, note and photo (the largest avatar).
	 */
	public static function format_author( $json ) {
		$avatar_urls = $json['avatar_urls'] ?? null;
		$avatar_urls = is_array( $avatar_urls ) ? end( $avatar_urls ) : null;
		$return      = array(
			'type'  => 'card',
			'name'  => $json['name'] ?? null,
			'url'   => $json['url'] ?? null,
			'note'  => $json['description'] ?? null,
			'photo' => $avatar_urls,
		);
		return $return;
	}

	/**
	 * Converts a REST API date into W3C format.
	 *
	 * Dates without a timezone are interpreted in the remote site's timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $time     Date from the REST API.
	 * @param DateTimeZone|null $timezone Optional. The site's timezone.
	 * @return string|null The date in W3C format, or null if empty or unparseable.
	 */
	public static function get_datetime( $time, $timezone = null ) {
		if ( empty( $time ) || ! is_string( $time ) ) {
			return null;
		}
		try {
			$datetime = new \DateTime( $time );
			if ( 'UTC' === $datetime->getTimeZone()->getName() ) {
				$datetime = new \DateTime( $time, $timezone );
			}
		} catch ( \Exception $e ) {
			return null;
		}
		return $datetime->format( DATE_W3C );
	}

	/**
	 * Returns a REST API post's date in W3C format.
	 *
	 * Uses the UTC <field>_gmt value, converted to the site's timezone when it is
	 * known. Falls back to the site-local <field> value, interpreted in $timezone,
	 * only if the GMT value is missing.
	 *
	 * @since 2.0.0
	 *
	 * @param array             $item     REST API post object.
	 * @param string            $field    'date' or 'modified'.
	 * @param DateTimeZone|null $timezone Optional. The site's timezone.
	 * @return string|null The date, or null if the post has none.
	 */
	public static function post_datetime( $item, $field, $timezone = null ) {
		$gmt = $item[ $field . '_gmt' ] ?? null;
		if ( is_string( $gmt ) && '' !== $gmt ) {
			try {
				$datetime = new \DateTime( $gmt, new \DateTimeZone( 'UTC' ) );
				if ( $timezone instanceof \DateTimeZone ) {
					$datetime->setTimezone( $timezone );
				}
				return $datetime->format( DATE_W3C );
			} catch ( \Exception $e ) {
				// Fall back to the local date below.
				unset( $e );
			}
		}
		return self::get_datetime( $item[ $field ] ?? null, $timezone );
	}

	/**
	 * Returns a site's name, URL, timezone and description from its REST API root.
	 *
	 * Only those fields are requested (_fields), rather than the whole embedded
	 * index, and the response is cached for a week.
	 *
	 * @since 2.0.0
	 *
	 * @param string $rest_url REST API root URL.
	 * @return array Any of name, url, timezone_string, gmt_offset and description
	 *               that are present, or an empty array if the request fails.
	 */
	public static function site_data( $rest_url ) {
		$fetch = self::fetch( $rest_url, '', true, array( '_fields' => implode( ',', self::SITE_FIELDS ) ) );
		if ( is_wp_error( $fetch ) || ! is_array( $fetch ) ) {
			return array();
		}
		return wp_array_slice_assoc( $fetch, self::SITE_FIELDS );
	}

	/**
	 * Returns a site's timezone from its REST API root data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fetch Site data with timezone_string and/or gmt_offset.
	 * @return DateTimeZone The named timezone, or a fixed offset from gmt_offset.
	 */
	public static function timezone( $fetch ) {
		$timezone_string = $fetch['timezone_string'] ?? null;
		if ( $timezone_string ) {
				return new \DateTimeZone( $timezone_string );
		}

		$offset  = (float) ( $fetch['gmt_offset'] ?? null );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
		return new \DateTimeZone( $tz_offset );
	}

	/**
	 * Returns the jf2 type for a post read through the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $item     REST API post object.
	 * @param string $rest_url REST API root URL.
	 * @return string jf2 type. Default 'entry'.
	 */
	public static function jf2_type( $item, $rest_url ) {
		/**
		 * Filters the jf2 type given to a post read through the WordPress REST API.
		 *
		 * @since 2.0.0
		 *
		 * @param string $type     jf2 type. Default 'entry'.
		 * @param array  $item     The REST API post object, including its WordPress
		 *                         post type ('type' => 'post', 'page', ...) and any
		 *                         extra fields such as Post Kinds' 'kind'.
		 * @param string $rest_url REST API root URL of the site.
		 */
		$type = apply_filters( 'parse_this_rest_api_jf2_type', 'entry', $item, $rest_url );
		return ( is_string( $type ) && '' !== $type ) ? $type : 'entry';
	}

	/**
	 * Returns the category and tag names embedded in a REST API post.
	 *
	 * Requires the post to have been requested with _embed, which every
	 * request from this class is.
	 *
	 * @since 2.0.0
	 *
	 * @param array $item REST API post object.
	 * @return string[] Category and tag names, without "Uncategorized".
	 */
	public static function get_categories( $item ) {
		$names = array();
		if ( empty( $item['_embedded']['wp:term'] ) || ! is_array( $item['_embedded']['wp:term'] ) ) {
			return $names;
		}
		foreach ( $item['_embedded']['wp:term'] as $terms ) {
			foreach ( (array) $terms as $term ) {
				if ( isset( $term['taxonomy'], $term['name'] ) && in_array( $term['taxonomy'], array( 'category', 'post_tag' ), true ) && 'Uncategorized' !== $term['name'] ) {
					$names[] = $term['name'];
				}
			}
		}
		return array_values( array_unique( $names ) );
	}

	/**
	 * Fetches a WordPress comment through the REST API and converts it to jf2.
	 *
	 * Used for a #comment-NNN link whose page doesn't mark the comment up with
	 * microformats (as default WordPress comment markup doesn't), or doesn't
	 * show it (a later comment page).
	 *
	 * @since 2.0.0
	 *
	 * @param string $rest_url REST API root URL.
	 * @param int    $id       Comment ID.
	 * @return array|null jf2 entry for the comment, or null if it can't be read
	 *                    (not approved, comments closed to the API, not found).
	 */
	public static function fetch_comment( $rest_url, $id ) {
		$comment = self::fetch( $rest_url, '/wp/v2/comments/' . absint( $id ), false, array() );
		if ( ! is_array( $comment ) || empty( $comment['id'] ) || empty( $comment['link'] ) ) {
			return null;
		}
		return self::get_comment( $comment );
	}

	/**
	 * Converts a REST API comment into a jf2 entry.
	 *
	 * The comment is a reply: to its parent comment when it is threaded, and
	 * otherwise to the post, whose URL is the comment's link without its
	 * fragment.
	 *
	 * @since 2.0.0
	 *
	 * @param array $comment REST API comment object.
	 * @return array jf2 entry.
	 */
	public static function get_comment( $comment ) {
		$link = is_string( $comment['link'] ?? null ) ? $comment['link'] : '';
		$post = strtok( $link, '#' );
		$html = Parser::clean_content( self::get_rendered( 'content', $comment ) );
		// The largest avatar size given.
		$avatars = ( isset( $comment['author_avatar_urls'] ) && is_array( $comment['author_avatar_urls'] ) ) ? $comment['author_avatar_urls'] : array();
		ksort( $avatars, SORT_NUMERIC );
		$parent = absint( $comment['parent'] ?? 0 );
		$jf2    = array(
			'type'        => 'entry',
			'url'         => $link,
			'published'   => self::post_datetime( $comment, 'date' ),
			'author'      => array_filter(
				array(
					'type'  => 'card',
					'name'  => $comment['author_name'] ?? null,
					'url'   => $comment['author_url'] ?? null,
					'photo' => $avatars ? end( $avatars ) : null,
				)
			),
			'content'     => array_filter(
				array(
					'html' => $html,
					'text' => trim( wp_strip_all_tags( $html ) ),
				)
			),
			'in-reply-to' => $parent ? $post . '#comment-' . $parent : $post,
		);
		$jf2              = array_filter( $jf2 );
		$jf2['post-type'] = post_type_discovery( $jf2 );
		return $jf2;
	}

	/**
	 * Converts a single REST API post into a jf2 entry.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item     REST API post object.
	 * @param string $rest_url REST API root URL.
	 * @return array jf2 entry with uid, url, name, content, summary, published,
	 *               updated, kind, featured, category and author where available.
	 */
	public static function get_post( $item, $rest_url ) {
		$site_data = self::site_data( $rest_url );
		$timezone  = self::timezone( $site_data );
		$newitem   = array_filter(
			array(
				'type'      => self::jf2_type( $item, $rest_url ),
				'uid'       => self::get_rendered( 'guid', $item ),
				'url'       => $item['link'] ?? null,
				'name'      => self::get_rendered( 'title', $item ),
				'content'   => array_filter(
					array(
						'html' => Parser::clean_content( self::get_rendered( 'content', $item ) ),
						'text' => wp_strip_all_tags( self::get_rendered( 'content', $item ) ),
					)
				),
				'summary'   => self::get_rendered( 'excerpt', $item ),
				'published' => self::post_datetime( $item, 'date', $timezone ),
				'updated'   => self::post_datetime( $item, 'modified', $timezone ),
				'kind'      => $item['kind'] ?? null,
			)
		);

		if ( array_key_exists( '_embedded', $item ) ) {
			$newitem['featured'] = $item['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;
			$newitem['category'] = self::get_categories( $item );
			$newitem['author']   = self::get_author( $item );
		}
		return array_filter( $newitem );
	}

	/**
	 * Converts a list of REST API posts into a jf2 feed.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $input Array with 'items' (REST API posts) and optionally
	 *                      '_total' and '_pages'.
	 * @param string $url   REST API root URL, used to look up site data.
	 * @param array  $args  Optional. Parse arguments. With 'debug', each item
	 *                      includes the raw REST API post under '_rest'.
	 * @return array jf2 feed with '_feed_type' => 'WordPress', the site's name,
	 *               summary and url, and 'items'.
	 */
	public static function posts_to_feed( $input, $url, $args = array() ) {
		$return            = array_filter(
			array(
				'type'       => 'feed',
				'_feed_type' => 'wordpress',
			)
		);
		$items             = ( isset( $input['items'] ) && is_array( $input['items'] ) ) ? $input['items'] : array();
		$data              = self::site_data( $url );
		$timezone          = self::timezone( $data );
		$return['items']   = array();
		$return['name']    = $data['name'] ?? null;
		$return['summary'] = $data['description'] ?? null;
		$return['url']     = $data['url'] ?? null;
		foreach ( $items as $item ) {
			$newitem = array_filter(
				array(
					'type'      => self::jf2_type( $item, $url ),
					'uid'       => self::get_rendered( 'guid', $item ),
					'url'       => $item['link'] ?? null,
					'name'      => self::get_rendered( 'title', $item ),
					'content'   => array_filter(
						array(
							'html' => Parser::clean_content( self::get_rendered( 'content', $item ) ),
							'text' => wp_strip_all_tags( self::get_rendered( 'content', $item ) ),
						)
					),
					'summary'   => self::get_rendered( 'excerpt', $item ),
					'published' => self::post_datetime( $item, 'date', $timezone ),
					'updated'   => self::post_datetime( $item, 'modified', $timezone ),
					'author'    => self::get_author( $item ),
					'kind'      => $item['kind'] ?? null,
				)
			);
			if ( array_key_exists( '_embedded', $item ) ) {
				$newitem['category'] = self::get_categories( $item );
				$newitem['featured'] = $item['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;
			}
			if ( ! empty( $args['debug'] ) ) {
				$newitem['_rest'] = $item;
			}
			$return['items'][] = array_filter( $newitem );
		}
		if ( array_key_exists( '_pages', $input ) ) {
			$return['_pages'] = $input['_pages'];
			$return['_total'] = $input['_total'];
		}
		return $return;
	}
}
