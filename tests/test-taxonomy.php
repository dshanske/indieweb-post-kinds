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

	public function test_kind_permalink_uses_the_term_cache() {
		Kind_Taxonomy::kind_defaultterms();
		$post = self::factory()->post->create();
		set_post_kind( $post, 'like' );
		get_the_terms( $post, 'kind' );
		$queries = $GLOBALS['wpdb']->num_queries;
		$this->assertSame( '/like/x/', Kind_Taxonomy::kind_permalink( '/%kind%/x/', $post, false ) );
		$this->assertSame( $queries, $GLOBALS['wpdb']->num_queries );
	}
}
