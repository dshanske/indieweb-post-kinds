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

		// Add the Correct Archive Title to Kind Archives.
		add_filter( 'get_the_archive_title', array( 'Kind_Taxonomy', 'kind_archive_title' ), 10 );
		add_filter( 'get_the_archive_description', array( 'Kind_Taxonomy', 'kind_archive_description' ), 10 );

		// Add Kind Permalinks.
		add_filter( 'post_link', array( 'Kind_Taxonomy', 'kind_permalink' ) , 10, 3 );
		add_filter( 'post_type_link', array( 'Kind_Taxonomy', 'kind_permalink' ) , 10 , 3 );

		// Add Dropdown
		add_action( 'restrict_manage_posts', array( 'Kind_Taxonomy', 'kind_dropdown' ), 10, 2 );

		// Add Links to Ping to the Webmention Sender.
		add_filter( 'webmention_links', array( 'Kind_Taxonomy', 'webmention_links' ), 11, 2 );

		// Add Classes to Post.
		add_filter( 'post_class', array( 'Kind_Taxonomy', 'post_class' ) );

		// Trigger Webmention on Change in Post Status.
		add_filter( 'transition_post_status', array( 'Kind_Taxonomy', 'transition' ), 10, 3 );
		// On Post Save Set Post Format
		add_action( 'save_post', array( 'Kind_Taxonomy', 'post_formats' ), 99, 3 );
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
			'show_ui' => true,
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
		$terms = self::get_kind_info( 'all', 'all' );
		foreach ( $terms as $key => $value ) {
			if ( ! term_exists( $key, 'kind' ) ) {
				wp_insert_term( $key, 'kind',
					array(
					'description' => $value['description'],
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

	public static function kind_archive_title( $title ) {
		if ( is_tax( 'kind' ) ) {
			$term = get_queried_object();
			return self::get_kind_info( $term->slug, 'name' );
		}
		return $title;
	}

	public static function kind_archive_description( $title ) {
		if ( is_tax( 'kind' ) ) {
			$term = get_queried_object();
			return self::get_kind_info( $term->slug, 'description' );
		}
		return $title;
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
		if ( ! $update ) {
			set_post_format( $post_ID, self::get_kind_info( $kind, 'property' ) );
		}
	}

	public static function select_metabox( $post ) {
		$include = get_option( 'kind_termslist' );
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
			$default = get_term_by( 'slug', get_option( 'kind_default' ), 'kind' );
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
				echo self::get_kind_info( $slug, 'singular_name' );
				echo '<br />';
				echo '</label></li>';

			}
		}
		echo '</ul></div>';
	}

	/**
	 * Returns all translated strings.
	 *
	 * @param $kind Post Kind to return.
	 * @param $property The individual property
	 * @return string|array Return kind-property. If either is set to all, return all.
	 */
	public static function get_kind_info( $kind, $property ) {
		if ( ! $kind || ! $property ) {
			return false;
		}
		$kinds = array(
			'article' => array(
				'singular_name' => _x( 'Article', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Articles', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( ' ', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => '', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'traditional long form content: a post with an explicit title and body', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/article',
				'show' => true // Show in Settings
			),
			'note' => array(
				'singular_name' => _x( 'Note', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Notes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( ' ', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => '', // microformats 2 property
				'format' => 'aside', // Post Format that maps to this
				'description' => __( 'short content: a post or status update with just plain content and typically without a title', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/note',
				'show' => false // Show in Settings
			),
			'reply' => array(
				'singular_name' => _x( 'Reply', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Replies', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Replied', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'in-reply-to', // microformats 2 property
				'format' => 'link', // Post Format that maps to this
				'description' => __( 'a reply to content typically on another site', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/reply',
				'show' => true // Show in Settings
			),
			'repost' => array(
				'singular_name' => _x( 'Repost', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Reposts', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Reposted', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'repost-of', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'a complete reposting of content from another site', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/repost',
				'show' => true // Show in Settings
			),
			'like' => array(
				'singular_name' => _x( 'Like', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Likes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Liked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'like-of', // microformats 2 property
				'format' => 'link', // Post Format that maps to this
				'description' => __( 'a way to pay compliments to the original post/poster of external content', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/like',
				'show' => true // Show in Settings
			),
			'favorite' => array(
				'singular_name' => _x( 'Favorite', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Favorites', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Favorited', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'favorite-of', // microformats 2 property
				'format' => 'link', // Post Format that maps to this
				'description' => __( 'special to the author', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/favorite',
				'show' => true // Show in Settings
			),
			'bookmark' => array(
				'singular_name' => _x( 'Bookmark', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Bookmarks', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Bookmarked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'bookmark-of', // microformats 2 property
				'format' => 'link', // Post Format that maps to this
				'description' => __( 'storing a link/bookmark for personal use or sharing with others', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/bookmark',
				'show' => true // Show in Settings
			),
			'photo' => array(
				'singular_name' => _x( 'Photo', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Photos', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( ' ', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'photo', // microformats 2 property
				'format' => 'image', // Post Format that maps to this
				'description' => __( 'a post with an embedded image/photo as its primary focus', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/photo',
				'show' => true // Show in Settings
			),
			'video' => array(
				'singular_name' => _x( 'Video', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Videos', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( ' ', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'video', // microformats 2 property
				'format' => 'video', // Post Format that maps to this
				'description' => __( 'a post with an embedded video as its primary focus', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/video',
				'show' => true // Show in Settings
			),
			'audio' => array(
				'singular_name' => _x( 'Audio', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Audios', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( ' ', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'audio', // microformats 2 property
				'format' => 'audio', // Post Format that maps to this
				'description' => __( 'a post with an embedded audio file as its primary focus', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/audio',
				'show' => true // Show in Settings
			),

			'tag' => array(
				'singular_name' => _x( 'Tag', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Tags', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Tagged', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'tag', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'allows you to tag a post as being of a specific category or tag, or for person tagging', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/tag',
				'show' => false // Show in Settings
			),
			'rsvp' => array(
				'singular_name' => _x( 'RSVP', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'RSVPs', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'RSVPed', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'in-reply-to', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'a specific type of reply regarding attendance of an event', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/rsvp',
				'show' => true // Show in Settings
			),
			'listen' => array(
				'singular_name' => _x( 'Listen', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Listens', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Listened', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'listen-of', // microformats 2 property
				'format' => 'audio', // Post Format that maps to this
				'description' => __( 'listening to audio; sometimes called a scrobble', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/listen',
				'show' => true // Show in Settings
			),
			'watch' => array(
				'singular_name' => _x( 'Watch', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Watches', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Watched', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'watch', // microformats 2 property
				'format' => 'video-of', // Post Format that maps to this
				'description' => __( 'watching a movie, television show, online video, play or other visual-based event', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/watch',
				'show' => true // Show in Settings
			),
			'checkin' => array(
				'singular_name' => _x( 'Checkin', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Checkins', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Checked into', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'checkin', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'identifying you are at a particular geographic location', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/checkin',
				'show' => false // Show in Settings
			),
			'wish' => array(
				'singular_name' => _x( 'Wish', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Wishes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Wished', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'wish', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'a post indicating a desire/wish. The archive of which would be a wishlist, such as a gift registry or similar', 'indieweb-post-kinds' ),
				'description-url' => '',
				'show' => false // Show in Settings
			),
			'play' => array(
				'singular_name' => _x( 'Play', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Playing', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Played', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'play', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'playing a game', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/game_play',
				'show' => true // Show in Settings
			),
			'weather' => array(
				'singular_name' => _x( 'Weather', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Weather', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( ' ', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'weather', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'current weather conditions', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/weather',
				'show' => false // Show in Settings
			),
			'exercise' => array(
				'singular_name' => _x( 'Exercise', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Exercise', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Exercised', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'exercise', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'some form of physical activity or workout (examples: walk, run, cycle, hike, yoga, etc.)', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/exercise',
				'show' => false // Show in Settings
			),
			'trip' => array(
				'singular_name' => _x( 'Trip', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Trips', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Travelled', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'trip', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'represents a geographic journey', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/trip',
				'show' => false // Show in Settings
			),
			'itinerary' => array(
				'singular_name' => _x( 'Itinerary', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Itineraries', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Travelled', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'itinerary', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'parts of a scheduled trip including transit by car, plane, train, etc.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/trip',
				'show' => false // Show in Settings
			),
			'eat' => array(
				'singular_name' => _x( 'Eat', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Eat', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Ate', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'food', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'what you are eating, perhaps for a food dairy', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/food',
				'show' => false // Show in Settings
			),
			'drink' => array(
				'singular_name' => _x( 'Drink', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Drinks', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Drank', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'food', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'what you are drinking, perhaps for a food dairy', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/food',
				'show' => false // Show in Settings
			),
			'follow' => array(
				'singular_name' => _x( 'Follow', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Follows', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Followed', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'follow', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'indicating you are now following or subscribing to another person`s activities online', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/follow',
				'show' => false // Show in Settings
			),
			'jam' => array(
				'singular_name' => _x( 'Jam', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Jams', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Listened to', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'jam-of', // microformats 2 property
				'format' => 'audio', // Post Format that maps to this
				'description' => __( 'a particularly personally meaningful song (a listen with added emphasis)', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/jam',
				'show' => true // Show in Settings
			),
			'read' => array(
				'singular_name' => _x( 'Read', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Reads', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Read', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'read', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'reading a book, magazine, newspaper, other physical document, or online post', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/read',
				'show' => true // Show in Settings
			),
			'quote' => array(
				'singular_name' => _x( 'Quote', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Quotes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Quoted', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'quotation-of', // microformats 2 property
				'format' => 'quote', // Post Format that maps to this
				'description' => __( 'quoted content', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/quote',
				'show' => true // Show in Settings
			),
			'mood' => array(
				'singular_name' => _x( 'Mood', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Moods', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Felt', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'mood', // microformats 2 property
				'format' => 'status', // Post Format that maps to this
				'description' => __( 'how you are feeling (example: happy, sad, indifferent, etc.)', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/mood',
				'show' => false // Show in Settings
			),
			'recipe' => array(
				'singular_name' => _x( 'Recipe', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Recipes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Cooked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'recipe', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'list of ingredients and directions for making food or drink', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/recipe',
				'show' => false // Show in Settings
			),
			'issue' => array(
				'singular_name' => _x( 'Issue', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Issues', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Filed an Issue', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'u-in-reply-to', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'Issue is a special kind of article post that is a reply to typically some source code, though potentially anything at a source control repository.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/issue',
				'show' => false // Show in Settings
			),
			'event' => array(
				'singular_name' => _x( 'Event', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name' => _x( 'Eventss', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb' => _x( 'Planned', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property' => 'h-event', // microformats 2 property
				'format' => '', // Post Format that maps to this
				'description' => __( 'An event is a type of post that in addition to a post name (event title) has a start datetime (likely end datetime), and a location.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/event',
				'show' => false // Show in Settings
			),
		);
		$kinds = apply_filters( 'kind_info', $kinds );
		ksort( $kinds );
		if ( 'all' === $kind ) {
			return $kinds;
		}
		if ( ! array_key_exists( $kind, $kinds ) ) {
			return false;
		}
		$k = $kinds[ $kind ];
		if ( 'all' === $property ) {
			return $k;
		}
		if ( ! array_key_exists( $property, $k ) ) {
			return false;
		}
		return $k[ $property ];
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

	public static function kind_dropdown( $post_type, $which ) {
		if ( 'post' === $post_type ) {
			$taxonomy = 'kind';
			$selected      = isset( $_GET[ $taxonomy ] ) ? $_GET[ $taxonomy ] : '';
			$kind_taxonomy = get_taxonomy( $taxonomy );
			wp_dropdown_categories( array(
				'show_option_all' => __( "All {$kind_taxonomy->label}", 'indieweb-post-kinds' ),
				'taxonomy'        => $taxonomy,
				'name'            => $taxonomy,
				'orderby'         => 'name',
				'selected'        => $selected,
				'hierarchical'    => false,
				'show_count'      => true,
				'hide_empty'      => true,
				'value_field'     => 'slug',
			) );
		}
	}

	public static function publish ( $ID, $post = null ) {
		if ( 'post' !== get_post_type( $ID ) ) {
			return;
		}
		if ( count( wp_get_post_terms( $ID,'kind' ) ) <= 0 ) {
			set_post_kind( $ID, get_option( 'kind_default' ) );
		}
	}

	public static function transition( $new, $old, $post ) {
		if ( 'publish' === $new ) {
			self::publish( $post->ID, $post );
		}
	}

	public static function post_class($classes) {
		if ( 'post' !== get_post_type() ) {
			return $classes;
		}
		$classes[] = 'kind-' . get_post_kind_slug();
		return $classes;
	}

	/**
	 * Returns a pretty, translated version of a post kind slug
	 *
	 * @param string $slug A post format slug.
	 * @return string The translated post format name.
	 */
	public static function get_post_kind_string( $slug ) {
		$string = self::get_kind_info( $slug, 'singular_name' );
		return $slug ? $slug : '';
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
			return self::get_kind_info( $kind, 'singular_name' );
			;
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
			return new WP_Error( 'invalid_post', __( 'Invalid post', 'indieweb-post-kinds' ) ); }
		$kind = sanitize_key( $kind );
		if ( ! self::get_kind_info( $kind, 'all' ) ) {
			return new WP_Error( 'invalid_kind', __( 'Invalid Kind', 'indieweb-post-kinds' ) );
		}
		return wp_set_post_terms( $post->ID, $kind, 'kind' );
	}

	public static function get_icon( $kind ) {
		// Substitute another svg sprite file
		$sprite = apply_filters( 'kind_icon_sprite', plugin_dir_url( __FILE__ ) . 'kind-sprite.svg', $kind );
		return '<svg class="svg-icon svg-' . $kind . '" aria-hidden="true"><use xlink:href="' . $sprite . '#' . $kind . '"></use></svg>';
	}
} // End Class Kind_Taxonomy

?>
