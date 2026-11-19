<?php

/**
 * Kind_View: hooks, content filters, feeds, embeds and markup helpers (#521).
 */
class ViewHelpersTest extends WP_UnitTestCase {
	const EMBED_URL = 'https://www.youtube.com/watch?v=pkTest';

	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		Kind_Taxonomy::kind_defaultterms();
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
		delete_option( 'kind_bottom' );
		delete_option( 'kind_embeds' );
		unset( $GLOBALS['current_screen'], $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Creates a reply citing a URL.
	 *
	 * @param string $url  Cited URL.
	 * @param array  $args Post arguments.
	 * @return int Post ID.
	 */
	private function reply( $url = 'https://example.com/post', $args = array() ) {
		$post = self::factory()->post->create( array_merge( array( 'post_content' => 'My reply' ), $args ) );
		set_post_kind( $post, 'reply' );
		( new Kind_Post( $post ) )->set(
			'in-reply-to',
			array(
				'type' => 'cite',
				'name' => 'The Post',
				'url'  => $url,
			)
		);
		return $post;
	}

	public function test_init_adds_the_filters() {
		Kind_View::init();
		$this->assertSame( 9, has_filter( 'the_content', array( 'Kind_View', 'content_response' ) ) );
		$this->assertSame( 9, has_filter( 'the_content_feed', array( 'Kind_View', 'content_feed_response' ) ) );
		$this->assertSame( 9, has_filter( 'the_excerpt', array( 'Kind_View', 'excerpt_response' ) ) );
		$this->assertSame( 10, has_filter( 'json_feed_item', array( 'Kind_View', 'json_feed_item' ) ) );
		$this->assertSame( 10, has_filter( 'wp_get_attachment_image_attributes', array( 'Kind_View', 'wp_get_attachment_image_attributes' ) ) );
	}

	public function test_kind_content_display_turns_the_content_filters_off() {
		remove_filter( 'the_content', array( 'Kind_View', 'content_response' ), 9 );
		add_filter( 'kind_content_display', '__return_false' );
		Kind_View::init();
		remove_filter( 'kind_content_display', '__return_false' );
		$off = has_filter( 'the_content', array( 'Kind_View', 'content_response' ) );
		Kind_View::init();

		$this->assertFalse( $off );
	}

	public function test_photo_attachments_get_u_photo() {
		$post  = self::factory()->post->create();
		$image = self::factory()->attachment->create_object( 'pk-view.jpg', $post, array( 'post_mime_type' => 'image/jpeg' ) );
		$image = get_post( $image );

		// Not a photo post.
		$this->assertSame( array(), Kind_View::wp_get_attachment_image_attributes( array(), $image ) );

		set_post_kind( $post, 'photo' );
		$this->assertSame( array( 'class' => 'u-photo' ), Kind_View::wp_get_attachment_image_attributes( array(), $image ) );
		$this->assertSame( array( 'class' => 'wp-image u-photo' ), Kind_View::wp_get_attachment_image_attributes( array( 'class' => 'wp-image u-photo' ), $image ) );
		$this->assertSame( array( 'class' => 'wp-image u-photo' ), Kind_View::wp_get_attachment_image_attributes( array( 'class' => 'wp-image' ), $image ) );
		// The list display's thumbnails are left alone.
		$this->assertSame( array( 'class' => 'kind-photo-thumbnail' ), Kind_View::wp_get_attachment_image_attributes( array( 'class' => 'kind-photo-thumbnail' ), $image ) );

		// Unattached media is left alone.
		$loose = get_post( self::factory()->attachment->create_object( 'pk-loose.jpg', 0, array( 'post_mime_type' => 'image/jpeg' ) ) );
		$this->assertSame( array(), Kind_View::wp_get_attachment_image_attributes( array(), $loose ) );
	}

	public function test_response_data_of_a_photo_post_cites_the_photo() {
		$post  = self::factory()->post->create();
		$image = self::factory()->attachment->create_object( 'pk-data.jpg', $post, array( 'post_mime_type' => 'image/jpeg', 'post_title' => 'Harbour at dusk' ) );
		set_post_kind( $post, 'photo' );

		$data = Kind_View::get_response_data( $post );
		$this->assertSame( array( $image ), $data['photos'] );
		$this->assertSame( 'Harbour at dusk', $data['cite']['name'] );
	}

	public function test_display_echoes_the_response() {
		$post = $this->reply();
		ob_start();
		Kind_View::display( $post );
		$this->assertStringContainsString( 'u-in-reply-to', ob_get_clean() );
	}

	public function test_content_response_is_left_out_in_the_admin_feeds_and_nested_content() {
		$post             = $this->reply();
		$GLOBALS['post'] = get_post( $post );

		set_current_screen( 'edit-post' );
		$this->assertSame( 'Content', Kind_View::content_response( 'Content' ) );
		unset( $GLOBALS['current_screen'] );

		$this->go_to( get_feed_link() );
		$GLOBALS['post'] = get_post( $post );
		$this->assertSame( 'Content', Kind_View::content_response( 'Content' ) );

		$this->go_to( get_permalink( $post ) );
		$GLOBALS['wp_current_filter'][] = 'the_content';
		$GLOBALS['wp_current_filter'][] = 'the_content';
		$nested                         = Kind_View::content_response( 'Content' );
		array_pop( $GLOBALS['wp_current_filter'] );
		array_pop( $GLOBALS['wp_current_filter'] );
		$this->assertSame( 'Content', $nested );
	}

	public function test_content_response_at_the_bottom() {
		$post = $this->reply();
		$this->go_to( get_permalink( $post ) );
		update_option( 'kind_bottom', 1 );
		$content = Kind_View::content_response( 'Content' );
		$this->assertStringStartsWith( 'Content', $content );
		$this->assertStringContainsString( 'u-in-reply-to', $content );
	}

	public function test_feed_content_response() {
		$GLOBALS['post'] = get_post( $this->reply() );

		$top = Kind_View::content_feed_response( 'Content', 'rss2' );
		$this->assertStringEndsWith( 'Content', $top );
		$this->assertStringContainsString( 'u-in-reply-to', $top );

		update_option( 'kind_bottom', 1 );
		$this->assertStringStartsWith( 'Content', Kind_View::content_feed_response( 'Content', 'rss2' ) );
	}

	public function test_json_feed_item_gets_the_cited_url() {
		$reply = $this->reply( 'https://example.com/cited' );
		$this->assertSame( array( 'external_url' => 'https://example.com/cited' ), Kind_View::json_feed_item( array(), get_post( $reply ) ) );

		$like = self::factory()->post->create();
		set_post_kind( $like, 'like' );
		update_post_meta( $like, 'mf2_like-of', array( 'https://example.com/one', 'https://example.com/two' ) );
		$this->assertSame( array( 'external_url' => 'https://example.com/two' ), Kind_View::json_feed_item( array(), get_post( $like ) ) );

		$note = self::factory()->post->create();
		set_post_kind( $note, 'note' );
		$this->assertSame( array( 'id' => 1 ), Kind_View::json_feed_item( array( 'id' => 1 ), get_post( $note ) ) );
	}

	public function test_excerpt_response() {
		unset( $GLOBALS['post'] );
		$this->assertSame( 'Excerpt', Kind_View::excerpt_response( 'Excerpt' ) );

		$post = $this->reply( 'https://example.com/post', array( 'post_excerpt' => '' ) );
		$this->go_to( get_permalink( $post ) );
		$excerpt = Kind_View::excerpt_response( 'Excerpt' );
		$this->assertStringContainsString( 'u-in-reply-to', $excerpt );
		$this->assertStringEndsWith( 'My reply', $excerpt );
	}

	public function test_markup_helpers() {
		$this->assertSame( '', Kind_View::get_attributes( null ) );
		$this->assertSame( ' class="a b" id="x"', Kind_View::get_attributes( array( 'class' => array( 'a', 'b', 'a' ), 'id' => array( 'x' ) ) ) );

		$this->assertSame( '', Kind_View::get_url_link( '' ) );
		$this->assertSame( '<a  class="u-url" href="https://example.com/">Example</a>', Kind_View::get_url_link( 'https://example.com/', 'Example', array( 'class' => array( 'u-url' ) ) ) );

		$this->assertSame( '<p class="c">Text</p>', Kind_View::get_formatted( 'Text', ' class="c"', 'p' ) );
	}

	public function test_get_embed() {
		update_option( 'kind_embeds', 1 );
		$this->assertSame( '<div class="kind-embed"><iframe class="pk-test-embed"></iframe><a class="u-url" href="https://www.youtube.com/watch?v=pkTest"></a></div>', Kind_View::get_embed( self::EMBED_URL ) );

		update_option( 'kind_embeds', 0 );
		$this->assertSame( '', Kind_View::get_embed( self::EMBED_URL ) );
	}

	public function test_get_embed_when_nothing_is_embedded() {
		update_option( 'kind_embeds', 1 );
		$block = function () {
			return new WP_Error( 'blocked', 'No requests in tests.' );
		};
		add_filter( 'pre_http_request', $block );
		$linkify                              = $GLOBALS['wp_embed']->linkifunknown;
		$GLOBALS['wp_embed']->linkifunknown = false;

		$embed = Kind_View::get_embed( 'https://vimeo.com/pk-test-nothing' );

		$GLOBALS['wp_embed']->linkifunknown = $linkify;
		remove_filter( 'pre_http_request', $block );
		$this->assertSame( '', $embed );
	}

	public function test_get_post_type_string() {
		$this->assertSame( ' ', Kind_View::get_post_type_string( null ) );
		$this->assertSame( ' ', Kind_View::get_post_type_string( 'not a url' ) );
		$this->assertSame( 'a tweet', Kind_View::get_post_type_string( 'https://twitter.com/someone/status/1' ) );
		$this->assertSame( 'a video', Kind_View::get_post_type_string( 'https://www.youtube.com/watch?v=1' ) );
		$this->assertSame( 'a post', Kind_View::get_post_type_string( 'https://example.com/post' ) );
	}

	public function test_get_hcard_variants() {
		$full = array(
			'type'  => 'card',
			'name'  => 'Ann',
			'url'   => 'https://example.com/ann',
			'photo' => 'https://example.com/ann.jpg',
		);

		$this->assertSame( '', Kind_View::get_hcard( array( 'type' => 'card', 'name' => '' ) ) );
		$this->assertSame( '<span class="h-card p-author">Ann</span>', Kind_View::get_hcard( $full, array( 'display' => 'name' ) ) );
		$this->assertSame( '<a class="h-card p-author" href="https://example.com/ann"><img class="u-photo" src="https://example.com/ann.jpg" alt="Ann" width="32" height="32" /></a>', Kind_View::get_hcard( $full, array( 'display' => 'photo' ) ) );
		$this->assertSame( '<img src="https://example.com/ann.jpg" class="h-card u-photo p-author" alt="Ann" width="20" height="20" />', Kind_View::get_hcard( array_merge( $full, array( 'url' => '' ) ), array( 'display' => 'photo', 'width' => 20, 'height' => 20 ) ) );
		$this->assertFalse( Kind_View::get_hcard( array_merge( $full, array( 'photo' => '' ) ), array( 'display' => 'photo' ) ) );
		$this->assertSame( '<span class="h-card p-author"><img src="https://example.com/ann.jpg" class="u-photo" alt="Ann" width="32" height="32" />Ann</span>', Kind_View::get_hcard( array_merge( $full, array( 'url' => '' ) ) ) );
		$this->assertFalse( Kind_View::get_hcard( $full, array( 'display' => 'sideways' ) ) );

		// A card with only a URL gets a generic name.
		$this->assertSame( '<a href="https://example.com/ann" class="h-card p-author">an author</a>', Kind_View::get_hcard( array( 'url' => 'https://example.com/ann' ) ) );
	}

	public function test_get_hcard_filters() {
		$not_a_card = function () {
			return 'not a card';
		};
		add_filter( 'get_hcard_data', $not_a_card );
		$this->assertFalse( Kind_View::get_hcard( array( 'name' => 'Ann' ) ) );
		remove_filter( 'get_hcard_data', $not_a_card );

		$custom = function () {
			return '<b>Custom</b>';
		};
		add_filter( 'get_hcard', $custom );
		$this->assertSame( '<b>Custom</b>', Kind_View::get_hcard( array( 'name' => 'Ann' ) ) );
		remove_filter( 'get_hcard', $custom );
	}

	public function test_cite_title_and_site_name_edge_cases() {
		$this->assertFalse( Kind_View::get_cite_title( array() ) );
		$this->assertSame( '', Kind_View::get_cite_title( array( 'summary' => 'No name or URL' ) ) );
		$this->assertFalse( Kind_View::get_site_name( 'Example' ) );
	}

	public function test_display_duration_with_years_months_and_days() {
		$this->assertSame( '<time class="dt-duration" datetime="P1Y2M3D">1 year 2 months 3 days</time>', Kind_View::display_duration( new DateInterval( 'P1Y2M3D' ) ) );
	}
}
