<?php

class MediaMetadataTest extends WP_UnitTestCase {
	public function test_generated_metadata_sets_duration_author_and_publication() {
		$attachment = self::factory()->attachment->create( array( 'post_mime_type' => 'audio/mpeg' ) );
		Kind_Media_Metadata::wp_generate_attachment_metadata(
			array(
				'length' => 210,
				'artist' => 'An Artist',
				'album'  => 'An Album',
			),
			$attachment
		);
		$this->assertSame( array( 'PT3M30S' ), get_post_meta( $attachment, 'mf2_duration', true ) );
		$this->assertSame( array( 'An Album' ), get_post_meta( $attachment, 'mf2_publication', true ) );
		$author = \ParseThis\mf2_to_jf2( get_post_meta( $attachment, 'mf2_author', true ) );
		$this->assertSame( 'An Artist', $author['name'] );
	}

	public function test_attachment_fields_round_trip_the_author() {
		$attachment = self::factory()->attachment->create(
			array(
				'post_mime_type' => 'image/jpeg',
				'file'           => 'pk-test.jpg',
			)
		);
		Kind_Media_Metadata::attachment_fields_to_save(
			array( 'ID' => $attachment ),
			array(
				'author_name' => 'Photo Person',
				'author_url'  => 'https://example.com/p',
			)
		);
		$fields = Kind_Media_Metadata::attachment_fields_to_edit( array(), get_post( $attachment ) );
		$this->assertSame( 'Photo Person', $fields['author_name']['value'] );
		$this->assertSame( 'https://example.com/p', $fields['author_url']['value'] );
	}

	public function test_images_are_found_in_content() {
		$this->assertSame(
			array( 999 ),
			Kind_Media_Metadata::get_img_from_content( '<p><img class="wp-image-999" src="https://example.com/a.jpg"></p>' )
		);
	}
}
