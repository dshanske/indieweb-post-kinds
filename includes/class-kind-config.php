<?php
/**
 * Post Kind Configuration Class
 *
 * @package Post Kinds
 * Sets Up Configuration Options for the Plugin.
 */

/**
 * Static Class to Configure Admin Options.
 *
 * @package Post Kinds
 */
class Kind_Config {

	/**
	 * Function to Initialize the Configuration.
	 *
	 * @access public
	 */
	public static function init() {
		add_action( 'admin_init' , array( 'Kind_Config', 'admin_init' ) );
		add_action( 'after_setup_theme', array( 'Kind_Config', 'remove_post_formats' ), 11 );
		add_action( 'admin_menu', array( 'Kind_Config', 'admin_menu' ), 11 );
		// Add post help tab
		add_action( 'load-post.php', array( 'Kind_Config', 'add_post_help_tab' ) , 20 );

		$args = array(
			'type' => 'array',
			'description' => 'Kinds Enabled on This Site',
			'show_in_rest' => true,
			'default' => array( 'article', 'reply', 'bookmark' ),
		);
		register_setting( 'iwt_options', 'kind_termslist', $args );
		$args = array(
			'type' => 'string',
			'description' => 'Default Kind',
			'show_in_rest' => true,
			'default' => 'note',
		);
		register_setting( 'iwt_options', 'kind_default', $args );
		$args = array(
			'type' => 'boolean',
			'description' => 'Rich Embed Support for Whitelisted Sites',
			'show_in_rest' => true,
			'default' => 1,
		);
		register_setting( 'iwt_options', 'kind_embeds', $args );
		$args = array(
			'type' => 'string',
			'description' => 'KSES Content Protection on Responses',
			'show_in_rest' => false,
			'default' => str_replace( '},"',"},\r\n\"", wp_json_encode( wp_kses_allowed_html( 'post' ), JSON_PRETTY_PRINT ) ),
		);
		register_setting( 'iwt_options', 'kind_kses', $args );
	}

	/**
	 * Function to Set up Settings.
	 *
	 * @access public
	 */
	public static function admin_init() {
		add_settings_section(
			'iwt-content',
			__( 'Content Options',
			'indieweb-post-kinds' ),
			array( 'Kind_Config', 'options_callback' ),
			'iwt_options'
		);
		add_settings_field(
			'termslist',
			__( 'Select All Kinds You Wish to Use', 'indieweb-post-kinds' ),
			array( 'Kind_Config', 'termcheck_callback' ),
			'iwt_options',
			'iwt-content'
		);
		add_settings_field(
			'defaultkind',
			__( 'Default Kind for New Posts', 'indieweb-post-kinds' ),
			array( 'Kind_Config', 'defaultkind_callback' ),
			'iwt_options',
			'iwt-content'
		);

		add_settings_field(
			'embeds',
			__( 'Embed Sites into your Response', 'indieweb-post-kinds' ),
			array( 'Kind_Config', 'checkbox_callback' ),
			'iwt_options', 'iwt-content' ,  array( 'name' => 'kind_embeds' )
		);

		if ( POST_KINDS_KSES ) {
			add_settings_field(
				'contentelements',
				__( 'Response Content Allowed Html Elements', 'indieweb-post-kinds' ) . ' <a href="http://codex.wordpress.org/Function_Reference/wp_kses">*</a>',
				array( 'Kind_Config', 'textbox_callback' ),
				'iwt_options',
				'iwt-content',
				array( 'name' => 'kind_kses' )
			);
		}
		// Add Query Var to Admin
		add_filter( 'query_vars', array( 'Kind_Config', 'query_var' ) );
	}


	public static function query_var($vars) {
		$vars[] = 'kindurl';
		return $vars;
	}

	/**
	 * Adds Options Page for Plugin Options.
	 *
	 * @access public
	 */
	public static function admin_menu() {
		// If the IndieWeb Plugin is installed use its menu.
		if ( class_exists( 'IndieWeb_Plugin' ) ) {
			add_submenu_page(
				'indieweb',
				__( 'Post Kinds', 'indieweb-post-kinds' ), // page title
				__( 'Post Kinds', 'indieweb-post-kinds' ), // menu title
				'manage_options', // access capability
				'kind_options',
				array( 'Kind_Config', 'options_form' )
			);
		} else {
			add_options_page( '', __( 'Post Kinds', 'indieweb-post-kinds' ), 'manage_options', 'kind_options', array( 'Kind_Config', 'options_form' ) );
		}
	}

	/**
	 * Callback for Options on Options Page.
	 *
	 * @access public
	 */
	public static function options_callback() {
		esc_html_e( '', 'indieweb-post-kinds' );
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
		$option = get_option( $args['name'] );
		$checked = ifset( $option );
		echo "<input name='" . $args['name'] . "' type='hidden' value='0' />";
		echo "<input name='" . $args['name'] . "' type='checkbox' value='1' " . checked( 1, $checked, false ) . ' /> ';
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
		$option = get_option( $args['name'] );
		if ( is_array( $option ) ) {
			$option = print_r( $option, true );
		}
		echo "<textarea rows='10' cols='50' class='large-text code' name='" . $args['name'] . "'>". $option . '</textarea> ';
	}

	/**
	 * Generate a Term List.
	 *
	 * @access public
	 */
	public static function termcheck_callback() {
		$terms = Kind_Taxonomy::get_kind_info( 'all', 'all' );
		// Hide these terms until ready for use for now.
		$hide = array( 'note', 'weather', 'exercise', 'travel', 'itinerary', 'event', 'tag', 'follow', 'drink', 'eat', 'trip', 'recipe', 'mood', 'issue' );
		foreach ( $hide as $hid ) {
			unset( $terms[ $hid ] );
		}
		$termslist = get_option( 'kind_termslist' );
		foreach ( $terms as $key => $value ) {
			echo "<input name='kind_termslist[]' type='checkbox' value='" . $key . "' " . checked( in_array( $key, $termslist ), true, false ) . ' /><strong>' . $value['singular_name'] . '</strong> - ' . $value['description'] . '<br />';
		}
	}


	/**
	 * Generate a Term List.
	 *
	 * @access public
	 */
	public static function defaultkind_callback() {
		$terms = get_option( 'kind_termslist' );
		$terms[] = 'note';

		$defaultkind = get_option( 'kind_default' );

		foreach ( $terms as $term ) {
			echo '<input id="kind_default" name="kind_default" type="radio" value="' . $term . '" '. checked( $term, $defaultkind, false ) . ' />' . Kind_Taxonomy::get_kind_info( $term, 'singular_name' ) . '<br />';
		}
	}



	/**
	 * Generate Options Form.
	 *
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

	public static function add_post_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'       => 'post_kind_help',
			'title'    => __( 'Post Properties', 'indieweb-post-kinds' ),
			'content'  => __('
				<p> The Post Properties tab represents the Microformats properties of a Post. For different kinds of posts, the different
				fields mean something different. Example: Artist Name vs Author Name</p>
				<ul><strong>Main</strong>
					<li><strong>URL</strong> - The URL the post is responding to</li>
					<li><strong>Name/Title</strong> - The name of what is being responded to</li>
					<li><strong>Summary/Quote</strong> - Summary of what the post is responding to or quote</li>
					<li><strong>Tags</strong> - Tags or categories for the piece to be displayed as hashtags</li>
				</ul>
				<ul><strong>Details</strong>
					<li><strong>Site Name/Publication/Album</strong></li>
					<li><strong>Featured Image/Site Icon</strong> - The URL of a featured image of what is being responded to or a site icon</li>
              				<li><strong>Published Time</strong></li>
              				<li><strong>Updated Time</strong></li>
				</ul>
				<ul><strong>Author</strong>
					<li><strong>Author/Artist Name</strong> </li>
					<li><strong>Author/Artist URL</strong></li>
					<li><strong>Author/Artist Photo URL</strong></li>
				</ul>
				<ul><strong>Other</strong>
              				<li><strong>Start Time</strong></li>
              				<li><strong>End Time</strong></li>
					<li><strong>Duration</strong> - Duration is calculated based on the difference between start and end time. You may just use the time field, 
					omitting date and timezone and setting start time to 0:00:00 to set a simple duration.</li> 
					<li><strong>RSVP</strong> - For RSVP posts, you can specify whether you are attending, not attending, unsure, or simply interested.</li>
            			</ul>
			', 'indieweb-post-kinds'),
		) );
	}

} // End Class

?>
