<?php
/**
 * Post Kind Post MetaBox Class
 *
 * Sets Up Tabbed Metabox in the Posting UI for Kind data.
 */

class Kind_Metabox {
	public static $version;
	public static function init() {
		add_action( 'edit_form_after_title', array( 'Kind_Metabox', 'after_title_metabox' ) );
		// Add meta box to new post/post pages only
		add_action( 'load-post.php', array( 'Kind_Metabox', 'kindbox_setup' ) );
		add_action( 'load-post-new.php', array( 'Kind_Metabox', 'kindbox_setup' ) );
		add_action( 'save_post', array( 'Kind_Metabox', 'save_post' ), 8, 2 );
		add_action( 'transition_post_status', array( 'Kind_Metabox', 'transition_post_status' ), 5, 3 );
		add_filter( 'wp_insert_post_empty_content', array( 'Kind_Metabox', 'wp_insert_post_empty_content' ), 11, 2 );
		add_action( 'change_kind', array( 'Kind_Metabox', 'change_kind' ), 10, 3 );
	}

	public static function change_kind( $post_id, $old_kind, $new_kind ) {
		$mf2_post = new MF2_Post( $post_id );
		$old_prop = Kind_Taxonomy::get_kind_info( $old_kind, 'property' );
		$new_prop = Kind_Taxonomy::get_kind_info( $new_kind, 'property' );
		if ( $mf2_post->has_key( $old_prop ) && ! $mf2_post->has_key( $new_prop ) ) {
			$mf2_post->set( $new_prop, $mf2_post->get( $old_prop ) );
		}
		$mf2_post->delete( $old_prop );
	}

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

	public static function after_title_metabox( $post ) {

			do_meta_boxes( get_current_screen(), 'kind_after_title', $post );
	}

	/* Meta box setup function. */
	public static function kindbox_setup() {
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( 'Kind_Metabox', 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( 'Kind_Metabox', 'enqueue_admin_scripts' ) );

	}

	public static function enqueue_admin_scripts() {
		if ( 'post' === get_current_screen()->id ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui' );

			wp_enqueue_script(
				'jquery-ui-timepicker',
				plugins_url( 'node_modules/timepicker/jquery.timepicker.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::$version,
				true
			);

			wp_enqueue_script(
				'jquery-datepair',
				plugins_url( 'node_modules/datepair.js/dist/jquery.datepair.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::$version,
				true
			);

			wp_enqueue_script(
				'kindmeta',
				plugins_url( 'js/kind.js', dirname( __FILE__ ) ),
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

			wp_enqueue_script(
				'moment',
				plugins_url( 'node_modules/moment/min/moment.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'2.20.1',
				true
			);
		}
	}

	public static function implode( $array ) {
			$array = kind_flatten_array( $array );
		if ( ! is_array( $array ) ) {
				return $array;
		}
			return implode( ';', $array );
	}

	public static function explode( $string ) {
		if ( ! is_string( $string ) ) {
				return $string;
		}
			return kind_flatten_array( explode( ';', $string ) );
	}

	public static function kind_the_time( $prefix, $label, $time, $class ) {
		$tz_seconds = get_option( 'gmt_offset' ) * 3600;
		$offset     = tz_seconds_to_offset( $tz_seconds );
		if ( isset( $time['offset'] ) ) {
			$offset = $time['offset'];
		}
		$string  = '<label class="half ' . $class . '" for="' . $prefix . '">' . $label . '<br/>';
		$string .= '<input class="date" type="date" name="' . $prefix . '_date" id="' . $prefix . '_date" value="' . ifset( $time['date'] ) . '"/>';
		$string .= '<input class="time" type="time" name="' . $prefix . '_time" id="' . $prefix . '_time" step="1" value="' . ifset( $time['time'] ) . '"/>';
		$string .= self::select_offset( $prefix, $offset );
		$string .= '</label>';
		return $string;
	}

	public static function select_offset( $prefix, $select ) {
		$string  = '<select name="' . $prefix . '_offset" id="' . $prefix . '_offset">';
		$string .= self::timezone_offset_choice( $select );
		$string .= '</select>';
		return $string;
	}

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

	public static function rsvp_choice( $selected ) {
		$rsvps  = array(
			''           => false,
			'yes'        => __( 'Yes', 'indieweb-post-kinds' ),
			'no'         => __( 'No', 'indieweb-post-kinds' ),
			'maybe'      => __( 'Maybe', 'indieweb-post-kinds' ),
			'interested' => __( 'Interested', 'indieweb-post-kinds' ),
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

	public static function rsvp_select( $selected ) {
		$string  = '<label for="mf2_rsvp">' . __( 'RSVP', 'indieweb-post-kinds' ) . '</label><br/>';
		$string .= '<select name="mf2_rsvp" id="mf2_rsvp">';
		$string .= self::rsvp_choice( $selected );
		$string .= '</select>';
		return $string;
	}

	/* Create one or more meta boxes to be displayed on the post editor screen. */
	public static function add_meta_boxes() {
		add_meta_box(
			'replybox-meta', // Unique ID
			esc_html__( 'Response Properties', 'indieweb-post-kinds' ), // Title
			array( 'Kind_Metabox', 'reply_metabox' ), // Callback function
			'post',
			'kind_after_title', // Context
			'default' // Priority
		);
	}

	public static function reply_metabox( $object, $box ) {
		load_template( plugin_dir_path( __DIR__ ) . 'templates/reply-metabox.php' );
	}

	/* Save the meta box's post metadata. */
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
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}
		$mf2_post = new MF2_Post( $post );
		$cite     = array();
		$start    = '';
		$end      = '';

		if ( isset( $_POST['mf2_start_date'] ) || isset( $_POST['mf2_start_time'] ) ) {
			$start = build_iso8601_time( $_POST['mf2_start_date'], $_POST['mf2_start_time'], $_POST['mf2_start_offset'] );
			if ( ! $start ) {
				$mf2_post->delete( 'dt-start' );
			}
		} else {
			$mf2_post->delete( 'dt-start' );
		}
		if ( isset( $_POST['mf2_end_date'] ) || isset( $_POST['mf2_end_time'] ) ) {
			$end = build_iso8601_time( $_POST['mf2_end_date'], $_POST['mf2_end_time'], $_POST['mf2_end_offset'] );
			if ( ! $end ) {
				$mf2_post->delete( 'dt-end' );
			}
		} else {
			$mf2_post->delete( 'dt-end' );
		}
		if ( $start !== $end ) {
			$mf2_post->set( 'dt-start', $start );
			$mf2_post->set( 'dt-end', $end );
		}
		$duration_keys = array(
			'duration_years'   => '',
			'duration_months'  => '',
			'duration_days'    => '',
			'duration_hours'   => '',
			'duration_minutes' => '',
			'duration_seconds' => '',
		);
		if ( array_intersect_key( $duration_keys, $_POST ) ) {
			$durations = array(
				'Y' => ifset( $_POST['duration_years'] ),
				'M' => ifset( $_POST['duration_months'] ),
				'D' => ifset( $_POST['duration_days'] ),
				'H' => ifset( $_POST['duration_hours'] ),
				'I' => ifset( $_POST['duration_minutes'] ),
				'S' => ifset( $_POST['duration_seconds'] ),
			);
			$durations = array_filter( $durations );
			$duration  = build_iso8601_duration( $durations );

		} else {
			$duration = calculate_duration( $start, $end );
		}
		if ( $duration ) {
			$mf2_post->set( 'duration', $duration );
		} else {
			$mf2_post->delete( 'duration' );
		}

		$mf2_post->set( 'rsvp', $_POST['mf2_rsvp'] );

		if ( isset( $_POST['cite_published_date'] ) || isset( $_POST['published_time'] ) ) {
			$cite['published'] = build_iso8601_time( $_POST['cite_published_date'], $_POST['cite_published_time'], $_POST['cite_published_offset'] );
		}
		if ( isset( $_POST['cite_updated_date'] ) || isset( $_POST['cite_updated_time'] ) ) {
			$cite['updated'] = build_iso8601_time( $_POST['cite_updated_date'], $_POST['cite_updated_time'], $_POST['cite_updated_offset'] );
		}
		$cite['summary'] = ifset( $_POST['cite_summary'] );
		$cite['name']    = ifset( $_POST['cite_name'] );
		$cite['url']     = ifset( $_POST['cite_url'] );
		if ( isset( $_POST['cite_tags'] ) ) {
			$cite['category'] = array_filter( explode( ';', $_POST['cite_tags'] ) );
		}
		$cite['publication'] = ifset( $_POST['cite_publication'] );
		$cite['featured']    = ifset( $_POST['cite_featured'] );

		$author          = array();
		$author['type']  = 'card';
		$author['name']  = self::explode( ifset( $_POST['cite_author_name'] ) );
		$author['url']   = self::explode( ifset( $_POST['cite_author_url'] ) );
		$author['photo'] = self::explode( ifset( $_POST['cite_author_photo'] ) );
		$author          = array_filter( $author );
		$cite['author']  = jf2_to_mf2( $author );
		$kind            = $mf2_post->get( 'kind', true );
		$type            = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		// Make sure there is no overwrite of properties that might not be handled by the plugin
		$fetch = $mf2_post->fetch( $type );
		if ( ! $fetch ) {
			$fetch = array();
		}
		if ( ! empty( $_POST['cite_media'] ) ) {
			$mf2_post->set( $type, array( $cite['url'] ) );
			return;
		}
		$cite = array_merge( $fetch, $cite );
		$cite = array_filter( $cite );
		// Temporary code which assumes everything except a checkin is a citation
		if ( 'checkin' === $kind ) {
			$cite['type'] = 'card';
		} else {
			$cite['type'] = 'cite';
		}
		$cite = jf2_to_mf2( $cite );
		$mf2_post->set( $type, $cite );
	}

	public static function transition_post_status( $new, $old, $post ) {
		if ( 'publish' === $new && 'publish' !== $old ) {
			self::save_post( $post->ID, $post );
		}
	}

}

