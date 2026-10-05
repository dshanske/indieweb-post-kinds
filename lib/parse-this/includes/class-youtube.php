<?php
/**
 * YouTube class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts video metadata from YouTube watch pages.
 *
 * Reads the ytInitialPlayerResponse JSON that YouTube embeds in the page.
 * Used by Parser::parse() as the alternate parser for youtube.com and
 * youtu.be URLs when the page has no usable microformats or JSON-LD.
 *
 * @since 1.0.0
 */
class YouTube extends Base {
	/**
	 * Parses a YouTube watch page into jf2.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Raw HTML of the watch page.
	 * @param string $url     URL of the page.
	 * @param array  $args    Parse arguments (see Parser::parse()). Unused.
	 * @return array jf2 properties for the video (name, summary, author, published,
	 *               duration, category, featured, video), or an empty array if the
	 *               player data could not be found.
	 */
	public static function parse( $content, $url, $args ) {
		if ( ! $content ) {
			return array();
		}

		if ( ! is_string( $content ) ) {
			return array();
		}

		$json = self::extract_json( $content, 'ytInitialPlayerResponse = ' );
		if ( null === $json ) {
			return array();
		}
		$decode = json_decode( $json, true );
		if ( empty( $decode ) ) {
			return array();
		}
		if ( ! isset( $decode['videoDetails'] ) ) {
			return array();
		}
		$details       = $decode['videoDetails'];
		$microformat   = isset( $decode['microformat']['playerMicroformatRenderer'] ) ? $decode['microformat']['playerMicroformatRenderer'] : array();
		$jf2           = array(
			'uid'       => $details['videoId'] ?? null,
			'name'      => $details['title'] ?? null,
			'duration'  => seconds_to_iso8601( $details['lengthSeconds'] ?? null ),
			'category'  => $details['keywords'] ?? null,
			'summary'   => $details['shortDescription'] ?? null,
			'published' => normalize_iso8601( $microformat['publishDate'] ?? null ),
		);
		$author        = array(
			'type' => 'card',
			'url'  => $microformat['ownerProfileUrl'] ?? null,
			'name' => $details['author'] ?? null,
		);
		$jf2['author'] = array_filter( $author );

		if ( isset( $details['thumbnail'] ) ) {
			$thumbnail       = end( $details['thumbnail']['thumbnails'] );
			$jf2['featured'] = $thumbnail['url'];
		}
		if ( isset( $microformat['embed'] ) ) {
			$jf2['video'] = $microformat['embed']['iframeUrl'] ?? null;
		}
		if ( ! empty( $args['debug'] ) ) {
			$jf2['_yt'] = $decode;
		}
		return array_filter( $jf2 );
	}

	/**
	 * Extracts the JSON object that follows a marker in a page.
	 *
	 * Matches braces while skipping over JSON strings, so a "};" inside a
	 * string doesn't end the object early, as a regular expression would.
	 *
	 * @since 2.0.0
	 *
	 * @param string $content Page content.
	 * @param string $marker  Text immediately before the object.
	 * @return string|null The JSON text, or null if it isn't found or is incomplete.
	 */
	private static function extract_json( $content, $marker ) {
		$position = strpos( $content, $marker );
		if ( false === $position ) {
			return null;
		}
		$start = strpos( $content, '{', $position + strlen( $marker ) );
		if ( false === $start ) {
			return null;
		}
		$depth     = 0;
		$in_string = false;
		$length    = strlen( $content );
		for ( $i = $start; $i < $length; $i++ ) {
			$char = $content[ $i ];
			if ( $in_string ) {
				if ( '\\' === $char ) {
					++$i; // Skip the escaped character.
				} elseif ( '"' === $char ) {
					$in_string = false;
				}
			} elseif ( '"' === $char ) {
				$in_string = true;
			} elseif ( '{' === $char ) {
				++$depth;
			} elseif ( '}' === $char ) {
				--$depth;
				if ( 0 === $depth ) {
					return substr( $content, $start, $i - $start + 1 );
				}
			}
		}
		return null;
	}
}
