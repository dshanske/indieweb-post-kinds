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

	public static function sanitize_content( $value ) {
		global $wpdb;
		$options = get_option( 'iwt_options' );
		$allowed = wp_kses_allowed_html( 'post' );
		if ( array_key_exists( 'contentelements', $options ) && json_decode( $options['contentelements'] ) != null ) {
			$allowed = json_decode( $options['contentelements'], true );
		}
		$charset = $wpdb->get_col_charset( $wpdb->posts, $emoji_field );
		if ( 'utf8' === $charset ) {
			$value = wp_encode_emoji( $value );
		}

		if(ifset($options[ 'protection' ])){
			return $value;
		}
		return wp_kses( ( string ) $value , $allowed );
	}

	public static function sanitize_text( $value ) {
		if ( is_array( $value ) ) {
			return array_map( $value, array( 'Kind_Meta', 'sanitize_text' ) );
		}
		if ( is_url( $value ) ) {
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
		if ( ! isset( $this->meta ) ) {
			return false;
		}
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
		$url = self::sanitize_text( $url );
		$kind = get_post_kind_slug( $this->post );
		$map = array_diff( Kind_Taxonomy::get_kind_properties(), array( '' ) );
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
		$summary = ifset( $cite['summary'] );
		$content = ifset( $cite['content'] );
		$cite = array_map( array( 'Kind_Meta', 'sanitize_text' ), $cite );

		if ( isset( $cite['summary'] ) ) {
				$cite['summary'] = self::sanitize_content( $summary );
		}
		if ( isset( $cite['content'] ) ) {
				$cite['content'] = self::sanitize_content( $content );
		}
		$cite = array_filter( array_diff( $cite, array( '' ) ) );
		$this->meta['cite'] = $cite;
	}

	public function build_time($date, $time, $offset) {
		if ( empty($date) ) {
			return false;
		}
		if (empty($time) ) {
			$time = '00:00:00';
		}
		return $date . 'T' . $time . $offset;
	}

	public static function dateIntervalToString(\DateInterval $interval) {
    // Reading all non-zero date parts.
    $date = array_filter(array(
        'Y' => $interval->y,
        'M' => $interval->m,
        'D' => $interval->d
    ));

    // Reading all non-zero time parts.
    $time = array_filter(array(
        'H' => $interval->h,
        'M' => $interval->i,
        'S' => $interval->s
    ));

    $specString = 'P';

    // Adding each part to the spec-string.
    foreach ($date as $key => $value) {
        $specString .= $value . $key;
    }
    if (count($time) > 0) {
        $specString .= 'T';
        foreach ($time as $key => $value) {
            $specString .= $value . $key;
        }
    }
    return $specString;
	}

	public function get_duration() {
		if ( array_key_exists('duration', $this->meta) ) {
			return $this->meta['duration'];
		}
		$start = array();
		$end = array();
	  if ( array_key_exists('dt-start', $this->meta) ) {
        $start = date_create_from_format("Y-m-d\TH:i:sP", $this->meta['dt-start']);
    }
    else if (! empty ($this->meta['cite']['published']) ) {
        $start = date_create_from_format("Y-m-d\TH:i:sP",$this->meta['cite']['published']);
    }
    if ( ! empty ( $this->meta['dt-end'] ) ) {
        $end = date_create_from_format("Y-m-d\TH:i:sP", $this->meta['dt-end']);
    }
    else if (! empty ($this->meta['cite']['updated']) ) {
        $end = date_create_from_format("Y-m-d\TH:i:sP",$this->meta['cite']['updated']);
    }
		if  ( ($start instanceof DateTime) && ($end instanceof DateTime)  ) {
			$duration = $start->diff($end);
			return self::dateIntervalToString($duration);
		}
		return false;
	}

	public function set_time($dt_start, $dt_end) {
		if ( ! empty( $dt_start ) || $dt_start ) {
			$this->meta['dt-start'] = $dt_start;
		}
		if ( ! empty( $dt_end ) || $dt_end ) {
			$this->meta['dt-end'] = $dt_end;
		}
	}

	public function get_time() {
		$time = array();
		if ( ! empty ( $this->meta['dt-start'] ) ) {
				$start = date_create_from_format("Y-m-d\TH:i:sP", $this->meta['dt-start']);
		}
		else if (! empty ($this->meta['cite']['published']) ) {
				$start = date_create_from_format("Y-m-d\TH:i:sP",$this->meta['cite']['published']);
		}
		if ( isset($start) && $start ) {
			$time['start_date'] = $start->format('Y-m-d');
    	$time['start_time'] = $start->format('H:i:s');
    	$time['start_offset'] = $start->format('P');
		}

    if ( ! empty ( $this->meta['dt-end'] ) ) {
        $end = date_create_from_format("Y-m-d\TH:i:sP", $this->meta['dt-end']);
    }
    else if (! empty ($this->meta['cite']['updated']) ) {
        $end = date_create_from_format("Y-m-d\TH:i:sP",$this->meta['cite']['updated']);
    }
    if ( isset($end) && $end ) {
      $time['end_date'] = $end->format('Y-m-d');
      $time['end_time'] = $end->format('H:i:s');
      $time['end_offset'] = $end->format('P');
    }
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
		$this->meta[$key] = self::sanitize_text( $value );
	}

	public function del( $key ) {
		delete_post_meta( $this->post->ID, 'mf2_' . $key );
	}

} // End Class
