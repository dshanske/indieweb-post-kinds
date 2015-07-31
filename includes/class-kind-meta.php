<?php
/**
 * Post Kind Metadata Class
 *
 * Retrieves and Processes the Metadata related to MF2
 */

class Kind_Meta {
	protected $meta; // Raw Meta Data
	protected $kind = ''; // Actual or Implied Kind
	protected $post;
	public function __construct( $post ) {
		$this->post = get_post( $post );
		if ( ! $this->post ) {
			return false;
		}
		if ( class_exists( 'kind_taxonomy' ) ) {
			$this->kind = get_post_kind_slug( get_post( $this->post->ID ) );
		}
		$this->meta = get_post_mf2meta( $this->post->ID );
	}

	public function build_meta( $raw ) {
		if ( isset( $raw['url'] ) ) {
			$body = self::fetch( $raw['url'] );
			$data = self::parse( $body );
			$data = array_merge( $raw, $data );
			$map = Kind_Taxonomy::get_kind_properties();
			if ( array_key_exists( $this->kind, $map ) ) {
					$this->meta[$map[$this->kind]] = $data['url'];
					unset( $data['url'] );	
			}
			$this->meta['cite'] = array_filter($data);
		}
	}

	// Save or Update Meta to Post
	public function save_meta() {
		if ( WP_DEBUG ) {
			// error_Log( 'Save MF2 Meta: ' . serialize( $this->meta ) );
		}
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
		if ( ! isset( $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.' ) );
		}
		$response = wp_safe_remote_get( $url, array(
			'timeout' => 30,
			// Use an explicit user-agent for Post Kinds
			'user-agent' => 'Post Kinds (WordPress/' . $wp_version . '); ' . get_bloginfo( 'url' )
		) );

		if ( is_wp_error( $response ) ) {
			return false;
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
		$data['name'] = ifset( $meta['og:title'] ) ?: ifset( $meta['twitter:title'] ) ?: ifset( $meta['og:music:song'] ) ;
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
		$data['type'] = ifset ( $meta['og:type'] );

		return array_filter( $data );
	}

	public function get_kind() {
		return ifset( $this->kind );
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
		if (array_key_exists( 'cite', $this->meta ) ) {
			$response = $this->meta['cite'];
		}
		else {
			$response = array();
		}
		$map = Kind_Taxonomy::get_kind_properties();
		if ( array_key_exists( $this->kind, $map ) ) {
			$response['url'] = ifset( $response['url'] ) ?: ifset ( $this->meta[ $map[$this->kind] ] );
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

} // End Class
