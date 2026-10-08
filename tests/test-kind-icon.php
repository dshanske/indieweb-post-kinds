<?php

class KindIconTest extends WP_UnitTestCase {
	public function test_get_icon() {
		$icon = Kind_Taxonomy::get_icon( 'like' );
		$this->assertStringStartsWith( '<span class="svg-icon svg-like" aria-label="Like"', $icon );
		$this->assertStringContainsString( '<svg', $icon );
		$this->assertSame( $icon, Kind_Taxonomy::get_icon( 'like' ) );
		$this->assertSame( '', Kind_Taxonomy::get_icon( 'no-such-kind' ) );
	}
}
