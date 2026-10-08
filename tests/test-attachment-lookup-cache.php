<?php

class AttachmentLookupCacheTest extends WP_UnitTestCase {
	private $audio;
	private $lookups = 0;

	public function set_up() {
		parent::set_up();
		$this->audio = self::factory()->attachment->create_object( 'pk-cache.mp3', 0, array( 'post_mime_type' => 'audio/mpeg' ) );
		update_attached_file( $this->audio, 'pk-cache.mp3' );
		add_filter( 'attachment_url_to_postid', array( $this, 'count_lookup' ) );
	}

	public function tear_down() {
		remove_filter( 'attachment_url_to_postid', array( $this, 'count_lookup' ) );
		parent::tear_down();
	}

	public function count_lookup( $id ) {
		++$this->lookups;
		return $id;
	}

	public function test_lookup_is_cached() {
		$url = wp_get_attachment_url( $this->audio );
		$this->assertSame( $this->audio, Kind_Post::get_attachment_id( $url ) );
		$this->assertSame( $this->audio, Kind_Post::get_attachment_id( $url ) );
		$this->assertSame( 1, $this->lookups );
	}

	public function test_missing_result_is_cached() {
		$this->assertSame( 0, Kind_Post::get_attachment_id( 'https://example.com/x.mp3' ) );
		$this->assertSame( 0, Kind_Post::get_attachment_id( 'https://example.com/x.mp3' ) );
		$this->assertSame( 1, $this->lookups );
	}

	public function test_post_changes_invalidate_the_cache() {
		$url = wp_get_attachment_url( $this->audio );
		$this->assertSame( $this->audio, Kind_Post::get_attachment_id( $url ) );
		wp_delete_attachment( $this->audio, true );
		$this->assertSame( 0, Kind_Post::get_attachment_id( $url ) );
		$this->assertSame( 2, $this->lookups );
	}

	public function test_empty_and_non_string_urls_are_not_looked_up() {
		$this->assertSame( 0, Kind_Post::get_attachment_id( '' ) );
		$this->assertSame( 0, Kind_Post::get_attachment_id( array( 'x' ) ) );
		$this->assertSame( 0, $this->lookups );
	}

	public function test_audio_property_is_looked_up_once_per_render() {
		$post = self::factory()->post->create();
		update_post_meta( $post, 'mf2_audio', array( wp_get_attachment_url( $this->audio ) ) );
		$kind_post = new Kind_Post( $post );
		$this->assertSame( array( $this->audio ), array_values( $kind_post->get_audio() ) );
		$this->assertSame( array( $this->audio ), array_values( $kind_post->get_audio() ) );
		$this->assertSame( 1, $this->lookups );
	}
}
