<?php

class AttachmentArtistTest extends WP_UnitTestCase {
	private $attachment;

	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->attachment = self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) );
	}

	/**
	 * Saves the artist fields, slashed as core passes them, and returns the stored properties.
	 */
	private function save( $fields ) {
		Kind_Media_Metadata::attachment_fields_to_save( get_post( $this->attachment, ARRAY_A ), wp_slash( $fields ) );
		$author = get_post_meta( $this->attachment, 'mf2_author', true );
		return $author ? $author['properties'] : array();
	}

	public function test_artist_is_sanitized() {
		$author = $this->save(
			array(
				'author_name' => 'O\'Brien <b>Ann</b><script>alert(1)</script> C:\\x',
				'author_url'  => 'https://example.com/a?b=1&c=2',
			)
		);
		$this->assertSame( array( 'O\'Brien Ann C:\\x' ), $author['name'] );
		$this->assertSame( array( 'https://example.com/a?b=1&c=2' ), $author['url'] );
	}

	public function test_unsafe_url_is_not_saved() {
		$author = $this->save(
			array(
				'author_name' => 'Ann',
				'author_url'  => 'javascript:alert(1)',
			)
		);
		$this->assertSame( array( 'Ann' ), $author['name'] );
		$this->assertArrayNotHasKey( 'url', $author );
	}

	public function test_empty_fields_delete_the_artist() {
		$this->save( array( 'author_name' => 'Ann' ) );
		$this->assertSame( array(), $this->save( array( 'author_name' => '', 'author_url' => '' ) ) );
	}

	public function test_user_who_cannot_edit_saves_nothing() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( array(), $this->save( array( 'author_name' => 'Ann' ) ) );
	}
}
