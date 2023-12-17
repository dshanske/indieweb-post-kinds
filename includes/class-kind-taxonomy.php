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
		$wp_version = get_bloginfo( 'version' );
		require_once plugin_dir_path( __FILE__ ) . '/register-kinds.php';

		// Add the Correct Archive Title to Kind Archives.
		if ( version_compare( $wp_version, '5.5', '>' ) ) {
			add_filter( 'get_the_archive_title', array( self::class, 'kind_archive_title' ), 10 );
		} else {
			add_filter( 'get_the_archive_title', array( self::class, 'kind_archive_title' ), 10, 3 );
		}

		add_filter( 'get_the_archive_title_prefix', array( self::class, 'kind_archive_prefix' ), 10 );
		add_filter( 'get_the_archive_description', array( self::class, 'kind_archive_description' ), 10 );
		add_filter( 'document_title_parts', array( self::class, 'document_title_parts' ), 10 );

		// Add Kind Permalinks.
		add_filter( 'post_link', array( self::class, 'kind_permalink' ), 10, 3 );
		add_filter( 'post_type_link', array( self::class, 'kind_permalink' ), 10, 3 );

		// Query Variable to Exclude Kinds from Feed
		add_filter( 'query_vars', array( self::class, 'query_vars' ) );
		add_action( 'pre_get_posts', array( self::class, 'kind_filter_query' ) );
		add_action( 'pre_get_posts', array( self::class, 'kind_photo_filter' ) );
		add_action( 'pre_get_posts', array( self::class, 'kind_alias_filter' ) );
		add_action( 'pre_get_posts', array( self::class, 'kind_firehose_query' ), 99 );

		// Add Dropdown
		add_action( 'restrict_manage_posts', array( self::class, 'kind_dropdown' ), 10, 2 );

		// Add Links to Ping to the Webmention Sender.
		add_filter( 'webmention_links', array( self::class, 'webmention_links' ), 11, 2 );

		// Add Links to Enclosures if Appropriate
		add_filter( 'enclosure_links', array( self::class, 'enclosure_links' ), 11, 2 );

		// Add Classes to Post.
		add_filter( 'post_class', array( self::class, 'post_class' ) );

		// Trigger Webmention on Change in Post Status.
		add_filter( 'transition_post_status', array( self::class, 'transition' ), 10, 3 );
		// On Post Save Set Post Format
		add_action( 'save_post', array( self::class, 'post_formats' ), 99, 3 );

		// Create hook triggered by change of kind
		add_action( 'set_object_terms', array( self::class, 'set_object_terms' ), 10, 6 );

		add_filter( 'single_post_title', array( self::class, 'single_post_title' ), 9, 2 );
		add_filter( 'the_title', array( self::class, 'the_title' ), 9, 2 );
		add_filter( 'get_sample_permalink', array( self::class, 'get_sample_permalink' ), 12, 5 );

		add_action( 'rest_api_init', array( self::class, 'rest_kind' ) );

		add_filter( 'embed_template_hierarchy', array( self::class, 'embed_template_hierarchy' ) );
		add_filter( 'template_include', array( self::class, 'template_include' ) );

		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	/** Template Redirect
	 *
	 */
	public static function template_include( $template ) {
		global $wp_query;

		// Only use this template for archive pages
		if ( is_singular() ) {
			return $template;
		}
		// if this is not a request for 'kind_photos'.
		if ( ! isset( $wp_query->query_vars['kind_photos'] ) ) {
					return $template;
		}
		$photo_template = locate_template( 'kind-photos.php' );
		if ( '' !== $photo_template ) {
			return $photo_template;
		}
		return $template;
	}

	/**
	 * Register the Route.
	 */
	public static function register_routes() {
		register_rest_route(
			'post-kinds/1.0',
			'/fields',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'read' ),
					'args'                => array(
						'kind' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'permission_callback' => function () {
						return current_user_can( 'read' );
					},
				),
			)
		);
	}

	public static function read( $request ) {
		$kind = $request->get_param( 'kind' );
		return self::get_kind_info( $kind, 'all' );
	}


	/**
	 * Add our query variables to query_vars list.
	 *
	 * @access public
	 *
	 * @param array $qvars Current query_vars.
	 * @return array
	 */
	public static function query_vars( $qvars ) {
		$qvars[] = 'exclude';
		$qvars[] = 'exclude_terms';
		$qvars[] = 'kind_photos';
		$qvars[] = 'kind_firehose';
		return $qvars;
	}

	/**
	 * Filter the query for our post kinds.
	 *
	 * @access public
	 *
	 * @param $query
	 */
	public static function kind_photo_filter( $query ) {
		// check if the user is requesting an admin page
		if ( is_admin() || ! $query->is_main_query() ) {
			return $query;
		}
		$photos = get_query_var( 'kind_photos' );
		// Return if  not set
		if ( $photos ) {
			$query->set( 'posts_per_page', 30 );
			$query->is_archive      = true;
			$query->is_comment_feed = false;
			$query->is_home         = false;
			$query->set(
				'meta_query',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_content_img_ids',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'mf2_photo',
						'compare' => 'EXISTS',
					),
				)
			);
		}
		return $query;
	}

	/**
	 * Filter the query for our post kinds.
	 *
	 * @access public
	 *
	 * @param $query
	 */
	public static function kind_filter_query( $query ) {
		// check if the user is requesting an admin page
		if ( is_admin() || ! $query->is_main_query() ) {
			return $query;
		}
		$exclude = get_query_var( 'exclude' );
		// Return if both are not set
		if ( ! taxonomy_exists( $exclude ) ) {
			return $query;
		}
		$filter = get_query_var( 'exclude_terms' );
		if ( empty( $filter ) ) {
			return $query;
		}
		$filter   = explode( ',', $filter );
		$operator = 'NOT IN';
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => $exclude,
					'field'    => 'slug',
					'terms'    => $filter,
					'operator' => $operator,
				),
			)
		);
		return $query;
	}

	/**
	 * Filter the main query for post kinds.
	 *
	 * @access public
	 *
	 * @param $query
	 */
	public static function kind_firehose_query( $query ) {

		// check if the user is requesting an admin page
		if ( is_admin() || ! $query->is_main_query() ) {
			return $query;
		}

		$filter = get_query_var( 'kind_firehose' );
		if ( ! empty( $filter ) ) {
			$query->is_archive      = true;
			$query->is_comment_feed = false;
			$query->is_home         = false;
			$query->is_front_page   = false;
			$query->is_single       = false;
			$query->is_posts_page   = false;

			return $query;
		}

		if ( $query->is_archive() ) {
			return $query;
		}

		$firehose = get_option( 'kind_firehose' );
		if ( empty( $firehose ) || ! is_array( $firehose ) ) {
			return $query;
		}

		$query->set(
			'tax_query',
			array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'kind',
					'field'    => 'slug',
					'terms'    => $firehose,
					'operator' => 'IN',
				),
				array(
					'taxonomy' => 'kind',
					'operator' => 'NOT EXISTS',
				),
			)
		);
		return $query;
	}

	/**
	 * Allows for some pre-defined aliases
	 *
	 * @access public
	 *
	 * @param $query
	 */
	public static function kind_alias_filter( $query ) {
		if ( empty( get_query_var( 'kind' ) ) ) {
			return $query;
		}

		$kind = get_query_var( 'kind' );
		if ( 'food' === $kind ) {
			$query->set( 'kind', 'eat,drink' );
		}
		if ( 'reaction' === $kind ) {
			$query->set( 'kind', 'bookmark,repost,like,favorite' );
		}
		if ( 'media' === $kind ) {
			$query->set( 'kind', 'watch,read,listen,play' );
		}
		return $query;
	}

	/**
	 * Register our REST API endpoint for post kinds.
	 *
	 * @access public
	 */
	public static function rest_kind() {
		register_rest_field(
			'post',
			'kind',
			array(
				'get_callback'    => array( self::class, 'get_post_kind_slug' ),
				'update_callback' => array( self::class, 'set_rest_post_kind' ),
				'schema'          => array(
					'kind' => __( 'Post Kind', 'indieweb-post-kinds' ),
					'type' => 'string',
				),
			)
		);
	}

	/**
	 * Filter template hierarchy to include our template files.
	 *
	 * @access public
	 *
	 * @param array $templates Array of template file names.
	 * @return mixed
	 */
	public static function embed_template_hierarchy( $templates ) {
		$object = get_queried_object();
		if ( ! empty( $object->post_type ) ) {
			$post_kind = get_post_kind( $object );
			if ( $post_kind ) {
				array_unshift( $templates, "embed-{$object->post_type}-{$post_kind}.php" );
			}
		}
		return $templates;
	}

	/**
	 * Generate a sample permalink for a post.
	 *
	 * @access public
	 *
	 * @param string  $permalink Current permalink.
	 * @param int     $post_id   Post ID.
	 * @param string  $title     Current post title.
	 * @param string  $name      Current post name.
	 * @param WP_Post $post      Post object.
	 * @return mixed
	 */
	public static function get_sample_permalink( $permalink, $post_id, $title, $name, $post ) {
		if ( 'publish' === $post->post_status || ! empty( $post->post_title ) ) {
			return $permalink;
		}
		// Only kick in if the permalink would be the post_id otherwise.
		if ( is_numeric( $permalink[1] ) && $post_id === (int) $permalink[1] ) {
			$excerpt = self::get_excerpt( $post );
			$excerpt = sanitize_title( mb_strimwidth( wp_strip_all_tags( $excerpt ), 0, 40 ) ); // phpcs:ignore
			if ( ! empty( $excerpt ) ) {
				$permalink[1] = wp_unique_post_slug( $excerpt, $post_id, $post->post_status, $post->post_type, $post->post_parent );
			}
		}
		return $permalink;
	}

	/**
	 * Generate a post title.
	 *
	 * @access public
	 *
	 * @param int!WP_Post    $post Post ID or Post Object.
	 * @return string
	 */
	public static function generate_title( $post, $length = 40 ) {
		$post = get_post( $post );
		if ( ! $post instanceof WP_Post ) {
			return null;
		}
		$kind    = get_post_kind_slug( $post );
		$excerpt = wp_strip_all_tags( self::get_excerpt( $post ) );
		if ( ! in_array( $kind, array( 'note', 'article' ), true ) || ! $kind ) {
			$kind_post = new Kind_Post( $post );
			$cite      = $kind_post->get_cite();
			if ( Parse_This_MF2::is_microformat( $cite ) ) {
				if ( array_key_exists( 'name', $cite['properties'] ) ) {
					$excerpt = $cite['properties']['name'];
					if ( is_array( $excerpt ) ) {
						$excerpt = $excerpt[0];
					}
				}
			}
		}
		return mb_strimwidth( $excerpt, 0, $length, '...' ); // phpcs:ignore
	}

	/**
	 * Filter the post title. Add a symbol at the end if generated title.
	 *
	 * @access public
	 *
	 * @param string $title   Current post title
	 * @param int    $post_id Post ID
	 * @return string
	 */
	public static function the_title( $title, $post_id ) {
		if ( ! $title && is_admin() ) {
			$title = self::generate_title( $post_id, 60 );
			if ( '' === $title ) {
				$title = '&diams;'; }
		}
		$post_kind = get_post_kind( $post_id );
		if ( get_option( 'kind_title' ) && '' !== $post_kind ) {
			if ( is_feed() && empty( $title ) ) {
				$title = sprintf( '[%1$s] %2$s', $post_kind, self::generate_title( $post_id, 60 ) );
			}
		}
		return $title;
	}

	/**
	 * Filter the single post title.
	 *
	 * @access public
	 *
	 * @param string $title   Current post title
	 * @param WP_Post    $post Post object
	 * @return string
	 */
	public static function single_post_title( $title, $post ) {
		if ( ! is_single() ) {
			return $title;
		}

		if ( empty( $title ) ) {
			$title = self::generate_title( $post );
		}
		$post_kind = get_post_kind( $post );
		if ( get_option( 'kind_title' ) && '' !== $post_kind && $post_kind ) {
			$title = '[' . $post_kind . '] ' . $title;
		}
		return $title;
	}


	/**
	 * Filter the post excerpt.
	 *
	 * @access public
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function get_excerpt( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return '';
		}
		if ( ! empty( $post->post_excerpt ) ) {
			return $post->post_excerpt;
		}
		return $post->post_content;
	}

	/**
	 * Set our post object terms.
	 *
	 * @access public
	 *
	 * @param object $object_id  Current object ID.
	 * @param array  $terms      Array of object terms.
	 * @param array  $tt_ids     Array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug
	 * @param bool   $append     If terms should be appended or overwrite.
	 * @param array  $old_tt_ids Array of original trem taxonomy IDs.
	 */
	public static function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( empty( $tt_ids ) && empty( $old_tt_ids ) ) {
			return;
		}
		if ( 'kind' === $taxonomy ) {
			$old_term = get_term_by( 'term_taxonomy_id', array_pop( $old_tt_ids ), 'kind' );
			$old_term = $old_term instanceof WP_Term ? $old_term->slug : '';
			$new_term = get_term_by( 'term_taxonomy_id', array_pop( $tt_ids ), 'kind' );
			$new_term = $new_term instanceof WP_Term ? $new_term->slug : '';
			if ( $old_term !== $new_term ) {
				// Trigger a hook on a changed kind identifying old and new so actions can be performed
				do_action( 'change_kind', $object_id, $old_term, $new_term );
			}
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

	public static function get_pagination_regex() {
		global $wp_rewrite;
		return $wp_rewrite->pagination_base . '/?([0-9]{1,})';
	}

	public static function get_feed_regex( $with_base = true ) {
		global $wp_rewrite;
		// Build a regex to match the feed section of URLs, something like (feed|atom|rss|rss2)/?
		$feedregex2 = '';
		foreach ( (array) $wp_rewrite->feeds as $feed_name ) {
			$feedregex2 .= $feed_name . '|';
		}

		$feedregex2 = '(' . trim( $feedregex2, '|' ) . ')';
		return ( $with_base ) ? $wp_rewrite->feed_base . '/' . $feedregex2 : $feedregex2;
	}

	public static function generate_permastruct( $elements ) {
		if ( empty( $elements ) || ! is_array( $elements ) ) {
			return '';
		}
		$elements[] = '?$';
		return implode( '/', $elements );
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
			'desc_field_description'     => __( 'This will appear on archives as the description if your theme supports', 'indieweb-post-kinds' ),
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
			'back_to_items'              => __( 'Back to Kinds', 'indieweb-post-kinds' ),
			'item_link'                  => __( 'Kind Link', 'indieweb-post-kinds' ),
			'item_link_description'      => __( 'A link to a kind', 'indieweb-post-kinds' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => false,
			'show_ui'            => true,
			'show_in_menu'       => WP_DEBUG,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => false,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => false,
			'show_admin_column'  => true,
			'meta_box_cb'        => array( self::class, 'select_metabox' ),
			'rewrite'            => true,
			'query_var'          => true,
			'default_term'       => array(
				'name' => __( 'Article', 'indieweb-post-kinds' ),
				'slug' => 'article',
			),

		);
		register_taxonomy( 'kind', array( 'post' ), $args );
		add_permastruct( 'kind_date', 'kind/%kind%/%year%/%monthnum%/' );

		$kind_rewrite_taxonomies = apply_filters( 'kind_rewrite_taxonomies', array( 'post_tag', 'category', 'series' ) );

		// For each kind, support filtering by other taxonomies.
		foreach ( $kind_rewrite_taxonomies as $taxonomy ) {
			add_permastruct( 'kind_' . $taxonomy, 'kind/%kind%/' . $taxonomy . '/%' . $taxonomy . '%' );
		}

		$kind_exclude_slug = apply_filters( 'kind_exclude_slug', 'exclude' );

		add_rewrite_tag( '%kind_exclude%', '([^/]*)', 'exclude=' );
		add_rewrite_tag( '%kind_exclude_terms%', '([a-z,]+)', 'exclude_terms=' );
		add_permastruct( 'kind_excludes', $kind_exclude_slug . '/%kind_exclude%/%kind_exclude_terms%' );

		$kind_photos_slug   = apply_filters( 'kind_photos_slug', 'photos' );
		$kind_firehose_slug = apply_filters( 'kind_firehose_slug', 'firehose' );

		$year_regex       = '([0-9]{4})';
		$month_regex      = '(0-9]{2})';
		$day_regex        = '([0-9]{1,})';
		$pagination_regex = self::get_pagination_regex();
		$feed_regex       = self::get_feed_regex();
		$feed_regex2      = self::get_feed_regex( false );
		$tax_regex        = '([^/]*)';
		$term_regex       = '([^/]*)';

		// Year Archives for Photos
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $year_regex, $pagination_regex ) ),
			'index.php?year=$matches[1]&paged=$matches[2]&kind_photos=1',
			'top'
		);
		add_rewrite_rule(
			implode( '/', array( $kind_photos_slug, $year_regex, '?$' ) ),
			'index.php?year=$matches[1]&kind_photos=1',
			'top'
		);

		// Year and Month Archive for Photos
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $year_regex, $month_regex, $pagination_regex ) ),
			'index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]&kind_photos=1',
			'top'
		);
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $year_regex, $month_regex ) ),
			'index.php?year=$matches[1]&monthnum=$matches[2]&kind_photos=1',
			'top'
		);

		// Year and Month And Day Archive for Photos
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $year_regex, $month_regex, $day_regex, $pagination_regex ) ),
			'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]&kind_photos=1',
			'top'
		);
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $year_regex, $month_regex, $day_regex ) ),
			'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&kind_photos=1',
			'top'
		);

		// Month And Day Archive for Photos
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $month_regex, $day_regex, $pagination_regex ) ),
			'index.php?monthnum=$matches[1]&day=$matches[2]&paged=$matches[3]&kind_photos=1',
			'top'
		);
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $month_regex, $day_regex ) ),
			'index.php?monthnum=$matches[1]&day=$matches[2]&kind_photos=1',
			'top'
		);

		// Root Photos Feed.
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $feed_regex2 ) ),
			'index.php?feed=$matches[1]&kind_photos=1',
			'top'
		);
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $feed_regex ) ),
			'index.php?feed=$matches[1]&kind_photos=1',
			'top'
		);

		// Root Photos Pagination.
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug, $pagination_regex ) ),
			'index.php?kind_photos=1&paged=$matches[1]',
			'top'
		);

		// Root Photos.
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_photos_slug ) ),
			'index.php?kind_photos=1',
			'top'
		);

		// Root Firehose Pagination.
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_firehose_slug, $pagination_regex ) ),
			'index.php?kind_firehose=1&paged=$matches[1]',
			'top'
		);

		// Root Firehose Feed.
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_firehose_slug, $feed_regex ) ),
			'index.php?kind_firehose=1&feed=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_firehose_slug, $feed_regex2 ) ),
			'index.php?kind_firehose=1&feed=$matches[1]',
			'top'
		);

		// Root Firehose.
		add_rewrite_rule(
			self::generate_permastruct( array( $kind_firehose_slug ) ),
			'index.php?kind_firehose=1',
			'top'
		);
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

	/**
	 * Update our post kind if the term already exists.
	 *
	 * @access private
	 *
	 * @param $term
	 */
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

	/**
	 * Create our post kind term.
	 *
	 * @access private
	 *
	 * @param $term
	 */
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

	/**
	 * Filter our post kind permalink.
	 *
	 * @access public
	 *
	 * @param string $permalink Permalink string to filter.
	 * @param int    $post_id   Post ID.
	 * @param bool   $leavename Whether or not to leave the post name.
	 * @return mixed
	 */
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

	/**
	 * Fetch the current post kind terms from the current query.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public static function get_terms_from_query() {
		global $wp_query;
		$terms = array();
		if ( ! empty( $wp_query->tax_query->queried_terms ) ) {
			$queried = wp_list_pluck( $wp_query->tax_query->queried_terms, 'terms' );
			foreach ( $queried as $tax => $slugs ) {
				foreach ( $slugs as $slug ) {
					$terms[] = get_term_by( 'slug', $slug, $tax );
				}
			}
		}
		return $terms;
	}

	/**
	 * Filters the post kind archive prefix.
	 * Prefix Introduced in WP5.5
	 *
	 * @access public
	 *
	 * @param string $prefix Archive title prefix.
	 * @return string|void
	 */
	public static function kind_archive_prefix( $prefix ) {
		if ( is_tax() || is_category() || is_tag() ) {
			$terms  = self::get_terms_from_query();
			$tax    = get_taxonomy( reset( $terms )->taxonomy );
			$plural = ( count( $terms ) === 1 );
			return ( $plural ? $tax->labels->singular_name : $tax->labels->name ) . ':';
		}
		return $prefix;
	}

	/**
	 * Filters the post kind archive title.
	 * Original Title and Prefix introduced in WP5.5.
	 *
	 * @access public
	 *
	 * @param string $title Current archive title value.
	 * @param string $original_title Title without prefix.
	 * @param string $prefix Archive title prefix.
	 * @return string|void
	 */
	public static function kind_archive_title( $title, $original_title = null, $prefix = null ) {
		$return     = array();
		$wp_version = get_bloginfo( 'version' );

		$prefix = apply_filters( 'get_the_archive_title_prefix', $prefix );

		if ( is_tax() || is_category() || is_tag() ) {
			$terms = self::get_terms_from_query();
			foreach ( $terms as $term ) {
				if ( 'kind' === $term->taxonomy ) {
					$return[] = self::get_kind_info( $term->slug, 'name' );
				} else {
					$return[] = $term->name;
				}
			}
			if ( $return ) {
				$title = join( ', ', $return );
			}
			if ( is_year() ) {
				/* translators: 1: Taxonomy. Yearly archive title. 2: Year */
				$title = sprintf( __( '%1$1s - %2$2s', 'indieweb-post-kinds' ), $title, get_the_date( _x( 'Y', 'yearly archives date format', 'indieweb-post-kinds' ) ) );
			} elseif ( is_month() ) {
				/* translators: 1: Taxonomy. 2: Month name and year */
				$title = sprintf( __( '%1$1s - %2$2s', 'indieweb-post-kinds' ), $title, get_the_date( _x( 'F Y', 'monthly archives date format', 'indieweb-post-kinds' ) ) );
			} elseif ( is_day() ) {
				/* translators: 1. Taxonomy . 1: Date */
				$title = sprintf( __( '%1$1s - %2$2s', 'indieweb-post-kinds' ), $title, get_the_date( _x( 'F j, Y', 'daily archives date format', 'indieweb-post-kinds' ) ) );
			}
		} elseif ( get_query_var( 'kind_firehose' ) ) {
			/* translators: 1. Title 2. Translated term for Firehose Feed  */
			$title = sprintf( __( '%1$s - %2$2s', 'indieweb-post-kinds' ), $title, __( 'Firehose', 'indieweb-post-kinds' ) );
		}

		if ( $prefix ) {
				$title = sprintf(
					/* translators: 1: Title prefix. 2: Title. */
					_x( '%1$s %2$s', 'archive title', 'indieweb-post-kinds' ),
					$prefix,
					'<span>' . $title . '</span>'
				);
		}
		return $title;
	}

	/**
	 * Filters the post kind archive description.
	 *
	 * @access public
	 *
	 * @param string $title Current archive description.
	 * @return string
	 */
	public static function kind_archive_description( $title ) {
		$return = array();
		if ( is_tax( 'kind' ) ) {
			$terms = self::get_terms_from_query();
			foreach ( $terms as $term ) {
				if ( 'kind' === $term->taxonomy ) {
					$return[] = self::get_kind_info( $term->slug, 'description' );
				} else {
					$return[] = $term->name;
				}
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

	/**
	 * Fiters the `<title>` tag parts for the post kind.
	 *
	 * @access public
	 *
	 * @param array $parts Document title parts.
	 * @return mixed
	 */
	public static function document_title_parts( $parts ) {
		if ( is_tax() || is_category() || is_tag() ) {
			$terms = self::get_terms_from_query();
			foreach ( $terms as $term ) {
				if ( 'kind' === $term->taxonomy ) {
					$return[] = self::get_kind_info( $term->slug, 'name' );
				} else {
					$return[] = $term->name;
				}
			}
			if ( $return ) {
				$parts['title'] = join( ', ', $return );
			}
		}
		return $parts;
	}

	/**
	 * Callback for the taxonomy meta box for our post kinds taxonomy.
	 *
	 * @access public
	 *
	 * @param WP_Post $post Post object.
	 */
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
		} elseif ( 'publish' === get_post_status( $post ) ) {
			// On existing published posts without a kind fall back on article which most closely mimics the behavior of an unclassified post
			$default = get_term_by( 'slug', 'article', 'kind' );
		} else {
			$default = get_term_by( 'slug', get_option( 'kind_default' ), 'kind' );
		}

		$terms     = get_terms(
			array(
				'taxonomy'   => 'kind',
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

	/**
	 * Register our post kinds.
	 *
	 * @access public
	 *
	 * @param string $slug Post kind slug.
	 * @param array  $args Post kind arguments {
		 * @param string $singular_name  Name for one instance of the kind.
		 * @param string $name   General name for the kind plural.
		 * @param string $verb The string for the verb or action (liked this).
		 * @param string $property Microformats 2 property, superseded by properties
		 * @param array $properties Properties is an array outlining the fields for the particular kind. See Kind Fields class for more details
		 * @param string $format Post Format that maps to this.
		 * @param string $description Description of the Kind
		 * @param url $description-url Link to more information
		 * @param string $title Should this kind have an explicit title.
		 * @param boolean $show Show in Settings.
	 * }
	 * @return bool
	 */
	public static function register_post_kind( $slug, $args ) {
		$kind = new Post_Kind( $slug, $args );
		// Do not allow reregistering existing kinds
		if ( isset( self::$kinds[ $slug ] ) ) {
			return false;
		}
		self::$kinds[ $slug ] = $kind;
		return true;
	}

	/**
	 * Retrieve info on a provided post kind.
	 *
	 * @access public
	 *
	 * @param string $slug Post kind to retrieve.
	 * @return bool|mixed
	 */
	public static function get_post_kind_info( $slug ) {
		if ( isset( self::$kinds[ $slug ] ) ) {
			return self::$kinds[ $slug ];
		}
		return false;
	}

	// Enable a hidden post kind

	/**
	 * Enables a hidden post kind.
	 *
	 * @access public
	 *
	 * @param string $slug Post kind slug.
	 * @param bool   $show Whether or not to show the post kind.
	 * @return bool
	 */
	public static function set_post_kind_visibility( $slug, $show = true ) {
		if ( isset( self::$kinds[ $slug ] ) ) {
			self::$kinds[ $slug ]->show = ( $show ? true : false );
			return true;
		}
		return false;
	}

	/**
	 * Retrieve list of registered post kinds.
	 *
	 * @access public
	 * @return array
	 */
	public static function get_kind_list() {
		return array_keys( self::$kinds );
	}

	/**
	 * Returns all translated strings.
	 *
	 * @param string $kind     Post Kind to return.
	 * @param string $property The individual property
	 * @return string|array Return kind-property. If either is set to all, return all.
	 */
	public static function get_kind_info( $kind, $property ) {
		if ( ! is_string( $kind ) || ! $property ) {
			return false;
		}
		if ( 'all' === $kind ) {
			return self::$kinds;
		}
		if ( ! array_key_exists( $kind, self::$kinds ) ) {
			return false;
		}
		$k = self::$kinds[ $kind ];
		if ( 'all' === $property ) {
			return $k;
		}
		if ( ! array_key_exists( $property, get_object_vars( $k ) ) ) {
			return false;
		}
		return $k->$property;
	}

	/**
	 * Add available webmention links.
	 *
	 * @access public
	 *
	 * @param array $links   Array of existing webmention links.
	 * @param int   $post_id Post ID.
	 * @return array
	 */
	public static function webmention_links( $links, $post_id ) {
		$kind_post = new Kind_Post( $post_id );
		$cites     = $kind_post->get_cite( 'url' );
		if ( is_string( $cites ) ) {
			$links[] = $cites;
		}
		if ( is_array( $cites ) ) {
			$links = array_merge( $links, $cites );
			$links = array_unique( $links );
		}
		return $links;
	}

	/**
	 * Add available enclosure links.
	 *
	 * @access public
	 *
	 * @param array $links   Array of existing enclosure links.
	 * @param int   $post_id Post ID.
	 * @return array
	 */
	public static function enclosure_links( $links, $post_id ) {
		$kind_post = new Kind_Post( $post_id );
		if ( in_array( $kind_post->get_kind(), array( 'photo', 'video', 'audio' ), true ) ) {
			$cites = $kind_post->get_cite( 'url' );
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

	/**
	 * Generate a dropdown of our post kinds for the post list table view.
	 *
	 * @access public
	 * @param string $post_type Current post type being listed.
	 * @param string $which     Current part of the post list table being rendered.
	 */
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

	/**
	 * Set our post kind terms upon publish transition status.
	 *
	 * @access public
	 * @param int          $post_id Current post ID
	 * @param WP_Post|null $post    Current post object.
	 */
	public static function publish( $post_id, $post = null ) {
		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}
		$count = wp_get_post_terms( $post_id, 'kind' );
		if ( is_countable( $count ) && $count <= 0 ) {
			set_post_kind( $post_id, get_option( 'kind_default' ) );
		}
	}

	/**
	 * Maybe set our post kind terms upon post status transition.
	 *
	 * @access public
	 *
	 * @param string  $new  New post status.
	 * @param string  $old  Old post status.
	 * @param WP_Post $post Post object.
	 */
	public static function transition( $new, $old, $post ) {
		if ( 'publish' === $new ) {
			self::publish( $post->ID, $post );
		}
	}

	/**
	 * Filter the classes on a post's markup.
	 *
	 * @access public
	 *
	 * @param array $classes Current post classes.
	 * @return array
	 */
	public static function post_class( $classes ) {
		if ( 'post' !== get_post_type() ) {
			return $classes;
		}
		$classes[] = 'kind-' . get_post_kind_slug();
		return $classes;
	}

	/**
	 * Returns a pretty, translated version of a post kind slug.
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
			return false;
		}
		return get_term_link( $term );
	}

	/**
	 * Retrieve a total count statistic for a given post kind.
	 *
	 * @access public
	 *
	 * @param string $kind Post kind to get post count for.
	 * @return bool|int
	 */
	public static function get_post_kind_count( $kind ) {
		$term = get_term_by( 'slug', $kind, 'kind' );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}
		return $term->count;
	}

	/**
	 * Returns the post kind slug for the current post.
	 *
	 * @access public
	 *
	 * @param WP_Post|int|null $post Post to retrieve post kind slug for.
	 * @return bool|string
	 */
	public static function get_post_kind_slug( $post = null ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return false; }
		$_kind = get_the_terms( $post, 'kind' );
		if ( ! empty( $_kind ) ) {
			$kind = array_shift( $_kind );
			return $kind->slug;
		} else {
			return false; }
	}

	/**
	 * Returns the post kind name for the current post.
	 *
	 * @access public
	 *
	 * @param WP_Post|int|null $post
	 * @return array|bool|string
	 */
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
		$kind = array();
		if ( $kinds ) {
			foreach ( (array) $kinds as $single ) {
				$kind[] = sanitize_key( $single );
			}
		}
		return has_term( $kind, 'kind', $post );
	}

	/**
	 * Assign a kind to a post.
	 *
	 * @param int|WP_Post $post The post for which to assign a kind.
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

	/**
	 * Update callback for REST API endpoint.
	 *
	 * @access public
	 *
	 * @param string $kind Post kind being processed.
	 * @param $post_array
	 */
	public static function set_rest_post_kind( $kind, $post_array ) {
		if ( isset( $post_array['id'] ) ) {
			self::set_post_kind( $post_array['id'], $kind );
		}
	}

	/**
	 * Whether or not to display the before kind content.
	 *
	 * @access public
	 * @return mixed|void
	 */
	public static function before_kind() {
		return apply_filters( 'kind_icon_display', true );
	}

	/**
	 * Display before Kind - either icon text or no display.
	 *
	 * @param string $kind    The slug for the kind of the current post.
	 * @param string $display Override display;
	 * @return string Marked up kind information.
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

	/**
	 * Retrieve the icon SVG for a provided post kind.
	 *
	 * @access public
	 *
	 * @param string $kind Post kind to retrieve the icon for.
	 * @param bool   $echo Whether or not the icon should be echo'd.
	 * @return string
	 */
	public static function get_icon( $kind, $echo = false ) {
		$name       = self::get_kind_info( $kind, 'singular_name' );
		$svg        = sprintf( '%1$ssvgs/%2$s.svg', plugin_dir_path( __DIR__ ), $kind );
		$attributes = apply_filters( 'post_kinds_icon_attributes', 'style="display: inline-block; max-height: 1rem; margin-right: 0.5rem"' );
		if ( file_exists( $svg ) ) {
			$return = sprintf( '<span class="svg-icon svg-%1$s" aria-label="%2$s" title="%2$s" %3$s><span aria-hidden="true">%4$s</span></span>', esc_attr( $kind ), esc_attr( $name ), $attributes, file_get_contents( $svg ) );
		} else {
			return '';
		}
		if ( $echo ) {
			echo $return; // phpcs:ignore
		}
		return $return;
	}
} // End Class Kind_Taxonomy
