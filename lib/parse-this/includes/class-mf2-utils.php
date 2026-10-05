<?php
/**
 * MF2_Utils class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Helpers for reading parsed microformats2 structures.
 *
 * Derived from php-mf-cleaner, XRay's Mf2 format and Semantic Linkbacks'
 * mf2 handler.
 *
 * @since 1.0.0
 *
 * @link https://github.com/barnabywalters/php-mf-cleaner
 * @link https://github.com/aaronpk/XRay/blob/master/lib/Formats/Mf2.php
 * @link https://github.com/pfefferle/wordpress-semantic-linkbacks/blob/master/includes/class-linkbacks-mf2-handler.php
 */
class MF2_Utils extends Base {

	/**
	 * Checks whether a value is a microformat object.
	 *
	 * A microformat is an associative array with a non-empty type and a
	 * properties key.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $mf Value to check.
	 * @return bool True if it is a microformat.
	 */
	public static function is_microformat( $mf ) {
		return ( is_array( $mf ) && ! wp_is_numeric_array( $mf ) && ! empty( $mf['type'] ) && isset( $mf['properties'] ) );
	}

	/**
	 * Checks whether a microformat has children.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $mf Value to check.
	 * @return bool True if it is a microformat with a children key.
	 */
	public static function has_children( $mf ) {
		return ( self::is_microformat( $mf ) && isset( $mf['children'] ) );
	}

	/**
	 * Checks whether a value is a parsed microformats document.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $mf Value to check.
	 * @return bool True if it has an items array.
	 */
	public static function is_microformat_array( $mf ) {
		return ( is_array( $mf ) && isset( $mf['items'] ) && is_array( $mf['items'] ) );
	}

	/**
	 * Checks whether a microformat has a given type.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $mf   Microformat.
	 * @param string $type Type to look for, for example h-entry.
	 * @return bool True if $type is among the microformat's types.
	 */
	public static function is_type( $mf, $type ) {
		return is_array( $mf ) && ! empty( $mf['type'] ) && is_array( $mf['type'] ) && in_array( $type, $mf['type'], true );
	}

	/**
	 * Returns the first type of a microformat.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $mf    Microformat.
	 * @param bool  $strip Optional. Whether to remove the h- prefix. Default false.
	 * @return string|false The type, or false if $mf is not a microformat.
	 */
	public static function get_type( $mf, $strip = false ) {
		$type = false;
		if ( self::is_microformat( $mf ) && is_array( $mf['type'] ) ) {
			$type = $mf['type'][0];
			if ( $strip ) {
				$type = str_replace( 'h-', '', $type );
			}
		}
		return $type;
	}

	/**
	 * Returns the text and sanitized HTML of an e-* property.
	 *
	 * The HTML is cleaned with Parser::clean_content() and only included
	 * when it differs from the text.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $mf       Microformat.
	 * @param string $property Property name, for example content.
	 * @return array|null Array with 'text' and possibly 'html', or null if the property
	 *                    is absent.
	 */
	public static function parse_html_value( $mf, $property ) {
		if ( ! array_key_exists( $property, $mf['properties'] ) ) {
			return null;
		}
		$textcontent = false;
		$htmlcontent = false;
		$content     = $mf['properties'][ $property ][0];
		if ( is_string( $content ) ) {
			$textcontent = $content;
		} elseif ( ! is_string( $content ) && is_array( $content ) && array_key_exists( 'value', $content ) ) {
			if ( array_key_exists( 'html', $content ) ) {
				$htmlcontent = trim( Parser::clean_content( $content['html'] ) );
				$textcontent = wp_strip_all_tags( $content['value'] );
			} else {
				$textcontent = trim( $content['value'] );
			}
		}
		$data = array(
			'text' => $textcontent,
		);
		if ( $htmlcontent && $textcontent !== $htmlcontent ) {
			$data['html'] = $htmlcontent;
		}
		return $data;
	}

	/**
	 * Checks whether a property value is an embedded HTML value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $p Property value.
	 * @return bool True if it is an associative array with value and html.
	 */
	public static function is_embedded_html( $p ) {
		return is_array( $p ) && ! wp_is_numeric_array( $p ) && isset( $p['value'] ) && isset( $p['html'] );
	}

	/**
	 * Checks whether a property value is an image with alt text.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $p Property value.
	 * @return bool True if it is an associative array with value and alt.
	 */
	public static function is_embedded_img( $p ) {
		return is_array( $p ) && ! wp_is_numeric_array( $p ) && isset( $p['value'] ) && isset( $p['alt'] );
	}

	/**
	 * Checks whether a microformat has a non-empty property.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $mf       Microformat.
	 * @param string $propname Property name.
	 * @return bool True if the property is present and non-empty.
	 */
	public static function has_prop( array $mf, $propname ) {
		return ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] );
	}


	/**
	 * Checks whether a parsed document has a rel value.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $mf      Parsed microformats document.
	 * @param string $relname Rel name, for example author.
	 * @return bool True if the document has at least one URL for that rel.
	 */
	public static function has_rel( array $mf, $relname ) {
		return ! empty( $mf['rels'][ $relname ] ) && is_array( $mf['rels'][ $relname ] );
	}

	/**
	 * Returns the URLs of a rel value.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $mf      Parsed microformats document.
	 * @param string $relname Rel name.
	 * @return string[]|false The URLs, or false if the rel is absent.
	 */
	public static function get_rel( array $mf, $relname ) {
		if ( self::has_rel( $mf, $relname ) ) {
			return $mf['rels'][ $relname ];
		}
		return false;
	}

	/**
	 * Checks whether a parsed document has rel-urls data for a URL.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $mf  Parsed microformats document.
	 * @param string $url URL to look up.
	 * @return bool True if rel-urls has an entry for $url.
	 */
	public static function has_rel_urls( array $mf, $url ) {
		return ! empty( $mf['rel-urls'][ $url ] ) && is_array( $mf['rel-urls'][ $url ] );
	}

	/**
	 * Removes empty values from parsed data.
	 *
	 * Unlike array_filter() without a callback, keeps "0" and 0, which are real
	 * values (a name of "0", a rating of 0).
	 *
	 * @since 2.0.0
	 *
	 * @param array $data Parsed properties.
	 * @return array The properties without null, false, '' and empty arrays.
	 */
	public static function filter_empty( $data ) {
		return array_filter(
			$data,
			function ( $value ) {
				return null !== $value && false !== $value && '' !== $value && array() !== $value;
			}
		);
	}

	/**
	 * Returns rel-urls data for a URL as h-card style properties.
	 *
	 * The link text, or failing that its title, becomes the name.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $mf  Parsed microformats document.
	 * @param string $url URL to look up.
	 * @return array|false Properties array with url and possibly name, or false if
	 *                     there is no rel-urls entry for $url.
	 */
	public static function get_rel_urls( array $mf, $url ) {
		if ( self::has_rel_urls( $mf, $url ) ) {
			$return = array(
				'url' => array( $url ),
			);
			if ( array_key_exists( 'text', $mf['rel-urls'][ $url ] ) ) {
				$return['name'] = array( $mf['rel-urls'][ $url ]['text'] );
			} elseif ( array_key_exists( 'title', $mf['rel-urls'][ $url ] ) ) {
				$return['name'] = array( $mf['rel-urls'][ $url ]['title'] );
			}
			return $return;
		}
		return false;
	}

	/**
	 * Returns the plain-text value of a property.
	 *
	 * Alias of get_plaintext().
	 *
	 * @deprecated 1.0.0 Use get_plaintext().
	 * @since 1.0.0
	 *
	 * @param array  $mf       Microformat.
	 * @param string $propname Property name.
	 * @param mixed  $fallback Optional. Value to return if the property is absent.
	 * @return mixed The first value as plain text, or $fallback.
	 */
	public static function get_prop( array $mf, $propname, $fallback = null ) {
		return self::get_plaintext( $mf, $propname, $fallback );
	}

	/**
	 * Returns the plain-text form of a property value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $v Property value.
	 * @return mixed The value key of a microformat, embedded HTML or image, the text
	 *               key of a parsed content array, or $v unchanged.
	 */
	public static function to_plaintext( $v ) {
		if ( self::is_microformat( $v ) || self::is_embedded_html( $v ) || self::is_embedded_img( $v ) ) {
			return $v['value'];
		} elseif ( is_array( $v ) && isset( $v['text'] ) ) {
			return $v['text'];
		}
		return $v;
	}

	/**
	 * Returns the first value of a property as plain text.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $mf       Microformat.
	 * @param string $propname Property name.
	 * @param mixed  $fallback Optional. Value to return if the property is absent.
	 * @return mixed The value, or $fallback.
	 */
	public static function get_plaintext( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return self::to_plaintext( current( $mf['properties'][ $propname ] ) );
		}
		return $fallback;
	}

	/**
	 * Returns every value of a property as plain text.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $mf       Microformat.
	 * @param string $propname Property name.
	 * @param mixed  $fallback Optional. Value to return if the property is absent.
	 * @return array|mixed The values, or $fallback.
	 */
	public static function get_plaintext_array( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return array_map( array( static::class, 'to_plaintext' ), $mf['properties'][ $propname ] ); }
		return $fallback;
	}

	/**
	 * Returns the HTML form of a property value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $v Property value.
	 * @return string The html key of embedded HTML, otherwise the value escaped with
	 *                htmlspecialchars().
	 */
	public static function to_html( $v ) {
		if ( self::is_embedded_html( $v ) ) {
			return $v['html']; } elseif ( self::is_microformat( $v ) ) {
			return htmlspecialchars( $v['value'] ); }
			return htmlspecialchars( $v );
	}

	/**
	 * Returns the first value of a property as HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $mf       Microformat.
	 * @param string $propname Property name.
	 * @param mixed  $fallback Optional. Value to return if the property is absent.
	 * @return mixed The value as HTML, or $fallback.
	 */
	public static function get_html( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return self::to_html( current( $mf['properties'][ $propname ] ) ); }
		return $fallback;
	}



	/**
	 * Returns a microformat's summary.
	 *
	 * Uses the summary property if present, otherwise the first 300 characters
	 * of the content text, with an ellipsis if truncated.
	 *
	 * @since 1.0.0
	 *
	 * @param array      $mf      Microformat.
	 * @param array|null $content Optional. Parsed content from parse_html_value(),
	 *                           to avoid parsing it again.
	 * @return string The summary, or an empty string.
	 */
	public static function get_summary( array $mf, $content = null ) {
		if ( self::has_prop( $mf, 'summary' ) ) {
			return self::get_prop( $mf, 'summary' );
		}
		if ( ! $content ) {
			$content = self::parse_html_value( $mf, 'content' );
		}
		if ( is_array( $content ) && array_key_exists( 'text', $content ) ) {
			$summary = mb_substr( $content['text'], 0, 300 );
			if ( 300 < mb_strlen( $content['text'] ) ) {
				$summary .= '...';
			}
			return $summary;
		}
		return '';
	}


	/**
	 * Returns a microformat's published date, falling back to updated.
	 *
	 * @since 1.0.0
	 *
	 * @param array $mf          Microformat.
	 * @param bool  $ensurevalid Optional. Whether to parse the value as a date.
	 *                           Default false.
	 * @param mixed $fallback    Optional. Passed to get_datetime_property().
	 * @return string|null The date in W3C format, or null if absent or not parsed.
	 */
	public static function get_published( array $mf, $ensurevalid = false, $fallback = null ) {
		$date = self::get_datetime_property( 'published', $mf, $ensurevalid, $fallback );
		if ( $date instanceof \DateTimeImmutable ) {
			return $date->format( DATE_W3C );
		}
		return null;
	}

	/**
	 * Returns a microformat's updated date, falling back to published.
	 *
	 * @since 1.0.0
	 *
	 * @param array $mf          Microformat.
	 * @param bool  $ensurevalid Optional. Whether to parse the value as a date.
	 *                           Default false.
	 * @param mixed $fallback    Optional. Passed to get_datetime_property().
	 * @return string|null The date in W3C format, or null if absent or not parsed.
	 */
	public static function get_updated( array $mf, $ensurevalid = false, $fallback = null ) {
		$date = self::get_datetime_property( 'updated', $mf, $ensurevalid, $fallback );
		if ( $date instanceof \DateTimeImmutable ) {
			return $date->format( DATE_W3C );
		}
		return null;
	}

	/**
	 * Returns a date property, falling back to its counterpart.
	 *
	 * For published the counterpart is updated; for any other name it is
	 * published.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name        Property name, for example published or updated.
	 * @param array  $mf          Microformat.
	 * @param bool   $ensurevalid Optional. Whether to parse the value into a
	 *                            DateTimeImmutable. Default false.
	 * @param mixed  $fallback    Optional. Value to return if absent or unparseable.
	 * @return DateTimeImmutable|string|mixed The parsed date when $ensurevalid is true,
	 *                                      the raw value otherwise, or $fallback.
	 */
	public static function get_datetime_property( $name, array $mf, $ensurevalid = false, $fallback = null ) {
		$compliment = 'published' === $name ? 'updated' : 'published';
		if ( self::has_prop( $mf, $name ) ) {
			$return = self::get_prop( $mf, $name );
		} elseif ( self::has_prop( $mf, $compliment ) ) {
			$return = self::get_prop( $mf, $compliment );
		} else {
			return $fallback;
		}
		if ( ! $ensurevalid ) {
			return $return;
		} else {
			try {
				return new \DateTimeImmutable( $return );
			} catch ( \Exception $e ) {
				return $fallback;
			}
		}
	}

	/**
	 * Checks whether two URLs have the same host.
	 *
	 * @since 1.0.0
	 *
	 * @param string $u1 First URL.
	 * @param string $u2 Second URL.
	 * @return bool True if the hosts match.
	 */
	public static function same_hostname( $u1, $u2 ) {
		return wp_parse_url( $u1, PHP_URL_HOST ) === wp_parse_url( $u2, PHP_URL_HOST );
	}

	/**
	 * Parses a URL, adding a pathname key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to parse.
	 * @return array The wp_parse_url() components plus 'pathname' ('/' if there is no path).
	 */
	public static function parse_url( $url ) {
		$r             = wp_parse_url( $url );
		$r['pathname'] = empty( $r['path'] ) ? '/' : $r['path'];
		return $r;
	}


	/**
	 * Checks whether two URLs are equal after normalization.
	 *
	 * See normalize_url().
	 *
	 * @since 1.0.0
	 *
	 * @param string $url1 First URL.
	 * @param string $url2 Second URL.
	 * @return bool True if they match.
	 */
	public static function urls_match( $url1, $url2 ) {
		return ( normalize_url( $url1 ) === normalize_url( $url2 ) );
	}
}
