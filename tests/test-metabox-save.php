<?php

class MetaboxSaveTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Saves the metabox for a new post of the kind, posting the fields slashed
	 * as WordPress does, and returns the saved post ID.
	 */
	private function save( $kind, $fields, $id = 0 ) {
		if ( ! $id ) {
			$id = self::factory()->post->create();
			set_post_kind( $id, $kind );
		}
		$_POST = wp_slash( array_merge( array( 'replykind_metabox_nonce' => wp_create_nonce( 'replykind_metabox' ) ), $fields ) );
		Kind_Metabox::save_post( $id, get_post( $id ) );
		return $id;
	}

	private function cite( $id, $kind ) {
		$cite = get_post_meta( $id, 'mf2_' . Kind_Taxonomy::get_kind_info( $kind, 'property' ), true );
		return $cite['properties'];
	}

	public function test_text_and_urls_are_sanitized_not_encoded() {
		$id   = $this->save(
			'bookmark',
			array(
				'cite_url'          => 'https://example.com/a?x=1&y=2',
				'cite_name'         => 'O\'Brien "quoted" C:\\path <b>bold</b>',
				'cite_publication'  => 'Pub & Co',
				'cite_tags'         => 'one; two;;three',
				'cite_featured'     => 'javascript:alert(1)',
				'cite_author_name'  => 'Ann;Bob',
				'cite_author_url'   => 'https://example.com/ann?a=1&b=2;javascript:alert(1)',
				'cite_author_photo' => 'https://example.com/p.jpg',
			)
		);
		$cite = $this->cite( $id, 'bookmark' );
		$this->assertSame( array( 'https://example.com/a?x=1&y=2' ), $cite['url'] );
		$this->assertSame( array( 'O\'Brien "quoted" C:\\path bold' ), $cite['name'] );
		$this->assertSame( array( 'Pub & Co' ), $cite['publication'] );
		$this->assertSame( array( 'one', 'two', 'three' ), $cite['category'] );
		$this->assertArrayNotHasKey( 'featured', $cite );
		// Ann keeps her URL and photo; Bob's unsafe URL is dropped.
		$this->assertSame( array( 'Ann' ), $cite['author'][0]['properties']['name'] );
		$this->assertSame( array( 'https://example.com/ann?a=1&b=2' ), $cite['author'][0]['properties']['url'] );
		$this->assertSame( array( 'https://example.com/p.jpg' ), $cite['author'][0]['properties']['photo'] );
		$this->assertSame( array( 'name' => array( 'Bob' ) ), $cite['author'][1]['properties'] );
	}

	/**
	 * Several authors are saved as one h-card each, paired by position (#132, #156).
	 */
	public function test_several_authors_are_separate_hcards() {
		$id     = $this->save(
			'read',
			array(
				'cite_url'          => 'https://example.com/book',
				'cite_author_name'  => 'Ann; Bob; Cat',
				'cite_author_url'   => 'https://example.com/ann; ; https://example.com/cat',
				'cite_author_photo' => '; https://example.com/bob.jpg',
			)
		);
		$author = $this->cite( $id, 'read' )['author'];
		$this->assertCount( 3, $author );
		$this->assertSame( array( 'h-card' ), $author[0]['type'] );
		$this->assertSame(
			array(
				'name' => array( 'Ann' ),
				'url'  => array( 'https://example.com/ann' ),
			),
			$author[0]['properties']
		);
		$this->assertSame(
			array(
				'name'  => array( 'Bob' ),
				'photo' => array( 'https://example.com/bob.jpg' ),
			),
			$author[1]['properties']
		);
		$this->assertSame(
			array(
				'name' => array( 'Cat' ),
				'url'  => array( 'https://example.com/cat' ),
			),
			$author[2]['properties']
		);

		// The metabox shows them as it took them, so saving again changes nothing.
		$kind_post = new Kind_Post( $id );
		$fields    = $kind_post->normalize_cite( $kind_post->get_cite() )['author'];
		$this->assertSame( 'Ann; Bob; Cat', $fields['name'] );
		$this->assertSame( 'https://example.com/ann; ; https://example.com/cat', $fields['url'] );
		$this->assertSame( '; https://example.com/bob.jpg; ', $fields['photo'] );
		$stored = get_post_meta( $id, 'mf2_read-of', true );
		$this->save(
			'read',
			array(
				'cite_url'          => 'https://example.com/book',
				'cite_author_name'  => $fields['name'],
				'cite_author_url'   => $fields['url'],
				'cite_author_photo' => $fields['photo'],
			),
			$id
		);
		$this->assertEquals( $stored, get_post_meta( $id, 'mf2_read-of', true ) );
	}

	public function test_one_author_is_a_single_hcard() {
		$id     = $this->save(
			'read',
			array(
				'cite_url'         => 'https://example.com/book',
				'cite_author_name' => 'Ann',
			)
		);
		$author = $this->cite( $id, 'read' )['author'];
		$this->assertSame( array( 'h-card' ), $author['type'] );
		$this->assertSame( array( 'name' => array( 'Ann' ) ), $author['properties'] );
	}

	public static function clearing_authors_cases() {
		return array(
			'with a name' => array( array( 'cite_name' => 'A book' ) ),
			'URL only'    => array( array() ),
		);
	}

	/**
	 * @dataProvider clearing_authors_cases
	 */
	public function test_clearing_the_author_fields_removes_the_authors( $fields ) {
		$id = $this->save(
			'read',
			array_merge(
				array(
					'cite_url'         => 'https://example.com/book',
					'cite_author_name' => 'Ann; Bob',
				),
				$fields
			)
		);
		$this->save(
			'read',
			array_merge(
				array(
					'cite_url'          => 'https://example.com/book',
					'cite_author_name'  => '',
					'cite_author_url'   => '',
					'cite_author_photo' => '',
				),
				$fields
			),
			$id
		);
		$stored = get_post_meta( $id, 'mf2_read-of', true );
		if ( empty( $fields ) ) {
			// A citation left with only a URL is stored as the URL.
			$this->assertSame( 'https://example.com/book', $stored );
		} else {
			$this->assertArrayNotHasKey( 'author', $stored['properties'] );
			$this->assertSame( array( 'A book' ), $stored['properties']['name'] );
		}
	}

	public function test_summary_is_saved_as_plain_text() {
		$id      = $this->save(
			'bookmark',
			array(
				'cite_url'     => 'https://example.com/a',
				'cite_summary' => "Line one <b>x</b><script>alert(1)</script>\nI <3 it",
			)
		);
		$summary = $this->cite( $id, 'bookmark' )['summary'][0];
		$this->assertStringNotContainsString( '<', $summary );
		$this->assertStringContainsString( "Line one x\n", $summary );
		$this->assertSame( 'I <3 it', html_entity_decode( substr( $summary, strpos( $summary, "\n" ) + 1 ) ) );
	}

	public function test_rsvp_is_validated_and_can_be_cleared() {
		$id = $this->save(
			'rsvp',
			array(
				'cite_url' => 'https://example.com/e',
				'mf2_rsvp' => 'interested',
			)
		);
		$this->assertSame( 'interested', get_post_meta( $id, 'mf2_rsvp', true ) );

		$_POST = wp_slash(
			array(
				'replykind_metabox_nonce' => wp_create_nonce( 'replykind_metabox' ),
				'mf2_rsvp'                => '',
			)
		);
		Kind_Metabox::save_post( $id, get_post( $id ) );
		$this->assertSame( '', get_post_meta( $id, 'mf2_rsvp', true ) );

		$id = $this->save( 'rsvp', array( 'mf2_rsvp' => 'yes"><script>' ) );
		$this->assertSame( '', get_post_meta( $id, 'mf2_rsvp', true ) );
	}

	public function test_missing_optional_fields_do_not_warn() {
		// Only the date is posted, not the time or offset.
		$id = $this->save(
			'listen',
			array(
				'cite_url'       => 'https://example.com/song',
				'mf2_start_date' => '2026-01-01',
				'mf2_rating'     => '4',
			)
		);
		$this->assertSame( '4', (string) get_post_meta( $id, 'mf2_rating', true ) );
	}

	public function test_invalid_nonce_saves_nothing() {
		$id    = self::factory()->post->create();
		$_POST = array(
			'replykind_metabox_nonce' => 'bad',
			'mf2_rsvp'                => 'yes',
		);
		Kind_Metabox::save_post( $id, get_post( $id ) );
		$this->assertSame( '', get_post_meta( $id, 'mf2_rsvp', true ) );
	}

	public function test_saving_again_does_not_nest_the_citation() {
		$fields = array(
			'cite_url'  => 'https://example.com/place',
			'cite_name' => 'Cafe',
		);
		$id     = $this->save( 'checkin', $fields );
		$this->save( 'checkin', $fields, $id );
		$this->save( 'checkin', $fields, $id );
		$cite = get_post_meta( $id, 'mf2_checkin', true );
		$this->assertSame( array( 'h-card' ), $cite['type'] );
		$this->assertEquals(
			array(
				'name' => array( 'Cafe' ),
				'url'  => array( 'https://example.com/place' ),
			),
			$cite['properties']
		);
	}

	public function test_nested_citation_is_flattened_keeping_other_properties() {
		$id = self::factory()->post->create();
		set_post_kind( $id, 'bookmark' );
		// As repeated saves before 4.0.0 stored it.
		update_post_meta(
			$id,
			'mf2_bookmark-of',
			array(
				'type'       => array( 'h-cite' ),
				'properties' => array(
					'properties' => array(
						'name'     => array( 'Old' ),
						'location' => array( 'Somewhere' ),
					),
					'type'       => array( 'cite' ),
					'name'       => array( 'Newer' ),
				),
			)
		);
		$this->save( 'bookmark', array( 'cite_name' => 'Newest' ), $id );
		$this->assertEquals(
			array(
				'location' => array( 'Somewhere' ),
				'name'     => array( 'Newest' ),
			),
			$this->cite( $id, 'bookmark' )
		);
	}

	public function test_naming_a_url_only_citation_adds_no_numeric_property() {
		$id = $this->save( 'reply', array( 'cite_url' => 'https://example.com/post' ) );
		$this->assertSame( 'https://example.com/post', get_post_meta( $id, 'mf2_in-reply-to', true ) );
		$this->save(
			'reply',
			array(
				'cite_url'  => 'https://example.com/post',
				'cite_name' => 'Named',
			),
			$id
		);
		$this->assertEquals(
			array(
				'name' => array( 'Named' ),
				'url'  => array( 'https://example.com/post' ),
			),
			$this->cite( $id, 'reply' )
		);
	}
}
