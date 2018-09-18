<?php
/**
 * MF2 Post Class
 *
 * @package Post Kinds
 * Assists in retrieving/saving microformats 2 properties from a post
 */
class MF2_Post {
	public $uid;
	public $post_author;
	public $author;
	public $publication;
	public $published;
	public $updated;
	public $content;
	public $summary;
	public $post_parent;
	public $kind;
	public $url;
	public $name;
	public $category = array();
	public $featured;
	private $mf2;

	public function __construct( $post ) {
		if ( is_numeric( $post ) ) {
			$this->uid = (int) $post;
		} elseif ( $post instanceof WP_Post ) {
			$this->uid = $post->ID;
		}
		$_mf2_post = wp_cache_get( $this->uid, 'mf2_posts' );
		if ( is_object( $_mf2_post ) ) {
			return $_mf2_post;
		}
		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}
		$this->post_author = $post->post_author;
		$this->author      = self::get_author();
		$this->post_parent = $post->post_parent;
		$this->published   = mysql2date( DATE_W3C, $post->post_date );
		$this->updated     = mysql2date( DATE_W3C, $post->post_modified );
		$this->publication = get_bloginfo( 'title' );
		if ( ! empty( $post->post_content ) ) {
			$this->content = array(
				'html'  => $post->post_content,
				'value' => wp_strip_all_tags( $post->post_content ),
			);
		}
		$this->summary = $post->post_excerpt;
		$this->mf2     = $this->get_mf2meta();
		$this->url     = get_permalink( $post->ID );
		$this->name    = $post->post_title;
		if ( $this->uid === (int) $this->name ) {
			unset( $this->name );
		}
		// Get a list of categories and extract their names
		$post_categories = get_the_terms( $post->ID, 'category' );
		if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) {
			$this->category = wp_list_pluck( $post_categories, 'name' );
		}

		// Get a list of tags and extract their names
		$post_tags = get_the_terms( $post->ID, 'post_tag' );
		if ( ! empty( $post_tags ) && ! is_wp_error( $post_tags ) ) {
			$this->category = array_merge( $this->category, wp_list_pluck( $post_tags, 'name' ) );
		}
		if ( in_array( 'Uncategorized', $this->category, true ) ) {
			unset( $this->category[ array_search( 'Uncategorized', $this->category, true ) ] );
		}
		if ( has_post_thumbnail( $post ) ) {
			$this->featured = wp_get_attachment_url( get_post_thumbnail_id( $post ), 'full' );
		}
		$this->kind = self::get_post_kind();
		wp_cache_set( $this->uid, $this, 'mf2_posts' );
	}

	private function get_post_kind() {
		if ( function_exists( 'get_post_kind_slug' ) ) {
			return get_post_kind_slug( $this->uid );
		} else {
			$mf2 = array(
				'type'       => array( 'h-entry' ),
				'properties' => $this->mf2,
			);
			return Parse_This_MF2::post_type_discovery( $mf2 );
		}
	}

	public static function get_post() {
		return get_post( $this->uid );
	}

	/**
	 * Is prefix in string.
	 *
	 * @param  string $source The source string.
	 * @param  string $prefix The prefix you wish to check for in source.
	 * @return boolean The result.
	 */
	public static function str_prefix( $source, $prefix ) {
		return strncmp( $source, $prefix, strlen( $prefix ) ) === 0;
	}

	/**
	 * Returns True if Array is Multidimensional.
	 *
	 * @param array $arr array.
	 *
	 * @return boolean result
	 */
	public static function is_multi_array( $arr ) {
		if ( count( $arr ) === count( $arr, COUNT_RECURSIVE ) ) {
			return false;
		} else {
			return true;
		}
	}

	public static function sanitize_content( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$allowed = wp_kses_allowed_html( 'post' );
		if ( 1 === (int) get_option( 'kind_protection' ) ) {
			$allowed = json_decode( get_option( 'kind_kses' ), true );
		}
		return wp_kses( $value, $allowed );
	}

	public static function sanitize_text( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_text' ), $value );
		}
		if ( wp_http_validate_url( $value ) ) {
			$value = esc_url_raw( $value );
		} else {
			$value = esc_attr( $value );
		}
		return $value;
	}

	/**
	 * Retrieve author
	 *
	 * @return boolean|array The result or false if does not exist.
	 */
	public function get_author() {
		if ( ! $this->post_author ) {
			return false;
		}
		return array(
			'type'       => array( 'h-card' ),
			'properties' => array(
				'name'  => array( get_the_author_meta( 'display_name', $this->post_author ) ),
				'url'   => array( get_the_author_meta( 'user_url', $this->post_author ) ? get_the_author_meta( 'user_url', $this->post_author ) : get_author_posts_url( $this->post_author ) ),
				'photo' => array( get_avatar_url( $this->post_author ) ),
			),
		);
	}

	/**
	 * Sets an array with only the mf2 prefixed meta.
	 *
	 */
	private function get_mf2meta() {
		$meta = get_post_meta( $this->uid );
		if ( isset( $meta['response'] ) ) {
			$response = maybe_unserialize( $meta['response'] );
			// Retrieve from the old response array and store in new location.
			if ( ! empty( $response ) ) {
				$new = array();
				// Convert to new format and update.
				if ( ! empty( $response['title'] ) ) {
					$new['name'] = $response['title'];
				}
				if ( ! empty( $response['url'] ) ) {
					$new['url'] = $response['url'];
				}
				if ( ! empty( $response['content'] ) ) {
					$new['content'] = $response['content'];
				}
				if ( ! empty( $response['published'] ) ) {
					$new['published'] = $response['published'];
				}
				if ( ! empty( $response['author'] ) ) {
					$new['card']         = array();
					$new['card']['name'] = $response['author'];
					if ( ! empty( $response['icon'] ) ) {
						$new['card']['photo'] = $response['icon'];
					}
				}
				$new         = array_unique( $new );
				$new['card'] = array_unique( $new['card'] );
				if ( isset( $new ) ) {
					update_post_meta( $this->uid, 'mf2_cite', $new );
					delete_post_meta( $this->uid, 'response' );
					$meta['cite'] = $new;
				}
			}
		}
		foreach ( $meta as $key => $value ) {
			if ( ! self::str_prefix( $key, 'mf2_' ) ) {
				unset( $meta[ $key ] );
			} else {
				unset( $meta[ $key ] );
				$key = str_replace( 'mf2_', '', $key );
				// Do not save microput prefixed instructions
				if ( self::str_prefix( $key, 'mp-' ) ) {
					continue;
				}
				$value = array_map( 'maybe_unserialize', $value );
				if ( 1 === count( $value ) ) {
					$value = array_shift( $value );
				}
				if ( is_string( $value ) ) {
					$meta[ $key ] = array( $value );
				} else {
					$meta[ $key ] = self::ensure_mf2( $key, $value );
				}
			}
		}
		return array_filter( $meta );
	}

	// To fix issues with possible errors with mf2 parsing
	private function ensure_mf2( $key, $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		foreach ( $value as $k => $v ) {
			$value[ $k ] = self::ensure_mf2( $key, $v );
		}
		if ( ! wp_is_numeric_array( $value ) && ! isset( $value['type'] ) ) {
			// These were the only two ones used before the enhancement
			if ( 'checkin' === $key ) {
				$value['type'] = 'h-card';
			} else {
				$value['type'] = 'h-cite';
			}
		}
		return $value;
	}

	/**
	 * Retrieve value
	 *
	 * @param  string $key The key to retrieve.
	 * @param  boolean $single Whether to return a a single value or array if there is only one value.
	 * @return boolean|string|array The result or false if does not exist.
	 */
	public function get( $key = null, $single = true ) {
		if ( 'mf2' === $key ) {
			return $this->mf2;
		}
		if ( null === $key ) {
			$vars = get_object_vars( $this );
			unset( $vars['mf2'] );
			$vars = array_filter( $vars );
			foreach ( $vars as $prop => $value ) {
				$vars[ $prop ] = array( $value );
			}
			$properties = array_merge( $vars, $this->mf2 );
			$properties = array_filter( $properties );
			$return     = array(
				'type'       => array( 'h-entry' ),
				'properties' => $properties,
			);
			if ( $single ) {
				return mf2_to_jf2( $return );
			}
			return $return;
		}
		$properties = array_keys( get_object_vars( $this ) );
		unset( $properties['mf2'] );
		if ( in_array( $key, $properties, true ) ) {
			$return = $this->$key;
		} else {
			if ( ! isset( $this->mf2[ $key ] ) ) {
				return false;
			}
			$return = $this->mf2[ $key ];
		}
		if ( empty( $return ) ) {
			return false;
		}
		if ( is_array( $return ) ) {
			return $single ? $this->single_array( $return ) : $return;
		}
		if ( is_string( $return ) ) {
			return $single ? $return : array( $return );
		}
	}

	public function has_key( $key ) {
		$keys = array_merge( get_object_vars( $this ), $this->mf2 );
		return isset( $keys[ $key ] );
	}

	private function single_array( $value, $discard = false ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( 1 === count( $value ) ) {
			return array_shift( $value );
		}
		if ( $discard && wp_is_numeric_array( $value ) ) {
			return array_shift( $value );
		}
		if ( self::is_multi_array( $value ) ) {
			return array_map( array( $this, 'single_array' ), $value );
		}
		return $value;
	}

	public function set( $key, $value = null ) {
		if ( ! $key ) {
			return;
		}
		if ( is_array( $key ) ) {
			foreach ( $key as $k => $v ) {
				self::set( $k, $v );
			}
		}
		if ( null === $value || empty( $value ) ) {
			return;
		}
		$properties = array_keys( get_object_vars( $this ) );
		unset( $properties['mf2'] );
		if ( ! in_array( $key, $properties, true ) ) {
			update_post_meta( $this->uid, 'mf2_' . $key, $value );
		} else {
			switch ( $key ) {
				case 'url':
					break;
				case 'published':
					$date      = new DateTime( $value );
					$tz_string = get_option( 'timezone_string' );
					if ( empty( $tz_string ) ) {
						$tz_string = 'UTC';
					}
					$date->setTimeZone( new DateTimeZone( $tz_string ) );
					$tz        = $date->getTimezone();
					$post_date = $date->format( 'Y-m-d H:i:s' );
					$date->setTimeZone( new DateTimeZone( 'GMT' ) );
					$post_date_gmt = $date->format( 'Y-m-d H:i:s' );
					wp_update_post(
						array(
							'ID'            => $this->uid,
							'post_date'     => $post_date,
							'post_date_gmt' => $post_date_gmt,
						)
					);
					break;
				case 'updated':
					$date      = new DateTime( $value );
					$tz_string = get_option( 'timezone_string' );
					if ( empty( $tz_string ) ) {
						$tz_string = 'UTC';
					}
					$date->setTimeZone( new DateTimeZone( $tz_string ) );
					$tz            = $date->getTimezone();
					$post_modified = $date->format( 'Y-m-d H:i:s' );
					$date->setTimeZone( new DateTimeZone( 'GMT' ) );
					$post_modified_gmt = $date->format( 'Y-m-d H:i:s' );
					wp_update_post(
						array(
							'ID'                => $this->uid,
							'post_modified'     => $post_modified,
							'post_modified_gmt' => $post_modified_gmt,
						)
					);
					break;
				case 'content':
					$key = 'post_content';
					wp_update_post(
						array(
							'ID' => $this->uid,
							$key => $value,
						)
					);
					break;
				case 'summary':
					$key = 'post_excerpt';
					wp_update_post(
						array(
							'ID' => $this->uid,
							$key => $value,
						)
					);
					break;
				default:
					wp_update_post(
						array(
							'ID' => $this->uid,
							$key => $value,
						)
					);
			}
		}
	}

	public function delete( $key ) {
		delete_post_meta( $this->uid, 'mf2_' . $key );
	}

	public function mf2_to_jf2( $cite ) {
		return mf2_to_jf2( $cite );
	}

	public function get_single( $value ) {
		if ( is_array( $value ) ) {
			return array_shift( $value );
		}
		return $value;
	}

	public function jf2_to_mf2( $cite, $type = 'cite' ) {
		if ( ! $cite || ! is_array( $cite ) | isset( $cite['properties'] ) ) {
			return $cite;
		}
		$cite = ifset( $cite['type'], $type );
		return jf2_to_mf2( $cite );
	}

	// Retrieve the right property to use for the link preview based on the kind.
	// It will return an array of properties or false if it cannot find what it needs.
	// Also will update old posts with new settings
	public function fetch( $property ) {

		// If the property is not set then exit
		if ( ! $property || ! $this->has_key( $property ) ) {
			return false;
		}
		$return = $this->get( $property );
		if ( wp_is_numeric_array( $return ) ) {
			$return = array_shift( $return );
		}
		// If it is in fact a string it is the pre 2.7.0 format and should be updated
		if ( is_string( $return ) ) {
			if ( $this->has_key( 'cite' ) ) {
				$cite        = array_filter( $this->get( 'cite' ) );
				$cite['url'] = $return;
				$this->set( $property, $cite );
				$this->delete( 'cite' );
				return $cite;
			} else {
				return array( 'url' => $return );
			}
		}
		if ( is_array( $return ) ) {
			return mf2_to_jf2( $return );
		}
		return false;
	}

	public function get_attached_media( $type, $post ) {
		$posts  = get_attached_media( $type, $post );
		$return = array();
		foreach ( $posts as $post ) {
			$return[] = $post->post_ID;
		}
		return array_filter( $return );
	}

	public function get_audios() {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'audio', $this->uid ) ) {
			return array( $this->uid );
		}
		$att_ids = $this->get_attached_media( 'audio', $this->uid );
		$audios  = $this->get( 'audio' );
		$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $audios ) );
		if ( ! empty( $att_ids ) ) {
			return $att_ids;
		}
		return false;
	}

	public function get_videos() {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'video', $this->uid ) ) {
			return array( $this->uid );
		}
		$att_ids = $this->get_attached_media( 'video', $this->uid );
		$videos  = $this->get( 'video' );
		$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $videos ) );
		if ( ! empty( $att_ids ) ) {
			return $att_ids;
		}
		return false;
	}

	public function get_images( $content_allow = false ) {
		// Check if the post itself is an image attachment.
		if ( wp_attachment_is( 'image', $this->uid ) ) {
			return array( $this->uid );
		}
		$post_content = ifset( $this->content['html'] );
		if ( $post_content ) {
			preg_match( '/id=[\'"]wp-image-([\d]*)[\'"]/i', $post_content, $att_ids );
			// If the content_allow flag is true then return the ids else return false so that there will not be double images
			if ( is_array( $att_ids ) && ! empty( $att_ids ) ) {
				return $content_allow ? $att_ids : array();
			}
			// Search the post's content for the <img /> tag and get its URL.
			$urls    = self::get_img_urls_from_content( $post_content );
			$att_ids = self::get_attachments_from_urls( $urls );
			if ( ! empty( $att_ids ) ) {
				return $content_allow ? $att_ids : array();
			}
		}
		// If there is a featured image return only that. Otherwise return all images
		$featured = get_post_thumbnail_id( $this->uid );
		if ( $featured ) {
			return $featured;
		}
		$att_ids = $this->get_attached_media( 'image', $this->uid );
		$photos  = $this->get( 'photo', false );
		if ( is_array( $photos ) ) {
			$newphotos = $this->sideload_images( $photos );
			$diff      = array_diff( $photos, $newphotos );
			if ( ! empty( $diff ) ) {
				$this->set( 'photo' );
			}
		}
		$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $photos ) );
		if ( ! empty( $att_ids ) ) {
			return $att_ids;
		}
		return false;
	}

	private function sideload_images( $photos ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		foreach ( $photos as $key => $value ) {
			if ( ! attachment_url_to_postid( $value ) ) {
				$id = media_sideload_image( $value, $this->uid, null, 'id' );
				if ( $id ) {
					$photos[ $key ] = wp_get_attachment_url( $id );
				}
			}
		}
		return $photos;
	}

	public function get_img_urls_from_content( $content ) {
		$content = wp_unslash( $content );
		$urls    = array();
		if ( preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
			foreach ( (array) $matches[0] as $image ) {
				if ( ! preg_match( '/src="([^"]+)"/', $image, $url_matches ) ) {
					continue;
				}
				if ( ! preg_match( '/[^\?]+\.(?:jpe?g|jpe|gif|png)(?:\?|$)/i', $url_matches[1] ) ) {
					continue;
				}
				$urls[] = $url_matches[1];
			}
		}
		return array_unique( $urls );
	}

	public function get_attachments_from_urls( $urls ) {
		if ( is_string( $urls ) ) {
			$urls = array( $urls );
		}
		$att_ids = array();
		if ( wp_is_numeric_array( $urls ) ) {
			foreach ( $urls as $url ) {
				$att_ids[] = attachment_url_to_postid( $url );
			}
		}
		return array_filter( array_unique( $att_ids ) );
	}

	public static function clean_post_cache( $post_id ) {
		wp_cache_delete( $post_id, 'mf2_posts' );
		self::cache_last_modified();
	}

	public static function clean_cache_meta( $empty, $post_id ) {
		self::clean_post_cache( $post_id );
	}

	public static function cache_last_modified() {
		wp_cache_set( 'last_changed', microtime(), 'mf2_posts' );
	}

}


add_action( 'added_post_meta', array( 'MF2_Post', 'clean_cache_meta' ), 10, 2 );
add_action( 'updated_post_meta', array( 'MF2_Post', 'clean_cache_meta' ), 10, 2 );
add_action( 'deleted_post_meta', array( 'MF2_Post', 'clean_cache_meta' ), 10, 2 );
add_action( 'clean_post_cache', array( 'MF2_Post', 'clean_post_cache' ) );
