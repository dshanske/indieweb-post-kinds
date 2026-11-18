<?php

/**
 * Kind_Post: lookups, attachments, media, citations and setters (#521).
 */
class KindPostAccessorsTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
	}

	/**
	 * Creates an attachment.
	 *
	 * @param string $file   File name.
	 * @param string $mime   MIME type.
	 * @param int    $parent Parent post ID.
	 * @param array  $args   Other post fields.
	 * @return int Attachment ID.
	 */
	private function attachment( $file, $mime, $parent = 0, $args = array() ) {
		return self::factory()->attachment->create_object( $file, $parent, array_merge( array( 'post_mime_type' => $mime ), $args ) );
	}

	/**
	 * Creates a post of a kind.
	 *
	 * @param string $kind Kind slug.
	 * @param array  $args Post arguments.
	 * @return int Post ID.
	 */
	private function kind_post( $kind, $args = array() ) {
		$post = self::factory()->post->create( $args );
		set_post_kind( $post, $kind );
		return $post;
	}

	public function test_construct_from_a_url() {
		$post = self::factory()->post->create();
		$this->assertSame( $post, ( new Kind_Post( get_permalink( $post ) ) )->get_post()->ID );

		$image = $this->attachment( 'pk-construct.jpg', 'image/jpeg' );
		$this->assertSame( $image, ( new Kind_Post( wp_get_attachment_url( $image ) ) )->get_post()->ID );
	}

	public function test_kind_of_an_attachment_is_its_media_type() {
		$this->assertSame( 'photo', ( new Kind_Post( $this->attachment( 'pk-kind.jpg', 'image/jpeg' ) ) )->get_kind() );
		$this->assertSame( 'video', ( new Kind_Post( $this->attachment( 'pk-kind.mp4', 'video/mp4' ) ) )->get_kind() );
		$this->assertSame( 'audio', ( new Kind_Post( $this->attachment( 'pk-kind.mp3', 'audio/mpeg' ) ) )->get_kind() );
		$this->assertNull( ( new Kind_Post( $this->attachment( 'pk-kind.pdf', 'application/pdf' ) ) )->get_kind() );
	}

	public function test_get_name() {
		$post = self::factory()->post->create( array( 'post_title' => 'A Title' ) );
		$this->assertSame( 'A Title', ( new Kind_Post( $post ) )->get( 'name' ) );

		// A title that is only the post ID isn't a name.
		$post = self::factory()->post->create();
		wp_update_post(
			array(
				'ID'         => $post,
				'post_title' => (string) $post,
			)
		);
		$this->assertFalse( ( new Kind_Post( $post ) )->get_name() );

		// Nor is an attachment title that is just the file name.
		$image = $this->attachment( 'uploads/pk-sunset.jpg', 'image/jpeg', 0, array( 'post_title' => 'pk-sunset' ) );
		$this->assertFalse( ( new Kind_Post( $image ) )->get_name() );

		$image = $this->attachment( 'pk-scaled.jpg', 'image/jpeg', 0, array( 'post_title' => 'pk-original' ) );
		wp_update_attachment_metadata( $image, array( 'original_image' => 'pk-original.jpg' ) );
		$this->assertFalse( ( new Kind_Post( $image ) )->get_name() );

		$image = $this->attachment( 'pk-named.jpg', 'image/jpeg', 0, array( 'post_title' => 'Sunset over the bay' ) );
		$this->assertSame( 'Sunset over the bay', ( new Kind_Post( $image ) )->get_name() );
	}

	public function test_get_html() {
		$post = self::factory()->post->create(
			array(
				'post_content' => '<p>The <em>content</em></p>',
				'post_excerpt' => 'The excerpt',
			)
		);
		$kind_post = new Kind_Post( $post );
		$this->assertSame(
			array(
				'html'  => '<p>The <em>content</em></p>',
				'value' => 'The content',
			),
			$kind_post->get( 'content' )
		);
		$this->assertSame( 'The excerpt', $kind_post->get( 'summary' )['value'] );
		$this->assertFalse( $kind_post->get_html( 'name' ) );

		$empty = self::factory()->post->create( array( 'post_excerpt' => '' ) );
		$this->assertFalse( ( new Kind_Post( $empty ) )->get_html( 'summary' ) );
	}

	public function test_get_url_featured_and_publication() {
		$post      = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );
		$this->assertSame( get_permalink( $post ), $kind_post->get( 'url' ) );
		$this->assertSame( get_bloginfo( 'title' ), $kind_post->get( 'publication' ) );
		$this->assertFalse( $kind_post->get( 'featured' ) );

		$image = $this->attachment( 'pk-featured.jpg', 'image/jpeg', $post );
		set_post_thumbnail( $post, $image );
		$this->assertSame( wp_get_attachment_url( $image ), $kind_post->get( 'featured' ) );

		$attachment = new Kind_Post( $image );
		$this->assertSame( wp_get_attachment_url( $image ), $attachment->get_url() );
		$this->assertNull( $attachment->get_publication() );
		update_post_meta( $image, 'mf2_publication', 'The Photo Agency' );
		$this->assertSame( 'The Photo Agency', $attachment->get_publication() );
	}

	public function test_get_datetime_property() {
		$post = self::factory()->post->create( array( 'post_date' => '2024-01-02 03:04:05' ) );
		$this->assertInstanceOf( 'DateTimeImmutable', ( new Kind_Post( $post ) )->get( 'updated' ) );

		update_post_meta( $post, 'mf2_start', array( '2024-05-06T07:08:09+00:00', '2024-05-07T00:00:00+00:00' ) );
		$this->assertSame( '2024-05-06T07:08:09+00:00', ( new Kind_Post( $post ) )->get( 'start' )->format( DATE_W3C ) );
	}

	public function test_get_author_of_posts_without_a_user_and_attachments() {
		$post = self::factory()->post->create( array( 'post_author' => 0 ) );
		$card = array(
			'type' => 'card',
			'name' => 'Someone Else',
		);
		update_post_meta( $post, 'mf2_author', $card );
		$this->assertSame( $card, ( new Kind_Post( $post ) )->get( 'author' ) );

		$user  = self::factory()->user->create();
		$image = $this->attachment( 'pk-author.jpg', 'image/jpeg', 0, array( 'post_author' => $user ) );
		$this->assertSame( '', ( new Kind_Post( $image ) )->get_author() );
		update_post_meta( $image, 'mf2_author', $card );
		$this->assertSame( $card, ( new Kind_Post( $image ) )->get_author() );
	}

	public function test_get_categories() {
		$category = self::factory()->category->create( array( 'name' => 'Travel' ) );
		$post     = self::factory()->post->create(
			array(
				'post_category' => array( $category ),
				'tags_input'    => array( 'beach' ),
			)
		);
		$this->assertSame( array( 'Travel', 'beach' ), array_values( ( new Kind_Post( $post ) )->get( 'category' ) ) );
	}

	public function test_attached_media_of_an_unknown_type() {
		$post = self::factory()->post->create();
		$this->assertFalse( ( new Kind_Post( $post ) )->get_attached_media( 'document' ) );
	}

	public function test_media_attachment_is_its_own_media() {
		$image = $this->attachment( 'pk-self.jpg', 'image/jpeg' );
		$video = $this->attachment( 'pk-self.mp4', 'video/mp4' );
		$audio = $this->attachment( 'pk-self.mp3', 'audio/mpeg' );
		$this->assertSame( array( $image ), ( new Kind_Post( $image ) )->get_photo() );
		$this->assertSame( array( $video ), ( new Kind_Post( $video ) )->get_video() );
		$this->assertSame( array( $audio ), ( new Kind_Post( $audio ) )->get_audio() );
	}

	public function test_media_in_the_content_is_not_repeated() {
		$post = self::factory()->post->create();
		update_post_meta( $post, '_content_img_ids', array( 11 ) );
		update_post_meta( $post, '_content_video_ids', array( 12 ) );
		update_post_meta( $post, '_content_audio_ids', array( 13 ) );
		$kind_post = new Kind_Post( $post );

		$this->assertSame( array(), $kind_post->get_photo() );
		$this->assertSame( array( 11 ), $kind_post->get_photo( false ) );
		$this->assertSame( array(), $kind_post->get_video() );
		$this->assertSame( array(), $kind_post->get_audio() );
	}

	public function test_photo_is_empty_when_there_is_a_featured_image() {
		$post  = self::factory()->post->create();
		$image = $this->attachment( 'pk-feat.jpg', 'image/jpeg', $post );
		set_post_thumbnail( $post, $image );
		$this->assertSame( array(), ( new Kind_Post( $post ) )->get_photo() );
	}

	public function test_attached_media() {
		$post  = self::factory()->post->create();
		$image = $this->attachment( 'pk-att.jpg', 'image/jpeg', $post );
		$video = $this->attachment( 'pk-att.mp4', 'video/mp4', $post );
		$kind_post = new Kind_Post( $post );
		$this->assertSame( array( $image ), array_values( $kind_post->get_photo() ) );
		$this->assertSame( array( $video ), array_values( $kind_post->get_video() ) );
	}

	public function test_get_attachments_from_urls() {
		$image     = $this->attachment( 'pk-urls.jpg', 'image/jpeg' );
		$url       = wp_get_attachment_url( $image );
		$kind_post = new Kind_Post( self::factory()->post->create() );

		$this->assertSame( array( $image ), $kind_post->get_attachments_from_urls( $url ) );
		$this->assertSame( array(), $kind_post->get_attachments_from_urls( 'https://example.com/none.jpg' ) );
		$this->assertSame( array( $image, 42 ), array_values( $kind_post->get_attachments_from_urls( array( array( 'url' => $url ), array( 'alt' => 'no url' ), 42 ) ) ) );
	}

	public function test_cite_of_an_attachment() {
		$audio = $this->attachment( 'pk-cite.mp3', 'audio/mpeg', 0, array( 'post_title' => 'A Song' ) );
		$cite  = ( new Kind_Post( $audio ) )->get_cite();
		$this->assertSame( array( 'h-cite' ), $cite['type'] );
		$this->assertSame( array( 'A Song' ), $cite['properties']['name'] );
		$this->assertSame( array( wp_get_attachment_url( $audio ) ), $cite['properties']['url'] );
		$this->assertSame( 'A Song', ( new Kind_Post( $audio ) )->get_cite( 'name' ) );
	}

	public function test_cite_lookups() {
		$reply = $this->kind_post( 'reply' );
		$this->assertEmpty( ( new Kind_Post( $reply ) )->get_cite() );

		update_post_meta(
			$reply,
			'mf2_in-reply-to',
			array(
				array(
					'type'       => array( 'h-cite' ),
					'properties' => array( 'url' => array( 'https://example.com/post' ) ),
				),
			)
		);
		$this->assertFalse( ( new Kind_Post( $reply ) )->get_cite( 'name' ) );
	}

	public function test_cite_stored_as_a_url() {
		$reply     = $this->kind_post( 'reply', array( 'meta_input' => array( 'mf2_in-reply-to' => 'https://www.example.com/a/post' ) ) );
		$kind_post = new Kind_Post( $reply );
		$this->assertSame( 'https://www.example.com/a/post', $kind_post->get_cite( 'url' ) );
		$this->assertSame( 'www.example.com/a/post', $kind_post->get_cite( 'name' ) );
		$this->assertFalse( $kind_post->get_cite( 'summary' ) );

		$named = $this->kind_post( 'reply', array( 'meta_input' => array( 'mf2_in-reply-to' => 'Just a name' ) ) );
		$this->assertSame( 'Just a name', ( new Kind_Post( $named ) )->get_cite( 'name' ) );
		$this->assertFalse( ( new Kind_Post( $named ) )->get_cite( 'url' ) );
	}

	public function test_cite_type_of_food() {
		$this->assertSame( 'food', ( new Kind_Post( $this->kind_post( 'eat' ) ) )->get_cite_type() );
		$this->assertSame( 'food', ( new Kind_Post( $this->kind_post( 'drink' ) ) )->get_cite_type() );
	}

	public function test_normalize_cite_flattens_arrays() {
		$kind_post = new Kind_Post( self::factory()->post->create() );
		$cite      = $kind_post->normalize_cite(
			array(
				'url'         => array( 'https://example.com/one', 'https://example.com/two' ),
				'summary'     => array(
					'html'  => '<b>Summary</b>',
					'value' => 'Summary',
				),
				'category'    => array( 'one', 'two' ),
				'publication' => array(
					'type' => 'card',
					'name' => 'The Publication',
				),
			)
		);
		$this->assertSame( 'https://example.com/one', $cite['url'] );
		$this->assertSame( '<b>Summary</b>', $cite['summary'] );
		$this->assertSame( 'one;two', $cite['category'] );
		$this->assertSame( 'The Publication', $cite['publication'] );
	}

	public function test_normalize_authors_edge_cases() {
		$this->assertSame( array(), Kind_Post::normalize_authors( 42 ) );
		$this->assertSame(
			array(
				array(
					'type'  => 'card',
					'name'  => 'Someone',
					'url'   => '',
					'photo' => 'https://example.com/me.jpg',
				),
			),
			Kind_Post::normalize_authors(
				array(
					'name'  => 'Someone',
					'photo' => array(
						'value' => 'https://example.com/me.jpg',
						'alt'   => 'A photo',
					),
				)
			)
		);
	}

	public function test_set_author_only_on_attachments() {
		$card  = array(
			'type' => 'card',
			'name' => 'The Photographer',
		);
		$image = $this->attachment( 'pk-set-author.jpg', 'image/jpeg' );
		( new Kind_Post( $image ) )->set( 'author', $card );
		$this->assertSame( $card, get_post_meta( $image, 'mf2_author', true ) );

		$post = self::factory()->post->create();
		$this->assertNull( ( new Kind_Post( $post ) )->set( 'author', $card ) );
		$this->assertSame( '', get_post_meta( $post, 'mf2_author', true ) );
	}

	public function test_set_featured() {
		$post      = self::factory()->post->create();
		$image     = $this->attachment( 'pk-set-featured.jpg', 'image/jpeg', $post );
		$kind_post = new Kind_Post( $post );

		$this->assertFalse( $kind_post->set( 'featured', 'https://example.com/elsewhere.jpg' ) );
		$this->assertFalse( has_post_thumbnail( $post ) );

		$kind_post->set( 'featured', wp_get_attachment_url( $image ) );
		$this->assertSame( $image, get_post_thumbnail_id( $post ) );
	}

	public function test_set_name_summary_and_content() {
		$post      = self::factory()->post->create();
		$kind_post = new Kind_Post( $post );

		$kind_post->set( 'name', 'New Title' );
		$kind_post->set(
			'summary',
			array(
				'html'  => '<b>Short</b>',
				'value' => 'Short',
			)
		);
		$kind_post->set( 'content', array( 'The body' ) );

		$updated = get_post( $post );
		$this->assertSame( 'New Title', $updated->post_title );
		$this->assertSame( '<b>Short</b>', $updated->post_excerpt );
		$this->assertSame( 'The body', $updated->post_content );
	}

	public function test_set_photo_as_a_citation_updates_the_attachment() {
		$post  = self::factory()->post->create();
		$image = $this->attachment( 'pk-set-photo.jpg', 'image/jpeg' );
		$url   = wp_get_attachment_url( $image );

		( new Kind_Post( $post ) )->set(
			'photo',
			array(
				'type'       => array( 'h-cite' ),
				'properties' => array(
					'url'  => array( $url ),
					'name' => array( 'Harbour at dusk' ),
				),
			)
		);
		$this->assertSame( array( $url ), get_post_meta( $post, 'mf2_photo', true ) );
		$this->assertSame( 'Harbour at dusk', get_post( $image )->post_title );

		// A citation of media that isn't in the library only stores the URL.
		( new Kind_Post( $post ) )->set(
			'video',
			array(
				'type'       => array( 'h-cite' ),
				'properties' => array( 'url' => array( 'https://example.com/v.mp4' ) ),
			)
		);
		$this->assertSame( array( 'https://example.com/v.mp4' ), get_post_meta( $post, 'mf2_video', true ) );
	}
}
