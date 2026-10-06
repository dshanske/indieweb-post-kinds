<?php

class PHP8FatalsTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	/**
	 * Values kind_termslist has been seen with that are not arrays of kinds.
	 */
	public function broken_termslists() {
		return array(
			'false'        => array( false ),
			'empty string' => array( '' ),
			'string'       => array( 'reply' ),
		);
	}

	/**
	 * @dataProvider broken_termslists
	 */
	public function test_get_termslist_always_returns_registered_kinds( $stored ) {
		update_option( 'kind_termslist', $stored );
		$this->assertSame( array(), Kind_Config::get_termslist() );
	}

	public function test_get_termslist_drops_unregistered_kinds() {
		update_option( 'kind_termslist', array( 'reply', 'no-such-kind', 'like' ) );
		$this->assertSame( array( 'reply', 'like' ), Kind_Config::get_termslist() );
	}

	/**
	 * @dataProvider broken_termslists
	 */
	public function test_admin_bar_links_with_broken_option( $stored ) {
		update_option( 'kind_termslist', $stored );
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$bar = new WP_Admin_Bar();
		Kind_Config::dashbar_links( $bar );
		$this->assertNotNull( $bar->get_node( 'note' ) );
	}

	/**
	 * @dataProvider broken_termslists
	 */
	public function test_settings_fields_with_broken_option( $stored ) {
		update_option( 'kind_termslist', $stored );
		ob_start();
		Kind_Config::termcheck_callback();
		Kind_Config::kindmultiselect_callback( array( 'name' => 'kind_firehose' ) );
		Kind_Config::defaultkind_callback();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'value="note"', $html );
	}

	/**
	 * @dataProvider broken_termslists
	 */
	public function test_kind_selector_with_broken_option( $stored ) {
		update_option( 'kind_termslist', $stored );
		$post = self::factory()->post->create_and_get();
		ob_start();
		Kind_Taxonomy::select_metabox( $post );
		$this->assertStringContainsString( 'value="note"', ob_get_clean() );
	}

	/**
	 * @dataProvider broken_termslists
	 */
	public function test_widget_forms_with_broken_option( $stored ) {
		update_option( 'kind_termslist', $stored );
		ob_start();
		( new Kind_Post_Widget() )->form( array() );
		( new Kind_Menu_Widget() )->form( array() );
		$this->assertStringContainsString( 'note', ob_get_clean() );
	}

	public function test_categories_of_a_tagged_post() {
		$post = self::factory()->post->create( array( 'tags_input' => array( 'one', 'two' ) ) );
		$categories = ( new Kind_Post( $post ) )->get_categories();
		$this->assertContains( 'one', $categories );
		$this->assertContains( 'two', $categories );
	}

	public function test_rest_kind_field_reads_each_posts_kind() {
		$reply = self::factory()->post->create();
		$like  = self::factory()->post->create();
		set_post_kind( $reply, 'reply' );
		set_post_kind( $like, 'like' );
		foreach ( array( $reply => 'reply', $like => 'like' ) as $id => $kind ) {
			$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $id ) );
			$this->assertSame( $kind, $response->get_data()['kind'] );
		}
	}

	public function test_rest_kind_field_sets_the_kind() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$post    = self::factory()->post->create();
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $post );
		$request->set_body_params( array( 'kind' => 'bookmark' ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'bookmark', get_post_kind_slug( $post ) );
	}

	public function test_rest_kind_field_rejects_an_invalid_kind() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$post    = self::factory()->post->create();
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $post );
		$request->set_body_params( array( 'kind' => 'no-such-kind' ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_set_rest_post_kind_checks_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$post   = self::factory()->post->create_and_get();
		$result = Kind_Taxonomy::set_rest_post_kind( 'like', $post );
		$this->assertWPError( $result );
		$this->assertSame( 'rest_cannot_edit', $result->get_error_code() );
	}

	public function test_get_photo_with_an_invalid_post() {
		$this->assertFalse( ( new Kind_Post( 0 ) )->get_photo() );
	}

	public function test_get_post_kind_slugs_lists_registered_kinds() {
		$slugs = get_post_kind_slugs();
		$this->assertContains( 'note', $slugs );
		$this->assertContains( 'reply', $slugs );
	}

	public function test_get_display_renders_the_given_post() {
		$target = self::factory()->post->create( array( 'meta_input' => array( 'mf2_bookmark-of' => array( 'https://example.com/target-post/' ) ) ) );
		set_post_kind( $target, 'bookmark' );
		$other = self::factory()->post->create( array( 'meta_input' => array( 'mf2_bookmark-of' => array( 'https://example.com/other-post/' ) ) ) );
		set_post_kind( $other, 'bookmark' );

		// The global post is a different post.
		$GLOBALS['post'] = get_post( $other );
		setup_postdata( $GLOBALS['post'] );
		$display = Kind_View::get_display( $target );
		wp_reset_postdata();

		$this->assertStringContainsString( 'target-post', $display );
		$this->assertStringNotContainsString( 'other-post', $display );
	}

	public function test_get_display_without_a_global_post() {
		$target = self::factory()->post->create( array( 'meta_input' => array( 'mf2_bookmark-of' => array( 'https://example.com/target-post/' ) ) ) );
		set_post_kind( $target, 'bookmark' );
		unset( $GLOBALS['post'] );
		$this->assertStringContainsString( 'target-post', Kind_View::get_display( $target ) );
	}
}
