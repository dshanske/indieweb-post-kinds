<?php

class KindIconTest extends WP_UnitTestCase {
	public function test_get_icon() {
		$icon = Kind_Taxonomy::get_icon( 'like' );
		$this->assertStringStartsWith( '<span class="svg-icon svg-like" aria-label="Like"', $icon );
		$this->assertStringContainsString( '<svg', $icon );
		$this->assertSame( $icon, Kind_Taxonomy::get_icon( 'like' ) );
		$this->assertSame( '', Kind_Taxonomy::get_icon( 'no-such-kind' ) );
	}

	/**
	 * Icons are sized by the stylesheet, so themes can resize them (#294).
	 */
	public function test_icon_has_no_inline_style() {
		$icon = Kind_Taxonomy::get_icon( 'like' );
		$this->assertStringStartsWith( '<span class="svg-icon svg-like" aria-label="Like" title="Like"><span aria-hidden="true"><svg width="1em" height="1em" ', $icon );
		$this->assertStringNotContainsString( 'style=', $icon );
	}

	public function test_icon_attributes_filter() {
		$callback = function () {
			return 'data-test="1"';
		};
		add_filter( 'post_kinds_icon_attributes', $callback );
		$icon = Kind_Taxonomy::get_icon( 'like' );
		remove_filter( 'post_kinds_icon_attributes', $callback );
		$this->assertStringStartsWith( '<span class="svg-icon svg-like" aria-label="Like" title="Like" data-test="1">', $icon );
	}
}
