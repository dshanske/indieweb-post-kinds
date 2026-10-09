<?php

class SmallBugsTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	public function test_photo_month_archive_rule() {
		global $wp_rewrite;
		Kind_Taxonomy::register();
		$this->assertArrayHasKey( 'photos/([0-9]{4})/([0-9]{2})/?$', $wp_rewrite->extra_rules_top );
		$this->assertSame( 'index.php?year=$matches[1]&monthnum=$matches[2]&kind_photos=1', $wp_rewrite->extra_rules_top['photos/([0-9]{4})/([0-9]{2})/?$'] );
	}

	public function test_default_kind_is_set_on_publish_when_missing() {
		update_option( 'kind_default', 'like' );
		$post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
		wp_set_object_terms( $post, array(), 'kind' );
		$this->assertFalse( get_post_kind_slug( $post ) );
		wp_publish_post( $post );
		$this->assertSame( 'like', get_post_kind_slug( $post ) );
	}

	public function test_default_kind_does_not_replace_an_existing_kind() {
		update_option( 'kind_default', 'like' );
		$post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
		set_post_kind( $post, 'bookmark' );
		wp_publish_post( $post );
		$this->assertSame( 'bookmark', get_post_kind_slug( $post ) );
	}

	public function test_kind_flatten_array() {
		$this->assertSame( array( 'a', 'b' ), kind_flatten_array( array( 'a', 'b' ) ) );
		$this->assertSame( 'a', kind_flatten_array( array( 'a' ) ) );
		$this->assertSame( 'a', kind_flatten_array( array( '', 'a' ) ) );
		$this->assertSame( array( 'a', 'b' ), kind_flatten_array( array( array( 'a' ), 'b' ) ) );
		$this->assertSame( '', kind_flatten_array( array( '' ) ) );
		$this->assertSame( 'plain', kind_flatten_array( 'plain' ) );
		// Several authors entered as a;b in the metabox.
		$this->assertSame( array( 'Ann', 'Bob' ), Kind_Metabox::explode( 'Ann;Bob' ) );
		$this->assertSame( 'Ann;Bob', Kind_Metabox::implode( array( 'Ann', 'Bob' ) ) );
	}

	public function test_get_post_kind_string_is_the_name() {
		$this->assertSame( Kind_Taxonomy::get_kind_info( 'like', 'singular_name' ), get_post_kind_string( 'like' ) );
		$this->assertSame( '', get_post_kind_string( 'no-such-kind' ) );
	}

	public function test_not_found_label() {
		$this->assertSame( 'No kinds found', get_taxonomy( 'kind' )->labels->not_found );
	}

	public function test_get_kind_view_part_returns_the_output() {
		$post = self::factory()->post->create( array( 'meta_input' => array( 'mf2_bookmark-of' => array( 'https://example.com/a-bookmark/' ) ) ) );
		set_post_kind( $post, 'bookmark' );
		$this->assertStringContainsString( 'a-bookmark', get_kind_view_part( 'kind', 'bookmark', array( 'post_id' => $post ) ) );
	}

	public function test_media_view_keeps_url_sources_and_microformats_classes() {
		$video = ( new Kind_Media_View( 'https://example.com/clip.mp4', 'video' ) )->get();
		$this->assertStringContainsString( 'https://example.com/clip.mp4', $video );
		$this->assertStringContainsString( 'u-video', $video );
		$audio = ( new Kind_Media_View( 'https://example.com/song.mp3', 'audio' ) )->get();
		$this->assertStringContainsString( 'https://example.com/song.mp3', $audio );
		$this->assertStringContainsString( 'u-audio', $audio );
	}

	public function test_global_functions_can_be_declared_first() {
		// Another plugin or theme may declare these first; loading them again must not fatal.
		require dirname( __DIR__ ) . '/includes/kind-functions.php';
		$this->assertTrue( function_exists( 'kind_get_the_title' ) );
	}
}
