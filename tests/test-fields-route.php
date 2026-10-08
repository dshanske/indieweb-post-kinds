<?php

class FieldsRouteTest extends WP_UnitTestCase {
	private function get( $kind ) {
		$request = new WP_REST_Request( 'GET', '/post-kinds/1.0/fields' );
		$request->set_param( 'kind', $kind );
		return rest_do_request( $request );
	}

	public function role_statuses() {
		return array(
			'logged out'  => array( '', 401 ),
			'subscriber'  => array( 'subscriber', 403 ),
			'contributor' => array( 'contributor', 200 ),
			'author'      => array( 'author', 200 ),
		);
	}

	/**
	 * @dataProvider role_statuses
	 */
	public function test_requires_edit_posts( $role, $status ) {
		if ( $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		}
		$this->assertSame( $status, $this->get( 'reply' )->get_status() );
	}

	public function test_returns_the_kind() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$data = $this->get( 'reply' )->get_data();
		$this->assertSame( 'reply', $data->slug );
		$this->assertSame( 'in-reply-to', $data->property );
		$this->assertSame( 200, $this->get( 'all' )->get_status() );
	}

	public function test_unknown_kind_is_not_found() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$response = $this->get( 'bogus' );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'rest_post_kind_not_found', $response->get_data()['code'] );
	}
}
