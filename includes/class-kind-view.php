<?php
/**
 * Post Kind View Class
 *
 * Includes Helper Functions to Set Up Display Behavior and Allows Calling of View Templates
 */

// The Kind_View class sets up the kind display behavior for kinds
class Kind_View {
	public static function init() {

		if ( apply_filters( 'kind_content_display', true ) ) {
			add_filter( 'the_content', array( static::class, 'content_response' ), 9 );
			add_filter( 'the_content_feed', array( static::class, 'content_feed_response' ), 9, 2 );
			add_filter( 'the_excerpt', array( static::class, 'excerpt_response' ), 9 );
		}

		add_filter( 'json_feed_item', array( static::class, 'json_feed_item' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( static::class, 'wp_get_attachment_image_attributes' ), 10, 2 );
	}

	/**
	 * Filters the attachment image attributes for image post kinds.
	 *
	 * @access public
	 *
	 * @param array   $attr       Attribute arguments for the attachment image.
	 * @param WP_Post $attachment Attachment post object.
	 * @return array
	 */
	public static function wp_get_attachment_image_attributes( array $attr, WP_Post $attachment ) {
		$parents = get_post_ancestors( $attachment );
		$count   = count( $parents );
		if ( 0 === $count ) {
			return $attr;
		}
		$id = $parents[ $count - 1 ];
		if ( 'photo' !== get_post_kind_slug( $id ) ) {
			return $attr;
		}

		if ( isset( $attr['class'] ) ) {
			$class = explode( ' ', $attr['class'] );

			// This class is added by the list display.
			if ( in_array( 'kind-photo-thumbnail', $class, true ) ) {
				return $attr;
			}
			$class[]       = 'u-photo';
			$attr['class'] = implode( ' ', array_unique( $class ) );
		} else {
			$attr['class'] = 'u-photo';
		}
		return $attr;
	}

	/*
	 * Function will locate the correct template and return it.
	 *
	 * @param string $slug Post Kind Slug.
	 * @param string $name Post Kind Term Name.
	 * @return string|null File if located or null if unable to find.
	 */
	public static function locate_view( $slug, $name ) {
		$name = (string) $name;
		if ( empty( $name ) ) {
			return '';
		}
		$templates[] = "{$slug}-{$name}.php";
		$templates[] = "{$slug}.php";
		$look        = apply_filters( 'kind_view_paths', array( get_theme_file_path( 'kind_views/' ) ) );
		$look[]      = plugin_dir_path( __DIR__ ) . 'views/';
		$located     = null;
		foreach ( (array) $templates as $template_name ) {
			if ( ! $template_name ) {
					continue;
			}
			foreach ( $look as $l ) {
				if ( file_exists( $l . $template_name ) ) {
					$located = $l . $template_name;
					break;
				}
			}
			if ( $located ) {
				break;
			}
		}
		return $located;
	}

	/**
	 * Post kind version of get_template_part WordPress function.
	 *
	 * Function will return the output.
	 *
	 * @access public
	 *
	 * @param string $slug Post kind slug.
	 * @param string $name Post kind term name.
	 * @param array $args Optional Arguments
	 * @return string
	 */
	public static function get_view_part( $slug, $name, $args = null ) {
		$located = self::locate_view( $slug, $name );
		// This should never happen.
		if ( empty( $located ) ) {
			return '';
		}

		$defaults = array(
			'post_id' => get_the_ID(),
		);
		$args     = is_null( $args ) ? $defaults : wp_parse_args( $args, $defaults );

		$kind_post = new Kind_Post( $args['post_id'] );
		$kind      = $kind_post->get_kind();
		$type      = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		$cite      = $kind_post->get_cite();
		$cite      = $kind_post->normalize_cite( $cite );
		$photos    = $kind_post->get_photo();

		if ( empty( $cite['name'] ) ) {
			$cite['name'] = $cite['url'];
		}

		$author = self::get_hcard( $cite['author'] );

		$url   = $cite['url'];
		$embed = self::get_embed( $cite['url'] );
		$kind  = $kind_post->get_kind();

		ob_start();
		include $located;
		$return = ob_get_contents();
		ob_end_clean();
		return wp_filter_content_tags( $return );
	}

	/**
	 * Return the post kind display.
	 *
	 * @access public
	 *
	 * @param int|null $post_id Post ID.
	 * @return mixed|void
	 */
	public static function get_display( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( 'post' === get_post_type( $post_id ) ) {
			$kind    = get_post_kind_slug( $post_id );
			$content = self::get_view_part( 'kind', $kind );
			return apply_filters( 'kind_response_display', $content, $post_id );
		}
	}

	/**
	 * Echo the output of get_display.
	 *
	 * @access public
	 * @param int|null $post_id Post ID.
	 */
	public static function display( $post_id = null ) {
		echo self::get_display( $post_id ); // phpcs:ignore
	}

	/**
	 * Output the post kind content to the post content.
	 *
	 * @access public
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function content_response( $content ) {
		if ( ( is_admin() ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $content;
		}

		if ( is_feed() ) {
			return $content;
		}
		global $wp_current_filter;
		// Don't allow to be added to the_content more than once (prevent infinite loops)
		$done = false;
		foreach ( $wp_current_filter as $filter ) {
			if ( 'the_content' === $filter ) {
				if ( $done ) {
					return $content;
				} else {
					$done = true;
				}
			}
		}
		if ( 1 === (int) get_option( 'kind_bottom' ) ) {
			return $content . self::get_display();
		}
		return self::get_display() . $content;
	}

	/**
	 * Output the post kind content to the feed item content.
	 *
	 * @access public
	 *
	 * @param string $content   Post content.
	 * @param string $feed_type Feed type being rendered.
	 * @return string
	 */
	public static function content_feed_response( $content, $feed_type ) {
		if ( 1 === (int) get_option( 'kind_bottom' ) ) {
			return $content . self::get_display();
		}
		return self::get_display() . $content;
	}

	/**
	 * Output the post kind content to the content if the `jsonfeed` plugin is active.
	 *
	 * @access public
	 *
	 * @param array   $feed_item Post content for the JSON feed item.
	 * @param WP_Post $post      Post object.
	 * @return mixed
	 */
	public static function json_feed_item( $feed_item, $post ) {

		$kind_post = new Kind_Post( $post );
		$kind      = $kind_post->get_kind();
		$type      = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		$cite      = mf2_to_jf2( $kind_post->get_cite() );

		if ( is_string( $cite ) ) {
			$url = wp_http_validate_url( $cite ) ? $cite : false;
		} elseif ( wp_is_numeric_array( $cite ) ) {
			$url = array_pop( $cite );
		} else {
			$url = $cite['url'] ?? '';
		}
		if ( $url ) {
			$feed_item['external_url'] = $url;
		}
		return $feed_item;
	}

	/**
	 * Append the post kind display to excerpts.
	 *
	 * @access public
	 *
	 * @param string $content Excerpt content.
	 * @return string
	 */
	public static function excerpt_response( $content ) {
		global $post;
		if ( ! $post ) {
			return $content;
		}
		if ( has_excerpt( get_the_ID() ) ) {
			return self::get_display() . get_the_excerpt();
		} else {
			return self::get_display() . wp_trim_words( $post->post_content );
		}
	}

	/**
	 * Extracts a domain name from a URL.
	 *
	 * This function will remove the www.prefix if it is part of the URL.
	 *
	 * @access public
	 *
	 * @param string $url URL to pars and extract domain for.
	 * @return string|string[]|null
	 */
	public static function extract_domain_name( $url ) {
		$parse = wp_parse_url( $url, PHP_URL_HOST );
		return preg_replace( '/^www\./', '', $parse );
	}

	/**
	 * Converts an array of attributes and output them as a string.
	 *
	 * @access public
	 *
	 * @param array|null $classes Array of classes to convert.
	 * @return string
	 */
	public static function get_attributes( $classes = null ) {
		if ( ! $classes ) {
			return '';
		}
		$return = '';
		foreach ( $classes as $key => $value ) {
			$return .= ' ' . esc_attr( $key ) . '="' . esc_attr( join( ' ', array_unique( $value ) ) ) . '"';
		}
		return $return;
	}

	/**
	 * Converts a URL into a complete `<a>` link with link text.
	 *
	 * @access public
	 *
	 * @param string       $url  URL to create an HTML link for.
	 * @param string       $name Link text to use.
	 * @param array|string $atr  Array of attributes to include on the link.
	 * @return string
	 */
	public static function get_url_link( $url, $name = '', $atr = '' ) {
		if ( empty( $url ) ) {
			return '';
		}
		if ( is_array( $atr ) ) {
				$atr = self::get_attributes( $atr );
		}
		$return = '<a ' . $atr . ' href="' . $url . '">' . $name . '</a>';
		return $return;
	}

	/**
	 * Create formatted HTML output for a field.
	 *
	 * @access public
	 *
	 * @param string $field Content to put in the markup
	 * @param string $attr  Attributes to add to the tag markup.
	 * @param string $type  HTML tag type to create. Default span.
	 *
	 * @return string
	 */
	public static function get_formatted( $field, $attr, $type = 'span' ) {
		if ( ! isset( $field ) ) {
			return $string;
		}
		$string = '<' . $type . $attr . '>' . $field . '</' . $type . '>';
		return $string;
	}

	/**
	 * Return post kind-wrapped oEmbed content for a provided URL.
	 *
	 * @access public
	 *
	 * @param string $url URL being output via oEmbed.
	 * @return string
	 */
	public static function get_embed( $url ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return '';
		}
		$option = get_option( 'kind_embeds' );
		if ( 0 === (int) $option ) {
				return '';
		}
		$host        = self::extract_domain_name( $url );
		$approvelist = array(
			'animoto.com',
			'blip.tv',
			'cloudup.com',
			'crowdsignal.com',
			'dailymotion.com',
			'flickr.com',
			'imgur.com',
			'issuu.com',
			'kickstarter.com',
			'meetup.com',
			'mixcloud.com',
			'reddit.com',
			'reverbnation.com',
			'scribd.com',
			'slideshare.net',
			'smugmug.com',
			'soundcloud.com',
			'speakerdeck.com',
			'spotify.com',
			'ted.com',
			'tiktok.com',
			'tumblr.com',
			'twitter.com',
			'videopress.com',
			'vimeo.com',
			'wordpress.tv',
			'youtube.com',
		);
		$approvelist = apply_filters( 'post_kind_embed_approvelist', $approvelist );
		if ( ! in_array( $host, $approvelist, true ) ) {
			return '';
		}
		if ( isset( $GLOBALS['wp_embed'] ) ) {
			$embed = $GLOBALS['wp_embed']->autoembed( $url );
		}
		if ( 0 === strcmp( $embed, $url ) ) {
			$embed = '';
		} else {
			$embed = sprintf( '<div class="kind-embed">%1$s<a class="u-url" href="%2$s"></a></div>', $embed, $url );
		}
			return $embed;
	}

	/**
	 * Returns an array of domains with the post type terminologies
	 *
	 * @access public
	 *
	 * @param string $url URL to use with translation.
	 * @return string A translated post type string for specific domain or 'a post'
	 */
	public static function get_post_type_string( $url ) {
		if ( ! $url || ! is_string( $url ) ) {
			return ' ';
		}
		if ( ! wp_http_validate_url( $url ) ) {
			return ' ';
		}
		$strings = array(
			'twitter.com'   => _x( 'a tweet', 'singular Twitter', 'indieweb-post-kinds' ),
			'vimeo.com'     => _x( 'a video', 'singular Vimeo', 'indieweb-post-kinds' ),
			'youtube.com'   => _x( 'a video', 'singular Youtube', 'indieweb-post-kinds' ),
			'instagram.com' => _x( 'an image', 'singular Intagram', 'indieweb-post-kinds' ),
		);
		$domain  = self::extract_domain_name( $url );
		if ( array_key_exists( $domain, $strings ) ) {
			return apply_filters( 'kind_post_type_string', $strings[ $domain ] );
		} else {
			return _x( 'a post', 'singular post', 'indieweb-post-kinds' );
		}
	}

	/**
	 * Retrieve/Generate the h-card.
	 *
	 * @param mixed $author The author to generate Accepts an array or optionally other info.
	 * @param array $args       {
	 *    Optional. Extra arguments to retrieve the avatar.
	 *
	 *     @type int          $height        Display height of the author image in pixels. Defaults to $size.
	 *     @type int          $width         Display width of the author image in pixels. Defaults to $size.
	 *     @type string       $display           Display 'photo', 'name', or 'both'. Defaults to 'name'.
	 * }
	 * @return false|string Marked up H-Card as String. False on failure.
	 */
	public static function get_hcard( $author, $args = null ) {
		$default = array(
			'height'  => 32,
			'width'   => 32,
			'display' => 'both',
		);
		$args    = wp_parse_args( $args, $default );

		/**
		 * Filter for alternate retrieval types
		 *
		 * This could be using WordPress's gravatar system, retrieval by pure URL, etc.
		 *
		 * @param string|boolean $author Defaults to false, but may return string.
		 * @param mixed  $author Data on the author, type optional. Defaults to array.
		 * @param array  $args        Arguments passed to get_hcard.
		 */
		$author = apply_filters( 'get_hcard_data', $author, $args );
		// If it didn't return an array as expected, then there is no valid author data.
		if ( ! is_array( $author ) ) {
			return false;
		}

		unset( $author['type'] );

		if ( empty( array_filter( $author ) ) ) {
			return '';
		}

		/**
		 * Filter for alternate presentation
		 *
		 * @param string|boolean $card Defaults to false, but may return string.
		 * @param mixed  $author Data on the author, type optional. Defaults to array.
		 * @param array  $args        Arguments passed to get_hcard.
		 */
		$card = apply_filters( 'get_hcard', '', $author, $args );
		if ( ! empty( $card ) ) {
			return $card;
		}
		// Temporarily drop multi-data on display
		if ( ! empty( $author['url'] ) && is_array( $author['url'] ) ) {
			$author['url'] = $author['url'][0];
		}

		if ( is_array( $author['name'] ) ) {
				$author['name'] = $author['name'][0];
		}

		if ( empty( $author['name'] ) && ! empty( $author['url'] ) ) {
			$author['name'] = __( 'an author', 'indieweb-post-kinds' );
		}

		// If no filter generated the card, generate the card.
		switch ( $args['display'] ) {
			case 'photo':
				if ( empty( $author['photo'] ) ) {
					return false;
				}
				if ( empty( $author['url'] ) ) {
					return sprintf( '<img src="%1s" class="h-card u-photo p-author" alt="%2s" width=%3s height=%4s />', $author['photo'], $author['name'], $args['width'], $args['height'] );
				} else {
					return sprintf( '<a class="h-card p-author" href="%1s"><img class="u-photo" src="%2s" alt="%3s" width=%4s height=%5s /></a>', $author['url'], $author['photo'], $author['name'], $args['width'], $args['height'] );
				}
				break;
			case 'name':
				return sprintf( '<span class="h-card p-author">%1s</span>', $author['name'] );
			case 'both':
				if ( ! empty( $author['photo'] ) ) {
					if ( empty( $author['url'] ) ) {
						return sprintf( '<span class="h-card p-author"><img src="%1s" class="u-photo" alt="%2s" width=%3s height=%4s />%5s</span>', $author['photo'], $author['name'], $args['width'], $args['height'], $author['name'] );
					} else {
						return sprintf( '<a href="%1s" class="h-card p-author"><img class="u-photo" src="%2s" alt="%3s" width=%4s height=%5s />%6s</a>', $author['url'], $author['photo'], $author['name'], $args['width'], $args['height'], $author['name'] );
					}
				} else {
					return sprintf( '<span class="h-card p-author">%1s</span>', $author['name'] );
				}
				break;
			default:
				return false;
		}
		return $card;
	}

	/**
	 * Retrieve a title for a given citation.
	 *
	 * @access public
	 *
	 * @param array $cite Array of citation data.
	 * @return bool|string
	 */
	public static function get_cite_title( $cite ) {
		if ( ! $cite ) {
			return false;
		}
		if ( empty( $cite['url'] ) ) {
			if ( ! isset( $cite['name'] ) ) {
				return '';
			}
			return sprintf( '<span class="p-name">%1s</span>', $cite['name'] );
		}
		// FIXME: Temporary Fix for array functionality
		if ( is_array( $cite['url'] ) ) {
			$cite['url'] = $cite['url'][0];
		}
		if ( ! array_key_exists( 'name', $cite ) ) {
			// $cite['name'] = self::get_post_type_string( $cite['url'] );
			$cite['name'] = $cite['url'];
		}
		return sprintf( '<a href="%1s" class="p-name u-url">%2s</a>', $cite['url'], $cite['name'] );
	}

	/**
	 * Retrieve site name for given citation.
	 *
	 * @access public
	 *
	 * @param array $cite Array of citation data.
	 * @return bool|string
	 */
	public static function get_site_name( $cite ) {
		if ( ! $cite || ! is_array( $cite ) ) {
			return false;
		}
		if ( ! array_key_exists( 'publication', $cite ) || empty( $cite['publication'] ) ) {
			return false;
		}
		return sprintf( '<span class="p-publication">%1s</span>', $cite['publication'] );
	}

	/**
	 * Returns a requested Rating item.
	 *
	 * The returned value will be a printf-ready translated string.
	 *
	 * @access
	 *
	 * @param int $rating Rating Value.
	 * @return mixed|string
	 */
	public static function rating_text( $rating ) {
		if ( ! $rating || 0 === $rating ) {
			return '';
		}

		$ret = '';
		for ( $i = $rating; $i > 0; $i-- ) {
			$ret .= '⭐';
		}

		return $ret;
	}

	/**
	 * Returns a requested RSVP option item.
	 *
	 * The returned value will be a printf-ready translated string.
	 *
	 * @access
	 *
	 * @param string $type RSVP type to return.
	 * @return mixed|string
	 */
	public static function rsvp_text( $type ) {
		if ( ! $type ) {
			return '';
		}
		$rsvp = array(
			/* translators: URL for link to event and name of event */
			'yes'        => __( 'Attending <a href="%1$s" class="u-in-reply-to">%2$s</a>', 'indieweb-post-kinds' ),
			/* translators: URL for link to event and name of event */
			'maybe'      => __( 'Might be attending <a href="%1$s" class="u-in-reply-to">%2$s</a>', 'indieweb-post-kinds' ),
			/* translators: URL for link to event and name of event */
			'no'         => __( 'Unable to Attend <a href="%1$s" class="u-in-reply-to">%2$s</a>', 'indieweb-post-kinds' ),
			/* translators: URL for link to event and name of event */
			'interested' => __( 'Interested in Attending <a href="%1$s" class=u-in-reply-to">%2$s</a>', 'indieweb-post-kinds' ),
			/* translators: URL for link to event and name of event */
			'remote'     => __( 'Attending <a href="%1$s" class="u-in-reply-to">%2$s</a> remotely', 'indieweb-post-kinds' ),
		);
		return $rsvp[ $type ];
	}

	/**
	 * Returns a requested read status option item.
	 *
	 * @access public
	 *
	 * @param string $type Read status to return.
	 * @return mixed|string
	 */
	public static function read_text( $type ) {
		if ( ! $type ) {
			return '';
		}
		$read = array(
			'to-read'  => __( 'Want to Read: ', 'indieweb-post-kinds' ),
			'reading'  => __( 'Reading: ', 'indieweb-post-kinds' ),
			'finished' => __( 'Finished Reading: ', 'indieweb-post-kinds' ),
		);
		return $read[ $type ];
	}

	/**
	 * Return a string for a requested duration.
	 *
	 * @access public
	 *
	 * @param string|Dateinterval $interval Duration to display.
	 * @return string
	 * @throws Exception
	 */
	public static function display_duration( $interval ) {
		if ( ! $interval instanceof DateInterval ) {
			$interval = new DateInterval( $interval );
		}
		if ( ! $interval ) {
			return '';
		}
		$bits     = array(
			'year'   => $interval->y,
			'month'  => $interval->m,
			'day'    => $interval->d,
			'hour'   => $interval->h,
			'minute' => $interval->i,
			'second' => $interval->s,
		);
		$duration = array();
		if ( $bits['year'] > 0 ) {
			/* translators: singular and plural */
			$duration[] = sprintf( _n( '%d year', '%d years', $bits['year'], 'indieweb-post-kinds' ), $bits['year'] );
		}
		if ( $bits['month'] > 0 ) {
			/* translators: singular and plural */
			$duration[] = sprintf( _n( '%d month', '%d months', $bits['month'], 'indieweb-post-kinds' ), $bits['month'] );
		}
		if ( $bits['day'] > 0 ) {
			/* translators: singular and plural */
			$duration[] = sprintf( _n( '%d day', '%d days', $bits['day'], 'indieweb-post-kinds' ), $bits['day'] );
		}
		if ( $bits['hour'] > 0 ) {
			/* translators: singular and plural */
			$duration[] = sprintf( _n( '%d hour', '%d hours', $bits['hour'], 'indieweb-post-kinds' ), $bits['hour'] );
		}
		if ( $bits['minute'] > 0 ) {
			/* translators: singular and plural */
			$duration[] = sprintf( _n( '%d minute', '%d minutes', $bits['minute'], 'indieweb-post-kinds' ), $bits['minute'] );
		}
		if ( $bits['second'] > 0 ) {
			/* translators: singular and plural */
			$duration[] = sprintf( _n( '%d second', '%d seconds', $bits['second'], 'indieweb-post-kinds' ), $bits['second'] );
		}

		return sprintf( '<time class="dt-duration" datetime="%1$s">%2$s</time>', date_interval_to_iso8601( $interval ), implode( ' ', $duration ) );
	}
}  // End Class
