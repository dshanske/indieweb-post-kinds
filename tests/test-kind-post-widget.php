<?php

class KindPostWidgetTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	private function render( $instance ) {
		ob_start();
		( new Kind_Post_Widget() )->widget(
			array(
				'before_widget' => '<section>',
				'after_widget'  => '</section>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			),
			$instance
		);
		return ob_get_clean();
	}

	public function test_lists_posts_of_the_kind_with_balanced_markup() {
		foreach ( array( 'First reply', 'Second reply', 'Third reply' ) as $title ) {
			set_post_kind( self::factory()->post->create( array( 'post_title' => $title ) ), 'reply' );
		}
		set_post_kind( self::factory()->post->create( array( 'post_title' => 'A like' ) ), 'like' );

		$html = $this->render(
			array(
				'title'  => 'Replies',
				'kind'   => 'reply',
				'number' => 2,
			)
		);
		$this->assertStringStartsWith( '<section><h2>Replies</h2>', $html );
		$this->assertStringEndsWith( '</section>', $html );
		$this->assertSame( 2, substr_count( $html, '<li>' ) );
		$this->assertStringNotContainsString( 'A like', $html );
	}

	public function test_prints_nothing_without_posts() {
		$this->assertSame(
			'',
			$this->render(
				array(
					'title' => 'Jams',
					'kind'  => 'jam',
				)
			)
		);
	}

	public function test_does_not_use_a_transient() {
		set_post_kind( self::factory()->post->create(), 'reply' );
		// An old transient from earlier versions must not be read (it left $posts undefined).
		set_transient( 'kind_post_widget', 'stale' );
		$html = $this->render( array( 'kind' => 'reply' ) );
		$this->assertSame( 1, substr_count( $html, '<li>' ) );
		$this->assertSame( 'stale', get_transient( 'kind_post_widget' ) );
	}
}
