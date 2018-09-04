<?php
// Parse This Global Functions

function jf2_to_mf2( $entry ) {
	if ( ! $entry || ! is_array( $entry ) | isset( $entry['properties'] ) ) {
		return $entry;
	}
	$return               = array();
	$return['type']       = array( 'h-' . $entry['type'] );
	$return['properties'] = array();
	unset( $entry['type'] );
	foreach ( $entry as $key => $value ) {
		// Exclude  values
		if ( empty( $value ) || ( '_raw' === $key ) ) {
			continue;
		}
		if ( ! wp_is_numeric_array( $value ) && is_array( $value ) && array_key_exists( 'type', $value ) ) {
			$value = jf2_to_mf2( $value );
		} elseif ( wp_is_numeric_array( $value ) && is_array( $value[0] ) && array_key_exists( 'type', $value[0] ) ) {
			foreach ( $value as $item ) {
				$items[] = jf2_to_mf2( $item );
			}
			$value = $items;
		} elseif ( ! wp_is_numeric_array( $value ) ) {
			$value = array( $value );
		} else {
			continue;
		}
		$return['properties'][ $key ] = $value;
	}
	return $return;
}
