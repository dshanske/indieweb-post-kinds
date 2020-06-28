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

	public static function fetch( $url, $endpoint, $cache = false ) {
		$url = str_replace( 'wp/v2/posts', $endpoint, $url );
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
		$response_code = wp_remote_retrieve_response_code( $response );
		$content_type  = wp_remote_retrieve_header( $response, 'content-type' );
		if ( in_array( $response_code, array( 403, 415 ), true ) ) {
			$args['user-agent'] = $user_agent;
			$response           = wp_safe_remote_get( $url, $args );
			$response_code      = wp_remote_retrieve_response_code( $response );
			if ( in_array( $response_code, array( 403, 415 ), true ) ) {
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
		return json_decode( $content, true );
	}

	public static function get_author( $id, $url ) {
		$json = self::fetch( $url, sprintf( 'wp/v2/users/%s', $id ) );
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
		return $return;
	}


	public static function get_authors( $url, $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}
		$json = self::fetch( $url, sprintf( 'wp/v2/users/?include=%1$s', implode( ',', $ids ) ) );
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

	public static function get_featured( $id, $url ) {
		$json = self::fetch( $url, sprintf( 'wp/v2/media/%s', $id ) );
		if ( is_wp_error( $json ) ) {
			return null;
		}
		return self::ifset( 'source_url', $json );
	}

	public static function get_media( $url, $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}
		$json = self::fetch( $url, sprintf( 'wp/v2/media/?include=%1$s', implode( ',', $ids ) ) );
		if ( is_wp_error( $json ) ) {
			return null;
		}
		return wp_list_pluck( $json, 'guid', 'id' );
	}

	public static function get_datetime( $time, $timezone = null ) {
		$datetime = new DateTime( $time );
		if ( 'UTC' === $datetime->getTimeZone()->getName() ) {
			$datetime = new DateTime( $time, $timezone );
		}
		return $datetime->format( DATE_W3C );
	}

	public static function feed_data( $url ) {
		$fetch = self::fetch( $url, '', true );
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

	public static function to_jf2( $content, $url ) {
		$return            = array_filter(
			array(
				'type'       => 'feed',
				'_feed_type' => 'wordpress',
			)
		);
		$data              = self::feed_data( $url );
		$timezone          = self::timezone( $data );
		$media_ids         = wp_list_pluck( $content, 'featured_media' );
		$author_ids        = wp_list_pluck( $content, 'author' );
		$media             = self::get_media( $url, $media_ids );
		$authors           = self::get_authors( $url, $author_ids );
		$return['items']   = array();
		$return['name']    = self::ifset( 'name', $data );
		$return['summary'] = self::ifset( 'description', $data );
		$return['url']     = self::ifset( 'url', $data );
		foreach ( $content as $item ) {
			if ( ! array_key_exists( $item['author'], $authors ) ) {
				$authors[ $item['author'] ] = self::get_author( $item['author'], $url );
			}
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
			if ( array_key_exists( 'featured_media', $item ) ) {
				if ( array_key_exists( (int) $item['featured_media'], $media ) ) {
					$newitem['featured'] = $media[ intval( $item['featured_media'] ) ]['rendered'];
				} else {
					$newitem['featured'] = self::get_featured( $item['featured_media'], $url );
				}
			}
			$return['items'][] = $newitem;
		}
		return $return;
	}
}



