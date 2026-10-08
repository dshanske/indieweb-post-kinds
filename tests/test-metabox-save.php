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
		$author = $cite['author'][0]['properties'] ?? $cite['author']['properties'];
		$this->assertSame( array( 'Ann', 'Bob' ), $author['name'] );
		$this->assertSame( array( 'https://example.com/ann?a=1&b=2' ), $author['url'] );
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
