<?php

class EscapingViewsTest extends WP_UnitTestCase {
	const BREAKOUT = '"><script>alert(1)</script>';

	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	private function cite( $suffix ) {
		return array(
			'type'        => 'cite',
			'url'         => 'https://example.com/a',
			'name'        => 'Name' . $suffix,
			'publication' => 'Pub' . $suffix,
			'summary'     => '<p>Quoted &lt;3 text</p>' . $suffix,
			'author'      => array(
				'type'  => 'card',
				'name'  => 'Ann' . $suffix,
				'url'   => 'https://example.com/ann',
				'photo' => 'https://example.com/p.jpg',
			),
		);
	}

	private function render( $kind, $cite, $meta = array() ) {
		$id = self::factory()->post->create( array( 'post_content' => 'c' ) );
		set_post_kind( $id, $kind );
		$kind_post = new Kind_Post( $id );
		$property  = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		if ( $property ) {
			$kind_post->set( $property, $cite );
		}
		$kind_post->set( $meta );
		return Kind_View::get_view_part( 'kind', $kind, array( 'post_id' => $id ) );
	}

	public function kinds() {
		$kinds = array( 'like', 'bookmark', 'favorite', 'reply', 'repost', 'read', 'listen', 'watch', 'jam', 'rsvp', 'checkin', 'eat', 'drink', 'issue', 'audio', 'video', 'photo', 'event', 'itinerary', 'quote', 'follow', 'wish', 'play', 'acquisition' );
		return array_combine( $kinds, array_map( 'array', $kinds ) );
	}

	/**
	 * @dataProvider kinds
	 */
	public function test_view_escapes_citation( $kind ) {
		if ( ! Kind_Taxonomy::get_post_kind_info( $kind ) ) {
			$this->markTestSkipped( "$kind is not registered." );
		}
		$html = $this->render(
			$kind,
			$this->cite( self::BREAKOUT ),
			array(
				'rating'      => '5' . self::BREAKOUT,
				'rsvp'        => 'yes',
				'read-status' => 'reading',
				'itinerary'   => array(
					array(
						'type'       => array( 'h-leg' ),
						'properties' => array(
							'operator' => array( 'Op' . self::BREAKOUT ),
							'origin'   => array( 'SFO' . self::BREAKOUT ),
						),
					),
				),
			)
		);
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( 'e-summary', $html );
	}

	public function test_citation_markup_is_kept() {
		$html = $this->render( 'like', $this->cite( ' & co' ) );
		$this->assertStringContainsString( 'class="response u-like-of h-cite"', $html );
		$this->assertStringContainsString( '<a href="https://example.com/a" class="p-name u-url">Name &amp; co</a>', $html );
		$this->assertStringContainsString( '<span class="p-publication">Pub &amp; co</span>', $html );
		$this->assertStringContainsString( '<blockquote class="p-summary">Quoted &lt;3 text &amp; co</blockquote>', $html );
		$this->assertStringContainsString( 'class="h-card p-author"', $html );
	}

	public function test_read_view_shows_publication_and_status() {
		$html = $this->render( 'read', $this->cite( '' ), array( 'read-status' => 'finished' ) );
		$this->assertStringContainsString( '<span class="p-read-status">Finished Reading: </span>', $html );
		$this->assertStringContainsString( '<span class="p-publication">Pub</span>', $html );
	}

	public function test_rsvp_view_links_the_event() {
		$html = $this->render( 'rsvp', $this->cite( '' ), array( 'rsvp' => 'interested' ) );
		$this->assertStringContainsString( '<data class="p-rsvp" value="interested">Interested in Attending <a href="https://example.com/a" class="u-in-reply-to">Name</a></data>', $html );
	}

	public function test_rsvp_text() {
		$this->assertSame( 'Attending <a href="https://example.com/e" class="u-in-reply-to">Party &amp; more</a>', Kind_View::rsvp_text( 'yes', 'https://example.com/e', 'Party & more<script>' ) );
		$this->assertSame( 'Attending <span class="p-in-reply-to">Party</span> remotely', Kind_View::rsvp_text( 'remote', '', 'Party' ) );
		$this->assertSame( 'Attending <a href="https://example.com/e" class="u-in-reply-to">https://example.com/e</a>', Kind_View::rsvp_text( 'yes', 'https://example.com/e' ) );
		// Called with only a type, it stays printf-ready, as before.
		$this->assertSame( 'Unable to Attend <a href="%1$s" class="u-in-reply-to">%2$s</a>', Kind_View::rsvp_text( 'no' ) );
		$this->assertSame( '', Kind_View::rsvp_text( 'never' ) );
		$this->assertSame( '', Kind_View::rsvp_text( '' ) );
	}

	public function test_rating_and_read_text() {
		$this->assertSame( '⭐⭐⭐', Kind_View::rating_text( '3' ) );
		$this->assertSame( str_repeat( '⭐', 10 ), Kind_View::rating_text( 1000000 ) );
		$this->assertSame( '', Kind_View::rating_text( '5"><script>' ) );
		$this->assertSame( '', Kind_View::rating_text( -2 ) );
		$this->assertSame( '', Kind_View::read_text( 'unknown' ) );
		$this->assertSame( 'Reading: ', Kind_View::read_text( 'reading' ) );
	}
}
