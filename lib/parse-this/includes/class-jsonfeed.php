<?php
/**
 * JSONFeed class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Converts JSON Feed (versions 1 and 1.1) documents into jf2 feeds.
 *
 * @since 1.0.0
 *
 * @link https://www.jsonfeed.org/version/1.1/
 */
class JSONFeed extends Base {
	/**
	 * Converts JSON Feed author data into jf2 cards.
	 *
	 * Accepts both the version 1 'author' object and the version 1.1 'authors'
	 * list. Entries that are not objects are skipped.
	 *
	 * @since 1.0.0
	 *
	 * @param array $array A feed or item object that may contain author/authors.
	 * @return array|null A single author (name, url, photo), a list of authors, or
	 *                    null if none are present.
	 */
	private static function get_author( $array ) {
		if ( isset( $array['author'] ) && ! isset( $array['authors'] ) ) {
			$array['authors'] = $array['author'];
		}
		if ( ! isset( $array['authors'] ) ) {
			return null;
		}
		$author = $array['authors'];
		$return = array();
		if ( ! is_array( $author ) ) {
			return null;
		}
		if ( ! wp_is_numeric_array( $author ) ) {
			$author = array( $author );
		}
		foreach ( $author as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$card = array_filter(
				array(
					'name'  => $element['name'] ?? null,
					'url'   => $element['url'] ?? null,
					'photo' => $element['avatar'] ?? null,
				)
			);
			if ( $card ) {
				$return[] = array( 'type' => 'card' ) + $card;
			}
		}
		$return = array_values( array_filter( $return ) );
		if ( 1 === count( $return ) ) {
			return $return[0];
		}
		return $return;
	}

	/**
	 * Converts a decoded JSON Feed into a jf2 feed.
	 *
	 * Item attachments are mapped by MIME type to audio, photo or video, and
	 * duration_in_seconds becomes an ISO 8601 duration.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $content Decoded JSON Feed document.
	 * @param string $url     URL the feed was fetched from.
	 * @return array jf2 feed with type 'feed', '_feed_type' => 'jsonfeed', feed-level
	 *               properties, 'items', and the '_last_published'/'_last_updated'
	 *               dates of its items.
	 */
	public static function to_jf2( $content, $url ) {
		$return          = array_filter(
			array(
				'type'       => 'feed',
				'_feed_type' => 'jsonfeed',
				'name'       => $content['title'] ?? null,
				'url'        => $url,
				'summary'    => $content['description'] ?? null,
				'photo'      => $content['icon'] ?? null,
				'author'     => self::get_author( $content ),
				'language'   => $content['language'] ?? null,
			)
		);
		$return['items'] = array();
		$items = ( isset( $content['items'] ) && is_array( $content['items'] ) ) ? $content['items'] : array();
		foreach ( $items as $item ) {
			// Relative URLs in an item are relative to the item, or to the feed when it has no URL.
			$item_url = isset( $item['url'] ) && is_string( $item['url'] ) ? pt_make_absolute_url( $item['url'], $url ) : null;
			$base     = $item_url ? $item_url : $url;
			$absolute = function ( $value ) use ( $base ) {
				return ( is_string( $value ) && '' !== $value ) ? pt_make_absolute_url( $value, $base ) : null;
			};
			$html     = Parser::clean_content( pt_absolute_urls_in_html( $item['content_html'] ?? null, $base ) );
			$text     = $item['content_text'] ?? null;
			if ( ( ! is_string( $text ) || '' === trim( $text ) ) && is_string( $html ) ) {
				// Feeds may give only HTML; jf2 content has text as well.
				$text = trim( wp_strip_all_tags( $html ) );
			}
			$newitem = array_filter(
				array(
					'type'        => 'entry',
					'uid'         => $item['id'] ?? null,
					'url'         => $item_url,
					'in-reply-to' => $absolute( $item['external_url'] ?? null ),
					'name'        => $item['title'] ?? null,
					'content'     => array_filter(
						array(
							'html' => $html,
							'text' => $text,
						)
					),
					'summary'     => $item['summary'] ?? null,
					'featured'    => $absolute( $item['image'] ?? null ),
					'published'   => normalize_iso8601( $item['date_published'] ?? null ),
					'updated'     => normalize_iso8601( $item['date_modified'] ?? null ),
					'author'      => self::get_author( $item ),
					'category'    => $item['tags'] ?? null,
					'language'    => $item['language'] ?? null,
				)
			);
			if ( array_key_exists( 'attachments', $item ) ) {
				foreach ( $item['attachments'] as $attachment ) {
					if ( ! isset( $attachment['mime_type'] ) || ! isset( $attachment['url'] ) ) {
						continue;
					}
					$type = explode( '/', $attachment['mime_type'] );
					$type = array_shift( $type );
					switch ( $type ) {
						case 'audio':
							$newitem['audio'] = $absolute( $attachment['url'] );
							if ( isset( $attachment['duration_in_seconds'] ) ) {
								$newitem['duration'] = seconds_to_iso8601( $attachment['duration_in_seconds'] );
							}
							break;
						case 'image':
							$newitem['photo'] = $absolute( $attachment['url'] );
							break;
						case 'video':
							$newitem['video'] = $absolute( $attachment['url'] );
							if ( isset( $attachment['duration_in_seconds'] ) ) {
								$newitem['duration'] = seconds_to_iso8601( $attachment['duration_in_seconds'] );
							}
							break;
					}
				}
			}
			$newitem['post-type'] = post_type_discovery( $newitem );
			$return['items'][]    = array_filter( $newitem );
		}
		$return['_last_published'] = self::find_last_published( $return['items'] );
		$return['_last_updated']   = self::find_last_updated( $return['items'] );
		return $return;
	}
}
