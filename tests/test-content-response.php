<?php

/**
 * The kind_content_response filter (#509).
 */
class ContentResponseTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	private function bookmark() {
		$id = self::factory()->post->create( array( 'post_content' => 'Body' ) );
		set_post_kind( $id, 'bookmark' );
		$kind_post = new Kind_Post( $id );
		$kind_post->set(
			'bookmark-of',
			array(
				'url'  => 'https://example.com/a',
				'name' => 'Cited Name',
			)
		);
		$this->go_to( get_permalink( $id ) );
		$GLOBALS['post'] = get_post( $id );
		return $id;
	}

	public function test_response_is_added() {
		$this->bookmark();
		$content = Kind_View::content_response( 'Body' );
		$this->assertStringContainsString( 'Cited Name', $content );
		$this->assertStringEndsWith( 'Body', $content );
	}

	public function test_filter_skips_response() {
		$id   = $this->bookmark();
		$seen = null;
		$skip = function ( $add, $post ) use ( &$seen ) {
			$seen = $post;
			return false;
		};
		add_filter( 'kind_content_response', $skip, 10, 2 );
		$content = Kind_View::content_response( 'Body' );
		remove_filter( 'kind_content_response', $skip, 10 );
		$this->assertSame( 'Body', $content );
		$this->assertSame( $id, $seen->ID );
	}
}
