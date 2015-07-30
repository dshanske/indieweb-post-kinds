<?php
/**
 * Post Kind Metadata Class
 *
 * Retrieves and Processes the Metadata related to MF2
 */

class Kind_Meta {
	protected $meta; // Raw Meta Data
	protected $kind = ''; // Actual or Implied Kind
	protected $meta_key = 'cite'; // The primary meta key and default
	protected $post;
	public function __construct( $post ) {
		$this->post = get_post( $post );
		if ( ! $this->post ) {
			return false;
		}
		if ( class_exists( 'kind_taxonomy' ) ) {
			$this->kind = get_post_kind_slug( get_post( $this->post->ID ) );
		}

		$this->meta = get_mf2_meta( $this->post->ID );
		if ( ! $this->meta ) {
			$response = get_post_meta( $this->post->ID, 'response', true );
			// Retrieve from the old response array and store as the first
			// entry in a new multidimensional array
			if ( ! empty( $response ) ) {
				$new = array();
				// Convert to new format and update
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
					$this->meta = array( 'cite' => $new );
				}
			}
		}
	}

	public function build_meta( $raw ) {
		if ( isset( $raw['url'] ) ) {
			$body = self::fetch( $raw['url'] );
			$data = self::parse( $body );
			$data = array_merge( $raw, $data );
			$this->meta['cite'] = $data;
		}
		if ( empty( $this->meta_key ) ) {
			if ( isset( $this->meta['cite'] ) ) {
				$this->meta_key = 'cite';
			}
		}
	}

	// Save or Update Meta to Post
	public function save_meta($post) {
		error_Log( 'Save Test: ' . serialize( $this->meta ) );
		$post = get_post( $post );
		if ( ! $post || ! $this->meta ) {
			return false;
		}
		foreach ( $this->meta as $key => $value ) {
			$key = 'mf2_' . $key;
			update_post_meta( $post->ID, $key, $value );
		}
	}

	// Return Body
	public function fetch($url) {
		if ( ! isset( $url ) ) {
			return false;
		} elseif ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return false;
		}
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$body = wp_remote_retrieve_body( $response );
		return $body;
	}

	public function parse ($content) {
		$data = self::ogpparse( $content );
		return array_filter( $data );
	}

	public function ogpparse($content) {
		$meta = \ogp\Parser::parse( $content );
		$data = array();
			$data['name'] = ifset( $meta['og:title'] ) ?: ifset( $meta['twitter:title'] );
			$data['content'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] );
		$data['site'] = ifset( $meta['og:site'] ) ?: ifset( $meta['twitter:site'] );
		$data['image'] = ifset( $meta['og:image'] ) ?: ifset( $meta['twitter:image'] );
		$data['publication'] = ifset( $meta['og:site_name'] );
		$metatags = ifset( $meta['article:tag'] ) ?: ifset( $meta['og:video:tag'] );
		$tags = array();
		if ( is_array( $metatags ) ) {
			foreach ( $metatags as $tag ) {
				$tags[] = str_replace( ',', ' -', $tag );
			}
			$tags = array_filter( $tags );
		}
		$data['tags'] = $data['tags'] ?: implode( ',' ,$tags );
		return array_filter( $data );
	}

	public function get_kind() {
		if ( isset( $this->kind ) ) {
			return $this->kind;
		}
			return false;
	}

	public function get_all_meta() {
		if ( ! empty( $this->meta ) ) {
			return $this->meta;
		}
		return false;
	}
	public function get_meta() {
		if ( ! isset( $this->meta ) ) {
			return false;
		}
		if ( empty( $this->meta_key ) ) {
			return false;
		}
		$response = $this->meta[ $this->meta_key ];
		if ( ! $response ) {
			return false;
		}
		$response = array_filter_recursive( $response );
		return array_filter( $response );
	}

	public function get_hcard() {
		$m = $this->get_meta();
		if ( isset( $m['card'] ) ) {
			return $m['card'];
		}
		return false;
	}

	public function get( $key ) {
		if ( isset( $this->meta[ $key ] ) ) {
			return $this->meta[ $key ];
		}
		return false;
	}


	/**
	 * maps classes to kinds
	 * courtesy of a similar function in Semantic Linkbacks
	 *
	 * @return array
	 */
	public function get_class_mapper() {
		$class_mapper = array();
		/*
		 * replies
		 * @link http://indiewebcamp.com/replies
		*/
		$class_mapper['in-reply-to'] = 'reply';
		$class_mapper['reply']       = 'reply';
		$class_mapper['reply-of']    = 'reply';
		/*
		 * repost
		 * @link http://indiewebcamp.com/repost
		 */
		$class_mapper['repost']      = 'repost';
		$class_mapper['repost-of']   = 'repost';
		/*
		 * likes
		 * @link http://indiewebcamp.com/likes
		 */
		$class_mapper['like']        = 'like';
		$class_mapper['like-of']     = 'like';
		/*
		 * favorite
		 * @link http://indiewebcamp.com/favorite
		 */
		$class_mapper['favorite']    = 'favorite';
		$class_mapper['favorite-of'] = 'favorite';
		/*
		* bookmark
		* @link http://indiewebcamp.com/bookmark
		*/
		$class_mapper['bookmark']    = 'bookmark';
		$class_mapper['bookmark-of'] = 'bookmark';

		/*
		 * rsvp
		 * @link http://indiewebcamp.com/rsvp
		 */
		$class_mapper['rsvp']        = 'rsvp';
		/*
		 * tag
		 * @link http://indiewebcamp.com/tag
		 */
		$class_mapper['tag-of']      = 'tag';

		$class_mapper['listen']      = 'listen';

		$class_mapper['watch']       = 'watch';
		$class_mapper['play']        = 'play';
		$class_mapper['wish']        = 'wish';

		return apply_filters( 'kind_class_mapper', $class_mapper );
	}

} // End Class
