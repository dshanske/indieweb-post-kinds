<?php
/**
 * Post Kind Metadata Class
 *
 * @package Post Kind
 * Retrieves and Processes the Metadata related to MF2.
 */

/**
 * Class to Manage Kind Meta.
 *
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
	 * Is String a URL.
	 *
	 * @param  string $url A string.
	 * @return boolean Whether string is a URL.
	 */
	public static function is_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
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
			if ( ! self::str_prefix( $key, 'mf2_' ) ) {
				unset( $meta[ $key ] );
			} else {
				unset( $meta[ $key ] );
				$key = str_replace( 'mf2_', '', $key );
				$value = array_map( 'maybe_unserialize', $value );
				$value = array_shift( $value );
				// If value is a multi-array with only one element.
				if ( self::is_multi_array( $value ) ) {
					if ( 1 === count( $value ) ) {
						$value = array_shift( $value );
					}
					if ( isset( $value['card'] ) ) {
						if ( self::is_multi_array( $value['card'] ) ) {
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

	public static function sanitize_content( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$allowed = wp_kses_allowed_html( 'post' );
		if ( 1 == get_option( 'kind_protection' ) ) {
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
	 * Adds additional meta out of an array of properties.
	 *
	 * @param array $raw An array of properties.
	 */
	public function build_meta( $raw ) {
		$raw = apply_filters( 'kind_build_meta', $raw );
		$kind = get_post_kind_slug( $this->post );
		$property = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		if ( isset( $raw['url'] ) ) {
			/**
			 * Allows additional changes to the kind data after parsing.
			 *
			 * @param array $raw An array of properties.
			 */
			if ( isset( $kind ) ) {
				if ( ! empty( $property ) ) {
					$this->meta[ $property ] = $raw['url'];
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
		if ( ! isset( $this->meta ) ) {
			return false;
		}
		$kind = get_post_kind_slug( $this->post );
		$property = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		if ( array_key_exists( 'cite', $this->meta ) ) {
			if ( array_key_exists( 'url', $this->meta['cite'] ) ) {
				return $this->meta['cite']['url'];
			}
		}
		if ( ! $kind ) {
			return false;
		}
		if ( ! empty( $property ) ) {
			if ( array_key_exists( $property, $this->meta ) ) {
				if ( is_string( $this->meta[ $property ] ) ) {
					return $this->meta[ $property ];
				}
				if ( is_array( $this->meta[ $property ] ) ) {
					if ( isset( $this->meta[ $property ][0] ) ) {
						return $this->meta[ $property ][0];
					}
				}
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
		$url = self::sanitize_text( $url );
		$kind = get_post_kind_slug( $this->post );
		$property = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		if ( ! empty( $property ) ) {
			$this->meta[ $property ] = array( $url );
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
			if ( ! empty( $value ) ) {
				update_post_meta( $this->post->ID, $key, $value );
			} else {
				delete_post_meta( $this->post->ID, $key );
			}
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
	 * Return Appropriate Cite Stored in the Object.
	 *
	 * return array $meta Return cite.
	 */
	public function get_cite() {
		if ( array_key_exists( 'cite', $this->meta ) ) {
			return $this->meta['cite'];
		}
		return false;
	}

	/**
	 * Return the Information on the Author.
	 *
	 * return array $author Data on Author.
	 */
	public function get_author() {
		if ( ! isset( $this->meta ) ) {
			return false;
		}
		if ( isset( $this->meta['author'] ) ) {
			return $this->meta['author'];
		}
		if ( isset( $this->meta['cite']['author'] ) ) {
			return $this->meta['cite']['author'];
		}
		return false;
	}

	/**
	 * Return the Information on the Author.
	 *
	 * array $author Data on Author.
	 */
	public function set_author($author) {
		if ( ! isset( $author ) ) {
			return false;
		}
		if ( is_array( $author ) ) {
			$author = array_map( array( 'Kind_Meta', 'sanitize_text' ), $author );
			$author = array_filter( $author );
		}
		if ( ! isset( $this->meta['cite'] ) ) {
			$this->meta['cite'] = array();
		}
		$author = array_filter( array_diff( $author, array( '' ) ) );

		$this->meta['cite']['author'] = $author;
	}

	public function set_cite($cite) {
		if ( ! $cite ) {
			return false;
		}
		$cite = array_map( array( 'Kind_Meta', 'sanitize_text' ), $cite );

		if ( isset( $cite['summary'] ) ) {
				$cite['summary'] = self::sanitize_content( $cite['summary'] );
		}
		if ( isset( $cite['content'] ) ) {
				$cite['content'] = self::sanitize_content( $cite['content'] );
		}
		$cite = array_filter( $cite );
		$this->meta['cite'] = $cite;
	}

	public function build_time($date, $time, $offset) {
		if ( empty( $date ) ) {
			$date = '0000-01-01';
		}
		if ( empty( $time ) ) {
			$time = '00:00:00';
		}
		return $date . 'T' . $time . $offset;
	}

	public static function DateIntervalToString(\DateInterval $interval) {
		// Reading all non-zero date parts.
		$date = array_filter(array(
			'Y' => $interval->y,
			'M' => $interval->m,
			'D' => $interval->d,
		));

		// Reading all non-zero time parts.
		$time = array_filter(array(
			'H' => $interval->h,
			'M' => $interval->i,
			'S' => $interval->s,
		));

		$specString = 'P';

		// Adding each part to the spec-string.
		foreach ( $date as $key => $value ) {
			$specString .= $value . $key;
		}
		if ( count( $time ) > 0 ) {
			$specString .= 'T';
			foreach ( $time as $key => $value ) {
				$specString .= $value . $key;
			}
		}
		return $specString;
	}

	public function get_duration() {
		if ( array_key_exists( 'duration', $this->meta ) ) {
			return $this->meta['duration'];
		}
		if ( array_key_exists( 'dt-start', $this->meta ) && array_key_exists( 'dt-end', $this->meta ) ) {
			return $this->calculate_duration( $this->meta['dt-start'], $this->meta['dt-end'] );
		}
		return false;
	}

	public function calculate_duration( $start_string, $end_string ) {
		$start = array();
		$end = array();
		if ( ! is_string( $start_string ) || ! is_string( $end_string ) ) {
			return false;
		}
		if ( $start_string == $end_string ) {
			return false;
		}
		$start = date_create_from_format( 'Y-m-d\TH:i:sP', $start_string );
		$end = date_create_from_format( 'Y-m-d\TH:i:sP', $end_string );
		if ( ($start instanceof DateTime) && ($end instanceof DateTime)  ) {
			$duration = $start->diff( $end );
			return self::DateIntervalToString( $duration );
		}
		return false;
	}

	public function divide_time( $time_string ) {
		$time = array();
		$datetime = date_create_from_format( 'Y-m-d\TH:i:sP', $time_string );
		if ( ! $datetime ) {
			return;
		}
		$time['date'] = $datetime->format( 'Y-m-d' );
		if ( '0000-01-01' == $time['date'] ) {
			$time['date'] = '';
		}
		$time['time'] = $datetime->format( 'H:i:s' );
		$time['offset'] = $datetime->format( 'P' );
		return $time;
	}


	/**
	 * Return a specific meta key.
	 *
	 * return $string An arbitray key.
	 */
	public function get( $key ) {
		return ifset( $this->meta[ $key ] );
	}

	public function set( $key, $value) {
		$this->meta[ $key ] = self::sanitize_text( $value );
	}

	public function del( $key ) {
		delete_post_meta( $this->post->ID, 'mf2_' . $key );
	}

} // End Class
