<?php
/**
 * Kind_Media_View class.
 *
 * @package Post_Kinds
 * @since   3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the markup for images, audio and video in kind views.
 *
 * @since 3.3.0
 */
class Kind_Media_View {
	/**
	 * Attachment IDs or media URLs.
	 *
	 * @since 3.3.0
	 * @var int[]|string[]|int|string
	 */
	private $ids;

	/**
	 * Media type: 'photo', 'audio' or 'video'.
	 *
	 * @since 3.3.0
	 * @var string
	 */
	private $type;

	/**
	 * Sets up the media to display.
	 *
	 * @since 3.3.0
	 *
	 * @param int[]|string[]|int|string $ids  Attachment IDs or media URLs.
	 * @param string                    $type Media type: 'photo', 'audio' or 'video'.
	 */
	public function __construct( $ids, $type ) {
		$this->ids  = $ids;
		$this->type = $type;
	}

	/**
	 * Returns the markup for the media.
	 *
	 * @since 3.3.0
	 *
	 * @return string The markup, or an empty string if the type is not supported.
	 */
	public function get() {
		switch ( $this->type ) {
			case 'photo':
				return $this->photo( $this->ids );
			case 'audio':
				return $this->audio( $this->ids );
			case 'video':
				return $this->video( $this->ids );
		}
		return '';
	}

	/**
	 * Returns a one-column gallery of photos.
	 *
	 * @since 3.3.0
	 *
	 * @param int[] $photos Attachment IDs.
	 * @return string The gallery markup.
	 */
	private function photo( $photos ) {
		return gallery_shortcode(
			array(
				'ids'     => $photos,
				'size'    => 'large',
				'columns' => 1,
				'link'    => 'file',
			)
		);
	}

	/**
	 * Return a media view for the audio post kind.
	 *
	 * @since 3.3.0
	 *
	 * @access private
	 *
	 * @param int|string|array $id   Audio attachment ID or audio URL, or a list of them.
	 * @param array|null       $args Arguments for the audio media view.
	 *
	 * @return array|string|void
	 */
	private function audio( $id, $args = null ) {
		$return  = array();
		$default = array(
			'class' => 'wp-audio-shortcode u-audio',
		);
		if ( is_array( $id ) ) {
			if ( 1 === count( $id ) ) {
				$id = array_shift( $id );
			} else {
				foreach ( $id as $i ) {
					$return[] = $this->audio( $i, $args );
				}
				return implode( ' ', $return );
			}
		}

		if ( wp_http_validate_url( $id ) ) {
			$args['src'] = $id;
		} elseif ( 0 === $id ) {
			return '';
		} else {
			$args['src'] = wp_get_attachment_url( (int) $id );
		}
		$return = '';
		if ( $args['src'] ) {
			$args   = wp_parse_args( $args, $default );
			$return = wp_audio_shortcode( $args );
		}
		return $return;
	}

	/**
	 * Return a media view for the video post kind.
	 *
	 * @since 3.3.0
	 *
	 * @access private
	 *
	 * @param int|string|array $id   Video attachment ID or URL, or a list of them.
	 * @param array|null       $args Arguments for the video media view.
	 *
	 * @return array|string|void
	 */
	private function video( $id, $args = null ) {
		$return   = array();
		$defaults = array(
			'class' => 'wp-video-shortcode u-video',
		);
		if ( is_array( $id ) ) {
			foreach ( $id as $i ) {
				$return[] = $this->video( $i, $args );
			}
			return implode( ' ', $return );
		} elseif ( wp_http_validate_url( $id ) ) {
			$args['src'] = $id;
		} elseif ( 0 === $id ) {
			return '';
		} else {
			$args['src'] = wp_get_attachment_url( (int) $id );
		}
		$return = '';
		if ( $args['src'] ) {
			$args   = wp_parse_args( $args, $defaults );
			$return = wp_video_shortcode( $args );
		}
		return $return;
	}
}
