<?php

class Parse_This_OPML {
	private static function ifset( $key, $array ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : null;
	}


	/**
	 * Downloads the $url and returns the feeds it finds
	 *
	 * @param string $url URL to scan.
	 * @return WP_Error|boolean WP_Error if invalid and true if successful
	 */
	public function fetch( $url ) {
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'indieweb-post-kinds' ) );
		}

		$user_agent = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36 Parse This/WP';
		$args       = array(
			'timeout'             => 15,
			'limit_response_size' => 1048576,
			'redirection'         => 5,
		// Use an explicit user-agent for Parse This
		);
		$links = array();

		$response      = wp_safe_remote_get( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$content_type  = wp_remote_retrieve_header( $response, 'content-type' );

		if ( in_array( $response_code, array( 403, 415 ), true ) ) {
			$args['user-agent'] = $user_agent;
			$response           = wp_safe_remote_get( $url, $args );
			$response_code      = wp_remote_retrieve_response_code( $response );
			if ( in_array( $response_code, array( 403, 415 ), true ) ) {
				return new WP_Error( 'source_error', 'Unable to Retrieve' );
			}
		}

		// Strip any character set off the content type
		$ct = explode( ';', $content_type );
		if ( is_array( $ct ) ) {
			$content_type = array_shift( $ct );
		}
		$content_type = trim( $content_type );

		$content = wp_remote_retrieve_body( $response );
		return $content;
	}

	public function convert( $content ) {
		$xml    = simplexml_load_string( $content );
		$xml    = $xml->body;
		$return = array();
		foreach ( $xml->outline as $outline ) {
			$top = array(
				'title'    => $outline['title'],
				'children' => array(),
			);
			foreach ( $outline as $feed ) {
				$top['children'][] = array(
					'name' => $feed['title'],
					'url'  => $feed['xmlUrl'],
				);
			}
			$return[] = $top;
		}
		return $return;
	}
}



