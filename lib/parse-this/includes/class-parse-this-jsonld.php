<?php
/**
 * Parse This JSON-LD class.
 */
class Parse_This_JSONLD extends Parse_This_Base {
	/**
	 * Parses _meta, _images, and _links data from the content.
	 *
	 * @access public
	 */
	public static function parse( $doc, $url, $args ) {
		if ( ! $doc ) {
			return array();
		}
		$xpath = new DOMXPath( $doc );

		$jsonld  = array();
		$content = '';
		foreach ( $xpath->query( "//script[@type='application/ld+json']" ) as $script ) {
			$content  = $script->textContent; // phpcs:ignore
			$jsonld[] = json_decode( $content, true );
		}
		$jsonld = array_filter( $jsonld );
		if ( 1 === count( $jsonld ) && wp_is_numeric_array( $jsonld ) ) {
			$jsonld = $jsonld[0];
		}
		if ( ! wp_is_numeric_array( $jsonld ) && ! self::is_jsonld( $jsonld ) && self::is_jsonld_graph( $jsonld ) ) {
			$jsonld = $jsonld['@graph'];
		}

		if ( self::is_jsonld( $jsonld ) ) {
			$jsonld = array( $jsonld );
		}

		$jf2 = self::jsonld_to_jf2( $jsonld );
		if ( WP_DEBUG ) {
			$jf2['_jsonld'] = $jsonld;
		}
		return array_filter( $jf2 );
	}

	public static function jsonld_to_jf2( $jsonld ) {
		if ( empty( $jsonld ) ) {
			return array();
		}
		$jf2 = array();
		foreach ( $jsonld as $json ) {
			$type = self::get_type( $json );
			switch ( $type ) {
				case 'entry':
					if ( ! array_key_exists( 'entry', $jf2 ) ) {
						$jf2['entry'] = self::article_to_hentry( $json );
					} else {
						$jf2['entry'] = array_merge( $jf2['entry'], self::article_to_hentry( $json ) );
					}
					break;
				case 'person':
					$jf2['person'] = self::person_to_hcard( $json );
					break;
				case 'org':
					if ( ! array_key_exists( 'org', $jf2 ) ) {
						$jf2['org'] = self::organization_to_hcard( $json );
					} else {
						$jf2['org'] = array_merge( $jf2['org'], self::organization_to_hcard( $json ) );
					}
					break;
				case 'site':
					$jf2['site'] = self::site_to_hcard( $json );
					break;
				case 'event':
					$jf2['event'] = self::event_to_hevent( $json );
					break;
				case 'image':
					$jf2['image'] = self::image_to_photo( $json );
					break;
				case 'audio':
					$jf2['audio'] = self::audio_to_audio( $json );
					break;
				case 'video':
					$jf2['video'] = self::video_to_video( $json );
					break;
				case 'music':
					$jf2['music'] = self::music_to_hcite( $json );
					break;
				case 'media':
					$jf2['media'] = self::media_to_hcite( $json );
					break;
				case 'place':
					$jf2['place'] = self::place_to_hcard( $json );
					break;
			}
		}
		$return = null;
		if ( array_key_exists( 'entry', $jf2 ) ) {
			$return = $jf2['entry'];
			if ( array_key_exists( 'video', $jf2 ) ) {
				$return = array_merge( $return, $jf2['video'] );
			}
			if ( array_key_exists( 'audio', $jf2 ) ) {
				$return = array_merge( $return, $jf2['audio'] );
			}
			if ( array_key_exists( 'person', $jf2 ) ) {
				$return['author'] = $jf2['person'];
			}
			if ( array_key_exists( 'org', $jf2 ) ) {
				$return['org'] = $jf2['org'];
			}
		} elseif ( array_key_exists( 'event', $jf2 ) ) {
			$return = $jf2['event'];
		} elseif ( array_key_exists( 'video', $jf2 ) ) {
			$return = $jf2['video'];
		} elseif ( array_key_exists( 'audio', $jf2 ) ) {
			$return = $jf2['audio'];
		} elseif ( array_key_exists( 'media', $jf2 ) ) {
			$return = $jf2['media'];
		} elseif ( array_key_exists( 'person', $jf2 ) ) {
			$return = $jf2['person'];
		} else {
			return $jf2;
		}

		if ( ! array_key_exists( 'author', $return ) && array_key_exists( 'person', $jf2 ) ) {
			$return['author'] = $jf2['person'];
		}
		if ( ! array_key_exists( 'publication', $return ) && array_key_exists( 'publisher', $jf2 ) ) {
			$return['publication'] = $jf2['publisher'];
		}
		return array_filter( $return );
	}

	public static function music_to_hcite( $music ) {
		if ( 'music' !== self::get_type( $music ) ) {
			return false;
		}

		$return = array(
			'type'      => 'cite',
			'name'      => ifset( $music['name'] ),
			'url'       => ifset( $music['url'] ),
			'summary'   => ifset( $music['description'] ),
			'duration'  => ifset( $music['duration'] ),
			'category'  => ifset( $music['genre'] ),
			'published' => normalize_iso8601( ifset( $music['datePublished'] ) ),
			'featured'  => self::image_to_photo( ifset( $music['image'] ) ),
		);
		if ( isset( $music['releaseOf'] ) ) {
			if ( isset( $music['releaseOf']['byArtist'] ) ) {
				$return['author'] = array();
				foreach ( $music['releaseOf']['byArtist'] as $artist ) {
					$return['author'][] = array(
						array_filter(
							array(
								'type' => 'card',
								'name' => ifset( $artist['name'] ),
								'url'  => ifset( $artist['@id'] ),
							)
						),
					);
				}
			}
		}
		if ( isset( $music['tracks'] ) ) {
			$return['tracks'] = array();
			foreach ( $music['tracks'] as $track ) {
				$return['tracks'][] = self::music_to_hcite( $track );
			}
		}
		return array_filter( $return );
	}

	public static function media_to_hcite( $movie ) {
		if ( 'media' !== self::get_type( $movie ) ) {
			return false;
		}

		$return = array(
			'type'      => 'cite',
			'name'      => ifset( $movie['name'] ),
			'url'       => ifset( $movie['url'] ),
			'summary'   => ifset( $movie['description'] ),
			'duration'  => ifset( $movie['duration'] ),
			'category'  => ifset( $movie['genre'] ),
			'published' => normalize_iso8601( ifset( $movie['datePublished'] ) ),
			'featured'  => self::image_to_photo( ifset( $movie['image'] ) ),
			'video'     => self::video_to_video( ifset( $movie['trailer'] ) ),
		);

		if ( empty( $return['duration'] ) && isset( $movie['timeRequired'] ) ) {
			$return['duration'] = $movie['timeRequired'];
		}
		foreach ( array( 'actor', 'director', 'creator' ) as $type ) {
			if ( isset( $movie[ $type ] ) ) {
				if ( ! wp_is_numeric_array( $movie[ $type ] ) ) {
					$movie[ $type ] = array( $movie[ $type ] );
				}
				$return[ $type ] = array();
				foreach ( $movie[ $type ] as $person ) {
					$return[ $type ][] = self::person_to_hcard( $person );
				}
			}
		}
		return array_filter( $return );
	}

	public static function event_to_hevent( $event ) {
		if ( 'event' !== self::get_type( $event ) ) {
			return false;
		}
		$return = array(
			'type'      => 'event',
			'name'      => ifset( $event['name'] ),
			'url'       => ifset( $event['url'] ),
			'summary'   => ifset( $event['description'] ),
			'organizer' => self::organization_to_hcard( ifset( $event['organizer'] ) ),
			'location'  => self::place_to_hcard( ifset( $event['location'] ) ),
			'start'     => normalize_iso8601( ifset( $event['startDate'] ) ),
			'end'       => normalize_iso8601( ifset( $event['endDate'] ) ),
			'featured'  => self::image_to_photo( ifset( $event['image'] ) ),
		);

		return array_filter( $return );
	}


	public static function image_to_photo( $image ) {
		if ( wp_is_numeric_array( $image ) ) {
			$image = array_pop( $image );
		}
		if ( is_string( $image ) ) {
			return $image;
		}
		if ( 'image' !== self::get_type( $image ) ) {
			return false;
		}

		/*
		 if ( isset( $image['caption'] ) ) {
			return array(
				'value' => $image['url'],
				'alt' => $image['caption']
			);
		} */
		return $image['url'];
	}

	public static function audio_to_audio( $audio ) {
		if ( is_string( $audio ) ) {
			return $audio;
		}
		if ( 'audio' !== self::get_type( $audio ) ) {
			return false;
		}

		$return = array(
			'name'      => ifset( $audio['name'] ),
			'summary'   => ifset( $audio['description'] ),
			'featured'  => ifset( $audio['thumbnailUrl'] ),
			'audio'     => ifset( $audio['contentUrl'] ),
			'published' => normalize_iso8601( ifset( $audio['uploadDate'] ) ),
			'duration'  => ifset( $audio['duration'] ),
		);
		if ( isset( $audio['transcript'] ) ) {
			$return['content'] = array(
				'html'  => Parse_This::clean_content( $audio['transcript'] ),
				'value' => wp_strip_all_tags( $audio['transcript'] ),
			);
		}
		if ( isset( $audio['publisher'] ) ) {
			$return['publication'] = self::organization_to_hcard( $audio['publisher'] );
		}
		return array_filter( $return );
	}

	public static function video_to_video( $video ) {
		if ( is_string( $video ) ) {
			return $video;
		}
		if ( 'video' !== self::get_type( $video ) ) {
			return false;
		}
		$return = array(
			'name'      => ifset( $video['name'] ),
			'summary'   => ifset( $video['description'] ),
			'featured'  => ifset( $video['thumbnailUrl'] ),
			'video'     => ifset( $video['contentUrl'] ),
			'published' => normalize_iso8601( ifset( $video['uploadDate'] ) ),
			'duration'  => ifset( $video['duration'] ),
		);

		if ( isset( $vidio['transcript'] ) ) {
			$return['content'] = array(
				'html'  => Parse_This::clean_content( $vidio['transcript'] ),
				'value' => wp_strip_all_tags( $vidio['transcript'] ),
			);
		}
		if ( isset( $video['publisher'] ) ) {
			$return['publication'] = self::organization_to_hcard( $video['publisher'] );
		}
		return array_filter( $return );
	}

	public static function geocoordinates_to_geo( $geo ) {
		if ( ! self::is_jsonld( $geo ) ) {
			return false;
		}
		if ( ! self::is_jsonld_type( $geo, 'GeoCoordinates' ) ) {
			return false;
		}
		$return = array(
			'type'      => 'geo',
			'latitude'  => ifset( $geo['latitude'] ),
			'longitude' => ifset( $geo['longitude'] ),
		);
		return array_filter( $return );
	}

	public static function postaladdress_to_address( $address ) {
		if ( ! self::is_jsonld_type( $address, 'PostalAddress' ) ) {
			return false;
		}

		$return = array(
			'locality'       => ifset( $address['addressLocality'] ),
			'region'         => ifset( $address['addressRegion'] ),
			'country-name'   => ifset( $address['addressCountry'] ),
			'postal-code'    => ifset( $address['postalCode'] ),
			'street-address' => ifset( $address['streetAddress'] ),
		);
		return array_filter( $return );
	}

	public static function place_to_hcard( $place ) {
		if ( ! self::is_jsonld( $place ) ) {
			return false;
		}
		if ( 'place' !== self::get_type( $place ) ) {
			return false;
		}
		$hcard = array(
			'type'  => 'card',
			'_type' => 'place',
			'name'  => ifset( $place['name'] ),
			'note'  => ifset( $place['description'] ),
			'tel'   => ifset( $place['telephone'] ),
			'photo' => self::image_to_photo( ifset( $place['image'] ) ),
			'me'    => ifset( $place['sameAs'] ),
			'geo'   => self::geocoordinates_to_geo( $place['geo'] ),
		);

		if ( isset( $place['address'] ) ) {
			$hcard = array_merge( $hcard, self::postaladdress_to_address( $place['address'] ) );
		}
		return array_filter( $hcard );
	}

	public static function person_to_hcard( $person ) {
		if ( is_string( $person ) ) {
			return array(
				'type' => 'card',
				'name' => $person,
			);
		}
		if ( ! self::is_jsonld( $person ) ) {
			return false;
		}
		if ( ! 'person' === self::get_type( $person ) ) {
			return false;
		}
		if ( isset( $person['name'] ) && is_array( $person['name'] ) ) {
			$author = array();
			foreach ( $person['name'] as $a ) {
				$author[] = array(
					'type' => 'card',
					'name' => $a,
				);
			}
		} else {

			$author = array(
				'type'      => 'card',
				'name'      => ifset( $person['name'] ),
				'email'     => ifset( $person['email'] ),
				'photo'     => self::image_to_photo( ifset( $person['image'] ) ),
				'url'       => ifset( $person['url'] ),
				'me'        => ifset( $person['sameAs'] ),
				'email'     => ifset( $person['email'] ),
				'dt-bday'   => ifset( $person['birthDate'] ),
				'job-title' => ifset( $person['jobTitle'] ),
				'location'  => self::place_to_hcard( ifset( $person['location'] ) ),
			);
		}
		return array_filter( $author );
	}

	public static function site_to_hcard( $website ) {
		if ( 'site' !== self::get_type( $website ) ) {
			return false;
		}

		$publication = array(
			'type'  => 'card',
			'_type' => 'website',
			'name'  => ifset( $website['name'] ),
			'url'   => ifset( $website['url'] ),
			'me'    => ifset( $website['sameAs'] ),
		);
		return array_filter( $publication );
	}

	public static function organization_to_hcard( $organization ) {
		if ( 'org' !== self::get_type( $organization ) ) {
			return false;
		}

		$publication = array(
			'type'     => 'card',
			'_type'    => $organization['@type'],
			'name'     => ifset( $organization['name'] ),
			'photo'    => self::image_to_photo( ifset( $organization['logo'] ) ),
			'url'      => ifset( $organization['url'] ),
			'me'       => ifset( $organization['sameAs'] ),
			'email'    => ifset( $organization['email'] ),
			'location' => self::place_to_hcard( ifset( $organization['location'] ) ),
			'summary'  => ifset( $organization['description'] ),
		);
		if ( empty( $publication['photo'] ) ) {
			$publication['photo'] = self::image_to_photo( ifset( $organization['image'] ) );
		}
		if ( isset( $organization['member'] ) ) {
			$publication['member'] = array();
			foreach ( $organization['member'] as $member ) {
				$publication['member'] = self::person_to_hcard( $member );
			}
			$publication['members'] = array_filter( $publication['members'] );
		}
		if ( isset( $organization['address'] ) ) {
			$address = self::postaladdress_to_address( $organization['address'] );
			if ( is_array( $address ) ) {
				$publication = array_merge( $publication, $address );
			} else {
				$publication['_address'] = $address;
			}
		}
		return array_filter( $publication );
	}

	public static function is_jsonld( $jsonld ) {
		return ( is_array( $jsonld ) && array_key_exists( '@type', $jsonld ) );
	}

	public static function is_jsonld_graph( $jsonld ) {
		return ( is_array( $jsonld ) && array_key_exists( '@graph', $jsonld ) );
	}

	public static function is_jsonld_type( $jsonld, $type ) {
		if ( ! self::is_jsonld( $jsonld ) ) {
			return false;
		}

		if ( is_string( $type ) ) {
			$type = array( $type );
		}
		return ( in_array( $jsonld['@type'], $type, true ) );
	}

	public static function get_type( $jsonld ) {
		if ( self::is_jsonld_type( $jsonld, array( 'WebPage', 'Article', 'NewsArticle', 'BlogPosting' ) ) ) {
			return 'entry';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Organization', 'NewsMediaOrganization', 'NGO', 'MusicGroup' ) ) ) {
			return 'org';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Person' ) ) ) {
			return 'person';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'WebSite' ) ) ) {
			return 'site';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Event', 'BusinessEvent' ) ) ) {
			return 'event';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'ImageObject' ) ) ) {
			return 'image';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'AudioObject' ) ) ) {
			return 'audio';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'VideoObject' ) ) ) {
			return 'video';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'MusicRelease' ) ) ) {
			return 'music';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Movie', 'TVSeries', 'TVEpisode' ) ) ) {
			return 'media';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Place' ) ) ) {
			return 'place';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'PostalAddress' ) ) ) {
			return 'address';
		}

		return false;
	}


	public static function article_to_hentry( $newsarticle ) {
		if ( 'entry' !== self::get_type( $newsarticle ) ) {
			return false;
		}
		$jf2          = array();
		$jf2['type']  = 'entry';
		$jf2['_type'] = $newsarticle['@type'];
		if ( isset( $newsarticle['datePublished'] ) ) {
			$jf2['published'] = normalize_iso8601( $newsarticle['datePublished'] );
		}

		if ( isset( $newsarticle['dateModified'] ) ) {
			$jf2['updated'] = normalize_iso8601( $newsarticle['dateModified'] );
		}

		if ( isset( $newsarticle['headline'] ) ) {
			$jf2['name'] = $newsarticle['headline'];
		} elseif ( isset( $newsarticle['name'] ) ) {
			$jf2['name'] = $newsarticle['name'];
		}
		if ( isset( $newsarticle['description'] ) ) {
			$jf2['summary'] = $newsarticle['description'];
		}
		if ( isset( $newsarticle['image'] ) ) {
			if ( wp_is_numeric_array( $newsarticle['image'] ) ) {
				$newsarticle['image'] = end( $newsarticle['image'] );
			}
			$jf2['featured'] = self::image_to_photo( $newsarticle['image'] );
		}
		if ( isset( $newsarticle['keywords'] ) ) {
			if ( ! is_array( $newsarticle['keywords'] ) ) {
				$newsarticle['keywords'] = explode( ',', $newsarticle['keywords'] );
			} elseif ( is_string( $newsarticle['keywords'] ) ) {
				$newsarticle['keywords'] = array( $newsarticle['keywords'] );
			}
			$jf2['category'] = array_map( 'trim', $newsarticle['keywords'] );
		}

		if ( isset( $newsarticle['articleBody'] ) ) {
			$jf2['content'] = array(
				'html'  => Parse_This::clean_content( $newsarticle['articleBody'] ),
				'value' => wp_strip_all_tags( $newsarticle['articleBody'] ),
			);
		}
		if ( isset( $newsarticle['author'] ) ) {
			if ( ! wp_is_numeric_array( $newsarticle['author'] ) ) {
				$newsarticle['author'] = array( $newsarticle['author'] );
			}
			$jf2['author'] = array();
			foreach ( $newsarticle['author'] as $author ) {
				$jf2['author'][] = self::person_to_hcard( $author );
			}
			$jf2['author'] = array_filter( $jf2['author'] );
			if ( 0 === count( $jf2['author'] ) ) {
				unset( $jf2['author'] );
			} elseif ( 1 === count( $jf2['author'] ) ) {
				$jf2['author'] = array_pop( $jf2['author'] );
			}
		}
		if ( isset( $newsarticle['creator'] ) ) {
			if ( ! wp_is_numeric_array( $newsarticle['creator'] ) ) {
				$newsarticle['creator'] = array( $newsarticle['creator'] );
			}
			$jf2['author'] = array();
			foreach ( $newsarticle['creator'] as $creator ) {
				$jf2['author'][] = self::person_to_hcard( $creator );
			}
		}

		if ( isset( $newsarticle['video'] ) ) {
			$jf2['video'] = $newsarticle['video'][0]['@id'];
		}
		if ( isset( $newsarticle['audio'] ) ) {
			$jf2['audio'] = $newsarticle['audio'][0]['@id'];
		}

		if ( isset( $newsarticle['publisher'] ) ) {
			$jf2['publication'] = self::organization_to_hcard( $newsarticle['publisher'] );
		}
		return array_filter( $jf2 );
	}
}
