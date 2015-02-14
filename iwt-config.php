<?php

function add_iwt_options_to_menu(){
	add_options_page( '', __('Post Kinds', 'Post kind'), 'manage_options', 'iwt_options', 'iwt_options_form');
}

add_action('admin_menu', 'add_iwt_options_to_menu');

add_action( 'admin_init', 'iwt_options_init' );
function iwt_options_init() {
    register_setting( 'iwt_options', 'iwt_options' );
    add_settings_section( 'iwt-content', __('Content Options', 'Post kind')  , 'iwt_options_callback', 'iwt_options' );
    add_settings_field( 'the_content', __('Add the Response Box (Required if No Theme Support)', 'Post kind'), 'iwt_callback', 'iwt_options', 'iwt-content' ,  array( 'name' => 'the_content') );
    add_settings_field( 'embeds', __('Add Rich Embed Support for Facebook, Google Plus, Instagram, etc', 'Post kind'), 'iwt_callback', 'iwt_options', 'iwt-content' ,  array( 'name' => 'embeds') );
    add_settings_field( 'cacher', __('Do Not Store Cached Responses', 'Post kind'), 'iwt_callback', 'iwt_options', 'iwt-content' ,  array( 'name' => 'cacher') );
    add_settings_field( 'disableformats', __('Disable Post Formats', 'Post kind'), 'iwt_callback', 'iwt_options', 'iwt-content' ,  array( 'name' => 'disableformats') );
//   add_settings_field( 'upgrade', __('Migrate to new data structure on update', 'Post kind'), 'iwt_callback', 'iwt_options', 'iwt-content' ,  array( 'name' => 'upgrade') );

}

function iwt_options_callback()
   {
	_e ('Options for Displaying the Response', 'Post kind');
   }

function iwt_callback(array $args)
   {
        $options = get_option('iwt_options');
        $name = $args['name'];
        $checked = $options[$name];
        echo "<input name='iwt_options[$name]' type='hidden' value='0' />";
        echo "<input name='iwt_options[$name]' type='checkbox' value='1' " . checked( 1, $checked, false ) . " /> ";
   }

function iwt_options_form() 
  {
	kind_defaultterms ();
	echo '<div class="wrap">';
	echo '<h2>' . __('Indieweb Post Kinds', 'Post kinds') . '</h2>';
	echo '<p>'; 
	_e( 'Adds support for responding and interacting with other sites', 'Post kinds');
	echo '</p><hr />';
	// iwt_upgrade(); 
?>
        <form method="post" action="options.php">
        <?php settings_fields( 'iwt_options' ); ?>

         <?php do_settings_sections( 'iwt_options' ); ?>
         <?php submit_button(); ?>
       </form>
     </div>
    <?php
 }

function iwt_upgrade()
  {
     $options = get_option('iwt_options');
     if ($options['upgrade'] == 1)
	{
	    $args = array(
		  'post_type' => 'post', 
		  'posts_per_page' => '-1',
		);
	    $the_query = new WP_Query( $args );
	     // The Loop
	    if ( $the_query->have_posts() ) {
		foreach( $the_query->posts as $post ) {
		$the_query->the_post();
		if (get_the_terms($post->ID, 'kind'))
		   {
		$response_title = get_post_meta( $post->ID, 'response_title', true);
                $response_url = get_post_meta( $post->ID, 'response_url', true);
                $response_quote = get_post_meta( $post->ID, 'response_quote', true);
		if (!empty($response_title)) {
			$meta['title'] = $response_title;
		   }
                if (!empty($response_url)) {
                        $meta['url'] = $response_url;
                   }
                if (!empty($response_quote)) {
                        $meta['content'] = $response_quote;
                   }
		if (!empty($response)) {
		        update_post_meta($post->ID, 'response', $meta); 
		   }
		// delete_post_meta($post->ID, 'response_url');
                // delete_post_meta($post->ID, 'response_title');
                // delete_post_meta($post->ID, 'response_quote');
		unset($response_title);
		unset($response_quote);
		unset($response_url);
	           }
		}
	      }
	    wp_reset_postdata();
	}

  }

function response_purge()
  {
	    $args = array(
		  'post_type' => 'post', 
		  'posts_per_page' => '-1',
		);
	    $the_query = new WP_Query( $args );
	     // The Loop
	    if ( $the_query->have_posts() ) {
		foreach( $the_query->posts as $post ) {
		$the_query->the_post();
		delete_post_meta($post->ID, '_resp_full');
	           }
		}
	    wp_reset_postdata();

  }

add_action( 'after_setup_theme', 'remove_post_formats', 11 ); 

function remove_post_formats() {
	$options = get_option('iwt_option');
	if($options['disableformats']==1) { remove_theme_support( 'post-formats' ); }
  }

?>
