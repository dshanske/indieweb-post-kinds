<?php
/**
 * Post Kind Post MetaBox Class
 *
 * Sets Up Metaboxes in the Posting UI for Kind data.
 */
add_action( 'init' , array( 'Kind_Postmeta', 'init' ) );

class Kind_Postmeta {
	public static function init() {
		// Add meta box to new post/post pages only
		add_action( 'load-post.php', array( 'Kind_Postmeta', 'kindbox_setup' ) );
		add_action( 'load-post-new.php', array( 'Kind_Postmeta', 'kindbox_setup' ) );
		add_action( 'save_post', array( 'Kind_Postmeta', 'save_post' ), 8, 2 );
		add_action( 'transition_post_status', array( 'Kind_Postmeta', 'transition_post_status' ) ,5,3 );
		// Experimental
			// add_filter( 'get_post_metadata', array( 'Kind_Postmeta', 'get_post_metadata' ) ,5,4 );
			// add_filter('wp_insert_post_data', array( 'Kind_Postmeta', 'change_title' ), 12, 2 );
	}

	/* Meta box setup function. */
	public static function kindbox_setup() {
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( 'Kind_Postmeta', 'add_postmeta_boxes' ) );
	}

	/* Create one or more meta boxes to be displayed on the post editor screen. */
	public static function add_postmeta_boxes() {
		add_meta_box(
			'responsebox-meta',      // Unique ID
			esc_html__( 'Citation/In Response To', 'Post kind' ),    // Title
			array( 'Kind_Postmeta', 'metabox' ),   // Callback function
			'post',         // Admin page (or post type)
			'normal',         // Context
			'default'         // Priority
		);
	}

	public static function cite_elements() {
		$cite_elements = array(
						'url' => _x( 'URL', 'Post kind' ),
						'name' => _x( 'Name/Title', 'Post kind' ),
						'publication' => _x( 'Site Name/Publication/Album', 'Post kind' ),
						'duration' => _x( 'Duration/Length', 'Post kind' ),
						);
		return $cite_elements;
	}
	public static function hcard_elements() {
		$hcard_elements = array(
						'name' => _x( 'Author/Artist Name', 'Post kind' ),
						'photo' => _x( 'Author Photo', 'Post kind' ),
					  );
		return $hcard_elements;
	}

	public static function metabox( $object, $box ) {
		wp_nonce_field( 'response_metabox', 'response_metabox_nonce' );
		$meta = new kind_meta( $object->ID );
		$kindmeta = $meta->get_meta();
		$kindmeta['url'] = $meta->get_url();
		$cite_elements = self::cite_elements();
		echo '<p>';
		foreach ( $cite_elements as $key => $value ) {
			echo '<label for="cite_' . $key . '">' . $value . '</label>';
			echo '<br />';
			echo '<input type="text" name="cite_' . $key . '"';
			if ( isset( $kindmeta[ $key ] ) ) {
				echo ' value="'. esc_attr( $kindmeta[ $key ] ) . '"';
			}
			echo ' size="70" />';
			echo '<br />';
		}
		$hcard_elements = self::hcard_elements();
		foreach ( $hcard_elements as $key => $value ) {
			echo '<label for="hcard_' . $key . '">' . $value . '</label>';
			echo '<br />';
			echo '<input type="text" name="hcard_' . $key . '"';
			if ( isset( $kindmeta['card'][ $key ] ) ) {
				echo ' value="'. esc_attr( $kindmeta['card'][ $key ] ) . '"';
			}
			echo ' size="70" />';
			echo '<br />';
		}
		?> 
		<br />
			<label for="cite_content"><?php _e( 'Content or Excerpt', 'Post kind' ); ?></label>
		<br />
			<textarea name="cite_content" id="cite_content" cols="70"><?php if ( ! empty( $kindmeta['content'] ) ) { echo $kindmeta['content']; } ?></textarea>
		</p>
		<?php
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
		if ( ! isset( $_POST['response_metabox_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['response_metabox_nonce'], 'response_metabox' ) ) {
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
		$hcard_elements = self::hcard_elements();
		$cite_elements = self::cite_elements();
		/* OK, its safe for us to save the data now. */
		$card = array();
		foreach ( $hcard_elements as $key => $value ) {
			if ( ! empty( $_POST[ 'hcard_'.$key ] ) ) {
				$card[ $key ] = $_POST[ 'hcard_'.$key ];
				if ( ! filter_var( $card[ $key ], FILTER_VALIDATE_URL ) === false ) {
					$card[ $key ] = esc_url_raw( $card[ $key ] );
				} else {
					$card[ $key ] = esc_attr( $card[ $key ] );
				}
			}
		}
		$cite = array();
		foreach ( $cite_elements as $key => $value ) {
			if ( ! empty( $_POST[ 'cite_'.$key ] ) ) {
				$cite[ $key ] = $_POST[ 'cite_'.$key ];
				if ( is_url( $cite[ $key ] ) ) {
						$cite[ $key ] = esc_url_raw( $cite[ $key ] );
				} else {
					$cite[ $key ] = esc_attr( $cite[ $key ] );
				}
			}
		}
		if ( ! empty( $_POST['cite_content'] ) ) {
			$allowed = wp_kses_allowed_html( 'post' );
			$options = get_option( 'iwt_options' );
			if ( array_key_exists( 'contentelements', $options ) && json_decode( $options['contentelements'] ) != null ) {
				$allowed = json_decode( $options['contentelements'], true );
			}
			$cite['content'] = wp_kses( ( string ) $_POST['cite_content'] , $allowed );
		}
		$card = array_filter( $card );
		if ( isset( $card['photo'] ) ) {
			if ( $options['authorimage'] == 1 ) {
				if ( extract_domain_name( $card['photo'] ) != extract_domain_name( get_site_url() ) ) {
					$card['photo'] = media_sideload_image( $card['photo'], $post_id, $card['name'], 'src' );
				}
			}
		}
		$cite['card'] = $card;
		$meta = new Kind_Meta( $post );
		$meta->build_meta( $cite );
		$meta->save_meta( $post );
	}

	public static function transition_post_status( $new, $old, $post ) {
		if ( $new == 'publish' && $old != 'publish' ) {
			self::save_post( $post->ID, $post );
		}
	}

	public static function get_post_metadata( $meta_value = null, $post_id, $meta_key, $single ) {
		return $meta_value;
	}
}
?>
