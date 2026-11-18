<?php

/**
 * Kind_Config: settings registration, the settings page and the admin bar (#521).
 *
 * The sanitize callbacks and the field callbacks' escaping are covered in
 * test-settings-sanitize.php, test-escaping-widgets-settings.php and
 * test-php8-fatals.php.
 */
class ConfigTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		Kind_Taxonomy::kind_defaultterms();
	}

	public function tear_down() {
		remove_action( 'admin_bar_menu', array( 'Kind_Config', 'dashbar_links' ), 20 );
		remove_action( 'admin_bar_menu', array( 'Kind_Config', 'remove_dashbar_post' ), 200 );
		remove_filter( 'query_vars', array( 'Kind_Config', 'query_var' ) );
		unset( $GLOBALS['wp_settings_fields'], $GLOBALS['wp_settings_sections'], $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	public function test_init_registers_the_settings_and_hooks() {
		Kind_Config::init();

		$settings = get_registered_settings();
		foreach ( array( 'kind_termslist', 'kind_default', 'kind_embeds', 'kind_firehose', 'kind_bottom', 'kind_display', 'kind_kses', 'kind_title' ) as $name ) {
			$this->assertArrayHasKey( $name, $settings, $name );
			$this->assertSame( 'iwt_options', $settings[ $name ]['group'], $name );
		}
		$this->assertSame( 'permalink', $settings['kind_base']['group'] );
		$this->assertSame( array( 'Kind_Config', 'sanitize_kind_list' ), $settings['kind_firehose']['sanitize_callback'] );

		$this->assertSame( 10, has_action( 'admin_init', array( 'Kind_Config', 'admin_init' ) ) );
		$this->assertSame( 11, has_action( 'admin_menu', array( 'Kind_Config', 'admin_menu' ) ) );
		$this->assertSame( 20, has_action( 'load-post.php', array( 'Kind_Config', 'add_post_help_tab' ) ) );
		$this->assertSame( 10, has_action( 'load-options-permalink.php', array( 'Kind_Config', 'save_kind_base' ) ) );
	}

	public function test_registered_defaults() {
		Kind_Config::init();
		foreach ( array( 'kind_termslist', 'kind_default', 'kind_embeds', 'kind_display', 'kind_kses', 'kind_title', 'kind_base' ) as $name ) {
			delete_option( $name );
		}
		$this->assertSame( array( 'article', 'reply', 'bookmark' ), get_option( 'kind_termslist' ) );
		$this->assertSame( 'note', get_option( 'kind_default' ) );
		$this->assertSame( 1, get_option( 'kind_embeds' ) );
		$this->assertSame( 'icon', get_option( 'kind_display' ) );
		$this->assertSame( 0, get_option( 'kind_title' ) );
		$this->assertSame( '', get_option( 'kind_base' ) );
		$this->assertIsArray( json_decode( get_option( 'kind_kses' ), true ) );
	}

	public function test_registered_settings_are_sanitized_on_save() {
		Kind_Config::init();
		update_option( 'kind_display', 'sideways' );
		$this->assertSame( 'icon', get_option( 'kind_display' ) );
		update_option( 'kind_default', 'no-such-kind' );
		$this->assertSame( 'note', get_option( 'kind_default' ) );
		update_option( 'kind_bottom', 'yes' );
		$this->assertSame( 1, get_option( 'kind_bottom' ) );
	}

	public function test_admin_init_adds_the_settings_fields() {
		Kind_Config::admin_init();
		global $wp_settings_fields, $wp_settings_sections;

		$this->assertArrayHasKey( 'iwt-content', $wp_settings_sections['iwt_options'] );
		$fields = $wp_settings_fields['iwt_options']['iwt-content'];
		foreach ( array( 'termslist', 'defaultkind', 'embeds', 'firehose', 'bottom', 'display', 'title' ) as $id ) {
			$this->assertArrayHasKey( $id, $fields, $id );
		}
		$this->assertSame( array( 'Kind_Config', 'checkbox_callback' ), $fields['embeds']['callback'] );
		$this->assertSame( 'kind_embeds', $fields['embeds']['args']['name'] );
		$this->assertSame( array( 'icon', 'text', 'both', 'hide' ), array_keys( $fields['display']['args']['options'] ) );
		$this->assertSame( '', $fields['display']['args']['class'] );

		// The KSES field is only offered when POST_KINDS_KSES is set, which it isn't in tests.
		$this->assertFalse( POST_KINDS_KSES );
		$this->assertArrayNotHasKey( 'contentelements', $fields );

		$this->assertArrayHasKey( 'kind_base', $wp_settings_fields['permalink']['optional'] );
		$this->assertSame( 'kind_base', $wp_settings_fields['permalink']['optional']['kind_base']['args']['label_for'] );

		$this->assertSame( 20, has_action( 'admin_bar_menu', array( 'Kind_Config', 'dashbar_links' ) ) );
		$this->assertSame( 200, has_action( 'admin_bar_menu', array( 'Kind_Config', 'remove_dashbar_post' ) ) );
		$this->assertSame( 10, has_filter( 'query_vars', array( 'Kind_Config', 'query_var' ) ) );
	}

	public function test_display_field_is_hidden_when_kind_icon_display_is_off() {
		add_filter( 'kind_icon_display', '__return_false' );
		Kind_Config::admin_init();
		remove_filter( 'kind_icon_display', '__return_false' );

		global $wp_settings_fields;
		$this->assertSame( 'hidden', $wp_settings_fields['iwt_options']['iwt-content']['display']['args']['class'] );
	}

	public function test_query_var_adds_kindurl() {
		$this->assertSame( array( 'p', 'kindurl' ), Kind_Config::query_var( array( 'p' ) ) );
	}

	public function test_admin_bar_new_content_links() {
		update_option( 'kind_termslist', array( 'reply', 'note', 'like' ) );
		$bar = new WP_Admin_Bar();
		$bar->add_node(
			array(
				'id'    => 'new-post',
				'title' => 'Post',
			)
		);

		Kind_Config::dashbar_links( $bar );
		Kind_Config::remove_dashbar_post( $bar );

		$this->assertNull( $bar->get_node( 'new-post' ) );
		foreach ( array( 'note', 'reply', 'like' ) as $kind ) {
			$node = $bar->get_node( $kind );
			$this->assertNotNull( $node, $kind );
			$this->assertSame( 'new-content', $node->parent );
			$this->assertSame( Kind_Taxonomy::get_kind_info( $kind, 'singular_name' ), $node->title );
			$this->assertSame( admin_url( 'post-new.php?kind=' . $kind ), $node->href );
		}
		$this->assertNull( $bar->get_node( 'bookmark' ) );
	}

	public function test_admin_menu_adds_the_options_page() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		global $submenu;
		$saved   = $submenu;
		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		Kind_Config::admin_menu();
		$slugs = wp_list_pluck( $submenu['options-general.php'] ?? array(), 2 );

		$submenu = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertContains( 'kind_options', $slugs );
	}

	public function test_admin_menu_needs_manage_options() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		global $submenu;
		$saved   = $submenu;
		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		Kind_Config::admin_menu();
		$slugs = wp_list_pluck( $submenu['options-general.php'] ?? array(), 2 );

		$submenu = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertNotContains( 'kind_options', $slugs );
	}

	public function test_kind_base_field() {
		update_option( 'kind_base', 'soort' );
		ob_start();
		Kind_Config::kind_base_callback();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'name="kind_base"', $html );
		$this->assertStringContainsString( 'value="soort"', $html );
		$this->assertStringContainsString( 'placeholder="kind"', $html );
	}

	public function test_checkbox_field() {
		update_option( 'kind_bottom', 1 );
		ob_start();
		Kind_Config::checkbox_callback( array( 'name' => 'kind_bottom' ) );
		$html = ob_get_clean();
		// The hidden input saves 0 when the box is unchecked.
		$this->assertStringContainsString( '<input name="kind_bottom" type="hidden" value="0" />', $html );
		$this->assertStringContainsString( "checked='checked'", $html );

		delete_option( 'kind_bottom' );
		ob_start();
		Kind_Config::checkbox_callback( array( 'name' => 'kind_bottom' ) );
		$this->assertStringNotContainsString( 'checked', ob_get_clean() );
	}

	public function test_options_form() {
		Kind_Config::admin_init();
		ob_start();
		Kind_Config::options_callback();
		Kind_Config::options_form();
		$html = ob_get_clean();
		$this->assertStringContainsString( '<form method="post" action="options.php">', $html );
		$this->assertStringContainsString( "name='option_page' value='iwt_options'", $html );
		$this->assertStringContainsString( 'name="_wpnonce"', $html );
		$this->assertStringContainsString( 'Content Options', $html );
		$this->assertStringContainsString( 'name="kind_termslist[]"', $html );
		$this->assertStringContainsString( 'name="kind_default"', $html );
		$this->assertStringContainsString( 'type="submit"', $html );
	}

	public function test_post_help_tab() {
		set_current_screen( 'post' );
		Kind_Config::add_post_help_tab();
		$tab = get_current_screen()->get_help_tab( 'post_kind_help' );
		$this->assertNotNull( $tab );
		$this->assertSame( 'Post Properties', $tab['title'] );
		$this->assertStringContainsString( 'Microformats properties', $tab['content'] );
	}
}
