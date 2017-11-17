<?php

class MF2PostTest extends WP_UnitTestCase {
	public function test_set_and_get_single_property() {
		$post = self::factory()->post->create();
		$mf2_post = new MF2_Post( $post );
		$mf2_post->set( 'like-of', 'http://www.example.com' );
		$mf2_post = new MF2_Post( $post );
		$this->assertEquals( 'http://www.example.com', $mf2_post->get( 'like-of', true ) );
	}
	public function test_set_and_get_array_property() {
		$post = self::factory()->post->create();
		$mf2_post = new MF2_Post( $post );
		$mf2_post->set( 'like-of', array( 'http://www.example.com', 'http://www.example2.com' ) );
		$mf2_post = new MF2_Post( $post );
		$this->assertEquals( array( 'http://www.example.com', 'http://www.example2.com' ), $mf2_post->get( 'like-of' ) );
	}
	public function test_set_and_get_field() {
		$post = self::factory()->post->create();
		$mf2_post = new MF2_Post( $post );
		$mf2_post->set( 'post_author', 4 );
		$mf2_post = new MF2_Post( $post );
		$this->assertEquals( 4, $mf2_post->get( 'post_author', true ) );
	}
	public function test_set_and_get_published() {
		$post = self::factory()->post->create();
		$mf2_post = new MF2_Post( $post );
		$mf2_post->set( 'published', '2016-01-01T04:01:23-08:00' );
		$mf2_post = new MF2_Post( $post );
		$this->assertEquals( '2016-01-01T12:01:23+00:00', $mf2_post->get( 'published', true ) );
	}
	public function test_set_and_get_multi_array() {
		$post = self::factory()->post->create();
		$mf2_post = new MF2_Post( $post );
		$mf2_post->set( array(
			'checkin' => 'Blah',
			'in_reply_to' => 'Nothing'
		) );
		$mf2_post = new MF2_Post( $post );
		$this->assertEquals( 'Blah', $mf2_post->get( 'checkin', true ) );
	}

}

