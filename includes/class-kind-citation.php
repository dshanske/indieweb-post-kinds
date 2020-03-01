<?php
/**
 * Kind Citation Class
 *
 * @package Post Kind
 * Used to Add a Citation Post Type
 *
 */

class Kind_Citation {
	private static $properties;
	private static $version;

	public static function register() {
		$cls           = get_called_class();
		self::$version = Post_Kinds_Plugin::$version;
		add_action( 'edit_form_after_title', array( $cls, 'edit_form_after_title' ) );
		add_action( 'save_post_citation', array( $cls, 'save_post_citation' ), 10, 3 );
		add_filter( 'enter_title_here', array( $cls, 'enter_title_here' ), 10, 2 );
		static::$properties = array(
			'url'         => array(
				'type'  => 'url',
				'label' => __( 'URL', 'indieweb-post-kinds' ),
			),
			'author'      => array(
				'type'  => 'author',
				'label' => __(
					'Author',
					'indieweb-post-kinds'
				),
			),
			'publication' => array(
				'type'  => 'text',
				'label' => __( 'Publication/Website', 'indieweb-post-kinds' ),
			),
			'published'   => array(
				'type'  => 'datetime',
				'label' => __( 'Publish Date', 'indieweb-post-kinds' ),
			),
			'featured'    => array(
				'type'  => 'url',
				'label' => __(
					'Featured Media',
					'indieweb-post-kinds'
				),
			),
		);
		self::register_taxonomy();
		self::register_post_type();
		self::register_meta();
	}

	public static function enter_title_here( $title, $post ) {
		if ( 'citation' !== get_post_type( $post ) ) {
			return $title;
		}
		return __( 'Add name of citation', 'indieweb-post-kinds' );
	}

	public static function edit_form_after_title() {
			printf( '<a href="#" id="lookup-citation" class="button">%1$s</a>', esc_html__( 'Lookup URL', 'indieweb-post-kinds' ) );
	}

	public static function save_post_citation( $post_id, $post, $update ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$fields = Kind_Fields::rebuild_data( $_POST );
		foreach ( $fields as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

	}

	/**
	 * Enqueue our needed assets.
	 *
	 * @access public
	 */
	public static function enqueue_admin_scripts() {
		if ( 'citation' === get_current_screen()->id ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui' );

			wp_enqueue_script(
				'jquery-ui-timepicker',
				plugins_url( 'js/jquery.timepicker.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::$version,
				true
			);

			wp_enqueue_script(
				'jquery-datepair',
				plugins_url( 'js/jquery.datepair.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::$version,
				true
			);

			wp_enqueue_script(
				'citation',
				plugins_url( 'js/citation.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::$version,
				true
			);

			// Provide a global object to our JS file containing our REST API endpoint, and API nonce
			// Nonce must be 'wp_rest'
			wp_localize_script(
				'citation',
				'PKAPI',
				array(
					'api_nonce'       => wp_create_nonce( 'wp_rest' ),
					'api_url'         => rest_url( '/parse-this/1.0/' ),
					'success_message' => __( 'Your URL has been successfully retrieved and parsed', 'indieweb-post-kinds' ),
					'clear_message'   => __( 'Are you sure you want to clear post properties?', 'indieweb-post-kinds' ),
				)
			);
			wp_enqueue_script(
				'moment',
				plugins_url( 'js/moment.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'2.20.1',
				true
			);
		}
	}


	public static function register_post_type() {
		register_post_type(
			'citation',
			array(
				'label'                => __( 'Citations', 'indieweb-post-kinds' ),
				'labels'               => array(
					'name'                      => __( 'Citations', 'indieweb-post-kinds' ),
					'singular_name'             => __( 'Citation', 'indieweb-post-kinds' ),
					'add_new'                   => _x( 'Add New', 'citation', 'indieweb-post-kinds' ),
					'add_new_item'              => __( 'Add New Citation', 'indieweb-post-kinds' ),
					'edit_item'                 => __( 'Edit Citation', 'indieweb-post-kinds' ),
					'new_item'                  => __( 'New Citation', 'indieweb-post-kinds' ),
					'view_item'                 => __( 'View Citation', 'indieweb-post-kinds' ),
					'view_items'                => __( 'View Citations', 'indieweb-post-kinds' ),
					'search_items'              => __( 'Search Citations', 'indieweb-post-kinds' ),
					'not_found'                 => __( 'No Citations Found', 'indieweb-post-kinds' ),
					'not_found_in_trash'        => __( 'No Citations Found in Trash', 'indieweb-post-kinds' ),
					'parent_item_colon'         => __( 'Parent Citation', 'indieweb-post-kinds' ),
					'all_items'                 => __( 'All Citations', 'indieweb-post-kinds' ),
					'archives'                  => __( 'Citation Archives', 'indieweb-post-kinds' ),
					'attributes'                => __( 'Citation Attributes', 'indieweb-post-kinds' ),
					'insert_into_item'          => __( 'Insert into Citation', 'indieweb-post-kinds' ),
					'uploaded_to_this_item'     => __( 'Uploaded to this Citation', 'indieweb-post-kinds' ),
					'featured_image'            => __( 'Featured', 'indieweb-post-kinds' ),
					'set_featured_image'        => __( 'Set Featured', 'indieweb-post-kinds' ),
					'remove_featured_image'     => __( 'Remove Featured', 'indieweb-post-kinds' ),
					'use_featured_image'        => __( 'Use Featured', 'indieweb-post-kinds' ),
					'menu_name'                 => __( 'Citations', 'indieweb-post-kinds' ),
					'filter_items_list'         => __( 'Filter Citations List', 'indieweb-post-kinds' ),
					'items_list_navigation'     => __( 'Citation list navigation', 'indieweb-post-kinds' ),
					'items_list'                => __( 'Citation list', 'indieweb-post-kinds' ),
					'items_published'           => __( 'Citation published', 'indieweb-post-kinds' ),
					'items_published_privately' => __( 'Citation published privately', 'indieweb-post-kinds' ),
					'items_reverted_to_draft'   => __( 'Citation reverted to draft', 'indieweb-post-kinds' ),
					'items_scheduled'           => __( 'Citation scheduled', 'indieweb-post-kinds' ),
					'items_updated'             => __( 'Citation updated', 'indieweb-post-kinds' ),
				),
				'public'               => true,
				'show_ui'              => true,
				'capability_type'      => 'post',
				'map_meta_cap'         => true,
				'menu_icon'            => 'dashicons-book',
				'hierarchical'         => false,
				'rewrite'              => false,
				'query_var'            => false,
				'show_in_nav_menus'    => false,
				'delete_with_user'     => true,
				'supports'             => array( 'title', 'author', 'thumbnail' ),
				'show_in_rest'         => true,
				'taxonomies'           => array( 'citation_categories' ),
				'register_meta_box_cb' => array( get_called_class(), 'add_meta_boxes' ),
			)
		);
	}

	public static function register_meta() {
		register_meta(
			'post',
			'mf2_url',
			array(
				'object_subtype'    => 'citation',
				'type'              => 'string',
				'description'       => __( 'Citation URL', 'indieweb-post-kinds' ),
				'single'            => true,
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
			)
		);
		register_meta(
			'post',
			'mf2_author',
			array(
				'object_subtype'    => 'citation',
				'type'              => 'string',
				'description'       => __( 'Citation Author', 'indieweb-post-kinds' ),
				'single'            => false,
				'sanitize_callback' => 'sanitize_text',
				'show_in_rest'      => true,
			)
		);

		register_meta(
			'post',
			'mf2_published',
			array(
				'object_subtype'    => 'citation',
				'type'              => 'string',
				'description'       => __( 'Citation Publish Date', 'indieweb-post-kinds' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text',
				'show_in_rest'      => true,
			)
		);

		register_meta(
			'post',
			'mf2_updated',
			array(
				'object_subtype'    => 'citation',
				'type'              => 'string',
				'description'       => __( 'Citation Updated Date', 'indieweb-post-kinds' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);

		register_meta(
			'post',
			'mf2_publication',
			array(
				'object_subtype'    => 'citation',
				'type'              => 'string',
				'description'       => __( 'Citation Publication', 'indieweb-post-kinds' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);
	}

	public static function register_taxonomy() {
			register_taxonomy(
				'citation_tags',
				'citation',
				array(
					'labels'                => array(
						'name' => __( 'Tags', 'indieweb-post-kinds' ),
					),
					'hierarchical'          => false,
					'query_var'             => 'tag',
					'public'                => true,
					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_rest'          => true,
					'rest_base'             => 'citation_tags',
					'rest_controller_class' => 'WP_REST_Terms_Controller',
				)
			);
	}

	/**
	 * Create one or more meta boxes to be displayed on the post editor screen.
	 *
	 * @access public
	 */
	public static function add_meta_boxes() {
		$cls = get_called_class();
		add_action( 'admin_enqueue_scripts', array( $cls, 'enqueue_admin_scripts' ) );
		add_meta_box(
			'replybox-meta', // Unique ID
			esc_html__( 'Properties', 'indieweb-post-kinds' ), // Title
			array( $cls, 'metabox' ), // Callback function
			'citation',
			'normal', // Context
			'default', // Priority
			array(
				'__block_editor_compatible_meta_box' => false,
				'__back_compat_meta_box'             => true,
			)
		);
		add_meta_box(
			'post-data-citation', // Unique ID
			esc_html__( 'Summary and Content', 'indieweb-post-kinds' ), // Title
			array( $cls, 'post_data_citation_box' ), // Callback function
			'citation',
			'normal', // Context
			'default', // Priority
			array(
				'__block_editor_compatible_meta_box' => false,
				'__back_compat_meta_box'             => true,
			)
		);
	}


	/**
	 *
	 *
	 * @param object $post
	 */
	public static function post_data_citation_box( $post ) {
		printf( '<p><label for="excerpt">%1$s</label><textarea class="widefat" rows="3" cols="40" name="excerpt" id="excerpt">%2$s</textarea></p>', esc_html__( 'Summary', 'indieweb-post-kinds' ), esc_html( $post->post_excerpt ) );
		printf( '<p><label for="content">%1$s</label><textarea class="widefat" rows="4" cols="40" name="content" id="content">%2$s</textarea></p>', esc_html__( 'Content', 'indieweb-post-kinds' ), esc_html( $post->post_content ) );
	}


	public static function metabox( $post ) {
		$data = Kind_Fields::get_mf2meta( $post );
		echo Kind_Fields::render( static::$properties, $data ); // phpcs:ignore
	}

}
