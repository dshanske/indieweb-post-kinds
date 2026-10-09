<?php

class ReadOnlyGettersTest extends WP_UnitTestCase {
	private $post;
	private $writes = 0;

	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
		$this->post = self::factory()->post->create();
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			add_action( $hook, array( $this, 'count_write' ) );
		}
	}

	public function tear_down() {
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			remove_action( $hook, array( $this, 'count_write' ) );
		}
		parent::tear_down();
	}

	public function count_write() {
		++$this->writes;
	}

	private function store( $kind, $key, $value ) {
		set_post_kind( $this->post, $kind );
		update_post_meta( $this->post, $key, $value );
		$this->writes = 0;
	}

	public function test_metabox_citation_is_returned_unchanged() {
		$cite = array(
			'properties' => array( 'name' => array( 'Cafe' ) ),
			'type'       => array( 'h-card' ),
		);
		$this->store( 'checkin', 'mf2_checkin', $cite );
		$this->assertSame( $cite, ( new Kind_Post( $this->post ) )->get_cite() );
		$this->assertSame( $cite, get_post_meta( $this->post, 'mf2_checkin', true ) );
		$this->assertSame( 0, $this->writes );
	}

	public function test_type_overwritten_by_earlier_reads_is_restored_from_the_kind() {
		$this->store(
			'checkin',
			'mf2_checkin',
			array(
				'properties' => array( 'name' => array( 'Cafe' ) ),
				'type'       => 'cite',
			)
		);
		$cite = ( new Kind_Post( $this->post ) )->get_cite();
		$this->assertSame( array( 'h-card' ), $cite['type'] );
		$this->assertSame( 0, $this->writes );
	}

	public function test_jf2_citation_is_returned_as_mf2_without_saving() {
		$this->store(
			'reply',
			'mf2_in-reply-to',
			array(
				'url'  => 'https://example.com/post',
				'name' => 'Post',
			)
		);
		$cite = ( new Kind_Post( $this->post ) )->get_cite();
		$this->assertSame( array( 'h-cite' ), $cite['type'] );
		$this->assertSame( array( 'https://example.com/post' ), $cite['properties']['url'] );
		$this->assertSame( 0, $this->writes );
	}

	public function test_old_string_citation_is_read_and_kept() {
		$this->store( 'reply', 'mf2_cite', 'https://example.com/old' );
		$kind_post = new Kind_Post( $this->post );
		$this->assertSame( 'https://example.com/old', $kind_post->get_cite() );
		$this->assertSame( 'https://example.com/old', $kind_post->get_cite() );
		$this->assertSame( 'https://example.com/old', get_post_meta( $this->post, 'mf2_cite', true ) );
		$this->assertSame( 0, $this->writes );
	}

	public function test_media_getters_do_not_write() {
		$this->store( 'photo', 'mf2_photo', array( 'https://example.com/x.jpg' ) );
		wp_update_post(
			array(
				'ID'           => $this->post,
				'post_content' => '<img src="https://example.com/y.jpg">',
			)
		);
		delete_post_meta( $this->post, '_content_img_ids' );
		$this->writes = 0;
		$kind_post    = new Kind_Post( $this->post );
		$kind_post->get_photo();
		$kind_post->get_audio();
		$kind_post->get_video();
		$this->assertSame( 0, $this->writes );
	}

	public function test_mf2_post_reads_old_response_meta_without_moving_it() {
		$this->setExpectedDeprecated( 'MF2_Post::__construct' );
		$this->store(
			'reply',
			'response',
			array(
				'title'  => 'Old',
				'url'    => 'https://example.com/old',
				'author' => 'Ann',
			)
		);
		$mf2_post = new MF2_Post( $this->post );
		$cite     = $mf2_post->get( 'cite', false );
		$this->assertSame( 'Old', $cite['name'] );
		$this->assertTrue( metadata_exists( 'post', $this->post, 'response' ) );
		$this->assertSame( 0, $this->writes );
	}

	public function test_mf2_post_images_do_not_sideload() {
		$this->setExpectedDeprecated( 'MF2_Post::__construct' );
		$this->store( 'photo', 'mf2_photo', array( 'https://example.com/remote.jpg' ) );
		add_filter( 'pre_http_request', array( $this, 'fail_request' ) );
		$images = ( new MF2_Post( $this->post ) )->get_images();
		remove_filter( 'pre_http_request', array( $this, 'fail_request' ) );
		$this->assertFalse( $images );
		$this->assertSame( 0, $this->writes );
	}

	public function fail_request() {
		$this->fail( 'Reading a post made an HTTP request.' );
	}
}
