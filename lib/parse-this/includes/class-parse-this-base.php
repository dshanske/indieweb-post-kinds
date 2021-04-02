<?php
/**
 * Parse This Base class.
 * Originally Derived from the Press This Class with Enhancements.
 */
class Parse_This_Base {
	/**
	 * Utility method to limit an array to 100 values.
	 * Originally set to 50 but some sites are very detailed in their meta.
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @param array $value Array to limit.
	 * @return array Original array if fewer than 100 values, limited array, empty array otherwise.
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
	 * Utility method to limit the length of a given string to 5,000 characters.
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @param string $value String to limit.
	 * @return bool|int|string If boolean or integer, that value. If a string, the original value
	 *                         if fewer than 5,000 characters, a truncated version, otherwise an
	 *                         empty string.
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
	 * Utility method to limit a given URL to 2,048 characters.
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @param string $url URL to check for length and validity.
	 * @param string $source_url URL URL to use to resolve relative URLs
	 * @return string Escaped URL if of valid length (< 2048) and makeup. Empty string otherwise.
	 */
	protected static function limit_url( $url, $source_url ) {
		if ( ! is_string( $url ) ) {
			return '';
		}

		// HTTP 1.1 allows 8000 chars but the "de-facto" standard supported in all current browsers is 2048.
		if ( strlen( $url ) > 2048 ) {
			return ''; // Return empty rather than a truncated/invalid URL
		}

		// Does not look like a URL.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		$url = WP_Http::make_absolute_url( $url, $source_url );

		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	/**
	 * Utility method to limit image source URLs.
	 *
	 * Excluded URLs include share-this type buttons, loaders, spinners, spacers, WordPress interface images,
	 * tiny buttons or thumbs, mathtag.com or quantserve.com images, or the WordPress.com stats gif.
	 *
	 * @param string $src Image source URL.
	 * @return string If not matched an excluded URL type, the original URL, empty string otherwise.
	 */
	protected static function limit_img( $src, $source_url ) {
		$src = self::limit_url( $src, $source_url );

		if ( preg_match( '!/ad[sx]?/!i', $src ) ) {
			// Ads
			return '';
		} elseif ( preg_match( '!(/share-?this[^.]+?\.[a-z0-9]{3,4})(\?.*)?$!i', $src ) ) {
			// Share-this type button
			return '';
		} elseif ( preg_match( '!/(spinner|loading|spacer|blank|rss)\.(gif|jpg|png)!i', $src ) ) {
			// Loaders, spinners, spacers
			return '';
		} elseif ( preg_match( '!/([^./]+[-_])?(spinner|loading|spacer|blank)s?([-_][^./]+)?\.[a-z0-9]{3,4}!i', $src ) ) {
			// Fancy loaders, spinners, spacers
			return '';
		} elseif ( preg_match( '!([^./]+[-_])?thumb[^.]*\.(gif|jpg|png)$!i', $src ) ) {
			// Thumbnails, too small, usually irrelevant to context
			return '';
		} elseif ( false !== stripos( $src, '/wp-includes/' ) ) {
			// Classic WordPress interface images
			return '';
		} elseif ( false !== stripos( $src, '/wp-content/themes' ) ) {
			// Anything within a WordPress theme directory
			return '';
		} elseif ( false !== stripos( $src, '/wp-content/plugins' ) ) {
			// Anything within a WordPress plugin directory
			return '';
		} elseif ( preg_match( '![^\d]\d{1,2}x\d+\.(gif|jpg|png)$!i', $src ) ) {
			// Most often tiny buttons/thumbs (< 100px wide)
			return '';
		} elseif ( preg_match( '!/pixel\.(mathtag|quantserve)\.com!i', $src ) ) {
			// See mathtag.com and https://www.quantcast.com/how-we-do-it/iab-standard-measurement/how-we-collect-data/
			return '';
		} elseif ( preg_match( '!/[gb]\.gif(\?.+)?$!i', $src ) ) {
			// WordPress.com stats gif
			return '';
		}
		// Optionally add additional limits
		return apply_filters( 'parse_this_img_filters', $src );
	}

	/**
	 * Limit embed source URLs to specific providers.
	 *
	 * Not all core oEmbed providers are supported. Supported providers include YouTube, Vimeo,
	 * Vine, Daily Motion, SoundCloud, and Twitter.
	 *
	 * @param string $src Embed source URL.
	 * @param string $source_url Source URL
	 * @return string If not from a supported provider, an empty string. Otherwise, a reformatted embed URL.
	 */
	protected static function limit_embed( $src, $source_url ) {
		$src = self::limit_url( $src, $source_url );

		if ( empty( $src ) ) {
			return '';
		}

		if ( preg_match( '!//(m|www)\.youtube\.com/(embed|v)/([^?]+)\?.+$!i', $src, $src_matches ) ) {
			// Embedded Youtube videos (www or mobile)
			$src = 'https://www.youtube.com/watch?v=' . $src_matches[3];
		} elseif ( preg_match( '!//player\.vimeo\.com/video/([\d]+)([?/].*)?$!i', $src, $src_matches ) ) {
			// Embedded Vimeo iframe videos
			$src = 'https://vimeo.com/' . (int) $src_matches[1];
		} elseif ( preg_match( '!//vimeo\.com/moogaloop\.swf\?clip_id=([\d]+)$!i', $src, $src_matches ) ) {
			// Embedded Vimeo Flash videos
			$src = 'https://vimeo.com/' . (int) $src_matches[1];
		} elseif ( preg_match( '!//vine\.co/v/([^/]+)/embed!i', $src, $src_matches ) ) {
			// Embedded Vine videos
			$src = 'https://vine.co/v/' . $src_matches[1];
		} elseif ( preg_match( '!//(www\.)?dailymotion\.com/embed/video/([^/?]+)([/?].+)?!i', $src, $src_matches ) ) {
			// Embedded Daily Motion videos
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
