<?php

/**
 * The Default Kind setting is the kind taxonomy's default term (#501).
 */
class DefaultKindTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	public function test_changing_the_setting_moves_the_default_term() {
		update_option( 'kind_default', 'like' );
		$this->assertSame( get_term_by( 'slug', 'like', 'kind' )->term_id, (int) get_option( 'default_term_kind' ) );
	}

	public function test_post_inserted_by_a_user_gets_the_default_kind() {
		update_option( 'kind_default', 'like' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$post = wp_insert_post(
			array(
				'post_title'   => 'Titled',
				'post_content' => 'Content',
				'post_status'  => 'publish',
			)
		);
		$this->assertSame( 'like', get_post_kind_slug( $post ) );
	}

	public function test_published_draft_gets_the_default_kind() {
		update_option( 'kind_default', 'bookmark' );
		$post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
		wp_set_object_terms( $post, array(), 'kind' );
		wp_publish_post( $post );
		$this->assertSame( 'bookmark', get_post_kind_slug( $post ) );
	}

	public function test_registering_with_a_translated_article_name_adds_no_term() {
		update_option( 'kind_default', 'article' );
		$count = wp_count_terms( array( 'taxonomy' => 'kind', 'hide_empty' => false ) );
		add_filter( 'gettext', array( $this, 'translate_article' ), 10, 3 );
		Kind_Taxonomy::register();
		remove_filter( 'gettext', array( $this, 'translate_article' ), 10 );
		$this->assertSame( $count, wp_count_terms( array( 'taxonomy' => 'kind', 'hide_empty' => false ) ) );
		$this->assertSame( get_term_by( 'slug', 'article', 'kind' )->term_id, (int) get_option( 'default_term_kind' ) );
	}

	public function translate_article( $translation, $text, $domain ) {
		return ( 'Article' === $text && 'indieweb-post-kinds' === $domain ) ? 'Artikel' : $translation;
	}
}
