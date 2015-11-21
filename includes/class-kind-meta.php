<?php
/**
 * Post Kind Metadata Class
 *
 * @package Post Kind
 * Retrieves and Processes the Metadata related to MF2.
 */

/**
 * Class to Manage Kind Meta.
 * @package Post Kinds
 */
class Kind_Meta {
	protected $meta;
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
				$key = str_replace( 'mf2_', '', $key );
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

	/**
	 * Adds additional meta out of an array of properties.
	 *
	 * @param array $raw An array of properties.
	 */
	public function build_meta( $raw ) {
    $raw = apply_filters ( 'kind_build_meta', $raw );
		$kind = get_post_kind_slug( $this->post );
		if ( isset( $raw['url'] ) ) {
			/**
			 * Allows additional changes to the kind data after parsing.
			 *
			 * @param array $raw An array of properties.
			 */
			$map = array_filter( Kind_Taxonomy::get_kind_properties() );
			if ( isset( $kind ) ) {
				if ( array_key_exists( $kind, $map ) ) {
						$this->meta[ $map[ $kind ] ] = $raw['url'];
						unset( $raw['url'] );
				}
			}
		}
		$this->meta['cite'] = array_filter( $raw );
	}

	/**
	 * Gets URL.
	 *
	 * @return string|array Either a string indicating the URL or an array of URLs.
	 */
	public function get_url( ) {
		$kind = get_post_kind_slug( $this->post );
		$map = Kind_Taxonomy::get_kind_properties();
		if ( array_key_exists( 'cite', $this->meta ) ) {
			if ( array_key_exists( 'url', $this->meta['cite'] ) ) {
				return $this->meta['cite']['url'];
			}
		}
		if ( ! $kind ) {
			return false;
		}
		if ( array_key_exists( $kind, $map ) ) {
			if ( array_key_exists( $map[ $kind ], $this->meta ) ) {
				return $this->meta[ $map[ $kind ] ];
			}
		}
		return false;
	}

	/**
	 * Sets URL.
	 *
	 * @param $url string|array Either a string indicating the URL or an array of URLs.
	 */
	public function set_url( $url ) {
		if ( empty( $url ) ) {
			return;
		}
		$kind = get_post_kind_slug( $this->post );
		$map = Kind_Taxonomy::get_kind_properties();
		if ( array_key_exists( $kind, $map ) ) {
			$this->meta[ $map[ $kind ] ] = $url;
		}
		if ( ! array_key_exists( 'cite', $this->meta ) ) {
			$this->meta['url'] = $url;
		}
	}


	/**
	 * Save meta to database.
	 */
	public function save_meta() {
		if ( ! $this->post || ! $this->meta ) {
			return false;
		}
		foreach ( $this->meta as $key => $value ) {
			$key = 'mf2_' . $key;
			update_post_meta( $this->post->ID, $key, $value );
		}
	}

	/**
	 * Return All Meta Stored in the Object.
	 *
	 * return array $meta All Mf2 meta.
	 */
	public function get_all_meta() {
		return ifset( $this->meta );
	}

	/**
	 * Returns the post associated with the meta object.
	 *
	 * return WP_Post $post
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Return Appropriate Meta Stored in the Object.
	 *
	 * return array $meta All Mf2 meta.
	 */
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
			return;
		}
		$map = Kind_Taxonomy::get_kind_properties();
		if ( ! array_key_exists( 'url', $response ) ) {
			if ( array_key_exists( $kind, $map ) ) {
				if ( array_key_exists( $map[ $kind ], $this->meta ) ) {
					$response['url'] = $this->meta[ $map[ $kind ] ];
				}
			}
		}
		return array_filter( $response );
	}

	/**
	 * Return the Information on the Author.
	 *
	 * return array $author Data on Author.
	 */
	public function get_author() {
		if ( isset( $this->meta['author'] ) ) {
			return $this->meta['author'];
		}
		if ( isset( $this->meta['cite']['author'] ) ) {
			return $this->meta['cite']['author'];
		}
		return false;
	}

	/**
	 * Return a specific meta key.
	 *
	 * return $string An arbitray key.
	 */
	public function get( $key ) {
		return ifset( $this->meta[ $key ] );
	}

} // End Class
