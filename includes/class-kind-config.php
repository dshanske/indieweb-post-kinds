<?php

add_action( 'admin_init' , array('kind_config', 'admin_init') );
add_action( 'after_setup_theme', array('kind_config', 'remove_post_formats'), 11 );
add_action('admin_menu', array('kind_config', 'admin_menu') );


// The kind_config class sets up the Settings Page for the plugin
class kind_config {

	public static function admin_init() {
		$options = get_option('iwt_options');
		register_setting( 'iwt_options', 'iwt_options' );
		add_settings_section( 'iwt-content', __('Content Options', 'Post kind')  , array('kind_config', 'options_callback'), 'iwt_options' );
		add_settings_field( 'embeds', __('Add Rich Embed Support for Facebook, Google Plus, Instagram, etc', 'Post kind'), array('kind_config', 'checkbox_callback'), 'iwt_options', 'iwt-content' ,  array( 'name' => 'embeds') );
		add_settings_field( 'cacher', __('Store Cached Responses', 'Post kind'), array('kind_config', 'checkbox_callback'), 'iwt_options', 'iwt-content' ,  array( 'name' => 'cacher') );
		add_settings_field( 'disableformats', __('Disable Post Formats', 'Post kind'), array('kind_config', 'checkbox_callback'), 'iwt_options', 'iwt-content' ,  array( 'name' => 'disableformats') );
		add_settings_field( 'protection', __('Disable Content Protection on Responses', 'Post kind') , array('kind_config', 'checkbox_callback') , 'iwt_options', 'iwt-content' ,  array( 'name' => 'protection') );
		if ($options) {
			if(array_key_exists('protection', $options) && $options['protection']==1 ) {
				add_settings_field( 'contentelements', __('Response Content Allowed Html Elements', 'Post kind') . ' <a href="http://codex.wordpress.org/Function_Reference/wp_kses">*</a>', array('kind_config', 'textbox_callback'), 'iwt_options', 'iwt-content' ,  array( 'name' => 'contentelements') );
    	}
  	}
		add_settings_field( 'linksharing', __('Enable Link Sharing Kinds', 'Post kind'), array('kind_config', 'checkbox_callback'), 'iwt_options', 'iwt-content' ,  array( 'name' => 'linksharing') );
		add_settings_field( 'mediacheckin', __('Enable Media Check-Ins', 'Post kind'), array('kind_config', 'checkbox_callback'), 'iwt_options', 'iwt-content' ,  array( 'name' => 'mediacheckin') );
	}

	public static function admin_menu() {
		add_options_page( '', __('Post Kinds', 'Post kind'), 'manage_options', 'kind_options', array('kind_config', 'options_form') );
	}

	public static function settings_link($links) {
		$settings_link = '<a href="options-general.php?page=kind_options">Settings</a>';
		array_unshift($links, $settings_link);
 		return $links;
	}

	public static function options_callback() {
		_e ('', 'Post kind');
	}

	public static function checkbox_callback(array $args) {
		$options = get_option('iwt_options');
		$name = $args['name'];
		$checked = $options[$name];
		echo "<input name='iwt_options[$name]' type='hidden' value='0' />";
		echo "<input name='iwt_options[$name]' type='checkbox' value='1' " . checked( 1, $checked, false ) . " /> ";
}

	public static function textbox_callback(array $args) {
		$options = get_option('iwt_options');
		$name = $args['name'];
		$val = '';
		if( $name=="contentelements" && !array_key_exists('contentelements',$options) ) {
			$val = str_replace("},\"","},\r\n\"",json_encode(wp_kses_allowed_html( 'post' ), JSON_PRETTY_PRINT));
		} 
		else{
			$val = $options[$name];
		}
		echo "<textarea rows='10' cols='50' class='large-text code' name='iwt_options[$name]'>".print_r($val,true)."</textarea> ";
	}

	public static function options_form() {
		kind_defaultterms ();
		echo '<div class="wrap">';
		echo '<h2>' . __('Indieweb Post Kinds', 'Post kinds') . '</h2>';
		echo '<p>'; 
		esc_html_e( 'Adds support for responding and interacting with other sites', 'Post kinds');
		echo '</p><hr />';
		echo '<form method="post" action="options.php">';
			settings_fields( 'iwt_options' );
    	do_settings_sections( 'iwt_options' );
    	submit_button();
    echo '</form></div>';
	}

function response_purge() {
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

	public static function remove_post_formats() {
		$options = get_option('iwt_option');
		if($options['disableformats']==1) { remove_theme_support( 'post-formats' ); }
	}

} // End Class

?>
