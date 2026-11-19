<?php

/**
 * The built-in kinds' field schemas (#542).
 */
class KindSchemasTest extends WP_UnitTestCase {
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
		foreach ( Kind_Taxonomy::get_kind_list() as $kind ) {
			$schema = Kind_Taxonomy::get_kind_info( $kind, 'properties' );
			if ( is_array( $schema ) ) {
				$problems = array_merge( $problems, $this->problems( $schema, $kind ) );
			}
		}
		$this->assertSame( array(), $problems );
	}
}
