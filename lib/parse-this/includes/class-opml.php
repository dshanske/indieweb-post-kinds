<?php
/**
 * OPML class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches and converts OPML subscription lists.
 *
 * Not currently used anywhere in the plugin.
 *
 * @since 1.0.0
 */
class OPML {

	/**
	 * Downloads an OPML document.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Returns WP_Error for a response other than 2xx.
	 *
	 * @param string $url URL of the OPML file.
	 * @return string|WP_Error The response body, or WP_Error if the URL is invalid,
	 *                         the request fails or the server returns an error.
	 */
	public function fetch( $url ) {
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'parse-this' ) );
		}

		$response = pt_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code > 299 ) {
			return new \WP_Error(
				'http-error',
				/* translators: %d: HTTP response code. */
				sprintf( __( 'The OPML file could not be retrieved (HTTP %d).', 'parse-this' ), $code )
			);
		}
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Converts OPML into a list of outline groups and their feeds.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Returns an empty array for invalid XML instead of a fatal
	 *              error. Values are strings, sanitized, rather than
	 *              SimpleXMLElement objects. Feeds listed outside any group are
	 *              returned in a group with an empty title.
	 *
	 * @param string $content OPML XML.
	 * @return array[] List of groups, each with 'title' and 'children', where each
	 *                 child has 'name' and 'url'. Feeds without an http or https
	 *                 xmlUrl are left out.
	 */
	public function convert( $content ) {
		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			return array();
		}
		// LIBXML_NONET stops the document loading anything over the network.
		$errors = libxml_use_internal_errors( true );
		$xml    = simplexml_load_string( $content, 'SimpleXMLElement', LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $errors );
		if ( false === $xml || ! isset( $xml->body ) ) {
			return array();
		}

		$return = array();
		$loose  = array();
		foreach ( $xml->body->outline as $outline ) {
			// A feed directly in the body, not in a group.
			if ( isset( $outline['xmlUrl'] ) ) {
				$feed = self::outline_to_feed( $outline );
				if ( $feed ) {
					$loose[] = $feed;
				}
				continue;
			}
			$top = array(
				'title'    => sanitize_text_field( (string) ( $outline['title'] ?? $outline['text'] ?? '' ) ),
				'children' => array(),
			);
			foreach ( $outline->outline as $child ) {
				$feed = self::outline_to_feed( $child );
				if ( $feed ) {
					$top['children'][] = $feed;
				}
			}
			$return[] = $top;
		}
		if ( $loose ) {
			$return[] = array(
				'title'    => '',
				'children' => $loose,
			);
		}
		return $return;
	}

	/**
	 * Converts one OPML outline element into a feed.
	 *
	 * @since 2.0.0
	 *
	 * @param \SimpleXMLElement $outline Outline element.
	 * @return array|null Array with 'name' and 'url', or null if the outline has
	 *                    no http or https xmlUrl.
	 */
	private static function outline_to_feed( $outline ) {
		$url = esc_url_raw( (string) ( $outline['xmlUrl'] ?? '' ), array( 'http', 'https' ) );
		if ( '' === $url ) {
			return null;
		}
		return array(
			'name' => sanitize_text_field( (string) ( $outline['title'] ?? $outline['text'] ?? '' ) ),
			'url'  => $url,
		);
	}
}
