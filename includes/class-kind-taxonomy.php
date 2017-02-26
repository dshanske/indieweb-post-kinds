<?php

/**
 * Post Kind Taxonomy Class
 *
 * Registers the taxonomy and sets its behavior.
 *
 * @package Post Kinds
 */
class Kind_Taxonomy {
	public static function init() {

		// Add Kind Permalinks.
		add_filter( 'post_link', array( 'Kind_Taxonomy', 'kind_permalink' ) , 10, 3 );
		add_filter( 'post_type_link', array( 'Kind_Taxonomy', 'kind_permalink' ) , 10 , 3 );

		// Add Classes to Post and Body.
		add_filter( 'post_class', array( 'Kind_Taxonomy', 'post_class' ) );
		add_filter( 'body_class', array( 'Kind_Taxonomy', 'body_class' ) );

		// Trigger Webmention on Change in Post Status.
		add_filter( 'transition_post_status', array( 'Kind_Taxonomy', 'transition' ), 10, 3 );
		// On Post Publish Invalidate any Stored Response.
		add_action( 'save_post', array( 'Kind_Taxonomy', 'post_formats' ), 99, 3 );

		// Add the Correct Archive Title to Kind Archives.
		add_filter( 'get_the_archive_title', array( 'Kind_Taxonomy', 'kind_archive_title' ) , 10 , 3 );

		// Set Post Kind for Micropub Inputs.
		add_action( 'after_micropub', array( 'Kind_Taxonomy', 'micropub_set_kind' ), 10, 2 );

		// Override Post Type in Semantic Linkbacks.
		add_filter( 'semantic_linkbacks_post_type', array( 'Kind_Taxonomy', 'semantic_post_type' ), 11, 2 );

		// Add Links to Ping to the Webmention Sender.
		add_filter( 'webmention_links', array( 'Kind_Taxonomy', 'webmention_links' ), 11, 2 );

		// Remove the Automatic Post Generation that the Micropub Plugin Offers
		remove_filter( 'before_micropub', array( 'Micropub', 'generate_post_content' ) );
	}


	/**
	 * Sets Post Format for Post Kind.
	 *
	 * @param int $post_ID Post ID
	 * @param WP_Post $post Post Object
	 * @param boolean $update, 
	 */
	public static function post_formats( $post_ID, $post, $update ) {
		$kind = get_post_kind_slug( $post_ID );
		switch ( $kind ) {
			case 'note':
				set_post_format( $post_ID, 'aside' );
				break;
			case 'article':
				set_post_format( $post_ID, '' );
				break;
			case 'favorite':
			case 'bookmark':
			case 'like':
			case 'reply':
				set_post_format( $post_ID, 'link' );
				break;
			case 'quote':
				set_post_format( $post_ID, 'quote' );
				break;
			case 'listen':
			case 'jam':
				set_post_format( $post_ID, 'audio');
				break;
			case 'watch':
				set_post_format( $post_ID, 'video');
				break;
			case 'photo':
				set_post_format( $post_ID, 'image' );
				break;
			case 'play':
			case 'read':
			case 'rsvp':
				set_post_format( $post_ID, 'status');
				break;
		}
	}

	/**
	 * To Be Run on Plugin Activation.
	 */
	public static function activate_kinds() {
		if ( function_exists( 'iwt_plugin_notice' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'You have Indieweb Taxonomy activated. Post Kinds replaces this plugin. Please disable Taxonomy before activating' );
		}
		self::register();
		self::kind_defaultterms();
	}

	/**
	 * Register the custom taxonomy for kinds.
	 */
	public static function register() {
		$labels = array(
			'name' => _x( 'Kinds', 'indieweb-post-kinds' ),
			'singular_name' => _x( 'Kind', 'indieweb-post-kinds' ),
			'search_items' => _x( 'Search Kinds', 'indieweb-post-kinds' ),
			'popular_items' => _x( 'Popular Kinds', 'indieweb-post-kinds' ),
			'all_items' => _x( 'All Kinds', 'indieweb-post-kinds' ),
			'parent_item' => _x( 'Parent Kind', 'indieweb-post-kinds' ),
			'parent_item_colon' => _x( 'Parent Kind:', 'indieweb-post-kinds' ),
			'edit_item' => _x( 'Edit Kind', 'indieweb-post-kinds' ),
			'view_item' => _x( 'View Kind', 'indieweb-post-kinds' ),
			'update_item' => _x( 'Update Kind', 'indieweb-post-kinds' ),
			'add_new_item' => _x( 'Add New Kind', 'indieweb-post-kinds' ),
			'new_item_name' => _x( 'New Kind', 'indieweb-post-kinds' ),
			'separate_items_with_commas' => _x( 'Separate kinds with commas', 'indieweb-post-kinds' ),
			'add_or_remove_items' => _x( 'Add or remove kinds', 'indieweb-post-kinds' ),
			'choose_from_most_used' => _x( 'Choose from the most used kinds', 'indieweb-post-kinds' ),
			'not found' => _x( 'No kinds found', 'indieweb-post-kinds' ),
			'no_terms' => _x( 'No kinds', 'indieweb-post-kinds' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'hierarchical' => false,
			'show_ui' => WP_DEBUG,
			'show_in_menu' => WP_DEBUG,
			'show_in_nav_menu' => true,
			'show_in_rest' => false,
			'show_tagcloud' => true,
			'show_in_quick_edit' => false,
			'show_admin_column' => true,
			'meta_box_cb' => array( 'Kind_Taxonomy', 'select_metabox' ),
			'rewrite' => true,
			'query_var' => true,
		);
		register_taxonomy( 'kind', array( 'post' ), $args );
	}

	/**
	 * Sets up Default Terms for Kind Taxonomy.
	 */
	public static function kind_defaultterms () {
		$terms = self::get_strings();
		foreach ( $terms as $key => $value ) {
			if ( ! term_exists( $key, 'kind' ) ) {
				wp_insert_term( $key, 'kind',
					array(
					'description' => $value,
					'slug' => $key,
				) );
			}
		}
	}
	public static function kind_permalink($permalink, $post_id, $leavename) {
		if ( false === strpos( $permalink, '%kind%' ) ) { return $permalink; }

		// Get post
		$post = get_post( $post_id );
		if ( ! $post ) { return $permalink; }

		// Get taxonomy terms
		$terms = wp_get_object_terms( $post->ID, 'kind' );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) && is_object( $terms[0] ) ) { $taxonomy_slug = $terms[0]->slug;
		} else { $taxonomy_slug = 'note'; }
		return str_replace( '%kind%', $taxonomy_slug, $permalink );
	}
	public static function kind_archive_title($title) {
		$strings = self::get_strings_plural();
		if ( is_tax( 'kind' ) ) {
			foreach ( $strings as $key => $string ) {
				if ( is_tax( 'kind', $key ) ) {
					$title = $string;
					return $title;
				}
			}
		}
		return $title;
	}

	public static function select_metabox( $post ) {
		$strings = self::get_strings();
		$option = get_option( 'iwt_options' );
		$include = array();
		if ( array_key_exists( 'termslist', $option ) ) {
			$include = $option['termslist'];
		}
		$include = array_merge( $include, array( 'note', 'reply', 'article' ) );
		// If Simple Location is Enabled, include the check-in type
		// Filter Kinds
		$include = array_unique( apply_filters( 'kind_include', $include ) );
		// Note cannot be removed or disabled without hacking the code
		if ( ! in_array( 'note', $include ) ) {
			$include[] = 'note';
		}
		if ( isset( $_GET['kind'] ) ) {
			$default = get_term_by( 'slug', $_GET['kind'], 'kind' );
		} else {
			$default = get_term_by( 'slug', $option['defaultkind'], 'kind' );
		}
		$terms = get_terms( 'kind', array( 'hide_empty' => 0 ) );
		$postterms = get_the_terms( $post->ID, 'kind' );
		$current = ($postterms ? array_pop( $postterms ) : false);
		$current = ($current ? $current->term_id : $default->term_id);
		echo '<div id="kind-all">';
		echo '<ul id="taxonomy-kind" class="list:kind category-tabs form-no-clear">';
		foreach ( $terms as $term ) {
			$id = 'kind-' . $term->term_id;
			$slug = $term->slug;
			if ( in_array( $slug, $include ) ) {
				echo "<li id='$id' class='kind-$slug'><label class='selectit'>";
				echo "<input type='radio' id='in-$id' name='tax_input[kind]'".checked( $current,$term->term_id,false )."value='$slug' />";
				echo self::get_icon( $slug );
				echo "$strings[$slug]<br />";
				echo '</label></li>';

			}
		}
		echo '</ul></div>';
	}

	/**
	 * Returns an array of post kind slugs to their translated and pretty display versions
	 *
	 * @return array The array of translated post kind names.
	 */
	public static function get_strings() {
		$strings = array(
			'article' => _x( 'Article', 'indieweb-post-kinds' ),
			'note'    => _x( 'Note',    'indieweb-post-kinds' ),
			'reply'     => _x( 'Reply',     'indieweb-post-kinds' ),
			'repost'  => _x( 'Repost',  'indieweb-post-kinds' ),
			'like'     => _x( 'Like',     'indieweb-post-kinds' ),
			'favorite'    => _x( 'Favorite',    'indieweb-post-kinds' ),
			'bookmark'    => _x( 'Bookmark',    'indieweb-post-kinds' ),
			'photo'   => _x( 'Photo',   'indieweb-post-kinds' ),
			'tag'    => _x( 'Tag',    'indieweb-post-kinds' ),
			'rsvp'    => _x( 'RSVP',    'indieweb-post-kinds' ),
			'listen'   => _x( 'Listen', 'indieweb-post-kinds' ),
			'watch'   => _x( 'Watch', 'indieweb-post-kinds' ),
			'checkin'   => _x( 'Checkin', 'indieweb-post-kinds' ),
			'wish'   => _x( 'Wish', 'indieweb-post-kinds' ),
			'play'   => _x( 'Play', 'indieweb-post-kinds' ),
			'weather'   => _x( 'Weather', 'indieweb-post-kinds' ),
			'exercise'   => _x( 'Exercise', 'indieweb-post-kinds' ),
			'trip'   => _x( 'Travel', 'indieweb-post-kinds' ),
			'itinerary' => _x( 'Itinerary', 'indieweb-post-kinds' ),
		'eat'   => _x( 'Eat', 'indieweb-post-kinds' ),
		'drink'   => _x( 'Drink', 'indieweb-post-kinds' ),
		'follow'   => _x( 'Follow', 'indieweb-post-kinds' ),
		'jam'   => _x( 'Jam', 'indieweb-post-kinds' ),
		'read' => _x( 'Read', 'indieweb-post-kinds' ),
		'quote' => _x( 'Quote', 'indieweb-post-kinds' ),
		'mood' => _x( 'Mood', 'indieweb-post-kinds' ),
		'recipe' => _x( 'Recipe', 'indieweb-post-kinds' ),
		);
		return apply_filters( 'kind_strings', $strings );
	}

	/**
	 * Returns an array of post kind slugs to their pluralized translated and pretty display versions
	 *
	 * @return array The array of translated post kind names.
	 */
	public static function get_strings_plural() {
		$strings = array(
			'article' => _x( 'Articles', 'indieweb-post-kinds' ),
			'note'    => _x( 'Notes',    'indieweb-post-kinds' ),
			'reply'     => _x( 'Replies',     'indieweb-post-kinds' ),
			'repost'  => _x( 'Reposts',  'indieweb-post-kinds' ),
			'like'     => _x( 'Likes',     'indieweb-post-kinds' ),
			'favorite'    => _x( 'Favorites',    'indieweb-post-kinds' ),
			'bookmark'    => _x( 'Bookmarks',    'indieweb-post-kinds' ),
			'photo'   => _x( 'Photos',   'indieweb-post-kinds' ),
			'tag'    => _x( 'Tags',    'indieweb-post-kinds' ),
			'rsvp'    => _x( 'RSVPs',    'indieweb-post-kinds' ),
			'listen'   => _x( 'Listens', 'indieweb-post-kinds' ),
			'watch'   => _x( 'Watches', 'indieweb-post-kinds' ),
			'checkin'   => _x( 'Checkins', 'indieweb-post-kinds' ),
			'wish'   => _x( 'Wishlist', 'indieweb-post-kinds' ),
			'play'   => _x( 'Plays', 'indieweb-post-kinds' ),
			'weather'   => _x( 'Weather', 'indieweb-post-kinds' ),
			'exercise'   => _x( 'Exercises', 'indieweb-post-kinds' ),
			'trip'   => _x( 'Travels', 'indieweb-post-kinds' ),
			'itinerary' => _x( 'Itineraries', 'indieweb-post-kinds' ),
		'eat'   => _x( 'Eat', 'indieweb-post-kinds' ),
		'drink'   => _x( 'Drinks', 'indieweb-post-kinds' ),
			'follow'   => _x( 'Follows', 'indieweb-post-kinds' ),
		'jam'   => _x( 'Jams', 'indieweb-post-kinds' ),
		'read' => _x( 'Read', 'indieweb-post-kinds' ),
		'quote' => _x( 'Quotes', 'indieweb-post-kinds' ),
		'mood' => _x( 'Moods', 'indieweb-post-kinds' ),
		'recipe' => _x( 'Recipes', 'indieweb-post-kinds' ),
		);
		return apply_filters( 'kind_strings_plural', $strings );
	}

	/**
	 * Returns an array of post kind slugs to their translated verbs
	 *
	 * @return array The array of translated post kind verbs.
	 */
	public static function get_verb_strings() {
		$strings = array(
			'article' => _x( ' ', 'indieweb-post-kinds' ),
			'note'    => _x( ' ',    'indieweb-post-kinds' ),
			'reply'     => _x( 'In Reply To',     'indieweb-post-kinds' ),
			'repost'  => _x( 'Reposted',  'indieweb-post-kinds' ),
			'like'     => _x( 'Liked',     'indieweb-post-kinds' ),
			'favorite'    => _x( 'Favorited',    'indieweb-post-kinds' ),
			'bookmark'    => _x( 'Bookmarked',    'indieweb-post-kinds' ),
			'photo'   => _x( ' ',   'indieweb-post-kinds' ),
			'tag'    => _x( 'Tagged',    'indieweb-post-kinds' ),
			'rsvp'    => _x( 'RSVPed',    'indieweb-post-kinds' ),
			'listen'    => _x( 'Listened to ',    'indieweb-post-kinds' ),
			'watch'   => _x( 'Watched', 'indieweb-post-kinds' ),
			'checkin'   => _x( 'Checked In At', 'indieweb-post-kinds' ),
			'wish'   => _x( 'Desires', 'indieweb-post-kinds' ),
			'play'   => _x( 'Played', 'indieweb-post-kinds' ),
			'weather'   => _x( 'Weathered', 'indieweb-post-kinds' ),
			'exercise'   => _x( 'Exercised', 'indieweb-post-kinds' ),
			'trip'   => _x( 'Traveled', 'indieweb-post-kinds' ),
			'itinerary' => _x( 'Traveled', 'indieweb-post-kinds' ),
		'eat'   => _x( 'Ate', 'indieweb-post-kinds' ),
		'drink'   => _x( 'Drank', 'indieweb-post-kinds' ),
		'follow'   => _x( 'Followed', 'indieweb-post-kinds' ),
		'jam'   => _x( 'Listened to', 'indieweb-post-kinds' ),
		'read' => _x( 'Is Reading', 'indieweb-post-kinds' ),
		'quote' => _x( 'Quoted', 'indieweb-post-kinds' ),
		'mood' => _x( 'Felt', 'indieweb-post-kinds' ),
		'recipe' => _x( 'Cooked', 'indieweb-post-kinds' ),

		);
		return apply_filters( 'kind_verbs', $strings );
	}

	/**
	 * Returns an array of properties associated with kinds.
	 *
	 * @return array The array of properties.
	 */
	public static function get_kind_properties() {
		$strings = array(
		'article' => '',
		'note'    => '',
		'reply'     => 'in-reply-to',
		'repost'  => 'repost-of',
		'like'     => 'like-of',
		'favorite'    => 'favorite-of',
		'bookmark'    => 'bookmark-of',
		'photo'   => 'photo',
		'tag'    => 'tag',
		'rsvp'    => 'rsvp',
		'listen'    => 'listen',
		'watch'   => 'watch',
		'checkin'   => 'checkin',
		'wish'   => 'wish',
		'play'   => 'play',
		'weather'   => 'weather',
		'exercise'   => 'exercise',
		'trip'   => 'trip',
		'itinerary' => 'itinerary',
		'eat'   => 'p3k-food',
		'drink'   => 'p3k-food',
		'follow'   => 'u-follow-of',
		'jam'   => 'jam',
		'read' => 'read',
		'quote' => 'u-quotation-of',
		'mood' => 'mood',
		'recipe' => 'recipe',
		);
		return apply_filters( 'kind_properties', $strings );
	}

	// Replaces need for Replacing the Entire Excerpt
	public static function semantic_post_type($post_type, $post_id) {
		return _x( 'this', 'indieweb-post-kinds' ) . ' ' . strtolower( get_post_kind( $post_id ) );
	}

	// Replacement for the Semantic Linkbacks Comment Excerpt
	public static function comment_text_excerpt($text, $comment = null, $args = array()) {
		// only change text for pingbacks/trackbacks/webmentions
		if ( ! $comment || '' === $comment->comment_type || ! get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_canonical', true ) ) {
			return $text;
		}
		// check comment type
		$comment_type = get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_type', true );
		if ( ! $comment_type || ! in_array( $comment_type, array_keys( SemanticLinkbacksPlugin::get_comment_type_strings() ) ) ) {
			$comment_type = 'mention';
		}
		$_kind = get_the_terms( $comment->comment_post_ID, 'kind' );
		if ( ! empty( $_kind ) ) {
			$kind = array_shift( $_kind );
			$kindstrings = self::get_strings();
			$post_format = $kindstrings[ $kind->slug ];
		} else {
			$post_format = get_post_format( $comment->comment_post_ID );
			// replace "standard" with "Article"
			if ( ! $post_format || 'standard' === $post_format ) {
				$post_format = 'Article';
			} else {
				$post_formatstrings = get_post_format_strings();
				// get the "nice" name
				$post_format = $post_formatstrings[ $post_format ];
			}
		}
		// generate the verb, for example "mentioned" or "liked"
		$comment_type_excerpts = SemanticLinkbacksPlugin::get_comment_type_excerpts();
		// get URL canonical url...
		$url = get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_canonical', true );
		// ...or fall back to source
		if ( ! $url ) {
			$url = get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_source', true );
		}
		// parse host
		$host = parse_url( $url, PHP_URL_HOST );
		// strip leading www, if any
		$host = preg_replace( '/^www\./', '', $host );
		// generate output
		$text = sprintf( $comment_type_excerpts[ $comment_type ], get_comment_author_link( $comment->comment_ID ), 'this ' . $post_format, $url, $host );
		return apply_filters( 'semantic_linkbacks_excerpt', $text );
	}

	public static function webmention_links( $links, $post_ID ) {
		$meta = new Kind_Meta( $post_ID );
		$cites = $meta->get_url();
		if ( is_string( $cites ) ) {
			$links[] = $cites;
		}
		if ( is_array( $cites ) ) {
			$links = array_merge( $links, $cite );
			$links = array_unique( $links );
		}
		return $links;
	}

	public static function publish ( $ID, $post = null ) {
		if ( 'post' !== get_post_type( $ID ) ) {
			return;
		}
		$option = get_option( 'iwt_options' );
		if ( count( wp_get_post_terms( $ID,'kind' ) ) <= 0 ) {
			set_post_kind( $ID, $option['defaultkind'] );
		}
	}

	public static function transition( $new, $old, $post ) {
		if ( 'publish' === $new ) {
			self::publish( $post->ID, $post );
		}
	}

	public static function kinds_as_type($classes) {
		$kind = get_post_kind_slug();
		switch ( $kind ) {
			case 'note':
				$classes[] = 'h-as-note';
				break;
			case 'article':
				$classes[] = 'h-as-article';
				break;
			case 'photo':
				$classes[] = 'h-as-image';
				break;
			case 'bookmark':
				$classes[] = 'h-as-bookmark';
				break;
		}
		return $classes;
	}

	public static function post_class($classes) {
		// Adds kind classes to post
		if ( ! is_singular() ) {
			$classes = self::kinds_as_type( $classes );
		}
		$classes[] = 'kind-' . get_post_kind_slug();
		return $classes;
	}

	public static function body_class($classes) {
		// Adds kind classes to body
		if ( is_singular() ) {
			$classes = self::kinds_as_type( $classes );
		}
		return $classes;
	}

	/**
	 * Returns true if kind is a response type kind.
	 * This means dynamically generated content is added
	 *
	 * @param string $kind The post kind slug.
	 * @return true/false.
	 */
	public static function response_kind( $kind ) {
		$not_responses = array( 'article', 'note' , 'photo' );
		if ( in_array( $kind, $not_responses ) ) { return false;
		} else { return true; }
	}

	/**
	 * Returns a pretty, translated version of a post kind slug
	 *
	 * @param string $slug A post format slug.
	 * @return string The translated post format name.
	 */
	public static function get_post_kind_string( $slug ) {
		$strings = self::get_strings();
		return ( isset( $strings[ $slug ] ) ) ? $strings[ $slug ] : '';
	}

	/**
	 * Returns a link to a post kind index.
	 *
	 * @param string $kind The post kind slug.
	 * @return string The post kind term link.
	 */
	public static function get_post_kind_link( $kind ) {
		$term = get_term_by( 'slug', $kind, 'kind' );
		if ( ! $term || is_wp_error( $term ) ) {
			return false; }
		return get_term_link( $term );
	}

	public static function get_post_kind_slug( $post = null ) {
		$post = get_post( $post );
		if ( ! $post = get_post( $post ) ) {
			return false; }
		$_kind = get_the_terms( $post->ID, 'kind' );
		if ( ! empty( $_kind ) ) {
			$kind = array_shift( $_kind );
			return $kind->slug;
		} else { return false; }
	}
	public static function get_post_kind( $post = null ) {
		$kind = get_post_kind_slug( $post );
		if ( $kind ) {
			$strings = self::get_strings();
			return $strings[ $kind ];
		} else {
			return false;
		}
	}


	/**
	 * Check if a post has any of the given kinds, or any kind.
	 *
	 * @uses has_term()
	 *
	 * @param string|array $kinds Optional. The kind to check.
	 * @param object|int   $post Optional. The post to check. If not supplied, defaults to the current post if used in the loop.
	 * @return bool True if the post has any of the given kinds (or any kind, if no kind specified), false otherwise.
	 */
	public static function has_post_kind( $kinds = array(), $post = null ) {
		$prefixed = array();
		if ( $kinds ) {
			foreach ( (array) $kind as $single ) {
				$kind[] = sanitize_key( $single );
			}
		}
		return has_term( $kind, 'kind', $post );
	}

	/**
	 * Assign a kind to a post
	 *
	 * @param int|object $post The post for which to assign a kind.
	 * @param string     $kind A kind to assign. Using an empty string or array will default to article.
	 * @return mixed WP_Error on error. Array of affected term IDs on success.
	 */
	public static function set_post_kind( $post, $kind = 'article' ) {
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post' ) ); }
		$kind = sanitize_key( $kind );
		if ( ! array_key_exists( $kind, self::get_strings() ) ) {
			$kind = 'article';
		}
		return wp_set_post_terms( $post->ID, $kind, 'kind' );
	}

	public static function get_icon( $kind ) {
		// Substitute another svg sprite file
		$sprite = apply_filters( 'kind_icon_sprite', plugin_dir_url( __FILE__ ) . 'kind-sprite.svg', $kind );
		return '<svg class="svg-icon svg-' . $kind . '" aria-hidden="true"><use xlink:href="' . $sprite . '#' . $kind . '"></use></svg>';
	}

	/**
	 * Take mf2 properties and set a post kind
	 *
	 * @param array $input Micropub Request in JSON
	 * @param array $wp_args Arguments passed to insert or update posts
	 */

	public static function micropub_set_kind( $input, $wp_args ) {
		// Only continue if create or update
		if ( ! $wp_args ) {
			return;
		}
		// If there are no properties in the request set it as note
		if ( ! isset( $input['properties'] ) ) {
			set_post_kind( $wp_args['ID'], 'note' );
		}
		if ( isset( $input['properties']['rsvp'] ) ) {
			set_post_kind( $wp_args['ID'], 'rsvp' );
			return;
		}
		if ( isset( $input['properties']['in-reply-to'] ) ) {
			set_post_kind( $wp_args['ID'], 'reply' );
			return;
		}
		if ( isset( $input['properties']['bookmark-of'] ) || isset( $input['properties']['bookmark'] ) ) {
			set_post_kind( $wp_args['ID'], 'bookmark' );
			return;
		}
		if ( isset( $input['properties']['in-reply-to'] ) ) {
			set_post_kind( $wp_args['ID'], 'reply' );
			return;
		}
		// This basically adds Teacup support
		if ( isset( $input['properties']['p3k-food'] ) ) {
			if ( isset( $input['properties']['p3k-type'] ) ) {
				if ( 'drink' === $input['properties']['p3k-type'] ) {
					set_post_kind( $wp_args['ID'], 'drink' );
					return;
				}
				set_post_kind( $post_id, 'eat' );
				return;
			}
		}
		// If it got all the way down here assume it is a note
		set_post_kind( $wp_args['ID'], 'note' );
	}

} // End Class Kind_Taxonomy

?>
