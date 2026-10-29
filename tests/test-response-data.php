<?php

/**
 * get_post_kind_response() (#510).
 */
class ResponseDataTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	private function post( $kind, $meta ) {
		$id = self::factory()->post->create( array( 'post_content' => 'Body' ) );
		set_post_kind( $id, $kind );
		$kind_post = new Kind_Post( $id );
		$kind_post->set( $meta );
		return $id;
	}

	public function test_no_response() {
		$this->assertNull( get_post_kind_response( 0 ) );
		$note = self::factory()->post->create();
		set_post_kind( $note, 'note' );
		$this->assertNull( get_post_kind_response( $note ) );
	}

	public function test_like() {
		$id   = $this->post(
			'like',
			array(
				'like-of' => array(
					'url'     => 'https://example.com/a',
					'name'    => 'Tom & Jerry',
					'summary' => '<p>Some <b>bold</b> text</p>',
					'author'  => array(
						'type' => 'card',
						'name' => 'Ann',
						'url'  => 'https://example.com/ann',
					),
				),
				'rating'  => '4',
			)
		);
		$data = get_post_kind_response( $id );
		$this->assertSame( $id, $data['post_id'] );
		$this->assertSame( 'like', $data['kind'] );
		$this->assertSame( Kind_Taxonomy::get_kind_info( 'like', 'verb' ), $data['verb'] );
		$this->assertSame( 'like-of', $data['property'] );
		$this->assertSame( 'https://example.com/a', $data['cite']['url'] );
		$this->assertSame( 'Tom & Jerry', $data['cite']['name'] );
		$this->assertSame( 'Some bold text', $data['cite']['summary'] );
		$this->assertSame( 'example.com', $data['cite']['publication'] );
		$this->assertCount( 1, $data['authors'] );
		$this->assertSame( 'Ann', $data['authors'][0]['name'] );
		$this->assertSame( 4, $data['rating'] );
		$this->assertSame( '⭐⭐⭐⭐', $data['rating_text'] );
		$this->assertSame( '', $data['rsvp'] );
		$this->assertSame( '', $data['rsvp_text'] );
		$this->assertSame( array(), $data['photos'] );
		$this->assertSame( array(), $data['itinerary'] );
	}

	public function test_rsvp_text_is_plain() {
		$id   = $this->post(
			'rsvp',
			array(
				'in-reply-to' => array(
					'url'  => 'https://example.com/e',
					'name' => 'Q&A Night',
				),
				'rsvp'        => 'yes',
			)
		);
		$data = get_post_kind_response( $id );
		$this->assertSame( 'yes', $data['rsvp'] );
		$this->assertSame( 'Attending Q&A Night', $data['rsvp_text'] );
	}

	public function test_duration_and_read() {
		$id   = $this->post(
			'read',
			array(
				'read-of'     => array( 'name' => 'A Book' ),
				'read-status' => 'reading',
				'duration'    => 'PT1H30M',
			)
		);
		$data = get_post_kind_response( $id );
		$this->assertSame( 'PT1H30M', $data['duration'] );
		$this->assertStringContainsString( '30', $data['duration_text'] );
		$this->assertStringNotContainsString( '<', $data['duration_text'] );
		$this->assertSame( 'reading', $data['read'] );
		$this->assertSame( 'Reading:', $data['read_text'] );
	}

	public function test_filter() {
		$id       = $this->post( 'bookmark', array( 'bookmark-of' => array( 'url' => 'https://example.com/b' ) ) );
		$callback = function ( $data, $post ) {
			$data['extra'] = $post->ID;
			return $data;
		};
		add_filter( 'kind_response_data', $callback, 10, 2 );
		$data = get_post_kind_response( $id );
		remove_filter( 'kind_response_data', $callback, 10 );
		$this->assertSame( $id, $data['extra'] );
	}

	public function test_itinerary() {
		$id   = $this->post(
			'itinerary',
			array(
				'itinerary' => array(
					array(
						'type'       => array( 'h-leg' ),
						'properties' => array(
							'origin'      => array( 'SFO' ),
							'destination' => array( 'JFK' ),
						),
					),
				),
			)
		);
		$data = get_post_kind_response( $id );
		$this->assertCount( 1, $data['itinerary'] );
		$this->assertSame( 'SFO', $data['itinerary'][0]['origin'] );
		$this->assertSame( '', $data['itinerary'][0]['operator'] );
	}
}
