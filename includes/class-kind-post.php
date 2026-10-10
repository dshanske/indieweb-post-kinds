<?php
/**
 * Kind_Post class.
 *
 * @package Post_Kinds
 * @since   3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the kind properties of a post.
 *
 * Replaces MF2_Post by looking up each property on demand instead of parsing
 * all of them up front.
 *
 * @since 3.4.0
 */
class Kind_Post {

	/**
	 * Post ID.
	 *
	 * @since 3.4.0
	 * @var int
	 */
	public $id;

	/**
	 * Sets up the post to read and write.
	 *
	 * @since 3.4.0
	 *
	 * @param int|WP_Post|string $post Post ID, post object, or the URL of a post or attachment.
	 */
	public function __construct( $post ) {
		if ( is_numeric( $post ) ) {
			$this->id = (int) $post;
		} elseif ( $post instanceof WP_Post ) {
			$this->id = $post->ID;
		} elseif ( wp_http_validate_url( $post ) ) {
			$id = url_to_postid( $post );
			if ( $id ) {
				$this->id = $id;
				$post     = $id;
			} else {
				$id       = self::get_attachment_id( $post );
				$this->id = $id;
				$post     = $id;
			}
		}

		$post = get_post( $post );
	}

	/**
	 * Checks whether a post is an attachment.
	 *
	 * @since 3.4.10
	 *
	 * @param int|WP_Post $post Post ID or post object.
	 * @return bool True if the post is an attachment.
	 */
	private function is_attachment( $post ) {
		return ( 'attachment' === get_post_type( $post ) );
	}


	/**
	 * Returns the post object.
	 *
	 * @since 3.4.0
	 *
	 * @return WP_Post|null The post, or null if it does not exist.
	 */
	public function get_post() {
		return get_post( $this->id );
	}

	/**
	 * Returns the post kind. For an attachment, returns its media type instead.
	 *
	 * @since 3.4.0
	 *
	 * @return string|false|null The kind slug ('photo', 'video' or 'audio' for an attachment),
	 *                           false if a post has no kind, or null for an attachment of another type.
	 */
	public function get_kind() {
		if ( $this->is_attachment( $this->id ) ) {
			if ( wp_attachment_is( 'image', $this->id ) ) {
				return 'photo';
			}
			if ( wp_attachment_is( 'video', $this->id ) ) {
				return 'video';
			}
			if ( wp_attachment_is( 'audio', $this->id ) ) {
				return 'audio';
			}
			return null;
		}
		return get_post_kind_slug( $this->id );
	}

	/**
	 * Returns the post's name (title).
	 *
	 * A title that is just the post ID, or an attachment title that is just its
	 * file name, does not count as a name.
	 *
	 * @since 3.4.0
	 *
	 * @return string|false The name, or false if there is none.
	 */
	public function get_name() {
		$post   = get_post( $this->id );
		$return = false;
		if ( ! empty( $post->post_title ) && ( $this->id !== (int) $post->post_title ) ) {
			$return = $post->post_title;
		}
		if ( $this->is_attachment( $this->id ) ) {
			$image_meta = wp_get_attachment_metadata( $this->id );
			if ( ! empty( $image_meta['original_image'] ) ) {
				$file = $image_meta['original_image'];
			} else {
				$file = get_post_meta( $this->id, '_wp_attached_file', true );
				$file = explode( '/', $file );
				$file = array_pop( $file );
			}
			$file = explode( '.', $file );
			$file = $file[0];
			if ( $return === $file ) {
				return false;
			}
		}
		return $return;
	}

	/**
	 * Returns the post's content or summary (excerpt).
	 *
	 * @since 3.4.0
	 *
	 * @param string $property Either 'content' or 'summary'.
	 * @return array|false Array with the HTML ('html') and plain text ('value') versions,
	 *                     or false if the property is empty or not supported.
	 */
	public function get_html( $property ) {
		if ( ! in_array( $property, array( 'summary', 'content' ), true ) ) {
			return false;
		}

		$post = get_post( $this->id );

		$content = ( 'content' === $property ) ? $post->post_content : $post->post_excerpt;
		if ( ! empty( $content ) ) {
			return array(
				'html'  => $content,
				'value' => wp_strip_all_tags( $content ),
			);
		}
		return false;
	}

	/**
	 * Returns the post's permalink, or the file URL for an attachment.
	 *
	 * @since 3.4.0
	 *
	 * @return string|false The URL, or false if there is none.
	 */
	public function get_url() {
		if ( 'attachment' === get_post_type( $this->id ) ) {
			return wp_get_attachment_url( $this->id );
		} else {
			return get_permalink( $this->id );
		}
	}

	/**
	 * Returns the URL of the post's featured image.
	 *
	 * @since 3.4.0
	 *
	 * @return string|false The URL, or false if the post has no featured image.
	 */
	public function get_featured() {
		if ( has_post_thumbnail( $this->id ) ) {
			return wp_get_attachment_url( get_post_thumbnail_id( $this->id ) );
		}
		return false;
	}


	/**
	 * Returns a date property as a date object.
	 *
	 * For posts other than attachments, 'published' and 'updated' come from the
	 * post's own dates. Everything else comes from post meta.
	 *
	 * @since 3.4.0
	 *
	 * @param string $property Property to return, such as 'published' or 'start'.
	 * @return DateTimeImmutable|false The date, or false if it is missing or not a valid date.
	 */
	public function get_datetime_property( $property ) {
		// In an attachment the post date properties reflect when the item was uploaded not when the piece was created.
		if ( 'attachment' !== get_post_type( $this->id ) && in_array( $property, array( 'published', 'updated' ), true ) ) {
			if ( 'published' === $property ) {
				return get_post_datetime( $this->id );
			} else {
				return get_post_datetime( $this->id, 'modified' );
			}
		}

		$datetime = get_post_meta( $this->id, 'mf2_' . $property, true );
		if ( ! $datetime ) {
			return false;
		}

		if ( is_array( $datetime ) ) {
			$datetime = $datetime[0];
		}
		return kind_safe_datetime( $datetime );
	}

	/**
	 * Returns the publication the post appeared in.
	 *
	 * For posts other than attachments, this is the site title.
	 *
	 * @since 3.4.0
	 *
	 * @return string|null The publication, or null if an attachment has none.
	 */
	public function get_publication() {
		if ( 'attachment' !== get_post_type( $this->id ) ) {
			return get_bloginfo( 'title' );
		}

		$publication = get_post_meta( $this->id, 'mf2_publication', true );
		if ( $publication ) {
			return $publication;
		}
	}

	/**
	 * Returns the post's stored duration.
	 *
	 * @since 3.4.0
	 *
	 * @return DateInterval|false The duration, or false if it is missing or not valid.
	 */
	public function get_duration() {
		$duration = get_post_meta( $this->id, 'mf2_duration', true );
		if ( is_array( $duration ) ) {
			$duration = $duration[0];
		}
		return kind_safe_interval( $duration );
	}

	/**
	 * Returns the names of the post's categories and tags, combined.
	 *
	 * 'Uncategorized' is left out.
	 *
	 * @since 3.4.0
	 *
	 * @return string[] Category and tag names.
	 */
	public function get_categories() {
		$category = array();
		// Get a list of categories and extract their names.
		$post_categories = get_the_terms( $this->id, 'category' );
		if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) {
			$category = wp_list_pluck( $post_categories, 'name' );
		}

		// Get a list of tags and extract their names.
		$post_tags = get_the_terms( $this->id, 'post_tag' );
		if ( ! empty( $post_tags ) && ! is_wp_error( $post_tags ) ) {
			$category = array_merge( $category, wp_list_pluck( $post_tags, 'name' ) );
		}
		if ( in_array( 'Uncategorized', $category, true ) ) {
			unset( $category[ array_search( 'Uncategorized', $category, true ) ] );
		}
		return $category;
	}

	/**
	 * Checks whether a string starts with a prefix.
	 *
	 * @since 3.4.0
	 *
	 * @param string $source The source string.
	 * @param string $prefix The prefix to look for.
	 * @return bool True if the source starts with the prefix.
	 */
	protected static function str_prefix( $source, $prefix ) {
		return strncmp( $source, $prefix, strlen( $prefix ) ) === 0;
	}

	/**
	 * Checks whether an array is multidimensional.
	 *
	 * @since 3.4.0
	 *
	 * @param array $arr The array to check.
	 * @return bool True if any element is an array.
	 */
	protected static function is_multi_array( $arr ) {
		if ( count( $arr ) === count( $arr, COUNT_RECURSIVE ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Unwraps single-item arrays, recursively.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $value   The value to unwrap.
	 * @param bool  $discard Optional. Whether to keep only the first item of a list
	 *                       with more than one item. Default false.
	 * @return mixed The value, with single-item arrays replaced by their item.
	 */
	protected function single_array( $value, $discard = false ) {
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
	 * Returns the post's author as an h-card.
	 *
	 * For an attachment, or a post with no WordPress author, this is the author
	 * stored in post meta.
	 *
	 * @since 3.4.0
	 *
	 * @return array|string|false The author as mf2, the stored value, or false if there is none.
	 */
	public function get_author() {
		$post = $this->get_post();
		if ( ! $post->post_author ) {
			$author = get_post_meta( $post->ID, 'mf2_author', true );
			return ( $author ?? false );
		}
		// Attachments may have been uploaded by a user but may have metadata for original author.
		if ( 'attachment' === get_post_type( $this->id ) ) {
			$author = get_post_meta( $post->ID, 'mf2_author', true );
			return ( $author ?? false );
		}
		return array(
			'type'       => array( 'h-card' ),
			'properties' => array(
				'name'  => array( get_the_author_meta( 'display_name', $post->post_author ) ),
				'url'   => array( get_the_author_meta( 'user_url', $post->post_author ) ? get_the_author_meta( 'user_url', $post->post_author ) : get_author_posts_url( $post->post_author ) ),
				'photo' => array( get_avatar_url( $post->post_author ) ),
			),
		);
	}

	/**
	 * Returns the IDs of media attached to the post.
	 *
	 * @since 3.4.0
	 *
	 * @param string $type 'audio', 'video' or 'photo'.
	 * @return int[]|false Attachment IDs, or false if the type is not supported.
	 */
	public function get_attached_media( $type ) {
		$type = strtolower( $type );
		if ( ! in_array( $type, array( 'photo', 'video', 'audio' ), true ) ) {
			return false;
		}
		if ( 'photo' === $type ) {
			$type = 'image';
		}
		$posts = get_attached_media( $type, $this->id );
		return wp_list_pluck( $posts, 'ID' );
	}

	/**
	 * Returns the post's photos.
	 *
	 * Looks in attached media, the photo property and images in the content.
	 * Returns an empty array if the post has a featured image.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $content Optional. Whether to return an empty array if the content
	 *                      has any images. Default true.
	 * @return int[]|false Attachment IDs, or false if there are none.
	 */
	public function get_photo( $content = true ) {
		if ( ! $this->get_post() ) {
			return false;
		}
		// Check if the post itself is an image attachment.
		if ( wp_attachment_is( 'image', $this->id ) ) {
			return array( $this->id );
		}

		// Recorded when the post is saved; see Kind_Media_Metadata::save_post().
		$content_ids = get_post_meta( $this->id, '_content_img_ids', true );

		// If there are photos in the content then end here if this is true.
		if ( ! empty( $content_ids ) ) {
			if ( $content ) {
				return array();
			} else {
				return $content_ids;
			}
		}

		// If there is a featured image return nothing on the assumption that photo and featured should not appear on the same post.
		$featured = $this->get_featured();
		if ( $featured ) {
			return array();
		}

		$att_ids = $this->get_attached_media( 'photo', $this->id );
		if ( ! $att_ids ) {
			$att_ids = array();
		}

		$photos = get_post_meta( $this->id, 'mf2_photo', true );

		if ( ! is_array( $content_ids ) ) {
			$content_ids = array();
		}

		$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $photos ), $content_ids );
		if ( ! empty( $att_ids ) ) {
			return array_unique( $att_ids );
		}
		return false;
	}

	/**
	 * Returns the post's audio.
	 *
	 * Looks in attached media, the audio property and audio in the content.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $content Optional. Whether to return an empty array if the content
	 *                      has any audio. Default true.
	 * @return array|false Attachment IDs, external URLs if no attachments were found,
	 *                     or false if there is no audio.
	 */
	public function get_audio( $content = true ) {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'audio', $this->id ) ) {
			return array( $this->id );
		}

		// Recorded when the post is saved; see Kind_Media_Metadata::save_post().
		$content_ids = get_post_meta( $this->id, '_content_audio_ids', true );

		// If there are ids in the content then end here if this is true.
		if ( ! empty( $content_ids ) && $content ) {
			return array();
		}

		if ( ! is_array( $content_ids ) ) {
			$content_ids = array();
		}

		$att_ids   = $this->get_attached_media( 'audio', $this->id );
		$audios    = get_post_meta( $this->id, 'mf2_audio', true );
		$audio_ids = is_array( $audios ) ? $this->get_attachments_from_urls( $audios ) : array();

		// If there are ids found return them.
		if ( ! empty( $audio_ids ) || ! empty( $att_ids ) || ! empty( $content_ids ) ) {
			return array_unique( array_merge( $att_ids, $audio_ids, $content_ids ) );
		}

		// This means there are external URLs for audio provided.
		if ( ! empty( $audios ) ) {
			return $audios;
		}

		return false;
	}

	/**
	 * Returns the post's videos.
	 *
	 * Looks in attached media, the video property and videos in the content.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $content Optional. Whether to return an empty array if the content
	 *                      has any videos. Default true.
	 * @return int[]|false Attachment IDs, or false if there are none.
	 */
	public function get_video( $content = true ) {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'video', $this->id ) ) {
			return array( $this->id );
		}
		// Recorded when the post is saved; see Kind_Media_Metadata::save_post().
		$content_ids = get_post_meta( $this->id, '_content_video_ids', true );

		// If there are ids in the content then end here if this is true.
		if ( ! empty( $content_ids ) && $content ) {
			return array();
		}

		if ( ! is_array( $content_ids ) ) {
			$content_ids = array();
		}

		$att_ids = $this->get_attached_media( 'video', $this->id );
		$videos  = get_post_meta( $this->id, 'mf2_video', true );
		if ( is_array( $videos ) ) {
			$att_ids = array_merge( $att_ids, $this->get_attachments_from_urls( $videos ), $content_ids );
		}
		if ( ! empty( $att_ids ) ) {
			return array_unique( $att_ids );
		}
		return false;
	}

	/**
	 * Look up the attachment ID for a URL, with caching.
	 *
	 * Wraps attachment_url_to_postid(), which runs an uncached query, in the
	 * object cache. The key includes the posts last_changed value, so adding,
	 * changing or deleting any post or attachment invalidates it. Without a
	 * persistent object cache this still avoids repeat lookups in a request.
	 *
	 * @since 4.0.0
	 *
	 * @param string $url Media URL.
	 * @return int Attachment ID, or 0 if there is none.
	 */
	public static function get_attachment_id( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return 0;
		}
		$key = 'attachment_id:' . md5( $url ) . ':' . wp_cache_get_last_changed( 'posts' );
		$id  = wp_cache_get( $key, 'post-kinds' );
		if ( false === $id ) {
			$id = attachment_url_to_postid( $url );
			wp_cache_set( $key, $id, 'post-kinds' );
		}
		return (int) $id;
	}

	/**
	 * Looks up the attachment IDs for media URLs.
	 *
	 * @since 3.4.0
	 *
	 * @param string|array $urls A URL, or a list of URLs, attachment IDs or arrays with a 'url' key.
	 * @return int[] Attachment IDs found. URLs with no attachment are left out.
	 */
	public function get_attachments_from_urls( $urls ) {
		if ( is_string( $urls ) ) {
			$attachment = self::get_attachment_id( $urls );
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
						$att_ids[] = self::get_attachment_id( $url['url'] );
					}
				} elseif ( is_numeric( $url ) ) {
					$att_ids[] = $url;
				} else {
					$att_ids[] = self::get_attachment_id( $url );
				}
			}
		}
		return array_filter( array_unique( $att_ids ) );
	}

	/**
	 * Returns a property of the post.
	 *
	 * @since 3.4.0
	 *
	 * @param string $key    The property to return.
	 * @param bool   $single Optional. Whether to unwrap a single value from its array. Default true.
	 * @return mixed The value, or false if the key is empty.
	 */
	public function get( $key, $single = true ) {
		if ( empty( $key ) ) {
			return false;
		}
		switch ( $key ) {
			case 'published':
			case 'updated':
			case 'start':
			case 'end':
				return $this->get_datetime_property( $key );
			case 'author':
				return $this->get_author();
			case 'category':
				return $this->get_categories();
			case 'featured':
				return $this->get_featured();
			case 'name':
				return $this->get_name();
			case 'publication':
				return $this->get_publication();
			case 'url':
				return $this->get_url();
			case 'duration':
				return $this->get_duration();
			case 'summary':
			case 'content':
				return $this->get_html( $key );
			default:
				$return = get_post_meta( $this->id, 'mf2_' . $key, true );
				if ( is_array( $return ) ) {
					return $single ? $this->single_array( $return ) : $return;
				}
				if ( is_string( $return ) ) {
					return $single ? $return : array( $return );
				}
		}
	}

	/**
	 * Returns the post's citation, or one property of it.
	 *
	 * For an attachment, the citation is built from the attachment itself.
	 * Citations stored as jf2 are returned as mf2.
	 *
	 * @since 3.4.0
	 *
	 * @param string|null $key Optional. Citation property to return. Default null, for the whole citation.
	 * @return mixed The citation as mf2 or a string, the property, or false if there is none.
	 */
	public function get_cite( $key = null ) {
		if ( 'attachment' === get_post_type( $this->id ) ) {
			$published = $this->get_datetime_property( 'published' );
			if ( $published instanceof DateTimeImmutable ) {
				$published = $published->format( DATE_W3C );
			}
			$duration = $this->get_duration();
			if ( $duration instanceof DateInterval ) {
				$duration = date_interval_to_iso8601( $duration );
			}
			$cite = \ParseThis\jf2_to_mf2(
				array_filter(
					array(
						'type'        => 'cite',
						'name'        => $this->get_name(),
						'url'         => $this->get_url(),
						'summary'     => $this->get_html( 'summary' ),
						'published'   => $published,
						'uid'         => $this->id,
						'author'      => $this->get_author(),
						'publication' => $this->get_publication(),
						'duration'    => $duration,
					)
				)
			);
		} else {
			$property = Kind_Taxonomy::get_kind_info( $this->get_kind(), 'property' );
			if ( empty( $property ) ) {
				return false;
			}
			$cite = $this->get( $property, false );

			if ( is_array( $cite ) ) {
				$cite = array_filter( $cite );
			}

			// Look in old location.
			if ( empty( $cite ) ) {
				$cite = $this->get( 'cite', false );
				if ( ! $cite ) {
					return false;
				}
				if ( wp_is_numeric_array( $cite ) && 1 === count( $cite ) ) {
					$cite = $cite[0];
				}
			}

			// Citations stored as jf2 are returned as mf2.
			if ( is_array( $cite ) && ! wp_is_numeric_array( $cite ) ) {
				if ( ! isset( $cite['properties'] ) ) {
					if ( empty( $cite['type'] ) ) {
						$cite['type'] = $this->get_cite_type();
					}
					$cite = \ParseThis\jf2_to_mf2( $cite );
				} elseif ( empty( $cite['type'] ) || ! is_array( $cite['type'] ) ) {
					// Reads before 4.0.0 stored the type as 'cite'.
					$cite['type'] = array( 'h-' . $this->get_cite_type() );
				}
			}
		}

		if ( ! $key ) {
			return $cite;
		}

		if ( wp_is_numeric_array( $cite ) && 1 === count( $cite ) ) {
			$cite = $cite[0];
		}

		// If this is a Microformat, then try to return the property.
		if ( is_array( $cite ) && array_key_exists( 'type', $cite ) && array_key_exists( 'properties', $cite ) ) {
			if ( array_key_exists( $key, $cite['properties'] ) ) {
				return $this->single_array( $cite['properties'][ $key ] );
			} else {
				return false;
			}
		}
		if ( is_string( $cite ) ) {
			if ( 'url' === $key && wp_http_validate_url( $cite ) ) {
				return $cite;
			}
			if ( 'name' === $key ) {
				if ( ! wp_http_validate_url( $cite ) ) {
					return $cite;
				} else {
					$parse = wp_parse_url( $cite );
					return $parse['host'] . $parse['path'];
				}
			}
		}

		return false;
	}

	/**
	 * Returns the jf2 type of the post's citation, based on its kind.
	 *
	 * Matches the type Kind_Metabox::save_post() stores: a checkin cites a
	 * place (card), eat and drink cite food, and every other kind a citation.
	 *
	 * @since 4.0.0
	 *
	 * @return string The jf2 type: 'card', 'food' or 'cite'.
	 */
	public function get_cite_type() {
		$kind = $this->get_kind();
		if ( 'checkin' === $kind ) {
			return 'card';
		}
		if ( in_array( $kind, array( 'drink', 'eat' ), true ) ) {
			return 'food';
		}
		return 'cite';
	}

	/**
	 * Returns a citation as jf2 with every key present and formatted for display.
	 *
	 * Missing keys are set to empty strings, which saves isset checks. For
	 * display and the metabox only.
	 *
	 * 'authors' lists every author as a card with name, url and photo. 'author'
	 * is the single author, or for several authors one card whose fields hold
	 * each author's value in turn, separated by semicolons, as the metabox
	 * shows them.
	 *
	 * @since 3.4.9
	 * @since 4.0.0 Added 'authors'. 'author' joins several authors' photos too.
	 *
	 * @param array|string|false $cite The citation, as mf2, jf2 or a URL or name.
	 * @return array The normalized citation.
	 */
	public function normalize_cite( $cite ) {
		// Ensures that an empty string is always present in the cite.
		$author_defaults = array(
			'type'  => 'card',
			'url'   => '',
			'name'  => '',
			'photo' => '',
		);
		$defaults        = array(
			'type'        => 'cite',
			'url'         => '',
			'name'        => '',
			'featured'    => '',
			'publication' => '',
			'published'   => '',
			'updated'     => '',
			'summary'     => '',
			'author'      => $author_defaults,
			'authors'     => array(),
			'category'    => '',
		);

		if ( ! $cite ) {
			return $defaults;
		}

		if ( wp_is_numeric_array( $cite ) && 1 === count( $cite ) ) {
			$cite = $cite[0];
		}
		if ( \ParseThis\MF2_Utils::is_microformat( $cite ) ) {
			$cite = \ParseThis\mf2_to_jf2( $cite );
		}

		if ( is_string( $cite ) ) {
			$cite = wp_http_validate_url( $cite ) ? array( 'url' => $cite ) : array( 'name' => $cite );
		}

		$cite = wp_parse_args( $cite, $defaults );

		if ( is_array( $cite['summary'] ) && array_key_exists( 'html', $cite['summary'] ) ) {
			$cite['summary'] = $cite['summary']['html'];
		}

		if ( is_array( $cite['category'] ) ) {
			$cite['category'] = implode( ';', $cite['category'] );
		}

		$cite['authors'] = self::normalize_authors( $cite['author'] );
		if ( 1 === count( $cite['authors'] ) ) {
			$cite['author'] = $cite['authors'][0];
		} else {
			$cite['author'] = $author_defaults;
			foreach ( array( 'name', 'url', 'photo' ) as $key ) {
				$values = wp_list_pluck( $cite['authors'], $key );
				if ( array_filter( $values ) ) {
					$cite['author'][ $key ] = implode( '; ', $values );
				}
			}
		}

		if ( is_array( $cite['publication'] ) ) {
			$cite['publication'] = $cite['publication']['name'];
		}

		// FIXME: Discards extra URLs as currently unsupported. This would be for multi-replies in theory.
		if ( isset( $cite['url'] ) && is_array( $cite['url'] ) ) {
			$cite['url'] = array_shift( $cite['url'] );
		}

		if ( empty( $cite['publication'] ) && ! empty( $cite['url'] ) && wp_parse_url( $cite['url'], PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$cite['publication'] = preg_replace( '/^www\./', '', wp_parse_url( $cite['url'], PHP_URL_HOST ) );
		}

		return $cite;
	}


	/**
	 * Returns a citation's authors as a list of cards.
	 *
	 * Accepts one author or a list, each as a jf2 or mf2 h-card, a URL or a
	 * name. Before 4.0.0 the metabox saved several authors as one card whose
	 * name, url and photo were lists; the values are paired up by position.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $author The author or authors.
	 * @return array[] Cards with string type, name, url and photo, leaving out empty ones.
	 */
	public static function normalize_authors( $author ) {
		if ( wp_is_numeric_array( $author ) ) {
			$authors = array();
			foreach ( $author as $item ) {
				$authors = array_merge( $authors, self::normalize_authors( $item ) );
			}
			return $authors;
		}
		if ( is_string( $author ) ) {
			$author = wp_http_validate_url( $author ) ? array( 'url' => $author ) : array( 'name' => $author );
		}
		if ( ! is_array( $author ) ) {
			return array();
		}
		if ( \ParseThis\MF2_Utils::is_microformat( $author ) ) {
			$author = \ParseThis\mf2_to_jf2( $author );
		}

		$fields = array();
		foreach ( array( 'name', 'url', 'photo' ) as $key ) {
			$value = isset( $author[ $key ] ) ? $author[ $key ] : '';
			if ( is_array( $value ) && isset( $value['value'] ) ) {
				// A jf2 photo with alt text.
				$value = $value['value'];
			}
			$fields[ $key ] = array_values( array_filter( (array) $value, 'is_scalar' ) );
		}

		$authors = array();
		$count   = max( array_map( 'count', $fields ) );
		for ( $i = 0; $i < $count; $i++ ) {
			$card = array( 'type' => 'card' );
			foreach ( $fields as $key => $values ) {
				$card[ $key ] = isset( $values[ $i ] ) ? trim( (string) $values[ $i ] ) : '';
			}
			if ( '' !== $card['name'] . $card['url'] . $card['photo'] ) {
				$authors[] = $card;
			}
		}
		return $authors;
	}
	/**
	 * Stores a date property.
	 *
	 * For posts other than attachments, 'published' and 'updated' set the
	 * post's own dates. Everything else is stored in post meta.
	 *
	 * @since 3.4.0
	 *
	 * @param string $key   Property to set, such as 'published' or 'start'.
	 * @param mixed  $value A date, in any form kind_safe_datetime() accepts.
	 * @return int|bool|WP_Error The result of updating the post or its meta,
	 *                           or false if the value is not a valid date.
	 */
	public function set_datetime_property( $key, $value ) {
		// In an attachment the post date properties reflect when the item was uploaded not when the piece was created.
		// A mutable copy, as the timezone is changed below.
		$value = kind_safe_datetime( $value, null, false );
		if ( ! $value ) {
			return false;
		}
		if ( 'attachment' !== get_post_type( $this->id ) && in_array( $key, array( 'published', 'updated' ), true ) ) {
			$k    = 'published' === $key ? 'post_date' : 'post_modified';
			$args = array( 'ID' => $this->id );
			$wptz = wp_timezone();
			$value->setTimeZone( $wptz );
			$args[ $k ] = $value->format( 'Y-m-d H:i:s' );
			$value->setTimeZone( new DateTimeZone( 'GMT' ) );
			$args[ $k . '_gmt' ] = $value->format( 'Y-m-d H:i:s' );
			return wp_update_post( $args, true );
		}

		return update_post_meta( $this->id, 'mf2_' . $key, $value->format( DATE_W3C ) );
	}

	/**
	 * Stores the post's duration as an ISO 8601 duration.
	 *
	 * @since 3.4.0
	 *
	 * @param mixed $value A duration, in any form kind_safe_interval() accepts.
	 * @return int|bool The result of updating the meta, or false if the value is not a valid duration.
	 */
	public function set_duration( $value ) {
		$value = kind_safe_interval( $value );
		if ( ! $value ) {
			return false;
		}

		$duration = date_interval_to_iso8601( $value );

		return update_post_meta( $this->id, 'mf2_duration', $duration );
	}

	/**
	 * Stores the author of an attachment.
	 *
	 * Other posts use their WordPress author, so nothing is stored for them.
	 *
	 * @since 3.4.0
	 *
	 * @param array $value Author, as mf2.
	 * @return int|bool|null The result of updating the meta, or null if the post is not an attachment.
	 */
	public function set_author( $value ) {
		// Attachments may have been uploaded by a user but may have metadata for original author.
		if ( 'attachment' === get_post_type( $this->id ) ) {
			return update_post_meta( $this->id, 'mf2_author', $value );
		}
	}

	/**
	 * Stores a property, or several.
	 *
	 * @since 3.4.0
	 *
	 * @param string|array $key   Property to set, or an array of property => value pairs.
	 * @param mixed        $value Optional. The value. Default null.
	 * @return mixed The result of storing the value. True when setting an array,
	 *               and null if the key or value is empty.
	 */
	public function set( $key, $value = null ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $k => $v ) {
				$this->set( $k, $v );
			}
			return true;
		}

		if ( empty( $key ) || empty( $value ) ) {
			return;
		}
		$args = array( 'ID' => $this->id );

		switch ( $key ) {
			case 'published':
			case 'updated':
			case 'start':
			case 'end':
				return $this->set_datetime_property( $key, $value );
			case 'author':
				return $this->set_author( $value );
			case 'featured':
				if ( wp_http_validate_url( $value ) ) {
					$featured = self::get_attachment_id( $value );
					if ( $featured ) {
						$value = $featured;
					}
				}
				if ( is_numeric( $value ) ) {
					return set_post_thumbnail( $this->id, $value );
				} else {
					return false;
				}
			case 'name':
				$args['post_title'] = $value;
				return wp_update_post( $args, true );
			case 'duration':
				return $this->set_duration( $value );
			case 'summary':
			case 'content':
				if ( is_array( $value ) ) {
					if ( array_key_exists( 'html', $value ) ) {
						$value = $value['html'];
					} elseif ( wp_is_numeric_array( $value ) ) {
						$value = $value[0];
					}
				}
				$k          = 'summary' === $key ? 'post_excerpt' : 'post_content';
				$args[ $k ] = $value;
				return wp_update_post( $args, true );
			case 'audio':
				// All media is handled identically.
			case 'video':
				// All media is handled identically.
			case 'photo':
				if ( \ParseThis\MF2_Utils::is_microformat( $value ) ) {
					$url = \ParseThis\MF2_Utils::get_plaintext( $value, 'url' );
					$id  = self::get_attachment_id( $url );
					if ( $id ) {
						$value = \ParseThis\mf2_to_jf2( $value );
						unset( $value['type'] );
						$attachment = new Kind_Post( $id );
						foreach ( $value as $k => $v ) {
							$attachment->set( $k, $v );
						}
					}

					return update_post_meta( $this->id, 'mf2_' . $key, array( $url ) );
				}
				// If it is not a microformat handle as default.
			default:
				return update_post_meta( $this->id, 'mf2_' . $key, $value );
		}
	}

	/**
	 * Deletes a property stored in post meta.
	 *
	 * @since 3.4.0
	 *
	 * @param string $key Property to delete.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key ) {
		return delete_post_meta( $this->id, 'mf2_' . $key );
	}
}
