<?php
/**
 * Post Kind Post MetaBox Class
 *
 * Sets Up Metaboxes in the Posting UI for Kind data.
 *
 */
add_action( 'init' , array( 'Kind_Postmeta', 'init' ) );

class Kind_Postmeta {
	public static function init() {
		// Add meta box to new post/post pages only 
		add_action( 'load-post.php', array( 'Kind_Postmeta' , 'kindbox_setup' ) );
		add_action( 'load-post-new.php', array( 'Kind_Postmeta', 'kindbox_setup' ) );
		add_action( 'save_post', array( 'Kind_Postmeta', 'save_post' ), 8, 2 );
		add_action('transition_post_status', array( 'Kind_Postmeta', 'transition_post_status' ) ,5,3);
//	  add_filter('wp_insert_post_data', array( 'Kind_Postmeta', 'change_title' ), 12, 2 );
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
			array('Kind_Postmeta', 'metabox'),   // Callback function
			'post',         // Admin page (or post type)
			'normal',         // Context
			'default'         // Priority
		);
	}

	public static function cite_elements() {
		$cite_elements = array(
                        'url' => _x( "URL", 'Post kind' ),
                        'name' => _x( "Name", 'Post kind' ),
                        'publication' => _x( "Site Name/Publication/Album", 'Post kind' ),
                        'duration' => _x( "Duration", 'Post kind' )
                        );
		return $cite_elements;
	}	

	public static function hcard_elements() {
		$hcard_elements = array(
                        'name' => _x( "Author/Artist Name", 'Post kind' ),
                        'photo' => _x( "Author Photo", 'Post kind' ),
                      );
		return $hcard_elements;
	}

	public static function metabox( $object, $box ) {
		wp_nonce_field( 'response_metabox', 'response_metabox_nonce' ); 
		$meta = new kind_meta ( $object->ID );
		$kindmeta = $meta->get_meta();
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
      if ( isset( $kindmeta[ 'card' ][ $key ] ) ) {
        echo ' value="'. esc_attr( $kindmeta[ 'card' ][ $key ] ) . '"';
      }
      echo ' size="70" />';
      echo '<br />';
    } 
		?> 
		<br />
			<label for="cite_content"><?php _e( "Content or Excerpt", 'Post kind' ); ?></label>
		<br />
			<textarea name="cite_content" id="cite_content" cols="70"><?php if ( ! empty( $kindmeta[ 'content' ] ) ) { echo $kindmeta[ 'content' ]; } ?></textarea>
		</p>
		<?php
	}

	public static function change_title( $data, $postarr )
	{    
    // If it is our form has not been submitted, so we dont want to do anything
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( !empty( $data[ 'post_title' ] ) ) {
			return $data;
		}
		$kind_strings = Kind_Taxonomy::get_strings();
    $kind = get_term_by( taxonomy_id, $_POST[ 'tax_input' ][ 'kind' ], 'kind' );	
		$title = $kind_strings[ $kind->slug ];
		if ( ! empty( $_POST[ 'cite_name' ] ) ) {
				$title .= ' - ' . $_POST[ 'cite_name' ];
		}
		$data[ 'post_title' ] = $title;
		$data[ 'post_name' ] = sanitize_title( $data[ 'post_title' ] );
    return $data;
	}

	/* Save the meta box's post metadata. */
	public static function save_post( $post_id, $post ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST[ 'response_metabox_nonce' ] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST[ 'response_metabox_nonce' ], 'response_metabox' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST[ 'post_type' ] ) && 'page' == $_POST[ 'post_type' ] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} 
  	else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}
		$kind = get_post_kind_slug( $post );
		$hcard_elements = self::hcard_elements();
		$cite_elements = self::cite_elements();
		/* OK, its safe for us to save the data now. */
		$card = array();
    foreach ( $hcard_elements as $key => $value ) {
			if ( ! empty( $_POST[ 'hcard_'.$key ] ) ) {
				$card[ $key ] = $_POST[ 'hcard_'.$key ];
				if ( ! filter_var( $card[ $key ], FILTER_VALIDATE_URL ) === false ) {
						$card[ $key ] = esc_url_raw( $card[ $key ] );
				}
				else {
						$card[ $key ] = esc_attr( $card[ $key ] );
				}
			}
		}
		$cite = array();
		foreach ( $cite_elements as $key => $value ) {
			if ( ! empty( $_POST[ 'cite_'.$key ] ) ) {
      		$cite[ $key ] = $_POST[ 'cite_'.$key ];
	        if ( ! filter_var( $cite[ $key ], FILTER_VALIDATE_URL ) === false ) {  
						$cite[ $key ] = esc_url_raw( $cite[ $key ] );
					}
					else {
						$cite[ $key ] = esc_attr( $cite[ $key ] );
					}
			}
    } 
		if ( ! empty( $_POST[ 'cite_content' ] ) ) {
			$allowed = wp_kses_allowed_html( 'post' );
      $options = get_option( 'iwt_options' );
			if( array_key_exists( 'contentelements', $options ) && json_decode( $options[ 'contentelements' ] ) != NULL ) {
				$allowed = json_decode( $options[ 'contentelements' ], true );
			}
      $cite[ 'content' ] =  wp_kses( ( string ) $_POST[ 'cite_content' ] , $allowed );
		}
		$card = array_filter( $card );
		if ( isset( $card[ 'photo' ] ) ) {
				if( $options[ 'authorimage' ] == 1 ) {
			 		if( extract_domain_name( $card[ 'photo' ] ) != extract_domain_name( get_site_url() ) ) {
			 			$card[ 'photo' ] = media_sideload_image( $card[ 'photo' ], $post_id, $card[ 'name' ], 'src' );
					}
			 }
		}
		$cite[ 'card' ] = $card;
		$cite = array_filter( $cite );
		if ( isset( $cite[ 'url' ]) ) {
			$data = get_post_meta( $post_id, '_parse', true );
			// Prevent Lookup More than Once
			if ( $data!='true' ) {
				$data = self::parse( $cite['url'] );
      	update_post_meta( $post_id,'_parse',  'true' );
				if ( ! isset( $cite[ 'name' ] ) ) {
						$cite[ 'name' ] = $data[ 'name' ];
				}
      	if ( ! isset( $cite[ 'publication' ] ) ) {
        	  $cite[ 'publication' ] = $data[ 'publication' ];
      	}
      	if ( ! isset( $cite[ 'content' ] ) ) {
      	    $cite[ 'content' ] = $data[ 'content' ];
      	}
				if ( isset( $data[ 'image' ]) ) {
						$images = get_children( array(
																			'post_type' => 'attachment',
																			'post_mime_type' => 'image',
																			'post_parent' => 'post_id'
																	) );
						if ( empty( $images ) ) {
							$media = media_sideload_image( $data[ 'image' ], $post_id );
						}
				}
			}
		}
		$cite = array_filter( $cite );
		if( ! empty( $cite ) ) {
			update_post_meta( $post_id, 'mf2_cite', $cite );
		}  
	}

	public static function transition_post_status( $new, $old, $post ) {
		if ( $new == 'publish' && $old != 'publish' ) {
			self::save_post( $post->ID, $post );
		}
	}

// Extract Relevant Data from a Web Page
  public static function parse($url) {
    if ( ! isset( $url ) ) {
      return false;
    }
    elseif ( filter_var( $url, FILTER_VALIDATE_URL ) === false )  { 
      return false; 
    }
    $response = wp_remote_get( $url );
    if ( is_wp_error( $response ) ) {
			return false;
    }
    $body = wp_remote_retrieve_body( $response );
    $meta = \ogp\Parser::parse( $body );
    $domain = parse_url( $url, PHP_URL_HOST );
		$data = array();
    $data[ 'name' ] = $meta[ 'og:title' ] ?: $meta[ 'twitter:title' ];
    $data[ 'content' ] = $meta[ 'og:description' ] ?: $meta[ 'twitter:description' ];
    $data[ 'site' ] = $meta[ 'og:site' ] ?: $meta[ 'twitter:site' ];
    $data[ 'image' ] = $meta[ 'og:image' ] ?: $meta[ 'twitter:image' ];
    $data[ 'publication' ] = $meta[ 'og:site_name' ];
    $metatags = $meta[ 'article:tag' ] ?: $meta[ 'og:video:tag' ];
    if( is_array( $metatags ) ) {
      foreach ( $metatags as $tag ) {
        $tags[] = str_replace( ',', ' -', $tag );
      }
      $tags = array_filter( $tags );
    }
    $data[ 'tags' ] = $data[ 'tags' ] ?: implode( "," ,$tags );
    return array_filter( $data );
  }
}

?>
