<?php

class FeedsTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	public function tear_down() {
		delete_option( 'kind_title' );
		parent::tear_down();
	}

	public function test_core_feed_handlers_are_kept() {
		$this->assertSame( 10, has_action( 'do_feed_rss2', 'do_feed_rss2' ) );
		$this->assertSame( 10, has_action( 'do_feed_atom', 'do_feed_atom' ) );
		$this->assertFalse( method_exists( 'Post_Kinds_Plugin', 'do_feed_rss2' ) );
	}

	private function untitled_note() {
		$id = self::factory()->post->create(
			array(
				'post_title'   => '',
				'post_content' => 'A short note about feeds',
			)
		);
		set_post_kind( $id, 'note' );
		return $id;
	}

	public function test_untitled_post_is_named_in_comment_feeds() {
		$id = $this->untitled_note();
		self::factory()->comment->create( array( 'comment_post_ID' => $id ) );
		$this->go_to( home_url( '/?feed=comments-rss2' ) );
		$this->assertTrue( is_comment_feed() );
		$this->assertSame( 'A short note about feeds', get_the_title( $id ) );
	}

	public function test_untitled_post_title_in_post_feeds() {
		$id = $this->untitled_note();
		$this->go_to( home_url( '/?feed=rss2' ) );
		$this->assertSame( '', get_the_title( $id ) );

		update_option( 'kind_title', 1 );
		$this->assertSame( '[Note] A short note about feeds', get_the_title( $id ) );
	}
}
