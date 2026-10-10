<?php

/**
 * Citations with several authors (#132, #156).
 */
class AuthorsTest extends WP_UnitTestCase {
	public function test_normalize_authors() {
		$this->assertSame( array(), Kind_Post::normalize_authors( '' ) );
		$this->assertSame( array(), Kind_Post::normalize_authors( array() ) );
		$this->assertSame(
			array(
				array(
					'type'  => 'card',
					'name'  => '',
					'url'   => 'https://example.com/ann',
					'photo' => '',
				),
			),
			Kind_Post::normalize_authors( 'https://example.com/ann' )
		);
		$this->assertSame( 'Ann', Kind_Post::normalize_authors( 'Ann' )[0]['name'] );

		// A list of h-cards, as mf2 or jf2, as Micropub and Parse This store them.
		$authors = Kind_Post::normalize_authors(
			array(
				array(
					'type'       => array( 'h-card' ),
					'properties' => array(
						'name' => array( 'Ann' ),
						'url'  => array( 'https://example.com/ann' ),
					),
				),
				array(
					'type' => 'card',
					'name' => 'Bob',
				),
			)
		);
		$this->assertSame( array( 'Ann', 'Bob' ), wp_list_pluck( $authors, 'name' ) );
		$this->assertSame( array( 'https://example.com/ann', '' ), wp_list_pluck( $authors, 'url' ) );

		// One card holding lists, as the metabox saved several authors before 4.0.0.
		$authors = Kind_Post::normalize_authors(
			array(
				'type'  => 'card',
				'name'  => array( 'Ann', 'Bob' ),
				'url'   => array( 'https://example.com/ann', 'https://example.com/bob' ),
				'photo' => array( 'https://example.com/ann.jpg' ),
			)
		);
		$this->assertSame( array( 'Ann', 'Bob' ), wp_list_pluck( $authors, 'name' ) );
		$this->assertSame( array( 'https://example.com/ann', 'https://example.com/bob' ), wp_list_pluck( $authors, 'url' ) );
		$this->assertSame( array( 'https://example.com/ann.jpg', '' ), wp_list_pluck( $authors, 'photo' ) );
	}

	public function test_normalize_cite_with_several_authors() {
		$kind_post = new Kind_Post( self::factory()->post->create() );
		$cite      = $kind_post->normalize_cite(
			array(
				'type'   => 'cite',
				'name'   => 'A Book',
				'author' => array(
					array(
						'type' => 'card',
						'name' => 'Ann',
						'url'  => 'https://example.com/ann',
					),
					array(
						'type'  => 'card',
						'name'  => 'Bob',
						'photo' => 'https://example.com/bob.jpg',
					),
				),
			)
		);
		$this->assertCount( 2, $cite['authors'] );
		$this->assertSame( 'Ann; Bob', $cite['author']['name'] );
		$this->assertSame( 'https://example.com/ann; ', $cite['author']['url'] );
		$this->assertSame( '; https://example.com/bob.jpg', $cite['author']['photo'] );

		$cite = $kind_post->normalize_cite( array( 'name' => 'No author' ) );
		$this->assertSame( array(), $cite['authors'] );
		$this->assertSame( '', $cite['author']['name'] );
	}

	public function test_view_shows_every_author() {
		Kind_Taxonomy::kind_defaultterms();
		$id = self::factory()->post->create( array( 'post_content' => 'c' ) );
		set_post_kind( $id, 'read' );
		( new Kind_Post( $id ) )->set(
			'read-of',
			array(
				'type'       => array( 'h-cite' ),
				'properties' => array(
					'name'   => array( 'A Book' ),
					'url'    => array( 'https://example.com/book' ),
					'author' => array(
						array(
							'type'       => array( 'h-card' ),
							'properties' => array(
								'name' => array( 'Ann' ),
								'url'  => array( 'https://example.com/ann' ),
							),
						),
						array(
							'type'       => array( 'h-card' ),
							'properties' => array( 'name' => array( 'Bob' ) ),
						),
					),
				),
			)
		);
		$html = Kind_View::get_view_part( 'kind', 'read', array( 'post_id' => $id ) );
		$this->assertStringContainsString( '<a href="https://example.com/ann" class="h-card p-author">Ann</a>', $html );
		$this->assertStringContainsString( '<span class="h-card p-author">Bob</span>', $html );
		$this->assertSame( 2, substr_count( $html, 'h-card p-author' ) );
	}
}
