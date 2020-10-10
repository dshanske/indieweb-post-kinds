<?php

class KindPostTest extends WP_UnitTestCase {
	public function test_set_and_get_single_property() {
		$post = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );
		$kind_post->set( 'like-of', 'http://www.example.com' );
		$kind_post = new Kind_Post( $post );
		$this->assertEquals( 'http://www.example.com', $kind_post->get( 'like-of', true ) );
	}
	public function test_set_and_get_array_property() {
		$post = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );
		$kind_post->set( 'like-of', array( 'http://www.example.com', 'http://www.example2.com' ) );
		$kind_post = new Kind_Post( $post );
		$this->assertEquals( array( 'http://www.example.com', 'http://www.example2.com' ), $kind_post->get( 'like-of' ) );
	}

	public function test_set_and_get_cite_reply() {
		$post = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );
		$cite = array(
				'type' => array( 'h-cite' ),
				'properties' => array( 
						'url' => array( 'http://www.example.com' ),
						'name' => array( 'Example Post' ),
				)
			);
		$kind_post->set( 'like-of', $cite );
		$kind_post = new Kind_Post( $post );
		$this->assertEquals( $cite, $kind_post->get( 'like-of', false ) );
	}

	public function test_set_and_get_nested_cite_reply() {
		$post = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );
		$cite = array(
				'type' => array( 'h-cite' ),
				'properties' => array( 
						'url' => array( 'http://www.example.com' ),
						'name' => array( 'Example Post' ),
						'author' => array(
							'type' => array( 'h-card' ),
							'properties' => array(
								'url' => array( 'https://www.example.com/author/doe' ),
								'name' => array( 'John Doe' ),
								'photo' => array( 'https://www.example.com/author/doe/photo.jpg' )
							)
						)
				)
			);
		$kind_post->set( 'like-of', $cite );
		$kind_post = new Kind_Post( $post );
		$this->assertEquals( $cite, $kind_post->get( 'like-of', false ) );
	}

	public function test_set_and_get_published() {
		$post = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );
		$datetime = new DateTime( '2016-01-01T04:01:23-08:00' );
		$kind_post->set( 'published', $datetime );
		$kind_post = new kind_Post( $post );
		$this->assertEquals( $datetime, $kind_post->get( 'published', true ) );
	}
	public function test_set_and_get_multi_array() {
		$post = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );
		$kind_post->set( array(
			'checkin' => 'Blah',
			'in_reply_to' => 'Nothing'
		) );
		$kind_post = new Kind_Post( $post );
		$this->assertEquals( 'Blah', $kind_post->get( 'checkin', true ) );
	}

}

