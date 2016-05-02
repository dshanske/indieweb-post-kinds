<?php
/**
 * Post Kind Post Tabbed MetaBox Class
 *
 * Sets Up Tabbed Metabox in the Posting UI for Kind data.
 */
add_action( 'init' , array( 'Kind_Tabmeta', 'init' ) );

class Kind_Tabmeta {
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
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'kindmeta-time',
				plugins_url( 'indieweb-post-kinds/includes/tabs/time.js' ),
				array( 'jquery' ),
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'kindmeta-tabs',
				plugins_url( 'indieweb-post-kinds/includes/tabs/tabs.js' ),
				array( 'jquery' ),
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'kindmeta-response',
				plugins_url( 'indieweb-post-kinds/includes/tabs/retrieve.js' ),
				array( 'jquery' ),
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'moment',
				'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js',
				array( 'jquery' ),
				'2.10.6'
			);
		}
	}


	/* Create one or more meta boxes to be displayed on the post editor screen. */
	public static function add_meta_boxes() {
		add_meta_box(
			'tabbox-meta',      // Unique ID
			esc_html__( 'Post Properties', 'Post kind' ),    // Title
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
		$time = $meta->get_time();
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
		if ( isset( $_POST['time'] ) ) {
			if ( isset( $_POST['time']['start_date'] ) || isset( $_POST['time']['start_time'] ) ) {
				$start = $meta->build_time( $_POST['time']['start_date'], $_POST['time']['start_time'], $_POST['time']['start_offset'] );
			}
			if ( isset( $_POST['time']['end_date'] ) || isset( $_POST['time']['end_time'] ) ) {
				$end = $meta->build_time( $_POST['time']['end_date'], $_POST['time']['end_time'], $_POST['time']['end_offset'] );
			}
		}
		if ( isset( $_POST['cite'] ) ) {
			if ( in_array( $kind, array( 'like', 'reply', 'repost', 'favorite', 'bookmark' ) ) ) {
				if ( ! empty( $start ) ) {
					$_POST['cite']['published'] = $start;
				}
				if ( ! empty( $end )  ) {
					$_POST['cite']['updated'] = $end;
				}
			} else {
				$meta->set_time( $start, $end );
			}
			$meta->set_cite( $_POST['cite'] );
		}
		if ( isset( $_POST['author'] ) ) {
			$meta->set_author( $_POST['author'] );
		}
		if ( isset( $_POST['url'] ) ) {
			$meta->set_url( $_POST['url'] );
		}
		// This is temporary - planning on improving this later
		if ( isset( $_POST['duration'] ) ) {
			$meta->set( 'duration', $_POST['duration'] );
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
