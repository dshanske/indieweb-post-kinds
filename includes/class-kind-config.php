<?php
/**
 * Post Kind Configuration Class
 *
 * @package Post Kinds
 * Sets Up Configuration Options for the Plugin.
 */

add_action( 'admin_init' , array( 'Kind_Config', 'admin_init' ) );
add_action( 'after_setup_theme', array( 'Kind_Config', 'remove_post_formats' ), 11 );
add_action( 'admin_menu', array( 'Kind_Config', 'admin_menu' ), 11 );
// Add post help tab
add_action( 'load-post.php', array( 'Kind_Config', 'add_post_help_tab') , 20 );

/**
 * Static Class to Configure Admin Options.
 * @package Post Kinds
 */
class Kind_Config {


	public static function Defaults() {
		return array( 
			'embeds' => '0',
			'cacher' => '0',
			'authorimages' => '0',
			'disableformats' => '0',
			'protection' => '0',
			'contentelements' => '',
			'termslist' => array( 'article', 'reply', 'bookmark' )
		);

	}

	/**
	 * Function to Set up Admin Settings.
	 * @access public
	 */
	public static function admin_init() {
		$options = get_option( 'iwt_options', self::Defaults() );
		register_setting( 'iwt_options', 'iwt_options' );
		add_settings_section( 'iwt-content', __( 'Content Options', 'Post kind' ), array( 'Kind_Config', 'options_callback' ), 'iwt_options' );
		add_settings_field( 'embeds', __( 'Add Rich Embed Support for Facebook, Google Plus, Instagram, etc', 'Post kind' ), array( 'Kind_Config', 'checkbox_callback' ), 'iwt_options', 'iwt-content' ,  array( 'name' => 'embeds' ) );
		// add_settings_field( 'cacher', __( 'Store Cached Responses', 'Post kind' ), array( 'Kind_Config', 'checkbox_callback' ), 'iwt_options', 'iwt-content' ,  array( 'name' => 'cacher' ) );
		// add_settings_field( 'authorimages', __( 'Sideload Author Images', 'Post kind' ), array( 'Kind_Config', 'checkbox_callback' ), 'iwt_options', 'iwt-content' ,  array( 'name' => 'authorimage' ) );
		add_settings_field( 'disableformats', __( 'Disable Post Formats', 'Post kind' ), array( 'Kind_Config', 'checkbox_callback' ), 'iwt_options', 'iwt-content' ,  array( 'name' => 'disableformats' ) );
		add_settings_field( 'protection', __( 'Disable Content Protection on Responses', 'Post kind' ) , array( 'Kind_Config', 'checkbox_callback' ) , 'iwt_options', 'iwt-content' ,  array( 'name' => 'protection' ) );
		if ( $options ) {
			if ( array_key_exists( 'protection', $options ) && 1 === $options['protection'] ) {
				add_settings_field( 'contentelements', __( 'Response Content Allowed Html Elements', 'Post kind' ) . ' <a href="http://codex.wordpress.org/Function_Reference/wp_kses">*</a>', array( 'Kind_Config', 'textbox_callback' ), 'iwt_options', 'iwt-content' ,  array( 'name' => 'contentelements' ) );
			}
		}
		add_settings_field( 'termslist', __( 'Select All Kinds You Wish to Use', 'Post kind' ), array( 'Kind_Config', 'termlist_callback' ), 'iwt_options', 'iwt-content' );
	}

	/**
	 * Adds Options Page for Plugin Options.
	 * @access public
	 */
	public static function admin_menu() {
		// If the IndieWeb Plugin is installed use its menu.
		if ( class_exists('IndieWeb_Plugin') ) {
 	    add_submenu_page(
  	    'indieweb',
    	  __( 'Post Kinds', 'Post kind' ), // page title
  	    __( 'Post Kinds', 'Post kind' ), // menu title
   	   'manage_options', // access capability
  	    'kind_options',
      	array('Kind_Config', 'options_form')
    );
		}
		else{ 
			add_options_page( '', __( 'Post Kinds', 'Post kind' ), 'manage_options', 'kind_options', array( 'Kind_Config', 'options_form' ) );		
		}
	}

	/**
	 * Adds link to Plugin Page for Options Page.
	 * @access public
	 * @param array $links Array of Existing Links.
	 * @return array Modified Links.
	 */
	public static function settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=kind_options">Settings</a>';
		array_unshift( $links, $settings_link );
			return $links;
	}

	/**
	 * Callback for Options on Options Page.
	 * @access public
	 */
	public static function options_callback() {
		esc_html_e( '', 'Post kind' );
	}

	/**
	 * Generate a Checkbox.
	 *
	 * @access public
	 * @param array $args {
	 *		Arguments.
	 *
	 *		@type string $name Checkbox Name.
	 */
	public static function checkbox_callback( array $args ) {
		$options = get_option( 'iwt_options', self::Defaults() );
		$name = $args['name'];
		$checked = ifset($options[ $name ]);
		echo "<input name='iwt_options[" . esc_html( $name ) . "]' type='hidden' value='0' />";
		echo "<input name='iwt_options[" . esc_html( $name ) . "]' type='checkbox' value='1' " . checked( 1, $checked, false ) . ' /> ';
	}

	/**
	 * Generate a Textbox.
	 *
	 * @access public
	 * @param array $args {
	 *    Arguments.
	 *
	 *    @type string $name Textbox Name.
	 */
	public static function textbox_callback( array $args ) {
		$options = get_option( 'iwt_options', self::Defaults() );
		$name = $args['name'];
		$val = '';
		if ( 'contentelements' === $name && ! array_key_exists( 'contentelements', $options ) ) {
			$val = str_replace( '},"',"},\r\n\"", wp_json_encode( wp_kses_allowed_html( 'post' ), JSON_PRETTY_PRINT ) );
		} else {
			$val = $options[ $name ];
		}
		echo "<textarea rows='10' cols='50' class='large-text code' name='iwt_options[" . esc_html( $name ) . "]'>". esc_textarea( print_r( $val,true ) ) . '</textarea> ';
	}

	/**
	 * Generate a Term List.
	 *
	 * @access public
	 */
	public static function termlist_callback() {
		$options = get_option( 'iwt_options', self::Defaults() );
		$terms = Kind_Taxonomy::get_strings();
		// Hide these terms until ready for use for now.
		$hide = array( 'note', 'weather', 'exercise', 'travel', 'rsvp', 'tag', 'follow', 'drink', 'eat', 'quote' );
		// Hide checkin option unless Simple Location is active.
		if ( ! class_exists( 'loc_config' ) ) {
			$hide[] = 'checkin';
		}
		foreach ( $hide as $hid ) {
			unset( $terms[ $hid ] );
		}
		if ( ! array_key_exists( 'termslist', $options ) ) {
			$termslist = array();
		} else {
			$termslist = $options['termslist'];
		}
		echo '<select id="termslist" name="iwt_options[termslist][]" multiple>';
		foreach ( $terms as $key => $value ) {
			echo '<option value="' . $key . '" '. selected( in_array( $key, $termslist ) ) . '>' . $value . '</option>';
		}
		echo '</select>';
	}


	/**
	 * Generate Options Form.
	 * @access public
	 */
	public static function options_form() {
		Kind_Taxonomy::kind_defaultterms();
		echo '<div class="wrap">';
		echo '<h2>' . esc_html__( 'Indieweb Post Kinds', 'Post kinds' ) . '</h2>';
		echo '<p>';
		esc_html_e( 'Adds support for responding and interacting with other sites.', 'Post kinds' );
		echo '</p><hr />';
		echo '<form method="post" action="options.php">';
			settings_fields( 'iwt_options' );
		do_settings_sections( 'iwt_options' );
		submit_button();
		echo '</form></div>';
	}

	/**
	 * Purge the response code stored in metadata.
	 * @access public
	 */
	public static function response_purge() {
		$args = array(
	  	'post_type' => 'post',
			'nopaging' => 'true',
		);
		$the_query = new WP_Query( $args );
		// The Loop.
		if ( $the_query->have_posts() ) {
			foreach ( $the_query->posts as $post ) {
				$the_query->the_post();
				delete_post_meta( $post->ID, '_resp_full' );
			}
		}
		wp_reset_postdata();
	}

	/**
	 * Disable Post Formats.
	 * @access public
	 */
	public static function remove_post_formats() {
		$options = get_option( 'iwt_option', self::Defaults() );
		if ( 1 === ifset($options['disableformats']) ) { remove_theme_support( 'post-formats' ); }
	}

	public static function add_post_help_tab() {
		get_current_screen()->add_help_tab( array(
			 'id'       => 'post_kind_help'
			,'title'    => __( 'Post Properties', 'Post kind' )
			,'content'  => __('
						<p> The Post Properties tab represents the Microformats properties of a Post. For different kinds of posts, the different
						fields mean something different. Example: Artist Name vs Author Name</p>
						<ul><strong>Response</strong>
							<li><strong>URL</strong> - The URL the post is responding to</li>
							<li><strong>Name/Title</strong> - The name of what is being responded to</li>
						</ul>
						<ul><strong>Citation</strong>
							<li><strong>Summary</strong> - Summary of what the post is responding to</li>
							<li><strong>Site Name/Publication/Album</strong></li>
							<li><strong>Featured Image</strong> - The URL of a featured image of what is being responded to </li>
							<li><strong>Author/Artist Name</strong> </li>
							<li><strong>Author/Artist URL</strong></li>
							<li><strong>Author Photo URL</strong></li>
						</ul>
            <ul><strong>Time</strong>
              <li><strong>Start/Published Time</strong></li>
              <li><strong>End/Updated Time</strong></li>
            </ul>


			', 'Post kind')

		) );
	}

} // End Class

?>
