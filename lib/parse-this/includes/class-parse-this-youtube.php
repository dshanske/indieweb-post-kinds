<?php
/**
 * Parse This YouTube class.
 */
class Parse_This_YouTube extends Parse_This_Base {
	/**
	 *
	 * @access public
	 */
	public static function parse( $content, $url, $args ) {
		if ( ! $content ) {
			return array();
		}

		preg_match( '#ytInitialPlayerResponse = (\{.+\});#U', $content, $match );
		$decode = json_decode( $match[1], true );
		if ( empty( $decode ) ) {
			return array();
		}
		if ( ! isset( $decode['videoDetails'] ) ) {
			return array();
		}
		$details       = $decode['videoDetails'];
		$microformat   = $decode['microformat']['playerMicroformatRenderer'];
		$jf2           = array(
			'uid'       => ifset( $details['videoID'] ),
			'name'      => ifset( $details['title'] ),
			'duration'  => seconds_to_iso8601( ifset( $details['lengthSeconds'] ) ),
			'category'  => ifset( $details['keywords'] ),
			'summary'   => ifset( $details['shortDescription'] ),
			'published' => normalize_iso8601( ifset( $microformat['publishDate'] ) ),
		);
		$author        = array(
			'type' => 'card',
			'url'  => ifset( $microformat['ownerProfileUrl'] ),
			'name' => ifset( $details['author'] ),
		);
		$jf2['author'] = array_filter( $author );

		if ( isset( $details['thumbnail'] ) ) {
			$thumbnail       = end( $details['thumbnail']['thumbnails'] );
			$jf2['featured'] = $thumbnail['url'];
		}
		if ( isset( $microformat['embed'] ) ) {
			$jf2['video'] = ifset( $microformat['embed']['iframeUrl'] );
		}
		if ( WP_DEBUG ) {
			$jf2['_yt'] = $decode;
		}
		return array_filter( $jf2 );
	}
}
