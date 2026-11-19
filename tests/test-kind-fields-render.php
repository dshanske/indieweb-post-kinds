<?php

/**
 * Kind_Fields: field definitions and rendering (#538).
 */
class KindFieldsRenderTest extends WP_UnitTestCase {
	const XSS = '"><script>alert(1)</script>';

	public function test_supported_types_are_the_field_renderers() {
		foreach ( array( 'text', 'textarea', 'url', 'number', 'select', 'list', 'datetime', 'duration', 'author', 'cite', 'venue' ) as $type ) {
			$this->assertTrue( Kind_Fields::supported_type( $type ), $type );
		}
		foreach ( array( 'coordinate', 'section', 'name', '', 'Text', 'text()', null ) as $type ) {
			$this->assertFalse( Kind_Fields::supported_type( $type ), (string) $type );
		}
	}

	public function test_fields_are_named_by_property() {
		$html = Kind_Fields::render(
			array(
				'rating' => array(
					'type'  => 'number',
					'label' => 'Rating',
				),
			),
			array( 'rating' => 4 )
		);
		$this->assertStringContainsString( '<label for="mf2_rating" class="">Rating', $html );
		$this->assertStringContainsString( '<input name="mf2_rating" id="mf2_rating" type="number" value="4" step="1" />', $html );
	}

	public function test_invalid_fields_are_skipped_without_dropping_the_rest() {
		$html = Kind_Fields::render(
			array(
				'first'  => array( 'label' => 'No type' ),
				'second' => 'not an array',
				'third'  => array(
					'type'  => 'select',
					'label' => 'No options',
				),
				'fourth' => array(
					'type'  => 'cite',
					'label' => 'No properties',
				),
				'last'   => array(
					'type'  => 'text',
					'label' => 'Last',
				),
			)
		);
		$this->assertStringContainsString( 'name="mf2_last"', $html );
		$this->assertStringNotContainsString( 'mf2_first', $html );
		$this->assertStringNotContainsString( 'mf2_third', $html );
		$this->assertStringNotContainsString( 'mf2_fourth', $html );
	}

	public function test_unsupported_types_are_text_and_labels_default_to_the_property() {
		$html = Kind_Fields::render(
			array(
				'location' => array( 'type' => 'coordinate' ),
			)
		);
		$this->assertStringContainsString( '<label for="mf2_location" class="">location', $html );
		$this->assertStringContainsString( 'type="text"', $html );
	}

	public function test_select() {
		$html = Kind_Fields::render(
			array(
				'rsvp' => array(
					'type'    => 'select',
					'label'   => 'RSVP',
					'options' => array(
						'yes' => 'Yes',
						'no'  => 'No',
					),
				),
			),
			array( 'rsvp' => array( 'no' ) )
		);
		$this->assertStringContainsString( '<select name="mf2_rsvp" id="mf2_rsvp">', $html );
		$this->assertStringContainsString( '<option value=""></option>', $html );
		$this->assertMatchesRegularExpression( '/<option value="no"\s+selected/', $html );
		$this->assertDoesNotMatchRegularExpression( '/<option value="yes"\s+selected/', $html );
	}

	public function test_textarea_and_list() {
		$html = Kind_Fields::render(
			array(
				'summary'  => array(
					'type'  => 'textarea',
					'label' => 'Summary',
				),
				'category' => array(
					'type'  => 'list',
					'label' => 'Tags',
				),
			),
			array(
				'summary'  => array(
					'html'  => '<p>Hi</p>',
					'value' => 'Hi',
				),
				'category' => array( 'one', 'two' ),
			)
		);
		$this->assertStringContainsString( '<textarea name="mf2_summary" id="mf2_summary">Hi</textarea>', $html );
		$this->assertStringContainsString( '<textarea name="mf2_category" id="mf2_category">one;two</textarea>', $html );
	}

	public function test_datetime_parts_and_offset() {
		$html = Kind_Fields::render(
			array(
				'start' => array(
					'type'  => 'datetime',
					'label' => 'Start',
				),
			),
			array( 'start' => '2024-05-06T18:00:00-04:00' )
		);
		$this->assertStringContainsString( '<label for="mf2_start-date" class="">Start', $html );
		$this->assertStringContainsString( '<input type="date" name="mf2_start[date]" id="mf2_start-date" value="2024-05-06" />', $html );
		$this->assertStringContainsString( '<input type="time" name="mf2_start[time]" id="mf2_start-time" step="1" value="18:00:00" />', $html );
		$this->assertStringContainsString( '<select name="mf2_start[offset]" id="mf2_start-offset">', $html );
		$this->assertMatchesRegularExpression( '/<option value="-04:00"\s+selected/', $html );
	}

	public function test_empty_datetime_is_not_now() {
		$html = Kind_Fields::render(
			array(
				'end' => array(
					'type'  => 'datetime',
					'label' => 'End',
				),
			)
		);
		$this->assertStringContainsString( 'name="mf2_end[date]" id="mf2_end-date" value=""', $html );
		$this->assertStringContainsString( 'name="mf2_end[time]" id="mf2_end-time" step="1" value=""', $html );
	}

	public function test_duration_shows_each_piece() {
		$html = Kind_Fields::render(
			array(
				'duration' => array(
					'type'   => 'duration',
					'label'  => 'Duration',
					'pieces' => array( 'D', 'H', 'I', 'X' ),
				),
			),
			array( 'duration' => 'P2DT1H30M' )
		);
		$this->assertStringContainsString( 'name="mf2_duration[D]" id="mf2_duration-D" value="2"', $html );
		$this->assertStringContainsString( 'name="mf2_duration[H]" id="mf2_duration-H" value="1"', $html );
		$this->assertStringContainsString( 'name="mf2_duration[I]" id="mf2_duration-I" value="30"', $html );
		$this->assertStringNotContainsString( '[X]', $html );
		$this->assertStringNotContainsString( '[S]', $html );
	}

	public function test_duration_defaults_to_hours_minutes_and_seconds() {
		$html = Kind_Fields::render(
			array(
				'duration' => array(
					'type'  => 'duration',
					'label' => 'Duration',
				),
			)
		);
		$this->assertSame( 3, substr_count( $html, 'type="number"' ) );
		$this->assertStringContainsString( 'name="mf2_duration[S]" id="mf2_duration-S" value=""', $html );
	}

	public function test_nested_fields_have_nested_names() {
		$schema = Kind_Taxonomy::get_kind_info( 'reply', 'properties' );
		$html   = Kind_Fields::render(
			$schema,
			array(
				'in-reply-to' => array(
					'type'       => array( 'h-cite' ),
					'properties' => array(
						'name'   => array( 'A Post' ),
						'author' => array(
							array(
								'type'       => array( 'h-card' ),
								'properties' => array( 'name' => array( 'Ann' ) ),
							),
						),
					),
				),
			)
		);
		$this->assertStringContainsString( '<fieldset id="mf2_in-reply-to" class="kind-field-cite"><legend>In Reply To</legend>', $html );
		$this->assertStringContainsString( 'name="mf2_in-reply-to[name]" id="mf2_in-reply-to-name" type="text" value="A Post"', $html );
		$this->assertStringContainsString( 'name="mf2_in-reply-to[author][name]" id="mf2_in-reply-to-author-name" type="text" value="Ann"', $html );
		$this->assertStringContainsString( 'name="mf2_in-reply-to[published][date]"', $html );
		$this->assertStringNotContainsString( 'name="mf2_name"', $html );
	}

	public function test_citation_given_as_a_url() {
		$html = Kind_Fields::render( Kind_Taxonomy::get_kind_info( 'like', 'properties' ), array( 'like-of' => 'https://example.com/liked' ) );
		$this->assertStringContainsString( 'name="mf2_like-of[url]" id="mf2_like-of-url" type="url" value="https://example.com/liked"', $html );
	}

	public function test_output_is_escaped() {
		$html = Kind_Fields::render(
			array(
				'text'   => array(
					'type'  => 'text',
					'label' => 'Label' . self::XSS,
					'class' => 'c' . self::XSS,
				),
				'note'   => array(
					'type'  => 'textarea',
					'label' => 'Note',
				),
				'choice' => array(
					'type'    => 'select',
					'label'   => 'Choice',
					'options' => array( 'v' . self::XSS => 'Option' . self::XSS ),
				),
				'cite'   => array(
					'type'       => 'cite',
					'label'      => 'Cite' . self::XSS,
					'properties' => array(
						'url' => array(
							'type'  => 'url',
							'label' => 'URL',
						),
					),
				),
			),
			array(
				'text' => 'Value' . self::XSS,
				'note' => '</textarea>' . self::XSS,
				'cite' => array( 'url' => 'https://example.com/' . self::XSS ),
			)
		);
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertSame( 1, substr_count( $html, '</textarea>' ) );
	}

	public function test_get_offset_list() {
		$offsets = Kind_Fields::get_offset_list();
		$this->assertContains( '+00:00', $offsets );
		$this->assertContains( '-05:00', $offsets );
	}
}
