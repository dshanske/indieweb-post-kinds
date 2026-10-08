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

	public function test_get_icon() {
		$icon = Kind_Taxonomy::get_icon( 'like' );
		$this->assertStringStartsWith( '<span class="svg-icon svg-like" aria-label="Like"', $icon );
		$this->assertStringContainsString( '<svg', $icon );
		$this->assertSame( $icon, Kind_Taxonomy::get_icon( 'like' ) );
		$this->assertSame( '', Kind_Taxonomy::get_icon( 'no-such-kind' ) );
	}
}
