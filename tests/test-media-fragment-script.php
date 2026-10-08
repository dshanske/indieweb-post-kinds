<?php

class MediaFragmentScriptTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	public function tear_down() {
		wp_dequeue_script( 'media-fragment' );
		parent::tear_down();
	}

	private function enqueued_on( $post_id ) {
		wp_dequeue_script( 'media-fragment' );
		$this->go_to( get_permalink( $post_id ) );
		Kind_Media_Metadata::enqueue();
		return wp_script_is( 'media-fragment' );
	}

	public function test_media_kinds_and_properties_count_as_media() {
		$listen = self::factory()->post->create();
		set_post_kind( $listen, 'listen' );
		$property = self::factory()->post->create();
		update_post_meta( $property, 'mf2_video', array( 'https://example.com/v.mp4' ) );
		$this->assertTrue( Kind_Media_Metadata::has_media( get_post( $listen ) ) );
		$this->assertTrue( Kind_Media_Metadata::has_media( get_post( $property ) ) );
	}

	public function test_media_in_content_counts_as_media() {
		foreach ( array( '<video src="v.mp4"></video>', '<audio controls>', '[audio mp3="a.mp3"]', '[playlist ids="1"]' ) as $content ) {
			$this->assertTrue( Kind_Media_Metadata::has_media( get_post( self::factory()->post->create( array( 'post_content' => $content ) ) ) ), $content );
		}
		$page = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '[video src="v.mp4"]',
			)
		);
		$this->assertTrue( Kind_Media_Metadata::has_media( get_post( $page ) ) );
	}

	public function test_posts_without_media_do_not_count() {
		$note = self::factory()->post->create( array( 'post_content' => 'An audiophile talks about [videos] and <audience>.' ) );
		set_post_kind( $note, 'note' );
		$this->assertFalse( Kind_Media_Metadata::has_media( get_post( $note ) ) );
		$this->assertFalse( Kind_Media_Metadata::has_media( null ) );
	}

	public function test_script_is_enqueued_deferred_only_with_media() {
		$plain = self::factory()->post->create( array( 'post_content' => 'Words' ) );
		$video = self::factory()->post->create( array( 'post_content' => '<video src="v.mp4"></video>' ) );
		$this->assertFalse( $this->enqueued_on( $plain ) );
		$this->assertTrue( $this->enqueued_on( $video ) );
		$this->assertSame( 1, wp_scripts()->get_data( 'media-fragment', 'group' ) );
	}
}
