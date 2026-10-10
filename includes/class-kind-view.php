<?php
/**
 * Kind_View class.
 *
 * @package Post_Kinds
 * @since   2.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sets up how kinds are displayed, and locates and renders the view templates.
 *
 * @since 2.0.2
 */
class Kind_View {
	/**
	 * Adds the hooks that display kind markup.
	 *
	 * @since 2.0.2
	 */
	public static function init() {

		/**
		 * Filters whether to add response markup to post content, excerpts and feeds automatically.
		 *
		 * Return false to place it in the theme instead, for example with
		 * Kind_View::get_display().
		 *
		 * @since 3.6.0
		 *
		 * @param bool $display Whether to add it. Default true.
		 */
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
	 * @since 2.6.1
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

	/**
	 * Finds the view file for a kind, in the theme or the plugin.
	 *
	 * @since 3.4.0
	 *
	 * @param string $slug View slug, such as 'kind'.
	 * @param string $name Kind slug, such as 'like'.
	 * @return string|null Path of the view file, an empty string if no kind is given,
	 *                     or null if no view file exists.
	 */
	public static function locate_view( $slug, $name ) {
		$name = (string) $name;
		if ( empty( $name ) ) {
			return '';
		}
		$templates   = array( "{$slug}-{$name}.php", "{$slug}.php" );
		/**
		 * Filters the folders searched for view files before the plugin's own views folder.
		 *
		 * @since 3.3.0
		 *
		 * @param string[] $paths Folder paths, each with a trailing slash. Default the theme's kind_views folder.
		 */
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
	 * @since 2.4.0
	 *
	 * @access public
	 *
	 * @param string $slug Post kind slug.
	 * @param string $name Post kind term name.
	 * @param array  $args Optional. Arguments for the view. 'post_id' is the post's ID.
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
	 * @since 2.1.1
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
			$content = self::get_view_part( 'kind', $kind, array( 'post_id' => $post_id ) );
			/**
			 * Filters the response markup for a post.
			 *
			 * @since 2.0.2
			 *
			 * @param string $content Rendered view markup.
			 * @param int    $post_id Post ID.
			 */
			return apply_filters( 'kind_response_display', $content, $post_id );
		}
	}

	/**
	 * Echo the output of get_display.
	 *
	 * @since 2.1.1
	 *
	 * @access public
	 * @param int|null $post_id Post ID.
	 */
	public static function display( $post_id = null ) {
		echo self::get_display( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The view escapes its output as it is built.
	}

	/**
	 * Output the post kind content to the post content.
	 *
	 * @since 2.0.2
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
		// Don't allow to be added to the_content more than once (prevent infinite loops).
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
	 * @since 3.0.5
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
	 * @since 3.0.5
	 *
	 * @access public
	 *
	 * @param array   $feed_item Post content for the JSON feed item.
	 * @param WP_Post $post      Post object.
	 * @return mixed
	 */
	public static function json_feed_item( $feed_item, $post ) {

		$kind_post = new Kind_Post( $post );
		$cite      = \ParseThis\mf2_to_jf2( $kind_post->get_cite() );

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
	 * @since 2.3.3
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
	 * @since 2.3.7
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
	 * @since 2.1.1
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
	 * @since 2.1.1
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
		$return = '<a ' . $atr . ' href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
		return $return;
	}

	/**
	 * Create formatted HTML output for a field.
	 *
	 * @since 2.2.0
	 *
	 * @access public
	 *
	 * @param string $field Content to put in the markup.
	 * @param string $attr  Attributes to add to the tag markup.
	 * @param string $type  HTML tag type to create. Default span.
	 *
	 * @return string
	 */
	public static function get_formatted( $field, $attr, $type = 'span' ) {
		if ( ! isset( $field ) ) {
			return '';
		}
		$type   = tag_escape( $type );
		$string = '<' . $type . $attr . '>' . $field . '</' . $type . '>';
		return $string;
	}

	/**
	 * Return post kind-wrapped oEmbed content for a provided URL.
	 *
	 * @since 2.1.1
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
		/**
		 * Filters the domains whose cited URLs are embedded with oEmbed.
		 *
		 * @since 3.3.6
		 *
		 * @param string[] $approvelist Domain names.
		 */
		$approvelist = apply_filters( 'post_kind_embed_approvelist', $approvelist );
		if ( ! in_array( $host, $approvelist, true ) ) {
			return '';
		}
		if ( ! isset( $GLOBALS['wp_embed'] ) ) {
			return '';
		}
		$embed = $GLOBALS['wp_embed']->autoembed( $url );
		if ( 0 === strcmp( $embed, $url ) ) {
			$embed = '';
		} else {
			// The embed is the oEmbed markup WordPress generated.
			$embed = sprintf( '<div class="kind-embed">%1$s<a class="u-url" href="%2$s"></a></div>', $embed, esc_url( $url ) );
		}
			return $embed;
	}

	/**
	 * Returns an array of domains with the post type terminologies
	 *
	 * @since 2.1.1
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
			/**
			 * Filters the phrase for what a known site's URL points to, such as 'a video' for YouTube.
			 *
			 * It runs only for the sites listed above; other URLs get "a post".
			 *
			 * @since 2.0.2
			 *
			 * @param string $string Translated phrase.
			 */
			return apply_filters( 'kind_post_type_string', $strings[ $domain ] );
		} else {
			return _x( 'a post', 'singular post', 'indieweb-post-kinds' );
		}
	}

	/**
	 * Retrieve/Generate the h-card.
	 *
	 * @since 2.1.1
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
		 * Filters the author data before it is turned into an h-card.
		 *
		 * Use it to look up author details another way, for example from a URL
		 * or Gravatar.
		 *
		 * @since 2.3.0
		 *
		 * @param mixed $author Author data, usually an array with name, url and photo.
		 * @param array $args   Arguments passed to Kind_View::get_hcard().
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
		 * Filters the h-card markup for an author.
		 *
		 * Return non-empty markup to replace the plugin's own h-card. The markup is
		 * output as-is, so it must be escaped.
		 *
		 * @since 2.3.0
		 *
		 * @param string $card   H-card markup. Default empty, which uses the plugin's markup.
		 * @param array  $author Author data, with name, url and photo.
		 * @param array  $args   Arguments passed to Kind_View::get_hcard().
		 */
		$card = apply_filters( 'get_hcard', '', $author, $args );
		if ( ! empty( $card ) ) {
			return $card;
		}
		// Temporarily drop multi-data on display.
		foreach ( array( 'name', 'url', 'photo' ) as $key ) {
			$value          = isset( $author[ $key ] ) ? $author[ $key ] : '';
			$author[ $key ] = is_array( $value ) ? (string) reset( $value ) : (string) $value;
		}

		// Escape everything here: author data often comes from other sites.
		$author['url']   = esc_url( $author['url'] );
		$author['photo'] = esc_url( $author['photo'] );
		if ( '' === trim( wp_strip_all_tags( $author['name'] ) ) && ! empty( $author['url'] ) ) {
			$author['name'] = __( 'an author', 'indieweb-post-kinds' );
		}
		$author['name']  = esc_html( wp_strip_all_tags( $author['name'] ) );
		$args['width']   = absint( $args['width'] );
		$args['height']  = absint( $args['height'] );

		// If no filter generated the card, generate the card.
		switch ( $args['display'] ) {
			case 'photo':
				if ( empty( $author['photo'] ) ) {
					return false;
				}
				if ( empty( $author['url'] ) ) {
					return sprintf( '<img src="%1$s" class="h-card u-photo p-author" alt="%2$s" width="%3$d" height="%4$d" />', $author['photo'], $author['name'], $args['width'], $args['height'] );
				} else {
					return sprintf( '<a class="h-card p-author" href="%1$s"><img class="u-photo" src="%2$s" alt="%3$s" width="%4$d" height="%5$d" /></a>', $author['url'], $author['photo'], $author['name'], $args['width'], $args['height'] );
				}
				break;
			case 'name':
				return sprintf( '<span class="h-card p-author">%1$s</span>', $author['name'] );
			case 'both':
				if ( ! empty( $author['photo'] ) ) {
					if ( empty( $author['url'] ) ) {
						return sprintf( '<span class="h-card p-author"><img src="%1$s" class="u-photo" alt="%2$s" width="%3$d" height="%4$d" />%5$s</span>', $author['photo'], $author['name'], $args['width'], $args['height'], $author['name'] );
					} else {
						return sprintf( '<a href="%1$s" class="h-card p-author"><img class="u-photo" src="%2$s" alt="%3$s" width="%4$d" height="%5$d" />%6$s</a>', $author['url'], $author['photo'], $author['name'], $args['width'], $args['height'], $author['name'] );
					}
				} else {
					return sprintf( '<span class="h-card p-author">%1$s</span>', $author['name'] );
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
	 * @since 2.3.0
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
		// FIXME: Temporary Fix for array functionality.
		$url  = isset( $cite['url'] ) ? $cite['url'] : '';
		$url  = esc_url( is_array( $url ) ? (string) reset( $url ) : (string) $url );
		$name = isset( $cite['name'] ) ? $cite['name'] : null;
		$name = is_array( $name ) ? (string) reset( $name ) : $name;
		if ( empty( $url ) ) {
			if ( null === $name ) {
				return '';
			}
			return sprintf( '<span class="p-name">%1$s</span>', esc_html( wp_strip_all_tags( (string) $name ) ) );
		}
		if ( null === $name || '' === $name ) {
			$name = $url;
		}
		return sprintf( '<a href="%1$s" class="p-name u-url">%2$s</a>', $url, esc_html( wp_strip_all_tags( (string) $name ) ) );
	}

	/**
	 * Retrieve site name for given citation.
	 *
	 * @since 2.3.0
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
		$publication = is_array( $cite['publication'] ) ? (string) reset( $cite['publication'] ) : (string) $cite['publication'];
		return sprintf( '<span class="p-publication">%1$s</span>', esc_html( wp_strip_all_tags( $publication ) ) );
	}

	/**
	 * Returns the citation's summary as escaped plain text.
	 *
	 * Summaries are plain text: tags are stripped, and the text is marked up as
	 * p-summary. Line breaks are kept.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 *
	 * @param array $cite Normalized citation (see Kind_Post::normalize_cite()).
	 * @return string The summary markup, or an empty string if there is none.
	 */
	public static function get_summary( $cite ) {
		if ( ! is_array( $cite ) || empty( $cite['summary'] ) ) {
			return '';
		}
		$summary = $cite['summary'];
		if ( is_array( $summary ) ) {
			foreach ( array( 'value', 'text', 'html' ) as $key ) {
				if ( isset( $summary[ $key ] ) ) {
					$summary = $summary[ $key ];
					break;
				}
			}
			$summary = is_array( $summary ) ? (string) reset( $summary ) : $summary;
		}
		$summary = trim( wp_strip_all_tags( (string) $summary ) );
		if ( '' === $summary ) {
			return '';
		}
		return sprintf( '<blockquote class="p-summary">%1$s</blockquote>', nl2br( esc_html( $summary ) ) );
	}

	/**
	 * Returns a rating as a row of stars.
	 *
	 * @since 3.7.0
	 *
	 * @access public
	 *
	 * @param int|string $rating Rating value. Values above 10 show 10 stars.
	 * @return string Plain text: one star per point, or an empty string.
	 */
	public static function rating_text( $rating ) {
		$rating = is_numeric( $rating ) ? (int) $rating : 0;
		if ( $rating <= 0 ) {
			return '';
		}
		return str_repeat( '⭐', min( $rating, 10 ) );
	}

	/**
	 * Returns the text for an RSVP.
	 *
	 * Called with only a type, it returns a printf-ready string whose
	 * %1$s and %2$s are the event URL and name, as before 4.0.0; the caller
	 * must escape both. With a URL or name, it returns the escaped sentence
	 * with the event linked.
	 *
	 * @since 2.6.1
	 *
	 * @access public
	 *
	 * @param string      $type RSVP value: yes, no, maybe, interested or remote.
	 * @param string|null $url  Optional. Event URL.
	 * @param string|null $name Optional. Event name. Defaults to the URL.
	 * @return string The RSVP text, or an empty string for an unknown type.
	 */
	public static function rsvp_text( $type, $url = null, $name = null ) {
		$rsvp = array(
			/* translators: %s: Link to the event */
			'yes'        => __( 'Attending %s', 'indieweb-post-kinds' ),
			/* translators: %s: Link to the event */
			'maybe'      => __( 'Might be attending %s', 'indieweb-post-kinds' ),
			/* translators: %s: Link to the event */
			'no'         => __( 'Unable to Attend %s', 'indieweb-post-kinds' ),
			/* translators: %s: Link to the event */
			'interested' => __( 'Interested in Attending %s', 'indieweb-post-kinds' ),
			/* translators: %s: Link to the event */
			'remote'     => __( 'Attending %s remotely', 'indieweb-post-kinds' ),
		);
		if ( ! is_string( $type ) || ! array_key_exists( $type, $rsvp ) ) {
			return '';
		}
		if ( null === $url && null === $name ) {
			return sprintf( esc_html( $rsvp[ $type ] ), '<a href="%1$s" class="u-in-reply-to">%2$s</a>' );
		}
		$url  = esc_url( (string) $url );
		$name = ( null === $name || '' === $name ) ? $url : $name;
		$name = esc_html( wp_strip_all_tags( (string) $name ) );
		if ( '' === $url ) {
			$link = sprintf( '<span class="p-in-reply-to">%1$s</span>', $name );
		} else {
			$link = sprintf( '<a href="%1$s" class="u-in-reply-to">%2$s</a>', $url, $name );
		}
		return sprintf( esc_html( $rsvp[ $type ] ), $link );
	}

	/**
	 * Returns a requested read status option item.
	 *
	 * @since 3.0.7
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
		return ( is_string( $type ) && isset( $read[ $type ] ) ) ? $read[ $type ] : '';
	}

	/**
	 * Return a string for a requested duration.
	 *
	 * @since 2.6.1
	 *
	 * @access public
	 *
	 * @param string|Dateinterval $interval Duration to display.
	 * @return string The duration markup, or an empty string if it is not a valid duration.
	 */
	public static function display_duration( $interval ) {
		$interval = kind_safe_interval( $interval );
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
