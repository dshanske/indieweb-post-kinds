<?php
/**
 * Kind Media View Class
 *
 * @package Post Kind
 * Used to Display Media
 */

class Kind_Media_View {
	private $ids;
	private $type;
	public function __construct( $ids, $type ) {
		$this->ids  = $ids;
		$this->type = $type;
	}

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
	 * @access private
	 * @param int|string   $id   Audio attachment ID or audio URL
	 * @param mixed $args Arguments for the audio media view.
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
			$return = wp_audio_shortcode( $args );
		}
		return $return;
	}

	/**
	 * Return a media view for the video post kind.
	 *
	 * @access private
	 * @param int|string   $id   Video attachment ID or URL.
	 * @param mixed $args Arguments for the video media view.
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
		} if ( 0 === $id ) {
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
