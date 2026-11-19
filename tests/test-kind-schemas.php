<?php

/**
 * The built-in kinds' field schemas (#542).
 */
class KindSchemasTest extends WP_UnitTestCase {
	/**
	 * The built-in kinds: other tests register kinds of their own, prefixed pk-.
	 *
	 * @return string[]
	 */
	private function built_in_kinds() {
		return array_values(
			array_filter(
				Kind_Taxonomy::get_kind_list(),
				function ( $kind ) {
					return 0 !== strpos( $kind, 'pk-' );
				}
			)
		);
	}

	/**
	 * Collects the problems in a schema: fields without a label, or with a type
	 * Kind_Fields has no renderer for.
	 *
	 * @param array  $schema Field definitions, keyed by property.
	 * @param string $path   Where the schema is, for messages.
	 * @return string[]
	 */
	private function problems( $schema, $path ) {
		$problems = array();
		foreach ( $schema as $key => $field ) {
			$where = $path . '.' . $key;
			if ( empty( $field['label'] ) ) {
				$problems[] = $where . ' has no label';
			}
			if ( empty( $field['type'] ) || ! Kind_Fields::supported_type( $field['type'] ) ) {
				$problems[] = $where . ' has an unsupported type: ' . ( $field['type'] ?? '(none)' );
			}
			if ( ! empty( $field['properties'] ) ) {
				$problems = array_merge( $problems, $this->problems( $field['properties'], $where ) );
			}
		}
		return $problems;
	}

	public function test_every_field_has_a_label_and_a_supported_type() {
		$problems = array();
		foreach ( $this->built_in_kinds() as $kind ) {
			$schema = Kind_Taxonomy::get_kind_info( $kind, 'properties' );
			if ( is_array( $schema ) ) {
				$problems = array_merge( $problems, $this->problems( $schema, $kind ) );
			}
		}
		$this->assertSame( array(), $problems );
	}

	public function test_shown_kinds_have_a_schema() {
		// Photo, video and audio wait for their own design (#542); a note has no fields.
		$waiting = array( 'photo', 'video', 'audio', 'note' );
		foreach ( $this->built_in_kinds() as $kind ) {
			$info = Kind_Taxonomy::get_post_kind_info( $kind );
			if ( ! $info->show || in_array( $kind, $waiting, true ) ) {
				continue;
			}
			$this->assertNotEmpty( $info->properties, $kind );
		}
	}

	public function test_citations_are_required_and_other_fields_optional() {
		foreach ( $this->built_in_kinds() as $kind ) {
			$info = Kind_Taxonomy::get_post_kind_info( $kind );
			foreach ( $info->properties as $key => $field ) {
				if ( in_array( $field['type'], array( 'cite', 'venue' ), true ) ) {
					$this->assertTrue( $field['required'], "$kind.$key" );
				} elseif ( 'rsvp' !== $key ) {
					$this->assertEmpty( $field['required'] ?? false, "$kind.$key" );
				}
			}
		}
		$this->assertTrue( Kind_Taxonomy::get_kind_info( 'rsvp', 'properties' )['rsvp']['required'] );
	}

	public function test_a_reply_needs_something_to_reply_to() {
		$schema = Kind_Taxonomy::get_kind_info( 'reply', 'properties' );
		$this->assertSame( array( 'in-reply-to' ), Kind_Fields::missing( $schema, Kind_Fields::sanitize( $schema, array() ) ) );
		$this->assertSame( array(), Kind_Fields::missing( $schema, Kind_Fields::sanitize( $schema, array( 'in-reply-to' => 'https://example.com/post' ) ) ) );
	}

	public function test_times_and_ratings_are_offered_but_optional() {
		foreach ( array( 'listen', 'watch', 'jam', 'play', 'read' ) as $kind ) {
			$schema = Kind_Taxonomy::get_kind_info( $kind, 'properties' );
			foreach ( array( 'rating', 'start', 'end', 'duration' ) as $key ) {
				$this->assertArrayHasKey( $key, $schema, "$kind.$key" );
			}
			$property = Kind_Taxonomy::get_kind_info( $kind, 'property' );
			$values   = Kind_Fields::sanitize( $schema, array( $property => 'https://example.com/thing' ) );
			$this->assertSame( array(), Kind_Fields::missing( $schema, $values ), $kind );
		}
		foreach ( array( 'review', 'eat', 'drink' ) as $kind ) {
			$this->assertArrayHasKey( 'rating', Kind_Taxonomy::get_kind_info( $kind, 'properties' ), $kind );
		}
		$this->assertArrayHasKey( 'read-status', Kind_Taxonomy::get_kind_info( 'read', 'properties' ) );
	}

	public function test_title_comes_from_a_name_field() {
		foreach ( $this->built_in_kinds() as $kind ) {
			$info = Kind_Taxonomy::get_post_kind_info( $kind );
			$this->assertSame( isset( $info->properties['name'] ), $info->title, $kind );
		}
		$this->assertTrue( Kind_Taxonomy::get_post_kind_info( 'article' )->title );
		$this->assertTrue( Kind_Taxonomy::get_post_kind_info( 'issue' )->title );
		$this->assertFalse( Kind_Taxonomy::get_post_kind_info( 'reply' )->title );
	}

	public function test_title_argument_is_deprecated_but_still_adds_a_name_field() {
		$this->setExpectedDeprecated( 'register_post_kind' );
		$kind = new Post_Kind( 'pk-legacy-title', array( 'title' => true ) );
		$this->assertTrue( $kind->title );
		$this->assertSame( Kind_Fields::title_field(), $kind->properties['name'] );
		$this->assertNull( $kind->__get( 'title' ) );
	}

	public function test_title_argument_false_adds_nothing() {
		$this->setExpectedDeprecated( 'register_post_kind' );
		$kind = new Post_Kind( 'pk-legacy-untitled', array( 'title' => false ) );
		$this->assertFalse( $kind->title );
		$this->assertSame( array(), $kind->properties );
	}

	public function test_cite_properties_overrides() {
		$properties = Kind_Fields::cite_properties(
			array(
				'updated'  => false,
				'author'   => array( 'label' => 'Artist' ),
				'location' => array(
					'type'  => 'text',
					'label' => 'Location',
				),
			)
		);
		$this->assertArrayNotHasKey( 'updated', $properties );
		$this->assertSame(
			array(
				'type'  => 'author',
				'label' => 'Artist',
			),
			$properties['author']
		);
		$this->assertSame( 'location', array_key_last( $properties ) );

		$field = Kind_Fields::cite_field( 'Optional', array(), false );
		$this->assertSame( 'cite', $field['type'] );
		$this->assertFalse( $field['required'] );
	}

	public function test_checkin_is_a_place_reference() {
		$schema = Kind_Taxonomy::get_kind_info( 'checkin', 'properties' );
		$this->assertSame( array( 'checkin' ), array_keys( $schema ) );
		$this->assertSame( 'venue', $schema['checkin']['type'] );
		$this->assertTrue( $schema['checkin']['required'] );
		$this->assertSame( array( 'name', 'url' ), array_keys( $schema['checkin']['properties'] ) );

		$this->assertSame( array( 'checkin' ), Kind_Fields::missing( $schema, Kind_Fields::sanitize( $schema, array() ) ) );
		$this->assertSame(
			array(
				'checkin' => array(
					'name' => 'City Hall',
					'url'  => 'https://example.com/venue/city-hall',
				),
			),
			Kind_Fields::sanitize(
				$schema,
				array(
					'checkin' => array(
						'type'       => array( 'h-card' ),
						'properties' => array(
							'name'      => array( 'City Hall' ),
							'url'       => array( 'https://example.com/venue/city-hall' ),
							'latitude'  => array( '40.7' ),
							'longitude' => array( '-74.0' ),
						),
					),
				)
			)
		);
	}
}
