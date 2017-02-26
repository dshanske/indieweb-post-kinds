<?php
/**
 * Post Kind View Class
 *
 * Handles the logic of adding the kind displays to the post content.
 */

// The Kind_View class sets up the kind display behavior for kinds
class Kind_View {
	public static function init() {
			add_filter( 'the_content', array( 'Kind_View', 'content_response' ), 20 );
			add_filter( 'the_excerpt', array( 'Kind_View', 'excerpt_response' ), 20 );
	}

	public static function extract_domain_name( $url ) {
		$parse = wp_parse_url( $url, PHP_URL_HOST );
		return preg_replace( '/^www\./', '', $parse );
	}


	public static function sanitize_output( $content ) {
		$allowed = wp_kses_allowed_html( 'post' );
		$options = get_option( 'iwt_options', Kind_Config::Defaults() );
		if ( array_key_exists( 'contentelements',$options ) && json_decode( $options['contentelements'] ) != null ) {
			$allowed = json_decode( $options['contentelements'], true );
		}

		if ( ifset( $options[ 'protection' ] ) ) {
			return $content;
		}
		return wp_kses( ( string ) $content ,$allowed );
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
			$options = get_option( 'iwt_options', Kind_Config::Defaults() );
		if ( $options['embeds'] == 0 ) {
				return '';
		}
		$host = self::extract_domain_name( $url );
		$whitelist = array( 
			'animoto.com', 'blip.tv', 'cloudup.com', 'collegehumor.com', 'dailymotion.com', 'facebook.com', 'flickr.com', 'funnyordie.com', 'hulu.com', 'imgur.com', 'instagram.com', 
			'issuu.com', 'kickstarter.com', 'meetup.com', 'mixcloud.com', 'photobucket.com', 'polldaddy.com', 'reddit.com', 'reverbnation.com', 'scribd.com', 'slideshare.net', 'smugmug.com',
			'soundcloud.com', 'speakerdeck.com', 'spotify.com', 'ted.com', 'tumblr.com', 'twitter.com', 'videopress.com', 'vimeo.com', 'wordpress.tv', 'youtube.com'
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
			$embed = '<div class="embed">' . $embed . '</div>';
		}
			return $embed;
	}

	public static function content_response ( $content ) {
		return self::get_display( get_the_ID(), is_single() ) . $content;
	}


	public static function excerpt_response ( $content ) {
		global $post;
		if ( has_excerpt( get_the_ID() ) ) {
			return self::get_display( get_the_ID(), is_single() ) . get_the_excerpt();
		} else {
			return self::get_display( get_the_ID(), is_single() ) . wp_trim_words( $post->post_content );
		}
	}

	// This mirrors get_template_part but for views and locates the correct file
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
			if ( file_exists( get_template_directory() . '/kind_views/' . $template_name ) ) {
				$located = get_template_directory() . '/kind_views/' . $template_name;
				break;
			}
			// Look in the views subdirectory.
			if ( file_exists( plugin_dir_path( __FILE__ ) . 'views/' . $template_name ) ) {
				$located = plugin_dir_path( __FILE__ ) . 'views/' . $template_name;
				break;
			}
		}
		ob_start();
		include ( $located );
		$return  = ob_get_contents();
		ob_end_clean();
		return $return;
	}

	// Return the Display
	public static function get_display( $post_ID, $single = false ) {
		if ( 'post' === get_post_type( $post_ID ) ) {
			$meta = new Kind_Meta( $post_ID );
			$kind = get_post_kind_slug( $post_ID );
			$cite = $meta->get_cite();
			$hcard = 'Unknown Author';
			$content = self::get_view_part( 'kind', $kind );
			return apply_filters( 'kind-response-display', $content, $post_ID );
		}
	}

	/**
	 * Returns an array of domains with the post type terminologies
	 *
	 * @return array A translated post type string for specific domain or 'a post'
	 */
	public static function get_post_type_string($url) {
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
			'display' => 'both'
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
		if ( ! array_key_exists( 'name', $cite ) && ! empty( $url ) ) {
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


	// Echo the output of get_display
	public static function display( ) {
		echo self::get_display( );
	}

}  // End Class


function kind_response_display() {
	echo apply_filters( 'kind_response_display', '' );
}
