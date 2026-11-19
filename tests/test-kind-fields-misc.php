<?php

/**
 * Kind_Fields: the time zone list and less common definitions (#539).
 */
class KindFieldsMiscTest extends WP_UnitTestCase {
	public function test_timezone_list_has_only_known_time_zones() {
		$zones = Kind_Fields::timezone_list();
		$this->assertGreaterThan( 200, count( $zones ) );
		foreach ( array_keys( $zones ) as $zone ) {
			$this->assertInstanceOf( 'DateTimeZone', new DateTimeZone( $zone ), $zone );
		}
		$this->assertSame( '(GMT+05:30) India Standard Time', $zones['Asia/Kolkata'] );
		$this->assertArrayNotHasKey( 'Asia/Calcutta', $zones );
	}

	public function test_venue_field_and_class_lists() {
		$html = Kind_Fields::render(
			array(
				'checkin' => array(
					'type'       => 'venue',
					'label'      => 'Checked In',
					'class'      => array( 'one', 'two' ),
					'properties' => array(
						'name'     => array(
							'type'  => 'text',
							'label' => 'Name',
						),
						'latitude' => array(
							'type'  => 'number',
							'label' => 'Latitude',
							'step'  => 0.0000001,
						),
					),
				),
			),
			array(
				'checkin' => array(
					'name'     => 'A Cafe',
					'latitude' => 40.5,
				),
			)
		);
		$this->assertStringContainsString( '<fieldset id="mf2_checkin" class="kind-field-venue one two"><legend>Checked In</legend>', $html );
		$this->assertStringContainsString( 'name="mf2_checkin[name]" id="mf2_checkin-name" type="text" value="A Cafe"', $html );
		$this->assertStringContainsString( 'name="mf2_checkin[latitude]" id="mf2_checkin-latitude" type="number" value="40.5"', $html );
	}

	public function test_duration_with_no_valid_pieces_uses_the_default() {
		$html = Kind_Fields::render(
			array(
				'duration' => array(
					'type'   => 'duration',
					'label'  => 'Duration',
					'pieces' => array( 'X' ),
				),
			)
		);
		$this->assertStringContainsString( 'name="mf2_duration[H]"', $html );
		$this->assertStringContainsString( 'name="mf2_duration[S]"', $html );
	}

	public function test_rebuild_data_merges_both_forms_of_parts() {
		$this->assertSame(
			array( 'start' => '2024-05-06T18:00:00+00:00' ),
			Kind_Fields::rebuild_data(
				array(
					'mf2_start_date' => '2024-05-06',
					'mf2_start'      => array(
						'time'   => '18:00:00',
						'offset' => '+00:00',
					),
				)
			)
		);
	}

	public function test_sanitize_labels_and_skips_invalid_definitions() {
		$this->assertSame(
			array( 'note' => 'Kept' ),
			Kind_Fields::sanitize(
				array(
					'note'   => array( 'type' => 'text' ),
					'broken' => array( 'type' => 'select' ),
					'string' => 'not a definition',
				),
				array(
					'note'   => 'Kept',
					'broken' => 'x',
					'string' => 'y',
				)
			)
		);
	}

	public function test_sanitize_a_list_of_only_invalid_values() {
		$schema = array(
			'url' => array(
				'type'  => 'url',
				'label' => 'URL',
			),
		);
		$this->assertSame( array(), Kind_Fields::sanitize( $schema, array( 'url' => array( 'javascript:alert(1)', '' ) ) ) );
	}

	public function test_sanitize_zero_duration_is_dropped() {
		$schema = array(
			'duration' => array(
				'type'  => 'duration',
				'label' => 'Duration',
			),
		);
		$this->assertSame( array(), Kind_Fields::sanitize( $schema, array( 'duration' => new DateInterval( 'PT0S' ) ) ) );
		$this->assertSame( array( 'duration' => 'PT45S' ), Kind_Fields::sanitize( $schema, array( 'duration' => 'PT45S' ) ) );
	}
}
