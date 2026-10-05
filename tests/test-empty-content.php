<?php

class EmptyContentTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		// Activation normally creates the kind terms.
		Kind_Taxonomy::kind_defaultterms();
	}

	/**
	 * Creates a published post with no content, title or excerpt, as Micropub does
	 * for a like, and assigns it a kind.
	 */
	private function create_contentless( $kind ) {
		$id = wp_insert_post(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
				'meta_input'  => array( 'micropub_auth_response' => array( 'client_id' => 'https://example.app/' ) ),
			),
			true
		);
		$this->assertIsInt( $id );
		set_post_kind( $id, $kind );
		return $id;
	}

	public function test_micropub_create_without_content_is_allowed() {
		$this->create_contentless( 'like' );
	}

	public function test_update_to_contentless_like_is_allowed() {
		$id     = $this->create_contentless( 'like' );
		$result = wp_update_post(
			array(
				'ID'         => $id,
				'tags_input' => array( 'pk-test' ),
			),
			true
		);
		$this->assertNotWPError( $result );
		$this->assertTrue( has_tag( 'pk-test', $id ) );
	}

	public function test_empty_article_is_still_rejected() {
		$result = wp_insert_post(
			array(
				'post_status' => 'publish',
				'tax_input'   => array( 'kind' => 'article' ),
			),
			true
		);
		$this->assertWPError( $result );
		$this->assertSame( 'empty_content', $result->get_error_code() );
	}

	public function test_update_to_empty_article_is_still_rejected() {
		$id     = $this->create_contentless( 'article' );
		$result = wp_update_post(
			array(
				'ID'         => $id,
				'tags_input' => array( 'pk-test' ),
			),
			true
		);
		$this->assertWPError( $result );
	}

	public function test_new_contentless_like_without_citation_is_rejected() {
		$result = wp_insert_post(
			array(
				'post_status' => 'publish',
				'tax_input'   => array( 'kind' => 'like' ),
			),
			true
		);
		$this->assertWPError( $result );
	}

	public function test_kind_from_postarr_accepts_slugs_ids_and_existing_posts() {
		$like = get_term_by( 'slug', 'like', 'kind' );
		$this->assertSame( 'like', Kind_Metabox::kind_from_postarr( array( 'tax_input' => array( 'kind' => 'like' ) ) ) );
		$this->assertSame( 'like', Kind_Metabox::kind_from_postarr( array( 'tax_input' => array( 'kind' => array( $like->term_id ) ) ) ) );
		$id = $this->create_contentless( 'bookmark' );
		$this->assertSame( 'bookmark', Kind_Metabox::kind_from_postarr( array( 'ID' => $id ) ) );
		$this->assertSame( '', Kind_Metabox::kind_from_postarr( array() ) );
	}
}
