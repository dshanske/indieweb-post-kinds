<?php

/**
 * The kind REST field's schema (#503).
 */
class RestKindFieldTest extends WP_UnitTestCase {
	public function test_schema_describes_the_field() {
		$data  = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' ) )->get_data();
		$field = $data['schema']['properties']['kind'];
		$this->assertNotEmpty( $field['description'] );
		$this->assertSame( Kind_Taxonomy::get_kind_list(), $field['enum'] );
		$this->assertSame( array( 'view', 'edit' ), $field['context'] );
	}

	public function test_unknown_kind_fails_argument_validation() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$post    = self::factory()->post->create();
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $post );
		$request->set_body_params( array( 'kind' => 'no-such-kind' ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	public function test_post_without_a_kind_reads_false() {
		$post = self::factory()->post->create();
		wp_set_object_terms( $post, array(), 'kind' );
		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post ) );
		$this->assertFalse( $response->get_data()['kind'] );
	}
}
