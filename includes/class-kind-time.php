<?php
defined( 'ABSPATH' ) || exit;

/**
 * Date, time and duration helpers for the editor fields and views.
 *
 * These were global functions with generic names, such as build_datetime(),
 * that could collide with other plugins. The global names remain as
 * deprecated wrappers in time-functions.php.
 *
 * @package Post_Kinds
 * @since 4.0.0
 */
class Kind_Time {

	/**
	 * Formats a date and time with the site's date and time formats.
	 *
	 * @since 4.0.0
	 *
	 * @param DateTimeInterface|string $date Date, or a string kind_safe_datetime() accepts.
	 * @return string The formatted date and time, or an empty string if the date is not valid.
	 */
	public static function display_formatted_datetime( $date ) {
		$date = kind_safe_datetime( $date );
		if ( ! $date ) {
			return '';
		}

		return $date->format( get_option( 'date_format' ) ) . ' ' . $date->format( get_option( 'time_format' ) );
	}

	/**
	 * Divides a date and time into the pieces of the editor's date and time fields.
	 *
	 * @since 4.0.0
	 *
	 * @param DateTimeInterface|string $datetime Date, or a string kind_safe_datetime() accepts.
	 * @return array|false {
	 *     Non-empty pieces, or false if the date is not valid.
	 *
	 *     @type string $date   Date in Y-m-d format.
	 *     @type string $time   Time in H:i:s format.
	 *     @type string $offset Offset such as '+02:00'.
	 *     @type string $class  Class of the date object.
	 * }
	 */
	public static function divide_datetime( $datetime ) {
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
		$time['offset'] = self::get_datetime_offset( $datetime );
		$time['class']  = get_class( $datetime );
		return array_filter( $time );
	}

	/**
	 * Builds a date and time from the pieces of the editor's fields.
	 *
	 * @since 4.0.0
	 *
	 * @param string                   $date   Date in Y-m-d format.
	 * @param string                   $time   Time in H:i or H:i:s format.
	 * @param string|DateTimeZone|null $offset Optional. Offset such as '+02:00', or a time zone. Default the site's time zone.
	 * @return DateTimeImmutable|false The date, or false if it is not valid.
	 */
	public static function build_datetime( $date, $time, $offset = null ) {
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
	 * Returns the UTC offset of a date, formatted as '+02:00'.
	 *
	 * @since 4.0.0
	 *
	 * @param DateTimeInterface|null $datetime Optional. Date. Default now, in the site's time zone.
	 * @return string|false The offset, or false if it cannot be read.
	 */
	public static function get_datetime_offset( $datetime = null ) {
		if ( ! $datetime ) {
			$datetime = new DateTimeImmutable( 'now', wp_timezone() );
		}
		$seconds = $datetime->getOffset();
		if ( false === $seconds ) {
			return false;
		}
		return ( $seconds < 0 ? '-' : '+' ) . sprintf( '%02d:%02d', abs( $seconds / 60 / 60 ), abs( $seconds / 60 ) % 60 );
	}

	/**
	 * Divides an ISO 8601 duration into its pieces.
	 *
	 * @since 4.0.0
	 *
	 * @param DateInterval|string|null $interval Duration, or an ISO 8601 duration string.
	 * @return int[] Years (Y), months (M), days (D), hours (H), minutes (I) and seconds (S); all 0 if the duration is not valid.
	 */
	public static function divide_interval( $interval ) {
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

	/**
	 * Builds an ISO 8601 duration from its pieces.
	 *
	 * @since 4.0.0
	 *
	 * @param int[] $values Years (Y), months (M), days (D), hours (H), minutes (I) and seconds (S).
	 * @return string The duration, or an empty string if every piece is 0.
	 */
	public static function build_interval( $values ) {
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
}
