<?php

class MediaContentTest extends WP_UnitTestCase {
	private $image;
	private $audio;
	private $lookups = 0;

	public function set_up() {
		parent::set_up();
		$this->image = self::factory()->attachment->create_object( 'pk-test.jpg', 0, array( 'post_mime_type' => 'image/jpeg' ) );
		update_attached_file( $this->image, 'pk-test.jpg' );
		$this->audio = self::factory()->attachment->create_object( 'pk-test.mp3', 0, array( 'post_mime_type' => 'audio/mpeg' ) );
		update_attached_file( $this->audio, 'pk-test.mp3' );
		add_filter( 'attachment_url_to_postid', array( $this, 'count_lookup' ) );
	}

	public function tear_down() {
		remove_filter( 'attachment_url_to_postid', array( $this, 'count_lookup' ) );
		parent::tear_down();
	}

	public function count_lookup( $id ) {
		++$this->lookups;
		return $id;
	}

	public function test_image_class_is_used_without_a_lookup() {
		$ids = Kind_Media_Metadata::get_img_from_content( '<img class="alignnone wp-image-' . $this->image . '" src="' . wp_get_attachment_url( $this->image ) . '">' );
		$this->assertSame( array( $this->image ), $ids );
		$this->assertSame( 0, $this->lookups );
	}

	public function test_image_without_class_is_looked_up() {
		$this->assertSame( array( $this->image ), Kind_Media_Metadata::get_img_from_content( '<img src="' . wp_get_attachment_url( $this->image ) . '">' ) );
		$this->assertSame( 1, $this->lookups );
	}

	public function test_media_outside_the_library_is_not_recorded() {
		$this->assertSame( array(), Kind_Media_Metadata::get_audio_from_content( '<audio><source src="https://example.com/x.mp3"></audio>' ) );
		$this->assertSame( array(), Kind_Media_Metadata::get_img_from_content( '<img src="https://example.com/x.jpg">' ) );
	}

	public function test_content_without_media_is_not_parsed() {
		$this->assertSame( array(), Kind_Media_Metadata::get_img_from_content( 'Just words, and a <a href="https://example.com/">link</a>.' ) );
		$this->assertSame( 0, $this->lookups );
	}

	public function test_saving_a_post_records_and_clears_its_media() {
		$post = self::factory()->post->create(
			array(
				'post_content' => '<img class="wp-image-' . $this->image . '" src="x.jpg"><audio><source src="' . wp_get_attachment_url( $this->audio ) . '?_=1"></audio>',
			)
		);
		$this->assertSame( array( $this->image ), get_post_meta( $post, '_content_img_ids', true ) );
		$this->assertSame( array( $this->audio ), get_post_meta( $post, '_content_audio_ids', true ) );
		$this->assertSame( '', get_post_meta( $post, '_content_video_ids', true ) );

		wp_update_post(
			array(
				'ID'           => $post,
				'post_content' => 'No media now.',
			)
		);
		$this->assertFalse( metadata_exists( 'post', $post, '_content_img_ids' ) );
		$this->assertFalse( metadata_exists( 'post', $post, '_content_audio_ids' ) );
	}

	public function test_gallery_ids_are_recorded_as_integers() {
		$post = self::factory()->post->create( array( 'post_content' => '[gallery ids="' . $this->image . '"]' ) );
		$this->assertSame( array( $this->image ), get_post_meta( $post, '_content_img_ids', true ) );
	}

	public function test_pages_are_not_scanned() {
		$page = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '<img class="wp-image-' . $this->image . '" src="x.jpg">',
			)
		);
		$this->assertFalse( metadata_exists( 'post', $page, '_content_img_ids' ) );
	}
}
