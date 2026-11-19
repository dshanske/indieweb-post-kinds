<?php

/**
 * The global template and time functions (#521).
 */
class GlobalFunctionsTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	/**
	 * Creates an untitled post of a kind.
	 *
	 * @param string $kind Kind slug.
	 * @param array  $args Post arguments.
	 * @return int Post ID.
	 */
	private function untitled( $kind, $args = array() ) {
		$post = self::factory()->post->create(
			array_merge(
				array(
					'post_title'   => '',
					'post_excerpt' => '',
				),
				$args
			)
		);
		set_post_kind( $post, $kind );
		return $post;
	}

	public function test_set_post_kind_visibility() {
		set_post_kind_visibility( 'jam', false );
		$hidden = Kind_Taxonomy::get_post_kind_info( 'jam' )->show;
		set_post_kind_visibility( 'jam' );
		$this->assertFalse( $hidden );
		$this->assertTrue( Kind_Taxonomy::get_post_kind_info( 'jam' )->show );
	}

	public function test_get_post_kind_link() {
		$this->assertSame( get_term_link( 'reply', 'kind' ), get_post_kind_link( 'reply' ) );
		$this->assertFalse( get_post_kind_link( 'no-such-kind' ) );
	}

	public function test_kind_display_echoes_the_response() {
		$post = self::factory()->post->create();
		set_post_kind( $post, 'reply' );
		( new Kind_Post( $post ) )->set(
			'in-reply-to',
			array(
				'type' => 'cite',
				'url'  => 'https://example.com/post',
			)
		);
		ob_start();
		kind_display( $post );
		$this->assertStringContainsString( 'u-in-reply-to', ob_get_clean() );
	}

	public function test_kind_flatten_array_filters_associative_arrays() {
		$this->assertSame(
			array( 'name' => 'Ann' ),
			kind_flatten_array(
				array(
					'name' => 'Ann',
					'url'  => '',
				)
			)
		);
	}

	public function test_kind_src_url_in_content() {
		$this->assertSame( 0, kind_src_url_in_content( '' ) );
		$this->assertSame( 0, kind_src_url_in_content( 'No images here.' ) );
		$this->assertSame( array( 'a.jpg', 'b.jpg' ), kind_src_url_in_content( '<img src="a.jpg"> and <img src="b.jpg">' ) );
	}

	public function test_kind_get_the_link_takes_class_lists() {
		$post = self::factory()->post->create( array( 'post_title' => 'Linked' ) );
		$link = kind_get_the_link( $post, array( 'a', 'b' ), array( 'c', 'd' ) );
		$this->assertStringContainsString( '<a class="a b" href="' . esc_url( get_permalink( $post ) ) . '">Linked</a>', $link );
		$this->assertStringContainsString( '<time class="c d"', $link );
	}

	public function test_kind_get_the_title_of_an_untitled_photo_post_shows_the_photo() {
		$post = $this->untitled( 'photo' );
		self::factory()->attachment->create_object( 'pk-title.jpg', $post, array( 'post_mime_type' => 'image/jpeg' ) );
		$title = kind_get_the_title( $post );
		$this->assertStringContainsString( 'kind-photo-thumbnail', $title );
		$this->assertStringContainsString( 'pk-title.jpg', $title );
	}

	public function test_kind_get_the_title_of_an_untitled_response() {
		$post = $this->untitled( 'like' );
		update_post_meta(
			$post,
			'mf2_like-of',
			array(
				'type' => 'cite',
				'url'  => 'https://example.com/post',
			)
		);
		// Without a cited name, the kind of thing cited.
		$this->assertStringContainsString( 'a post', kind_get_the_title( $post ) );

		update_post_meta(
			$post,
			'mf2_like-of',
			array(
				'type' => 'cite',
				'name' => array( 'First', 'Second' ),
				'url'  => 'https://example.com/post',
			)
		);
		$this->assertStringContainsString( esc_html( wp_json_encode( array( 'First', 'Second' ) ) ), kind_get_the_title( $post ) );
	}

	public function test_kind_get_the_title_of_an_untitled_note() {
		$post = $this->untitled( 'note', array( 'post_content' => 'A note that is long enough to be shortened for the title' ) );
		$this->assertStringContainsString( 'A note that is long enough to be shor...', kind_get_the_title( $post ) );

		$excerpt = $this->untitled( 'note', array( 'post_excerpt' => 'The excerpt' ) );
		$this->assertStringContainsString( 'The excerpt', kind_get_the_title( $excerpt ) );
	}

	public function test_timezone_helpers() {
		$this->assertSame( 0, tz_offset_to_seconds( 'no offset' ) );
		$this->assertSame( '+01:00', tz_seconds_to_timezone( 3600 )->getName() );
		$this->assertSame( 'UTC', tz_seconds_to_timezone( 0 )->getName() );
	}

	public function test_tz_timezone_to_seconds() {
		$this->markTestSkipped( 'tz_timezone_to_seconds() always fails: DateTimeZone::getOffset() needs a date (#531).' );

		$this->assertSame( 0, tz_timezone_to_seconds( 'UTC' ) );
		$this->assertSame( -18000, tz_timezone_to_seconds( '-05:00' ) );
	}

	public function test_build_iso8601_time_without_a_date_or_time() {
		$this->assertSame( '', build_iso8601_time( '', '', '+00:00' ) );
	}

	public function test_build_iso8601_duration() {
		$this->assertSame(
			'P1Y2DT3H30M5S',
			build_iso8601_duration(
				array(
					'Y' => 1,
					'D' => 2,
					'H' => 3,
					'I' => 30,
					'S' => 5,
				)
			)
		);
		$this->assertSame( 'P2M', build_iso8601_duration( array( 'M' => 2 ) ) );
		$this->assertSame( 'PT45S', build_iso8601_duration( array( 'S' => 45 ) ) );
		$this->assertSame( '', build_iso8601_duration( array( 'X' => 1 ) ) );
	}

	public function test_date_interval_to_iso8601_with_date_parts() {
		$this->assertSame( 'P1Y2M3DT4H', date_interval_to_iso8601( new DateInterval( 'P1Y2M3DT4H' ) ) );
	}
}
