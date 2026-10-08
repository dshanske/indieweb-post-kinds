<?php

class UpgradeTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
		delete_option( Kind_Upgrade::VERSION_OPTION );
		delete_option( Kind_Upgrade::PROGRESS_OPTION );
		delete_option( Kind_Upgrade::ARTICLE_OPTION );
		delete_option( 'kind_firehose' );
		wp_clear_scheduled_hook( Kind_Upgrade::CRON_HOOK );
	}

	public function tear_down() {
		remove_all_filters( 'kind_upgrade_batch_size' );
		wp_clear_scheduled_hook( Kind_Upgrade::CRON_HOOK );
		parent::tear_down();
	}

	private function post( $kind, $meta = array(), $content = 'Words' ) {
		$id = self::factory()->post->create( array( 'post_content' => $content ) );
		set_post_kind( $id, $kind );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, wp_slash( $value ) );
		}
		return $id;
	}

	public function test_damaged_citation_is_repaired() {
		$id = $this->post(
			'checkin',
			array(
				'mf2_checkin' => array(
					'type'       => 'cite',
					'properties' => array(
						'properties' => array(
							'name'     => array( 'Cafe' ),
							'location' => array( 'Here' ),
						),
						'type'       => array( 'cite' ),
						'name'       => array( 'Cafe 2' ),
						0            => array( 'https://example.com/c' ),
					),
				),
			)
		);
		$this->assertSame( array( 'repaired mf2_checkin' ), Kind_Upgrade::upgrade_post( $id ) );
		$cite = get_post_meta( $id, 'mf2_checkin', true );
		$this->assertSame( array( 'h-card' ), $cite['type'] );
		$this->assertEquals(
			array(
				'name'     => array( 'Cafe 2' ),
				'location' => array( 'Here' ),
			),
			$cite['properties']
		);
	}

	public function test_jf2_citation_is_saved_as_mf2_keeping_backslashes() {
		$id = $this->post(
			'reply',
			array(
				'mf2_in-reply-to' => array(
					'url'  => 'https://example.com/j',
					'name' => 'C:\\path',
				),
			)
		);
		Kind_Upgrade::upgrade_post( $id );
		$cite = get_post_meta( $id, 'mf2_in-reply-to', true );
		$this->assertSame( array( 'h-cite' ), $cite['type'] );
		$this->assertSame( array( 'C:\\path' ), $cite['properties']['name'] );
	}

	public function test_old_cite_is_moved_to_the_kind_property() {
		$id = $this->post( 'reply', array( 'mf2_cite' => 'https://example.com/old' ) );
		$this->assertSame( array( 'moved mf2_cite to mf2_in-reply-to' ), Kind_Upgrade::upgrade_post( $id ) );
		$this->assertSame( 'https://example.com/old', get_post_meta( $id, 'mf2_in-reply-to', true ) );
		$this->assertFalse( metadata_exists( 'post', $id, 'mf2_cite' ) );
	}

	public function test_response_meta_is_moved_to_the_kind_property() {
		$id = $this->post(
			'bookmark',
			array(
				'response' => array(
					'title'  => 'Old',
					'url'    => 'https://example.com/r',
					'author' => 'Ann',
				),
			)
		);
		Kind_Upgrade::upgrade_post( $id );
		$cite = get_post_meta( $id, 'mf2_bookmark-of', true );
		$this->assertSame( array( 'Old' ), $cite['properties']['name'] );
		$this->assertSame( array( 'h-card' ), $cite['properties']['author'][0]['type'] );
		$this->assertFalse( metadata_exists( 'post', $id, 'response' ) );
	}

	public function test_valid_citations_are_left_alone() {
		$micropub = array(
			array(
				'type'       => array( 'h-cite' ),
				'properties' => array( 'url' => array( 'https://example.com/m' ) ),
			),
		);
		$ids      = array(
			$this->post( 'like', array( 'mf2_like-of' => $micropub ) ),
			$this->post( 'reply', array( 'mf2_in-reply-to' => 'https://example.com/u' ) ),
			$this->post( 'reply', array( 'mf2_in-reply-to' => array( 'https://example.com/u' ) ) ),
			$this->post( 'note' ),
		);
		foreach ( $ids as $id ) {
			$this->assertSame( array(), Kind_Upgrade::upgrade_post( $id ) );
		}
		$this->assertSame( $micropub, get_post_meta( $ids[0], 'mf2_like-of', true ) );
	}

	public function test_missing_media_ids_are_recorded() {
		$image = self::factory()->attachment->create_object( 'pk-upgrade.jpg', 0, array( 'post_mime_type' => 'image/jpeg' ) );
		$id    = $this->post( 'photo', array(), '<img class="wp-image-' . $image . '" src="x.jpg">' );
		delete_post_meta( $id, '_content_img_ids' );
		$this->assertSame( array( 'recorded media IDs' ), Kind_Upgrade::upgrade_post( $id ) );
		$this->assertSame( array( $image ), get_post_meta( $id, '_content_img_ids', true ) );
	}

	public function test_dry_run_saves_nothing_and_rerun_changes_nothing() {
		$id = $this->post( 'reply', array( 'mf2_cite' => 'https://example.com/old' ) );
		$this->assertNotEmpty( Kind_Upgrade::upgrade_post( $id, true ) );
		$this->assertTrue( metadata_exists( 'post', $id, 'mf2_cite' ) );
		$this->assertNotEmpty( Kind_Upgrade::upgrade_post( $id ) );
		$this->assertSame( array(), Kind_Upgrade::upgrade_post( $id ) );
	}

	public function test_init_schedules_a_batch_only_while_pending() {
		Kind_Upgrade::init();
		$this->assertNotFalse( wp_next_scheduled( Kind_Upgrade::CRON_HOOK ) );
		Kind_Upgrade::complete();
		$this->assertFalse( wp_next_scheduled( Kind_Upgrade::CRON_HOOK ) );
		Kind_Upgrade::init();
		$this->assertFalse( wp_next_scheduled( Kind_Upgrade::CRON_HOOK ) );
	}

	public function test_batches_cover_every_post_then_complete() {
		add_filter(
			'kind_upgrade_batch_size',
			function () {
				return 2;
			}
		);
		$ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$ids[] = $this->post( 'reply', array( 'mf2_cite' => 'https://example.com/' . $i ) );
		}
		$runs = 0;
		while ( Kind_Upgrade::is_pending() && $runs < 10 ) {
			Kind_Upgrade::run_batch();
			++$runs;
		}
		$this->assertFalse( Kind_Upgrade::is_pending() );
		$this->assertFalse( get_option( Kind_Upgrade::PROGRESS_OPTION ) );
		$this->assertFalse( wp_next_scheduled( Kind_Upgrade::CRON_HOOK ) );
		foreach ( $ids as $i => $id ) {
			$this->assertSame( 'https://example.com/' . $i, get_post_meta( $id, 'mf2_in-reply-to', true ) );
		}
	}

	private function unclassified( $meta = array(), $status = 'publish' ) {
		$id = self::factory()->post->create( array( 'post_status' => $status ) );
		wp_delete_object_term_relationships( $id, 'kind' );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	public function test_kind_is_discovered_from_stored_properties() {
		$rsvp = $this->unclassified(
			array(
				'mf2_in-reply-to' => array( 'https://example.com/event' ),
				'mf2_rsvp'        => 'yes',
			)
		);
		$like = $this->unclassified( array( 'mf2_like-of' => array( 'https://example.com/liked' ) ) );
		$this->assertSame( array( 'assigned kind rsvp from its properties' ), Kind_Upgrade::upgrade_post( $rsvp ) );
		$this->assertSame( 'rsvp', get_post_kind_slug( $rsvp ) );
		Kind_Upgrade::upgrade_post( $like );
		$this->assertSame( 'like', get_post_kind_slug( $like ) );
		$this->assertFalse( get_option( Kind_Upgrade::ARTICLE_OPTION ) );
	}

	public function test_post_without_properties_becomes_an_article() {
		$id = $this->unclassified();
		$this->assertSame( array( 'assigned kind article' ), Kind_Upgrade::upgrade_post( $id ) );
		$this->assertSame( 'article', get_post_kind_slug( $id ) );
		$this->assertSame( '1', (string) get_option( Kind_Upgrade::ARTICLE_OPTION ) );
	}

	public function test_drafts_and_classified_posts_keep_their_kind() {
		$draft = $this->unclassified( array(), 'draft' );
		$note  = $this->post( 'note' );
		$this->assertSame( array(), Kind_Upgrade::upgrade_post( $draft ) );
		$this->assertSame( array(), Kind_Upgrade::upgrade_post( $note ) );
		$this->assertFalse( get_post_kind_slug( $draft ) );
		$this->assertSame( 'note', get_post_kind_slug( $note ) );
	}

	public function test_dry_run_assigns_no_kind() {
		$id = $this->unclassified();
		$this->assertSame( array( 'assigned kind article' ), Kind_Upgrade::upgrade_post( $id, true ) );
		$this->assertFalse( get_post_kind_slug( $id ) );
		$this->assertFalse( get_option( Kind_Upgrade::ARTICLE_OPTION ) );
	}

	public function test_article_is_added_to_a_limited_home_page() {
		update_option( 'kind_firehose', array( 'note' ) );
		Kind_Upgrade::upgrade_post( $this->unclassified() );
		$this->assertTrue( Kind_Upgrade::complete() );
		$this->assertSame( array( 'note', 'article' ), get_option( 'kind_firehose' ) );
		$this->assertFalse( get_option( Kind_Upgrade::ARTICLE_OPTION ) );
	}

	public function test_home_page_selection_is_kept_when_not_needed() {
		// No posts became articles.
		update_option( 'kind_firehose', array( 'note' ) );
		$this->assertFalse( Kind_Upgrade::complete() );
		$this->assertSame( array( 'note' ), get_option( 'kind_firehose' ) );

		// The home page shows every kind.
		delete_option( 'kind_firehose' );
		Kind_Upgrade::upgrade_post( $this->unclassified() );
		$this->assertFalse( Kind_Upgrade::complete() );
		$this->assertFalse( get_option( 'kind_firehose' ) );
	}

	public function test_home_query_drops_not_exists_once_complete() {
		update_option( 'kind_firehose', array( 'note' ) );
		$this->go_to( home_url( '/' ) );
		$this->assertStringContainsString( 'NOT EXISTS', $GLOBALS['wp_query']->request );

		Kind_Upgrade::complete();
		$this->go_to( home_url( '/' ) );
		$this->assertStringNotContainsString( 'NOT EXISTS', $GLOBALS['wp_query']->request );
	}
}
