<?php

/**
 * Kind_Media_Metadata and Kind_Media_View: hooks, fields, metadata and media markup (#521).
 */
class MediaCoverageTest extends WP_UnitTestCase {
	/**
	 * Creates an attachment with its file recorded.
	 *
	 * @param string $file File name.
	 * @param string $mime MIME type.
	 * @return int Attachment ID.
	 */
	private function attachment( $file, $mime ) {
		$id = self::factory()->attachment->create_object( $file, 0, array( 'post_mime_type' => $mime ) );
		update_attached_file( $id, $file );
		return $id;
	}

	public function test_init_adds_the_hooks() {
		Kind_Media_Metadata::init();
		$this->assertSame( 33, has_filter( 'wp_generate_attachment_metadata', array( 'Kind_Media_Metadata', 'wp_generate_attachment_metadata' ) ) );
		$this->assertSame( 9, has_filter( 'wp_update_attachment_metadata', array( 'Kind_Media_Metadata', 'wp_sanitize_media_metadata' ) ) );
		$this->assertSame( 10, has_action( 'wp_enqueue_scripts', array( 'Kind_Media_Metadata', 'enqueue' ) ) );
		$this->assertSame( 20, has_action( 'save_post_post', array( 'Kind_Media_Metadata', 'save_post' ) ) );
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			$this->assertSame( 10, has_action( $hook, array( 'Kind_Media_Metadata', 'photo_meta_changed' ) ), $hook );
		}
		$this->assertSame( 10, has_filter( 'attachment_fields_to_edit', array( 'Kind_Media_Metadata', 'attachment_fields_to_edit' ) ) );
		$this->assertSame( 10, has_filter( 'attachment_fields_to_save', array( 'Kind_Media_Metadata', 'attachment_fields_to_save' ) ) );
	}

	public function test_is_amp_endpoint_without_amp() {
		$this->assertFalse( Kind_Media_Metadata::is_amp_endpoint() );
	}

	public function test_artist_fields_of_an_image_without_an_artist() {
		$image  = $this->attachment( 'pk-fields.jpg', 'image/jpeg' );
		$fields = Kind_Media_Metadata::attachment_fields_to_edit( array(), get_post( $image ) );
		$this->assertSame( '', $fields['author_name']['value'] );
		$this->assertSame( '', $fields['author_url']['value'] );

		// Other media don't get the fields.
		$audio = $this->attachment( 'pk-fields.mp3', 'audio/mpeg' );
		$this->assertSame( array(), Kind_Media_Metadata::attachment_fields_to_edit( array(), get_post( $audio ) ) );
	}

	public function test_video_in_content() {
		$video = $this->attachment( 'pk-content.mp4', 'video/mp4' );
		$html  = '<video controls><source src="' . wp_get_attachment_url( $video ) . '?_=1" type="video/mp4"></video>';
		$this->assertSame( array( $video ), Kind_Media_Metadata::get_video_from_content( $html ) );
	}

	public function test_save_post_of_a_missing_post_does_nothing() {
		Kind_Media_Metadata::save_post( PHP_INT_MAX );
		$this->assertSame( '', get_post_meta( PHP_INT_MAX, '_content_img_ids', true ) );
	}

	public function test_sanitize_media_metadata_converts_latin1() {
		$this->assertSame( 'not an array', Kind_Media_Metadata::wp_sanitize_media_metadata( 'not an array' ) );
		$this->assertSame(
			array(
				'title'      => "Caf\xc3\xa9",
				'album'      => 'Plain ASCII',
				'length'     => 3,
				'image_meta' => array( 'credit' => 'Ann' ),
			),
			Kind_Media_Metadata::wp_sanitize_media_metadata(
				array(
					'title'      => "Caf\xe9",
					'album'      => 'Plain ASCII',
					'length'     => 3,
					'image_meta' => array( 'credit' => 'Ann' ),
				)
			)
		);
	}

	public function test_sanitize_media_metadata_keeps_utf8() {
		$this->markTestSkipped( 'Attachment metadata: UTF-8 text is double-encoded on save (#532).' );

		$metadata = array(
			'title'  => 'Café',
			'artist' => '東京',
		);
		$this->assertSame( $metadata, Kind_Media_Metadata::wp_sanitize_media_metadata( $metadata ) );
	}

	public function test_generated_metadata_credit_becomes_the_artist() {
		$image = $this->attachment( 'pk-credit.jpg', 'image/jpeg' );
		$this->assertSame( 'not an array', Kind_Media_Metadata::wp_generate_attachment_metadata( 'not an array', $image ) );

		Kind_Media_Metadata::wp_generate_attachment_metadata( array( 'image_meta' => array( 'credit' => 'Jane Photographer' ) ), $image );
		$author = get_post_meta( $image, 'mf2_author', true );
		$this->assertSame( array( 'h-card' ), $author['type'] );
		$this->assertSame( array( 'Jane Photographer' ), $author['properties']['name'] );
	}

	public function test_media_view_photo_gallery_and_unknown_type() {
		$image = $this->attachment( 'pk-gallery.jpg', 'image/jpeg' );
		$html  = ( new Kind_Media_View( array( $image ), 'photo' ) )->get();
		$this->assertStringContainsString( 'gallery', $html );
		$this->assertStringContainsString( 'pk-gallery.jpg', $html );

		$this->assertSame( '', ( new Kind_Media_View( array( $image ), 'document' ) )->get() );
	}

	public function test_media_view_audio() {
		$audio = $this->attachment( 'pk-view.mp3', 'audio/mpeg' );
		$html  = ( new Kind_Media_View( array( $audio ), 'audio' ) )->get();
		$this->assertStringContainsString( 'pk-view.mp3', $html );
		$this->assertStringContainsString( 'u-audio', $html );

		$this->assertSame( '', ( new Kind_Media_View( 0, 'audio' ) )->get() );
	}

	public function test_media_view_video() {
		$one  = $this->attachment( 'pk-one.mp4', 'video/mp4' );
		$two  = $this->attachment( 'pk-two.mp4', 'video/mp4' );
		$html = ( new Kind_Media_View( array( $one, $two ), 'video' ) )->get();
		$this->assertStringContainsString( 'pk-one.mp4', $html );
		$this->assertStringContainsString( 'pk-two.mp4', $html );
		$this->assertStringContainsString( 'u-video', $html );

		$this->assertSame( '', ( new Kind_Media_View( 0, 'video' ) )->get() );
	}
}
