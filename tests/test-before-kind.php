<?php

/**
 * The kind's icon or name before the response (#508).
 */
class BeforeKindTest extends WP_UnitTestCase {
	public function test_icon_outside_feeds() {
		$before = Kind_Taxonomy::get_before_kind( 'like', 'icon' );
		$this->assertStringContainsString( '<svg', $before );
		$this->assertStringNotContainsString( 'kind-display-text', $before );
	}

	public function test_icon_falls_back_to_text_in_feeds() {
		$this->go_to( home_url( '/?feed=rss2' ) );
		$this->assertTrue( is_feed() );
		$before = Kind_Taxonomy::get_before_kind( 'like', 'icon' );
		$this->assertSame( Kind_Taxonomy::get_before_kind( 'like', 'text' ), $before );
		$this->assertStringContainsString( 'kind-display-text', $before );
		$this->assertStringNotContainsString( '<svg', $before );
	}

	public function test_both_drops_icon_in_feeds() {
		$this->go_to( home_url( '/?feed=rss2' ) );
		$before = Kind_Taxonomy::get_before_kind( 'like', 'both' );
		$this->assertSame( Kind_Taxonomy::get_before_kind( 'like', 'text' ), $before );
	}

	public function test_display_icons_filter() {
		add_filter( 'kind_display_icons', '__return_false' );
		$before = Kind_Taxonomy::get_before_kind( 'like', 'icon' );
		remove_filter( 'kind_display_icons', '__return_false' );
		$this->assertSame( Kind_Taxonomy::get_before_kind( 'like', 'text' ), $before );

		$this->go_to( home_url( '/?feed=rss2' ) );
		add_filter( 'kind_display_icons', '__return_true' );
		$before = Kind_Taxonomy::get_before_kind( 'like', 'icon' );
		remove_filter( 'kind_display_icons', '__return_true' );
		$this->assertStringContainsString( '<svg', $before );
	}
}
