<?php

/**
 * Post_Kinds_Plugin: loading, activation, notices, assets and the privacy text (#521).
 *
 * The plugin file is loaded by the test bootstrap before coverage is collected,
 * so these tests call its hooks again directly. The file-level code (constants,
 * autoloader and hook registration) can't be measured.
 */
class PluginBootstrapTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
	}

	/**
	 * Number of rewrite rule flushes counted by count_flush().
	 *
	 * @var int
	 */
	private $flushes = 0;

	/**
	 * Counts rewrite rule flushes, and keeps them soft so no .htaccess is written.
	 *
	 * @return bool
	 */
	public function count_flush() {
		++$this->flushes;
		return false;
	}

	public function tear_down() {
		remove_filter( 'flush_rewrite_rules_hard', array( $this, 'count_flush' ) );
		unset( $GLOBALS['current_screen'] );
		wp_dequeue_style( 'kind' );
		wp_dequeue_style( 'kind-admin' );
		parent::tear_down();
	}

	public function test_init_reads_the_version_from_the_plugin_header() {
		Post_Kinds_Plugin::init();
		$header = get_file_data( dirname( __DIR__ ) . '/indieweb-post-kinds.php', array( 'Version' => 'Version' ) );
		$this->assertSame( $header['Version'], Post_Kinds_Plugin::$version );
		$this->assertTrue( taxonomy_exists( 'kind' ) );
	}

	public function test_plugins_loaded_adds_the_hooks() {
		Post_Kinds_Plugin::plugins_loaded();

		foreach ( array( 'Plugins', 'Media_Metadata', 'Config', 'Metabox', 'View', 'Upgrade' ) as $class ) {
			$this->assertSame( 10, has_action( 'init', array( 'Kind_' . $class, 'init' ) ), $class );
		}
		$this->assertSame( 10, has_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/indieweb-post-kinds.php' ), array( 'Post_Kinds_Plugin', 'settings_link' ) ) );
		$this->assertSame( 10, has_action( 'wp_enqueue_scripts', array( 'Post_Kinds_Plugin', 'style_load' ) ) );
		$this->assertSame( 10, has_action( 'admin_enqueue_scripts', array( 'Post_Kinds_Plugin', 'admin_style_load' ) ) );
		$this->assertSame( 10, has_action( 'admin_init', array( 'Post_Kinds_Plugin', 'privacy_declaration' ) ) );
		$this->assertTrue( has_action( 'widgets_init' ) );
		// The bundled Parse This is current, so neither notice is added.
		$this->assertFalse( has_action( 'admin_notices', array( 'Post_Kinds_Plugin', 'parse_this_error' ) ) );
		$this->assertFalse( has_action( 'admin_notices', array( 'Post_Kinds_Plugin', 'parse_this_outdated_notice' ) ) );
	}

	public function test_parse_this_error_notice() {
		ob_start();
		Post_Kinds_Plugin::parse_this_error();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'Parse This is not installed', $html );
	}

	public function test_parse_this_outdated_notice_is_for_administrators() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		ob_start();
		Post_Kinds_Plugin::parse_this_outdated_notice();
		$this->assertSame( '', ob_get_clean() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		ob_start();
		Post_Kinds_Plugin::parse_this_outdated_notice();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringContainsString( '<strong>' . esc_html( Post_Kinds_Plugin::parse_this_source() ) . '</strong>', $html );
	}

	public function test_editor_error_is_not_shown_on_classicpress() {
		$this->assertSame( ! Post_Kinds_Plugin::is_classicpress(), Post_Kinds_Plugin::show_editor_error() );
		$this->assertSame( function_exists( 'classicpress_version' ), Post_Kinds_Plugin::is_classicpress() );
	}

	public function test_classic_editor_error() {
		// Outside the post screen, the block editor is assumed.
		$this->assertTrue( Post_Kinds_Plugin::post_uses_gutenberg() );
		ob_start();
		Post_Kinds_Plugin::classic_editor_error();
		$this->assertStringContainsString( 'Classic Editor or Disable Gutenberg Plugin is not active', ob_get_clean() );

		set_current_screen( 'post' );
		get_current_screen()->is_block_editor( false );
		$this->assertFalse( Post_Kinds_Plugin::post_uses_gutenberg() );
		$this->assertSame( '', Post_Kinds_Plugin::classic_editor_error() );

		get_current_screen()->is_block_editor( true );
		$this->assertTrue( Post_Kinds_Plugin::post_uses_gutenberg() );
	}

	public function test_rewrite_rules_are_flushed_when_this_plugin_updates() {
		$basename = plugin_basename( dirname( __DIR__ ) . '/indieweb-post-kinds.php' );
		add_filter( 'flush_rewrite_rules_hard', array( $this, 'count_flush' ) );

		$flushes = $this->flushes;
		Post_Kinds_Plugin::upgrader_process_complete(
			null,
			array(
				'action'  => 'update',
				'type'    => 'plugin',
				'plugins' => array( 'other/other.php' ),
			)
		);
		Post_Kinds_Plugin::upgrader_process_complete(
			null,
			array(
				'action' => 'install',
				'type'   => 'plugin',
			)
		);
		$this->assertSame( $flushes, $this->flushes );

		Post_Kinds_Plugin::upgrader_process_complete(
			null,
			array(
				'action'  => 'update',
				'type'    => 'plugin',
				'plugins' => array( 'other/other.php', $basename ),
			)
		);
		$this->assertSame( $flushes + 1, $this->flushes );
	}

	public function test_activate_and_deactivate() {
		Kind_Taxonomy::kind_defaultterms();
		wp_delete_term( get_term_by( 'slug', 'like', 'kind' )->term_id, 'kind' );
		add_filter( 'flush_rewrite_rules_hard', array( $this, 'count_flush' ) );
		$flushes = $this->flushes;

		Post_Kinds_Plugin::activate();
		$this->assertInstanceOf( 'WP_Term', get_term_by( 'slug', 'like', 'kind' ) );
		$this->assertGreaterThan( $flushes, $this->flushes );

		$flushes = $this->flushes;
		Post_Kinds_Plugin::deactivate();
		$this->assertSame( $flushes + 1, $this->flushes );
	}

	public function test_settings_link() {
		$links = Post_Kinds_Plugin::settings_link( array( '<a href="#">Deactivate</a>' ) );
		$this->assertCount( 2, $links );
		$this->assertStringContainsString( '>Settings</a>', $links[0] );
		$this->assertSame( '<a href="#">Deactivate</a>', $links[1] );
	}

	public function test_stylesheets() {
		Post_Kinds_Plugin::init();
		Post_Kinds_Plugin::style_load();
		Post_Kinds_Plugin::admin_style_load();
		$this->assertTrue( wp_style_is( 'kind', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'kind-admin', 'enqueued' ) );
		$this->assertStringEndsWith( 'css/kind.min.css', wp_styles()->registered['kind']->src );
		$this->assertSame( Post_Kinds_Plugin::$version, wp_styles()->registered['kind']->ver );
		$this->assertStringEndsWith( 'css/kind.admin.min.css', wp_styles()->registered['kind-admin']->src );
	}

	public function test_privacy_policy_text() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';
		set_current_screen( 'dashboard' );
		// wp_add_privacy_policy_content() must run during admin_init.
		$GLOBALS['wp_current_filter'][] = 'admin_init';
		Post_Kinds_Plugin::privacy_declaration();
		array_pop( $GLOBALS['wp_current_filter'] );

		$property = new ReflectionProperty( 'WP_Privacy_Policy_Content', 'policy_content' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}
		$content = $property->getValue();
		$property->setValue( null, array() );

		$ours = wp_list_filter( $content, array( 'plugin_name' => 'Post Kinds' ) );
		$this->assertCount( 1, $ours );
		$this->assertStringContainsString( 'rich citation', reset( $ours )['policy_text'] );
	}
}
