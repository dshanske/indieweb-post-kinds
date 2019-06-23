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
		$cls = get_called_class();
		add_filter( 'wp_update_attachment_metadata', array( $cls, 'wp_update_attachment_metadata' ), 33, 2 );
		if ( ! function_exists( 'wp_sanitize_media_metadata' ) ) {
			add_filter( 'wp_update_attachment_metadata', array( $cls, 'wp_sanitize_media_metadata' ), 9, 2 );
		}
		add_action( 'wp_enqueue_scripts', array( $cls, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( is_singular() ) {
			wp_enqueue_script(
				'media-fragment',
				plugins_url( 'js/clone-media-fragment.js', dirname( __FILE__ ) ),
				array(),
				'1.0',
				true
			);
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
				$value    = $encoding ? mb_convert_encoding( $value, 'UTF-8', $encoding ) : utf8_encode( $value );
			}
			$metadata[ $name ] = $value;
		}
		return $metadata;
	}

	public static function wp_update_attachment_metadata( $data, $attachment_id ) {
		$data = array_filter( $data );
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


