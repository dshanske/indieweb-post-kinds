<?php

class DeprecationsTest extends WP_UnitTestCase {
	/**
	 * Runs a callback and returns the deprecation notices it raises.
	 */
	private function deprecations( $callback ) {
		$notices = array();
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			function ( $errno, $message ) use ( &$notices ) {
				$notices[] = $message;
				return true;
			},
			E_DEPRECATED | E_USER_DEPRECATED
		);
		try {
			$callback();
		} finally {
			restore_error_handler();
		}
		return $notices;
	}

	public function test_custom_kind_arguments_without_dynamic_properties() {
		$kind    = null;
		$notices = $this->deprecations(
			function () use ( &$kind ) {
				$kind = new Post_Kind(
					'pk-custom',
					array(
						'singular_name' => 'Custom',
						'custom_flag'   => 'yes',
					)
				);
				$kind->another = 'set later';
			}
		);
		$this->assertSame( array(), $notices );
		$this->assertSame( 'yes', $kind->custom_flag );
		$this->assertTrue( isset( $kind->custom_flag ) );
		$this->assertSame( 'set later', $kind->another );
		$this->assertNull( $kind->missing );
		$this->assertTrue( $kind->has( 'custom_flag' ) );
		$this->assertTrue( $kind->has( 'singular_name' ) );
		$this->assertFalse( $kind->has( 'missing' ) );
		$this->assertFalse( $kind->has( 'extra' ) );

		$json = json_decode( wp_json_encode( $kind ), true );
		$this->assertSame( 'yes', $json['custom_flag'] );
		$this->assertSame( 'Custom', $json['singular_name'] );
		$this->assertArrayNotHasKey( 'extra', $json );
	}

	public function test_get_kind_info_reads_custom_arguments() {
		register_post_kind(
			'pk-custom-info',
			array(
				'singular_name' => 'Custom',
				'custom_flag'   => 'yes',
			)
		);
		$this->assertSame( 'yes', Kind_Taxonomy::get_kind_info( 'pk-custom-info', 'custom_flag' ) );
		$this->assertSame( 'Custom', Kind_Taxonomy::get_kind_info( 'pk-custom-info', 'singular_name' ) );
		$this->assertFalse( Kind_Taxonomy::get_kind_info( 'pk-custom-info', 'missing' ) );
	}

	public function test_show_defaults_to_false() {
		$this->assertFalse( ( new Post_Kind( 'pk-hidden' ) )->show );
	}

	public function test_mf2_post_array_access_methods_declare_return_type_will_change() {
		if ( PHP_VERSION_ID < 80000 ) {
			$this->markTestSkipped( 'Attributes are read with reflection on PHP 8.' );
		}
		foreach ( array( 'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset' ) as $method ) {
			$attributes = ( new ReflectionMethod( 'MF2_Post', $method ) )->getAttributes( 'ReturnTypeWillChange' );
			$this->assertCount( 1, $attributes, $method );
		}
	}

	public function test_mf2_post_reads_attachment_metadata() {
		$this->setExpectedDeprecated( 'MF2_Post::__construct' );
		$attachment = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
		update_post_meta( $attachment, 'mf2_published', array( '2026-01-01T10:00:00+00:00' ) );
		update_post_meta( $attachment, 'mf2_publication', array( 'An Album' ) );
		$mf2 = new MF2_Post( $attachment );
		$this->assertSame( '2026-01-01T10:00:00+00:00', $mf2->get_published() );
		$this->assertSame( 'An Album', $mf2->get_publication() );
	}

	public function test_kind_post_author_photo_is_the_authors_avatar() {
		$user   = self::factory()->user->create();
		$post   = self::factory()->post->create( array( 'post_author' => $user ) );
		$author = ( new Kind_Post( $post ) )->get_author();
		$this->assertSame( get_avatar_url( $user ), $author['properties']['photo'][0] );
	}

	public function test_get_formatted_without_a_field() {
		$this->assertSame( '', Kind_View::get_formatted( null, '' ) );
	}

	public function test_get_embed_without_wp_embed() {
		update_option( 'kind_embeds', 1 );
		$saved = $GLOBALS['wp_embed'];
		unset( $GLOBALS['wp_embed'] );
		try {
			$this->assertSame( '', Kind_View::get_embed( 'https://youtube.com/watch?v=abc' ) );
		} finally {
			$GLOBALS['wp_embed'] = $saved;
		}
	}

	public function test_mf2_post_is_deprecated() {
		$this->setExpectedDeprecated( 'MF2_Post::__construct' );
		new MF2_Post( self::factory()->post->create() );
	}
}
