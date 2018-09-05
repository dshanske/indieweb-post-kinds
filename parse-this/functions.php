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

function mf2_to_jf2( $entry ) {
	if ( wp_is_numeric_array( $entry ) || ! isset( $entry['properties'] ) ) {
		return false;
	}
	$jf2         = array();
	$jf2['type'] = str_replace( 'h-', '', $entry['type'] );
	if ( isset( $entry['properties'] ) ) {
		foreach ( $entry['properties'] as $key => $value ) {
			if ( 1 === count( $value ) ) {
				$jf2[ $key ] = array_pop( $value );
			} elseif ( wp_is_numeric_array( $value ) ) {
				$jf2[ $key ] = $value;
			} elseif ( isset( $value['type'] ) ) {
				$jf2[ $key ] = mf2_to_jf2( $value );
			} else {
				$jf2[ $key ] = $value;
			}
		}
	} elseif ( isset( $entry['items'] ) ) {
		$jf2['children'] = array();
		foreach ( $entry['items'] as $item ) {
			$jf2['children'][] = mf2_to_jf2( $item );
		}
	}
	return $jf2;
}

