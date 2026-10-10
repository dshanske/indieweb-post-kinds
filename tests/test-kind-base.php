<?php

/**
 * The Kind base setting on Settings > Permalinks (#207).
 */
class KindBaseTest extends WP_UnitTestCase {
	public function tear_down() {
		delete_option( 'kind_base' );
		$_POST    = array();
		$_REQUEST = array();
		Kind_Taxonomy::register();
		parent::tear_down();
	}

	public function test_sanitize_kind_base() {
		$this->assertSame( 'soort', Kind_Config::sanitize_kind_base( ' Soort ' ) );
		$this->assertSame( 'blog/soort', Kind_Config::sanitize_kind_base( '/blog//Soort/' ) );
		$this->assertSame( '', Kind_Config::sanitize_kind_base( 'kind' ) );
		$this->assertSame( '', Kind_Config::sanitize_kind_base( '' ) );
		$this->assertSame( '', Kind_Config::sanitize_kind_base( array( 'soort' ) ) );
	}

	public function test_get_kind_base() {
		$this->assertSame( 'kind', Kind_Taxonomy::get_kind_base() );
		update_option( 'kind_base', 'Soort' );
		$this->assertSame( 'soort', get_option( 'kind_base' ) );
		$this->assertSame( 'soort', Kind_Taxonomy::get_kind_base() );

		$callback = function () {
			return 'type';
		};
		add_filter( 'kind_base_slug', $callback );
		$this->assertSame( 'type', Kind_Taxonomy::get_kind_base() );
		remove_filter( 'kind_base_slug', $callback );
	}

	public function test_archive_links_and_rules_use_the_base() {
		Kind_Taxonomy::kind_defaultterms();
		update_option( 'kind_base', 'soort' );
		$this->set_permalink_structure( '/%postname%/' );
		Kind_Taxonomy::register();
		flush_rewrite_rules();

		$this->assertSame( home_url( '/soort/note/' ), get_term_link( 'note', 'kind' ) );
		$rules = get_option( 'rewrite_rules' );
		$this->assertArrayHasKey( 'soort/([^/]+)/?$', $rules );
		$this->assertArrayHasKey( 'soort/([^/]+)/([0-9]{4})/([0-9]{1,2})/?$', $rules );
		$this->assertArrayNotHasKey( 'kind/([^/]+)/?$', $rules );
	}

	public function test_permalink_page_saves_the_base() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST    = array(
			'kind_base' => 'Soort',
			'_wpnonce'  => wp_create_nonce( 'update-permalink' ),
		);
		$_REQUEST = $_POST;
		Kind_Config::save_kind_base();
		$this->assertSame( 'soort', get_option( 'kind_base' ) );
	}

	public function test_permalink_page_needs_manage_options() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$_POST    = array(
			'kind_base' => 'soort',
			'_wpnonce'  => wp_create_nonce( 'update-permalink' ),
		);
		$_REQUEST = $_POST;
		Kind_Config::save_kind_base();
		$this->assertSame( '', get_option( 'kind_base', '' ) );
	}
}
