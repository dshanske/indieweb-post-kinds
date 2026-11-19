<?php

/**
 * Kind_Fields: rebuilding form data and the shared sanitize() (#539).
 */
class KindFieldsDataTest extends WP_UnitTestCase {
	/**
	 * A schema with one field of each type.
	 *
	 * @return array
	 */
	private function schema() {
		return array(
			'name'     => array(
				'type'  => 'text',
				'label' => 'Name',
			),
			'summary'  => array(
				'type'  => 'textarea',
				'label' => 'Summary',
			),
			'url'      => array(
				'type'  => 'url',
				'label' => 'URL',
			),
			'rating'   => array(
				'type'  => 'number',
				'label' => 'Rating',
			),
			'rsvp'     => array(
				'type'    => 'select',
				'label'   => 'RSVP',
				'options' => array(
					'yes' => 'Yes',
					'no'  => 'No',
				),
			),
			'category' => array(
				'type'  => 'list',
				'label' => 'Tags',
			),
			'start'    => array(
				'type'  => 'datetime',
				'label' => 'Start',
			),
			'duration' => array(
				'type'  => 'duration',
				'label' => 'Duration',
			),
			'author'   => array(
				'type'  => 'author',
				'label' => 'Author',
			),
			'cite'     => array(
				'type'       => 'cite',
				'label'      => 'Cite',
				'properties' => array(
					'name' => array(
						'type'  => 'text',
						'label' => 'Name',
					),
					'url'  => array(
						'type'  => 'url',
						'label' => 'URL',
					),
				),
			),
		);
	}

	public function test_rebuild_data_reads_mf2_fields_and_reassembles_parts() {
		$data = array(
			'mf2_rsvp'        => 'yes',
			'mf2_start'       => array(
				'date'   => '2024-05-06',
				'time'   => '18:00',
				'offset' => '-04:00',
			),
			'mf2_end_date'    => '2024-05-06',
			'mf2_end_time'    => '21:00:00',
			'mf2_end_offset'  => '+00:00',
			'mf2_duration'    => array(
				'H' => '1',
				'I' => '30',
				'S' => '',
			),
			'mf2_in-reply-to' => array(
				'name'      => ' A Post ',
				'published' => array(
					'date'   => '',
					'time'   => '',
					'offset' => '+00:00',
				),
				'empty'     => '',
			),
			'cite_url'        => 'https://example.com/not-mf2',
			0                 => 'numeric key',
		);
		$this->assertSame(
			array(
				'rsvp'        => 'yes',
				'start'       => '2024-05-06T18:00:00-04:00',
				'end'         => '2024-05-06T21:00:00+00:00',
				'duration'    => 'PT1H30M',
				'in-reply-to' => array( 'name' => 'A Post' ),
			),
			Kind_Fields::rebuild_data( $data )
		);
	}

	public function test_rebuild_data_with_a_schema_sanitizes() {
		$data = array(
			'mf2_name'   => 'Name <b>bold</b>',
			'mf2_url'    => 'javascript:alert(1)',
			'mf2_bogus'  => 'Not in the schema',
			'mf2_rating' => '4',
		);
		$this->assertSame(
			array(
				'name'   => 'Name bold',
				'rating' => 4,
			),
			Kind_Fields::rebuild_data( $data, $this->schema() )
		);
	}

	public function test_render_and_rebuild_round_trip() {
		$schema = Kind_Taxonomy::get_kind_info( 'reply', 'properties' );
		$values = array(
			'in-reply-to' => array(
				'name'      => 'A Post',
				'url'       => 'https://example.com/post',
				'author'    => array(
					'name' => 'Ann',
					'url'  => 'https://example.com/ann',
				),
				'published' => '2024-01-02T03:04:05+00:00',
				'category'  => array( 'one', 'two' ),
			),
		);

		// Submit the rendered form as a browser would.
		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?><form>' . Kind_Fields::render( $schema, $values ) . '</form>' );
		libxml_clear_errors();
		$query = array();
		foreach ( $doc->getElementsByTagName( 'input' ) as $input ) {
			$query[] = rawurlencode( $input->getAttribute( 'name' ) ) . '=' . rawurlencode( $input->getAttribute( 'value' ) );
		}
		foreach ( $doc->getElementsByTagName( 'textarea' ) as $textarea ) {
			$query[] = rawurlencode( $textarea->getAttribute( 'name' ) ) . '=' . rawurlencode( $textarea->textContent );
		}
		foreach ( $doc->getElementsByTagName( 'select' ) as $select ) {
			foreach ( $select->getElementsByTagName( 'option' ) as $option ) {
				if ( $option->hasAttribute( 'selected' ) ) {
					$query[] = rawurlencode( $select->getAttribute( 'name' ) ) . '=' . rawurlencode( $option->getAttribute( 'value' ) );
				}
			}
		}
		parse_str( implode( '&', $query ), $posted );

		$this->assertSame( $values, Kind_Fields::rebuild_data( $posted, $schema ) );
	}

	public function test_sanitize_by_type() {
		$this->assertSame(
			array(
				'name'     => 'A name',
				'summary'  => "Line one\nLine two",
				'url'      => 'https://example.com/a?x=1&y=2',
				'rating'   => 3.5,
				'rsvp'     => 'no',
				'category' => array( 'one', 'two', 'three' ),
				'start'    => '2024-05-06T18:00:00-04:00',
				'duration' => 'PT1H30M',
				'author'   => array(
					'name' => 'Ann',
					'url'  => 'https://example.com/ann',
				),
				'cite'     => array( 'url' => 'https://example.com/cited' ),
			),
			Kind_Fields::sanitize(
				$this->schema(),
				array(
					'name'     => '<b>A name</b>',
					'summary'  => array(
						'html'  => '<p>Line one</p>',
						'value' => "Line one\nLine <i>two</i>",
					),
					'url'      => ' https://example.com/a?x=1&y=2 ',
					'rating'   => '3.5',
					'rsvp'     => array( 'no' ),
					'category' => 'one; two;;three',
					'start'    => '2024-05-06T18:00:00-04:00',
					'duration' => array(
						'H' => 1,
						'I' => '30',
					),
					'author'   => array(
						'type'       => array( 'h-card' ),
						'properties' => array(
							'name' => array( 'Ann' ),
							'url'  => array( 'https://example.com/ann' ),
						),
					),
					'cite'     => 'https://example.com/cited',
					'bogus'    => 'dropped',
				)
			)
		);
	}

	public function test_sanitize_drops_invalid_values() {
		$this->assertSame(
			array(),
			Kind_Fields::sanitize(
				$this->schema(),
				array(
					'name'     => '   ',
					'summary'  => array( 'not' => 'text' ),
					'url'      => 'javascript:alert(1)',
					'rating'   => 'four',
					'rsvp'     => 'perhaps',
					'category' => ';;',
					'start'    => 'not a date',
					'duration' => 'PT0S',
					'author'   => array( 'url' => 'javascript:alert(1)' ),
					'cite'     => array( 'bogus' => 'x' ),
				)
			)
		);
		$this->assertSame( array(), Kind_Fields::sanitize( 'not a schema', array() ) );
		$this->assertSame( array(), Kind_Fields::sanitize( $this->schema(), 'not values' ) );
	}

	public function test_sanitize_lists_of_values() {
		$this->assertSame(
			array(
				'url'    => array( 'https://example.com/one', 'https://example.com/two' ),
				'author' => array(
					array( 'name' => 'Ann' ),
					array( 'name' => 'Bob' ),
				),
			),
			Kind_Fields::sanitize(
				$this->schema(),
				array(
					'url'    => array( 'https://example.com/one', 'javascript:alert(1)', 'https://example.com/two' ),
					'author' => array( 'Ann', 'Bob' ),
				)
			)
		);
	}

	public function test_sanitize_dates_from_parts() {
		$schema = array(
			'start' => array(
				'type'  => 'datetime',
				'label' => 'Start',
			),
		);
		$this->assertSame( array( 'start' => '2024-05-06T00:00:00-04:00' ), Kind_Fields::sanitize( $schema, array( 'start' => array( 'date' => '2024-05-06', 'offset' => '-04:00' ) ) ) );
		// A time without a date isn't a date.
		$this->assertSame( array(), Kind_Fields::sanitize( $schema, array( 'start' => array( 'time' => '18:00' ) ) ) );
	}

	public function test_sanitize_citation_given_as_a_name() {
		$schema = Kind_Taxonomy::get_kind_info( 'like', 'properties' );
		$this->assertSame( array( 'like-of' => array( 'name' => 'Just a name' ) ), Kind_Fields::sanitize( $schema, array( 'like-of' => 'Just a name' ) ) );
	}
}
