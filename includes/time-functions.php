<?php
/**
 * Time Functions
 *
 * Global Scoped Functions for Handling Time.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'kind_safe_datetime' ) ) {
	/**
	 * Creates a date from a value without throwing on invalid input.
	 *
	 * Dates come from post meta and Micropub requests, where a malformed value
	 * would otherwise throw an uncaught exception and break the page.
	 *
	 * @param DateTimeInterface|string $value     Date, or a date string.
	 * @param DateTimeZone|null        $timezone  Optional. Timezone for strings without one.
	 * @param bool                     $immutable Optional. Whether to return a DateTimeImmutable. Default true.
	 * @return DateTimeImmutable|DateTime|false The date, or false if the value is not a valid date.
	 */
	function kind_safe_datetime( $value, $timezone = null, $immutable = true ) {
		if ( $value instanceof DateTimeInterface ) {
			if ( $immutable ) {
				return $value instanceof DateTimeImmutable ? $value : DateTimeImmutable::createFromMutable( $value );
			}
			return $value instanceof DateTime ? $value : DateTime::createFromImmutable( $value );
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return false;
		}
		try {
			return $immutable ? new DateTimeImmutable( $value, $timezone ) : new DateTime( $value, $timezone );
		} catch ( Exception $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'kind_safe_interval' ) ) {
	/**
	 * Creates a duration from an ISO 8601 duration without throwing on invalid input.
	 *
	 * @param DateInterval|string $value Duration, or an ISO 8601 duration such as PT3M30S.
	 * @return DateInterval|false The duration, or false if the value is not a valid duration.
	 */
	function kind_safe_interval( $value ) {
		if ( $value instanceof DateInterval ) {
			return $value;
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return false;
		}
		try {
			return new DateInterval( $value );
		} catch ( Exception $e ) {
			return false;
		}
	}
}

if ( ! function_exists( 'tz_seconds_to_offset' ) ) {
	function tz_seconds_to_offset( $seconds ) {
		return ( $seconds < 0 ? '-' : '+' ) . sprintf( '%02d:%02d', abs( $seconds / 60 / 60 ), abs( $seconds / 60 ) % 60 );
	}
}

if ( ! function_exists( 'tz_offset_to_seconds' ) ) {
	function tz_offset_to_seconds( $offset ) {
		if ( preg_match( '/([+-])(\d{2}):?(\d{2})/', $offset, $match ) ) {
			$sign = ( '-' === $match[1] ) ? -1 : 1;
			return ( ( $match[2] * 60 * 60 ) + ( $match[3] * 60 ) ) * $sign;
		} else {
			return 0;
		}
	}
}

if ( ! function_exists( 'tz_seconds_to_timezone' ) ) {
	function tz_seconds_to_timezone( $seconds ) {
		if ( 0 !== $seconds ) {
			$tz = timezone_open( tz_seconds_to_offset( $seconds ) );
		} else {
			$tz = timezone_open( 'UTC' );
		}
		return $tz;
	}
}


if ( ! function_exists( 'tz_timezone_to_seconds' ) ) {
	function tz_timezone_to_seconds( $timezone ) {
		$tz = timezone_open( $timezone );
		if ( $tz ) {
			return $tz->getOffset();
		}
		return false;
	}
}

if ( ! function_exists( 'get_gmt_offsets' ) ) {

	/**
	 * Returns the UTC offsets currently in use by any time zone, sorted.
	 *
	 * The list is built from every time zone, so it is kept in the object
	 * cache for the day: for the request, and across requests when the cache
	 * is persistent. Daylight saving changes it at most a few times a year.
	 *
	 * @return string[] Offsets such as '-05:00'.
	 */
	function get_gmt_offsets() {
		$key = 'gmt_offsets_' . gmdate( 'Y-m-d' );
		$o   = wp_cache_get( $key, 'post-kinds' );
		if ( is_array( $o ) ) {
			return $o;
		}
		$o       = array();
		$t_zones = timezone_identifiers_list();
		foreach ( $t_zones as $a ) {
			try {
				// Some identifiers, such as 'US/Pacific-New', throw on some PHP versions.
				$zone    = new DateTimeZone( $a );
				$seconds = $zone->getOffset( new DateTime( 'now', $zone ) );
				$o[]     = tz_seconds_to_offset( $seconds );
			} catch ( Exception $e ) {
				// Skip the zone rather than fail.
				continue;
			}
		}
		$o = array_unique( $o );
		asort( $o );
		wp_cache_set( $key, $o, 'post-kinds', DAY_IN_SECONDS );
		return $o;
	}
}

if ( ! function_exists( 'get_default_offset' ) ) {
	// Gets the default offset
	function get_default_offset() {
		$tz_seconds = get_option( 'gmt_offset' ) * 3600;
		return tz_seconds_to_offset( $tz_seconds );
	}
}

if ( ! function_exists( 'build_iso8601_time' ) ) {
	// Turns individual pieces of a date and time into a single ISO8601 string
	function build_iso8601_time( $date, $time, $offset ) {
		if ( empty( $date ) && empty( $time ) ) {
			return '';
		}
		if ( empty( $offset ) ) {
			$offset = get_default_offset();
		}
		return $date . 'T' . $time . $offset;
	}
}

if ( ! function_exists( 'build_iso8601_duration' ) ) {
	// Given an array with the pieces of a duration build an ISO8601 duration
	function build_iso8601_duration( $values ) {
		$values = array_filter( $values );
		if ( empty( $values ) ) {
			return '';
		}

		$date = wp_array_slice_assoc( $values, array( 'Y', 'M', 'D' ) );
		$time = wp_array_slice_assoc( $values, array( 'H', 'I', 'S' ) );
		if ( ! $date && ! $time ) {
			return '';
		}
		$spec = 'P';
		// Adding each part to the spec-string.
		if ( count( $date ) > 0 ) {
			foreach ( $date as $key => $value ) {
				$spec .= $value . $key;
			}
		}
		if ( count( $time ) > 0 ) {
			$spec .= 'T';
			foreach ( $time as $key => $value ) {
				if ( 'I' === $key ) {
					$spec .= $value . 'M';
				} else {
					$spec .= $value . $key;
				}
			}
		}
		return $spec;
	}
}

if ( ! function_exists( 'calculate_duration' ) ) {
	// Given two ISO8601 time strings return a DateInterval Object
	function calculate_duration( $start_string, $end_string ) {
		$start = array();
		$end   = array();
		if ( ! is_string( $start_string ) || ! is_string( $end_string ) ) {
			return false;
		}
		if ( $start_string === $end_string ) {
			return false;
		}
		$start = date_create_from_format( 'Y-m-d\TH:i:sP', $start_string );
		$end   = date_create_from_format( 'Y-m-d\TH:i:sP', $end_string );
		if ( ( $start instanceof DateTime ) && ( $end instanceof DateTime ) ) {
			$duration = $start->diff( $end );
			return $duration;
		}
		return false;
	}
}

if ( ! function_exists( 'date_interval_to_iso8601' ) ) {

	// Return a date interval as an ISO8601 string
	function date_interval_to_iso8601( \DateInterval $interval ) {
		// Reading all non-zero date parts.
		$date = array_filter(
			array(
				'Y' => $interval->y,
				'M' => $interval->m,
				'D' => $interval->d,
			)
		);
		// Reading all non-zero time parts.
		$time = array_filter(
			array(
				'H' => $interval->h,
				'M' => $interval->i,
				'S' => $interval->s,
			)
		);
		$spec = 'P';
		// Adding each part to the spec-string.
		foreach ( $date as $key => $value ) {
			$spec .= $value . $key;
		}
		if ( count( $time ) > 0 ) {
			$spec .= 'T';
			foreach ( $time as $key => $value ) {
				$spec .= $value . $key;
			}
		}
		return $spec;
	}
}

function display_formatted_datetime( $date ) {
	$date = kind_safe_datetime( $date );
	if ( ! $date ) {
		return '';
	}

	return $date->format( get_option( 'date_format' ) ) . ' ' . $date->format( get_option( 'time_format' ) );
}


/**
 * Function to divide a datetime into an array for use in a field
 *
 * @access public
 *
 * @param DateTime $datetime
 * @return array {
 *  @type string $date Date in Y-m-d format.
 *  @type string $time Time in H:i:s format.
 *  @type DateTimeZone $timezone Timezone object.
 * }
 */
function divide_datetime( $datetime ) {
	if ( ! $datetime ) {
		return false;
	}

	$datetime = kind_safe_datetime( $datetime );
	if ( ! $datetime ) {
		return false;
	}

	$time         = array();
	$time['date'] = $datetime->format( 'Y-m-d' );
	if ( '0000-01-01' === $time['date'] ) {
		$time['date'] = '';
	}
	$time['time']   = $datetime->format( 'H:i:s' );
	$time['offset'] = get_datetime_offset( $datetime );
	$time['class']  = get_class( $datetime );
	return array_filter( $time );
}

/**
 * Function to build a datetime from individual pieces
 *
 * @access public
 *
 * @param string $date Date in Y-m-d format.
 * @param string $time Time in H:i:s format.
 * @param DateTimeZone $timezone Timezone object.
 *
 * @return DateTimeImmutable|false DateTime object or false if not valid
 */
function build_datetime( $date, $time, $offset = null ) {
	if ( empty( $date ) || empty( $time ) ) {
		return false;
	}
	$timezone = false;
	if ( is_string( $offset ) && '' !== $offset ) {
		// timezone_open() warns on invalid input; it then returns false.
		$timezone = @timezone_open( $offset ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	} elseif ( $offset instanceof DateTimeZone ) {
		$timezone = $offset;
	}
	if ( ! $timezone ) {
		$timezone = wp_timezone();
	}
	// The time may be H:i or H:i:s; the offset is applied as the timezone.
	return kind_safe_datetime( $date . 'T' . $time, $timezone );
}

/**
 * Return a formatted offset from a datetime object
 *
 * @access public
 *
 * @param DateTime $datetime DateTime object or if not passed set to now and site timezone
 *
 * @return string|false Formatted offset or false if not valid
 */
function get_datetime_offset( $datetime = null ) {
	if ( ! $datetime ) {
		$datetime = new DateTimeImmutable( 'now', wp_timezone() );
	}
	$seconds = $datetime->getOffset();
	if ( false === $seconds ) {
		return false;
	}
	return ( $seconds < 0 ? '-' : '+' ) . sprintf( '%02d:%02d', abs( $seconds / 60 / 60 ), abs( $seconds / 60 ) % 60 );
}


// Given an ISO8601 duration return an array with the piece otherwise 0 duration.
function divide_interval( $interval ) {
	$default = array(
		'Y' => 0,
		'M' => 0,
		'D' => 0,
		'H' => 0,
		'I' => 0,
		'S' => 0,
	);
	if ( ! $interval ) {
		return $default;
	}
	if ( is_string( $interval ) && ! empty( $interval ) ) {
		try {
			$interval = new DateInterval( $interval );
		} catch ( \Exception $e ) {
			return $default;
		}
	}
	// Reading all non-zero date parts.
	$return = array(
		'Y' => $interval->y,
		'M' => $interval->m,
		'D' => $interval->d,
		'H' => $interval->h,
		'I' => $interval->i,
		'S' => $interval->s,
	);
	return wp_parse_args( $return, $default );
}


// Given an array with the pieces of a duration build an ISO8601 duration
function build_interval( $values ) {
	$date = wp_array_slice_assoc( $values, array( 'Y', 'M', 'D' ) );
	$time = wp_array_slice_assoc( $values, array( 'H', 'I', 'S' ) );
	$date = array_filter( $date );
	$time = array_filter( $time );
	if ( ! $date && ! $time ) {
		return '';
	}
	$spec = 'P';
	// Adding each part to the spec-string.
	foreach ( $date as $key => $value ) {
		$spec .= $value . $key;
	}
	if ( count( $time ) > 0 ) {
		$spec .= 'T';
		foreach ( $time as $key => $value ) {
			if ( 'I' === $key ) {
				$spec .= $value . 'M';
			} else {
				$spec .= $value . $key;
			}
		}
	}
	return $spec;
}
