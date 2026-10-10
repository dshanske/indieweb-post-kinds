<?php
/**
 * Global date and time functions.
 *
 * The generic helpers moved to Kind_Time in 4.0.0; their global names remain
 * here as deprecated wrappers.
 *
 * @package Post_Kinds
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'kind_safe_datetime' ) ) {
	/**
	 * Creates a date from a value without throwing on invalid input.
	 *
	 * Dates come from post meta and Micropub requests, where a malformed value
	 * would otherwise throw an uncaught exception and break the page.
	 *
	 * @since 4.0.0
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
	 * @since 4.0.0
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
	/**
	 * Converts a UTC offset in seconds to an offset string.
	 *
	 * @since 2.3.0
	 *
	 * @param int $seconds Offset in seconds.
	 * @return string Offset such as '-05:00'.
	 */
	function tz_seconds_to_offset( $seconds ) {
		return ( $seconds < 0 ? '-' : '+' ) . sprintf( '%02d:%02d', abs( $seconds / 60 / 60 ), abs( $seconds / 60 ) % 60 );
	}
}

if ( ! function_exists( 'tz_offset_to_seconds' ) ) {
	/**
	 * Converts an offset string to a UTC offset in seconds.
	 *
	 * @since 2.3.0
	 *
	 * @param string $offset Offset such as '-05:00' or '-0500'.
	 * @return int Offset in seconds, or 0 if the string is not an offset.
	 */
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
	/**
	 * Converts a UTC offset in seconds to a time zone.
	 *
	 * @since 3.2.2
	 *
	 * @param int $seconds Offset in seconds.
	 * @return DateTimeZone|false The time zone, or false if it cannot be created.
	 */
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
	/**
	 * Returns a time zone's UTC offset in seconds.
	 *
	 * @since 3.2.2
	 *
	 * @param string $timezone Time zone name or offset string.
	 * @return int|false Offset in seconds, or false if the time zone is not valid.
	 */
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
	 * @since 3.0.0
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
	/**
	 * Returns the site's UTC offset, from its gmt_offset option.
	 *
	 * @since 3.0.0
	 *
	 * @return string Offset such as '-05:00'.
	 */
	function get_default_offset() {
		$tz_seconds = get_option( 'gmt_offset' ) * 3600;
		return tz_seconds_to_offset( $tz_seconds );
	}
}

if ( ! function_exists( 'build_iso8601_time' ) ) {
	/**
	 * Joins a date, time and offset into an ISO 8601 string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $date   Date in Y-m-d format.
	 * @param string $time   Time in H:i:s format.
	 * @param string $offset Offset such as '-05:00'. Empty for the site's offset.
	 * @return string The date and time, or an empty string if both are empty.
	 */
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
	/**
	 * Builds an ISO 8601 duration from its pieces.
	 *
	 * @since 3.0.0
	 *
	 * @param int[] $values Years (Y), months (M), days (D), hours (H), minutes (I) and seconds (S).
	 * @return string The duration, or an empty string if every piece is empty.
	 */
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
	/**
	 * Returns the duration between two dates.
	 *
	 * @since 2.5.0
	 * @since 4.0.0 Also accepts DateTimeInterface objects, such as Kind_Post::get( 'start' ) returns.
	 *
	 * @param DateTimeInterface|string $start_string Start, as a date or a string in Y-m-d\TH:i:sP format.
	 * @param DateTimeInterface|string $end_string   End, as a date or a string in Y-m-d\TH:i:sP format.
	 * @return DateInterval|false The duration, or false if either date is not valid or they are the same.
	 */
	function calculate_duration( $start_string, $end_string ) {
		$dates = array();
		foreach ( array( $start_string, $end_string ) as $value ) {
			if ( is_string( $value ) ) {
				// Only the full format: looser parsing reads stray text, such as a single letter, as a date.
				$value = date_create_immutable_from_format( 'Y-m-d\TH:i:sP', $value );
			}
			if ( ! $value instanceof DateTimeInterface ) {
				return false;
			}
			$dates[] = $value;
		}
		list( $start, $end ) = $dates;
		if ( $start == $end ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Compares the moments, not the objects.
			return false;
		}
		return $start->diff( $end );
	}
}

if ( ! function_exists( 'date_interval_to_iso8601' ) ) {
	/**
	 * Converts a duration to an ISO 8601 duration string.
	 *
	 * @since 3.0.0
	 *
	 * @param \DateInterval $interval The duration.
	 * @return string The duration, such as 'PT3M30S'.
	 */
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

if ( ! function_exists( 'display_formatted_datetime' ) ) {
	/**
	 * Deprecated wrapper; see Kind_Time::display_formatted_datetime().
	 *
	 * @since 3.2.0
	 * @deprecated 4.0.0 Use Kind_Time::display_formatted_datetime(). Will be removed in 5.0.0.
	 *
	 * @param DateTimeInterface|string $date Date, or a string kind_safe_datetime() accepts.
	 * @return string The formatted date and time, or an empty string if the date is not valid.
	 */
	function display_formatted_datetime( $date ) {
		_deprecated_function( __FUNCTION__, '4.0.0', 'Kind_Time::display_formatted_datetime()' );
		return Kind_Time::display_formatted_datetime( $date );
	}
}

if ( ! function_exists( 'divide_datetime' ) ) {
	/**
	 * Deprecated wrapper; see Kind_Time::divide_datetime().
	 *
	 * @since 3.3.4
	 * @deprecated 4.0.0 Use Kind_Time::divide_datetime(). Will be removed in 5.0.0.
	 *
	 * @param DateTimeInterface|string $datetime Date, or a string kind_safe_datetime() accepts.
	 * @return array|false The date's parts. See Kind_Time::divide_datetime().
	 */
	function divide_datetime( $datetime ) {
		_deprecated_function( __FUNCTION__, '4.0.0', 'Kind_Time::divide_datetime()' );
		return Kind_Time::divide_datetime( $datetime );
	}
}

if ( ! function_exists( 'build_datetime' ) ) {
	/**
	 * Deprecated wrapper; see Kind_Time::build_datetime().
	 *
	 * @since 3.3.4
	 * @deprecated 4.0.0 Use Kind_Time::build_datetime(). Will be removed in 5.0.0.
	 *
	 * @param string                   $date   Date in Y-m-d format.
	 * @param string                   $time   Time in H:i or H:i:s format.
	 * @param string|DateTimeZone|null $offset Optional. Offset or time zone. Default the site's time zone.
	 * @return DateTimeImmutable|false The date, or false if it is not valid.
	 */
	function build_datetime( $date, $time, $offset = null ) {
		_deprecated_function( __FUNCTION__, '4.0.0', 'Kind_Time::build_datetime()' );
		return Kind_Time::build_datetime( $date, $time, $offset );
	}
}

if ( ! function_exists( 'get_datetime_offset' ) ) {
	/**
	 * Deprecated wrapper; see Kind_Time::get_datetime_offset().
	 *
	 * @since 3.3.4
	 * @deprecated 4.0.0 Use Kind_Time::get_datetime_offset(). Will be removed in 5.0.0.
	 *
	 * @param DateTimeInterface|null $datetime Optional. Date. Default now, in the site's time zone.
	 * @return string|false The offset, or false if it cannot be read.
	 */
	function get_datetime_offset( $datetime = null ) {
		_deprecated_function( __FUNCTION__, '4.0.0', 'Kind_Time::get_datetime_offset()' );
		return Kind_Time::get_datetime_offset( $datetime );
	}
}

if ( ! function_exists( 'divide_interval' ) ) {
	/**
	 * Deprecated wrapper; see Kind_Time::divide_interval().
	 *
	 * @since 3.3.4
	 * @deprecated 4.0.0 Use Kind_Time::divide_interval(). Will be removed in 5.0.0.
	 *
	 * @param DateInterval|string|null $interval Duration, or an ISO 8601 duration string.
	 * @return int[] The duration's parts. See Kind_Time::divide_interval().
	 */
	function divide_interval( $interval ) {
		_deprecated_function( __FUNCTION__, '4.0.0', 'Kind_Time::divide_interval()' );
		return Kind_Time::divide_interval( $interval );
	}
}

if ( ! function_exists( 'build_interval' ) ) {
	/**
	 * Deprecated wrapper; see Kind_Time::build_interval().
	 *
	 * @since 3.3.4
	 * @deprecated 4.0.0 Use Kind_Time::build_interval(). Will be removed in 5.0.0.
	 *
	 * @param int[] $values Years (Y), months (M), days (D), hours (H), minutes (I) and seconds (S).
	 * @return string The duration, or an empty string if every piece is 0.
	 */
	function build_interval( $values ) {
		_deprecated_function( __FUNCTION__, '4.0.0', 'Kind_Time::build_interval()' );
		return Kind_Time::build_interval( $values );
	}
}
