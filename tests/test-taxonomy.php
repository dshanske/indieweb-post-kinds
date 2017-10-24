<?php

class TaxonomyTest extends WP_UnitTestCase {
	public function test_set_and_get_post_kind() {
		$post = self::factory()->post->create();
		set_post_kind( $post, 'like' );
		$this->assertEquals( 'like', get_post_kind_slug( $post ) );
	}
	public function test_set_and_has_post_kind() {
		$post = self::factory()->post->create();
		set_post_kind( $post, 'like' );
		$this->assertTrue( has_post_kind( 'like', $post ) );
	}
}

