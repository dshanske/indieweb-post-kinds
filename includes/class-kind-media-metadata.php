<?php
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
		$wp_version = get_bloginfo( 'version' );
		add_filter( 'wp_generate_attachment_metadata', array( static::class, 'wp_generate_attachment_metadata' ), 33, 2 );
		if ( version_compare( $wp_version, '5.3', '>' ) ) {
			add_filter( 'wp_update_attachment_metadata', array( static::class, 'wp_sanitize_media_metadata' ), 9, 2 );
		}
		add_action( 'wp_enqueue_scripts', array( static::class, 'enqueue' ) );

		add_action( 'save_post', array( static::class, 'save_post' ), 20 );

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
		if ( ! is_front_page() && is_singular() && ! self::is_amp_endpoint() ) {
			wp_enqueue_script(
				'media-fragment',
				plugins_url( 'js/clone-media-fragment.js', __DIR__ ),
				array(),
				'1.0',
				true
			);
		}
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
		if ( Parse_This_MF2_Utils::is_microformat( $author ) ) {
			$author = mf2_to_jf2( $author );
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
		$author = array();
		if ( isset( $attachment['author_name'] ) && ! empty( $attachment['author_name'] ) ) {
			$author['name'] = $attachment['author_name'];
		}
		if ( isset( $attachment['author_url'] ) && ! empty( $attachment['author_url'] ) ) {
			$author['url'] = $attachment['author_url'];
		}
		$author = array_filter( $author );
		if ( empty( $author ) ) {
			delete_post_meta( $post['ID'], 'mf2_author' );
		} else {
			$author['type'] = 'card';
			update_post_meta( $post['ID'], 'mf2_author', jf2_to_mf2( $author ) );
		}
		return $post;
	}

	/*
	 * Determine Attached Images from a Content Block.
	 *
	 * @param string $content Content.
	 * @return array Array of Attachment IDs.
	*/
	public static function get_img_from_content( $content ) {
		$content = wp_unslash( $content );
		$return  = array();
		$doc     = pt_load_domdocument( $content );
		$images  = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$classes = $image->getAttribute( 'class' );
			$classes = explode( ' ', $classes );
			foreach ( $classes as $class ) {
				if ( 0 === strpos( $class, 'wp-image-' ) ) {
					$id = (int) str_replace( 'wp-image-', '', $class );
					if ( 0 !== $id ) {
						$return[] = $id;
					}
					break;
				}
			}
			$url = $image->getAttribute( 'src' );
			$id  = attachment_url_to_postid( $url );
			if ( 0 !== $id ) {
				$return[] = $id;
			}
		}
		return array_unique( $return );
	}

	/*
	 * Determine Attached Audio from a Content Block.
	 *
	 * @param string $content Content.
	 * @return array Array of Attachment IDs.
	*/
	public static function get_audio_from_content( $content ) {
		$content = wp_unslash( $content );
		$return  = array();
		$doc     = pt_load_domdocument( $content );
		$audios  = $doc->getElementsByTagName( 'audio' );
		foreach ( $audios as $audio ) {
			$sources = $audio->getElementsByTagName( 'source' );
			foreach ( $sources as $source ) {
				$url      = remove_query_arg( '_', $source->getAttribute( 'src' ) );
				$id       = attachment_url_to_postid( $url );
				$return[] = $id;
			}
		}
		return array_unique( $return );
	}

	/*
	 * Determine Attached Videos from a Content Block.
	 *
	 * @param string $content Content.
	 * @return array Array of Attachment IDs.
	*/
	public static function get_video_from_content( $content ) {
		$content = wp_unslash( $content );
		$return  = array();
		$doc     = pt_load_domdocument( $content );
		$videos  = $doc->getElementsByTagName( 'video' );
		foreach ( $videos as $video ) {
			$sources = $video->getElementsByTagName( 'source' );
			foreach ( $sources as $source ) {
				$url      = remove_query_arg( '_', $source->getAttribute( 'src' ) );
				$id       = attachment_url_to_postid( $url );
				$return[] = $id;
			}
		}
		return array_unique( $return );
	}

	private function media_sideload_image( $url, $post_id, $description = null ) {
		// To prevent sideloading the same URL multiple times check for the original URL which will now be stored
		$ids = get_posts(
			array(
				'post_type'        => 'attachment',
				'suppress_filters' => false,
				'nopaging'         => true,
				'meta_key'         => '_source_url',
				'meta_value'       => $url,
				'fields'           => 'ids',
			)
		);
		if ( ! empty( $ids ) ) {
			return $ids[0];
		}
		$id = media_sideload_image( $url, $post_id, $description, 'id' );
		// Version 5.4 adds this automatically.
		if ( $id && version_compare( $wp_version, '5.3', '>' ) ) {
			update_post_meta( $id, '_source_url', $url );
		}
		return $id;
	}



	/**
	 * Every time the post is saved check for media embedded in content and save a list of attachment IDs.
	*/
	public static function save_post( $post_id ) {
		$post    = get_post( $post_id );
		$content = do_shortcode( $post->post_content );

		$gallery_ids = array();
		$gallery     = get_post_gallery( $post_id, false );

		if ( is_array( $gallery ) ) {
			if ( array_key_exists( 'ids', $gallery ) ) {
				$gallery_ids = explode( ',', $gallery['ids'] );
			}
		}
		$ids = self::get_img_from_content( $content );
		if ( is_array( $ids ) && is_array( $gallery_ids ) ) {
			$ids = array_merge( $ids, $gallery_ids );
		}

		if ( ! $ids ) {
			delete_post_meta( $post_id, '_content_img_ids' );
		} else {
			update_post_meta( $post_id, '_content_img_ids', $ids );
		}
		$ids = self::get_video_from_content( $content );
		if ( ! $ids ) {
			delete_post_meta( $post_id, '_content_video_ids' );
		} else {
			update_post_meta( $post_id, '_content_video_ids', $ids );
		}
		$ids = self::get_audio_from_content( $content );
		if ( ! $ids ) {
			delete_post_meta( $post_id, '_content_audio_ids' );
		} else {
			update_post_meta( $post_id, '_content_audio_ids', $ids );
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
		$data       = array_filter( $data );
		$attachment = get_post( $attachment_id, ARRAY_A );
		if ( isset( $data['image_meta'] ) ) {
			$meta = $data['image_meta'];
			if ( ! empty( $meta['credit'] ) ) {
				update_post_meta(
					$attachment_id,
					'mf2_author',
					jf2_to_mf2(
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
				jf2_to_mf2(
					array(
						'name' => $data['artist'],
						'type' => 'card',
					)
				)
			);
		}
		if ( isset( $data['length'] ) ) {
			update_post_meta( $attachment_id, 'mf2_duration', array( seconds_to_iso8601( $data['length'] ) ) );
		}
		return $data;
	}
} // End Class
