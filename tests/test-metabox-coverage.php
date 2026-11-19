<?php

/**
 * Kind_Metabox: hooks, kind changes, empty content, the editor screen and
 * the less common save paths (#521).
 */
class MetaboxCoverageTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		Kind_Taxonomy::kind_defaultterms();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tear_down() {
		$_POST = array();
		unset( $GLOBALS['current_screen'], $GLOBALS['wp_meta_boxes'] );
		wp_dequeue_script( 'kindmeta' );
		parent::tear_down();
	}

	/**
	 * Saves the metabox for a new post of a kind, with the fields slashed as WordPress posts them.
	 *
	 * @param string $kind   Kind slug.
	 * @param array  $fields Posted fields.
	 * @return int Post ID.
	 */
	private function save( $kind, $fields ) {
		$id = self::factory()->post->create();
		set_post_kind( $id, $kind );
		$_POST = wp_slash( array_merge( array( 'replykind_metabox_nonce' => wp_create_nonce( 'replykind_metabox' ) ), $fields ) );
		Kind_Metabox::save_post( $id, get_post( $id ) );
		return $id;
	}

	public function test_init_adds_the_hooks() {
		Kind_Metabox::init();
		$this->assertSame( Post_Kinds_Plugin::$version, Kind_Metabox::$version );
		$this->assertSame( 10, has_action( 'edit_form_after_title', array( 'Kind_Metabox', 'after_title_metabox' ) ) );
		$this->assertSame( 10, has_action( 'load-post.php', array( 'Kind_Metabox', 'kindbox_setup' ) ) );
		$this->assertSame( 10, has_action( 'load-post-new.php', array( 'Kind_Metabox', 'kindbox_setup' ) ) );
		$this->assertSame( 8, has_action( 'save_post', array( 'Kind_Metabox', 'save_post' ) ) );
		$this->assertSame( 5, has_action( 'transition_post_status', array( 'Kind_Metabox', 'transition_post_status' ) ) );
		$this->assertSame( 11, has_filter( 'wp_insert_post_empty_content', array( 'Kind_Metabox', 'wp_insert_post_empty_content' ) ) );
		$this->assertSame( 10, has_action( 'change_kind', array( 'Kind_Metabox', 'change_kind' ) ) );
	}

	public function test_change_kind_moves_the_citation() {
		$post = self::factory()->post->create();
		update_post_meta( $post, 'mf2_in-reply-to', array( 'https://example.com/post' ) );

		Kind_Metabox::change_kind( $post, '', 'like' );
		Kind_Metabox::change_kind( $post, 'reply', 'reply' );
		$this->assertSame( array( 'https://example.com/post' ), get_post_meta( $post, 'mf2_in-reply-to', true ) );

		Kind_Metabox::change_kind( $post, 'reply', 'like' );
		$this->assertSame( '', get_post_meta( $post, 'mf2_in-reply-to', true ) );
		$this->assertSame( 'https://example.com/post', get_post_meta( $post, 'mf2_like-of', true ) );
	}

	public function test_empty_content_rules() {
		$this->assertFalse( Kind_Metabox::wp_insert_post_empty_content( true, array( 'post_status' => 'trash' ) ) );

		$new_reply = array( 'tax_input' => array( 'kind' => 'reply' ) );
		$this->assertTrue( Kind_Metabox::wp_insert_post_empty_content( true, $new_reply ) );
		$_POST['cite_url'] = 'https://example.com/post';
		$this->assertFalse( Kind_Metabox::wp_insert_post_empty_content( true, $new_reply ) );
	}

	public function test_response_properties_box_after_the_title() {
		$post = self::factory()->post->create();
		set_post_kind( $post, 'reply' );
		$GLOBALS['post'] = get_post( $post );
		set_current_screen( 'post' );

		Kind_Metabox::add_meta_boxes();
		ob_start();
		Kind_Metabox::after_title_metabox( get_post( $post ) );
		$html = ob_get_clean();
		unset( $GLOBALS['post'] );

		// How core renders the box differs between versions; check the registration and its container.
		$box = $GLOBALS['wp_meta_boxes']['post']['kind_after_title']['default']['replybox-meta'];
		$this->assertSame( array( 'Kind_Metabox', 'reply_metabox' ), $box['callback'] );
		$this->assertFalse( $box['args']['__block_editor_compatible_meta_box'] );
		$this->assertStringContainsString( 'id="kind_after_title-sortables"', $html );
	}

	public function test_editor_scripts_only_on_the_post_screen() {
		set_current_screen( 'edit-post' );
		Kind_Metabox::enqueue_admin_scripts();
		$this->assertFalse( wp_script_is( 'kindmeta', 'enqueued' ) );

		set_current_screen( 'post' );
		Kind_Metabox::enqueue_admin_scripts();
		$this->assertTrue( wp_script_is( 'kindmeta', 'enqueued' ) );
		$this->assertStringContainsString( 'var PKAPI', wp_scripts()->get_data( 'kindmeta', 'data' ) );
		$this->assertStringContainsString( 'api_nonce', wp_scripts()->get_data( 'kindmeta', 'data' ) );
	}

	public function test_implode_and_explode_pass_other_values_through() {
		$this->assertSame( 'plain', Kind_Metabox::implode( 'plain' ) );
		$this->assertSame( 'a;b', Kind_Metabox::implode( array( 'a', 'b' ) ) );
		$this->assertSame( array( 'a' ), Kind_Metabox::explode( array( 'a' ) ) );
		$this->assertSame( array( 'a', 'b' ), Kind_Metabox::explode( 'a;b' ) );
	}

	public function test_save_needs_permission() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$post = $this->save( 'reply', array( 'cite_url' => 'https://example.com/post' ) );
		$this->assertSame( '', get_post_meta( $post, 'mf2_in-reply-to', true ) );

		$page = $this->save( 'reply', array( 'post_type' => 'page', 'cite_url' => 'https://example.com/post' ) );
		$this->assertSame( '', get_post_meta( $page, 'mf2_in-reply-to', true ) );
	}

	public function test_start_and_end_give_a_duration() {
		$post = $this->save(
			'watch',
			array(
				'mf2_start_date'   => '2024-05-06',
				'mf2_start_time'   => '18:00:00',
				'mf2_start_offset' => '+00:00',
				'mf2_end_date'     => '2024-05-06',
				'mf2_end_time'     => '19:30:00',
				'mf2_end_offset'   => '+00:00',
				'cite_url'         => 'https://example.com/film',
			)
		);
		$this->assertSame( 'PT1H30M', get_post_meta( $post, 'mf2_duration', true ) );
	}

	public function test_empty_end_is_deleted() {
		$post = self::factory()->post->create();
		set_post_kind( $post, 'event' );
		update_post_meta( $post, 'mf2_end', array( '2024-05-06T21:00:00+00:00' ) );
		$_POST = wp_slash(
			array(
				'replykind_metabox_nonce' => wp_create_nonce( 'replykind_metabox' ),
				'mf2_end_date'            => '',
				'mf2_end_time'            => '',
			)
		);
		Kind_Metabox::save_post( $post, get_post( $post ) );
		$this->assertSame( '', get_post_meta( $post, 'mf2_end', true ) );
	}

	public function test_cite_published_and_updated_times() {
		$post = $this->save(
			'bookmark',
			array(
				'cite_url'              => 'https://example.com/post',
				'cite_name'             => 'A Post',
				'cite_published_date'   => '2024-01-02',
				'cite_published_time'   => '03:04:05',
				'cite_published_offset' => '+00:00',
				'cite_updated_date'     => '2024-02-03',
				'cite_updated_time'     => '04:05:06',
				'cite_updated_offset'   => '-05:00',
			)
		);
		$cite = get_post_meta( $post, 'mf2_bookmark-of', true );
		$this->assertSame( array( '2024-01-02T03:04:05+00:00' ), $cite['properties']['published'] );
		$this->assertSame( array( '2024-02-03T04:05:06-05:00' ), $cite['properties']['updated'] );
	}

	public function test_food_citation_is_an_h_food() {
		$post = $this->save( 'eat', array( 'cite_name' => 'Pancakes' ) );
		$cite = get_post_meta( $post, 'mf2_ate', true );
		$this->assertSame( array( 'h-food' ), $cite['type'] );
		$this->assertSame( array( 'Pancakes' ), $cite['properties']['name'] );
	}
}
