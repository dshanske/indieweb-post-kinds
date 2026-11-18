<?php

/**
 * Kind_Plugins: hooks and the smaller integrations (Tempus, ActivityPub, Hum,
 * Semantic Linkbacks), plus Micropub edge cases (#521).
 */
class PluginsCoverageTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
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

	public function test_init_adds_the_hooks() {
		Kind_Plugins::init();
		$this->assertSame( 10, has_filter( 'micropub_tax_input', array( 'Kind_Plugins', 'micropub_tax_input' ) ) );
		$this->assertSame( 9, has_action( 'after_micropub', array( 'Kind_Plugins', 'micropub_set_kind' ) ) );
		$this->assertSame( 11, has_action( 'after_micropub', array( 'Kind_Plugins', 'post_formats' ) ) );
		$this->assertSame( 11, has_filter( 'before_micropub', array( 'Kind_Plugins', 'micropub_parse' ) ) );
		$this->assertSame( 20, has_action( 'after_micropub', array( 'Kind_Plugins', 'schedule_enrichment' ) ) );
		$this->assertSame( 10, has_action( Kind_Plugins::ENRICH_EVENT, array( 'Kind_Plugins', 'enrich_citation' ) ) );
		$this->assertSame( 10, has_filter( 'micropub_query', array( 'Kind_Plugins', 'micropub_query' ) ) );
		$this->assertSame( 10, has_filter( 'micropub_dynamic_render', '__return_false' ) );
		$this->assertSame( 11, has_filter( 'semantic_linkbacks_post_type', array( 'Kind_Plugins', 'semantic_post_type' ) ) );
		$this->assertSame( 11, has_action( 'hum_type_prefix', array( 'Kind_Plugins', 'hum_type_prefix' ) ) );
		$this->assertSame( 12, has_filter( 'activitypub_post', array( 'Kind_Plugins', 'activitypub_post' ) ) );
	}

	public function test_tempus_widget_post_title() {
		$post = self::factory()->post->create( array( 'post_title' => 'A Title' ) );
		$this->assertSame( 'A Title', Kind_Plugins::tempus_widget_post_title( 'ignored', get_post( $post ) ) );
	}

	public function activitypub_types() {
		return array(
			'note'    => array( 'note', 'Note' ),
			'photo'   => array( 'photo', 'Image' ),
			'video'   => array( 'video', 'Video' ),
			'audio'   => array( 'audio', 'Audio' ),
			'article' => array( 'article', 'Article' ),
			'like'    => array( 'like', 'Unchanged' ),
		);
	}

	/**
	 * @dataProvider activitypub_types
	 */
	public function test_activitypub_object_type( $kind, $type ) {
		$post   = $this->kind_post( $kind );
		$object = Kind_Plugins::activitypub_post(
			array(
				'id'   => get_permalink( $post ),
				'type' => 'Unchanged',
			)
		);
		$this->assertSame( $type, $object['type'] );
	}

	public function test_activitypub_reply_is_in_reply_to_the_cited_url() {
		$post = $this->kind_post( 'reply' );
		( new Kind_Post( $post ) )->set(
			'in-reply-to',
			array(
				'type' => 'cite',
				'url'  => 'https://example.com/post',
			)
		);
		$object = Kind_Plugins::activitypub_post( array( 'id' => get_permalink( $post ) ) );
		$this->assertSame( 'Note', $object['type'] );
		$this->assertSame( 'https://example.com/post', $object['inReplyTo'] );
	}

	public function test_activitypub_leaves_pages_alone() {
		$page   = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$object = array(
			'id'   => get_permalink( $page ),
			'type' => 'Page',
		);
		$this->assertSame( $object, Kind_Plugins::activitypub_post( $object ) );
	}

	public function test_hum_local_types() {
		$this->assertSame( array( 'b', 'f', 'e', 'g', 'h', 'm', 'q', 'r', 'x', 'u' ), Kind_Plugins::hum_local_types( array( 'b' ) ) );
	}

	public function test_hum_type_prefix() {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->assertSame( 'p', Kind_Plugins::hum_type_prefix( 'p', $page ) );

		$this->assertSame( 't', Kind_Plugins::hum_type_prefix( 'p', $this->kind_post( 'note' ) ) );

		$without = null;
		foreach ( Kind_Taxonomy::get_kind_list() as $kind ) {
			if ( ! Kind_Taxonomy::get_kind_info( $kind, 'shortlink' ) ) {
				$without = $kind;
				break;
			}
		}
		$this->assertNotNull( $without );
		$this->assertSame( 'p', Kind_Plugins::hum_type_prefix( 'p', $this->kind_post( $without ) ) );
	}

	public function test_semantic_post_type() {
		$this->assertSame( 'this reply', Kind_Plugins::semantic_post_type( 'post', $this->kind_post( 'reply' ) ) );
	}

	public function test_micropub_hooks_without_a_post() {
		$this->assertNull( Kind_Plugins::micropub_set_kind( array( 'type' => array( 'h-entry' ) ), array() ) );
		$this->assertNull( Kind_Plugins::post_formats( array(), array() ) );
		$this->assertNull( Kind_Plugins::schedule_enrichment( array(), array() ) );
	}

	public function test_stored_mf2_skips_empty_properties_and_includes_content() {
		$post = self::factory()->post->create(
			array(
				'post_title'   => '',
				'post_content' => 'Body text',
			)
		);
		update_post_meta( $post, 'mf2_like-of', array() );
		update_post_meta( $post, 'mf2_category', array( 'one' ) );

		$mf2 = Kind_Plugins::stored_mf2( $post );
		$this->assertSame( array( 'h-entry' ), $mf2['type'] );
		$this->assertSame(
			array(
				'category' => array( 'one' ),
				'content'  => array( 'Body text' ),
			),
			$mf2['properties']
		);
	}

	public function test_server_errors_are_temporary_failures() {
		foreach ( array( 429, 500, 503 ) as $code ) {
			$error = new WP_Error( 'http_status', 'Failed', array( 'response_code' => $code ) );
			$this->assertSame( 'temporary', Kind_Plugins::failure_type( $error ), $code );
		}
	}

	public function test_enrich_citation_of_a_missing_post_does_nothing() {
		$requests = 0;
		$count    = function ( $pre ) use ( &$requests ) {
			++$requests;
			return $pre;
		};
		add_filter( 'pre_http_request', $count );
		Kind_Plugins::enrich_citation( PHP_INT_MAX, 'in-reply-to', 'https://example.com/post', 1 );
		remove_filter( 'pre_http_request', $count );
		$this->assertSame( 0, $requests );
	}
}
