<?php

/**
 * Kind_Fields: required fields (#549).
 */
class KindFieldsRequiredTest extends WP_UnitTestCase {
	/**
	 * A reply-like schema: the citation is required, and within it the URL.
	 *
	 * @return array
	 */
	private function schema() {
		return array(
			'in-reply-to' => array(
				'type'       => 'cite',
				'label'      => 'In Reply To',
				'required'   => true,
				'properties' => array(
					'url'  => array(
						'type'     => 'url',
						'label'    => 'URL',
						'required' => true,
					),
					'name' => array(
						'type'  => 'text',
						'label' => 'Name',
					),
				),
			),
			'rating'      => array(
				'type'  => 'number',
				'label' => 'Rating',
			),
		);
	}

	public function test_missing_required_fields() {
		$this->assertSame( array( 'in-reply-to' ), Kind_Fields::missing( $this->schema(), array() ) );
		$this->assertSame( array( 'in-reply-to' ), Kind_Fields::missing( $this->schema(), array( 'in-reply-to' => array() ) ) );
		$this->assertSame( array( 'in-reply-to' ), Kind_Fields::missing( $this->schema(), 'not values' ) );
		$this->assertSame( array(), Kind_Fields::missing( 'not a schema', array() ) );
	}

	public function test_nested_required_fields_are_checked_when_the_parent_has_a_value() {
		$this->assertSame( array( 'in-reply-to.url' ), Kind_Fields::missing( $this->schema(), array( 'in-reply-to' => array( 'name' => 'A Post' ) ) ) );
		$this->assertSame( array(), Kind_Fields::missing( $this->schema(), array( 'in-reply-to' => array( 'url' => 'https://example.com/post' ) ) ) );

		// Each citation in a list is checked.
		$this->assertSame(
			array( 'in-reply-to.url' ),
			Kind_Fields::missing(
				$this->schema(),
				array(
					'in-reply-to' => array(
						array( 'url' => 'https://example.com/one' ),
						array( 'name' => 'No URL' ),
					),
				)
			)
		);
	}

	public function test_optional_fields_are_never_missing() {
		$this->assertSame( array(), Kind_Fields::missing( $this->schema(), array( 'in-reply-to' => array( 'url' => 'https://example.com/post' ) ) ) );
	}

	public function test_invalid_values_count_as_missing_after_sanitizing() {
		$values = Kind_Fields::sanitize( $this->schema(), array( 'in-reply-to' => array( 'url' => 'javascript:alert(1)' ) ) );
		$this->assertSame( array( 'in-reply-to' ), Kind_Fields::missing( $this->schema(), $values ) );
	}

	public function test_required_fields_are_marked_but_not_enforced_by_the_browser() {
		$html = Kind_Fields::render( $this->schema() );
		$this->assertStringContainsString( '<fieldset id="mf2_in-reply-to" class="kind-field-cite kind-field-required">', $html );
		$this->assertStringContainsString( '<label for="mf2_in-reply-to-url" class="kind-field-required">URL', $html );
		$this->assertStringContainsString( 'type="url" value="" aria-required="true" />', $html );
		$this->assertStringContainsString( '<label for="mf2_in-reply-to-name" class="">Name', $html );
		$this->assertStringNotContainsString( ' required', str_replace( array( 'aria-required', 'kind-field-required' ), '', $html ) );
	}

	public function test_other_required_field_types_are_marked() {
		$html = Kind_Fields::render(
			array(
				'rsvp'    => array(
					'type'     => 'select',
					'label'    => 'RSVP',
					'required' => true,
					'options'  => array( 'yes' => 'Yes' ),
				),
				'summary' => array(
					'type'     => 'textarea',
					'label'    => 'Summary',
					'required' => 1,
				),
				'start'   => array(
					'type'     => 'datetime',
					'label'    => 'Start',
					'required' => true,
				),
			)
		);
		$this->assertStringContainsString( '<select name="mf2_rsvp" id="mf2_rsvp" aria-required="true">', $html );
		$this->assertStringContainsString( '<textarea name="mf2_summary" id="mf2_summary" aria-required="true">', $html );
		$this->assertStringContainsString( 'id="mf2_start-date" value="" aria-required="true" />', $html );
	}
}
