<?php
/**
 * MF2 Post Class
 *
 * @package Post Kinds
 * Assists in retrieving/saving microformats 2 properties from a post
 */
class MF2_Post implements ArrayAccess {
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
	private $mf2 = array();

	public function __construct( $post ) {
		if ( is_numeric( $post ) ) {
			$this->uid = (int) $post;
		} elseif ( $post instanceof WP_Post ) {
			$this->uid = $post->ID;
		} elseif ( wp_http_validate_url( $post ) ) {
			$id = url_to_postid( $post );
			if ( $id ) {
				$this->uid = $id;
				$post      = $id;
			} else {
				$id        = attachment_url_to_postid( $post );
				$this->uid = $id;
				$post      = $id;
			}
		}
		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}
		$this->post_author = $post->post_author;
		$this->author      = self::get_author();
		$this->post_parent = $post->post_parent;
		$this->published   = get_the_date( DATE_W3C, $post );
		$this->updated     = get_the_modified_date( DATE_W3C, $post );
		$this->publication = get_bloginfo( 'title' );
		if ( ! empty( $post->post_content ) ) {
			$this->content = array(
				'html'  => $post->post_content,
				'value' => wp_strip_all_tags( $post->post_content ),
			);
		}
		$this->summary  = $post->post_excerpt;
		$this->mf2      = $this->get_mf2meta();
		$this->url      = get_permalink( $post->ID );
		$this->name     = $post->post_title;
		$this->category = $this->get_categories( $post->ID );
		if ( $this->uid === (int) $this->name ) {
			unset( $this->name );
		}
		if ( has_post_thumbnail( $post ) ) {
			$this->featured = wp_get_attachment_url( get_post_thumbnail_id( $post ), 'full' );
		}
		$this->kind = self::get_post_kind();
	}

	public function offsetExists( $offset ) {
		$vars = get_object_vars( $this );
		if ( array_key_exists( $offset, $vars ) ) {
			return true;
		}
		return array_key_exists( $offset, $this->mf2 );
	}

	public function offsetGet( $offset ) {
		$vars = get_object_vars( $this );
		if ( array_key_exists( $offset, $vars ) ) {
			return $vars[ $offset ];
		}
		if ( array_key_exists( $offset, $this->mf2 ) ) {
			return $this->mf2[ $offset ];
		}
		return null;
	}

	public function offsetSet( $offset, $value ) {
		$this->set( $offset, $value );
	}

	public function offsetUnset( $offset ) {
		$this->delete( $offset );
	}

	public function get_categories( $post_id ) {
		$category = array();
		// Get a list of categories and extract their names
		$post_categories = get_the_terms( $post_id, 'category' );
		if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) {
			$category = wp_list_pluck( $post_categories, 'name' );
		}

		// Get a list of tags and extract their names
		$post_tags = get_the_terms( $post_id, 'post_tag' );
		if ( ! empty( $post_tags ) && ! is_wp_error( $post_tags ) ) {
			$category = array_merge( $this->category, wp_list_pluck( $post_tags, 'name' ) );
		}
		if ( in_array( 'Uncategorized', $category, true ) ) {
			unset( $category[ array_search( 'Uncategorized', $category, true ) ] );
		}
		return $category;
	}

	private function get_post_kind() {
		if ( is_attachment( $this->uid ) ) {
			if ( wp_attachment_is( 'image', $this->uid ) ) {
				return 'photo';
			}
			if ( wp_attachment_is( 'video', $this->uid ) ) {
				return 'video';
			}
			if ( wp_attachment_is( 'audio', $this->uid ) ) {
				return 'audio';
			}
			return null;
		}
		if ( function_exists( 'get_post_kind_slug' ) ) {
			return get_post_kind_slug( $this->uid );
		} else {
			$mf2 = array(
				'type'       => array( 'h-entry' ),
				'properties' => $this->mf2,
			);
			return post_type_discovery( mf2_to_jf2( $mf2 ) );
		}
	}

	public function get_post() {
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

	public function sanitize_text( $value ) {
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
			return ifset( $this->meta['author'], false );
		}
		// Attachments may have been uploaded by a user but may have metadata for original author
		if ( is_attachment( $this->uid ) && isset( $this->meta['author'] ) ) {
			return $this->meta['author'];
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
		if ( ! $meta ) {
			return array();
		}
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
					$meta[ $key ] = $value;
				}
			}
		}
		return array_filter( $meta );
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
			if ( isset( $properties['type'] ) ) {
				$type = $properties['type'];
				unset( $properties['type'] );
			} else {
				$type = array( 'h-entry' );
			}
			$return = array(
				'type'       => $type,
				'properties' => $properties,
			);
			if ( $single ) {
				$return = mf2_to_jf2( $return );
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
				case 'uid':
					break;
				case 'post_author':
					if ( is_numeric( $value ) ) {
						wp_update_post(
							array(
								'ID'          => $this->uid,
								'post_author' => $value,
							)
						);
					}
					break;
				case 'author':
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

	public function jf2_to_mf2( $item, $type = 'cite' ) {
		if ( is_array( $item ) && isset( $item['type'] ) && ! isset( $item['properties'] ) ) {
			return jf2_to_mf2( $item );
		}
		$item['type'] = ifset( $item['type'], $type );
		return jf2_to_mf2( $item );
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
		$posts = get_attached_media( $type, $post );
		return wp_list_pluck( $posts, 'ID' );
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
		$att_ids = array_unique( array_merge( $att_ids, $this->get_attachments_from_urls( $videos ) ) );
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
			$att_ids = self::get_img_ids_from_content( $post_content );
			if ( $att_ids ) {
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
			return array( $featured );
		}
		$att_ids = $this->get_attached_media( 'image', $this->uid );
		$photos  = $this->get( 'photo', false );
		if ( is_array( $photos ) ) {
			if ( ! wp_is_numeric_array( $photos ) ) {
				$photos = array( $photos );
			}

			$photos = $this->sideload_images( $photos );
			$this->set( 'photo', $photos );
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
			if ( is_string( $value ) ) {
				if ( ! wp_http_validate_url( $value ) ) {
					continue;
				} else {
					if ( ! attachment_url_to_postid( $value ) ) {
						$id = media_sideload_image( $value, $this->uid, null, 'id' );
						if ( $id ) {
							$photos[ $key ] = wp_get_attachment_url( $id );
						}
					}
				}
			}
			// Attempt to normalize old data
			if ( is_array( $value ) ) {
				$value = mf2_to_jf2( $value );
				$id    = attachment_url_to_postid( $value['url'] );
				if ( ! $id ) {
					$id = media_sideload_image( $value['url'], $this->uid, null, 'id' );
					if ( $id ) {
						$value['url'] = wp_get_attachment_url( $id );
					}
				}
				$args = array(
					'ID'           => $id,
					'post_title'   => ifset( $value['name'] ),
					'post_excerpt' => ifset( $value['summary'] ),
				);
				$args = array_filter( $args );
				wp_update_post( $args );
				unset( $value['name'] );
				unset( $value['summary'] );
				foreach ( $value as $k => $v ) {
					update_post_meta( $id, 'mf2_' . $k, $v );
				}
				$photos[ $key ] = $value['url'];
			}
		}
		return $photos;
	}

	public function get_img_ids_from_content( $content ) {
		$content = wp_unslash( $content );
		$return  = array();
		$doc     = new DOMDocument();
		$doc->loadHTML( $content );
		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$classes = $image->getAttribute( 'class' );
			$classes = explode( ' ', $classes );
			foreach ( $classes as $class ) {
				if ( 0 === strpos( $class, 'wp-image-' ) ) {
					$return[] = (int) str_replace( 'wp-image-', '', $class );
				}
			}
		}
		return $return;
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
				if ( is_array( $url ) ) {
					if ( isset( $url['url'] ) ) {
						$att_ids[] = attachment_url_to_postid( $url['url'] );
					}
				} else {
					$att_ids[] = attachment_url_to_postid( $url );
				}
			}
		}
		return array_filter( array_unique( $att_ids ) );
	}
}

function get_mf2_post( $post_id ) {
	if ( $post_id instanceof MF2_Post ) {
		return $post_id;
	}
	return new MF2_Post( $post_id );
}

