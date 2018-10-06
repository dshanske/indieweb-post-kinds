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
		if ( isset( $metadata['album'] ) ) {
			update_post_meta( $attachment_id, 'mf2_publication', $metadata['album'] );
		}
		if ( isset( $metadata['artist'] ) ) {
			update_post_meta( $attachment_id, 'mf2_author', $metadata['artist'] );
		}
		if ( isset( $metadata['length'] ) ) {
			update_post_meta( $attachment_id, 'mf2_duration', seconds_to_iso8601( $metadata['length'] ) );
		}
		return $metadata;
	}
} // End Class


