<?php
defined( 'ABSPATH' ) || exit;

/**
 *
 * Enhances Metadata for Media
 */
class Kind_Media_Metadata {

	/**
	 * Function to Initialize the Configuration.
	 *
	 * @access public
	 */
	public static function init() {
		add_filter( 'wp_generate_attachment_metadata', array( static::class, 'wp_generate_attachment_metadata' ), 33, 2 );
		add_filter( 'wp_update_attachment_metadata', array( static::class, 'wp_sanitize_media_metadata' ), 9, 2 );
		add_action( 'wp_enqueue_scripts', array( static::class, 'enqueue' ) );

		// Kinds apply to posts only; revisions and autosaves have their own post type.
		add_action( 'save_post_post', array( static::class, 'save_post' ), 20 );

		// The photo property can change outside a save, for example from Micropub.
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			add_action( $hook, array( static::class, 'photo_meta_changed' ), 10, 3 );
		}

		add_filter( 'attachment_fields_to_edit', array( static::class, 'attachment_fields_to_edit' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( static::class, 'attachment_fields_to_save' ), 10, 2 );
	}

	public static function is_amp_endpoint() {
		if ( function_exists( 'is_amp_endpoint' ) ) {
			return is_amp_endpoint();
		}
		return false;
	}

	public static function enqueue() {
		if ( is_front_page() || ! is_singular() || self::is_amp_endpoint() || ! self::has_media( get_queried_object() ) ) {
			return;
		}
		wp_enqueue_script(
			'media-fragment',
			plugins_url( 'js/clone-media-fragment.js', __DIR__ ),
			array(),
			'1.0',
			// WordPress 6.2 reads the array as a true $in_footer; 6.3 and later also defer it.
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Returns whether a post may show audio or video, which the media
	 * fragment script links to.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $post Post object, usually the queried object.
	 * @return bool True if the post has an audio or video kind or property, or
	 *              audio or video in its content.
	 */
	public static function has_media( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
		if ( 'post' === $post->post_type ) {
			if ( has_post_kind( array( 'audio', 'video', 'listen', 'watch', 'jam' ), $post ) ) {
				return true;
			}
			if ( get_post_meta( $post->ID, 'mf2_audio', true ) || get_post_meta( $post->ID, 'mf2_video', true ) ) {
				return true;
			}
		}
		return (bool) preg_match( '/<(audio|video)[\s>]|\[(audio|video|playlist)[\s\]]/i', $post->post_content );
	}


	/**
	 * Displays the Media Creator Description on the Attachment Page.
	 *
	 * @param array   $form_fields See attachment_fields_to_edit filter in WordPress.
	 * @param WP_Post $post Attachment post object.
	 * @return array $form_fields Updated with extra fields.
	 */
	public static function attachment_fields_to_edit( $form_fields, $post ) {

		$author = get_post_meta( $post->ID, 'mf2_author', true );
		if ( \ParseThis\MF2_Utils::is_microformat( $author ) ) {
			$author = \ParseThis\mf2_to_jf2( $author );
		} else {
			$author = array();
		}

		if ( wp_attachment_is( 'image', $post ) ) {
			$form_fields['author_name'] = array(
				'value'        => ( $author['name'] ?? '' ),
				'label'        => __( 'Artist', 'indieweb-post-kinds' ),
				'helps'        => __( 'The creator of the image', 'indieweb-post-kinds' ),
				'show_in_edit' => true,
			);
			$form_fields['author_url']  = array(
				'value'        => ( $author['url'] ?? '' ),
				'label'        => __( 'Artist URL', 'indieweb-post-kinds' ),
				'helps'        => __( 'The Artists URL or webpage', 'indieweb-post-kinds' ),
				'show_in_edit' => true,
			);
		}
		return $form_fields;
	}

	/**
	 * Saves Extra Fields.
	 *
	 * @param array $post Attachment post data.
	 * @param array   $attachment data.
	 */
	public static function attachment_fields_to_save( $post, $attachment ) {
		// Core checks this before saving; check again as the filter can be applied elsewhere.
		if ( empty( $post['ID'] ) || ! current_user_can( 'edit_post', $post['ID'] ) ) {
			return $post;
		}
		// Core passes the request data slashed.
		$author = array();
		if ( isset( $attachment['author_name'] ) && is_string( $attachment['author_name'] ) ) {
			$author['name'] = sanitize_text_field( wp_unslash( $attachment['author_name'] ) );
		}
		if ( isset( $attachment['author_url'] ) && is_string( $attachment['author_url'] ) ) {
			$author['url'] = sanitize_url( trim( wp_unslash( $attachment['author_url'] ) ), array( 'http', 'https' ) );
		}
		$author = array_filter( $author );
		if ( empty( $author ) ) {
			delete_post_meta( $post['ID'], 'mf2_author' );
		} else {
			$author['type'] = 'card';
			// The metadata API unslashes, so slash the sanitized values.
			update_post_meta( $post['ID'], 'mf2_author', wp_slash( \ParseThis\jf2_to_mf2( $author ) ) );
		}
		return $post;
	}

	/**
	 * Finds the media library attachments used in a content block.
	 *
	 * The content is parsed once for images, audio and video. An image's ID
	 * is taken from its wp-image-{id} class when it has one; otherwise, and
	 * for audio and video sources, the URL is looked up.
	 *
	 * @since 4.0.0
	 *
	 * @param string $content Content.
	 * @return array {
	 *     Attachment IDs found, each list without duplicates or zeros.
	 *
	 *     @type int[] $img   Image attachment IDs.
	 *     @type int[] $audio Audio attachment IDs.
	 *     @type int[] $video Video attachment IDs.
	 * }
	 */
	private static function get_media_from_content( $content ) {
		$return = array(
			'img'   => array(),
			'audio' => array(),
			'video' => array(),
		);
		$content = wp_unslash( (string) $content );
		// Most content has no media; skip parsing it.
		if ( false === stripos( $content, '<img' ) && false === stripos( $content, '<audio' ) && false === stripos( $content, '<video' ) ) {
			return $return;
		}
		$doc = \ParseThis\pt_load_domdocument( $content );

		foreach ( $doc->getElementsByTagName( 'img' ) as $image ) {
			$id = 0;
			foreach ( explode( ' ', $image->getAttribute( 'class' ) ) as $class ) {
				if ( 0 === strpos( $class, 'wp-image-' ) ) {
					$id = (int) substr( $class, strlen( 'wp-image-' ) );
					break;
				}
			}
			if ( ! $id ) {
				$id = attachment_url_to_postid( $image->getAttribute( 'src' ) );
			}
			$return['img'][] = $id;
		}
		foreach ( array( 'audio', 'video' ) as $tag ) {
			foreach ( $doc->getElementsByTagName( $tag ) as $media ) {
				foreach ( $media->getElementsByTagName( 'source' ) as $source ) {
					$return[ $tag ][] = attachment_url_to_postid( remove_query_arg( '_', $source->getAttribute( 'src' ) ) );
				}
			}
		}
		foreach ( $return as $type => $ids ) {
			$return[ $type ] = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		}
		return $return;
	}

	/*
	 * Determine Attached Images from a Content Block.
	 *
	 * @param string $content Content.
	 * @return array Array of Attachment IDs.
	*/
	public static function get_img_from_content( $content ) {
		return self::get_media_from_content( $content )['img'];
	}

	/*
	 * Determine Attached Audio from a Content Block.
	 *
	 * @param string $content Content.
	 * @return array Array of Attachment IDs.
	*/
	public static function get_audio_from_content( $content ) {
		return self::get_media_from_content( $content )['audio'];
	}

	/*
	 * Determine Attached Videos from a Content Block.
	 *
	 * @param string $content Content.
	 * @return array Array of Attachment IDs.
	*/
	public static function get_video_from_content( $content ) {
		return self::get_media_from_content( $content )['video'];
	}

	/**
	 * Every time a post is saved, stores the attachment IDs of the media in its content.
	 *
	 * The lists are kept in the _content_img_ids, _content_audio_ids and
	 * _content_video_ids meta; a key is deleted when there is no media of that
	 * type, so the photos archive can select posts by whether the key exists.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_post( $post_id ) {
		// The classic editor's draft autosave; the next save will record the media.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$content = $post->post_content;
		// Shortcodes such as [gallery], [audio] and [video] produce the media markup.
		if ( false !== strpos( $content, '[' ) ) {
			$content = do_shortcode( $content );
		}
		$media = self::get_media_from_content( $content );

		$gallery = get_post_gallery( $post_id, false );
		if ( is_array( $gallery ) && ! empty( $gallery['ids'] ) ) {
			$media['img'] = array_values( array_unique( array_merge( $media['img'], array_filter( array_map( 'intval', explode( ',', $gallery['ids'] ) ) ) ) ) );
		}

		foreach ( $media as $type => $ids ) {
			$key = '_content_' . $type . '_ids';
			if ( empty( $ids ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $ids );
			}
		}
		self::update_photo_flag( $post_id );
	}

	/**
	 * Updates the photo flag when a post's photo property changes.
	 *
	 * @since 4.0.0
	 *
	 * @param int|int[] $meta_id   Meta ID, or IDs when deleting.
	 * @param int       $object_id Post ID.
	 * @param string    $meta_key  Meta key.
	 */
	public static function photo_meta_changed( $meta_id, $object_id, $meta_key ) {
		if ( 'mf2_photo' === $meta_key && 'post' === get_post_type( $object_id ) ) {
			self::update_photo_flag( $object_id );
		}
	}

	/**
	 * Records whether a post has photos, for the photos archive.
	 *
	 * A post has photos when it has images in its content or a photo
	 * property. The _kind_has_photo meta exists only for those posts, so the
	 * archive can select them with one indexed meta key.
	 *
	 * @since 4.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	public static function update_photo_flag( $post_id ) {
		if ( get_post_meta( $post_id, '_content_img_ids', true ) || get_post_meta( $post_id, 'mf2_photo', true ) ) {
			update_post_meta( $post_id, '_kind_has_photo', 1 );
		} else {
			delete_post_meta( $post_id, '_kind_has_photo' );
		}
	}

	/**
	 * Sanitizes metadata extracted from media files.
	 * https://core.trac.wordpress.org/ticket/46800
	 * Currently only binary strings are sanitized with focus on preventing propagation of
	 * bad character encodings from causing database calls and API endpoints to fail.
	 *
	 * @param array $metadata An existing array with data
	 *
	 * @return array Returns array of sanitized metadata.
	 */
	public static function wp_sanitize_media_metadata( $metadata ) {
		if ( ! is_array( $metadata ) ) {
				return $metadata;
		}
		foreach ( $metadata as $name => $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = wp_sanitize_media_metadata( $value );
			} elseif ( is_string( $value ) && preg_match( '~[^\x20-\x7E\t\r\n]~', $value ) > 0 ) {
				$encoding = mb_detect_encoding( $value, 'ISO-8859-1, UCS-2' );
				$value    = $encoding ? mb_convert_encoding( $value, 'UTF-8', $encoding ) : $value;
			}
			$metadata[ $name ] = $value;
		}
		return $metadata;
	}

	public static function wp_generate_attachment_metadata( $data, $attachment_id ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		$data = array_filter( $data );
		if ( isset( $data['image_meta'] ) ) {
			$meta = $data['image_meta'];
			if ( ! empty( $meta['credit'] ) ) {
				update_post_meta(
					$attachment_id,
					'mf2_author',
					\ParseThis\jf2_to_mf2(
						array(
							'name' => $meta['credit'],
							'type' => 'card',
						)
					)
				);
			}
		}
		if ( ! empty( $data['album'] ) ) {
			update_post_meta( $attachment_id, 'mf2_publication', array( $data['album'] ) );
		}
		if ( ! empty( $data['artist'] ) ) {
			update_post_meta(
				$attachment_id,
				'mf2_author',
				\ParseThis\jf2_to_mf2(
					array(
						'name' => $data['artist'],
						'type' => 'card',
					)
				)
			);
		}
		if ( isset( $data['length'] ) ) {
			update_post_meta( $attachment_id, 'mf2_duration', array( \ParseThis\seconds_to_iso8601( $data['length'] ) ) );
		}
		return $data;
	}
} // End Class
