<?php
/**
 * Post Kind Post Tabbed MetaBox Class
 *
 * Sets Up Tabbed Metabox in the Posting UI for Kind data.
 */

class Kind_Tabmeta {
	public static $version;
	public static function init() {
		// Add meta box to new post/post pages only
		add_action( 'load-post.php', array( 'Kind_Tabmeta', 'kindbox_setup' ) );
		add_action( 'load-post-new.php', array( 'Kind_Tabmeta', 'kindbox_setup' ) );
		add_action( 'save_post', array( 'Kind_Tabmeta', 'save_post' ), 8, 2 );
		add_action( 'transition_post_status', array( 'Kind_Tabmeta', 'transition_post_status' ) ,5,3 );
	}

	/* Meta box setup function. */
	public static function kindbox_setup() {
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( 'Kind_Tabmeta', 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( 'Kind_Tabmeta', 'enqueue_admin_scripts' ) );

	}

	public static function enqueue_admin_scripts() {
		if ( 'post' === get_current_screen()->id ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui' );

			wp_enqueue_script(
				'jquery-ui-timepicker',
				plugins_url( 'indieweb-post-kinds/includes/tabs/jquery.timepicker.min.js' ),
				array( 'jquery' ),
				self::$version
			);

			wp_enqueue_script(
				'kindmeta-time',
				plugins_url( 'indieweb-post-kinds/includes/tabs/time.js' ),
				array( 'jquery' ),
				self::$version
			);

			wp_enqueue_script(
				'kindmeta-tabs',
				plugins_url( 'indieweb-post-kinds/includes/tabs/tabs.js' ),
				array( 'jquery' ),
				self::$version
			);

			wp_enqueue_script(
				'kindmeta-response',
				plugins_url( 'indieweb-post-kinds/includes/tabs/retrieve.js' ),
				array( 'jquery' ),
				self::$version
			);

			// Provide a global object to our JS file containing our REST API endpoint, and API nonce
			// Nonce must be 'wp_rest'
			wp_localize_script( 'kindmeta-response', 'rest_object',
				array(
					'api_nonce' => wp_create_nonce( 'wp_rest' ),
					'api_url'   => rest_url( '/link-preview/1.0/' ),
					'link_preview_success_message' => __( 'Your URL has been successfully retrieved and parsed', 'indieweb-post-kinds' ),
				)
			);

			wp_enqueue_script(
				'moment',
				'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js',
				array( 'jquery' ),
				'2.10.6'
			);
		}
	}

	public static function kind_get_timezones() {
		$o = array();
		$t_zones = timezone_identifiers_list();

		foreach ( $t_zones as $a ) {
			$t = '';
			try {
					  // this throws exception for 'US/Pacific-New'
					$zone = new DateTimeZone( $a );
				$seconds = $zone->getOffset( new DateTime( 'now' , $zone ) );
					$o[] = self::tz_seconds_to_offset( $seconds );
			} // exceptions must be catched, else a blank page
			catch (Exception $e) {
				// die("Exception : " . $e->getMessage() . '<br />');
				// what to do in catch ? , nothing just relax
			}
		}
			$o = array_unique( $o );
			asort( $o );
		return $o;
	}

	public static function kind_the_time( $prefix, $label, $time ) {
		$tz_seconds = get_option( 'gmt_offset' ) * 3600;
		$offset = self::tz_seconds_to_offset( $tz_seconds );
		if ( isset( $time['offset'] ) ) {
			$offset = $time['offset'];
		}
		$string = '<label for="' . $prefix .  '">' . $label . '</label><br/>';
		$string .= '<input type="date" name="' . $prefix . '_date" id="' . $prefix . '_date" value="' . ifset( $time['date'] ) . '"/>';
		$string .= '<input type="time" name="' . $prefix . '_time" id="' . $prefix . '_time" step="1" value="' . ifset( $time['time'] ) . '"/>';
		$string .= self::select_offset( $prefix, $offset );
		return $string;
	}

	public static function select_offset( $prefix, $select ) {
		$tzlist = self::kind_get_timezones();
		$string = '<select name="'  . $prefix . '_offset" id="' . $prefix . '_offset">';
		foreach ( $tzlist as $key => $value ) {
			$string .= '<option value="' . $value . '"';
			if ( $select == $value ) {
				$string .= ' selected';
			}
			$string .= '>GMT' . $value . '</option>';
		}
		$string .= '</select>';
		return $string;
	}

	public static function rsvp_select( $selected ) {
		$rsvps = array(
			'' => false,
			'yes' => __( 'Yes', 'indieweb-post-kinds' ),
			'no' => __( 'No', 'indieweb-post-kinds' ),
			'maybe' => __( 'Maybe', 'indieweb-post-kinds' ),
			'interested' => __( 'Interested', 'indieweb-post-kinds' ),
		);
		$string = '<label for="mf2_rsvp">' . __( 'RSVP', 'indieweb-post-kinds' ) .  '</label><br/>';
		$string .= '<select name="mf2_rsvp" id="mf2_rsvp">';
		foreach ( $rsvps as $key => $value ) {
			$string .= '<option value="' . $key . '"';
			if ( $selected == $key ) {
				$string .= ' selected';
			}
			$string .= '>' . $value . '</option>';
		}
		$string .= '</select>';
		return $string;
	}

	// Echos a Standard Formbox Box - Defaulting to Text
	public static function metabox_text( $property, $label, $default = '', $type = 'text' ) {
		$string = '<label for="' . $property .  '">' . $label . '</label><br/>';
		if ( 'textarea' === $type ) {
			$string .= '<textarea name="' . $property . '" id="' . $property . '" data-role="none" class="widefat">' . $default . '</textarea>';
			return $string;
		}
		$string .= '<input type="' . $type . '" name="' . $property . '" id="' . $property . '" class="widefat" value="' . $default . '" />';
		return $string;

	}



	public static function tz_seconds_to_offset($seconds) {
		return ($seconds < 0 ? '-' : '+') . sprintf( '%02d:%02d', abs( $seconds / 60 / 60 ), abs( $seconds / 60 ) % 60 );
	}

	public static function tz_offset_to_seconds($offset) {
		if ( preg_match( '/([+-])(\d{2}):?(\d{2})/', $offset, $match ) ) {
			$sign = ($match[1] == '-' ? -1 : 1);
			return (($match[2] * 60 * 60) + ($match[3] * 60)) * $sign;
		} else {
			return 0;
		}
	}

	/* Create one or more meta boxes to be displayed on the post editor screen. */
	public static function add_meta_boxes() {
		add_meta_box(
			'tabbox-meta',      // Unique ID
			esc_html__( 'Post Properties', 'indieweb-post-kinds' ),    // Title
			array( 'Kind_Tabmeta', 'display_metabox' ),   // Callback function
			'post',         // Admin page (or post type)
			'normal',         // Context
			'default'         // Priority
		);
	}

	public static function display_metabox( $object, $box ) {
		wp_nonce_field( 'tabkind_metabox', 'tabkind_metabox_nonce' );
		$meta = new kind_meta( $object->ID );
		$cite = $meta->get_cite();
		$author = $meta->get_author();
		$url = $meta->get_url();
		if ( ! $url ) {
			if ( array_key_exists( 'kindurl', $_GET ) ) {
				$url = $_GET['kindurl'];
			}
		}
		$time = array(); // $meta->get_time();
		include_once( 'tabs/tab-navigation.php' );
	}

	public static function change_title( $data, $postarr ) {

		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! empty( $data['post_title'] ) ) {
			return $data;
		}
		$kind_strings = Kind_Taxonomy::get_strings();
		$kind = get_term_by( taxonomy_id, $_POST['tax_input']['kind'], 'kind' );
		$title = $kind_strings[ $kind->slug ];
		if ( ! empty( $_POST['cite_name'] ) ) {
				$title .= ' - ' . $_POST['cite_name'];
		}
		$data['post_title'] = $title;
		$data['post_name'] = sanitize_title( $data['post_title'] );
		return $data;
	}

	/* Save the meta box's post metadata. */
	public static function save_post( $post_id, $post ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['tabkind_metabox_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['tabkind_metabox_nonce'], 'tabkind_metabox' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}
		$kind = get_post_kind_slug( $post );
		$meta = new Kind_Meta( $post );
		$cite = array();
		$start = '';
		$end = '';

		if ( isset( $_POST['mf2_start_date'] ) || isset( $_POST['mf2_start_time'] ) ) {
			$start = $meta->build_time( $_POST['mf2_start_date'], $_POST['mf2_start_time'], $_POST['mf2_start_offset'] );
		}
		if ( isset( $_POST['mf2_end_date'] ) || isset( $_POST['mf2_end_time'] ) ) {
			$end = $meta->build_time( $_POST['mf2_end_date'], $_POST['mf2_end_time'], $_POST['mf2_end_offset'] );
		}
		if ( $start !== $end ) {
			$meta->set( 'dt-start', $start );
			$meta->set( 'dt-end', $end );
		}
		$duration = $meta->calculate_duration( $start, $end );
		if ( $duration && ! isset( $_POST['cite_duration'] ) ) {
			$meta->set( 'duration', $duration );
		} else {
			$meta->del( 'duration' );
		}
		$meta->set( 'rsvp' , $_POST['mf2_rsvp' ] );

		if ( isset( $_POST['cite_published_date'] ) || isset( $_POST['published_time'] ) ) {
			$cite['published'] = $meta->build_time( $_POST['cite_published_date'], $_POST['cite_published_time'], $_POST['cite_published_offset'] );
		}
		if ( isset( $_POST['cite_updated_date'] ) || isset( $_POST['cite_updated_time'] ) ) {
			$cite['updated'] = $meta->build_time( $_POST['cite_updated_date'], $_POST['cite_updated_time'], $_POST['cite_updated_offset'] );
		}
		$cite['summary'] = ifset( $_POST['cite_summary'] );
		$cite['name'] = ifset( $_POST['cite_name'] );
		if ( isset( $_POST['cite_tags'] ) ) {
			$cite['category'] = explode( ';', $_POST['cite_tags'] );
		}
		$cite['publication'] = ifset( $_POST['cite_publication'] );
		$cite['featured'] = ifset( $_POST['cite_featured'] );

		$meta->set_cite( array_filter( $cite ) );
		$author = array();
		$author['name'] = ifset( $_POST['cite_author_name'] );
		$author['url'] = ifset( $_POST['cite_author_url'] );
		$author['photo'] = ifset( $_POST['cite_author_photo'] );
		if ( ! empty( $author ) ) {
			$meta->set_author( $author );
		}
		if ( isset( $_POST['cite_url'] ) ) {
			$meta->set_url( $_POST['cite_url'] );
		}
		$meta->save_meta( $post );
	}

	public static function transition_post_status( $new, $old, $post ) {
		if ( $new == 'publish' && $old != 'publish' ) {
			self::save_post( $post->ID, $post );
		}
	}

}
?>
