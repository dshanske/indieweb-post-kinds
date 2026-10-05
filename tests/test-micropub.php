<?php

class MicropubTest extends WP_UnitTestCase {
	const ENTRY = 'https://example.com/2026/01/01/an-entry/';
	const HOME  = 'https://example.com/';
	// Resolves in DNS (wp_http_validate_url() checks), but the mock fails the request.
	const DOWN  = 'https://example.net/down';
	const GONE  = 'https://example.com/missing/';
	const JSON  = 'https://example.com/data.json';

	/**
	 * Timeouts the mocked requests were made with, keyed by URL.
	 *
	 * @var array
	 */
	private $timeouts = array();

	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
		add_filter( 'pre_http_request', array( $this, 'mock_http' ), 10, 3 );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'mock_http' ), 10 );
		parent::tear_down();
	}

	/**
	 * Serves fixed pages instead of making network requests.
	 */
	public function mock_http( $pre, $args, $url ) {
		$this->timeouts[ $url ] = isset( $args['timeout'] ) ? $args['timeout'] : null;
		if ( self::JSON === $url ) {
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => '{"unrelated":"data"}',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}
		if ( self::GONE === $url ) {
			return array(
				'headers'  => array( 'content-type' => 'text/html' ),
				'body'     => 'Not found',
				'response' => array(
					'code'    => 404,
					'message' => 'Not Found',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}
		$pages = array(
			self::ENTRY => '<html><head><title>An Entry</title></head><body><article class="h-entry"><h1 class="p-name">An Entry</h1><a class="u-url" href="' . self::ENTRY . '">link</a><time class="dt-published" datetime="2026-01-01T10:00:00+00:00">Jan 1</time><a class="p-author h-card" href="https://example.com/">Jane Doe</a><div class="e-content"><p>The cited text.</p></div></article></body></html>',
			self::HOME  => '<html><head><title>Example Site</title></head><body><div class="h-feed"><h1 class="p-name">Example Site</h1><article class="h-entry"><a class="u-url" href="' . self::ENTRY . '">An Entry</a><div class="e-content">The cited text.</div></article></div></body></html>',
		);
		if ( ! isset( $pages[ $url ] ) ) {
			return new WP_Error( 'http_request_failed', 'Unreachable in tests' );
		}
		return array(
			'headers'  => array( 'content-type' => 'text/html; charset=UTF-8' ),
			'body'     => $pages[ $url ],
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Runs a callback, capturing the notices reported with wp_trigger_error().
	 *
	 * @return string[] The notices.
	 */
	private function capture_notices( $callback ) {
		$notices = array();
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			function ( $errno, $message ) use ( &$notices ) {
				$notices[] = $message;
				return true;
			},
			E_USER_NOTICE
		);
		try {
			$callback();
		} finally {
			restore_error_handler();
		}
		return $notices;
	}

	private function create_request( $properties ) {
		return array(
			'type'       => array( 'h-entry' ),
			'properties' => $properties,
		);
	}

	private function assert_no_bookkeeping( $value ) {
		if ( ! is_array( $value ) ) {
			return;
		}
		foreach ( $value as $key => $item ) {
			if ( is_string( $key ) ) {
				$this->assertStringStartsNotWith( '_', $key );
				$this->assertNotContains( $key, array( 'post-type', 'refs' ) );
			}
			$this->assert_no_bookkeeping( $item );
		}
	}

	public function test_url_is_enriched_into_a_clean_citation() {
		$input = Kind_Plugins::micropub_parse( $this->create_request( array( 'in-reply-to' => array( self::ENTRY ) ) ) );
		$cite  = $input['properties']['in-reply-to'][0];
		$this->assertSame( array( 'h-cite' ), $cite['type'] );
		$this->assertSame( array( self::ENTRY ), $cite['properties']['url'] );
		$this->assertSame( 'Jane Doe', \ParseThis\mf2_to_jf2( $cite )['author']['name'] );
		$this->assert_no_bookkeeping( $cite );
	}

	public function test_home_page_is_cited_as_the_page() {
		$input = Kind_Plugins::micropub_parse( $this->create_request( array( 'like-of' => array( self::HOME ) ) ) );
		$cite  = $input['properties']['like-of'][0];
		$this->assertSame( array( 'h-cite' ), $cite['type'] );
		$this->assertArrayNotHasKey( 'children', $cite );
		$this->assert_no_bookkeeping( $cite );
	}

	public function test_unrecognized_content_is_not_stored() {
		$input = Kind_Plugins::micropub_parse( $this->create_request( array( 'bookmark-of' => array( self::JSON ) ) ) );
		$cite  = $input['properties']['bookmark-of'][0];
		$this->assertSame( array( 'h-cite' ), $cite['type'] );
		$this->assertSame( array( self::JSON ), $cite['properties']['url'] );
		$this->assertArrayNotHasKey( 'raw', $cite['properties'] );
	}

	public function test_non_array_value_is_left_alone() {
		$request = $this->create_request( array( 'in-reply-to' => array( 'url' => self::ENTRY ) ) );
		$this->assertSame( $request, Kind_Plugins::micropub_parse( $request ) );
	}

	public function test_client_sent_citation_is_kept() {
		$client  = array(
			'type'       => array( 'h-cite' ),
			'properties' => array(
				'url'  => array( self::ENTRY ),
				'name' => array( 'As sent' ),
			),
		);
		$request = $this->create_request( array( 'bookmark-of' => array( $client ) ) );
		$this->assertSame( $request, Kind_Plugins::micropub_parse( $request ) );
	}

	public function test_unreachable_url_is_kept_and_reported() {
		$request = $this->create_request( array( 'in-reply-to' => array( self::DOWN ) ) );
		$result  = null;
		$notices = $this->capture_notices(
			function () use ( $request, &$result ) {
				$result = Kind_Plugins::micropub_parse( $request );
			}
		);
		$this->assertSame( $request, $result );
		$this->assertCount( 1, $notices );
		$this->assertStringContainsString( self::DOWN, $notices[0] );
	}

	public function test_request_parse_uses_short_timeout_and_background_uses_default() {
		Kind_Plugins::micropub_parse( $this->create_request( array( 'in-reply-to' => array( self::ENTRY ) ) ) );
		$this->assertEquals( 5, $this->timeouts[ self::ENTRY ] );

		$post = self::factory()->post->create( array( 'meta_input' => array( 'mf2_in-reply-to' => array( self::ENTRY ) ) ) );
		Kind_Plugins::enrich_citation( $post, 'in-reply-to', self::ENTRY, 2 );
		$this->assertEquals( 15, $this->timeouts[ self::ENTRY ] );
	}

	public function test_temporary_failure_is_retried_in_the_background() {
		$post = self::factory()->post->create();
		$this->capture_notices(
			function () use ( $post ) {
				$input = Kind_Plugins::micropub_parse( $this->create_request( array( 'in-reply-to' => array( self::DOWN ) ) ) );
				Kind_Plugins::schedule_enrichment( $input, array( 'ID' => $post ) );
			}
		);
		$this->assertNotFalse( wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'in-reply-to', self::DOWN, 2 ) ) );
	}

	public function test_permanent_failure_is_not_retried() {
		$post = self::factory()->post->create();
		$this->capture_notices(
			function () use ( $post ) {
				$input = Kind_Plugins::micropub_parse( $this->create_request( array( 'in-reply-to' => array( self::GONE ) ) ) );
				Kind_Plugins::schedule_enrichment( $input, array( 'ID' => $post ) );
			}
		);
		$this->assertFalse( wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'in-reply-to', self::GONE, 2 ) ) );
	}

	public function test_background_parse_replaces_the_url() {
		$post = self::factory()->post->create( array( 'meta_input' => array( 'mf2_in-reply-to' => array( self::ENTRY ) ) ) );
		Kind_Plugins::enrich_citation( $post, 'in-reply-to', self::ENTRY, 2 );
		$values = get_post_meta( $post, 'mf2_in-reply-to', true );
		$this->assertSame( array( 'h-cite' ), $values[0]['type'] );
		$this->assert_no_bookkeeping( $values );
	}

	public function test_background_parse_leaves_edited_citations_alone() {
		$edited = array( 'https://example.com/a-different-post/' );
		$post   = self::factory()->post->create( array( 'meta_input' => array( 'mf2_in-reply-to' => $edited ) ) );
		Kind_Plugins::enrich_citation( $post, 'in-reply-to', self::ENTRY, 2 );
		$this->assertSame( $edited, get_post_meta( $post, 'mf2_in-reply-to', true ) );
	}

	public function test_background_retries_stop_after_the_last_attempt() {
		$post = self::factory()->post->create( array( 'meta_input' => array( 'mf2_in-reply-to' => array( self::DOWN ) ) ) );
		$this->capture_notices(
			function () use ( $post ) {
				Kind_Plugins::enrich_citation( $post, 'in-reply-to', self::DOWN, 2 );
				Kind_Plugins::enrich_citation( $post, 'in-reply-to', self::DOWN, 3 );
			}
		);
		$this->assertNotFalse( wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'in-reply-to', self::DOWN, 3 ) ) );
		$this->assertFalse( wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'in-reply-to', self::DOWN, 4 ) ) );
	}

	public function test_enrichment_can_be_turned_off() {
		add_filter( 'post_kinds_micropub_enrich', '__return_false' );
		$request = $this->create_request( array( 'in-reply-to' => array( self::ENTRY ) ) );
		$this->assertSame( $request, Kind_Plugins::micropub_parse( $request ) );
		remove_filter( 'post_kinds_micropub_enrich', '__return_false' );
	}

	public function test_queries_and_updates_are_untouched() {
		$query  = array( 'q' => 'source', 'url' => self::ENTRY );
		$update = array(
			'action'  => 'update',
			'url'     => self::ENTRY,
			'replace' => array( 'in-reply-to' => array( self::ENTRY ) ),
		);
		$this->assertSame( $query, Kind_Plugins::micropub_parse( $query ) );
		$this->assertSame( $update, Kind_Plugins::micropub_parse( $update ) );
	}

	public function test_kind_is_set_from_post_type_discovery() {
		$post = self::factory()->post->create();
		Kind_Plugins::micropub_set_kind( $this->create_request( array( 'like-of' => array( self::ENTRY ) ) ), array( 'ID' => $post ) );
		$this->assertSame( 'like', get_post_kind_slug( $post ) );
	}

	public function test_micropub_dynamic_render_is_turned_off() {
		$this->assertFalse( apply_filters( 'micropub_dynamic_render', true, null ) );
	}
}
