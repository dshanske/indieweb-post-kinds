<?php
/**
 * Base class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for the format parsers.
 *
 * Provides date ordering for feed items and sanitizers for values taken from
 * remote documents. limit_string() is derived from the Press This code that
 * was removed from WordPress core.
 *
 * @since 1.0.0
 */
class Base {

	/**
	 * Sorts items newest first by a date field.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $items List of jf2 items.
	 * @param string $field Optional. Date property to sort by. Default 'published'.
	 * @return array|null The sorted, reindexed items, or null if $items is empty or
	 *                    its first item has no string value for $field.
	 */
	protected static function order_by_date( $items, $field = 'published' ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return null;
		}
		$items = array_values( $items );
		// If the first entry does not have this field return.
		if ( ! is_array( $items[0] ) || ! array_key_exists( $field, $items[0] ) ) {
			return null;
		}
		if ( ! is_string( $items[0][ $field ] ) ) {
			return null;
		}
		usort(
			$items,
			function ( $a, $b ) use ( $field ) {
				return ( strtotime( $b[ $field ] ) - strtotime( $a[ $field ] ) );
			}
		);
		return $items;
	}

	/**
	 * Returns the most recent published date in a list of items.
	 *
	 * @since 2.0.0
	 *
	 * @param array $items List of jf2 items.
	 * @return string|null The date in W3C format, or null if no items have a
	 *                     parseable published date.
	 */
	protected static function find_last_published( $items ) {
		$items = self::order_by_date( $items, 'published' );
		if ( ! $items ) {
			return null;
		}
		try {
			$return = new \DateTime( $items[0]['published'], wp_timezone() );
		} catch ( \Exception $e ) {
			return null;
		}
		return $return->format( DATE_W3C );
	}

	/**
	 * Validates an email address.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email Email address to check.
	 * @return string|false The address if valid, false otherwise.
	 */
	public static function validate_email( $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Returns the most recent updated date in a list of items.
	 *
	 * @since 2.0.0
	 *
	 * @param array $items List of jf2 items.
	 * @return string|null The date in W3C format, or null if no items have a
	 *                     parseable updated date.
	 */
	protected static function find_last_updated( $items ) {
		$items = self::order_by_date( $items, 'updated' );
		if ( ! $items ) {
			return null;
		}
		try {
			$return = new \DateTime( $items[0]['updated'], wp_timezone() );
		} catch ( \Exception $e ) {
			return null;
		}
		return $return->format( DATE_W3C );
	}

	/**
	 * Sanitizes a string and limits it to 5,000 characters.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to limit.
	 * @return bool|int|float|string Numbers and booleans unchanged; strings trimmed,
	 *                               truncated to 5,000 characters and passed through
	 *                               sanitize_text_field(); an empty string for any
	 *                               other type.
	 */
	protected static function limit_string( $value ) {
		$return = '';
		if ( is_numeric( $value ) || is_bool( $value ) ) {
			$return = $value;
		} elseif ( is_string( $value ) ) {
			if ( mb_strlen( $value ) > 5000 ) {
				$return = mb_substr( $value, 0, 5000 );
			} else {
				$return = $value;
			}
			$return = sanitize_text_field( trim( $return ) );
		}

		return $return;
	}

	/**
	 * Adds a value to an array key, turning the key into a list on repeats.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $array Array to add to.
	 * @param string $key   Key to set.
	 * @param mixed  $value Value to add.
	 * @return array The updated array.
	 */
	public static function set( $array, $key, $value ) {
		if ( ! isset( $array[ $key ] ) ) {
			$array[ $key ] = $value;
		} elseif ( is_string( $array[ $key ] ) ) {
			$array[ $key ] = array( $array[ $key ], $value );
		} elseif ( is_array( $array[ $key ] ) ) {
			$array[ $key ][] = $value;
		}
		return $array;
	}
}
