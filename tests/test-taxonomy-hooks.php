<?php

/**
 * Kind_Taxonomy: archive titles, query filters, templates, links and error paths (#521).
 */
class TaxonomyHooksTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		Kind_Taxonomy::kind_defaultterms();
	}

	public function tear_down() {
		$_GET = array();
		delete_option( 'kind_title' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/**
	 * Creates a published post of a kind.
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

	/**
	 * A citation stored for the reply kind.
	 *
	 * @param string $url Cited URL.
	 * @return array
	 */
	private function reply_cite( $url ) {
		return array(
			'mf2_in-reply-to' => array(
				array(
					'type'       => array( 'h-cite' ),
					'properties' => array(
						'name' => array( 'Cited Post' ),
						'url'  => array( $url ),
					),
				),
			),
		);
	}

	public function test_init_adds_the_hooks() {
		Kind_Taxonomy::init();
		$this->assertSame( 10, has_filter( 'get_the_archive_title', array( 'Kind_Taxonomy', 'kind_archive_title' ) ) );
		$this->assertSame( 10, has_filter( 'document_title_parts', array( 'Kind_Taxonomy', 'document_title_parts' ) ) );
		$this->assertSame( 10, has_filter( 'post_link', array( 'Kind_Taxonomy', 'kind_permalink' ) ) );
		$this->assertSame( 99, has_action( 'pre_get_posts', array( 'Kind_Taxonomy', 'kind_firehose_query' ) ) );
		$this->assertSame( 11, has_filter( 'webmention_links', array( 'Kind_Taxonomy', 'webmention_links' ) ) );
		$this->assertSame( 99, has_action( 'save_post', array( 'Kind_Taxonomy', 'post_formats' ) ) );
		$this->assertSame( 9, has_filter( 'the_title', array( 'Kind_Taxonomy', 'the_title' ) ) );
		$this->assertSame( 10, has_action( 'rest_api_init', array( 'Kind_Taxonomy', 'register_routes' ) ) );
		$this->assertSame( 10, has_filter( 'template_include', array( 'Kind_Taxonomy', 'template_include' ) ) );
	}

	public function test_kind_archive_title_description_and_document_title() {
		$this->kind_post( 'reply' );
		$this->go_to( get_term_link( 'reply', 'kind' ) );
		$this->assertTrue( is_tax( 'kind' ) );

		$this->assertSame( 'Kind: <span>Replies</span>', get_the_archive_title() );
		$this->assertSame( Kind_Taxonomy::get_kind_info( 'reply', 'description' ), get_the_archive_description() );
		$this->assertSame( array( 'title' => 'Replies' ), Kind_Taxonomy::document_title_parts( array( 'title' => 'Reply' ) ) );
	}

	public function test_archive_of_several_kinds() {
		$this->kind_post( 'reply' );
		$this->kind_post( 'like' );
		$this->go_to( add_query_arg( 'kind', 'reply,like', home_url( '/' ) ) );

		// The order of the queried terms differs between WordPress versions.
		$slugs = wp_list_pluck( Kind_Taxonomy::get_terms_from_query(), 'slug' );
		$this->assertEqualSets( array( 'reply', 'like' ), $slugs );
		$names        = array();
		$descriptions = array();
		foreach ( $slugs as $slug ) {
			$names[]        = Kind_Taxonomy::get_kind_info( $slug, 'name' );
			$descriptions[] = Kind_Taxonomy::get_kind_info( $slug, 'description' );
		}

		$this->assertSame( 'Kinds: <span>' . implode( ', ', $names ) . '</span>', get_the_archive_title() );
		$this->assertSame( 'Kinds:', Kind_Taxonomy::kind_archive_prefix( 'Kind:' ) );
		$this->assertSame( implode( ', ', $names ), Kind_Taxonomy::document_title_parts( array( 'title' => 'x' ) )['title'] );
		$this->assertSame( implode( '<br />', $descriptions ), Kind_Taxonomy::kind_archive_description( '' ) );
	}

	public function test_dated_kind_archive_titles() {
		$this->kind_post( 'reply', array( 'post_date' => '2024-03-05 10:00:00' ) );

		$this->go_to( add_query_arg( array( 'kind' => 'reply', 'year' => 2024 ), home_url( '/' ) ) );
		$GLOBALS['wp_query']->the_post();
		$this->assertSame( 'Kind: <span>Replies - 2024</span>', get_the_archive_title() );

		$this->go_to( add_query_arg( array( 'kind' => 'reply', 'm' => 202403 ), home_url( '/' ) ) );
		$GLOBALS['wp_query']->the_post();
		$this->assertSame( 'Kind: <span>Replies - March 2024</span>', get_the_archive_title() );

		$this->go_to( add_query_arg( array( 'kind' => 'reply', 'm' => 20240305 ), home_url( '/' ) ) );
		$GLOBALS['wp_query']->the_post();
		$this->assertSame( 'Kind: <span>Replies - March 5, 2024</span>', get_the_archive_title() );
		wp_reset_postdata();
	}

	public function test_category_archive_keeps_the_category_name() {
		$category = self::factory()->category->create( array( 'name' => 'Travel' ) );
		self::factory()->post->create( array( 'post_category' => array( $category ) ) );
		// By slug; archives requested by ID are #525.
		$this->go_to( add_query_arg( 'category_name', 'travel', home_url( '/' ) ) );

		$this->assertSame( 'Category: <span>Travel</span>', get_the_archive_title() );
		$this->assertSame( 'Travel', Kind_Taxonomy::document_title_parts( array( 'title' => 'x' ) )['title'] );
		// Only kind archives get the kind's description.
		$this->assertSame( 'original', Kind_Taxonomy::kind_archive_description( 'original' ) );
	}

	public function test_category_archive_requested_by_id() {
		$this->markTestSkipped( 'Category and tag archives requested by ID get an empty title and PHP warnings (#525).' );

		$category = self::factory()->category->create( array( 'name' => 'Travel' ) );
		self::factory()->post->create( array( 'post_category' => array( $category ) ) );
		$this->go_to( add_query_arg( 'cat', $category, home_url( '/' ) ) );
		$this->assertSame( 'Category: <span>Travel</span>', get_the_archive_title() );
	}

	public function test_titles_outside_taxonomy_archives() {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( 'Prefix:', Kind_Taxonomy::kind_archive_prefix( 'Prefix:' ) );
		$this->assertSame( 'Archives', Kind_Taxonomy::kind_archive_title( 'Archives' ) );
		$this->assertSame( array( 'title' => 'Home' ), Kind_Taxonomy::document_title_parts( array( 'title' => 'Home' ) ) );
		$this->assertSame( 'Blog', Kind_Taxonomy::kind_archive_description( 'Blog' ) );
		$this->assertSame( array(), Kind_Taxonomy::get_terms_from_query() );
	}

	public function test_firehose_archive_title_and_query() {
		$this->kind_post( 'reply' );
		$this->go_to( add_query_arg( 'kind_firehose', 1, home_url( '/' ) ) );

		$this->assertTrue( is_archive() );
		$this->assertFalse( is_home() );
		$this->assertSame( 'Archives - Firehose', Kind_Taxonomy::kind_archive_title( 'Archives' ) );
	}

	public function test_exclude_query_removes_kinds() {
		$reply = $this->kind_post( 'reply' );
		$like  = $this->kind_post( 'like' );
		$note  = $this->kind_post( 'note' );
		$this->go_to( add_query_arg( array( 'exclude' => 'kind', 'exclude_terms' => 'reply,like' ), home_url( '/' ) ) );

		$this->assertSame(
			array(
				array(
					'taxonomy' => 'kind',
					'field'    => 'slug',
					'terms'    => array( 'reply', 'like' ),
					'operator' => 'NOT IN',
				),
			),
			get_query_var( 'tax_query' )
		);
		$ids = wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' );
		$this->assertContains( $note, $ids );
		$this->assertNotContains( $reply, $ids );
		$this->assertNotContains( $like, $ids );
	}

	public function test_exclude_query_without_terms_is_ignored() {
		$this->go_to( add_query_arg( 'exclude', 'kind', home_url( '/' ) ) );
		$this->assertEmpty( get_query_var( 'tax_query' ) );
	}

	/**
	 * @dataProvider kind_aliases
	 */
	public function test_kind_aliases( $alias, $kinds ) {
		$this->go_to( add_query_arg( 'kind', $alias, home_url( '/' ) ) );
		$this->assertSame( $kinds, get_query_var( 'kind' ) );
	}

	public function kind_aliases() {
		return array(
			'food'     => array( 'food', 'eat,drink' ),
			'reaction' => array( 'reaction', 'bookmark,repost,like,favorite' ),
			'media'    => array( 'media', 'watch,read,listen,play' ),
			'a kind'   => array( 'reply', 'reply' ),
		);
	}

	public function test_template_include() {
		$post = $this->kind_post( 'photo' );

		$this->go_to( get_permalink( $post ) );
		$this->assertSame( 'single.php', Kind_Taxonomy::template_include( 'single.php' ) );

		$this->go_to( home_url( '/' ) );
		$this->assertSame( 'index.php', Kind_Taxonomy::template_include( 'index.php' ) );

		$this->go_to( add_query_arg( 'kind_photos', 1, home_url( '/' ) ) );
		$this->assertSame( 'index.php', Kind_Taxonomy::template_include( 'index.php' ) );

		// A theme's kind-photos.php is used for the photo archive.
		$file = get_stylesheet_directory() . '/kind-photos.php';
		file_put_contents( $file, '<?php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		try {
			$this->assertSame( $file, Kind_Taxonomy::template_include( 'index.php' ) );
		} finally {
			unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	public function test_embed_template_hierarchy() {
		$post = $this->kind_post( 'reply' );
		$this->go_to( get_permalink( $post ) );
		$templates = Kind_Taxonomy::embed_template_hierarchy( array( 'embed.php' ) );
		$this->assertCount( 2, $templates );
		$this->assertSame( 'embed.php', $templates[1] );

		$this->go_to( home_url( '/' ) );
		$this->assertSame( array( 'embed.php' ), Kind_Taxonomy::embed_template_hierarchy( array( 'embed.php' ) ) );
	}

	public function test_embed_template_uses_the_kind_slug() {
		$this->markTestSkipped( 'The embed template hierarchy uses the kind\'s translated name instead of its slug (#526).' );

		$post = $this->kind_post( 'reply' );
		$this->go_to( get_permalink( $post ) );
		$this->assertSame( array( 'embed-post-reply.php', 'embed.php' ), Kind_Taxonomy::embed_template_hierarchy( array( 'embed.php' ) ) );
	}

	public function test_sample_permalink_of_an_untitled_draft_uses_the_content() {
		$post = self::factory()->post->create(
			array(
				'post_status'  => 'draft',
				'post_title'   => '',
				'post_content' => 'Hello world from an untitled note',
				'post_excerpt' => '',
			)
		);
		$permalink = array( home_url( '/%postname%/' ), (string) $post );
		$result    = Kind_Taxonomy::get_sample_permalink( $permalink, $post, '', '', get_post( $post ) );
		$this->assertSame( 'hello-world-from-an-untitled-note', $result[1] );

		// A permalink that isn't the post ID is left alone.
		$permalink = array( home_url( '/%postname%/' ), 'my-slug' );
		$this->assertSame( $permalink, Kind_Taxonomy::get_sample_permalink( $permalink, $post, '', '', get_post( $post ) ) );
	}

	public function test_untitled_post_in_the_admin_gets_a_generated_title() {
		set_current_screen( 'edit-post' );
		$post = $this->kind_post( 'note', array( 'post_title' => '', 'post_content' => 'A short note', 'post_excerpt' => '' ) );
		$this->assertSame( 'A short note', Kind_Taxonomy::the_title( '', $post ) );

		// With no content either, a placeholder keeps the row clickable.
		$empty = $this->kind_post( 'note', array( 'post_title' => '', 'post_content' => '', 'post_excerpt' => '' ) );
		$this->assertSame( '&diams;', Kind_Taxonomy::the_title( '', $empty ) );
	}

	public function test_single_post_title() {
		$post = $this->kind_post( 'note', array( 'post_title' => '', 'post_content' => 'Generated from content', 'post_excerpt' => '' ) );

		$this->go_to( home_url( '/' ) );
		$this->assertSame( 'Title', Kind_Taxonomy::single_post_title( 'Title', get_post( $post ) ) );

		$this->go_to( get_permalink( $post ) );
		$this->assertSame( 'Generated from content', Kind_Taxonomy::single_post_title( '', get_post( $post ) ) );

		update_option( 'kind_title', 1 );
		$this->assertSame( '[Note] Title', Kind_Taxonomy::single_post_title( 'Title', get_post( $post ) ) );
	}

	public function test_generate_title_edge_cases() {
		$this->assertNull( Kind_Taxonomy::generate_title( PHP_INT_MAX ) );
		$this->assertSame( '', Kind_Taxonomy::get_excerpt( null ) );
		$post = self::factory()->post->create(
			array(
				'post_excerpt' => 'The excerpt',
				'post_content' => 'The content',
			)
		);
		$this->assertSame( 'The excerpt', Kind_Taxonomy::get_excerpt( get_post( $post ) ) );
	}

	public function test_generate_permastruct_without_elements() {
		$this->assertSame( '', Kind_Taxonomy::generate_permastruct( array() ) );
		$this->assertSame( '', Kind_Taxonomy::generate_permastruct( 'photos' ) );
		$this->assertSame( 'photos/?$', Kind_Taxonomy::generate_permastruct( array( 'photos' ) ) );
	}

	public function test_kind_permalink_fallbacks() {
		$this->assertSame( '/%kind%/x/', Kind_Taxonomy::kind_permalink( '/%kind%/x/', PHP_INT_MAX, false ) );

		$post = self::factory()->post->create();
		wp_set_object_terms( $post, array(), 'kind' );
		$this->assertSame( '/note/x/', Kind_Taxonomy::kind_permalink( '/%kind%/x/', $post, false ) );
	}

	public function test_webmention_links_add_the_cited_url() {
		$post  = $this->kind_post( 'reply', array( 'meta_input' => $this->reply_cite( 'https://example.com/post' ) ) );
		$links = Kind_Taxonomy::webmention_links( array( 'https://example.org/linked' ), $post );
		$this->assertSame( array( 'https://example.org/linked', 'https://example.com/post' ), $links );

		$note = $this->kind_post( 'note' );
		$this->assertSame( array( 'https://example.org/linked' ), Kind_Taxonomy::webmention_links( array( 'https://example.org/linked' ), $note ) );
	}

	public function test_webmention_links_with_several_cited_urls() {
		$meta  = $this->reply_cite( 'https://example.com/one' );
		$meta['mf2_in-reply-to'][0]['properties']['url'][] = 'https://example.com/two';
		$post  = $this->kind_post( 'reply', array( 'meta_input' => $meta ) );
		$links = Kind_Taxonomy::webmention_links( array( 'https://example.com/one' ), $post );
		$this->assertSame( array( 'https://example.com/one', 'https://example.com/two' ), array_values( $links ) );
	}

	public function test_enclosure_links_only_for_media_kinds() {
		$photo = $this->kind_post( 'photo', array( 'meta_input' => array( 'mf2_photo' => array( 'https://example.com/photo.jpg' ) ) ) );
		$this->assertSame( array( 'https://example.com/photo.jpg' ), Kind_Taxonomy::enclosure_links( array(), $photo ) );

		$meta  = array(
			'mf2_photo' => array(
				array(
					'type'       => array( 'h-cite' ),
					'properties' => array( 'url' => array( 'https://example.com/a.jpg', 'https://example.com/b.jpg' ) ),
				),
			),
		);
		$photo = $this->kind_post( 'photo', array( 'meta_input' => $meta ) );
		$this->assertSame( array( 'https://example.com/a.jpg', 'https://example.com/b.jpg' ), array_values( Kind_Taxonomy::enclosure_links( array( 'https://example.com/a.jpg' ), $photo ) ) );

		$reply = $this->kind_post( 'reply', array( 'meta_input' => $this->reply_cite( 'https://example.com/post' ) ) );
		$this->assertSame( array(), Kind_Taxonomy::enclosure_links( array(), $reply ) );
	}

	public function test_kind_dropdown_on_the_posts_screen() {
		$this->kind_post( 'reply' );
		$_GET['kind'] = 'reply';
		ob_start();
		Kind_Taxonomy::kind_dropdown( 'post', 'top' );
		$html = ob_get_clean();
		$this->assertStringContainsString( "name='kind'", $html );
		$this->assertStringContainsString( 'All Kinds', $html );
		$this->assertMatchesRegularExpression( '/value="reply"\s+selected/', $html );

		ob_start();
		Kind_Taxonomy::kind_dropdown( 'page', 'top' );
		$this->assertSame( '', ob_get_clean() );
	}

	public function test_select_metabox_preselects_the_requested_kind() {
		$post = self::factory()->post->create( array( 'post_status' => 'auto-draft' ) );
		wp_set_object_terms( $post, array(), 'kind' );
		$reply = get_term_by( 'slug', 'reply', 'kind' );

		$_GET['kind'] = 'reply';
		ob_start();
		Kind_Taxonomy::select_metabox( get_post( $post ) );
		$html = ob_get_clean();
		$this->assertMatchesRegularExpression( '/id="in-kind-' . $reply->term_id . '" name="tax_input\[kind\]" value="reply"\s+checked/', $html );
	}

	public function test_select_metabox_always_offers_note_and_falls_back_on_the_default_kind() {
		update_option( 'kind_default', 'article' );
		$post = self::factory()->post->create( array( 'post_status' => 'auto-draft' ) );
		wp_set_object_terms( $post, array(), 'kind' );
		$article = get_term_by( 'slug', 'article', 'kind' );

		$remove_note = function ( $kinds ) {
			return array_diff( $kinds, array( 'note' ) );
		};
		add_filter( 'kind_include', $remove_note );
		ob_start();
		Kind_Taxonomy::select_metabox( get_post( $post ) );
		$html = ob_get_clean();
		remove_filter( 'kind_include', $remove_note );
		delete_option( 'kind_default' );

		$this->assertStringContainsString( 'value="note"', $html );
		$this->assertMatchesRegularExpression( '/id="in-kind-' . $article->term_id . '" name="tax_input\[kind\]" value="article"\s+checked/', $html );
	}

	public function test_post_class() {
		$post = $this->kind_post( 'like' );
		$this->go_to( get_permalink( $post ) );
		$this->assertSame( array( 'a', 'kind-like' ), Kind_Taxonomy::post_class( array( 'a' ) ) );

		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page ) );
		$this->assertSame( array( 'a' ), Kind_Taxonomy::post_class( array( 'a' ) ) );
	}

	public function test_unknown_kinds() {
		$this->assertFalse( Kind_Taxonomy::get_post_kind_info( 'no-such-kind' ) );
		$this->assertFalse( Kind_Taxonomy::get_post_kind_link( 'no-such-kind' ) );
		$this->assertFalse( Kind_Taxonomy::get_post_kind_count( 'no-such-kind' ) );
		$this->assertFalse( Kind_Taxonomy::set_post_kind_visibility( 'no-such-kind' ) );
	}

	public function test_set_post_kind_visibility() {
		$this->assertTrue( Kind_Taxonomy::set_post_kind_visibility( 'jam', false ) );
		$this->assertFalse( Kind_Taxonomy::get_post_kind_info( 'jam' )->show );
		$this->assertTrue( Kind_Taxonomy::set_post_kind_visibility( 'jam' ) );
		$this->assertTrue( Kind_Taxonomy::get_post_kind_info( 'jam' )->show );
	}

	public function test_set_post_kind_errors() {
		$this->assertWPError( Kind_Taxonomy::set_post_kind( PHP_INT_MAX, 'reply' ) );
		$post  = self::factory()->post->create();
		$error = Kind_Taxonomy::set_post_kind( $post, 'no-such-kind' );
		$this->assertWPError( $error );
		$this->assertSame( 'invalid_kind', $error->get_error_code() );
	}

	public function test_set_rest_post_kind_errors() {
		$error = Kind_Taxonomy::set_rest_post_kind( 'reply', array() );
		$this->assertSame( 'rest_invalid_post', $error->get_error_code() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$post  = self::factory()->post->create();
		$error = Kind_Taxonomy::set_rest_post_kind( 'no-such-kind', get_post( $post ) );
		$this->assertSame( 'invalid_kind', $error->get_error_code() );
		$this->assertSame( 400, $error->get_error_data()['status'] );
	}

	public function test_get_before_kind_when_hidden() {
		$this->assertSame( '', Kind_Taxonomy::get_before_kind( 'reply', 'hide' ) );
		add_filter( 'kind_icon_display', '__return_false' );
		$this->assertSame( '', Kind_Taxonomy::get_before_kind( 'reply', 'both' ) );
		remove_filter( 'kind_icon_display', '__return_false' );
	}
}
