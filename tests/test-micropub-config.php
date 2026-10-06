<?php

class MicropubConfigTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		update_option( 'kind_termslist', array( 'article', 'reply', 'like' ) );
	}

	public function test_config_lists_enabled_kinds_and_note() {
		$response = Kind_Plugins::micropub_query( array( 'q' => array( 'config' ) ), array( 'q' => 'config' ) );
		$this->assertSame(
			array(
				array(
					'type' => 'note',
					'name' => Kind_Taxonomy::get_kind_info( 'note', 'singular_name' ),
				),
				array(
					'type' => 'article',
					'name' => Kind_Taxonomy::get_kind_info( 'article', 'singular_name' ),
				),
				array(
					'type' => 'reply',
					'name' => Kind_Taxonomy::get_kind_info( 'reply', 'singular_name' ),
				),
				array(
					'type' => 'like',
					'name' => Kind_Taxonomy::get_kind_info( 'like', 'singular_name' ),
				),
			),
			$response['post-types']
		);
		// The rest of the response is kept.
		$this->assertSame( array( 'config' ), $response['q'] );
	}

	public function test_post_types_from_other_plugins_are_kept() {
		$other    = array(
			'type' => 'like',
			'name' => 'Favourite',
		);
		$response = Kind_Plugins::micropub_query( array( 'post-types' => array( $other ) ), array( 'q' => 'config' ) );
		$this->assertSame( $other, $response['post-types'][0] );
		$this->assertCount( 1, wp_list_filter( $response['post-types'], array( 'type' => 'like' ) ) );
		$this->assertCount( 4, $response['post-types'] );
	}

	public function test_other_queries_are_untouched() {
		$response = array( 'syndicate-to' => array() );
		$this->assertSame( $response, Kind_Plugins::micropub_query( $response, array( 'q' => 'syndicate-to' ) ) );
		$error = new WP_Error( 'invalid_request', 'unknown query' );
		$this->assertSame( $error, Kind_Plugins::micropub_query( $error, array( 'q' => 'config' ) ) );
	}

	public function test_post_types_are_filterable() {
		$only_notes = function () {
			return array(
				array(
					'type' => 'note',
					'name' => 'Note',
				),
			);
		};
		add_filter( 'post_kinds_micropub_post_types', $only_notes );
		$response = Kind_Plugins::micropub_query( array(), array( 'q' => 'config' ) );
		remove_filter( 'post_kinds_micropub_post_types', $only_notes );
		$this->assertSame( array( 'note' ), wp_list_pluck( $response['post-types'], 'type' ) );
	}
}
