<?php

/**
 * Post Kind Taxonomy Class
 *
 * Registers the taxonomy and sets its behavior.
 *
 * @package Post Kinds
 */
final class Kind_Taxonomy {
	private static $kinds = array(); // Store a Post_Kind class which is a definition of a specific kind

	public static function init() {

		self::register_initial_kinds();

		// Add the Correct Archive Title to Kind Archives.
		add_filter( 'get_the_archive_title', array( 'Kind_Taxonomy', 'kind_archive_title' ), 10 );
		add_filter( 'get_the_archive_description', array( 'Kind_Taxonomy', 'kind_archive_description' ), 10 );

		// Add Kind Permalinks.
		add_filter( 'post_link', array( 'Kind_Taxonomy', 'kind_permalink' ), 10, 3 );
		add_filter( 'post_type_link', array( 'Kind_Taxonomy', 'kind_permalink' ), 10, 3 );

		// Add Dropdown
		add_action( 'restrict_manage_posts', array( 'Kind_Taxonomy', 'kind_dropdown' ), 10, 2 );

		// Add Links to Ping to the Webmention Sender.
		add_filter( 'webmention_links', array( 'Kind_Taxonomy', 'webmention_links' ), 11, 2 );

		// Add Links to Enclosures if Appropriate
		add_filter( 'enclosure_links', array( 'Kind_Taxonomy', 'enclosure_links' ), 11, 2 );

		// Add Classes to Post.
		add_filter( 'post_class', array( 'Kind_Taxonomy', 'post_class' ) );

		// Trigger Webmention on Change in Post Status.
		add_filter( 'transition_post_status', array( 'Kind_Taxonomy', 'transition' ), 10, 3 );
		// On Post Save Set Post Format
		add_action( 'save_post', array( 'Kind_Taxonomy', 'post_formats' ), 99, 3 );

		// Create hook triggered by change of kind
		add_action( 'set_object_terms', array( 'Kind_Taxonomy', 'set_object_terms' ), 10, 6 );

		add_filter( 'the_title', array( 'Kind_Taxonomy', 'the_title' ), 9, 2 );

		add_action( 'rest_api_init', array( 'Kind_Taxonomy', 'rest_kind' ) );
	}

	public static function rest_kind() {
		register_rest_field(
			'post',
			'kind',
			array(
				'get_callback'    => array( 'Kind_Taxonomy', 'get_post_kind_slug' ),
				'update_callback' => array( 'Kind_Taxonomy', 'set_rest_post_kind' ),
				'schema'          => array(
					'kind' => __( 'Post Kind', 'indieweb-post-kinds' ),
					'type' => 'string',
				),
			)
		);
	}


	public static function the_title( $title, $post_id ) {
		if ( ! $title && is_admin() ) {
			echo mb_strimwidth( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 0, 40, '...' ); // phpcs:ignore
		}
		return $title;
	}

	public static function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( empty( $tt_ids ) && empty( $old_tt_ids ) ) {
			return;
		}
		if ( 'kind' === $taxonomy ) {
			$old_term = get_term_by( 'term_taxonomy_id', array_pop( $old_tt_ids ), 'kind' );
			$old_term = $old_term instanceof WP_Term ? $old_term->slug : '';
			$new_term = get_term_by( 'term_taxonomy_id', array_pop( $tt_ids ), 'kind' );
			$new_term = $new_term instanceof WP_Term ? $new_term->slug : '';
			// Trigger a hook on a changed kind identifying old and new so actions can be performed
			do_action( 'change_kind', $object_id, $old_term, $new_term );
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
		flush_rewrite_rules();
	}

	/**
	 * Register the custom taxonomy for kinds.
	 */
	public static function register() {
		$labels = array(
			'name'                       => _x( 'Kinds', 'taxonomy general name', 'indieweb-post-kinds' ),
			'singular_name'              => _x( 'Kind', 'taxonomy singular name', 'indieweb-post-kinds' ),
			'search_items'               => _x( 'Search Kinds', 'search locations', 'indieweb-post-kinds' ),
			'popular_items'              => _x( 'Popular Kinds', 'popular kinds', 'indieweb-post-kinds' ),
			'all_items'                  => _x( 'All Kinds', 'all taxonomy items', 'indieweb-post-kinds' ),
			'parent_item'                => _x( 'Parent Kind', 'taxonomy parent item', 'indieweb-post-kinds' ),
			'parent_item_colon'          => _x( 'Parent Kind:', 'taxonomy parent item with colon', 'indieweb-post-kinds' ),
			'edit_item'                  => _x( 'Edit Kind', 'edit taxonomy item', 'indieweb-post-kinds' ),
			'view_item'                  => _x( 'View Kind', 'view taxonomy item', 'indieweb-post-kinds' ),
			'update_item'                => _x( 'Update Kind', 'update taxonomy item', 'indieweb-post-kinds' ),
			'add_new_item'               => _x( 'Add New Kind', 'add taxonomy item', 'indieweb-post-kinds' ),
			'new_item_name'              => _x( 'New Kind', 'new taxonomy item', 'indieweb-post-kinds' ),
			'separate_items_with_commas' => _x( 'Separate kinds with commas', 'separate kinds with commas', 'indieweb-post-kinds' ),
			'add_or_remove_items'        => _x( 'Add or remove kinds', 'add or remove items', 'indieweb-post-kinds' ),
			'choose_from_most_used'      => _x( 'Choose from the most used kinds', 'choose most used', 'indieweb-post-kinds' ),
			'not found'                  => _x( 'No kinds found', 'no kinds found', 'indieweb-post-kinds' ),
			'no_terms'                   => _x( 'No kinds', 'no kinds', 'indieweb-post-kinds' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => false,
			'show_ui'            => true,
			'show_in_menu'       => WP_DEBUG,
			'show_in_nav_menu'   => true,
			'show_in_rest'       => false,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => false,
			'show_admin_column'  => true,
			'meta_box_cb'        => array( 'Kind_Taxonomy', 'select_metabox' ),
			'rewrite'            => true,
			'query_var'          => true,
		);
		register_taxonomy( 'kind', array( 'post' ), $args );
	}

	/**
	 * Sets up Default Terms for Kind Taxonomy.
	 */
	public static function kind_defaultterms() {
		$terms = self::get_kind_list();
		foreach ( $terms as $term ) {
			if ( ! get_term_by( 'slug', $term, 'kind' ) ) {
				self::create_post_kind( $term );
			} else {
				self::update_post_kind( $term );
			}
		}
	}

	private static function update_post_kind( $term ) {
		$t = get_term_by( 'slug', $term, 'kind' );
		if ( ! $t ) {
			return self::create_post_kind( $term );
		}
		$kind = self::get_post_kind_info( $term );
		if ( $kind ) {
			wp_update_term(
				$t->term_id,
				'kind',
				array(
					'name'        => $kind->singular_name,
					'description' => $kind->description,
					'slug'        => $term,
				)
			);
		}
	}

	private static function create_post_kind( $term ) {
		$kind = self::get_post_kind_info( $term );
		if ( $kind ) {
			wp_insert_term(
				$kind->slug,
				'kind',
				array(
					'name'        => $kind->singular_name,
					'description' => $kind->description,
					'slug'        => $term,
				)
			);
		}
	}

	public static function kind_permalink( $permalink, $post_id, $leavename ) {
		if ( false === strpos( $permalink, '%kind%' ) ) {
			return $permalink; }

		// Get post
		$post = get_post( $post_id );
		if ( ! $post ) {
			return $permalink; }

		// Get taxonomy terms
		$terms = wp_get_object_terms( $post->ID, 'kind' );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) && is_object( $terms[0] ) ) {
			$taxonomy_slug = $terms[0]->slug;
		} else {
			$taxonomy_slug = 'note'; }
		return str_replace( '%kind%', $taxonomy_slug, $permalink );
	}

	public static function get_terms_from_query() {
		global $wp_query;
		$terms = array();
		$slugs = $wp_query->tax_query->queried_terms['kind']['terms'];
		foreach ( $slugs as $slug ) {
			$terms[] = get_term_by( 'slug', $slug, 'kind' );
		}
		return $terms;
	}


	public static function kind_archive_title( $title ) {
		$return = array();
		if ( is_tax( 'kind' ) ) {
			$terms = self::get_terms_from_query();
			foreach ( $terms as $term ) {
				$return[] = self::get_kind_info( $term->slug, 'name' );
			}
			if ( $return ) {
				return join( ', ', $return );
			}
		}
		return $title;
	}

	public static function kind_archive_description( $title ) {
		$return = array();
		if ( is_tax( 'kind' ) ) {
			$terms = self::get_terms_from_query();
			foreach ( $terms as $term ) {
				$return[] = self::get_kind_info( $term->slug, 'description' );
			}
			if ( $return ) {
				return join( '<br />', $return );
			}
		}
		return $title;
	}

	/**
	 * Sets Post Format for Post Kind.
	 *
	 * @param int     $post_id Post ID
	 * @param WP_Post $post Post Object
	 * @param boolean $update,
	 */
	public static function post_formats( $post_id, $post, $update ) {
		$kind = get_post_kind_slug( $post_id );
		if ( ! $update ) {
			set_post_format( $post_id, self::get_kind_info( $kind, 'format' ) );
		}
	}

	public static function select_metabox( $post ) {
		$include = get_option( 'kind_termslist' );
		$include = array_merge( $include, array( 'note', 'reply', 'article' ) );
		// If Simple Location is Enabled, include the check-in type
		// Filter Kinds
		$include = array_unique( apply_filters( 'kind_include', $include ) );
		// Note cannot be removed or disabled without hacking the code
		if ( ! in_array( 'note', $include, true ) ) {
			$include[] = 'note';
		}
		if ( isset( $_GET['kind'] ) ) {
			$default = get_term_by( 'slug', $_GET['kind'], 'kind' );
		} else {
			// On existing published posts without a kind fall back on article which most closely mimics the behavior of an unclassified post
			if ( 'publish' === get_post_status( $post ) ) {
				$default = get_term_by( 'slug', 'article', 'kind' );
			} else {
				$default = get_term_by( 'slug', get_option( 'kind_default' ), 'kind' );
			}
		}
		$terms     = get_terms(
			'kind',
			array(
				'hide_empty' => 0,
			)
		);
		$postterms = get_the_terms( $post->ID, 'kind' );
		$current   = ( $postterms ? array_pop( $postterms ) : false );
		$current   = ( $current ? $current->term_id : $default->term_id );
		echo '<div id="kind-all">';
		echo '<ul id="taxonomy-kind" class="list:kind category-tabs form-no-clear">';
		foreach ( $terms as $term ) {
			$id   = 'kind-' . $term->term_id;
			$slug = $term->slug;
			if ( in_array( $slug, $include, true ) ) {
				printf( '<li id="%1$s" class="kind-%2$s"><label class="selectit">', esc_attr( $id ), esc_attr( $slug ) );
				printf( '<input type="radio" id="in-%1$s" name="tax_input[kind]" value="%2$s" %3$s />', esc_attr( $id ), esc_attr( $slug ), checked( $current, $term->term_id, false ) );
				self::get_icon( $slug, true );
				echo esc_html( self::get_kind_info( $slug, 'singular_name' ) );
				echo '<br />';
				echo '</label></li>';

			}
		}
		echo '</ul></div>';
	}

	public static function register_post_kind( $slug, $args ) {
		$kind = new Post_Kind( $slug, $args );
		// Do not allow reregistering existing kinds
		if ( isset( static::$kinds[ $slug ] ) ) {
			return false;
		}
		static::$kinds[ $slug ] = $kind;
		return true;
	}

	public static function get_post_kind_info( $slug ) {
		if ( isset( static::$kinds[ $slug ] ) ) {
			return static::$kinds[ $slug ];
		}
		return false;
	}

	// Enable a hidden post kind
	public static function set_post_kind_visibility( $slug, $show = true ) {
		if ( isset( static::$kinds[ $slug ] ) ) {
			static::$kinds[ $slug ]->show = ( $show ? true : false );
			return true;
		}
		return false;
	}

	public static function get_kind_list() {
		return array_keys( static::$kinds );
	}

	/**
	 * Register Built in Kinds

	 */
	public static function register_initial_kinds() {
		register_post_kind(
			'article',
			array(
				'singular_name'   => __( 'Article', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Articles', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => ' ', // The string for the verb or action (liked this)
				'property'        => '', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'traditional long form content: a post with an explicit title and body', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/article',
				'title'           => true, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'note',
			array(
				'singular_name'   => __( 'Note', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Notes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => ' ', // The string for the verb or action (liked this)
				'property'        => '', // microformats 2 property
				'format'          => 'aside', // Post Format that maps to this
				'description'     => __( 'short content: a post or status update with just plain content and typically without a title', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/note',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'reply',
			array(
				'singular_name'   => __( 'Reply', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Replies', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Replied to', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'in-reply-to', // microformats 2 property
				'format'          => 'link', // Post Format that maps to this
				'description'     => __( 'a reply to content typically on another site', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/reply',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'repost',
			array(
				'singular_name'   => __( 'Repost', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Reposts', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Reposted', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'repost-of', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'a complete reposting of content from another site', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/repost',
				'title'           => true, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'like',
			array(
				'singular_name'   => __( 'Like', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Likes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Liked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'like-of', // microformats 2 property
				'format'          => 'link', // Post Format that maps to this
				'description'     => __( 'a way to pay compliments to the original post/poster of external content', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/like',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'favorite',
			array(
				'singular_name'   => __( 'Favorite', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Favorites', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Favorited', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'favorite-of', // microformats 2 property
				'format'          => 'link', // Post Format that maps to this
				'description'     => __( 'special to the author', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/favorite',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'bookmark',
			array(
				'singular_name'   => __( 'Bookmark', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Bookmarks', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Bookmarked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'bookmark-of', // microformats 2 property
				'format'          => 'link', // Post Format that maps to this
				'description'     => __( 'storing a link/bookmark for personal use or sharing with others', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/bookmark',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'photo',
			array(
				'singular_name'   => __( 'Photo', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Photos', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => ' ', // The string for the verb or action (liked this)
				'property'        => 'photo', // microformats 2 property
				'format'          => 'image', // Post Format that maps to this
				'description'     => __( 'a post with an embedded image/photo as its primary focus', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/photo',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'video',
			array(
				'singular_name'   => __( 'Video', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Videos', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => ' ', // The string for the verb or action (liked this)
				'property'        => 'video', // microformats 2 property
				'format'          => 'video', // Post Format that maps to this
				'description'     => __( 'a post with an embedded video as its primary focus', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/video',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'audio',
			array(
				'singular_name'   => __( 'Audio', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Audios', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => ' ', // The string for the verb or action (liked this)
				'property'        => 'audio', // microformats 2 property
				'format'          => 'audio', // Post Format that maps to this
				'description'     => __( 'a post with an embedded audio file as its primary focus', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/audio',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'tag',
			array(
				'singular_name'   => __( 'Tag', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Tags', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Tagged', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'tag-of', // microformats 2 property
				'format'          => 'link', // Post Format that maps to this
				'description'     => __( 'allows you to tag a post as being of a specific category or tag, or for person tagging', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/tag',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'rsvp',
			array(
				'singular_name'   => __( 'RSVP', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'RSVPs', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'RSVPed', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'in-reply-to', // microformats 2 property
				'format'          => 'link', // Post Format that maps to this
				'description'     => __( 'a specific type of reply regarding attendance of an event', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/rsvp',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'listen',
			array(
				'singular_name'   => __( 'Listen', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Listens', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Listened', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'listen-of', // microformats 2 property
				'format'          => 'audio', // Post Format that maps to this
				'description'     => __( 'listening to audio; sometimes called a scrobble', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/listen',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'watch',
			array(
				'singular_name'   => __( 'Watch', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Watches', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Watched', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'watch-of', // microformats 2 property
				'format'          => 'video', // Post Format that maps to this
				'description'     => __( 'watching a movie, television show, online video, play or other visual-based event', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/watch',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'checkin',
			array(
				'singular_name'   => __( 'Checkin', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Checkins', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Checked into', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'checkin', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'identifying you are at a particular geographic location', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/checkin',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'wish',
			array(
				'singular_name'   => __( 'Wish', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Wishes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Wished', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'wish-of', // microformats 2 property
				'format'          => 'link', // Post Format that maps to this
				'description'     => __( 'a post indicating a desire/wish. The archive of which would be a wishlist, such as a gift registry or similar', 'indieweb-post-kinds' ),
				'description-url' => '',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'play',
			array(
				'singular_name'   => __( 'Play', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Playing', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Played', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'play-of', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'playing a game', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/game_play',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'weather',
			array(
				'singular_name'   => __( 'Weather', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Weather', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => ' ', // The string for the verb or action (liked this)
				'property'        => 'weather', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'current weather conditions', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/weather',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'exercise',
			array(
				'singular_name'   => __( 'Exercise', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Exercise', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Exercised', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'exercise', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'some form of physical activity or workout (examples: walk, run, cycle, hike, yoga, etc.)', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/exercise',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'trip',
			array(
				'singular_name'   => __( 'Trip', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Trips', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Travelled', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'trip', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'represents a geographic journey', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/trip',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'itinerary',
			array(
				'singular_name'   => __( 'Itinerary', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Itineraries', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Travelled', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'itinerary', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'parts of a scheduled trip including transit by car, plane, train, etc.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/trip',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'eat',
			array(
				'singular_name'   => __( 'Eat', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Eat', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Ate', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'food', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'what you are eating, perhaps for a food dairy', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/food',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'drink',
			array(
				'singular_name'   => __( 'Drink', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Drinks', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Drank', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'food', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'what you are drinking, perhaps for a food dairy', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/food',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'follow',
			array(
				'singular_name'   => __( 'Follow', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Follows', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Followed', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'follow-of', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'indicating you are now following or subscribing to another person`s activities online', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/follow',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'jam',
			array(
				'singular_name'   => __( 'Jam', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Jams', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Listened to', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'jam-of', // microformats 2 property
				'format'          => 'audio', // Post Format that maps to this
				'description'     => __( 'a particularly personally meaningful song (a listen with added emphasis)', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/jam',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'read',
			array(
				'singular_name'   => __( 'Read', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Reads', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Read', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'read-of', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'reading a book, magazine, newspaper, other physical document, or online post', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/read',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'quote',
			array(
				'singular_name'   => __( 'Quote', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Quotes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Quoted', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'quotation-of', // microformats 2 property
				'format'          => 'quote', // Post Format that maps to this
				'description'     => __( 'quoted content', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/quote',
				'title'           => false, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'mood',
			array(
				'singular_name'   => __( 'Mood', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Moods', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Felt', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'mood', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'how you are feeling (example: happy, sad, indifferent, etc.)', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/mood',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'recipe',
			array(
				'singular_name'   => __( 'Recipe', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Recipes', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Cooked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'recipe', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'list of ingredients and directions for making food or drink', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/recipe',
				'title'           => true, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'issue',
			array(
				'singular_name'   => __( 'Issue', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Issues', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Filed an Issue', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'in-reply-to', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'Issue is a special kind of article post that is a reply to typically some source code, though potentially anything at a source control repository.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/issue',
				'title'           => true, // Should this kind have an explicit title
				'show'            => true, // Show in Settings
			)
		);
		register_post_kind(
			'question',
			array(
				'singular_name'   => __( 'Question', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Questions', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Asked a question', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'question', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'Question is a post type for soliciting answer replies, which are then typically up/down voted by others and then displayed underneath the question post ordered by highest positive vote count rather than time ordered.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/question',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'sleep',
			array(
				'singular_name'   => __( 'Sleep', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Sleeps', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Slept', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'sleep', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'Sleep is a passive metrics post type that indicates how much time (and often a graph of how deeply) a person has slept.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/sleep',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'event',
			array(
				'singular_name'   => __( 'Event', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Events', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Planned', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'event', // microformats 2 property
				'format'          => '', // Post Format that maps to this
				'description'     => __( 'An event is a type of post that in addition to a post name (event title) has a start datetime (likely end datetime), and a location.', 'indieweb-post-kinds' ),
				'description-url' => 'https://indieweb.org/event',
				'title'           => true, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
		register_post_kind(
			'acquisition',
			array(
				'singular_name'   => __( 'Acquisition', 'indieweb-post-kinds' ), // Name for one instance of the kind
				'name'            => __( 'Acquisitions', 'indieweb-post-kinds' ), // General name for the kind plural
				'verb'            => __( 'Acquired', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
				'property'        => 'acquired-of', // microformats 2 property
				'format'          => 'status', // Post Format that maps to this
				'description'     => __( 'Purchases, gifts, found things, or objects donated', 'indieweb-post-kinds' ),
				'description-url' => 'http://indieweb.org/acquisition',
				'title'           => false, // Should this kind have an explicit title
				'show'            => false, // Show in Settings
			)
		);
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
		if ( 'all' === $kind ) {
			return static::$kinds;
		}
		if ( ! array_key_exists( $kind, static::$kinds ) ) {
			return false;
		}
		$k = static::$kinds[ $kind ];
		if ( 'all' === $property ) {
			return $k;
		}
		if ( ! array_key_exists( $property, get_object_vars( $k ) ) ) {
			return false;
		}
		return $k->$property;
	}

	public static function webmention_links( $links, $post_id ) {
		$mf2_post = new MF2_Post( $post_id );
		$cite     = $mf2_post->fetch( self::get_kind_info( $mf2_post->get( 'kind' ), 'property' ) );
		$cites    = ifset( $cite['url'] );
		if ( is_string( $cites ) ) {
			$links[] = $cites;
		}
		if ( is_array( $cites ) ) {
			$links = array_merge( $links, $cites );
			$links = array_unique( $links );
		}
		return $links;
	}

	public static function enclosure_links( $links, $post_id ) {
		$mf2_post = new MF2_Post( $post_id );
		if ( in_array( $mf2_post->kind, array( 'photo', 'video', 'audio' ), true ) ) {
			$cite  = $mf2_post->fetch( self::get_kind_info( $mf2_post->get( 'kind' ), 'property' ) );
			$cites = ifset( $cite['url'] );
			if ( is_string( $cites ) ) {
				$links[] = $cites;
			}
			if ( is_array( $cites ) ) {
				$links = array_merge( $links, $cites );
				$links = array_unique( $links );
			}
		}
		return $links;
	}

	public static function kind_dropdown( $post_type, $which ) {
		if ( 'post' === $post_type ) {
			$taxonomy      = 'kind';
			$selected      = isset( $_GET[ $taxonomy ] ) ? $_GET[ $taxonomy ] : '';
			$kind_taxonomy = get_taxonomy( $taxonomy );
			wp_dropdown_categories(
				array(
					/* translators: All */
					'show_option_all' => sprintf( __( 'All %1s', 'indieweb-post-kinds' ), $kind_taxonomy->label ),
					'taxonomy'        => $taxonomy,
					'name'            => $taxonomy,
					'orderby'         => 'name',
					'selected'        => $selected,
					'hierarchical'    => false,
					'show_count'      => true,
					'hide_empty'      => true,
					'value_field'     => 'slug',
				)
			);
		}
	}

	public static function publish( $post_id, $post = null ) {
		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( count( wp_get_post_terms( $post_id, 'kind' ) ) <= 0 ) {
			set_post_kind( $post_id, get_option( 'kind_default' ) );
		}
	}

	public static function transition( $new, $old, $post ) {
		if ( 'publish' === $new ) {
			self::publish( $post->ID, $post );
		}
	}

	public static function post_class( $classes ) {
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
		if ( is_array( $post ) && isset( $post['id'] ) ) {
			$post = $post['id'];
		}
		$post = get_post( $post );
		if ( ! $post ) {
			return false; }
		$_kind = get_the_terms( $post->ID, 'kind' );
		if ( ! empty( $_kind ) ) {
			$kind = array_shift( $_kind );
			return $kind->slug;
		} else {
			return false; }
	}
	public static function get_post_kind( $post = null ) {
		$kind = get_post_kind_slug( $post );
		if ( $kind ) {
			return self::get_kind_info( $kind, 'singular_name' );
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
			foreach ( (array) $kinds as $single ) {
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

	public static function set_rest_post_kind( $kind, $post_array ) {
		if ( isset( $post_array['id'] ) ) {
			self::set_post_kind( $post_array['id'], $kind );
		}
	}

	public static function before_kind() {
		return apply_filters( 'kind_icon_display', true );
	}

	/**
	 * Display before Kind - either icon text or no display
	 *
	 * @param string $kind The slug for the kind of the current post
	 * @param string $display Override display
	 * @return string Marked up kind information
	 */
	public static function get_before_kind( $kind, $display = null ) {
		if ( ! self::before_kind() ) {
			return '';
		}
		if ( ! $display ) {
			$display = get_option( 'kind_display' );
		}
		$text = '<span class="kind-display-text">' . self::get_kind_info( $kind, 'verb' ) . '</span> ';
		$icon = self::get_icon( $kind );
		// Hide Icon in Feed View
		if ( 'text' !== $display && is_feed() ) {
			$icon = '';
		}
		switch ( $display ) {
			case 'both':
				return $icon . $text;
			case 'icon':
				return $icon;
			case 'text':
				return $text;
			default:
				return '';
		}
	}

	public static function get_icon( $kind, $echo = false ) {
		$icon = self::get_kind_info( $kind, 'icon' );
		// Use a sprite otherwise use passed URL
		if ( ! wp_http_validate_url( $icon ) ) {
			// Substitute another svg sprite file
			$sprite = apply_filters( 'kind_icon_sprite', plugins_url( 'kinds.svg', dirname( __FILE__ ) ), $kind );
			if ( '' === $sprite ) {
				return '';
			}
			$sprite = $sprite . '#' . $kind;
		}
		$name   = self::get_kind_info( $kind, 'singular_name' );
		$return = sprintf( '<svg class="svg-icon svg-%1$s" aria-hidden="true" aria-label="%2$s" title="%2$s" ><use xlink:href="%3$s"></use></svg>', esc_attr( $kind ), esc_attr( $name ), esc_url_raw( $sprite ) );
		if ( $echo ) {
			echo $return; // phpcs:ignore
		}
		return $return;
	}
} // End Class Kind_Taxonomy


