<?php
/**
 * Global functions for registering, reading and displaying kinds.
 *
 * @package Post_Kinds
 * @since   2.0.2
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'register_post_kind' ) ) {
	/**
	 * Registers a post kind.
	 *
	 * @since 3.1.0
	 *
	 * @param string $slug Post kind slug.
	 * @param array  $args Post kind arguments. See Kind_Taxonomy::register_post_kind().
	 */
	function register_post_kind( $slug, $args ) {
		Kind_Taxonomy::register_post_kind( $slug, $args );
	}
}

if ( ! function_exists( 'set_post_kind_visibility' ) ) {
	/**
	 * Shows or hides a post kind in Settings.
	 *
	 * @since 3.1.0
	 *
	 * @param string $slug Post kind slug.
	 * @param bool   $show Optional. Whether to show the kind. Default true.
	 */
	function set_post_kind_visibility( $slug, $show = true ) {
		Kind_Taxonomy::set_post_kind_visibility( $slug, $show );
	}
}

if ( ! function_exists( 'get_post_kind_slugs' ) ) {
	/**
	 * Retrieves an array of post kind slugs.
	 *
	 * @since 0.30
	 *
	 * @return array The array of post kind slugs.
	 */
	function get_post_kind_slugs() {
		return Kind_Taxonomy::get_kind_list();
	}
}

if ( ! function_exists( 'get_post_kind_string' ) ) {
	/**
	 * Returns a pretty, translated version of a post kind slug.
	 *
	 * @since 0.30
	 *
	 * @param string $slug A post kind slug.
	 * @return string The translated post kind name, or an empty string if the kind is not registered.
	 */
	function get_post_kind_string( $slug ) {
		return Kind_Taxonomy::get_post_kind_string( $slug );
	}
}

if ( ! function_exists( 'get_post_kind_link' ) ) {
	/**
	 * Returns a link to a post kind index.
	 *
	 * @since 0.30
	 *
	 * @param string $kind The post kind slug.
	 * @return string|WP_Error|false The post kind term link, a WP_Error from get_term_link(),
	 *                               or false if the kind has no term.
	 */
	function get_post_kind_link( $kind ) {
		return Kind_Taxonomy::get_post_kind_link( $kind );
	}
}

if ( ! function_exists( 'get_post_kind_slug' ) ) {
	/**
	 * Returns the post kind slug for the current post.
	 *
	 * @since 0.30
	 *
	 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
	 * @return string|false The post kind slug, or false if the post has no kind.
	 */
	function get_post_kind_slug( $post = null ) {
		return Kind_Taxonomy::get_post_kind_slug( $post );
	}
}

if ( ! function_exists( 'get_post_kind' ) ) {
	/**
	 * Returns the post kind name for the current post.
	 *
	 * @since 0.30
	 *
	 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
	 * @return string|false The translated post kind name, or false if the post has no kind.
	 */
	function get_post_kind( $post = null ) {
		return Kind_Taxonomy::get_post_kind( $post );
	}
}

if ( ! function_exists( 'has_post_kind' ) ) {
	/**
	 * Check if a post has any of the given kinds, or any kind.
	 *
	 * @since 0.30
	 *
	 * @uses has_term()
	 *
	 * @param string|array $kinds Optional. The kind to check.
	 * @param WP_Post|int  $post  Optional. The post to check. If not supplied, defaults to the current post if used in the loop.
	 * @return bool True if the post has any of the given kinds (or any kind, if no kind specified), false otherwise.
	 */
	function has_post_kind( $kinds = array(), $post = null ) {
		return Kind_Taxonomy::has_post_kind( $kinds, $post );
	}
}

if ( ! function_exists( 'set_post_kind' ) ) {
	/**
	 * Assign a kind to a post.
	 *
	 * @since 0.30
	 *
	 * @param int|WP_Post $post The post for which to assign a kind.
	 * @param string      $kind A registered kind's slug.
	 * @return array|false|WP_Error Array of affected term IDs on success. WP_Error if the post
	 *                              or the kind is not valid, or false on failure.
	 */
	function set_post_kind( $post, $kind ) {
		return Kind_Taxonomy::set_post_kind( $post, $kind );
	}
}

if ( ! function_exists( 'get_kind_view_part' ) ) {
	/**
	 * Returns the rendered view for a kind.
	 *
	 * @since 2.5.0
	 *
	 * @param string     $slug View slug, such as 'kind'.
	 * @param string     $name Optional. Kind slug, such as 'like'. Default null.
	 * @param array|null $args Optional. Arguments for the view. See Kind_View::get_view_part(). Default null.
	 * @return string The view's markup.
	 */
	function get_kind_view_part( $slug, $name = null, $args = null ) {
		return Kind_View::get_view_part( $slug, $name, $args );
	}
}

if ( ! function_exists( 'kind_display' ) ) {
	/**
	 * Outputs the kind markup for a post.
	 *
	 * For themes that place the markup themselves instead of adding it to the content.
	 *
	 * @since 2.6.1
	 *
	 * @param int|null $post_id Optional. Post ID. Default the current post.
	 */
	function kind_display( $post_id = null ) {
			echo Kind_View::get_display( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The view escapes its output as it is built.
	}
}

if ( ! function_exists( 'kind_flatten_array' ) ) {
	/**
	 * Removes empty values from an array and unwraps single-item lists, recursively.
	 *
	 * @since 2.7.5
	 *
	 * @param mixed $a The value to flatten.
	 * @return mixed The flattened value. An empty string if nothing is left.
	 */
	function kind_flatten_array( $a ) {
		if ( ! is_array( $a ) ) {
			return $a;
		}
		if ( wp_is_numeric_array( $a ) ) {
			$a = array_values( array_filter( array_map( 'kind_flatten_array', $a ) ) );
		} else {
			$a = array_filter( $a );
		}
		if ( empty( $a ) ) {
			return '';
		}
		if ( 1 === count( $a ) && isset( $a[0] ) ) {
			return $a[0];
		}
		return $a;
	}
}

if ( ! function_exists( 'kind_src_url_in_content' ) ) {
	/**
	 * Returns every src attribute value in some markup.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The markup.
	 * @return string[]|int The src URLs, or 0 if there are none.
	 */
	function kind_src_url_in_content( $content ) {
		if ( ! $content ) {
			return 0;
		}
		if ( preg_match_all( '@src="([^"]+)"@', $content, $output ) ) {
			return array_pop( $output );
		}
		return 0;
	}
}

if ( ! function_exists( 'kind_get_the_link' ) ) {
	/**
	 * Returns a marked up link to a post, followed by its date.
	 *
	 * @since 3.5.9
	 *
	 * @param WP_Post|int|null  $post     Optional. Post to display. Default the current post.
	 * @param string|array|null $cls      Optional. Classes to add to the link. Default null.
	 * @param string|array|null $date_cls Optional. Classes to add to the date. Default null.
	 * @return string Marked up link to a post.
	 */
	function kind_get_the_link( $post = null, $cls = null, $date_cls = null ) {
		$post = get_post( $post );
		$kind = get_post_kind_slug( $post );

		if ( is_array( $cls ) ) {
			$cls = implode( ' ', $cls );
		}

		if ( is_array( $date_cls ) ) {
			$date_cls = implode( ' ', $date_cls );
		}

		$time_string = '<time class="%3$s" datetime="%1$s">%2$s</time>';
		$time_string = sprintf(
			$time_string,
			esc_attr( get_the_date( DATE_W3C, $post ) ),
			esc_html( get_the_date( '', $post ) ),
			esc_attr( $date_cls )
		);
		return sprintf( '<a class="%4$s" href="%2$s">%1$s</a> - %3$s', kind_get_the_title( $post, $kind ), esc_url( get_the_permalink( $post ) ), $time_string, esc_attr( $cls ) );
	}
}

if ( ! function_exists( 'kind_get_the_title' ) ) {
	/**
	 * Returns a post's title, generating one if the post has none.
	 *
	 * Most post kinds do not have an explicit title.
	 *
	 * @since 3.5.9
	 *
	 * @param WP_Post|int|null $post Optional. Post to display. Default the current post.
	 * @param array            $args {
	 *     Optional. Arguments.
	 *
	 *     @type int[]|string $photo_size Image size for a photo post's thumbnail. Default array( 32, 32 ).
	 * }
	 * @return string The title, or the kind's icon or name and generated text.
	 */
	function kind_get_the_title( $post = null, $args = array() ) {
		$defaults = array(
			'photo_size' => array( 32, 32 ),
		);

		$args = wp_parse_args( $args, $defaults );

		$post      = get_post( $post );
		$kind      = get_post_kind_slug( $post );
		$title     = get_the_title( $post );
		$before    = Kind_Taxonomy::get_before_kind( $kind );
		$content   = '';
		$kind_post = new Kind_Post( $post );

		if ( ! empty( $title ) ) {
			return $title;
		}

		if ( in_array( $kind, array( 'audio', 'video', 'photo' ), true ) ) {
			switch ( $kind ) {
				case 'photo':
					$photos = $kind_post->get_photo();
					$before = wp_get_attachment_image(
						$photos[0],
						$args['photo_size'],
						false,
						array(
							'class' => 'kind-photo-thumbnail',
						)
					);
			}
		} elseif ( ! in_array( $kind, array( 'note', 'article' ), true ) ) {
			$cite = $kind_post->get_cite( 'name' );
			if ( false === $cite ) {
				$content = Kind_View::get_post_type_string( $kind_post->get_cite( 'url' ) );
			} else {
				$content = $cite;
			}
		} else {
			$content = $post->post_excerpt;
			// If no excerpt use content.
			if ( ! $content ) {
				$content = $post->post_content;
			}
			// If no content use date.
			if ( $content ) {
				$content = mb_strimwidth( wp_strip_all_tags( $content ), 0, 40, '...' );
			}
		}
		if ( is_array( $content ) ) {
			$content = wp_json_encode( $content );
		}
		// The content is text (a cited name or an excerpt), often from another site.
		$content = esc_html( wp_strip_all_tags( (string) $content ) );

		/**
		 * Filters the text of a generated post title.
		 *
		 * The text is a cited name, an excerpt or similar, already escaped. A callback
		 * that adds markup must escape what it adds.
		 *
		 * @since 3.5.9
		 *
		 * @param string  $content Escaped title text.
		 * @param WP_Post $post    The post.
		 */
		$content = apply_filters( 'kind_get_the_title_content', $content, $post );
		/**
		 * Filters the markup shown before a generated post title.
		 *
		 * The markup is output as-is, so it must be escaped.
		 *
		 * @since 3.5.9
		 *
		 * @param string  $before The kind's icon or name, from Kind_Taxonomy::get_before_kind().
		 * @param WP_Post $post   The post.
		 */
		$before  = apply_filters( 'kind_get_the_title_before', $before, $post );

		return trim( sprintf( '%1$s %2$s', $before, $content ) );
	}
}
