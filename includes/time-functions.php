<?php
/**
 * Time Functions
 *
 * Global Scoped Functions for Handling Time.
 *
 * @package Post Kinds
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



// Gets the default offset
function get_default_offset() {
	$tz_seconds = get_option( 'gmt_offset' ) * 3600;
	return tz_seconds_to_offset( $tz_seconds );
}

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

