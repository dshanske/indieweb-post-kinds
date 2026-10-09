<?php
/**
 * Kind_Metabox class.
 *
 * @package Post_Kinds
 * @since   2.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the tabbed Post Kinds metabox to the Classic Editor and saves its fields.
 *
 * @since 2.2.0
 */
class Kind_Metabox {

	/**
	 * Plugin version, used to version enqueued assets.
	 *
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
	 * Filters whether a post should be considered empty.
	 *
	 * Many kinds (likes, bookmarks, reposts, and so on) legitimately have no
	 * content, title or excerpt, which core would otherwise reject as empty.
	 *
	 * @access public
	 *
	 * @param bool  $maybe_empty Whether or not the post should be considered empty.
	 * @param array $postarr     Data for the post to be inserted or updated.
	 *
	 * @return bool
	 */
	public static function wp_insert_post_empty_content( $maybe_empty, $postarr ) {
		// Always let updates to trash posts through.
		if ( isset( $postarr['post_status'] ) && 'trash' === $postarr['post_status'] ) {
			return false;
		}
		// Let all Micropub creates through.
		if ( isset( $postarr['meta_input']['micropub_auth_response'] ) ) {
			return false;
		}
		$kind = self::kind_from_postarr( $postarr );
		// Use traditional rules for articles, and for posts without a kind.
		if ( 'article' === $kind || ! $kind ) {
			return $maybe_empty;
		}
		// An existing post of a kind other than article may have no content,
		// for example when Micropub updates a like.
		if ( ! empty( $postarr['ID'] ) ) {
			return false;
		}
		// A new post from the metabox needs something to respond to.
		foreach ( array( 'cite_url', 'cite_name', 'cite_summary' ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only checks whether a field was filled in; save_post() verifies the nonce before saving.
			if ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) && '' !== sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) {
				return false;
			}
		}
		return $maybe_empty;
	}

	/**
	 * Returns the kind slug for post data being inserted or updated.
	 *
	 * The kind comes from tax_input, which the kind metabox submits as a slug and
	 * other callers may pass as term IDs, or failing that from the existing post.
	 *
	 * @access public
	 *
	 * @param array $postarr Data for the post to be inserted or updated.
	 * @return string Kind slug, or an empty string if none.
	 */
	public static function kind_from_postarr( $postarr ) {
		if ( isset( $postarr['tax_input']['kind'] ) ) {
			$kind = $postarr['tax_input']['kind'];
			if ( is_array( $kind ) ) {
				$kind = reset( $kind );
			}
			$term = is_numeric( $kind ) ? get_term_by( 'id', (int) $kind, 'kind' ) : get_term_by( 'slug', (string) $kind, 'kind' );
			return ( $term instanceof WP_Term ) ? $term->slug : '';
		}
		if ( ! empty( $postarr['ID'] ) ) {
			$kind = get_post_kind_slug( $postarr['ID'] );
			return $kind ? $kind : '';
		}
		return '';
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
					'error_message'   => __( 'Error: Unable to Retrieve', 'indieweb-post-kinds' ),
					'invalid_url'     => __( 'Invalid URL', 'indieweb-post-kinds' ),
					'media_title'     => __( 'Attach', 'indieweb-post-kinds' ),
					'media_button'    => __( 'Use this media', 'indieweb-post-kinds' ),
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
		$time       = Kind_Time::divide_datetime( $datetime );
		if ( ! is_array( $time ) ) {
			$time = array();
		}
		if ( isset( $time['offset'] ) ) {
			$offset = $time['offset'];
		}
		$prefix  = esc_attr( $prefix );
		$string  = '<label class="half ' . esc_attr( $class ) . '" for="' . $prefix . '">' . esc_html( $label ) . '<br/>';
		$string .= '<input class="date" type="date" name="' . $prefix . '_date" id="' . $prefix . '_date" value="' . esc_attr( $time['date'] ?? '' ) . '"/>';
		$string .= '<input class="time" type="time" name="' . $prefix . '_time" id="' . $prefix . '_time" step="1" value="' . esc_attr( $time['time'] ?? '' ) . '"/>';
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
		$string  = '<select name="' . esc_attr( $prefix ) . '_offset" id="' . esc_attr( $prefix ) . '_offset">';
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
		foreach ( $tzlist as $value ) {
			$string .= '<option value="' . esc_attr( $value ) . '"';
			if ( $select === $value ) {
				$string .= ' selected';
			}
			$string .= '>GMT' . esc_html( $value ) . '</option>';
		}
		return $string;
	}

	/**
	 * Render the options for the rating select field.
	 *
	 * @access public
	 *
	 * @param string $selected Selected Rating choice
	 * @return string
	 */
	public static function rating_choice( $selected ) {
		$selected = is_numeric( $selected ) ? intval( $selected ) : 0;
		$ratings  = array(
			0 => false,
			1 => __( 'Very Dissatisfied', 'indieweb-post-kinds' ),
			2 => __( 'Dissatisfied', 'indieweb-post-kinds' ),
			3 => __( 'Neutral', 'indieweb-post-kinds' ),
			4 => __( 'Satisfied', 'indieweb-post-kinds' ),
			5 => __( 'Very Satisfied', 'indieweb-post-kinds' ),
		);
		$ret      = '';
		foreach ( $ratings as $key => $value ) {
			$ret .= '<option value="' . esc_attr( $key ) . '"';
			if ( $selected === $key ) {
				$ret .= ' selected';
			}
			$ret .= '>' . esc_html( (string) $value ) . '</option>';
		}
		return $ret;
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
			$string .= '<option value="' . esc_attr( $key ) . '"';
			if ( $selected === $key ) {
				$string .= ' selected';
			}
			$string .= '>' . esc_html( (string) $value ) . '</option>';
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
		$string  = '<label for="mf2_rsvp">' . esc_html__( 'RSVP', 'indieweb-post-kinds' ) . '</label><br/>';
		$string .= '<select name="mf2_rsvp" id="mf2_rsvp">';
		$string .= self::rsvp_choice( $selected );
		$string .= '</select>';
		return $string;
	}

	/**
	 * Render our rating select input.
	 *
	 * @access public
	 *
	 * @param string $selected Selected Rating option.
	 * @return string
	 */
	public static function rating_select( $selected ) {
		$string  = '<label for="mf2_rating">' . esc_html__( 'Rating', 'indieweb-post-kinds' ) . '</label><br/>';
		$string .= '<select name="mf2_rating" id="mf2_rating">';
		$string .= self::rating_choice( $selected );
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
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['replykind_metabox_nonce'] ) ), 'replykind_metabox' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( 'page' === self::posted_text( 'post_type' ) ) {
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
			$start = build_iso8601_time( self::posted_text( 'mf2_start_date' ), self::posted_text( 'mf2_start_time' ), self::posted_text( 'mf2_start_offset' ) );
			if ( ! $start ) {
				$kind_post->delete( 'start' );
			}
		} else {
			$kind_post->delete( 'start' );
		}
		if ( isset( $_POST['mf2_end_date'] ) || isset( $_POST['mf2_end_time'] ) ) {
			$end = build_iso8601_time( self::posted_text( 'mf2_end_date' ), self::posted_text( 'mf2_end_time' ), self::posted_text( 'mf2_end_offset' ) );
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
			'Y' => absint( self::posted_text( 'duration_years' ) ),
			'M' => absint( self::posted_text( 'duration_months' ) ),
			'D' => absint( self::posted_text( 'duration_days' ) ),
			'H' => absint( self::posted_text( 'duration_hours' ) ),
			'I' => absint( self::posted_text( 'duration_minutes' ) ),
			'S' => absint( self::posted_text( 'duration_seconds' ) ),
		);
		$duration  = build_iso8601_duration( $durations );

		if ( empty( $duration ) && isset( $start ) && isset( $end ) ) {
			$duration = calculate_duration( $start, $end );
			if ( $duration instanceof DateInterval ) {
				$duration = date_interval_to_iso8601( $duration );
			}
		}
		if ( ! empty( $duration ) ) {
			$kind_post->set_duration( $duration );
		} else {
			$kind_post->delete( 'duration' );
		}

		if ( isset( $_POST['mf2_rsvp'] ) ) {
			$rsvp = self::posted_text( 'mf2_rsvp' );
			if ( in_array( $rsvp, array( 'yes', 'no', 'maybe', 'interested', 'remote' ), true ) ) {
				$kind_post->set( 'rsvp', $rsvp );
			} else {
				$kind_post->delete( 'rsvp' );
			}
		}
		$rating = self::posted_text( 'mf2_rating' );
		if ( is_numeric( $rating ) ) {
			$kind_post->set( 'rating', intval( $rating ) );
		}

		if ( isset( $_POST['cite_published_date'] ) || isset( $_POST['cite_published_time'] ) ) {
			$cite['published'] = build_iso8601_time( self::posted_text( 'cite_published_date' ), self::posted_text( 'cite_published_time' ), self::posted_text( 'cite_published_offset' ) );
		}
		if ( isset( $_POST['cite_updated_date'] ) || isset( $_POST['cite_updated_time'] ) ) {
			$cite['updated'] = build_iso8601_time( self::posted_text( 'cite_updated_date' ), self::posted_text( 'cite_updated_time' ), self::posted_text( 'cite_updated_offset' ) );
		}
		// Summaries are plain text; line breaks are kept.
		$cite['summary'] = isset( $_POST['cite_summary'] ) && is_string( $_POST['cite_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cite_summary'] ) ) : '';
		$cite['name']    = self::posted_text( 'cite_name' );
		$cite['url']     = self::posted_urls( 'cite_url' );
		if ( isset( $_POST['cite_tags'] ) ) {
			$cite['category'] = array_values( array_filter( array_map( 'trim', explode( ';', self::posted_text( 'cite_tags' ) ) ) ) );
		}
		$cite['publication'] = self::posted_text( 'cite_publication' );
		$cite['featured']    = self::posted_urls( 'cite_featured' );

		$author          = array();
		$author['name']  = self::explode( self::posted_text( 'cite_author_name' ) );
		$author['url']   = self::posted_urls( 'cite_author_url', true );
		$author['photo'] = self::posted_urls( 'cite_author_photo', true );

		$author = array_filter( $author );
		if ( ! empty( $author ) ) {

			$author['type'] = 'card';
			$cite['author'] = \ParseThis\jf2_to_mf2( $author );
		}
		$kind = $kind_post->get_kind();
		$type = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		// Make sure there is no overwrite of properties that might not be handled by the plugin
		$fetch = self::stored_cite_properties( $kind_post->get_cite() );
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
		// Values are unslashed for sanitizing; the metadata API expects them slashed.
		$kind_post->set( $type, wp_slash( $cite ) );
	}

	/**
	 * Returns a posted metabox field as sanitized text.
	 *
	 * Only called from save_post(), after the nonce is verified.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key Field name.
	 * @return string The unslashed, sanitized value, or an empty string if it is missing.
	 */
	private static function posted_text( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in save_post().
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in save_post().
		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	}

	/**
	 * Returns the properties of a stored citation, keyed by property name.
	 *
	 * Saving merges these with the posted fields, so properties the metabox
	 * does not edit are kept. A citation stored as a plain URL has none.
	 * Before 4.0.0, saving merged the whole stored microformat instead, nesting
	 * it inside its own properties one level deeper on each save; those levels
	 * are flattened here, newer values winning.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $cite Citation as returned by Kind_Post::get_cite().
	 * @return array Property values, each a list.
	 */
	private static function stored_cite_properties( $cite ) {
		if ( wp_is_numeric_array( $cite ) && 1 === count( $cite ) ) {
			$cite = $cite[0];
		}
		if ( ! is_array( $cite ) || empty( $cite['properties'] ) || ! is_array( $cite['properties'] ) ) {
			return array();
		}
		return array_filter( Kind_Upgrade::flatten_properties( $cite['properties'] ) );
	}

	/**
	 * Returns a posted metabox URL field, sanitized.
	 *
	 * Only called from save_post(), after the nonce is verified.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key      Field name.
	 * @param bool   $multiple Optional. Whether the field holds several URLs separated by semicolons. Default false.
	 * @return string|string[] The URL, or for multiple values a URL or list of URLs, as Kind_Metabox::explode() returns. Empty if none is valid.
	 */
	private static function posted_urls( $key, $multiple = false ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in save_post().
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verified in save_post(); each URL is sanitized with sanitize_url() below (sanitize_text_field() would alter %-encoding).
		$value = trim( wp_unslash( $_POST[ $key ] ) );
		if ( ! $multiple ) {
			return sanitize_url( $value );
		}
		$urls = array_filter( array_map( 'sanitize_url', array_map( 'trim', explode( ';', $value ) ) ) );
		return kind_flatten_array( array_values( $urls ) );
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
