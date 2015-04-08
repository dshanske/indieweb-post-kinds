<?php
// Adds Post Meta Box for Kind Taxonomy
// Plan is to optionally automate filling in of this data from secondary plugins

// Possible extra fields - author name, author url, author profile image, 
// publish date


// Add meta box to new post/post pages only 
add_action('load-post.php', 'kindbox_setup');
add_action('load-post-new.php', 'kindbox_setup');

/* Meta box setup function. */
function kindbox_setup() {

  /* Add meta boxes on the 'add_meta_boxes' hook. */
  add_action( 'add_meta_boxes', 'kindbox_add_postmeta_boxes' );
}

/* Create one or more meta boxes to be displayed on the post editor screen. */
function kindbox_add_postmeta_boxes() {

  add_meta_box(
    'responsebox-meta',      // Unique ID
    esc_html__( 'Citation/In Response To', 'Post kind' ),    // Title
    'response_metabox',   // Callback function
    'post',         // Admin page (or post type)
    'normal',         // Context
    'default'         // Priority
  );

}

function response_metabox( $object, $box ) {
	wp_nonce_field( 'response_metabox', 'response_metabox_nonce' ); 
	$kindmeta = get_kind_meta ($object->ID);
  ?>  
  <p>
    <label for="response_url"><?php _e( "URL", 'Post kind' ); ?></label>
    <br />
    <input type="text" name="response_url" id="response_url" value="<?php if (isset ($kindmeta['cite'][0]['url'])) {echo esc_attr( $kindmeta['cite'][0]['url'] ); } ?>" size="70" />
    <br />
    <label for="response_name"><?php _e( "Name", 'Post kind' ); ?></label>
    <br />
    <input type="text" name="response_name" id="response_name" value="<?php if (isset ($kindmeta['cite'][0]['name'])) { echo esc_attr($kindmeta['cite'][0]['name']); } ?>" size="70" />
	<br />
    <label for="response_author"><?php _e( "Author/Artist Name", 'Post kind' ); ?></label>
    <br />
    <input type="text" name="response_author" id="response_author" value="<?php if (isset ($kindmeta['cite'][0]['card'][0]['name'])) { echo esc_attr($kindmeta['cite'][0]['card'][0]['name']); } ?>" size="70" />
        <br />
    <label for="response_photo"><?php _e( "Author Photo", 'Post kind' ); ?></label>
    <br />
    <input type="text" name="response_photo" id="response_photo" value="<?php if (isset ($kindmeta['cite'][0]['card'][0]['photo'])) { echo esc_attr($kindmeta['cite'][0]['card'][0]['photo']); } ?>" size="70" />
        <br />
    <label for="response_publication"><?php _e( "Site Name/Publication/Album", 'Post kind' ); ?></label>
    <br />
    <input type="text" name="response_publication" id="response_publication" value="<?php if (isset ($kindmeta['cite'][0]['publication'])) { echo esc_attr($kindmeta['cite'][0]['publication']); } ?>" size="70" />
        <br />

    <label for="response_content"><?php _e( "Content or Excerpt", 'Post kind' ); ?></label>
    <br />
    <textarea name="response_content" id="response_content" cols="70"><?php if (isset ($kindmeta['cite'][0]['content'])) { echo esc_attr( $kindmeta['cite'][0]['content'] ); } ?></textarea>
  
  </p>

<?php }

/* Save the meta box's post metadata. */
function responsebox_save_post_meta( $post_id, $post ) {
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
	} 
  else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, its safe for us to save the data now. */
	if( isset( $_POST[ 'response_url' ] ) && !empty( $_POST[ 'response_url' ] ) ) {
    $cite[0]['url'] = esc_url_raw( $_POST[ 'response_url' ] );
	}
	if( isset( $_POST[ 'response_name' ] ) && !empty( $_POST[ 'response_name' ] ) ) {
    $cite[0]['name'] = esc_attr( $_POST[ 'response_name' ] ) ;
  }
  if( isset( $_POST[ 'response_author' ] ) && !empty( $_POST[ 'response_author' ] ) ) {
    $cite[0]['card'][0]['name'] = esc_attr( $_POST[ 'response_author' ] ) ;
  }
  if( isset( $_POST[ 'response_photo' ] ) && !empty( $_POST[ 'response_photo' ] ) ) {
    $cite[0]['card'][0]['photo'] = esc_url_raw( $_POST[ 'response_photo' ] ) ;
  }
  if( isset( $_POST[ 'response_publication' ] ) && !empty( $_POST[ 'response_publication' ] ) ) {
    $cite[0]['publication'] = esc_attr( $_POST[ 'response_publication' ] ) ;
  }
	if( isset( $_POST[ 'response_content' ] ) && !empty( $_POST[ 'response_content' ] ) ) {
    $allowed = wp_kses_allowed_html( 'post' );
    $options = get_option( 'iwt_options' );
    if(array_key_exists('contentelements',$options) && json_decode($options['contentelements']) != NULL){
      $allowed = json_decode($options['contentelements'],true);
    }
    $cite[0]['content'] =  wp_kses((string) $_POST[ 'response_content' ] ,$allowed); 
  }
  if(!empty($cite)) {
    update_post_meta( $post_id,'mf2_cite', $cite);
  }  
}

add_action( 'save_post', 'responsebox_save_post_meta', 8, 2 );

function responsebox_transition_post_meta($new, $old, $post) {
	if ($new == 'publish' && $old != 'publish') {
		responsebox_save_post_meta($post->ID,$post);
	}
}

add_action('transition_post_status','responsebox_transition_post_meta',5,3);

function get_kind_response($post_id) {
  return get_post_meta($post_id, 'response', true);
}	

?>
