<?php
/**
 *
 * Enhances Metadata for Media
 */
class Media_Metadata {

	/**
	 * Function to Initialize the Configuration.
	 *
	 * @access public
	 */
	public static function init() {
		$cls = get_called_class();
		add_filter( 'wp_update_attachment_metadata', array( $cls, 'wp_update_attachment_metadata' ), 10, 2 );
	}

	public static function wp_update_attachment_metadata( $metadata, $attachment_id ) {
		if ( ! empty( $metadata['album'] ) ) {
			update_post_meta( $attachment_id, 'mf2_publication', array( $metadata['album'] ) );
		}
		if ( ! empty( $metadata['artist'] ) ) {
			update_post_meta(
				$attachment_id,
				'mf2_author',
				jf2_to_mf2(
					array(
						'name' => $metadata['artist'],
						'type' => 'card',
					)
				)
			);
		}
		if ( isset( $metadata['length'] ) ) {
			update_post_meta( $attachment_id, 'mf2_duration', array( seconds_to_iso8601( $metadata['length'] ) ) );
		}
		return $metadata;
	}
} // End Class


