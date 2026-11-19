<?php

/**
 * The kind views with embeds, attached photos, media attachments and event times (#521).
 *
 * The views' own "no citation" early returns can't be reached: get_view_part()
 * always passes a normalized citation array.
 */
class ViewVariantsTest extends WP_UnitTestCase {
	const EMBED_URL = 'https://www.youtube.com/watch?v=pkTest';

	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
		update_option( 'kind_embeds', 1 );
		wp_embed_register_handler(
			'pk-test',
			'#^https://www\.youtube\.com/watch\?v=pkTest$#',
			function () {
				return '<iframe class="pk-test-embed"></iframe>';
			}
		);
	}

	public function tear_down() {
		wp_embed_unregister_handler( 'pk-test' );
		delete_option( 'kind_embeds' );
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Creates a post of a kind citing a URL, and renders it.
	 *
	 * @param string   $kind  Kind slug.
	 * @param string   $url   Cited URL.
	 * @param callable $setup Optional. Called with the post ID before rendering.
	 * @return string The view's markup.
	 */
	private function render( $kind, $url, $setup = null ) {
		$post = self::factory()->post->create( array( 'post_content' => 'c' ) );
		set_post_kind( $post, $kind );
		$property = Kind_Taxonomy::get_kind_info( $kind, 'property' );
		( new Kind_Post( $post ) )->set(
			$property,
			array(
				'type' => 'cite',
				'name' => 'Cited',
				'url'  => $url,
			)
		);
		if ( $setup ) {
			$setup( $post );
		}
		$GLOBALS['post'] = get_post( $post );
		return Kind_View::get_view_part( 'kind', $kind, array( 'post_id' => $post ) );
	}

	/**
	 * Attaches an image to a post.
	 *
	 * @param int    $post Post ID.
	 * @param string $file File name.
	 * @return int Attachment ID.
	 */
	private function attach_image( $post, $file = 'pk-variant.jpg' ) {
		return self::factory()->attachment->create_object( $file, $post, array( 'post_mime_type' => 'image/jpeg' ) );
	}

	public function kinds_with_embeds() {
		return array(
			'bookmark' => array( 'bookmark' ),
			'favorite' => array( 'favorite' ),
			'issue'    => array( 'issue' ),
			'like'     => array( 'like' ),
			'listen'   => array( 'listen' ),
			'read'     => array( 'read' ),
			'watch'    => array( 'watch' ),
			'jam'      => array( 'jam' ),
			'checkin'  => array( 'checkin' ),
			'drink'    => array( 'drink' ),
			'eat'      => array( 'eat' ),
			'rsvp'     => array( 'rsvp' ),
			'repost'   => array( 'repost' ),
			'video'    => array( 'video' ),
			'audio'    => array( 'audio' ),
			'photo'    => array( 'photo' ),
		);
	}

	/**
	 * @dataProvider kinds_with_embeds
	 */
	public function test_embeddable_citation_is_embedded( $kind ) {
		$html = $this->render( $kind, self::EMBED_URL );
		$this->assertStringContainsString( '<iframe class="pk-test-embed"></iframe>', $html );
		$this->assertStringContainsString( 'e-summary', $html );
	}

	public function kinds_with_photos() {
		return array(
			'listen'  => array( 'listen' ),
			'read'    => array( 'read' ),
			'jam'     => array( 'jam' ),
			'checkin' => array( 'checkin' ),
			'drink'   => array( 'drink' ),
			'eat'     => array( 'eat' ),
			'event'   => array( 'event' ),
		);
	}

	/**
	 * @dataProvider kinds_with_photos
	 */
	public function test_attached_photos_are_shown( $kind ) {
		$html = $this->render(
			$kind,
			'https://example.com/cited',
			function ( $post ) {
				$this->attach_image( $post );
			}
		);
		$this->assertStringContainsString( 'gallery', $html );
		$this->assertStringContainsString( 'pk-variant', $html );
	}

	public function test_event_times() {
		$html = $this->render(
			'event',
			'https://example.com/event',
			function ( $post ) {
				update_post_meta( $post, 'mf2_start', '2024-05-06T18:00:00+00:00' );
				update_post_meta( $post, 'mf2_end', '2024-05-06T21:00:00+00:00' );
			}
		);
		$this->assertStringContainsString( '<time class="dt-start" datetime="2024-05-06T18:00:00+00:00">', $html );
		$this->assertStringContainsString( '<time class="dt-end" datetime="2024-05-06T21:00:00+00:00">', $html );
	}

	public function test_photo_post_with_one_photo_cites_it() {
		$html = $this->render(
			'photo',
			'https://example.com/cited',
			function ( $post ) {
				$image = $this->attach_image( $post, 'pk-single.jpg' );
				wp_update_post(
					array(
						'ID'         => $image,
						'post_title' => 'Harbour at dusk',
					)
				);
			}
		);
		$this->assertStringContainsString( '<span class="p-name">Harbour at dusk</span>', $html );
		$this->assertStringContainsString( 'gallery', $html );
		$this->assertStringNotContainsString( 'pk-test-embed', $html );
	}

	public function test_video_post_with_one_video_attachment() {
		$html = $this->render(
			'video',
			'https://example.com/cited',
			function ( $post ) {
				self::factory()->attachment->create_object(
					'pk-single.mp4',
					$post,
					array(
						'post_mime_type' => 'video/mp4',
						'post_title'     => 'A Short Film',
					)
				);
			}
		);
		$this->assertStringContainsString( '<span class="p-name">A Short Film</span>', $html );
		$this->assertStringContainsString( 'pk-single.mp4', $html );
	}

	public function test_video_post_citing_a_video_file() {
		$html = $this->render( 'video', 'https://example.com/film.mp4' );
		$this->assertStringContainsString( 'https://example.com/film.mp4', $html );
		$this->assertStringContainsString( 'wp-video', $html );
	}

	public function test_audio_post_with_one_audio_attachment() {
		$html = $this->render(
			'audio',
			'https://example.com/cited',
			function ( $post ) {
				self::factory()->attachment->create_object(
					'pk-single.mp3',
					$post,
					array(
						'post_mime_type' => 'audio/mpeg',
						'post_title'     => 'A Song',
					)
				);
			}
		);
		$this->assertStringContainsString( '<span class="p-name">A Song</span>', $html );
		$this->assertStringContainsString( 'pk-single.mp3', $html );
	}
}
