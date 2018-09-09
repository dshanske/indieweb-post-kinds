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
		$this->ID          = $post->ID;
		$this->post_author = $post->post_author;
		$this->post_parent = $post->post_parent;
		$this->published   = mysql2date( DATE_W3C, $post->post_date );
		$this->updated     = mysql2date( DATE_W3C, $post->post_modified );
		$this->content     = $post->post_content;
		$this->summary     = $post->post_excerpt;
		$this->mf2         = $this->get_mf2meta();
		$this->url         = get_permalink( $this->ID );
		$this->name        = $post->post_name;
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
		$this->kind = self::get_post_kind();
	}

	private function get_post_kind() {
		if ( function_exists( 'get_post_kind_slug' ) ) {
			return get_post_kind_slug( $this->ID );
		} else {
			$mf2 = array( 
				'type' => array( 'h-entry' ),
				'properties' => $this->mf2
			);
			return Parse_This_MF2::post_type_discovery( $mf2 );
		}
	}

	public static function get_post() {
		return get_post( $this->ID );
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
		return wp_kses( $value, $allowed );
	}

	public static function sanitize_text( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_text' ), $value );
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
					$new['card']         = array();
					$new['card']['name'] = $response['author'];
					if ( ! empty( $response['icon'] ) ) {
						$new['card']['photo'] = $response['icon'];
					}
				}
				$new         = array_unique( $new );
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
				// Do not save microput prefixed instructions
				if ( self::str_prefix( $key, 'mp-' ) ) {
					continue;
				}
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
		if ( 'mf2' === $key ) {
			return $this->mf2;
		}
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

	public function has_key( $key ) {
		$keys = array_merge( get_object_vars( $this ), $this->mf2 );
		return isset( $keys[ $key ] );
	}

	private function single_array( $value, $discard = false ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( 1 === count( $value ) ) {
			return array_shift( $value );
		}
		if ( $discard && wp_is_numeric_array( $value ) ) {
			return array_shift( $value );
		}
		if ( self::is_multi_array( $value ) ) {
			return array_map( array( $this, 'single_array' ), $value );
		}
		return $value;
	}

	public function set( $key, $value = null ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $k => $v ) {
				self::set( $k, $v );
			}
		}
		if ( null === $value || empty( $value ) ) {
			return;
		}
		$properties = array_keys( get_object_vars( $this ) );
		unset( $properties['mf2'] );
		if ( ! in_array( $key, $properties, true ) ) {
			update_post_meta( $this->ID, 'mf2_' . $key, $value );
		} else {
			switch ( $key ) {
				case 'url':
					break;
				case 'published':
					$date      = new DateTime( $value );
					$tz_string = get_option( 'timezone_string' );
					if ( empty( $tz_string ) ) {
						$tz_string = 'UTC';
					}
					$date->setTimeZone( new DateTimeZone( $tz_string ) );
					$tz        = $date->getTimezone();
					$post_date = $date->format( 'Y-m-d H:i:s' );
					$date->setTimeZone( new DateTimeZone( 'GMT' ) );
					$post_date_gmt = $date->format( 'Y-m-d H:i:s' );
					wp_update_post(
						array(
							'ID'            => $this->ID,
							'post_date'     => $post_date,
							'post_date_gmt' => $post_date_gmt,
						)
					);
					break;
				case 'updated':
					$date      = new DateTime( $value );
					$tz_string = get_option( 'timezone_string' );
					if ( empty( $tz_string ) ) {
						$tz_string = 'UTC';
					}
					$date->setTimeZone( new DateTimeZone( $tz_string ) );
					$tz            = $date->getTimezone();
					$post_modified = $date->format( 'Y-m-d H:i:s' );
					$date->setTimeZone( new DateTimeZone( 'GMT' ) );
					$post_modified_gmt = $date->format( 'Y-m-d H:i:s' );
					wp_update_post(
						array(
							'ID'                => $this->ID,
							'post_modified'     => $post_modified,
							'post_modified_gmt' => $post_modified_gmt,
						)
					);
					break;
				case 'content':
					$key = 'post_content';
					wp_update_post(
						array(
							'ID' => $this->ID,
							$key => $value,
						)
					);
					break;
				case 'summary':
					$key = 'post_excerpt';
					wp_update_post(
						array(
							'ID' => $this->ID,
							$key => $value,
						)
					);
					break;
				default:
					wp_update_post(
						array(
							'ID' => $this->ID,
							$key => $value,
						)
					);
			}
		}
	}

	public function delete( $key ) {
		delete_post_meta( $this->ID, 'mf2_' . $key );
	}

	public function mf2_to_jf2( $cite ) {
		if ( ! $cite ) {
			return $cite;
		}
		if ( ! is_array( $cite ) ) {
			return $cite;
		}
		if ( ! isset( $cite['properties'] ) ) {
			return $this->single_array( $cite );
		}
		$return = array();
		if ( isset( $cite['type'] ) ) {
			$return['type'] = array_shift( $cite['type'] );
		}
		foreach ( $cite['properties'] as $key => $value ) {
			if ( is_array( $value ) && 1 === count( $value ) && wp_is_numeric_array( $value ) ) {
				$value = array_shift( $value );
				$value = $this->mf2_to_jf2( $value );
			}
			$return[ $key ] = $value;
		}
		return array_filter( $return );
	}

	public function get_single( $value ) {
		if ( is_array( $value ) ) {
			return array_shift( $value );
		}
		return $value;
	}

	public function jf2_to_mf2( $cite, $type = 'h-cite' ) {
		if ( ! $cite || ! is_array( $cite ) | isset( $cite['properties'] ) ) {
			return $cite;
		}
		$return               = array();
		$return['type']       = array( ifset( $cite['type'], $type ) );
		$return['properties'] = array();
		unset( $cite['type'] );
		foreach ( $cite as $key => $value ) {
			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}
			$return['properties'][ $key ] = $value;
		}
		return array_filter( $return );
	}

	public function set_by_kind( $value, $type = 'h-cite' ) {
		if ( ! $this->kind || ! $value ) {
			return false;
		}

		// Find out where to find information
		$property = Kind_Taxonomy::get_kind_info( $this->kind, 'property' );
		if ( is_array( $value ) ) {
			$value = $this->jf2_to_mf2( $value, $type );
		}

		// If the property is not set then exit
		if ( ! $property ) {
			return false;
		}
		$this->set( $property, $value );
	}


	// Retrieve the right property to use for the link preview based on the kind.
	// It will return an array of properties or false if it cannot find what it needs.
	// Also will update old posts with new settings
	public function fetch() {
		if ( ! $this->kind ) {
			return false;
		}
		// Find out where to find information
		$property = Kind_Taxonomy::get_kind_info( $this->kind, 'property' );
		// If the property is not set then exit
		if ( ! $property || ! $this->has_key( $property ) ) {
			return false;
		}
		$return = $this->get( $property );
		if ( wp_is_numeric_array( $return ) ) {
			$return = array_shift( $return );
		}
		// If it is in fact a string it is the pre 2.7.0 format and should be updated
		if ( is_string( $return ) ) {
			if ( $this->has_key( 'cite' ) ) {
				$cite        = array_filter( $this->get( 'cite' ) );
				$cite['url'] = $return;
				$this->set( $property, $cite );
				$this->delete( 'cite' );
				return $cite;
			} else {
				return array( 'url' => $return );
			}
		}
		if ( is_array( $return ) ) {
			return $this->mf2_to_jf2( $return );
		}
		return false;
	}
}
