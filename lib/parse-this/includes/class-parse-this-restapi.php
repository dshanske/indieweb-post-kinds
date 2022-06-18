<?php

class Parse_This_RESTAPI {
	private static function ifset( $key, $array ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : null;
	}

	public static function get_rendered( $key, $item ) {
		if ( ! array_key_exists( $key, $item ) ) {
			return null;
		}
		if ( array_key_exists( 'rendered', $item[ $key ] ) ) {
			return $item[ $key ]['rendered'];
		}
		return null;
	}

	public static function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	public static function get_rest_url( $rest_url, $path ) {
		if ( ! wp_http_validate_url( $rest_url ) ) {
			return false;
		}
		$path  = '/' . ltrim( $path, '/' );
		$query = wp_parse_url( $rest_url, PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			$query = explode( '=', $query );
			if ( array_key_exists( 'rest_route' ) ) {
				return add_query_arg( 'rest_route', $path, trailingslashit( $rest_url ) );
			}
			return false;
		}

		$rest_url = untrailingslashit( $rest_url );
		return $rest_url . $path;
	}

	public static function get_rest_path( $rest_url, $url ) {
		if ( ! wp_http_validate_url( $rest_url ) ) {
			return false;
		}
		$query = wp_parse_url( $rest_url, PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			$query = explode( '=', $query );
			if ( array_key_exists( 'rest_route' ) ) {
				return $query['rest_route'];
			}
		}
		$path = str_replace( $rest_url, '', $url );
		return '/' . ltrim( $path, '/' );
	}


	public static function fetch( $rest_url, $path, $cache = false ) {
		$url = self::get_rest_url( $rest_url, $path );
		$key = 'pt_rest_' . self::base64url_encode( $url );
		if ( $cache ) {
			$transient = get_transient( $key );
			if ( false !== $transient ) {
				return json_decode( $transient, true );
			}
		}

		$user_agent = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36 Parse This/WP';
		$args       = array(
			'timeout'             => 15,
			'limit_response_size' => 1048576,
			'redirection'         => 5,
			// Use an explicit user-agent for Parse This
		);

		$response      = wp_safe_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$content_type  = wp_remote_retrieve_header( $response, 'content-type' );
		if ( in_array( $response_code, array( 404, 403, 415 ), true ) ) {
			$args['user-agent'] = $user_agent;
			$response           = wp_safe_remote_get( $url, $args );
			$response_code      = wp_remote_retrieve_response_code( $response );
			if ( in_array( $response_code, array( 404, 403, 415 ), true ) ) {
				return new WP_Error( 'source_error', 'Unable to Retrieve' );
			}
		}

		// Strip any character set off the content type
		$ct = explode( ';', $content_type );
		if ( is_array( $ct ) ) {
			$content_type = array_shift( $ct );
		}
		$content_type = trim( $content_type );
		// List of content types we know how to handle
		if ( 'application/json' !== $content_type ) {
			return new WP_Error( 'content-type', 'Retrieved incorrect page', array( 'content-type' => $content_type ) );
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

	public static function parse( $content, $rest_url, $args ) {
		// This is the REST URL itself if it has this.
		if ( array_key_exists( 'namespaces', $content ) ) {
			// Return site data if single otherwise feed data.
			if ( 'single' === $args['return'] ) {
				$return       = array(
					'type' => 'card',
				);
				$timezone     = self::timezone( $content );
				$return['tz'] = $timezone->getName();
				if ( array_key_exists( '_links', $content ) ) {
					if ( array_key_exists( 'wp:featuredmedia', $content['_links'] ) ) {
						$photo = array();
						foreach ( $content['_links']['wp:featuredmedia'] as $media ) {
							$photo[] = self::get_featured( $rest_url, self::get_rest_path( $rest_url, $media['href'] ) );
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
				$content = self::fetch( $rest_url, '/wp/v2/posts' );

				$content = self::posts_to_feed( $content, $rest_url );
				return $content;
			}
		} elseif ( array_key_exists( 'id', $content ) ) {
			return self::get_post( $content, $rest_url );
		}
		return false;
	}

	public static function get_author( $rest_url, $path ) {
		$json = self::fetch( $rest_url, $path );
		if ( is_wp_error( $json ) ) {
			return null;
		}
		$avatar_urls = self::ifset( 'avatar_urls', $json );
		$avatar_urls = is_array( $avatar_urls ) ? end( $avatar_urls ) : null;
		$return      = array(
			'type'  => 'card',
			'name'  => self::ifset( 'name', $json ),
			'url'   => self::ifset( 'url', $json ),
			'note'  => self::ifset( 'description', $json ),
			'photo' => $avatar_urls,
		);
		return array_filter( $return );
	}


	public static function get_authors( $rest_url, $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}
		$json = self::fetch( $rest_url, sprintf( 'wp/v2/users/?include=%1$s', implode( ',', $ids ) ) );
		if ( is_wp_error( $json ) ) {
			return null;
		}
		$return = array();
		foreach ( $json as $author ) {
			$return[ $author['id'] ] = self::format_author( $author );
		}
		return $return;
	}

	public static function format_author( $json ) {
		$avatar_urls = self::ifset( 'avatar_urls', $json );
		$avatar_urls = is_array( $avatar_urls ) ? end( $avatar_urls ) : null;
		$return      = array(
			'type'  => 'card',
			'name'  => self::ifset( 'name', $json ),
			'url'   => self::ifset( 'url', $json ),
			'note'  => self::ifset( 'description', $json ),
			'photo' => $avatar_urls,
		);
		return $return;
	}

	public static function get_featured( $rest_url, $path ) {
		$json = self::fetch( $rest_url, $path );
		if ( is_wp_error( $json ) ) {
			return null;
		}
		return self::ifset( 'source_url', $json );
	}

	public static function get_media( $rest_url, $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}
		$json = self::fetch( $rest_url, sprintf( 'wp/v2/media/?include=%1$s', implode( ',', $ids ) ) );
		if ( is_wp_error( $json ) ) {
			return null;
		}
		return wp_list_pluck( $json, 'guid', 'id' );
	}

	public static function get_tags( $rest_url, $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}
		$json = self::fetch( $rest_url, sprintf( '/wp/v2/tags?include=%1$s', implode( ',', $ids ) ) );
		if ( is_wp_error( $json ) ) {
			return null;
		}
		return wp_list_pluck( $json, 'name', 'id' );
	}

	public static function get_datetime( $time, $timezone = null ) {
		$datetime = new DateTime( $time );
		if ( 'UTC' === $datetime->getTimeZone()->getName() ) {
			$datetime = new DateTime( $time, $timezone );
		}
		return $datetime->format( DATE_W3C );
	}

	public static function site_data( $rest_url ) {
		$fetch = self::fetch( $rest_url, '', true );
		return wp_array_slice_assoc( $fetch, array( 'name', 'url', 'timezone_string', 'gmt_offset', 'description' ) );
	}

	public static function timezone( $fetch ) {
		$timezone_string = self::ifset( 'timezone_string', $fetch );
		if ( $timezone_string ) {
				return new DateTimeZone( $timezone_string );
		}

		$offset  = (float) self::ifset( 'gmt_offset', $fetch );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
		return new DateTimeZone( $tz_offset );
	}

	public static function get_post( $item, $rest_url ) {
		$site_data = self::site_data( $rest_url );
		$author    = self::get_rest_path( $rest_url, $item['_links']['author'][0]['href'] );
		$timezone  = self::timezone( $site_data );
		$newitem   = array_filter(
			array(
				'uid'       => self::get_rendered( 'guid', $item ),
				'url'       => self::ifset( 'link', $item ),
				'name'      => self::get_rendered( 'title', $item ),
				'content'   => array_filter(
					array(
						'html' => Parse_This::clean_content( self::get_rendered( 'content', $item ) ),
						'text' => wp_strip_all_tags( self::get_rendered( 'content', $item ) ),
					)
				),
				'summary'   => self::get_rendered( 'excerpt', $item ),
				'published' => self::get_datetime( self::ifset( 'date', $item ), $timezone ),
				'updated'   => self::get_datetime( self::ifset( 'modified', $item ), $timezone ),
				'author'    => self::get_author( $rest_url, $author ),
				'kind'      => self::ifset( 'kind', $item ),
			)
		);
		if ( array_key_exists( 'featured_media', $item ) ) {
			$featured            = self::get_rest_path( $rest_url, $item['_links']['wp:featuredmedia'][0]['href'] );
			$newitem['featured'] = self::get_featured( $rest_url, $featured );
		}
		if ( array_key_exists( 'tags', $item ) && ! empty( $item['tags'] ) ) {
			foreach ( $item['_links']['wp:term'] as $term ) {
				if ( 'post_tag' === $term['taxonomy'] ) {
					$tag_path            = self::get_rest_path( $rest_url, $term['href'] );
					$tags                = self::fetch( $rest_url, $tag_path );
					$newitem['category'] = wp_list_pluck( $tags['items'], 'name' );
				}
			}
		}
		return array_filter( $newitem );
	}

	public static function posts_to_feed( $input, $url ) {
		$return            = array_filter(
			array(
				'type'       => 'feed',
				'_feed_type' => 'wordpress',
			)
		);
		$items             = $input['items'];
		$data              = self::site_data( $url );
		$timezone          = self::timezone( $data );
		$media_ids         = wp_list_pluck( $items, 'featured_media' );
		$author_ids        = wp_list_pluck( $items, 'author' );
		$tag_ids           = wp_list_pluck( $items, 'tags' );
		$tag_ids           = array_merge( ...$tag_ids );
		$media             = self::get_media( $url, $media_ids );
		$authors           = self::get_authors( $url, $author_ids );
		$tags              = self::get_tags( $url, $tag_ids );
		$return['items']   = array();
		$return['name']    = self::ifset( 'name', $data );
		$return['summary'] = self::ifset( 'description', $data );
		$return['url']     = self::ifset( 'url', $data );
		foreach ( $items as $item ) {
			$newitem = array_filter(
				array(
					'uid'       => self::get_rendered( 'guid', $item ),
					'url'       => self::ifset( 'link', $item ),
					'name'      => self::get_rendered( 'title', $item ),
					'content'   => array_filter(
						array(
							'html' => Parse_This::clean_content( self::get_rendered( 'content', $item ) ),
							'text' => wp_strip_all_tags( self::get_rendered( 'content', $item ) ),
						)
					),
					'summary'   => self::get_rendered( 'excerpt', $item ),
					'published' => self::get_datetime( self::ifset( 'date', $item ), $timezone ),
					'updated'   => self::get_datetime( self::ifset( 'modified', $item ), $timezone ),
					'author'    => $authors[ $item['author'] ],
					'kind'      => self::ifset( 'kind', $item ),
				)
			);
			if ( array_key_exists( 'tags', $item ) ) {
				$category            = array_values( array_intersect_key( $tags, array_flip( $item['tags'] ) ) );
				$newitem['category'] = $category;
			}
			if ( array_key_exists( 'featured_media', $item ) ) {
				if ( array_key_exists( (int) $item['featured_media'], $media ) ) {
					$newitem['featured'] = $media[ intval( $item['featured_media'] ) ]['rendered'];
				}
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



