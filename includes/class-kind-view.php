<?php
/**
 * Post Kind View Class
 *
 * Includes Helper Functions to Set Up Display Behavior and Allows Calling of View Templates
 */

// The Kind_View class sets up the kind display behavior for kinds
class Kind_View {
	public static function init() {
		add_filter( 'the_content', array( 'Kind_View', 'content_response' ), 20 );
		add_filter( 'the_excerpt', array( 'Kind_View', 'excerpt_response' ), 20 );
		add_filter( 'wp_get_attachment_image_attributes', array( 'Kind_View', 'wp_get_attachment_image_attributes' ), 10, 2 );
	}

	public static function wp_get_attachment_image_attributes( array $attr, WP_Post $attachment ) {
		$parents = get_post_ancestors( $attachment );
		$id = $parents[ count( $parents ) -1 ];
		if ( 'photo' !== get_post_kind_slug( $id ) ) {
			return $attr;
		}
		if ( isset( $attr['class'] ) ) {
			$class = explode( ' ', $attr['class'] );
			$class[] = 'u-photo';
			$attr['class'] = implode( ' ', array_unique( $class ) );
		} else {
			$attr['class'] = 'u-photo';
		}
		return $attr;
	}

	// This mirrors get_template_part but for views and locates the correct file and returns the output
	public static function get_view_part($slug, $name = null) {
		$name = (string) $name;
		if ( '' !== $name ) {
			$templates[] = "{$slug}-{$name}.php";
		}
		$templates[] = "{$slug}.php";
		foreach ( (array) $templates as $template_name ) {
			if ( ! $template_name ) {
					continue;
			}
			// If the Theme Has a kind_views directory look there first.
			if ( file_exists( get_stylesheet_directory() . '/kind_views/' . $template_name ) ) {
				$located = get_stylesheet_directory() . '/kind_views/' . $template_name;
				break;
			}
			// Look in the views subdirectory.
			if ( file_exists( plugin_dir_path( __FILE__ ) . 'views/' . $template_name ) ) {
				$located = plugin_dir_path( __FILE__ ) . 'views/' . $template_name;
				break;
			}
		}
		ob_start();
		include( $located );
		$return  = ob_get_contents();
		ob_end_clean();
		return wp_make_content_images_responsive( $return );
	}


	// Return the Display
	public static function get_display( $post_ID = null ) {
		if ( ! $post_ID ) {
			$post_ID = get_the_ID();
		}
		if ( 'post' === get_post_type( $post_ID ) ) {
			$kind = get_post_kind_slug( $post_ID );
			$content = self::get_view_part( 'kind', $kind );
			return apply_filters( 'kind-response-display', $content, $post_ID );
		}
	}

	// Echo the output of get_display
	public static function display( $post_ID = null ) {
		echo self::get_display( $post_ID );
	}

	public static function content_response ( $content ) {
		return self::get_display( ) . $content;
	}


	public static function excerpt_response ( $content ) {
		global $post;
		if ( has_excerpt( get_the_ID() ) ) {
			return self::get_display( ) . get_the_excerpt();
		} else {
			return self::get_display( ) . wp_trim_words( $post->post_content );
		}
	}

	public static function extract_domain_name( $url ) {
		$parse = wp_parse_url( $url, PHP_URL_HOST );
		return preg_replace( '/^www\./', '', $parse );
	}


	// Take an array of attributes and output them as a string
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

	// Takes a url and returns it as marked up HTML
	public static function get_url_link( $url, $name='', $atr='' ) {
		if ( empty( $url ) ) {
			return '';
		}
		if ( is_array( $atr ) ) {
				$atr = self::get_attributes( $atr );
		}
		$return = '<a ' . $atr . ' href="' . $url . '">' . $name . '</a>';
		return $return;
	}

	public static function get_formatted( $field, $attr, $type = 'span' ) {
		if ( ! isset( $field ) ) {
			return $string;
		}
		$string = '<' . $type . $attr . '>' . $field . '</' . $type . '>';
		return $string;
	}

	public static function get_embed( $url ) {
		$option = get_option( 'kind_embeds' );
		if ( 0 == $option ) {
				return '';
		}
		$host = self::extract_domain_name( $url );
		$whitelist = array(
			'animoto.com',
		'blip.tv',
		'cloudup.com',
		'collegehumor.com',
		'dailymotion.com',
		'facebook.com',
		'flickr.com',
		'funnyordie.com',
		'hulu.com',
		'imgur.com',
		'instagram.com',
			'issuu.com',
		'kickstarter.com',
		'meetup.com',
		'mixcloud.com',
		'photobucket.com',
		'polldaddy.com',
		'reddit.com',
		'reverbnation.com',
		'scribd.com',
		'slideshare.net',
		'smugmug.com',
			'soundcloud.com',
		'speakerdeck.com',
		'spotify.com',
		'ted.com',
		'tumblr.com',
		'twitter.com',
		'videopress.com',
		'vimeo.com',
		'wordpress.tv',
		'youtube.com',
		);
		$whitelist = apply_filters( 'post_kind_embed_whitelist', $whitelist );
		if ( ! in_array( $host, $whitelist ) ) {
			return '';
		}
		if ( isset( $GLOBALS['wp_embed'] ) ) {
			$embed = $GLOBALS['wp_embed']->autoembed( $url );
		}
		if ( strcmp( $embed, $url ) == 0 ) {
			$embed = '';
		} else {
			$embed = '<div class="kind-embed">' . $embed . '</div>';
		}
			return $embed;
	}

	/**
	 * Returns an array of domains with the post type terminologies
	 *
	 * @return array A translated post type string for specific domain or 'a post'
	 */
	public static function get_post_type_string( $url ) {
		if ( ! $url ) {
			return ' ';
		}
		$strings = array(
			'twitter.com' => _x( 'a tweet', 'indieweb-post-kinds' ),
			'vimeo.com' => _x( 'a video', 'indieweb-post-kinds' ),
			'youtube.com'   => _x( 'a video', 'indieweb-post-kinds' ),
			'instagram.com' => _x( 'an image', 'indieweb-post-kinds' ),
		);
		$domain = self::extract_domain_name( $url );
		if ( array_key_exists( $domain, $strings ) ) {
			return apply_filters( 'kind_post_type_string', $strings[ $domain ] );
		} else {
			return _x( 'a post', 'indieweb-post-kinds' );
		}
	}

	/**
	 * Retrieve/Generate the h-card.
	 *
	 * @param mixed $author The author to generate Accepts an array or optionally other info
	 * @param array $args       {
	 *    Optional. Extra arguments to retrieve the avatar.
	 *
	 *     @type int          $height        Display height of the author image in pixels. Defaults to $size.
	 *     @type int          $width         Display width of the author image in pixels. Defaults to $size.
	 *     @type string       $display			 Display 'photo', 'name', or 'both'. Defaults to 'name'.
	 * }
	 * @return false|string Marked up H-Card as String. False on failure.
	 */
	public static function get_hcard($author, $args = null) {
		$default = array(
			'height' => 32,
			'width' => 32,
			'display' => 'both',
		);
		$args = wp_parse_args( $args, $default );
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
		// If no filter generated the card, generate the card.
		switch ( $args['display'] ) {
			case 'photo':
				if ( ! array_key_exists( 'photo', $author ) ) {
					return false;
				}
				if ( ! array_key_exists( 'url', $author ) ) {
					return sprintf( '<img src="%1s" class="h-card u-photo p-author" alt="%2s" width=%3s height=%4s />', $author['photo'], $author['name'], $args['width'], $args['height'] );
				} else {
					return sprintf( '<a class="h-card p-author" href="%1s"><img class="u-photo" src="%2s" alt="%3s" width=%4s height=%5s /></a>', $author['url'], $author['photo'], $author['name'], $args['width'], $args['height'] );
				}
				break;
			case 'name':
				return sprintf( '<span class="h-card p-author">%1s</span>', $author['name'] );
				break;
			case 'both':
				if ( array_key_exists( 'photo', $author ) ) {
					if ( ! array_key_exists( 'url', $author ) ) {
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

	public static function get_cite_title($cite, $url) {
		if ( ! $cite ) {
			return false;
		}
		// FIXME: Temporary Fix for array functionality
		if ( is_array( $url ) ) {
			$url = $url[0];
		}
		if ( ! array_key_exists( 'name', $cite ) ) {
			$cite['name'] = self::get_post_type_string( $url );
		}
		if ( isset( $url ) ) {
			return sprintf( '<a href="%1s" class="p-name u-url">%2s</a>', $url, $cite['name'] );
		} else {
			return sprintf( '<span class="p-name">%1s</span>', $cite['name'] );
		}
	}

	public static function get_site_name($cite, $url) {
		if ( ! $cite ) {
			return false;
		}
		if ( ! array_key_exists( 'publication', $cite ) ) {
			return false;
		}
		return sprintf( '<span class="p-publication">%1s</span>', $cite['publication'] );
	}

	public static function rsvp_text( $type ) {
		$rsvp = array(
			'yes' => __( 'Attending <a href="%1s" class="u-in-reply-to">%2s</a>', 'indieweb-post-kinds' ),
			'maybe' => __( 'Might be attending <a href="%1s" class="u-in-reply-to">%2s</a>', 'indieweb-post-kinds' ),
			'no' => __( 'Unable to Attend <a href="%1s" class="u-in-reply-to">%2s</a>', 'indieweb-post-kinds' ),
			'interested' => __( 'Interested in Attending %s', 'indieweb-post-kinds' ),
		);
		return $rsvp[ $type ];
	}

	public static function display_duration( $duration ) {
		if ( ! $duration ) {
			return '';
		}
		$interval = new DateInterval( $duration );
		$bits = array(
			'year'    => $interval->y,
			'month'   => $interval->m,
			'day' 	  => $interval->d,
			'hour'    => $interval->h,
			'minute'  => $interval->i,
			'second'  => $interval->s,
		);
		$return = '';
		if ( $bits['year'] > 0 ) {
			$return .= sprintf( _n( '%d year', '%d years', $bits['year'], 'indieweb-post-kinds' ), $bits['year'] );
		}
		if ( $bits['month'] > 0 ) {
			$return .= sprintf( _n( ' %d month', ' %d months', $bits['month'], 'indieweb-post-kinds' ), $bits['month'] );
		}
		if ( $bits['day'] > 0 ) {
			$return .= sprintf( _n( ' %d day', ' %d days', $bits['day'], 'indieweb-post-kinds' ), $bits['day'] );
		}
		if ( $bits['hour'] > 0 ) {
			$return .= sprintf( _n( ' %d hour', ' %d hours', $bits['hour'], 'indieweb-post-kinds' ), $bits['hour'] );
		}
		if ( $bits['minute'] > 0 ) {
			$return .= sprintf( _n( ' %d minute', ' %d minutes', $bits['minute'], 'indieweb-post-kinds' ), $bits['minute'] );
		}
		if ( $bits['second'] > 0 ) {
			$return .= sprintf( _n( ' %d second', ' %d seconds', $bits['second'], 'indieweb-post-kinds' ), $bits['second'] );
		}
		return trim( $return );
	}

}  // End Class
