<?php
/**
 * Post Kind Post MetaBox Class
 *
 * Sets Up Tabbed Metabox in the Posting UI for Kind data.
 */

class Kind_Metabox {

	/**
	 * @var string $version
	 */
	public static $version;

	/**
	 * Function to initiate our metabox.
	 *
	 * @access public
	 */
	public static function init() {
		self::$version = Post_Kinds_Plugin::$version;
		add_action( 'edit_form_after_title', array( static::class, 'after_title_metabox' ) );
		// Add meta box to new post/post pages only
		add_action( 'load-post.php', array( static::class, 'kindbox_setup' ) );
		add_action( 'load-post-new.php', array( static::class, 'kindbox_setup' ) );
		add_action( 'save_post', array( static::class, 'save_post' ), 8, 2 );
		add_action( 'transition_post_status', array( static::class, 'transition_post_status' ), 5, 3 );
		add_filter( 'wp_insert_post_empty_content', array( static::class, 'wp_insert_post_empty_content' ), 11, 2 );
		add_action( 'change_kind', array( static::class, 'change_kind' ), 10, 3 );
	}

	/**
	 * Function to change our post kind.
	 *
	 * @access public
	 *
	 * @param int    $post_id  Current post ID.
	 * @param string $old_kind Original post kind.
	 * @param string $new_kind New post kind to set.
	 */
	public static function change_kind( $post_id, $old_kind, $new_kind ) {
		if ( empty( $old_kind ) || empty( $new_kind ) ) {
			return;
		}
		if ( $old_kind === $new_kind ) {
			return;
		}

		$kind_post = new Kind_Post( $post_id );
		if ( ! $kind_post ) {
			return;
		}
		$old_prop = Kind_Taxonomy::get_kind_info( $old_kind, 'property' );
		$new_prop = Kind_Taxonomy::get_kind_info( $new_kind, 'property' );
		if ( $old_prop === $new_prop ) {
			return;
		}

		if ( empty( $new_prop ) ) {
			return;
		}
		$old = $kind_post->get( $old_prop );
		if ( ! empty( $old ) ) {
			$kind_post->set( $new_prop, $old );
		}
		$kind_post->delete( $old_prop );
	}

	/**
	 * Function to change if a new post should be considered empty.
	 *
	 * @access public
	 *
	 * @param bool  $maybe_empty Whether or not the post should be considered empty.
	 * @param array $postarr     Data for the post to be inserted.
	 *
	 * @return bool
	 */
	public static function wp_insert_post_empty_content( $maybe_empty, $postarr ) {
		// Always let updates to trash posts through
		if ( 'trash' === $postarr['post_status'] ) {
			return false;
		}
		// Let All Micropub Posts through
		if ( isset( $postarr['meta_input'] ) && isset( $postarr['meta_input']['micropub_auth_response'] ) ) {
			return false;
		}
		if ( ! isset( $postarr['tax_input'] ) && ! isset( $postarr['tax_input']['kind'] ) ) {
			return $maybe_empty;
		}
		$kind = get_term_by( 'id', $postarr['tax_input']['kind'][0], 'kind' );
		$kind = ( $kind instanceof WP_Term ) ? $kind->slug : '';
		// Use traditional rules for articles
		if ( 'article' === $kind || ! $kind ) {
			return $maybe_empty;
		}
		$keys = array( 'cite_url', 'cite_name', 'cite_summary' );
		$keys = array_flip( $keys );
		$diff = array_filter( array_intersect_key( $_POST, $keys ) );
		if ( ! empty( $diff ) ) {
			return false;
		}
		return $maybe_empty;
	}

	/**
	 * Execute metaboxes for the current screen, after the post title.
	 *
	 * @access public
	 * @param WP_Post $post Post object for the current screen.
	 */
	public static function after_title_metabox( $post ) {

			do_meta_boxes( get_current_screen(), 'kind_after_title', $post );
	}

	/**
	 * Metabox setup.
	 *
	 * @access public
	 */
	public static function kindbox_setup() {
		$cls = get_called_class();
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( $cls, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $cls, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue our needed assets.
	 *
	 * @access public
	 */
	public static function enqueue_admin_scripts() {
		if ( 'post' === get_current_screen()->id ) {
			wp_enqueue_style( 'jquery-ui' );

			wp_enqueue_script(
				'kindmeta',
				plugins_url( 'js/kind.js', __DIR__ ),
				array( 'jquery' ),
				self::$version,
				true
			);

			// Provide a global object to our JS file containing our REST API endpoint, and API nonce
			// Nonce must be 'wp_rest'
			wp_localize_script(
				'kindmeta',
				'PKAPI',
				array(
					'api_nonce'       => wp_create_nonce( 'wp_rest' ),
					'api_url'         => rest_url( '/parse-this/1.0/' ),
					'success_message' => __( 'Your URL has been successfully retrieved and parsed', 'indieweb-post-kinds' ),
					'clear_message'   => __( 'Are you sure you want to clear post properties?', 'indieweb-post-kinds' ),
				)
			);

			wp_enqueue_script( 'moment' );
		}
	}

	/**
	 * Utility function to concatenate a list of post kinds.
	 *
	 * @access public
	 *
	 * @param array $array Selected post kinds.
	 * @return array|mixed|string
	 */
	public static function implode( $array ) {
		$array = kind_flatten_array( $array );
		if ( is_array( $array ) ) {
			return implode( ';', $array );
		}
		return $array;
	}

	/**
	 * Utility function to separate out a list of post kinds.
	 *
	 * @access public
	 *
	 * @param string $string Selected post kinds.
	 * @return array|mixed
	 */
	public static function explode( $string ) {
		if ( is_string( $string ) ) {
			return kind_flatten_array( explode( ';', $string ) );
		}
		return $string;
	}

	/**
	 * Function to render date/time field inputs.
	 *
	 * @access public
	 *
	 * @param string $prefix Field prefix.
	 * @param string $label  Label text.
	 * @param string $datetime   Date/time value.
	 * @param string $class  Class to use for fields.
	 * @return string
	 */
	public static function kind_the_time( $prefix, $label, $datetime, $class ) {
		$tz_seconds = get_option( 'gmt_offset' ) * 3600;
		$offset     = tz_seconds_to_offset( $tz_seconds );
		$time       = divide_datetime( $datetime );
		if ( ! is_array( $time ) ) {
			$time = array();
		}
		if ( isset( $time['offset'] ) ) {
			$offset = $time['offset'];
		}
		$string  = '<label class="half ' . $class . '" for="' . $prefix . '">' . $label . '<br/>';
		$string .= '<input class="date" type="date" name="' . $prefix . '_date" id="' . $prefix . '_date" value="' . ( $time['date'] ?? '' ) . '"/>';
		$string .= '<input class="time" type="time" name="' . $prefix . '_time" id="' . $prefix . '_time" step="1" value="' . ( $time['time'] ?? '' ) . '"/>';
		$string .= self::select_offset( $prefix, $offset );
		$string .= '</label>';
		return $string;
	}

	/**
	 * Function to render our timezone choices.
	 *
	 * @access public
	 *
	 * @param string $prefix Field prefix.
	 * @param string $select Selected field type.
	 * @return string
	 */
	public static function select_offset( $prefix, $select ) {
		$string  = '<select name="' . $prefix . '_offset" id="' . $prefix . '_offset">';
		$string .= self::timezone_offset_choice( $select );
		$string .= '</select>';
		return $string;
	}

	/**
	 * Function to render options for a chosen timezone select field.
	 *
	 * @access public
	 *
	 * @param string $select Selected option.
	 * @return string
	 */
	public static function timezone_offset_choice( $select ) {
		$tzlist = get_gmt_offsets();
		$string = '';
		foreach ( $tzlist as $key => $value ) {
			$string .= '<option value="' . $value . '"';
			if ( $select === $value ) {
				$string .= ' selected';
			}
			$string .= '>GMT' . $value . '</option>';
		}
		return $string;
	}

	/**
	 * Render the options for the RSVP select field.
	 *
	 * @access public
	 *
	 * @param string $selected Selected RSVP choice
	 * @return string
	 */
	public static function rsvp_choice( $selected ) {
		$rsvps  = array(
			''           => false,
			'yes'        => __( 'Yes', 'indieweb-post-kinds' ),
			'no'         => __( 'No', 'indieweb-post-kinds' ),
			'maybe'      => __( 'Maybe', 'indieweb-post-kinds' ),
			'interested' => __( 'Interested', 'indieweb-post-kinds' ),
			'remote'     => __( 'Remote', 'indieweb-post-kinds' ),
		);
		$string = '';
		foreach ( $rsvps as $key => $value ) {
			$string .= '<option value="' . $key . '"';
			if ( $selected === $key ) {
				$string .= ' selected';
			}
			$string .= '>' . $value . '</option>';
		}
		return $string;
	}

	/**
	 * Render our RSVP select input.
	 *
	 * @access public
	 *
	 * @param string $selected Selected RSVP option.
	 * @return string
	 */
	public static function rsvp_select( $selected ) {
		$string  = '<label for="mf2_rsvp">' . __( 'RSVP', 'indieweb-post-kinds' ) . '</label><br/>';
		$string .= '<select name="mf2_rsvp" id="mf2_rsvp">';
		$string .= self::rsvp_choice( $selected );
		$string .= '</select>';
		return $string;
	}

	/**
	 * Create one or more meta boxes to be displayed on the post editor screen.
	 *
	 * @access public
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'replybox-meta', // Unique ID
			esc_html__( 'Response Properties', 'indieweb-post-kinds' ), // Title
			array( static::class, 'reply_metabox' ), // Callback function
			'post',
			'kind_after_title', // Context
			'default', // Priority
			array(
				'__block_editor_compatible_meta_box' => false,
				'__back_compat_meta_box'             => true,
			)
		);
	}

	/**
	 * Render our reply meta box.
	 *
	 * @access public
	 *
	 * @param WP_Post $object Post object for the current screen.
	 * @param array   $box    Array of meta box arguments.
	 */
	public static function reply_metabox( $object, $box ) {
		load_template( plugin_dir_path( __DIR__ ) . 'templates/reply-metabox.php' );
	}

	/**
	 * Process and save meta box data.
	 *
	 * @access public
	 *
	 * @param int    $post_id Saved post ID.
	 * @param WP_Pos $post    Saved post object.
	 */
	public static function save_post( $post_id, $post ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['replykind_metabox_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['replykind_metabox_nonce'], 'replykind_metabox' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
		}
		$kind_post = new Kind_Post( $post );
		$cite      = array();
		$start     = '';
		$end       = '';

		if ( isset( $_POST['mf2_start_date'] ) || isset( $_POST['mf2_start_time'] ) ) {
			$start = build_iso8601_time( sanitize_text_field( $_POST['mf2_start_date'] ), sanitize_text_field( $_POST['mf2_start_time'] ), sanitize_text_field( $_POST['mf2_start_offset'] ) );
			if ( ! $start ) {
				$kind_post->delete( 'start' );
			}
		} else {
			$kind_post->delete( 'start' );
		}
		if ( isset( $_POST['mf2_end_date'] ) || isset( $_POST['mf2_end_time'] ) ) {
			$end = build_iso8601_time( sanitize_text_field( $_POST['mf2_end_date'] ), sanitize_text_field( $_POST['mf2_end_time'] ), sanitize_text_field( $_POST['mf2_end_offset'] ) );
			if ( ! $end ) {
				$kind_post->delete( 'end' );
			}
		} else {
			$kind_post->delete( 'end' );
		}
		if ( $start !== $end ) {
			$kind_post->set_datetime_property( 'start', $start );
			$kind_post->set_datetime_property( 'end', $end );
		}

		$durations = array(
			'Y' => intval( $_POST['duration_years'] ?? '' ),
			'M' => intval( $_POST['duration_months'] ?? '' ),
			'D' => intval( $_POST['duration_days'] ?? '' ),
			'H' => intval( $_POST['duration_hours'] ?? '' ),
			'I' => intval( $_POST['duration_minutes'] ?? '' ),
			'S' => intval( $_POST['duration_seconds'] ?? '' ),
		);
		$duration  = build_iso8601_duration( $durations );

		if ( empty( $duration ) && isset( $start ) && isset( $end ) ) {
			$duration = calculate_duration( $start, $end );
			if ( $duration instanceof DateInterval ) {
				$duration = date_interval_to_iso8601( $duration );
				error_log( wp_json_encode( $duration ) );
			}
		}
		if ( ! empty( $duration ) ) {
			$kind_post->set_duration( $duration );
		} else {
			$kind_post->delete( 'duration' );
		}

		$kind_post->set( 'rsvp', $_POST['mf2_rsvp'] );

		if ( isset( $_POST['cite_published_date'] ) || isset( $_POST['published_time'] ) ) {
			$cite['published'] = build_iso8601_time( sanitize_text_field( $_POST['cite_published_date'] ), sanitize_text_field( $_POST['cite_published_time'] ), sanitize_text_field( $_POST['cite_published_offset'] ) );
		}
		if ( isset( $_POST['cite_updated_date'] ) || isset( $_POST['cite_updated_time'] ) ) {
			$cite['updated'] = build_iso8601_time( sanitize_text_field( $_POST['cite_updated_date'] ), sanitize_text_field( $_POST['cite_updated_time'] ), sanitize_text_field( $_POST['cite_updated_offset'] ) );
		}
		$cite['summary'] = wp_kses_post( $_POST['cite_summary'] ?? '' );
		$cite['name']    = sanitize_text_field( $_POST['cite_name'] ?? '' );
		$cite['url']     = esc_url( $_POST['cite_url'] ?? '' );
		if ( isset( $_POST['cite_tags'] ) ) {
			$cite['category'] = array_filter( explode( ';', sanitize_text_field( $_POST['cite_tags'] ) ) );
		}
		$cite['publication'] = sanitize_text_field( $_POST['cite_publication'] ?? '' );
		$cite['featured']    = esc_url( $_POST['cite_featured'] ?? '' );

		$author         = array();
		$author['name'] = self::explode( sanitize_text_field( $_POST['cite_author_name'] ?? '' ) );
		$author['url']  = self::explode( $_POST['cite_author_url'] ?? '' );
		if ( is_array( $author['url'] ) ) {
			$author['url'] = array_map( 'esc_url', $author['url'] );
		} else {
			$author['url'] = esc_url( $author['url'] );
		}

		$author['photo'] = self::explode( $_POST['cite_author_photo'] ?? '' );

		if ( is_array( $author['photo'] ) ) {
			$author['photo'] = array_map( 'esc_url', $author['photo'] );
		} else {
			$author['photo'] = esc_url( $author['photo'] );
		}

		$author = array_filter( $author );
		if ( ! empty( $author ) ) {

			$author['type'] = 'card';
			$cite['author'] = jf2_to_mf2( $author );
		}
		$kind = $kind_post->get_kind();
		$type = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		// Make sure there is no overwrite of properties that might not be handled by the plugin
		$fetch = $kind_post->get_cite();
		if ( ! $fetch ) {
			$fetch = array();
		} else {
			$fetch = array_filter( $fetch );
		}
		if ( empty( $_POST['cite_media'] ) ) {
			$cite = array_merge( $fetch, $cite );
			$cite = array_filter( $cite );
		}

		if ( ! empty( $cite ) ) {
			if ( 1 === count( $cite ) && array_key_exists( 'url', $cite ) ) {
				$cite = $cite['url'];
			} else {
				$build = array();
				foreach ( $cite as $key => $value ) {
					$build[ $key ] = is_array( $value ) ? $value : array( $value );
				}
				$cite = array( 'properties' => $build );
				// Temporary code which assumes everything except a checkin is a citation
				if ( 'checkin' === $kind ) {
					$cite['type'] = array( 'h-card' );
				} elseif ( in_array( $kind, array( 'drink', 'eat' ), true ) ) {
					$cite['type'] = array( 'h-food' );
				} else {
					$cite['type'] = array( 'h-cite' );
				}
			}
		}
		$kind_post->set( $type, $cite );
	}

	/**
	 * Function to handle saving our kind data upon post status transition.
	 *
	 * @access public
	 *
	 * @param string  $new  New post status.
	 * @param string  $old  Old post status.
	 * @param WP_Post $post Post object.
	 */
	public static function transition_post_status( $new, $old, $post ) {
		if ( 'publish' === $new && 'publish' !== $old ) {
			self::save_post( $post->ID, $post );
		}
	}
}
