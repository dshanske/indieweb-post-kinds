<?php
/**
 * Helpers for processing microformats2 array structures.
 * Derived from https://github.com/barnabywalters/php-mf-cleaner
 * and https://github.com/aaronpk/XRay/blob/master/lib/Formats/Mf2.php
 * and https://github.com/pfefferle/wordpress-semantic-linkbacks/blob/master/includes/class-linkbacks-mf2-handler.php
 **/

class Parse_This_MF2 {

	/**
	 * is this what type
	 *
	 * @param array $mf Parsed Microformats Array
	 * @param string $type Type
	 * @return bool
	 */
	public static function is_type( $mf, $type ) {
		return is_array( $mf ) && ! empty( $mf['type'] ) && is_array( $mf['type'] ) && in_array( $type, $mf['type'], true );
	}

	/**
	 * Parse Content
	 *
	 * @param array $mf Parsed Microformats Array.
	 * @return array $data Content array consisting of text and html properties.
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
				$htmlcontent = trim( wp_kses_post( $content['html'] ) );
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
	 * Verifies if $mf is an array without numeric keys, and has a 'properties' key.
	 *
	 * @param $mf
	 * @return bool
	 */
	public static function is_microformat( $mf ) {
		return ( is_array( $mf ) && ! wp_is_numeric_array( $mf ) && ! empty( $mf['type'] ) && isset( $mf['properties'] ) );
	}


	/**
	 * Verifies if $mf has an 'items' key which is also an array, returns true.
	 *
	 * @param $mf
	 * @return bool
	 */
	public static function is_microformat_collection( $mf ) {
		return ( is_array( $mf ) && isset( $mf['items'] ) && is_array( $mf['items'] ) );
	}

	/**
	 * Verifies if $p is an array without numeric keys and has key 'value' and 'html' set.
	 *
	 * @param $p
	 * @return bool
	 */
	public static function is_embedded_html( $p ) {
		return is_array( $p ) && ! wp_is_numeric_array( $p ) && isset( $p['value'] ) && isset( $p['html'] );
	}

	/**
	 * Verifies if property named $propname is in array $mf.
	 *
	 * @param array    $mf
	 * @param $propname
	 * @return bool
	 */
	public static function has_prop( array $mf, $propname ) {
		return ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] );
	}


	/**
	 * Verifies if rel named $relname is in array $mf.
	 *
	 * @param array   $mf
	 * @param $relname
	 * @return bool
	 */
	public static function has_rel( array $mf, $relname ) {
		return ! empty( $mf['rels'][ $relname ] ) && is_array( $mf['rels'][ $relname ] );
	}

	/**
	 * shortcut for getPlaintext.
	 *
	 * @deprecated use getPlaintext from now on
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return mixed|null
	 */
	public static function get_prop( array $mf, $propname, $fallback = null ) {
		return self::get_plaintext( $mf, $propname, $fallback );
	}

	/**
	 * If $v is a microformat or embedded html, return $v['value']. Else return v.
	 *
	 * @param $v
	 * @return mixed
	 */
	public static function to_plaintext( $v ) {
		if ( self::is_microformat( $v ) || self::is_embedded_html( $v ) ) {
			return $v['value'];
		} elseif ( is_array( $v ) && isset( $v['text'] ) ) {
			return $v['text'];
		}
		return $v;
	}

	/**
	 * Returns plaintext of $propname with optional $fallback
	 *
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return mixed|null
	 * @link http://php.net/manual/en/function.current.php
	 */
	public static function get_plaintext( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return self::to_plaintext( current( $mf['properties'][ $propname ] ) );
		}
		return $fallback;
	}

	/**
	 * Converts $propname in $mf into array_map plaintext, or $fallback if not valid.
	 *
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return null
	 */
	public static function get_plaintext_array( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return array_map( array( 'Parse_Mf2', 'to_plaintext' ), $mf['properties'][ $propname ] ); }
		return $fallback;
	}

	/**
	 *  Return an array of properties, and may contain plaintext content
	 *
	 * @param array       $mf
	 * @param array       $properties
	 * @return null|array
	 */
	public static function get_prop_array( array $mf, $properties, $args = null ) {
		if ( ! self::is_microformat( $mf ) ) {
			return array();
		}

		$data = array();
		foreach ( $properties as $p ) {
			if ( array_key_exists( $p, $mf['properties'] ) ) {
				foreach ( $mf['properties'][ $p ] as $v ) {
					if ( self::is_microformat( $v ) ) {
						$data[ $p ] = self::parse_item( $v, $mf, $args );
					} else {
						if ( isset( $data[ $p ] ) ) {
							$data[ $p ][] = $v;
						} else {
							$data[ $p ] = array( $v );
						}
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Returns ['html'] element of $v, or ['value'] or just $v, in order of availablility.
	 *
	 * @param $v
	 * @return mixed
	 */
	public static function to_html( $v ) {
		if ( self::is_embedded_html( $v ) ) {
			return $v['html']; } elseif ( self::is_microformat( $v ) ) {
			return htmlspecialchars( $v['value'] ); }
			return htmlspecialchars( $v );
	}

	/**
	 * Gets HTML of $propname or if not, $fallback
	 *
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return mixed|null
	 */
	public static function get_html( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return self::to_html( current( $mf['properties'][ $propname ] ) ); }
		return $fallback;
	}



	/**
	 * Returns 'summary' element of $mf or a truncated Plaintext of $mf['properties']['content'] with 19 chars and ellipsis.
	 *
	 * @deprecated as not often used
	 * @param array $mf
	 * @param array $content
	 * @return mixed|null|string
	 */
	public static function get_summary( array $mf, $content = null ) {
		if ( self::has_prop( $mf, 'summary' ) ) {
			return self::get_prop( $mf, 'summary' );
		}
		if ( ! $content ) {
			$content = self::parse_html_value( $mf, 'content' );
		}
		$summary = substr( $content['text'], 0, 300 );
		if ( 300 < strlen( $content['text'] ) ) {
			$summary .= '...';
		}
		return $summary;
	}


	/**
	 * Gets the date published of $mf array.
	 *
	 * @param array       $mf
	 * @param bool        $ensurevalid
	 * @param null|string $fallback optional result if date not available
	 * @return mixed|null
	 */
	public static function get_published( array $mf, $ensurevalid = false, $fallback = null ) {
		return self::get_datetime_property( 'published', $mf, $ensurevalid, $fallback );
	}

	/**
	 * Gets the date updated of $mf array.
	 *
	 * @param array $mf
	 * @param bool  $ensurevalid
	 * @param null  $fallback
	 * @return mixed|null
	 */
	public static function get_updated( array $mf, $ensurevalid = false, $fallback = null ) {
		return self::get_datetime_property( 'updated', $mf, $ensurevalid, $fallback );
	}

	/**
	 * Gets the DateTime properties including published or updated, depending on params.
	 *
	 * @param $name string updated or published
	 * @param array                            $mf
	 * @param bool                             $ensurevalid
	 * @param null|string                      $fallback
	 * @return mixed|null
	 */
	public static function get_datetime_property( $name, array $mf, $ensurevalid = false, $fallback = null ) {
		$compliment = 'published' === $name ? 'updated' : 'published';
		if ( self::has_prop( $mf, $name ) ) {
			$return = self::get_prop( $mf, $name ); } elseif ( self::has_prop( $mf, $compliment ) ) {
			$return = self::get_prop( $mf, $compliment );
			} else {
				return $fallback; }
			if ( ! $ensurevalid ) {
				return $return; } else {
				try {
					$date = new DateTime( $return );
					return $date->format( DATE_W3C );
				} catch ( Exception $e ) {
					return $fallback;
				}
				}
	}

	/**
	 * True if same hostname is parsed on both
	 *
	 * @param $u1 string url
	 * @param $u2 string url
	 * @return bool
	 * @link http://php.net/manual/en/function.parse-url.php
	 */
	public static function same_hostname( $u1, $u2 ) {
		return wp_parse_url( $u1, PHP_URL_HOST ) === wp_parse_url( $u2, PHP_URL_HOST );
	}


	/**
	 * Large function for fishing out author of $mf from various possible array elements.
	 *
	 * @param array      $item Individual item
	 * @param array      $mf2 Overall Microformats array
	 * @param boolean $follow Follow author arrays
	 */
	public static function find_author( $item, $mf2, $follow = false ) {
		// Author Discovery
		// http://indieweb,org/authorship
		$authorpage = false;
		if ( self::has_prop( $item, 'author' ) ) {
			// Check if any of the values of the author property are an h-card
			foreach ( $item['properties']['author'] as $a ) {
				if ( self::is_type( $a, 'h-card' ) ) {
					// 5.1 "if it has an h-card, use it, exit."
					return $a;
				} elseif ( is_string( $a ) ) {
					if ( wp_http_validate_url( $a ) ) {
						// 5.2 "otherwise if author property is an http(s) URL, let the author-page have that URL"
						$authorpage = $a;
					} else {
						// 5.3 "otherwise use the author property as the author name, exit"
						// We can only set the name, no h-card or URL was found
						$author = self::get_plaintext( $item, 'author' );
					}
				} else {
					// This case is only hit when the author property is an mf2 object that is not an h-card
					$author = self::get_plaintext( $item, 'author' );
				}
				if ( ! $authorpage ) {
					return array(
						'type'       => array( 'h-card' ),
						'properties' => array(
							'name' => array( $author ),
						),
					);
				}
			}
		}
			// 6. "if no author page was found" ... check for rel-author link
		if ( ! $authorpage ) {
			if ( isset( $mf2['rels'] ) && isset( $mf2['rels']['author'] ) ) {
				$authorpage = $mf2['rels']['author'][0];
			}
		}
		// 7. "if there is an author-page URL" ...
		if ( $authorpage ) {
			if ( $follow && ! self::urls_match( $authorpage, self::get_plaintext( $mf2, 'url' ) ) ) {
				$parse = new Parse_This( $authorpage );
				$parse->fetch();
				$parse->parse();
				return $parse->get();
			} else {
				return array(
					'type'       => array( 'h-card' ),
					'properties' => array(
						'url' => array( $authorpage ),
					),
				);
			}
		}
	}

	/**
	 * Returns array per parse_url standard with pathname key added.
	 *
	 * @param $url
	 * @return mixed
	 * @link http://php.net/manual/en/function.parse-url.php
	 */
	public static function parse_url( $url ) {
		$r             = wp_parse_url( $url );
		$r['pathname'] = empty( $r['path'] ) ? '/' : $r['path'];
		return $r;
	}


	/**
	 * See if urls match for each component of parsed urls. Return true if so.
	 *
	 * @param $url1
	 * @param $url2
	 * @return bool
	 * @see parseUrl()
	 */
	public static function urls_match( $url1, $url2 ) {
		return ( normalize_url( $url1 ) === normalize_url( $url2 ) );
	}

	/**
	 * Flattens microformats. Can intake multiple Microformats including possible MicroformatCollection.
	 *
	 * @param array $mfs
	 * @return array
	 */
	public static function flatten_microformat_properties( array $mf ) {
		$items = array();

		if ( ! self::is_microformat( $mf ) ) {
			return $items; }

		foreach ( $mf['properties'] as $proparray ) {
			foreach ( $proparray as $prop ) {
				if ( self::is_microformat( $prop ) ) {
					$items[] = $prop;
					$items   = array_merge( $items, self::flatten_microformat_properties( $prop ) );
				}
			}
		}

		return $items;
	}

	/**
	 * Flattens microformats. Can intake multiple Microformats including possible MicroformatCollection.
	 *
	 * @param array $mfs
	 * @return array
	 */
	public static function flatten_microformats( array $mfs ) {
		if ( self::is_microformat_collection( $mfs ) ) {
			$mfs = $mfs['items']; } elseif ( self::is_microformat( $mfs ) ) {
			$mfs = array( $mfs ); }

			$items = array();

			foreach ( $mfs as $mf ) {
				$items[] = $mf;

				$items = array_merge( $items, self::flatten_microformat_properties( $mf ) );

				if ( empty( $mf['children'] ) ) {
					continue; }

				foreach ( $mf['children'] as $child ) {
					$items[] = $child;
					$items   = array_merge( $items, self::flatten_microformat_properties( $child ) );
				}
			}

			return $items;
	}

	/**
	 *
	 * @param array $mfs
	 * @param $name
	 * @param bool  $flatten
	 * @return mixed
	 */
	public static function find_microformats_by_type( array $mfs, $name, $flatten = true ) {
		return self::find_microformats_by_callable(
			$mfs,
			function ( $mf ) use ( $name ) {
				return in_array( $name, $mf['type'], true );
			},
			$flatten
		);
	}


	/**
	 * Can determine if a microformat key with value exists in $mf. Returns true if so.
	 *
	 * @param array     $mfs
	 * @param $propname
	 * @param $propvalue
	 * @param bool      $flatten
	 * @return mixed
	 * @see findMicroformatsByCallable()
	 */
	public static function find_microformats_by_property( array $mfs, $propname, $propvalue, $flatten = true ) {
		return find_microformats_by_callable(
			$mfs,
			function ( $mf ) use ( $propname, $propvalue ) {
				if ( ! hasProp( $mf, $propname ) ) {
					return false; }

				if ( in_array( $propvalue, $mf['properties'][ $propname ], true ) ) {
					return true; }

				return false;
			},
			$flatten
		);
	}

	/**
	 * $callable should be a function or an exception will be thrown. $mfs can accept microformat collections.
	 * If $flatten is true then the result will be flattened.
	 *
	 * @param array    $mfs
	 * @param $callable
	 * @param bool     $flatten
	 * @return mixed
	 * @link http://php.net/manual/en/function.is-callable.php
	 * @see flattenMicroformats()
	 */
	public static function find_microformats_by_callable( array $mfs, $callable, $flatten = true ) {
		if ( ! is_callable( $callable ) ) {
			throw new \InvalidArgumentException( '$callable must be callable' ); }

		if ( $flatten && ( self::is_microformat( $mfs ) || self::is_microformat_collection( $mfs ) ) ) {
			$mfs = self::flatten_microformats( $mfs ); }

		return array_values( array_filter( $mfs, $callable ) );
	}

	public static function find_hfeed( $input, $url ) {
		if ( ! class_exists( 'Mf2\Parser' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'lib/mf2/Parser.php';
		}
		if ( is_string( $input ) || is_a( $input, 'DOMDocument' ) ) {
			$parser = new Mf2\Parser( $input, $url );
			$input  = $parser->parse();
		}
		$feeds = self::find_microformats_by_type( $input, 'h-feed', $flatten = true );
		if ( empty( $feeds ) && array_key_exists( 'items', $input ) ) {
			if ( 1 < count( $input['items'] ) ) {
				$feeds[] = array(
					'type'       => 'h-feed',
					'properties' => array(
						'url' => array( $url ),
					),
				);
			}
		}
		foreach ( $feeds as $key => $feed ) {
			if ( ! array_key_exists( 'url', $feed['properties'] ) ) {
				if ( array_key_exists( 'id', $feed ) ) {
					$feeds[ $key ]['properties']['url'] = array( $url . '#' . $feed['id'] );
				} else {
					$feeds[ $key ]['properties']['url'] = array( $url );
				}
			}
		}
		return $feeds;
	}


	/*
	 * Parse MF2 into JF2
	 *
	 * @param string|DOMDocument|array $input HTML marked up content, HTML in DOMDocument, or array of already parsed MF2 JSON
	 */
	public static function parse( $input, $url, $args = array() ) {
		$defaults    = array(
			'alternate' => true, // Use rel-alternate if set for jf2 or mf2
			'return'    => 'single',
			'follow'    => false, // Follow author links and return parsed data
		);
		$args        = wp_parse_args( $args, $defaults );
		$args['url'] = $url;
		if ( ! in_array( $args['return'], array( 'single', 'feed' ), true ) ) {
			$args['return'] = 'single';
		}
		// Normalize all urls to ensure comparisons
		$url = normalize_url( $url );
		if ( ! class_exists( 'Mf2\Parser' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'lib/mf2/Parser.php';
		}
		if ( is_string( $input ) || is_a( $input, 'DOMDocument' ) ) {
			$parser = new Mf2\Parser( $input, $url );
			$input  = $parser->parse();
			if ( $args['alternate'] ) {
				// Check for rel-alternate jf2 or mf2 feed
				if ( isset( $input['rel-urls'] ) ) {
					foreach ( $input['rel-urls'] as $rel => $info ) {
						if ( isset( $info['rels'] ) && in_array( 'alternate', $info['rels'], true ) ) {
							if ( isset( $info['type'] ) ) {
								if ( 'application/jf2feed+json' === $info['type'] ) {
									$parse = new Parse_This( $rel );
									$parse->fetch();
									return $parse->get();
								}
								if ( 'application/jf2+json' === $info['type'] ) {
									$parse = new Parse_This( $rel );
									$parse->fetch();
									return $parse->get();
								}
								if ( 'application/mf2+json' === $info['type'] ) {
									$parse = new Parse_This( $rel );
									$parse->fetch();
									$input = $parse->get( 'content' );
									break;
								}
							}
						}
					}
				}
			}
		}
		if ( ! is_array( $input ) ) {
			return array();
		}

		$count = count( $input['items'] );
		if ( 0 === $count ) {
			return array();
		}

		if ( 'feed' === $args['return'] && $count > 1 ) {
			$input = self::normalize_feed( $input );
			$count = count( $input['items'] );
		}

		if ( 1 === $count ) {
			return self::parse_item( $input['items'][0], $input, $args );
		}

		$return = array();
		$card   = null;
		foreach ( $input['items'] as $key => $item ) {
			$parsed = self::parse_item( $item, $input, $args );
			if ( isset( $parsed['url'] ) ) {
				if ( is_array( $parsed['url'] ) ) {
					$check = in_array( $url, $parsed['url'], true );
				} elseif ( is_string( $parsed['url'] ) ) {
					$check = self::urls_match( $url, $parsed['url'] );
				}
				if ( $check ) {
					if ( 'feed' !== $args['return'] ) {
						return $parsed;
					}
				}
			}
			$return[] = $parsed;
		}
		return $return;
	}

	// Tries to normalize a set of items into a feed
	public static function normalize_feed( $input ) {
		$hcard = array();
		foreach ( $input['items'] as $key => $item ) {
			if ( self::is_type( $item, 'h-card' ) ) {
				$hcard = $item;
				unset( $input['items'][ $key ] );
				break;
			}
		}
		if ( 1 === count( $input['items'] ) ) {
			if ( self::has_prop( $input['items'][0], 'author' ) ) {
				$input['items'][0]['properties']['author'] = array( $hcard );
			}
			return $input;
		}
		return array(
			'items' => array(
				array(
					'type'       => array( 'h-feed' ),
					'properties' => array(
						'author' => array( $hcard ),
					),
					'children'   => $input['items'],
				),
			),
		);
	}

	public static function parse_hfeed( $entry, $mf, $args ) {
		$data           = array(
			'type'  => 'feed',
			'items' => array(),
		);
		$data['name']   = self::get_plaintext( $entry, 'name' );
		$author         = self::find_author( $entry, $args['follow'] );
		$data['author'] = self::parse_hcard( $author, $mf, $args );
		$data['uid']    = self::get_plaintext( $entry, 'uid' );
		if ( isset( $entry['id'] ) && isset( $args['url'] ) && ! $data['uid'] ) {
			$data['uid'] = $args['url'] . '#' . $entry['id'];
		}

		if ( isset( $entry['children'] ) && 'feed' === $args['return'] ) {
			$data['items'] = self::parse_children( $entry['children'], $mf, $args );
		}
		$data    = array_filter( $data );
		$authors = array();
		if ( isset( $data['author'] ) ) {
			$authors[] = $data['author'];
		}
		if ( isset( $data['items'] ) ) {
			foreach ( $data['items'] as $key => $item ) {
				foreach ( $authors as $author ) {
					if ( is_string( $author['url'] ) ) {
						$author['url'] = array( $author['url'] );
					}
					if ( array_key_exists( 'author', $item ) && in_array( $item['author']['url'], $author['url'], true ) ) {
						$item['author'] = $author;
						break;
					}
				}
				$data['items'][ $key ] = $item;
			}
		}
		return $data;
	}

	public static function parse_children( $children, $mf, $args ) {
		$items = array();
		$index = 0;
		foreach ( $children as $child ) {
			if ( isset( $args['limit'] ) && $args['limit'] === $index ) {
				continue;
			}
			$item = self::parse_item( $child, $mf, $args );
			if ( isset( $item['type'] ) ) {
				$items[] = $item;
			}
			$index++;
		}
		return array_filter( $items );
	}

	public static function parse_item( $item, $mf, $args ) {
		if ( self::is_type( $item, 'h-feed' ) ) {
			return self::parse_hfeed( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-card' ) ) {
			return self::parse_hcard( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-entry' ) || self::is_type( $item, 'h-cite' ) ) {
			return self::parse_hentry( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-event' ) ) {
			return self::parse_hevent( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-review' ) ) {
			return self::parse_hreview( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-recipe' ) ) {
			return self::parse_hrecipe( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-listing' ) ) {
			return self::parse_hlisting( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-product' ) ) {
			return self::parse_hproduct( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-resume' ) ) {
			return self::parse_hresume( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-item' ) ) {
			return self::parse_hitem( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-leg' ) ) {
			return self::parse_hleg( $item, $mf, $args );
		}
		return self::parse_hunknown( $item, $mf, $args );
	}

	public static function compare( $string1, $string2 ) {
		if ( empty( $string1 ) || empty( $string2 ) ) {
			return false;
		}
		$string1 = trim( $string1 );
		$string2 = trim( $string2 );
		return ( 0 === strpos( $string1, $string2 ) );
	}

	public static function parse_hunknown( $unknown, $mf, $args ) {
		// Parse unknown h property
		$data         = self::parse_h( $unknown, $mf, $args );
		$data['type'] = $unknown['type'][0];
		return $data;
	}

	public static function parse_h( $entry, $mf, $args ) {
		$data              = array();
		$data['name']      = self::get_plaintext( $entry, 'name' );
		$data['published'] = self::get_published( $entry, true, null );
		$data['updated']   = self::get_updated( $entry, true, null );
		$data['url']       = normalize_url( self::get_plaintext( $entry, 'url' ) );
		$author            = self::find_author( $entry, $mf, $args['follow'] );
		$data['author']    = self::parse_hcard( $author, $mf, $args, $data['url'] );
		$data['content']   = self::parse_html_value( $entry, 'content' );
		$data['summary']   = self::get_summary( $entry, $data['content'] );
		// If name and content are equal remove name
		if ( self::compare( $data['name'], $data['content']['text'] ) ) {
			unset( $data['name'] );
		}
		// If summary and content are equal remove summary
		if ( self::compare( $data['summary'], $data['content']['text'] ) ) {
			unset( $data['summary'] );
		}

		if ( isset( $mf['rels']['syndication'] ) ) {
			if ( isset( $data['syndication'] ) ) {
				if ( is_string( $data['syndication'] ) ) {
					$data['syndication'] = array( $data['syndication'] );
				}
				$data['syndication'] = array_unique( array_merge( $data['syndication'], $mf['rels']['syndication'] ) );
			} else {
				$data['syndication'] = $mf['rels']['syndication'];
			}
		}
		return array_filter( $data );
	}

	public static function parse_hleg( $leg, $mf, $args ) {
		// The aaronpk special
		$data       = array();
		$properties = array(
			'url',
			'name',
			'origin',
			'destination',
			'operator',
			'transit-type',
			'number',
		);
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $leg, $property );
		}

		$data['departure'] = self::get_datetime_property( 'departure', $leg, true, null );
		$data['arrival']   = self::get_datetime_property( 'arrival', $leg, true, null );
		$data              = array_filter( $data );
		return $data;
	}

	public static function parse_hentry( $entry, $mf, $args ) {
		// Array Values
		$properties   = array(
			'checkin',
			'category',
			'invitee',
			'photo',
			'video',
			'audio',
			'syndication',
			'in-reply-to',
			'like-of',
			'repost-of',
			'bookmark-of',
			'favorite-of',
			'listen-of',
			'quotation-of',
			'watch-of',
			'read-of',
			'play-of',
			'jam-of',
			'itinerary',
			'tag-of',
			'location',
			'checked-in-by',
			'pk-ate',
			'pk-drank',
		);
		$data         = self::get_prop_array( $entry, $properties );
		$data['type'] = self::is_type( $entry, 'h-entry' ) ? 'entry' : 'cite';
		$properties   = array( 'url', 'weather', 'temperature', 'rsvp', 'featured', 'swarm-coins', 'latitude', 'longitude' );
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $entry, $property );
		}
		$data = array_filter( $data );
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		if ( $args['references'] ) {
			$data = jf2_references( $data );
		}
		$data['post-type'] = post_type_discovery( $data );
		return array_filter( $data );
	}

	public static function parse_hcard( $hcard, $mf, $args, $url = false ) {
		if ( ! self::is_microformat( $hcard ) ) {
			return;
		}
		$data       = array();
		$properties = array(
			'url',
			'uid',
			'name',
			'note',
			'photo',
			'bday',
			'callsign',
			'latitude',
			'longitude',
			'street-address',
			'extended-address',
			'locality',
			'region',
			'country-name',
			'label',
			'post-office-box',
			'given-name',
			'honoric-prefix',
			'additional-name',
			'family-name',
			'honorifix-suffix',
			'email',
			'postal-code',
			'altitude',
			'location',
		);
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $hcard, $property );
		}
		$data = array_filter( $data );
		$data = array_merge( self::get_prop_array( $hcard, array_keys( $hcard['properties'] ) ), $data );

		$data['type'] = 'card';
		if ( isset( $hcard['children'] ) ) {
			// In the case of sites like tantek.com where multiple feeds are nested inside h-card if it is a feed request return only the first feed
			if ( 'feed' === $args['return'] && self::is_type( $hcard['children'][0], 'h-feed' ) ) {
				$feed = self::parse_hfeed( $hcard['children'][0], $mf, $args );
				unset( $data['children'] );
				$feed['author'] = $data;
				return array_filter( $feed );
			} else {
				$data['items'] = self::parse_children( $hcard['children'], $mf, $args );
			}
		}
		return array_filter( $data );
	}

	public static function parse_hevent( $entry, $mf, $args ) {
		$data       = array(
			'type' => 'event',
			'name' => null,
			'url'  => null,
		);
		$data       = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		$properties = array( 'location', 'start', 'end', 'photo', 'uid', 'url' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		return array_filter( $data );
	}

	public static function parse_hreview( $entry, $mf, $args ) {
		$data       = array(
			'type' => 'review',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array( 'summary', 'published', 'rating', 'best', 'worst' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}


	public static function parse_hproduct( $entry, $mf, $args ) {
		$data       = array(
			'type' => 'product',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'brand', 'photo', 'audio', 'video' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array( 'identifier', 'price', 'description' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}


	public static function parse_hresume( $entry, $mf, $args ) {
		$data       = array(
			'type' => 'resume',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf ) );
		return array_filter( $data );
	}

	public static function parse_hlisting( $entry, $mf, $args ) {
		$data       = array(
			'type' => 'listing',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}

	public static function parse_hrecipe( $entry, $mf, $args ) {
		$data       = array(
			'type' => 'recipe',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}

	public static function parse_hitem( $entry, $mf, $args ) {
		$data       = array(
			'type' => 'item',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}

	public static function parse_hadr( $hadr, $mf, $args ) {
		$data       = array(
			'type' => 'adr',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'url', 'name', 'photo', 'location', 'latitude', 'longitude', 'note', 'uid', 'locality', 'region', 'country' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $hadr, $p );
			if ( null !== $v ) {
				// Make sure the URL property is actually a URL
				if ( 'url' === $p || 'photo' === $p ) {
					if ( wp_http_validate_url( $v ) ) {
						$data[ $p ] = $v;
					}
				} else {
					$data[ $p ] = $v;
				}
			}
		}
		return array_filter( $data );
	}

}
