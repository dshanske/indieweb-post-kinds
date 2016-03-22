<?php
/**
 * Post Kind Taxonomy Class
 *
 * Registers the taxonomy and sets its behavior.
 *
 * @package Post Kinds
 */

add_action( 'init' , array( 'Kind_Taxonomy', 'init' ) );
// Register Kind Taxonomy.
add_action( 'init', array( 'Kind_Taxonomy', 'register' ), 1 );


// On Activation, add terms.
register_activation_hook( __FILE__, array( 'Kind_Taxonomy', 'activate_kinds' ) );

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
		add_action( 'publish_post', array( 'Kind_Taxonomy', 'invalidate_response' ), 10, 2 );

		// Return Kind Meta as part of the JSON Rest API.
		add_filter( 'json_prepare_post' , array( 'Kind_Taxonomy', 'json_rest_add_kindmeta' ) , 10 , 3 );

		// Add the Correct Archive Title to Kind Archives.
		add_filter( 'get_the_archive_title', array( 'Kind_Taxonomy', 'kind_archive_title' ) , 10 , 3 );
		// Remove the built-in meta box selector in place of a custom one.
		add_action( 'admin_menu', array( 'Kind_Taxonomy', 'remove_meta_box' ) );
		add_action( 'add_meta_boxes', array( 'Kind_Taxonomy', 'add_meta_box' ) );

		// Set Post Kind for Micropub Inputs.
		add_action( 'after_micropub', array( 'Kind_Taxonomy', 'micropub_set_kind' ) );

		// Override Post Type in Semantic Linkbacks.
		add_filter( 'semantic_linkbacks_post_type', array( 'Kind_Taxonomy', 'semantic_post_type' ), 11, 2 );

		// Remove the Automatic Post Generation that the Micropub Plugin Offers
		remove_filter( 'before_micropub', array( 'Micropub', 'generate_post_content' ) );

		//send salmention when comment on post approved
		add_action('transition_comment_status', array( 'Kind_Taxonomy', 'comment_transition' ), 10, 3);

	}


	/**
	 * Deletes cached response.
	 *
	 * @param int $post_id Post to Delete Cache of
	 */
	public static function invalidate_response( $post_id ) {
		delete_post_meta( $post_id, '_resp_full' );
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
			'name' => _x( 'Kinds', 'Post kind' ),
			'singular_name' => _x( 'Kind', 'Post kind' ),
			'search_items' => _x( 'Search Kinds', 'Post kind' ),
			'popular_items' => _x( 'Popular Kinds', 'Post kind' ),
			'all_items' => _x( 'All Kinds', 'Post kind' ),
			'parent_item' => _x( 'Parent Kind', 'Post kind' ),
			'parent_item_colon' => _x( 'Parent Kind:', 'Post kind' ),
			'edit_item' => _x( 'Edit Kind', 'Post kind' ),
			'update_item' => _x( 'Update Kind', 'Post kind' ),
			'add_new_item' => _x( 'Add New Kind', 'Post kind' ),
			'new_item_name' => _x( 'New Kind', 'Post kind' ),
			'separate_items_with_commas' => _x( 'Separate kinds with commas', 'Post kind' ),
			'add_or_remove_items' => _x( 'Add or remove kinds', 'Post kind' ),
			'choose_from_most_used' => _x( 'Choose from the most used kinds', 'Post kind' ),
			'menu_name' => _x( 'Kinds', 'Post kind' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => WP_DEBUG,
			'show_tagcloud' => true,
			'show_admin_column' => true,
			'hierarchical' => false,
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

	public static function remove_meta_box() {
		remove_meta_box( 'tagsdiv-kind', 'post', 'normal' );
	}

	public static function add_meta_box() {
		if ( ! MULTIKIND ) {
			add_meta_box( 'kind_select', 'Post Kinds', array( 'Kind_Taxonomy', 'select_metabox' ),'post' ,'side','core' );
		} else {
			// Add Multi-Select Box for MultiKind support
			add_meta_box( 'tagsdiv-kind', 'Post Kinds', array( 'Kind_Taxonomy', 'multiselect_metabox' ),'post' ,'side','core' );
		}
	}

	public static function select_metabox( $post ) {
		$strings = self::get_strings();
		$option = get_option( 'iwt_options', Kind_Config::Defaults() );
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
				echo kind_icon( $slug );
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
			'article' => _x( 'Article', 'Post kind' ),
			'note'    => _x( 'Note',    'Post kind' ),
			'reply'     => _x( 'Reply',     'Post kind' ),
			'repost'  => _x( 'Repost',  'Post kind' ),
			'like'     => _x( 'Like',     'Post kind' ),
			'favorite'    => _x( 'Favorite',    'Post kind' ),
			'bookmark'    => _x( 'Bookmark',    'Post kind' ),
			'photo'   => _x( 'Photo',   'Post kind' ),
			'tag'    => _x( 'Tag',    'Post kind' ),
			'rsvp'    => _x( 'RSVP',    'Post kind' ),
			'listen'   => _x( 'Listen', 'Post kind' ),
			'watch'   => _x( 'Watch', 'Post kind' ),
			'checkin'   => _x( 'Checkin', 'Post kind' ),
			'wish'   => _x( 'Wish', 'Post kind' ),
			'play'   => _x( 'Play', 'Post kind' ),
			'weather'   => _x( 'Weather', 'Post kind' ),
			'exercise'   => _x( 'Exercise', 'Post kind' ),
			'travel'   => _x( 'Travel', 'Post kind' ),
		'eat'   => _x( 'Eat', 'Post kind' ),
		'drink'   => _x( 'Drink', 'Post kind' ),
		'follow'   => _x( 'Follow', 'Post kind' ),
		'jam'   => _x( 'Jam', 'Post kind' ),
		'read' => _x( 'Read', 'Post kind' ),
		'quote' => _x( 'Quote', 'Post kind' )
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
			'article' => _x( 'Articles', 'Post kind' ),
			'note'    => _x( 'Notes',    'Post kind' ),
			'reply'     => _x( 'Replies',     'Post kind' ),
			'repost'  => _x( 'Reposts',  'Post kind' ),
			'like'     => _x( 'Likes',     'Post kind' ),
			'favorite'    => _x( 'Favorites',    'Post kind' ),
			'bookmark'    => _x( 'Bookmarks',    'Post kind' ),
			'photo'   => _x( 'Photos',   'Post kind' ),
			'tag'    => _x( 'Tags',    'Post kind' ),
			'rsvp'    => _x( 'RSVPs',    'Post kind' ),
			'listen'   => _x( 'Listens', 'Post kind' ),
			'watch'   => _x( 'Watches', 'Post kind' ),
			'checkin'   => _x( 'Checkins', 'Post kind' ),
			'wish'   => _x( 'Wishlist', 'Post kind' ),
			'play'   => _x( 'Plays', 'Post kind' ),
			'weather'   => _x( 'Weather', 'Post kind' ),
			'exercise'   => _x( 'Exercises', 'Post kind' ),
			'travel'   => _x( 'Travels', 'Post kind' ),
		'eat'   => _x( 'Eat', 'Post kind' ),
		'drink'   => _x( 'Drinks', 'Post kind' ),
			'follow'   => _x( 'Follows', 'Post kind' ),
		'jam'   => _x( 'Jams', 'Post kind' ),
		'read' => _x( 'Read', 'Post kind' ),
		'quote' => _x( 'Quotes', 'Post kind' )
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
			'article' => _x( ' ', 'Post kind' ),
			'note'    => _x( ' ',    'Post kind' ),
			'reply'     => _x( 'In Reply To',     'Post kind' ),
			'repost'  => _x( 'Reposted',  'Post kind' ),
			'like'     => _x( 'Liked',     'Post kind' ),
			'favorite'    => _x( 'Favorited',    'Post kind' ),
			'bookmark'    => _x( 'Bookmarked',    'Post kind' ),
			'photo'   => _x( ' ',   'Post kind' ),
			'tag'    => _x( 'Tagged',    'Post kind' ),
			'rsvp'    => _x( 'RSVPed',    'Post kind' ),
			'listen'    => _x( 'Listened to ',    'Post kind' ),
			'watch'   => _x( 'Watched', 'Post kind' ),
			'checkin'   => _x( 'Checked In At', 'Post kind' ),
			'wish'   => _x( 'Desires', 'Post kind' ),
			'play'   => _x( 'Played', 'Post kind' ),
			'weather'   => _x( 'Weathered', 'Post kind' ),
			'exercise'   => _x( 'Exercised', 'Post kind' ),
			'travel'   => _x( 'Traveled', 'Post kind' ),
		'eat'   => _x( 'Ate', 'Post kind' ),
		'drink'   => _x( 'Drank', 'Post kind' ),
		'follow'   => _x( 'Followed', 'Post kind' ),
		'jam'   => _x( 'Listened to', 'Post kind' ),
		'read' => _x( 'Is Reading', 'Post kind' ),
		'quote' => _x( 'Quoted', 'Post kind' )

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
		'travel'   => 'travel',
		'eat'   => 'p3k-food',
		'drink'   => 'p3k-food',
		'follow'   => 'u-follow-of',
		'jam'   => 'jam',
		'read' => 'read',
		'quote' => 'quote'
		);
		return apply_filters( 'kind_properties', $strings );
	}

	// Replaces need for Replacing the Entire Excerpt
	public static function semantic_post_type($post_type, $post_id) {
		return _x( 'this', 'Post kind' ) . ' ' . strtolower( get_post_kind( $post_id ) );
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

	public static function publish ( $ID, $post=null) {
    $option = get_option( 'iwt_options', Kind_Config::Defaults() );
		if(count(wp_get_post_terms($ID,'kind'))<=0){
			set_post_kind($ID, $option['defaultkind']);
		}

		$cites = get_post_meta( $ID, 'mf2_cite', true );
		if ( empty( $cites ) ) { return; }
		if ( isset( $cites['url'] ) ) {
			send_webmention( get_permalink( $ID ), $cites['url'] );
			if ( WP_DEBUG ) {
				error_log( 'WEBMENTION CALLED'.get_permalink( $ID ).' : '.$cites['url'] );
			}
		} else {
			foreach ( $cites as $cite ) {
				if ( ! empty( $cite ) && isset( $cite['url'] ) ) {
					send_webmention( get_permalink( $ID ), $cite['url'] );
					if ( WP_DEBUG ) {
			 			error_log( 'WEBMENTIONS CALLED'.get_permalink( $ID ).' : '.$cite['url'] );
					}
				}
			}
		}
	}

	public static function transition($new,$old,$post) {
		if ( 'publish' === $new ) {
			self::publish( $post->ID,$post );
		}
	}

	public static function comment_transition($new_status, $old_status, $comment) {
	    if($old_status != $new_status) {
		if($new_status == 'approved') {
		    $postid = $comment->comment_post_ID;
		    self::publish( $postid );
		}
	    }
	}

	public static function json_rest_add_kindmeta($_post,$post,$context) {
		$response = new Kind_Meta( $post['ID'] );
		if ( ! empty( $response ) ) { $_post['mf2'] = $response->get_all_meta(); }
		return $_post;
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
	 * @param string     $kind A kind to assign. Using an empty string or array will default to note.
	 * @return mixed WP_Error on error. Array of affected term IDs on success.
	 */
	public static function set_post_kind( $post, $kind = 'note' ) {
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post' ) ); }
		$kind = sanitize_key( $kind );
		if ( ! array_key_exists( $kind, self::get_strings() ) ) {
			$kind = 'note';
		}
		return wp_set_post_terms( $post->ID, $kind, 'kind' );
	}

	/**
	 * Take mf2 properties and set a post kind
	 *
	 * @param int $post_id The post for which to assign a kind.
	 */

	public static function micropub_set_kind( $post_id ) {
		if ( isset( $_POST['rsvp'] ) ) {
			set_post_kind( $post_id, 'rsvp' );
			return;
		}
		if ( isset( $_POST['in-reply-to'] ) ) {
			set_post_kind( $post_id, 'reply' );
			return;
		}
		if ( isset( $_POST['bookmark-of'] ) || isset( $_POST['bookmark'] ) ) {
			set_post_kind( $post_id, 'bookmark' );
			return;
		}
		if ( isset( $_POST['in-reply-to'] ) ) {
			set_post_kind( $post_id, 'reply' );
			return;
		}
		// This basically adds Teacup support
		if ( isset( $_POST['p3k-food'] ) ) {
			if ( isset( $_POST['p3k-type'] ) ) {
				if ( 'drink' === $_POST['p3k-type'] ) {
					set_post_kind( $post_id, 'drink' );
					return;
				}
				set_post_kind( $post_id, 'eat' );
				return;
			}
		}
		set_post_kind( $post_id, 'note' );
	}

} // End Class Kind_Taxonomy

?>
