<?php
/**
 * Kind Post Class
 *
 * @package Post Kinds
 * Assists in retrieving/saving properties from a Post. Replaces MF2_Post by only looking for Items on Demand instead of Parsing Them initially.
 */
class Kind_Post {

	public $id;

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
				$id       = attachment_url_to_postid( $post );
				$this->id = $id;
				$post     = $id;
			}
		}

		$post = get_post( $post );
	}

	/*
	 * Check is post is attachment.
	 *
	 * @param WP_Post $post Post Object.
	 * @return boolean True if attachment.
	 */
	private function is_attachment( $post ) {
		return ( 'attachment' === get_post_type( $post ) );
	}


	/*
	 * Returns WP_Post Object
	 *
	 * @return WP_Post Post Object.
	 *
	 */
	public function get_post() {
		return get_post( $this->id );
	}

	/*
	 * Returns the Post Kind. For Attachments will return the media type.
	 *
	 * @return string Post Kind.
	 *
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

	/*
	 * Get Name.
	 *
	 * @return string Return name.
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

	/*
	 * Get Content.
	 *
	 * @return array with HTML and Plaintext Version of Content or Summary
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

	/*
	 * Get Permalink URL
	 *
	 * @return string $url
	 */
	public function get_url() {
		if ( 'attachment' === get_post_type( $this->id ) ) {
			return wp_get_attachment_url( $this->id );
		} else {
			return get_permalink( $this->id );
		}
	}

	/*
	 * Get Featured Image Permalink
	 *
	 * @return string $url
	 */
	public function get_featured() {
		if ( has_post_thumbnail( $this->id ) ) {
			return wp_get_attachment_url( get_post_thumbnail_id( $this->id ) );
		}
		return false;
	}


	/*
	 * Return datetime property as a DateTime Object.
	 *
	 * @param string $property Property You Wish to Return.
	 * @return DateTimeImmutable Published Time in Local Timezone.
	 *
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
		return new DateTimeImmutable( $datetime );
	}

	/*
	 * Returns Publication.
	 *
	 * @return string
	 *
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

	public function get_duration() {
		$duration = get_post_meta( $this->id, 'mf2_duration', true );
		if ( is_array( $duration ) ) {
			$duration = $duration[0];
		}
		if ( $duration ) {
			return new DateInterval( $duration );
		}
		return false;
	}

	/*
	 * Return Categories which are a combination of Tags and Categories.
	 *
	 * @return array Array of the names of Categories and Tag names.
	 *
	 */
	public function get_categories() {
		$category = array();
		// Get a list of categories and extract their names
		$post_categories = get_the_terms( $this->id, 'category' );
		if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) {
			$category = wp_list_pluck( $post_categories, 'name' );
		}

		// Get a list of tags and extract their names
		$post_tags = get_the_terms( $this->id, 'post_tag' );
		if ( ! empty( $post_tags ) && ! is_wp_error( $post_tags ) ) {
			$category = array_merge( $this->category, wp_list_pluck( $post_tags, 'name' ) );
		}
		if ( in_array( 'Uncategorized', $category, true ) ) {
			unset( $category[ array_search( 'Uncategorized', $category, true ) ] );
		}
		return $category;
	}

	/**
	 * Is prefix in string.
	 *
	 * @param  string $source The source string.
	 * @param  string $prefix The prefix you wish to check for in source.
	 * @return boolean The result.
	 */
	protected static function str_prefix( $source, $prefix ) {
		return strncmp( $source, $prefix, strlen( $prefix ) ) === 0;
	}

	/**
	 * Returns True if Array is Multidimensional.
	 *
	 * @param array $arr array.
	 *
	 * @return boolean result
	 */
	protected static function is_multi_array( $arr ) {
		if ( count( $arr ) === count( $arr, COUNT_RECURSIVE ) ) {
			return false;
		} else {
			return true;
		}
	}

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
	 * Retrieve author
	 *
	 * @return boolean|array The result or false if does not exist.
	 */
	public function get_author() {
		$post = $this->get_post();
		if ( ! $post->post_author ) {
			$author = get_post_meta( $post->ID, 'mf2_author', true );
			return ( $author ?? false );
		}
		// Attachments may have been uploaded by a user but may have metadata for original author
		if ( 'attachment' === get_post_type( $this->id ) ) {
			$author = get_post_meta( $post->ID, 'mf2_author', true );
			return ( $author ?? false );
		}
		return array(
			'type'       => array( 'h-card' ),
			'properties' => array(
				'name'  => array( get_the_author_meta( 'display_name', $post->post_author ) ),
				'url'   => array( get_the_author_meta( 'user_url', $post->post_author ) ? get_the_author_meta( 'user_url', $post->post_author ) : get_author_posts_url( $post->post_author ) ),
				'photo' => array( get_avatar_url( $this->post_author ) ),
			),
		);
	}

	/*
	 * Return Attached Media IDs.
	 *
	 * @param string $type audio, video, or photo.
	 * @return array Array of Media IDs.
	 *
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

	/*
	 * Return Attached Photos.
	 *
	 * Looks in both attached media and the photo property.
	 *
	 * @param boolean $content If true then return empty if there are any images in content.
	 * @return array Array of Media IDs.
	 *
	 */
	public function get_photo( $content = true ) {
		// Check if the post itself is an image attachment.
		if ( wp_attachment_is( 'image', $this->id ) ) {
			return array( $this->id );
		}

		$content_ids = get_post_meta( $this->id, '_content_img_ids', true );

		if ( false === $content_ids ) {
			// Check for a gallery first.
			$gallery = get_post_gallery( $this->id, false );
			if ( $gallery & array_key_exists( 'ids', $gallery ) ) {
				$content_ids = explode( ',', $gallery['ids'] );
			}
			$content_ids = array();
			$post        = $this->get_post();
			$shortcode   = false;
			$shortcodes  = apply_filter( 'kind_photo_shortcode_exclude', array( 'vr', '360' ) );
			if ( $post->post_content ) {
				foreach ( $shortcodes as $code ) {
					if ( has_shortcode( $post->post_content, $code ) ) {
						$shortcode = true;
					}
				}
				if ( $shortcode ) {
					$content_ids = array();
				} else {
					$content_ids = array_merge( $content_ids, Kind_Media_Metadata::get_img_from_content( $post->post_content ) );
				}
				update_post_meta( $this->id, '_content_img_ids', $content_ids );
			}
		}

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

	/*
	 * Return Attached Audio.
	 *
	 * Looks in both attached media and the audio property.
	 *
	 * @param boolean $content If true then return empty if there are any audio files in content.
	 * @return array Array of Media IDs or URLs.
	 *
	 */
	public function get_audio( $content = true ) {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'audio', $this->id ) ) {
			return array( $this->id );
		}

		$content_ids = get_post_meta( $this->id, '_content_audio_ids', true );

		if ( false === $content_ids ) {
			$post = get_post();
			if ( $post->post_content ) {
				$content_ids = Kind_Media_Metadata::get_audio_from_content( $post->post_content );
				update_post_meta( $this->id, '_content_audio_ids', $content_ids );
			}
		}

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

		// If there are ids found return them
		if ( ! empty( $audio_ids ) || ! empty( $att_ids ) || ! empty( $content_ids ) ) {
			return array_unique( array_merge( $att_ids, $this->get_attachments_from_urls( $audios ), $content_ids ) );
		}

		// This means there are external URLs for audio provided.
		if ( ! empty( $audios ) ) {
			return $audios;
		}

		return false;
	}

	/*
	 * Return Attached Video.
	 *
	 * Looks in both attached media and the audio property.
	 *
	 * @param boolean $content If true then return empty if there are any video files in content.
	 * @return array Array of Media IDs.
	 *
	 */
	public function get_video( $content = true ) {
		// Check if the post itself if an audio attachment.
		if ( wp_attachment_is( 'video', $this->id ) ) {
			return array( $this->id );
		}
		$content_ids = get_post_meta( $this->id, '_content_video_ids', true );

		if ( false === $content_ids ) {
			$post = get_post();
			if ( $post->post_content ) {
				$content_ids = Kind_Media_Metadata::get_video_from_content( $post->post_content );
				update_post_meta( $this->id, '_content_video_ids', $content_ids );
			}
		}

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
				} elseif ( is_numeric( $url ) ) {
					$att_ids[] = $url;
				} else {
					$att_ids[] = attachment_url_to_postid( $url );
				}
			}
		}
		return array_filter( array_unique( $att_ids ) );
	}

	/**
	 * Retrieve value
	 *
	 * @param  string $key The key to retrieve.
	 * @param  boolean $single Whether to return a a single value or array if there is only one value.
	 * @return mixed The result or false if does not exist.
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
			$cite = jf2_to_mf2(
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
				$this->delete( 'cite' );
			}
		}

		// If this is formatted as JF2 try to convert it to MF2 and update.
		if ( is_array( $cite ) && ! wp_is_numeric_array( $cite ) ) {
			$cite['type'] = 'cite';
			$cite         = jf2_to_mf2( $cite );
			$property     = Kind_Taxonomy::get_kind_info( $this->get_kind(), 'property' );
			$this->set( $property, $cite );
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

	/* Returns a normalized cite with all possible parameters present to reduce isset checks and to ensure everything is formatted correctly
	 * For Display and Metabox purposes only.
	 */
	public function normalize_cite( $cite ) {
		// Ensures that an empty string is always present in the cite
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
			'category'    => '',
		);

		if ( ! $cite ) {
			return $defaults;
		}

		if ( wp_is_numeric_array( $cite ) && 1 === count( $cite ) ) {
			$cite = $cite[0];
		}
		if ( Parse_This_MF2_Utils::is_microformat( $cite ) ) {
			$cite = mf2_to_jf2( $cite );
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

		if ( wp_is_numeric_array( $cite['author'] ) ) {
			if ( 1 === count( $cite['author'] ) ) {
				$cite['author'] = array_pop( $cite['author'] );
			}
		}

		if ( is_string( $cite['author'] ) ) {
			$cite['author'] = wp_http_validate_url( $cite['author'] ) ? array( 'url' => $cite['author'] ) : array( 'name' => $cite['author'] );
		}

		$cite['author'] = wp_parse_args( $cite['author'], $author_defaults );

		if ( is_array( $cite['publication'] ) ) {
			$cite['publication'] = $cite['publication']['name'];
		}

		if ( is_array( $cite['author']['name'] ) ) {
			$cite['author']['name'] = implode( ';', $cite['author']['name'] );
		}
		if ( is_array( $cite['author']['url'] ) ) {
			$cite['author']['url'] = implode( ';', $cite['author']['url'] );
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

	public function set_datetime_property( $key, $value ) {
		// In an attachment the post date properties reflect when the item was uploaded not when the piece was created.
		if ( ! $value instanceof DateTime ) {
			$value = new DateTime( $value );
		}
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

	public function set_duration( $value ) {
		if ( ! $value instanceof DateInterval ) {
			$value = new DateInterval( $value );
		}
		if ( ! $value ) {
			return false;
		}

		$duration = date_interval_to_iso8601( $value );

		return update_post_meta( $this->id, 'mf2_duration', $duration );
	}

	/**
	 * Set author
	 *
	 * @param array $value Author microformat.
	 * @return boolean|WP_Error
	 */
	public function set_author( $value ) {
		// Attachments may have been uploaded by a user but may have metadata for original author
		if ( 'attachment' === get_post_type( $this->id ) ) {
			return update_post_meta( $this->id, 'mf2_author', $value );
		}
	}

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
					$featured = attachment_url_to_postid( $value );
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
				/* All media is handled identically.
				*/
			case 'video':
				/* All Media is handled identically.
				*/
			case 'photo':
				if ( Parse_This_MF2::is_microformat( $value ) ) {
					$url = Parse_This_MF2::get_plaintext( $value, 'url' );
					$id  = attachment_url_to_postid( $url );
					if ( $id ) {
						$value = mf2_to_jf2( $value );
						unset( $value['type'] );
						$attachment = new Kind_Post( $id );
						foreach ( $value as $k => $v ) {
							$attachment->set( $k, $v );
						}
					}

					return update_post_meta( $this->id, 'mf2_' . $key, array( $url ) );
				}
				/* If it is not a microformat handle as default.
				*/
			default:
				return update_post_meta( $this->id, 'mf2_' . $key, $value );
		}
	}

	public function delete( $key ) {
		return delete_post_meta( $this->id, 'mf2_' . $key );
	}
}
