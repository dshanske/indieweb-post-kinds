<?php
/**
 * MF2_Post class.
 *
 * @package Post_Kinds
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MF2 Post Class
 *
 * Assists in retrieving/saving microformats 2 properties from a post.
 *
 * @since 3.1.0
 * @deprecated 4.0.0 Use Kind_Post. MF2_Post will be removed in a future major version.
 */
class MF2_Post implements ArrayAccess {
	/**
	 * Post ID.
	 *
	 * @since 3.1.0
	 * @var int
	 */
	public $uid;

	/**
	 * ID of the post author.
	 *
	 * @since 3.1.0
	 * @var int|string
	 */
	public $post_author;

	/**
	 * Post type.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	public $post_type;

	/**
	 * Author, as an h-card.
	 *
	 * @since 3.1.0
	 * @var array|string|false
	 */
	public $author;

	/**
	 * Publication the post appeared in.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $publication;

	/**
	 * Published date, in DATE_W3C format.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $published;

	/**
	 * Updated date, in DATE_W3C format.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $updated;

	/**
	 * Content, with 'html' and 'value' versions.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	public $content;

	/**
	 * Summary (excerpt).
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $summary;

	/**
	 * ID of the parent post.
	 *
	 * @since 3.1.0
	 * @var int
	 */
	public $post_parent;

	/**
	 * Post kind slug, or media type for an attachment.
	 *
	 * @since 3.1.0
	 * @var string|false|null
	 */
	public $kind;

	/**
	 * Permalink, or file URL for an attachment.
	 *
	 * @since 3.1.0
	 * @var string|false
	 */
	public $url;

	/**
	 * Post title.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $name;

	/**
	 * Category and tag names.
	 *
	 * @since 3.1.0
	 * @var string[]
	 */
	public $category = array();

	/**
	 * URL of the featured image.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $featured;

	/**
	 * Properties stored in mf2_ prefixed post meta, without the prefix.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	private $mf2 = array();

	/**
	 * Reads a post's properties.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0 Use Kind_Post.
	 *
	 * @param int|WP_Post|string $post Post ID, post object, or the URL of a post or attachment.
	 */
	public function __construct( $post ) {
		_deprecated_function( __METHOD__, '4.0.0', 'Kind_Post' );
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
			return;
		}
		$this->mf2         = $this->get_mf2meta();
		$this->post_author = $post->post_author;
		$this->post_type   = $post->post_type;
		$this->author      = $this->get_author();
		$this->post_parent = $post->post_parent;
		$this->published   = $this->get_published();
		$this->updated     = $this->get_updated();
		$this->publication = $this->get_publication();
		if ( ! empty( $post->post_content ) ) {
			$this->content = array(
				'html'  => $post->post_content,
				'value' => wp_strip_all_tags( $post->post_content ),
			);
		}
		$this->summary = $post->post_excerpt;
		if ( 'attachment' === $post->post_type ) {
			$this->url = wp_get_attachment_url( $post->ID );
		} else {
			$this->url = get_permalink( $post->ID );
		}
		$this->name     = $post->post_title;
		$this->category = $this->get_categories( $post->ID );
		if ( $this->uid === (int) $this->name ) {
			unset( $this->name );
		}
		if ( has_post_thumbnail( $post ) ) {
			$this->featured = wp_get_attachment_url( get_post_thumbnail_id( $post ) );
		}
		$this->kind = self::get_post_kind();
	}

	/**
	 * Returns the published date.
	 *
	 * For an attachment, this is the date stored in post meta.
	 *
	 * @since 3.3.0
	 *
	 * @return string|false The date, or false if there is none.
	 */
	public function get_published() {
		if ( 'attachment' === $this->post_type ) {
			return $this->get_single( $this->mf2['published'] ?? '' );
		}
		return get_the_date( DATE_W3C, $this->uid );
	}

	/**
	 * Returns the updated date.
	 *
	 * For an attachment, this is the date stored in post meta.
	 *
	 * @since 3.3.0
	 *
	 * @return string|false The date, or false if there is none.
	 */
	public function get_updated() {
		if ( 'attachment' === $this->post_type ) {
			return $this->get_single( $this->mf2['updated'] ?? '' );
		}
		return get_the_modified_date( DATE_W3C, $this->uid );
	}

	/**
	 * Returns the publication the post appeared in.
	 *
	 * For posts other than attachments, this is the site title.
	 *
	 * @since 3.3.0
	 *
	 * @return string The publication, or an empty string if an attachment has none.
	 */
	public function get_publication() {
		if ( 'attachment' !== $this->post_type ) {
			return get_bloginfo( 'title' );
		}
		return $this->get_single( $this->mf2['publication'] ?? '' );
	}

	/**
	 * Whether a property exists, for ArrayAccess.
	 *
	 * @since 3.2.0
	 *
	 * @param string $offset Property name.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		$vars = get_object_vars( $this );
		if ( array_key_exists( $offset, $vars ) ) {
			return true;
		}
		return array_key_exists( $offset, $this->mf2 );
	}

	/**
	 * Returns a property, for ArrayAccess.
	 *
	 * @since 3.2.0
	 *
	 * @param string $offset Property name.
	 * @return mixed The value, or null if it does not exist.
	 */
	#[\ReturnTypeWillChange]
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

	/**
	 * Sets a property, for ArrayAccess.
	 *
	 * @since 3.2.0
	 *
	 * @param string $offset Property name.
	 * @param mixed  $value  The value.
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->set( $offset, $value );
	}

	/**
	 * Deletes a property stored in post meta, for ArrayAccess.
	 *
	 * @since 3.2.0
	 *
	 * @param string $offset Property name.
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->delete( $offset );
	}

	/**
	 * Returns the names of a post's categories and tags.
	 *
	 * 'Uncategorized' is left out.
	 *
	 * @since 3.2.0
	 *
	 * @param int $post_id Post ID.
	 * @return string[] Category and tag names.
	 */
	public function get_categories( $post_id ) {
		$category = array();
		// Get a list of categories and extract their names.
		$post_categories = get_the_terms( $post_id, 'category' );
		if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) {
			$category = wp_list_pluck( $post_categories, 'name' );
		}

		// Get a list of tags and extract their names.
		$post_tags = get_the_terms( $post_id, 'post_tag' );
		if ( ! empty( $post_tags ) && ! is_wp_error( $post_tags ) ) {
			$category = array_merge( $this->category, wp_list_pluck( $post_tags, 'name' ) );
		}
		if ( in_array( 'Uncategorized', $category, true ) ) {
			unset( $category[ array_search( 'Uncategorized', $category, true ) ] );
		}
		return $category;
	}

	/**
	 * Returns the post kind, or the media type of an attachment.
	 *
	 * @since 3.1.0
	 *
	 * @return string|false|null The kind slug, or null for an attachment that is not image, video or audio.
	 */
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

	/**
	 * Returns the post object.
	 *
	 * @since 3.1.0
	 *
	 * @return WP_Post|null The post, or null if it does not exist.
	 */
	public function get_post() {
		return get_post( $this->uid );
	}

	/**
	 * Is prefix in string.
	 *
	 * @since 3.1.0
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
	 * @since 3.1.0
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

	/**
	 * Sanitizes HTML with the allowed tags from the plugin settings.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $value The value. Anything but a string is returned unchanged.
	 * @return mixed The sanitized value.
	 */
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

	/**
	 * Sanitizes a URL or text, recursively.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $value The value.
	 * @return string|array The sanitized value.
	 */
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
	 * @since 3.1.0
	 *
	 * @return boolean|array The result or false if does not exist.
	 */
	public function get_author() {
		if ( ! $this->post_author ) {
			return $this->get_single( $this->mf2['author'] ?? false );
		}
		// Attachments may have been uploaded by a user but may have metadata for original author.
		if ( 'attachment' === $this->post_type ) {
			return $this->get_single( $this->mf2['author'] ?? '' );
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
	 * Returns the properties stored in mf2_ prefixed post meta, without the prefix.
	 *
	 * @since 3.1.0
	 */
	private function get_mf2meta() {
		$meta = get_post_meta( $this->uid );
		if ( ! $meta ) {
			return array();
		}
		// Very old versions stored the citation in the response meta. Read it as
		// mf2_cite when there is no newer citation; the upgrade routine moves it.
		if ( isset( $meta['response'] ) && ! isset( $meta['mf2_cite'] ) ) {
			$response = maybe_unserialize( $meta['response'][0] );
			if ( is_array( $response ) ) {
				$new = array();
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
					$new['card'] = array( 'name' => $response['author'] );
					if ( ! empty( $response['icon'] ) ) {
						$new['card']['photo'] = $response['icon'];
					}
				}
				if ( $new ) {
					$meta['mf2_cite'] = array( $new );
				}
			}
		}
		foreach ( $meta as $key => $value ) {
			if ( ! self::str_prefix( $key, 'mf2_' ) ) {
				unset( $meta[ $key ] );
			} else {
				unset( $meta[ $key ] );
				$key = str_replace( 'mf2_', '', $key );
				// Do not save microput prefixed instructions.
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
	 * Map Properties Based on Post Type
	 *
	 * @since 3.3.0
	 */
	private function get_post_type_properties() {
		$properties = array_keys( get_object_vars( $this ) );
		unset( $properties['mf2'] );
		if ( 'attachment' === $this->post_type ) {
			$properties = array_diff( $properties, array( 'published', 'updated', 'author' ) );
		}
		return $properties;
	}

	/**
	 * Retrieve value
	 *
	 * @since 3.1.0
	 *
	 * @param string|null $key    Optional. The key to retrieve. Default null, for every property.
	 * @param bool        $single Optional. Whether to unwrap a single value from its array. Default true.
	 * @return boolean|string|array The result or false if does not exist.
	 */
	public function get( $key = null, $single = true ) {
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
		$properties = $this->get_post_type_properties();
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

	/**
	 * Whether the post has a property.
	 *
	 * @since 3.1.0
	 *
	 * @param string $key Property name.
	 * @return bool
	 */
	public function has_key( $key ) {
		$keys = array_merge( get_object_vars( $this ), $this->mf2 );
		return isset( $keys[ $key ] );
	}

	/**
	 * Unwraps single-item arrays, recursively.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $value   The value to unwrap.
	 * @param bool  $discard Optional. Whether to keep only the first item of a list
	 *                       with more than one item. Default false.
	 * @return mixed The value, with single-item arrays replaced by their item.
	 */
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

	/**
	 * Stores a property, or several.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $key   Property name, an array of property => value pairs, or an mf2 item.
	 * @param mixed        $value Optional. The value. Default null.
	 * @return mixed The result of storing the value, or false or null if nothing was stored.
	 */
	public function set( $key, $value = null ) {
		if ( ! $key ) {
			return;
		}
		if ( Parse_This_MF2::is_microformat( $key ) ) {
			$key = $key['properties'];
		}
		if ( is_array( $key ) ) {
			foreach ( $key as $k => $v ) {
				self::set( $k, $v );
			}
		}
		if ( null === $value || empty( $value ) ) {
			return false;
		}
		if ( in_array( $key, array( 'photo', 'video', 'audio' ), true ) ) {
			if ( 'attachment' === $this->post_type ) {
				return;
			}
			if ( Parse_This_MF2::is_microformat( $value ) ) {
				$u          = Parse_This_MF2::get_plaintext( $value, 'url', null );
				$attachment = new MF2_Post( attachment_url_to_postid( $u ) );
				$attachment->set( $value );
				return update_post_meta( $this->uid, 'mf2_' . $key, array( $u ) );
			}
			if ( wp_is_numeric_array( $value ) ) {
				if ( is_string( $value[0] ) ) {
					return update_post_meta( $this->uid, 'mf2_' . $key, $value );
				} else {
					$mprop = array();
					foreach ( $value as $media ) {
						if ( Parse_This_MF2::is_microformat( $media ) ) {
							$u          = Parse_This_MF2::get_plaintext( $media, 'url', null );
							$attachment = new MF2_Post( attachment_url_to_postid( $u ) );
							$attachment->set( $media );
							$mprop[] = $u;
						}
					}
					if ( ! empty( $mprop ) ) {
						return update_post_meta( $this->uid, 'mf2_' . $key, $mprop );
					}
				}
			}
		}
		$properties = $this->get_post_type_properties();
		unset( $properties['mf2'] );
		if ( ! in_array( $key, $properties, true ) ) {
			return update_post_meta( $this->uid, 'mf2_' . $key, $value );
		} else {
			$value = $this->get_single( $value );
			switch ( $key ) {
				case 'url':
				case 'uid':
				case 'kind':
				case 'post_type':
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
					return wp_update_post(
						array(
							'ID'            => $this->uid,
							'post_date'     => $post_date,
							'post_date_gmt' => $post_date_gmt,
						)
					);
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
					return wp_update_post(
						array(
							'ID'                => $this->uid,
							'post_modified'     => $post_modified,
							'post_modified_gmt' => $post_modified_gmt,
						)
					);
				case 'name':
					return wp_update_post(
						array(
							'ID'         => $this->uid,
							'post_title' => $value,
						)
					);
				case 'content':
					return wp_update_post(
						array(
							'ID'           => $this->uid,
							'post_content' => $value,
						)
					);
				case 'summary':
					return wp_update_post(
						array(
							'ID'           => $this->uid,
							'post_excerpt' => $value,
						)
					);
				case 'publication':
					if ( get_bloginfo( 'title' ) === $value ) {
						return false;
					}
					return update_post_meta( $this->uid, 'mf2_publication', array( $value ) );
				case 'featured':
					$attachment_id = attachment_url_to_postid( $value );
					if ( $attachment_id ) {
						return set_post_thumbnail( $this->uid, $attachment_id );
					}
					return false;
				default:
					return wp_update_post(
						array(
							'ID' => $this->uid,
							$key => $value,
						)
					);
			}
		}
	}

	/**
	 * Deletes a property stored in post meta.
	 *
	 * @since 3.1.0
	 *
	 * @param string $key Property name.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key ) {
		return delete_post_meta( $this->uid, 'mf2_' . $key );
	}

	/**
	 * Converts mf2 to jf2.
	 *
	 * @since 3.1.0
	 *
	 * @param array $cite The mf2 item.
	 * @return array The jf2 item.
	 */
	public function mf2_to_jf2( $cite ) {
		return mf2_to_jf2( $cite );
	}

	/**
	 * Returns the first item of an array.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $value The value.
	 * @return mixed The first item, or the value if it is not an array.
	 */
	public function get_single( $value ) {
		if ( is_array( $value ) ) {
			return array_shift( $value );
		}
		return $value;
	}

	/**
	 * Converts jf2 to mf2.
	 *
	 * @since 3.1.0
	 *
	 * @param array  $item The jf2 item.
	 * @param string $type Optional. jf2 type to use if the item has none. Default 'cite'.
	 * @return array The mf2 item.
	 */
	public function jf2_to_mf2( $item, $type = 'cite' ) {
		if ( is_array( $item ) && isset( $item['type'] ) && ! isset( $item['properties'] ) ) {
			return jf2_to_mf2( $item );
		}
		$item['type'] = $item['type'] ?? $type;
		return jf2_to_mf2( $item );
	}

	/**
	 * Returns a property for the link preview, as jf2.
	 *
	 * Updates a property stored in the format used before 2.7.0.
	 *
	 * @since 3.1.0
	 *
	 * @param string $property Property name.
	 * @return array|false The property, or false if there is none.
	 */
	public function fetch( $property ) {

		// If the property is not set then exit.
		if ( ! $property || ! $this->has_key( $property ) ) {
			return false;
		}
		$return = $this->get( $property, false );
		if ( wp_is_numeric_array( $return ) ) {
			$return = array_shift( $return );
		}
		// If it is in fact a string it is the pre 2.7.0 format and should be updated.
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

	/**
	 * Returns the IDs of media attached to a post.
	 *
	 * @since 3.1.0
	 *
	 * @param string      $type MIME type, such as 'image'.
	 * @param int|WP_Post $post Post ID or post object.
	 * @return int[] Attachment IDs.
	 */
	public function get_attached_media( $type, $post ) {
		$posts = get_attached_media( $type, $post );
		return wp_list_pluck( $posts, 'ID' );
	}

	/**
	 * Returns the post's audio.
	 *
	 * @since 3.1.0
	 *
	 * @return int[]|false Attachment IDs, or false if there are none.
	 */
	public function get_audios() {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'audio', $this->uid ) ) {
			return array( $this->uid );
		}
		$att_ids = $this->get_attached_media( 'audio', $this->uid );
		$audios  = $this->get( 'audio' );
		$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $audios ) );
		if ( ! empty( $att_ids ) ) {
			return array_unique( $att_ids );
		}
		return false;
	}

	/**
	 * Returns the post's videos.
	 *
	 * @since 3.1.0
	 *
	 * @return int[]|false Attachment IDs, or false if there are none.
	 */
	public function get_videos() {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'video', $this->uid ) ) {
			return array( $this->uid );
		}
		$att_ids = $this->get_attached_media( 'video', $this->uid );
		$videos  = $this->get( 'video' );
		$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $videos ) );
		if ( ! empty( $att_ids ) ) {
			return array_unique( $att_ids );
		}
		return false;
	}

	/**
	 * Returns the post's images.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $content_allow Optional. Whether to return images in the content. Default false.
	 * @return int[]|false Attachment IDs, or false if there are none.
	 */
	public function get_images( $content_allow = false ) {
		// Check if the post itself is an image attachment.
		if ( wp_attachment_is( 'image', $this->uid ) ) {
			return array( $this->uid );
		}
		$post_content = $this->content['html'] ?? '';
		if ( $post_content ) {
			// Recorded when the post is saved; see Kind_Media_Metadata::save_post().
			$att_ids = get_post_meta( $this->uid, '_content_img_ids', true );
			if ( $att_ids ) {
				return $content_allow ? $att_ids : array();
			}
			if ( ! empty( $att_ids ) ) {
				return $content_allow ? array_unique( $att_ids ) : array();
			}
		}
		// If there is a featured image return only that. Otherwise return all images.
		$featured = get_post_thumbnail_id( $this->uid );
		if ( $featured ) {
			return array( $featured );
		}
		$att_ids = $this->get_attached_media( 'image', $this->uid );
		$photos  = $this->get( 'photo', false );
		if ( is_array( $photos ) && ! wp_is_numeric_array( $photos ) ) {
			$photos = array( $photos );
		}
		$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $photos ) );
		if ( ! empty( $att_ids ) ) {
			return array_filter( $att_ids );
		}
		return false;
	}

	/**
	 * Looks up the attachment IDs for media URLs.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $urls A URL, or a list of URLs or arrays with a 'url' key.
	 * @return int[] Attachment IDs found.
	 */
	public function get_attachments_from_urls( $urls ) {
		if ( is_string( $urls ) ) {
			$attachment = attachment_url_to_postid( $urls );
			if ( $attachment ) {
				return array( $attachment );
			} else {
				return array();
			}
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
