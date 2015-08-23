<?php
/**
 * Post Kind Metadata Class
 *
 * Retrieves and Processes the Metadata related to MF2
 */

class Kind_Meta {
	protected $meta; // Raw Meta Data
	protected $post;
	public function __construct( $post ) {
		$this->post = get_post( $post );
		if ( ! $this->post ) {
			return false;
		}
		$this->get_mf2meta( $this->post->ID );
	}

	/**
	 * Sets an array with only the mf2 prefixed meta.
	 *
	 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
	 */
	private function get_mf2meta( $post ) {
		$post = get_post( $post );
		$meta = get_post_meta( $post->ID );
		if ( ! $meta ) {
			$this->meta = array();
			return;
		}
		if ( isset( $meta['response'] ) ) {
			$response = maybe_unserialize( $meta['response'] );
			// Retrieve from the old response array and store in new location.
			if ( ! empty( $response ) ) {
				$new = array();
				// Convert to new format and update.
				if ( ! empty( $response['title'] ) ) {
					$new['name'] = $response['title'];
				}
				if ( ! empty( $response['url'] ) ) {
					$new['url'] = $response['url'];
				}
				if ( ! empty( $response['content'] ) ) {
					$new['content'] = $response['content'];
				}
				if ( ! empty( $response['published'] ) ) {
					$new['published'] = $response['published'];
				}
				if ( ! empty( $response['author'] ) ) {
					$new['card'] = array();
					$new['card']['name'] = $response['author'];
					if ( ! empty( $response['icon'] ) ) {
						$new['card']['photo'] = $response['icon'];
					}
				}
				$new = array_unique( $new );
				$new['card'] = array_unique( $new['card'] );
				if ( isset( $new ) ) {
					update_post_meta( $this->post->ID, 'mf2_cite', $new );
					delete_post_meta( $this->post->ID, 'response' );
					$meta['cite'] = $new;
				}
			}
		}
		foreach ( $meta as $key => $value ) {
			if ( ! str_prefix( $key, 'mf2_' ) ) {
				unset( $meta[ $key ] );
			} else {
				unset( $meta[ $key ] );
				$key = trim( $key, 'mf2_' );
				$value = array_map( 'maybe_unserialize', $value );
				$value = array_shift( $value );
				// If value is a multi-array with only one element.
				if ( is_multi_array( $value ) ) {
					if ( 1 === count( $value ) ) {
						$value = array_shift( $value );
					}
					if ( isset( $value['card'] ) ) {
						if ( is_multi_array( $value['card'] ) ) {
							if ( 1 === count( $value['card'] ) ) {
								$value['card'] = array_shift( $value['card'] );
							}
						}
						$value['card'] = array_filter( $value['card'] );
					}
				}
				if ( is_array( $value ) ) {
					$value = array_filter( $value );
				}
				$meta[ $key ] = $value;
			}
		}
		$this->meta = array_filter( $meta );
	}

	public function build_meta( $raw ) {
		$kind = get_post_kind_slug( $this->post );
		if ( isset( $raw['url'] ) ) {
			$body = self::fetch( $raw['url'] );
			$data = self::parse( $body );
			$data = array_merge( $raw, $data );
			$map = Kind_Taxonomy::get_kind_properties();
			if ( array_key_exists( $kind, $map ) ) {
					$this->meta[ $map[ $kind ] ] = $data['url'];
					unset( $data['url'] );
			}
			$this->meta['cite'] = array_filter( $data );
		}
	}

	// Save or Update Meta to Post
	public function save_meta() {
		if ( ! $this->post || ! $this->meta ) {
			return false;
		}
		foreach ( $this->meta as $key => $value ) {
			$key = 'mf2_' . $key;
			update_post_meta( $this->post->ID, $key, $value );
		}
	}

	// Return Body
	private function fetch($url) {
		global $wp_version;
		if ( ! isset( $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.' ) );
		}
		$response = wp_safe_remote_get( $url, array(
			'timeout' => 30,
			// Use an explicit user-agent for Post Kinds
			'user-agent' => 'Post Kinds (WordPress/' . $wp_version . '); ' . get_bloginfo( 'url' ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = wp_remote_retrieve_body( $response );
		return $body;
	}

	private function parse ($content) {
		$data = self::ogpparse( $content );
		return array_filter( $data );
	}

	private function ogpparse($content) {
		$meta = \ogp\Parser::parse( $content );
		$data = array();
		$data['name'] = ifset( $meta['og:title'] ) ?: ifset( $meta['twitter:title'] ) ?: ifset( $meta['og:music:song'] );
		$data['content'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] );
		$data['site'] = ifset( $meta['og:site'] ) ?: ifset( $meta['twitter:site'] );
		$data['image'] = ifset( $meta['og:image'] ) ?: ifset( $meta['twitter:image'] );
		$data['publication'] = ifset( $meta['og:site_name'] ) ?: ifset( $meta['og:music:album'] );
		$data['published'] = ifset( $meta['og:article:published_time'] ) ?: ifset( $meta['og:music:release_date'] ) ?: ifset( $meta['og:video:release_date'] );
		$metatags = ifset( $meta['article:tag'] ) ?: ifset( $meta['og:video:tag'] );
		$tags = array();
		if ( is_array( $metatags ) ) {
			foreach ( $metatags as $tag ) {
				$tags[] = str_replace( ',', ' -', $tag );
			}
			$tags = array_filter( $tags );
		}
		$data['tags'] = $data['tags'] ?: implode( ',' ,$tags );
		// Extended Parameters
		$data['audio'] = ifset( $meta['og:audio'] );
		$data['video'] = ifset( $meta['og:video'] );
		$data['duration'] = ifset( $meta['music:duration'] ) ?: ifset( $meta['video:duration'] );
		$data['type'] = ifset( $meta['og:type'] );

		return array_filter( $data );
	}

	public function get_all_meta() {
		return ifset( $this->meta );
	}

	public function get_post() {
		return $this->post;
	}

	public function get_meta() {
		if ( ! isset( $this->meta ) ) {
			return false;
		}
		if ( array_key_exists( 'cite', $this->meta ) ) {
			$response = $this->meta['cite'];
		} else {
			$response = array();
		}
		$kind = get_post_kind_slug( $this->post );
		if ( ! $kind ) {
			$kind = 'note';
		}
		$map = Kind_Taxonomy::get_kind_properties();
		if ( array_key_exists( $kind, $map ) ) {
			$response['url'] = ifset( $response['url'] ) ?: ifset( $this->meta[ $map[ $kind ] ] );
		}
		return array_filter( $response );
	}

	public function get_author() {
		if ( isset( $this->meta['card'] ) ) {
			return $this->meta['card'];
		}
		if ( isset( $this->meta['author'] ) ) {
			return $this->meta['author'];
		}
		return false;
	}

	public function get( $key ) {
		return ifset( $this->meta[ $key ] );
	}

} // End Class
