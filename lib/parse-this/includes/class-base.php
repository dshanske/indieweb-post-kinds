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
 * remote documents. The limit_* sanitizers are derived from the Press This
 * code that was removed from WordPress core.
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
	 * Limits an array to 100 values.
	 *
	 * Originally 50, raised because some sites are very detailed in their meta.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Array to limit.
	 * @return array The first 100 values of $value, or an empty array if $value is
	 *               not an array.
	 */
	protected static function limit_array( $value ) {
		if ( is_array( $value ) ) {
			if ( count( $value ) > 100 ) {
				return array_slice( $value, 0, 100 );
			}

			return $value;
		}

		return array();
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
	 * Validates a URL, resolves it against the source URL, and limits its length.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url        URL to check.
	 * @param string $source_url URL of the document, used to resolve relative URLs.
	 * @return string The absolute URL passed through esc_url_raw() (http and https
	 *                only), or an empty string if it is not a string, longer than
	 *                2,048 characters, or invalid.
	 */
	protected static function limit_url( $url, $source_url ) {
		if ( ! is_string( $url ) ) {
			return '';
		}

		// HTTP 1.1 allows 8000 chars but the "de-facto" standard supported in all current browsers is 2048.
		if ( strlen( $url ) > 2048 ) {
			return ''; // Return empty rather than a truncated/invalid URL.
		}

		// Does not look like a URL.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		$url = pt_make_absolute_url( $url, $source_url );

		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	/**
	 * Filters out image URLs that are unlikely to be content images.
	 *
	 * Excludes ad images, share buttons, loaders and spinners, spacers,
	 * thumbnails, WordPress interface, theme and plugin images, tracking pixels
	 * and the WordPress.com stats image.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src        Image source URL.
	 * @param string $source_url URL of the document, used to resolve relative URLs.
	 * @return string The image URL, or an empty string if it is excluded.
	 */
	protected static function limit_img( $src, $source_url ) {
		$src = self::limit_url( $src, $source_url );

		if ( preg_match( '!/ad[sx]?/!i', $src ) ) {
			// Ads.
			return '';
		} elseif ( preg_match( '!(/share-?this[^.]+?\.[a-z0-9]{3,4})(\?.*)?$!i', $src ) ) {
			// Share-this type button.
			return '';
		} elseif ( preg_match( '!/(spinner|loading|spacer|blank|rss)\.(gif|jpg|png)!i', $src ) ) {
			// Loaders, spinners, spacers.
			return '';
		} elseif ( preg_match( '!/([^./]+[-_])?(spinner|loading|spacer|blank)s?([-_][^./]+)?\.[a-z0-9]{3,4}!i', $src ) ) {
			// Fancy loaders, spinners, spacers.
			return '';
		} elseif ( preg_match( '!([^./]+[-_])?thumb[^.]*\.(gif|jpg|png)$!i', $src ) ) {
			// Thumbnails, too small, usually irrelevant to context.
			return '';
		} elseif ( false !== stripos( $src, '/wp-includes/' ) ) {
			// Classic WordPress interface images.
			return '';
		} elseif ( false !== stripos( $src, '/wp-content/themes' ) ) {
			// Anything within a WordPress theme directory.
			return '';
		} elseif ( false !== stripos( $src, '/wp-content/plugins' ) ) {
			// Anything within a WordPress plugin directory.
			return '';
		} elseif ( preg_match( '![^\d]\d{1,2}x\d+\.(gif|jpg|png)$!i', $src ) ) {
			// Most often tiny buttons/thumbs (< 100px wide).
			return '';
		} elseif ( preg_match( '!/pixel\.(mathtag|quantserve)\.com!i', $src ) ) {
			// Tracking pixels (mathtag.com, quantserve.com); see https://www.quantcast.com/how-we-do-it/iab-standard-measurement/how-we-collect-data/ for details.
			return '';
		} elseif ( preg_match( '!/[gb]\.gif(\?.+)?$!i', $src ) ) {
			// WordPress.com stats gif.
			return '';
		}
		/**
		 * Filters an image URL after the built-in exclusions have been applied.
		 *
		 * Return an empty string to exclude the image.
		 *
		 * @since 1.0.0
		 *
		 * @param string $src Absolute image URL that passed the built-in checks, or an
		 *                    empty string if the URL was invalid.
		 */
		return apply_filters( 'parse_this_img_filters', $src );
	}

	/**
	 * Converts an embed URL into the canonical URL of what it embeds.
	 *
	 * YouTube, Vimeo, Daily Motion and Vine (now defunct) player URLs are
	 * rewritten to their watch-page form. Any other URL is kept only if it
	 * matches a registered oEmbed provider.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src        Embed source URL.
	 * @param string $source_url URL of the document, used to resolve relative URLs.
	 * @return string The canonical URL, or an empty string if no provider matches.
	 */
	protected static function limit_embed( $src, $source_url ) {
		$src = self::limit_url( $src, $source_url );

		if ( empty( $src ) ) {
			return '';
		}

		if ( preg_match( '!//(m|www)\.youtube\.com/(embed|v)/([^?]+)\?.+$!i', $src, $src_matches ) ) {
			// Embedded Youtube videos (www or mobile).
			$src = 'https://www.youtube.com/watch?v=' . $src_matches[3];
		} elseif ( preg_match( '!//player\.vimeo\.com/video/([\d]+)([?/].*)?$!i', $src, $src_matches ) ) {
			// Embedded Vimeo iframe videos.
			$src = 'https://vimeo.com/' . (int) $src_matches[1];
		} elseif ( preg_match( '!//vimeo\.com/moogaloop\.swf\?clip_id=([\d]+)$!i', $src, $src_matches ) ) {
			// Embedded Vimeo Flash videos.
			$src = 'https://vimeo.com/' . (int) $src_matches[1];
		} elseif ( preg_match( '!//vine\.co/v/([^/]+)/embed!i', $src, $src_matches ) ) {
			// Embedded Vine videos. Vine shut down in 2017; kept for old links.
			$src = 'https://vine.co/v/' . $src_matches[1];
		} elseif ( preg_match( '!//(www\.)?dailymotion\.com/embed/video/([^/?]+)([/?].+)?!i', $src, $src_matches ) ) {
			// Embedded Daily Motion videos.
			$src = 'https://www.dailymotion.com/video/' . $src_matches[2];
		} else {
			$oembed = _wp_oembed_get_object();

			if ( ! $oembed->get_provider(
				$src,
				array(
					'discover' => false,
				)
			) ) {
				$src = '';
			}
		}

		return $src;
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
