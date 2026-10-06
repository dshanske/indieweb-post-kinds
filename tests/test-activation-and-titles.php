<?php

class ActivationAndTitlesTest extends WP_UnitTestCase {
	public function test_activation_creates_a_term_for_every_kind() {
		// Remove some kind terms, as on a site where activation only created article.
		foreach ( array( 'like', 'jam', 'bookmark' ) as $slug ) {
			$term = get_term_by( 'slug', $slug, 'kind' );
			if ( $term ) {
				wp_delete_term( $term->term_id, 'kind' );
			}
		}
		Kind_Taxonomy::activate_kinds();
		foreach ( Kind_Taxonomy::get_kind_list() as $slug ) {
			$this->assertInstanceOf( WP_Term::class, get_term_by( 'slug', $slug, 'kind' ), $slug );
		}
	}

	public function test_activation_registers_kinds_when_init_has_not_run() {
		// As on activation: the plugin's init (and so the built-in kinds) has not run.
		$kinds   = new ReflectionProperty( 'Kind_Taxonomy', 'kinds' );
		$loaded  = new ReflectionProperty( 'Kind_Taxonomy', 'builtins_loaded' );
		if ( PHP_VERSION_ID < 80100 ) {
			$kinds->setAccessible( true );
			$loaded->setAccessible( true );
		}
		$saved_kinds  = $kinds->getValue();
		$saved_loaded = $loaded->getValue();
		$kinds->setValue( null, array() );
		$loaded->setValue( null, false );
		try {
			Kind_Taxonomy::activate_kinds();
			$this->assertContains( 'like', Kind_Taxonomy::get_kind_list() );
			$this->assertContains( 'note', Kind_Taxonomy::get_kind_list() );
			$this->assertInstanceOf( WP_Term::class, get_term_by( 'slug', 'like', 'kind' ) );
		} finally {
			$kinds->setValue( null, $saved_kinds );
			$loaded->setValue( null, $saved_loaded );
		}
	}

	public function test_load_kinds_keeps_custom_kinds_registered_first() {
		$kinds  = new ReflectionProperty( 'Kind_Taxonomy', 'kinds' );
		$loaded = new ReflectionProperty( 'Kind_Taxonomy', 'builtins_loaded' );
		if ( PHP_VERSION_ID < 80100 ) {
			$kinds->setAccessible( true );
			$loaded->setAccessible( true );
		}
		$saved_kinds  = $kinds->getValue();
		$saved_loaded = $loaded->getValue();
		$kinds->setValue( null, array() );
		$loaded->setValue( null, false );
		try {
			register_post_kind( 'pk-early', array( 'singular_name' => 'Early' ) );
			Kind_Taxonomy::load_kinds();
			$list = Kind_Taxonomy::get_kind_list();
			$this->assertContains( 'pk-early', $list );
			$this->assertContains( 'note', $list );
		} finally {
			$kinds->setValue( null, $saved_kinds );
			$loaded->setValue( null, $saved_loaded );
		}
	}

	public function test_generated_title_uses_the_cited_name() {
		$post = self::factory()->post->create(
			array(
				'post_title'   => '',
				'post_content' => 'Replying to this.',
				'meta_input'   => array(
					'mf2_in-reply-to' => array(
						array(
							'type'       => array( 'h-cite' ),
							'properties' => array(
								'name' => array( 'Example Post' ),
								'url'  => array( 'https://example.com/post' ),
							),
						),
					),
				),
			)
		);
		set_post_kind( $post, 'reply' );
		$this->assertSame( 'Example Post', Kind_Taxonomy::generate_title( $post ) );
	}

	public function test_generated_title_falls_back_to_the_excerpt() {
		$post = self::factory()->post->create(
			array(
				'post_title'   => '',
				'post_content' => 'Liked this.',
				'meta_input'   => array( 'mf2_like-of' => array( 'https://example.com/post' ) ),
			)
		);
		set_post_kind( $post, 'like' );
		$this->assertSame( 'Liked this.', Kind_Taxonomy::generate_title( $post ) );
	}
}
