<?php
/**
 * MF2 Post Class
  *
   * @package Post Kinds
	* Assists in retrieving/saving microformats 2 properties from a post
	 */
class MF2_Post {
	public $ID;
	public $post_author;
	public $published;
	public $updated;
	public $content;
	public $summary;
	public $post_parent;
	public $kind;
	public $url;
	public $name;
	public $category = array();
	public $featured;
	private $mf2;

	public function __construct( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}
		$this->ID = $post->ID;
		$this->post_author = $post->post_author;
		$this->post_parent = $post->post_parent;
		$this->published = mysql2date( DATE_W3C, $post->post_date );
		$this->updated = mysql2date( DATE_W3C, $post->post_modified );
		$this->content = $post->post_content;
		$this->summary = $post->post_excerpt;
		$this->mf2 = $this->get_mf2meta();
		$this->kind = get_post_kind_slug( $this->ID );
		$this->url = get_permalink( $this->ID );
		$this->name = $post->post_name;
		// Get a list of categories and extract their names
		$post_categories = get_the_terms( $post->ID, 'category' );
		if ( ! empty( $post_categories ) && ! is_wp_error( $post_categories ) ) {
			$this->category = wp_list_pluck( $post_categories, 'name' );
		}

		// Get a list of tags and extract their names
		$post_tags = get_the_terms( $post->ID, 'post_tag' );
		if ( ! empty( $post_tags ) && ! is_wp_error( $post_tags ) ) {
				$this->category = array_merge( $this->category, wp_list_pluck( $post_tags, 'name' ) );
		}
		if ( in_array( 'Uncategorized', $this->category, true ) ) {
			unset( $this->category[ array_search( 'Uncategorized', $this->category, true ) ] );
		}
		if ( has_post_thumbnail( $post ) ) {
			$this->featured = wp_get_attachment_url( get_post_thumbnail_id( $post ), 'full' );
		}
	}


	/**
	 * Is prefix in string.
	 *
	 * @param  string $source The source string.
	 * @param  string $prefix The prefix you wish to check for in source.
	 * @return boolean The result.
	 */
	public static function str_prefix( $source, $prefix ) {
		return strncmp( $source, $prefix, strlen( $prefix ) ) === 0;
	}

	/**
	 * Is String a URL.
	 *
	 * @param  string $url A string.
	 * @return boolean Whether string is a URL.
	 */
	public static function is_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
	}

	/**
	 * Returns True if Array is Multidimensional.
	 *
	 * @param array $arr array.
	 *
	 * @return boolean result
	 */
	public static function is_multi_array( $arr ) {
		if ( count( $arr ) === count( $arr, COUNT_RECURSIVE ) ) {
			return false;
		} else {
			return true;
		}
	}

	public static function sanitize_content( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$allowed = wp_kses_allowed_html( 'post' );
		if ( 1 === (int) get_option( 'kind_protection' ) ) {
			$allowed = json_decode( get_option( 'kind_kses' ), true );
		}
		return wp_kses( $value , $allowed );
	}

	public static function sanitize_text( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( 'Kind_Meta', 'sanitize_text' ), $value );
		}
		if ( self::is_url( $value ) ) {
			$value = esc_url_raw( $value );
		} else {
			$value = esc_attr( $value );
		}
		return $value;
	}

	/**
	 * Retrieve author
	 *
	 * @param  boolean $single Whether to return a a single value or array if the key is single.
	 * @return boolean|string|array The result or false if does not exist.
	 */
	public function get_author( $single ) {
	}

	/**
	 * Sets an array with only the mf2 prefixed meta.
	 *
	 */
	private function get_mf2meta() {
		$meta = get_post_meta( $this->ID );
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
					update_post_meta( $this->ID, 'mf2_cite', $new );
					delete_post_meta( $this->ID, 'response' );
					$meta['cite'] = $new;
				}
			}
		}
		foreach ( $meta as $key => $value ) {
			if ( ! self::str_prefix( $key, 'mf2_' ) ) {
				unset( $meta[ $key ] );
			} else {
				unset( $meta[ $key ] );
				$key = str_replace( 'mf2_', '', $key );
				$value = array_map( 'maybe_unserialize', $value );
				if ( 1 === count( $value ) ) {
					$value = array_shift( $value );
				}
				$meta[ $key ] = $value;
			}
		}
		return array_filter( $meta );
	}

	/**
	 * Retrieve value
	 *
	 * @param  string $key The key to retrieve.
	 * @param  boolean $single Whether to return a a single value or array if there is only one value.
	 * @return boolean|string|array The result or false if does not exist.
	 */
	public function get( $key = null, $single = false ) {
		if ( null === $key ) {
			$return = array_merge( get_object_vars( $this ), $this->mf2 );
			unset( $return['mf2'] );
			return array_filter( $return );
		}
		$properties = array_keys( get_object_vars( $this ) );
		unset( $properties['mf2'] );
		if ( in_array( $key, $properties, true ) ) {
			$return = $this->$key;
		} else {
			if ( ! isset( $this->mf2[ $key ] ) ) {
				return false;
			}
			$return = $this->mf2[ $key ];
		}
		if ( empty( $return ) ) {
			return false;
		}
		if ( is_array( $return ) ) {
			return $single ? $this->single_array( $return ) : $return;
		}
		if ( is_string( $return ) ) {
			return $single ? $return : array( $return );
		}
	}

	private function single_array( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( 1 === count( $value ) ) {
			return array_shift( $value );
		}
		if ( self::is_multi_array( $value ) ) {
			return array_map( array( $this, 'single_array' ), $value );
		}
		return $value;
	}

	public function set( $key, $value ) {
		$properties = array_keys( get_object_vars( $this ) );
		unset( $properties['mf2'] );
		if ( ! in_array( $key, $properties, true ) ) {
			update_post_meta( $this->ID, 'mf2_' . $key, $value );
		} else {
			switch( $key ) {
				case 'published':
					$date = new DateTime( $value );
					$tz_string = get_option( 'timezone_string' );
					if ( empty( $tz_string ) ) {
						$tz_string = 'UTC';
					}
					$date->setTimeZone( new DateTimeZone( $tz_string ) );
					$tz = $date->getTimezone(); 
					$post_date = $date->format( 'Y-m-d H:i:s' );
					$date->setTimeZone( new DateTimeZone( 'GMT' ) );
					$post_date_gmt = $date->format( 'Y-m-d H:i:s' );
					wp_update_post(
						array(
							'ID' => $this->ID,
							'post_date' => $post_date,
							'post_date_gmt' => $post_date_gmt
						)
					);
					break;
				default:
					wp_update_post(
						array(
							'ID' => $this->ID,
							$key => $value
						)
					);
			}
		}
	}

	public function delete( $key ) {
	}
}
