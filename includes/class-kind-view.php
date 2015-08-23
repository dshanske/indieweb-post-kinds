<?php
/**
 * Post Kind View Class
 *
 * Handles the logic of adding the kind displays to the post content.
 */
add_action( 'init' , array( 'Kind_View', 'init' ) );

// The Kind_View class sets up the kind display behavior for kinds
class Kind_View {
	public static function init() {
		// If the Theme Has Not Declared Support for Post Kinds
		// Add the Response Display to the Content Filter
		if ( ! current_theme_supports( 'post-kinds' ) ) {
			add_filter( 'the_content', array( 'Kind_View', 'content_response' ), 20 );
		} else {
			add_filter( 'kind_response_display', array( 'Kind_View', 'content_response' ) );
		}
		add_filter( 'the_content_feed', array( 'Kind_View', 'kind_content_feed' ), 20 );

	}

	public static function sanitize_output( $content ) {
		$allowed = wp_kses_allowed_html( 'post' );
		$options = get_option( 'iwt_options' );
		if ( array_key_exists( 'contentelements',$options ) && json_decode( $options['contentelements'] ) != null ) {
			$allowed = json_decode( $options['contentelements'], true );
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

	public static function get_embed( $url ) {
			$options = get_option( 'iwt_options' );
		if ( $options['embeds'] == 0 ) {
				return '';
		}
		if ( isset( $GLOBALS['wp_embed'] ) ) {
			$embed = $GLOBALS['wp_embed']->autoembed( $url );
		}
			// Passes through the oembed handler in WordPress
			$host = extract_domain_name( $url );
		switch ( $host ) {
			case 'facebook.com':
				$embed = self::get_embed_facebook( $url );
				break;
			case 'plus.google.com':
				$embed = self::get_embed_gplus( $url );
				break;
		}
		if ( strcmp( $embed, $url ) == 0 ) {
			$embed = '';
		} else {
			$embed = '<div class="embed">' . $embed . '</div>';
		}
			return $embed;
	}

	public static function get_embed_facebook ( $url ) {
		$embed = '<div id="fb-root"></div>';
		$embed .= '<script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));</script>';
		$embed .= '<div class="fb-post" data-href="' . esc_url( $url ) . '" data-width="466"><div class="fb-xfbml-parse-ignore"><a href="' . esc_url( $url ) .  '">Post</a></div></div>';
		return $embed;
	}
	public static function get_embed_gplus ( $url ) {
		$embed = '<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>';
		$embed .= '<div class="g-post" data-href="' . esc_url( $url ) . '"></div>';
		return $embed;
	}

	public static function kind_content_feed( $content ) {
		$response = self::get_kind_response_display();
		$response = str_replace( ']]>', ']]&gt;', $response );
		return $response . $content;
	}
	public static function get_kind_response_display() {
		global $post;
		return self::get_display( $post );
	}
	public static function content_response ( $content ) {
		$c = '';
		$c .= self::get_kind_response_display();
		$c .= $content;
		return $c;
	}

	// Return the Display
	public static function get_display($post) {
		$post = get_post();
		$meta = new Kind_Meta( $post );
		$kind = get_post_kind_slug( $post );
		$response = null;
		/**
		 * Filter whether to retrieve the display early.
		 *
		 * This allows for a filter to replace the entire string.
		 *
		 *
		 * @param array	$args          The display. defaults to null.
     * @param object	$meta	   A kind_meta object.
     */
    $response = apply_filters( 'pre_get_kind_display', $response, $meta );
		if ( ! is_null( $response ) ) {
			return $response;
		}
		$response = get_post_meta( $post->ID, '_resp_full', true );
		$options = get_option( 'iwt_options' );
		$content = '';
		$final = '';
		if ( ( $options['cacher'] == 1 ) && ( ! empty( $response ) ) ) {
			return apply_filters( 'kind-response-display', $response );
		}
		$verbstrings = Kind_Taxonomy::get_verb_strings();
		if ( $kind ) {
			$verb = '<span class="verb"><strong>' . $verbstrings[ $kind ] . '</strong></span>';
		} else {
			$verb = '';
		}
		$card = self::hcards( );
		if ( ! empty( $card ) ) {
			$cards = ' ' . Kind_Taxonomy::get_author_string( $verb ) . ' ' . $card;
		} else {
			$cards = '';
		}
		$m = $meta->get_meta( );
		if ( isset( $m['url'] ) ) {
			$urlatr = array(
									'class' => array( 'u-url', 'p-name' ),
								);
			if ( isset( $m['name'] ) ) {
					$url = self::get_url_link( $m['url'], $m['name'], $urlatr );
			} else {
				$url = self::get_url_link( $m['url'], self::get_post_type_string( $m['url'] ), $urlatr );
			}
			$pub = ' ' . Kind_Taxonomy::get_publication_string( $kind ) . ' ';
			if ( isset( $m['publication'] ) ) {
				$pub .= '<em>' . $m['publication'] . '</em>';
			} else {
				$pub .= '<em>' . extract_domain_name( $m['url'] ) . '</em>';
			}
			$content = self::sanitize_output( $content ) . self::get_embed( $m['url'] );
		} else {
			$url = '';
			$pub = '';
		}
		if ( isset( $m['duration'] ) ) {
			$time = ' <em>' . Kind_Taxonomy::get_duration_string( $kind ) . ' ' . '<span class="p-duration">' . $m['duration'] . '</span></em>';
		} else {
			$time = '';
		}
		if ( isset( $m['content'] ) ) {
			$content .= '<blockquote class="e-content">' . $m['content'] . '</blockquote>';
		}
		$c = $verb . ' ' . $url . $pub . $cards . $time . $content;
		$c = trim( $c );
		if ( ! empty( $c ) ) {
			$final = '<div ' . self::context_class( 'response h-cite', 'p' ) . '>' . $c . '</div>';
		} else {
			apply_filters( 'kind-response-display', '' );
		}
		update_post_meta( $post->ID, '_resp_full', $final );
		return apply_filters( 'kind-response-display', $final );
		;
	}

	public static function get_context_class ( $class = '', $classtype='u' ) {
		$classes = array();
		global $post;
		if ( get_post_kind( $post ) ) {
			switch ( get_post_kind( $post ) ) {
				case 'like':
					$classes[] = $classtype.'-like-of';
				break;
				case 'favorite':
					$classes[] = $classtype.'-favorite-of';
				break;
				case 'repost':
					$classes[] = $classtype.'-repost-of';
				break;
				case 'reply':
					$classes[] = $classtype.'-in-reply-to';
				break;
				case 'rsvp':
					$classes[] = $classtype.'-in-reply-to';
				break;
				case 'tag':
					$classes[] = $classtype.'-tag-of';
				break;
				case 'bookmark':
					$classes[] = $classtype.'-bookmark-of';
				break;
				case 'listen':
					$classes[] = $classtype.'-listen';
				break;
				case 'watch':
					$classes[] = $classtype.'-watch';
				break;
				case 'game':
					$classes[] = $classtype.'-play';
				break;
				case 'wish':
					$classes[] = $classtype.'-wish';
				break;
			}
		}
		if ( ! empty( $class ) ) {
			if ( ! is_array( $class ) ) {
				$class = preg_split( '#\s+#', $class ); }
			$classes = array_merge( $classes, $class );
		} else {
			// Ensure that we always coerce class to being an array.
			$class = array( $class );
		}
		$classes = array_map( 'esc_attr', $classes );
		/**
	 * Filter the list of CSS kind classes for the current response URL.
	 *
	 * @param array  $classes An array of kind classes.
	 * @param string $class   A comma-separated list of additional classes added to the link.
	 * @param string $kind    The slug of the kind the post is set to
	 */
		return apply_filters( 'kind_classes', $classes, $class, get_post_kind( $post ) );
	}
	public static function context_class( $class = '', $classtype='u' ) {
		// Separates classes with a single space, collates classes
		return 'class="' . join( ' ', self::get_context_class( $class, $classtype ) ) . '"';
	}
	/**
	 * Returns an array of domains with the post type terminologies
	 *
	 * @return array A translated post type string for specific domain or 'a post'
	 */
	public static function get_post_type_string($url) {
		$strings = array(
		'twitter.com' => _x( 'a tweet', 'Post kind' ),
		'vimeo.com' => _x( 'a video', 'Post kind' ),
		'youtube.com'   => _x( 'a video', 'Post kind' ),
			'instagram.com' => _x( 'an image', 'Post kind' ),
		);
		$domain = extract_domain_name( $url );
		if ( array_key_exists( $domain, $strings ) ) {
			return apply_filters( 'kind_post_type_string', $strings[ $domain ] );
		} else {
			return _x( 'a post', 'Post kind' );
		}
	}

	public static function get_hcards() {
		global $post;
		$meta = new Kind_Meta( $post );
		$cards = $meta->get_author( );
		if ( ! $cards ) {
			return false;
		}
		$output = '';
		if ( is_multi_array( $cards ) ) {
			foreach ( $cards as $card ) {
				$output .= self::get_hcard( $card );
			}
			return $output;
		}
		return self::get_hcard( $cards );
	}
	public static function get_hcard( $card, $author = false ) {
		if ( empty( $card ) ) {
			return '';
		}
		$hcardatr = array(
									'class' => array( 'h-card' ),
								);
		if ( $author ) {
			$hcardatr['class'][] = 'p-author';
			$hcardatr['rel'][] = 'author';
		}
		$data = '';
		$name = '';
		if ( isset( $card['family-name'] ) ) {
				$name .= self::get_formatted( $card['honorific-prefix'], ' class="p-honorific-prefix"' );
				$name .= self::get_formatted( $card['given-name'], ' class="p-given-name"' );
			$name .= self::get_formatted( $card['additional-name'], ' class="p-additional-name"' );
			$name .= self::get_formatted( $card['honorific-suffix'], ' class="p-honorific-suffix"' );
		} else {
				$name .= $card['name'];
		}
		if ( ! empty( $card['photo'] ) ) {
			$data .= '<img class="u-photo" src="' . $card['photo'] . '" title="' . $card['name'] . '" />';
		}
		$data .= self::get_formatted( $name, $atr = array(
														  'class' => array( 'p-name' ),
														) );
		if ( ! empty( $card['url'] ) ) {
				$data = self::get_url_link( $card['url'], $data, array(
																													'class' => array( 'u-url' ),
																													'title' => array( $card['name'] ),
																												 ) );
		}
		foreach ( $card as $key => $value ) {
			if ( ! in_array( $key, array( 'photo', 'name', 'url' ) ) ) {
				$data .= self::get_formatted( $value, $atr = array(
																												'class' => array( $this->map_key( '$key' ) ),
																											) );
			}
		}
		$hcard = self::get_formatted( $data, $hcardatr );
		return $hcard;
	}

	public static function map_key( $key, $pre='' ) {
		if ( ! empty( $pre ) ) {
			return $pre.'-' . $key;
		}
		$p = array(
		'name',
		'honorific-prefix',
		'given-name',
		'additional-name',
		'family-name',
		'sort-string',
								'honorific-suffix',
		'nickname',
		'category',
		'adr',
		'post-office-box',
		'extended-address',
								'street-address',
		'locality',
		'region',
		'postal-code',
		'country-name',
		'label',
		'latitude',
								'longitude',
		'altitude',
		'tel',
		'note',
		'org',
		'job-title',
		'role',
		'sex',
		'gender-identity',
		);
		if ( in_array( $key, $p ) ) {
				return 'p-' . $key;
		} else {
				return 'u-' . $key;
		}
	}
	public static function hcards() {
		echo self::get_hcards();
	}

	// Echo the output of get_display
	public static function display( ) {
		echo self::get_display( );
	}

}  // End Class


function kind_response_display() {
	echo apply_filters( 'kind_response_display', '' );
}
