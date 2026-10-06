<?php

class MicropubTest extends WP_UnitTestCase {
	const ENTRY = 'https://example.com/2026/01/01/an-entry/';
	const HOME  = 'https://example.com/';
	// Resolves in DNS (wp_http_validate_url() checks), but the mock fails the request.
	const DOWN  = 'https://example.net/down';
	const GONE  = 'https://example.com/missing/';
	const JSON  = 'https://example.com/data.json';
	const IMAGE = 'https://example.com/picture.png';

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
		// A response supplied by an earlier filter (in a test) wins.
		if ( false !== $pre ) {
			return $pre;
		}
		$this->timeouts[ $url ] = isset( $args['timeout'] ) ? $args['timeout'] : null;
		if ( self::IMAGE === $url ) {
			return array(
				'headers'  => array( 'content-type' => 'image/png' ),
				'body'     => 'not really a png',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}
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

	/**
	 * Runs a Micropub create for one citation and the after_micropub step,
	 * capturing the failure notices.
	 */
	private function create_with_citation( $property, $url ) {
		$post = self::factory()->post->create( array( 'meta_input' => array( 'mf2_' . $property => array( $url ) ) ) );
		$this->capture_notices(
			function () use ( $post, $property, $url ) {
				$input = Kind_Plugins::micropub_parse( $this->create_request( array( $property => array( $url ) ) ) );
				Kind_Plugins::schedule_enrichment( $input, array( 'ID' => $post ) );
			}
		);
		return $post;
	}

	public function test_missing_page_is_retried_once_after_hours() {
		$post = $this->create_with_citation( 'in-reply-to', self::GONE );
		$next = wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'in-reply-to', self::GONE, 2 ) );
		$this->assertNotFalse( $next );
		$this->assertGreaterThanOrEqual( time() + 6 * HOUR_IN_SECONDS - 60, $next );

		// The retry also finds the page missing: no further retry.
		$this->capture_notices(
			function () use ( $post ) {
				Kind_Plugins::enrich_citation( $post, 'in-reply-to', self::GONE, 2 );
			}
		);
		$this->assertFalse( wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'in-reply-to', self::GONE, 3 ) ) );
		$this->assertSame( 'failed', get_post_meta( $post, Kind_Plugins::STATUS_META, true ) );
	}

	public function test_unavailable_retry_delay_is_filterable() {
		$delay = function () {
			return HOUR_IN_SECONDS;
		};
		add_filter( 'post_kinds_micropub_unavailable_retry_delay', $delay );
		$post = $this->create_with_citation( 'in-reply-to', self::GONE );
		remove_filter( 'post_kinds_micropub_unavailable_retry_delay', $delay );
		$next = wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'in-reply-to', self::GONE, 2 ) );
		$this->assertLessThanOrEqual( time() + HOUR_IN_SECONDS, $next );
	}

	public function test_unparseable_content_is_not_retried() {
		$post = $this->create_with_citation( 'bookmark-of', self::IMAGE );
		$this->assertFalse( wp_next_scheduled( Kind_Plugins::ENRICH_EVENT, array( $post, 'bookmark-of', self::IMAGE, 2 ) ) );
		$log = Kind_Plugins::get_citation_log( $post );
		$this->assertSame( 'permanent', $log[0]['type'] );
		$this->assertSame( 'failed', get_post_meta( $post, Kind_Plugins::STATUS_META, true ) );
	}

	public function test_failures_are_logged_with_details() {
		$entries = array();
		$capture = function ( $entry ) use ( &$entries ) {
			$entries[] = $entry;
		};
		add_action( 'post_kinds_citation_enrichment', $capture );
		$post = $this->create_with_citation( 'like-of', self::GONE );
		remove_action( 'post_kinds_citation_enrichment', $capture );

		$log = Kind_Plugins::get_citation_log( $post );
		$this->assertCount( 1, $log );
		$this->assertSame( $log, $entries );
		$entry = $log[0];
		$this->assertSame( 'like-of', $entry['property'] );
		$this->assertSame( self::GONE, $entry['url'] );
		$this->assertSame( 1, $entry['attempt'] );
		$this->assertSame( 'failed', $entry['result'] );
		$this->assertSame( 'unavailable', $entry['type'] );
		$this->assertSame( 'not_found', $entry['code'] );
		$this->assertSame( 404, $entry['response_code'] );
		$this->assertNotEmpty( $entry['message'] );
		$this->assertGreaterThan( time(), $entry['retry'] );
		$this->assertSame( 'retrying', get_post_meta( $post, Kind_Plugins::STATUS_META, true ) );
	}

	public function test_successful_retry_clears_the_status() {
		$post = $this->create_with_citation( 'in-reply-to', self::DOWN );
		$this->assertSame( 'retrying', get_post_meta( $post, Kind_Plugins::STATUS_META, true ) );

		// The site is back: serve the entry for the same URL.
		$serve = function ( $pre, $args, $url ) {
			return self::DOWN === $url ? $this->mock_http( $pre, $args, self::ENTRY ) : $pre;
		};
		add_filter( 'pre_http_request', $serve, 5, 3 );
		Kind_Plugins::enrich_citation( $post, 'in-reply-to', self::DOWN, 2 );
		remove_filter( 'pre_http_request', $serve, 5 );

		$log = Kind_Plugins::get_citation_log( $post );
		$this->assertSame( 'enriched', end( $log )['result'] );
		$this->assertSame( '', get_post_meta( $post, Kind_Plugins::STATUS_META, true ) );
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
		$log = Kind_Plugins::get_citation_log( $post );
		$this->assertSame( 'skipped', end( $log )['result'] );
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

	public function test_queries_and_updates_without_citations_are_untouched() {
		$query  = array(
			'q'   => 'source',
			'url' => self::ENTRY,
		);
		$update = array(
			'action'  => 'update',
			'url'     => self::ENTRY,
			'replace' => array( 'content' => array( 'Edited' ) ),
			'delete'  => array( 'category' ),
		);
		$this->assertSame( $query, Kind_Plugins::micropub_parse( $query ) );
		$this->assertSame( $update, Kind_Plugins::micropub_parse( $update ) );
	}

	public function test_replaced_citation_is_enriched_in_updates() {
		$update = Kind_Plugins::micropub_parse(
			array(
				'action'  => 'update',
				'url'     => 'https://example.com/my-post/',
				'replace' => array( 'in-reply-to' => array( self::ENTRY ) ),
			)
		);
		$cite   = $update['replace']['in-reply-to'][0];
		$this->assertSame( array( 'h-cite' ), $cite['type'] );
		$this->assert_no_bookkeeping( $cite );
	}

	/**
	 * Creates a post as Micropub would store it: kind and mf2_ properties.
	 */
	private function micropub_post( $kind, $properties ) {
		$meta = array();
		foreach ( $properties as $name => $values ) {
			$meta[ 'mf2_' . $name ] = $values;
		}
		$post = self::factory()->post->create(
			array(
				'post_content' => '',
				'meta_input'   => $meta,
			)
		);
		set_post_kind( $post, $kind );
		return $post;
	}

	public function test_update_that_changes_the_response_property_changes_the_kind() {
		$post = $this->micropub_post( 'like', array( 'like-of' => array( self::ENTRY ) ) );
		// What Micropub stores for: replace in-reply-to, delete like-of.
		update_post_meta( $post, 'mf2_in-reply-to', array( self::HOME ) );
		delete_post_meta( $post, 'mf2_like-of' );
		$update = array(
			'action'  => 'update',
			'url'     => get_permalink( $post ),
			'replace' => array( 'in-reply-to' => array( self::HOME ) ),
			'delete'  => array( 'like-of' ),
		);
		Kind_Plugins::micropub_set_kind( $update, array( 'ID' => $post ) );
		Kind_Plugins::post_formats( $update, array( 'ID' => $post ) );
		$this->assertSame( 'reply', get_post_kind_slug( $post ) );
		$this->assertSame( Kind_Taxonomy::get_kind_info( 'reply', 'format' ), get_post_format( $post ) );
		// The properties stay where the client put them.
		$this->assertSame( array( self::HOME ), get_post_meta( $post, 'mf2_in-reply-to', true ) );
	}

	public function test_kind_change_does_not_move_properties() {
		// Replacing like-of on a reply, keeping its in-reply-to: Parse This's Post
		// Type Discovery checks like-of before in-reply-to, so the kind becomes
		// like. The editor's change_kind handler would have moved the old
		// in-reply-to over the new like-of.
		$post = $this->micropub_post( 'reply', array( 'in-reply-to' => array( self::ENTRY ) ) );
		update_post_meta( $post, 'mf2_like-of', array( self::HOME ) );
		Kind_Plugins::micropub_set_kind(
			array(
				'action'  => 'update',
				'url'     => get_permalink( $post ),
				'replace' => array( 'like-of' => array( self::HOME ) ),
			),
			array( 'ID' => $post )
		);
		$this->assertSame( 'like', get_post_kind_slug( $post ) );
		$this->assertSame( array( self::HOME ), get_post_meta( $post, 'mf2_like-of', true ) );
		$this->assertSame( array( self::ENTRY ), get_post_meta( $post, 'mf2_in-reply-to', true ) );
		// The editor handler is restored afterwards.
		$this->assertSame( 10, has_action( 'change_kind', array( 'Kind_Metabox', 'change_kind' ) ) );
	}

	public function test_unrelated_update_keeps_a_chosen_kind() {
		// A kind chosen in the editor that Post Type Discovery would not produce.
		$post = $this->micropub_post( 'watch', array( 'in-reply-to' => array( self::ENTRY ) ) );
		Kind_Plugins::micropub_set_kind(
			array(
				'action'  => 'update',
				'url'     => get_permalink( $post ),
				'replace' => array( 'content' => array( 'Edited' ) ),
				'add'     => array( 'category' => array( 'film' ) ),
			),
			array( 'ID' => $post )
		);
		$this->assertSame( 'watch', get_post_kind_slug( $post ) );
	}

	public function test_update_changes_kind_detects_kind_properties() {
		$this->assertTrue( Kind_Plugins::update_changes_kind( array( 'replace' => array( 'like-of' => array( self::ENTRY ) ) ) ) );
		$this->assertTrue( Kind_Plugins::update_changes_kind( array( 'delete' => array( 'in-reply-to' ) ) ) );
		$this->assertTrue( Kind_Plugins::update_changes_kind( array( 'delete' => array( 'photo' => array( 'https://example.com/a.jpg' ) ) ) ) );
		$this->assertTrue( Kind_Plugins::update_changes_kind( array( 'replace' => array( 'rsvp' => array( 'yes' ) ) ) ) );
		$this->assertFalse( Kind_Plugins::update_changes_kind( array( 'replace' => array( 'content' => array( 'x' ) ) ) ) );
		$this->assertFalse( Kind_Plugins::update_changes_kind( array( 'delete' => array( 'category' ) ) ) );
	}

	public function test_kind_is_set_from_post_type_discovery() {
		$post = self::factory()->post->create();
		Kind_Plugins::micropub_set_kind( $this->create_request( array( 'like-of' => array( self::ENTRY ) ) ), array( 'ID' => $post ) );
		$this->assertSame( 'like', get_post_kind_slug( $post ) );
	}

	public function test_tax_input_gets_the_kind() {
		$like = $this->create_request( array( 'like-of' => array( self::ENTRY ) ) );
		$this->assertSame( array( 'kind' => array( 'like' ) ), Kind_Plugins::micropub_tax_input( null, $like ) );
		// Other taxonomies are kept, and a kind already chosen is not replaced.
		$this->assertSame(
			array(
				'location' => array( 'here' ),
				'kind'     => array( 'like' ),
			),
			Kind_Plugins::micropub_tax_input( array( 'location' => array( 'here' ) ), $like )
		);
		$this->assertSame( array( 'kind' => array( 'bookmark' ) ), Kind_Plugins::micropub_tax_input( array( 'kind' => array( 'bookmark' ) ), $like ) );
		// Nothing to discover.
		$this->assertNull( Kind_Plugins::micropub_tax_input( null, array( 'action' => 'delete' ) ) );
	}

	public function test_post_is_inserted_with_its_kind_and_format() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$seen    = array();
		$observe = function ( $new_status, $old_status, $post ) use ( &$seen ) {
			$seen[] = get_post_kind_slug( $post->ID );
		};
		add_action( 'transition_post_status', $observe, 10, 3 );
		$id = wp_insert_post(
			array(
				'post_status'  => 'publish',
				'post_content' => 'Liked',
				'tax_input'    => Kind_Plugins::micropub_tax_input( null, $this->create_request( array( 'like-of' => array( self::ENTRY ) ) ) ),
			)
		);
		remove_action( 'transition_post_status', $observe, 10 );
		$this->assertSame( array( 'like' ), $seen );
		$this->assertSame( 'like', get_post_kind_slug( $id ) );
		$this->assertSame( Kind_Taxonomy::get_kind_info( 'like', 'format' ), get_post_format( $id ) );
	}

	public function test_set_kind_fallback_only_changes_a_wrong_kind() {
		$post = self::factory()->post->create();
		set_post_kind( $post, 'like' );
		$changes = 0;
		$count   = function () use ( &$changes ) {
			++$changes;
		};
		add_action( 'change_kind', $count );
		Kind_Plugins::micropub_set_kind( $this->create_request( array( 'like-of' => array( self::ENTRY ) ) ), array( 'ID' => $post ) );
		remove_action( 'change_kind', $count );
		$this->assertSame( 0, $changes );
		$this->assertSame( 'like', get_post_kind_slug( $post ) );
	}

	public function test_micropub_dynamic_render_is_turned_off() {
		$this->assertFalse( apply_filters( 'micropub_dynamic_render', true, null ) );
	}
}
