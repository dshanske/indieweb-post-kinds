<?php

class PhotoFlagTest extends WP_UnitTestCase {
	private function has_flag( $id ) {
		return metadata_exists( 'post', $id, '_kind_has_photo' );
	}

	public function test_images_in_content_set_the_flag() {
		$image = self::factory()->attachment->create_object( 'pk-flag.jpg', 0, array( 'post_mime_type' => 'image/jpeg' ) );
		$id    = self::factory()->post->create( array( 'post_content' => '<img class="wp-image-' . $image . '" src="x.jpg">' ) );
		$this->assertTrue( $this->has_flag( $id ) );

		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => 'No image now',
			)
		);
		$this->assertFalse( $this->has_flag( $id ) );
	}

	public function test_photo_property_sets_the_flag_outside_a_save() {
		$id = self::factory()->post->create( array( 'post_content' => 'Words' ) );
		$this->assertFalse( $this->has_flag( $id ) );
		update_post_meta( $id, 'mf2_photo', array( 'https://example.com/p.jpg' ) );
		$this->assertTrue( $this->has_flag( $id ) );
		delete_post_meta( $id, 'mf2_photo' );
		$this->assertFalse( $this->has_flag( $id ) );
	}

	public function test_photo_property_on_other_post_types_is_ignored() {
		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $id, 'mf2_photo', array( 'https://example.com/p.jpg' ) );
		$this->assertFalse( $this->has_flag( $id ) );
	}

	public function test_photos_archive_selects_flagged_posts() {
		$with    = self::factory()->post->create( array( 'post_content' => 'Words' ) );
		$without = self::factory()->post->create( array( 'post_content' => 'Words' ) );
		update_post_meta( $with, 'mf2_photo', array( 'https://example.com/p.jpg' ) );

		$this->go_to( home_url( '/?kind_photos=1' ) );
		$ids = wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' );
		$this->assertContains( $with, $ids );
		$this->assertNotContains( $without, $ids );
	}

	public function test_upgrade_records_the_flag() {
		$id = self::factory()->post->create( array( 'post_content' => 'Words' ) );
		add_post_meta( $id, 'mf2_photo', array( 'https://example.com/p.jpg' ) );
		delete_post_meta( $id, '_kind_has_photo' );
		$this->assertSame( array( 'recorded media IDs' ), Kind_Upgrade::upgrade_post( $id ) );
		$this->assertTrue( $this->has_flag( $id ) );
	}
}
