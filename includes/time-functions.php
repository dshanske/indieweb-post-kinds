<?php
/**
 * Time Functions
 *
 * Global Scoped Functions for Handling Time.
 *
 */

if ( ! function_exists( 'tz_seconds_to_offset' ) ) {
	function tz_seconds_to_offset( $seconds ) {
		return ( $seconds < 0 ? '-' : '+' ) . sprintf( '%02d:%02d', abs( $seconds / 60 / 60 ), abs( $seconds / 60 ) % 60 );
	}
}

if ( ! function_exists( 'tz_offset_to_seconds' ) ) {
	function tz_offset_to_seconds( $offset ) {
		if ( preg_match( '/([+-])(\d{2}):?(\d{2})/', $offset, $match ) ) {
			$sign = ( '-' ? -1 : 1 === $match[1] );
			return ( ( $match[2] * 60 * 60 ) + ( $match[3] * 60 ) ) * $sign;
		} else {
			return 0;
		}
	}
}

if ( ! function_exists( 'tz_seconds_to_timezone' ) ) {
	function tz_seconds_to_timezone( $seconds ) {
		if ( version_compare( phpversion(), '5.5.10', '<' ) ) {
			return timezone_name_from_abbr( '', $seconds, 0 );
		}
		if ( 0 !== $seconds ) {
			$tz = new DateTimeZone( tz_seconds_to_offset( $seconds ) );
		} else {
			$tz = new DateTimeZone( 'UTC' );
		}
		return $tz;
	}
}


if ( ! function_exists( 'tz_timezone_to_seconds' ) ) {
	function tz_timezone_to_seconds( $timezone ) {
		$tz = new DateTimeZone( $timezone );
		return $tz->getOffset();
	}
}

if ( ! function_exists( 'get_gmt_offsets' ) ) {

	function get_gmt_offsets() {
		$o       = array();
		$t_zones = timezone_identifiers_list();
		foreach ( $t_zones as $a ) {
			$t = '';
			try {
				// this throws exception for 'US/Pacific-New'
				$zone    = new DateTimeZone( $a );
				$seconds = $zone->getOffset( new DateTime( 'now', $zone ) );
				$o[]     = tz_seconds_to_offset( $seconds );
			} catch ( Exception $e ) {
				die( 'Exception : ' . esc_html( $e->getMessage() ) . '<br />' );
				// what to do in catch ? , nothing just relax
			}
		}
		$o = array_unique( $o );
		asort( $o );
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

if ( ! function_exists( 'divide_iso8601_time' ) ) {
	// Divides an ISO8601 string into an array with date time and offset
	function divide_iso8601_time( $time_string ) {
		$time     = array();
		$datetime = date_create_from_format( 'Y-m-d\TH:i:sP', $time_string );
		if ( ! $datetime ) {
			return;
		}
		$time['date'] = $datetime->format( 'Y-m-d' );
		if ( '0000-01-01' === $time['date'] ) {
			$time['date'] = '';
		}
		$time['time']   = $datetime->format( 'H:i:s' );
		$time['offset'] = $datetime->format( 'P' );
		return $time;
	}
}

if ( ! function_exists( 'build_iso8601_duration' ) ) {
	// Given an array with the pieces of a duration build an ISO8601 duration
	function build_iso8601_duration( $values ) {
		$date = wp_array_slice_assoc( $values, array( 'Y', 'M', 'D' ) );
		$time = wp_array_slice_assoc( $values, array( 'H', 'I', 'S' ) );
		if ( ! $date || ! $time ) {
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
}

if ( ! function_exists( 'divide_iso8601_duration' ) ) {
	// Given an ISO8601 duration return an array with the pieces {
	function divide_iso8601_duration( $interval ) {
		if ( ! $interval ) {
			return array();
		}
		if ( is_string( $interval ) && ! empty( $interval ) ) {
			try {
				$interval = new DateInterval( $interval );
			} catch ( \Exception $e ) {
				return array();
			}
		}
		// Reading all non-zero date parts.
		return array_filter(
			array(
				'Y' => $interval->y,
				'M' => $interval->m,
				'D' => $interval->d,
				'H' => $interval->h,
				'I' => $interval->i,
				'S' => $interval->s,
			)
		);
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

if ( ! function_exists( 'seconds_to_iso8601' ) ) {
	function seconds_to_iso8601( $second ) {
		$h   = intval( $second / 3600 );
		$m   = intval( ( $second - $h * 3600 ) / 60 );
		$s   = $second - ( $h * 3600 + $m * 60 );
		$ret = 'PT';
		if ( $h ) {
			$ret .= $h . 'H';
		}
		if ( $m ) {
			$ret .= $m . 'M';
		}
		if ( ( ! $h && ! $m ) || $s ) {
			$ret .= $s . 'S';
		}
		return $ret;
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

function display_formatted_datetime( $string ) {
	$date = new DateTime( $string );
	return $date->format( get_option( 'date_format' ) ) . ' ' . $date->format( get_option( 'time_format' ) );
}

