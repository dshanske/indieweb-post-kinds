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
		$kind = get_post_kind_slug( $this->post );
		if ( isset( $raw['url'] ) ) {
//			$body = self::fetch( $raw['url'] );
//			$data = self::parse( $body, $raw['url'] );
			$data = array_filter( $raw );
			/**
			 * Allows additional changes to the kind data after parsing.
			 *
			 * @param array $data An array of properties.
			 */

			$data = apply_filters ( 'kind_build_meta', $data );
			$map = Kind_Taxonomy::get_kind_properties();
			if ( ! isset( $kind ) ) {
				if ( array_key_exists( $kind, $map ) ) {
						$this->meta[ $map[ $kind ] ] = $data['url'];
						unset( $data['url'] );
				}
			}
			$this->meta['cite'] = array_filter( $data );
		}
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
	 * Retrieves the body of a URL for parsing.
	 *
	 * @param string $url A valid URL.
	 */
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

	/**
	 * Parses marked up HTML.
	 *
	 * @param string $content HTML marked up content.
	 */
	private function parse ($content, $url) {
		$ogpdata = self::ogpparse( $content );
		$mf2data = self::mf2parse( $content, $url);
		$data = array_merge( $ogpdata, $mf2data );
		$data =  array_filter( $data );
		/**
		 * Parse additionally by plugin.
		 *
		 * @param array $data An array of properties.
		 * @param string $content The content of the retrieved page.
		 */
		return apply_filters ( 'kind_parse_data', $data, $content );
	}

  /**
   * Parses marked up HTML using MF2.
   *
   * @param string $content HTML marked up content.
   */
  private function mf2parse($content, $url) {
		$data = array();
		$parsed = Mf2\parse($content, $url);
		if(mf2_cleaner::isMicroformatCollection($parsed)) {
      $entries = mf2_cleaner::findMicroformatsByType($parsed, 'h-entry');
			if($entries) {
				$entry = $entries[0];
        if(mf2_cleaner::isMicroformat($entry)) {
        	foreach($entry['properties'] as $key => $value) {
           	$data[$key] = mf2_cleaner::getPlaintext($entry, $key);
          }
					$data['published'] = mf2_cleaner::getPublished($entry);
					$data['updated'] = mf2_cleaner::getUpdated($entry);
				  $data['name'] = mf2_cleaner::getPlaintext($entry, 'name');
  //        $data['content'] = mf2_cleaner::getHtml($entry, 'content');
	//				$data['summary'] = mf2_cleaner::getHtml($entry, 'summary');
						// Temporary measure till next version
					  $data['content'] = mf2_cleaner::getPlaintext($entry, 'summary');

          $data['name'] = trim(preg_replace('/https?:\/\/([^ ]+|$)/', '', $data['name']));
					$author = mf2_cleaner::getAuthor($entry);
         	if ($author) {
							$data['author']=array();
							foreach($author['properties'] as $key => $value) {
								$data['author'][$key] = mf2_cleaner::getPlaintext($author, $key);
							}
							$data['author']=array_filter($data['author']);
          }
				}
			}		
		}
		return array_filter( $data );
	}

	/**
	 * Parses marked up HTML using OGP.
	 *
	 * @param string $content HTML marked up content.
	 */
	private function ogpparse($content) {
		$meta = \ogp\Parser::parse( $content );
		$data = array();
		$data['name'] = ifset( $meta['og:title'] ) ?: ifset( $meta['twitter:title'] ) ?: ifset( $meta['og:music:song'] );
//    $data['summary'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] );
		$data['content'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] );
		$data['site'] = ifset( $meta['og:site'] ) ?: ifset( $meta['twitter:site'] );
		$data['featured'] = ifset( $meta['og:image'] ) ?: ifset( $meta['twitter:image'] );
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
		$data['tags'] = ifset($data['tags']) ?: implode( ',' ,$tags );
		// Extended Parameters
		$data['audio'] = ifset( $meta['og:audio'] );
		$data['video'] = ifset( $meta['og:video'] );
		$data['duration'] = ifset( $meta['music:duration'] ) ?: ifset( $meta['video:duration'] );
		$data['type'] = ifset( $meta['og:type'] );

		return array_filter( $data );
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
