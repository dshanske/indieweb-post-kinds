<?php

/**
 * Kind_Upgrade: the WP-CLI command and the less common upgrade paths (#521).
 *
 * WP-CLI's classes come with the wp-cli/i18n-command dev dependency; its
 * Execution logger records what the command reports.
 */
class UpgradeCoverageTest extends WP_UnitTestCase {
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
		wp_clear_scheduled_hook( Kind_Upgrade::CRON_HOOK );
		if ( class_exists( 'WP_CLI' ) ) {
			$logger = new ReflectionProperty( 'WP_CLI', 'logger' );
			if ( PHP_VERSION_ID < 80100 ) {
				$logger->setAccessible( true );
			}
			$logger->setValue( null, null );
		}
		parent::tear_down();
	}

	/**
	 * Runs the WP-CLI command and returns the lines it printed.
	 *
	 * @param array $assoc_args Command options.
	 * @return string[]
	 */
	private function cli( $assoc_args ) {
		if ( ! class_exists( 'WP_CLI\\Loggers\\Execution' ) ) {
			$this->markTestSkipped( 'WP-CLI is not installed.' );
		}
		$logger = new WP_CLI\Loggers\Execution();
		WP_CLI::set_logger( $logger );
		Kind_Upgrade::cli( array(), $assoc_args );
		return explode( "\n", trim( $logger->stdout ) );
	}

	/**
	 * Creates a published post without a kind.
	 *
	 * @param array $meta Post meta.
	 * @return int Post ID.
	 */
	private function unclassified( $meta = array() ) {
		$id = self::factory()->post->create();
		wp_delete_object_term_relationships( $id, 'kind' );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	public function test_run_batch_does_nothing_once_complete() {
		update_option( Kind_Upgrade::VERSION_OPTION, Kind_Upgrade::VERSION );
		$id = $this->unclassified();
		Kind_Upgrade::run_batch();
		$this->assertEmpty( get_post_kind_slug( $id ) );
		$this->assertFalse( wp_next_scheduled( Kind_Upgrade::CRON_HOOK ) );
	}

	public function test_pages_are_not_upgraded() {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->assertSame( array(), Kind_Upgrade::upgrade_post( $page ) );
	}

	public function test_empty_properties_are_ignored_when_discovering_the_kind() {
		$id = $this->unclassified(
			array(
				'mf2_in-reply-to' => '',
				'mf2_like-of'     => array( 'https://example.com/liked' ),
			)
		);
		Kind_Upgrade::upgrade_post( $id );
		$this->assertSame( 'like', get_post_kind_slug( $id ) );
	}

	public function test_old_cite_list_of_one_url_becomes_the_url() {
		$id = self::factory()->post->create();
		set_post_kind( $id, 'reply' );
		update_post_meta( $id, 'mf2_cite', array( 'https://example.com/old' ) );

		$this->assertSame( array( 'moved mf2_cite to mf2_in-reply-to' ), Kind_Upgrade::upgrade_post( $id ) );
		$this->assertSame( 'https://example.com/old', get_post_meta( $id, 'mf2_in-reply-to', true ) );
	}

	public function test_response_meta_that_is_not_an_array_is_removed() {
		$id = self::factory()->post->create();
		set_post_kind( $id, 'bookmark' );
		update_post_meta( $id, 'response', 'not an array' );

		Kind_Upgrade::upgrade_post( $id );
		$this->assertFalse( metadata_exists( 'post', $id, 'response' ) );
		$this->assertEmpty( get_post_meta( $id, 'mf2_bookmark-of', true ) );
	}

	public function test_cli_upgrades_one_post() {
		$id = $this->unclassified( array( 'mf2_like-of' => array( 'https://example.com/liked' ) ) );

		$lines = $this->cli( array( 'post' => $id, 'dry-run' => true ) );
		$this->assertEmpty( get_post_kind_slug( $id ) );
		$this->assertSame( 'Success: 1 of 1 posts would change.', end( $lines ) );
		$this->assertStringStartsWith( "Post {$id}: ", $lines[0] );

		$lines = $this->cli( array( 'post' => (string) $id ) );
		$this->assertSame( 'like', get_post_kind_slug( $id ) );
		$this->assertSame( 'Success: 1 of 1 posts changed.', end( $lines ) );
	}

	public function test_cli_upgrades_every_post_in_batches() {
		add_filter(
			'kind_upgrade_batch_size',
			function () {
				return 2;
			}
		);
		// The home page shows only some kinds, so Article is added to keep untyped posts there.
		update_option( 'kind_firehose', array( 'reply' ) );
		$ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$ids[] = $this->unclassified();
		}

		$lines = $this->cli( array() );
		remove_all_filters( 'kind_upgrade_batch_size' );

		foreach ( $ids as $id ) {
			$this->assertSame( 'article', get_post_kind_slug( $id ) );
		}
		$this->assertFalse( Kind_Upgrade::is_pending() );
		$this->assertSame( array( 'reply', 'article' ), get_option( 'kind_firehose' ) );
		$this->assertContains( 'Added Article to the kinds shown on the home page, so posts that had no kind stay there.', $lines );
		$this->assertMatchesRegularExpression( '/^Success: \d+ of \d+ posts changed\.$/', end( $lines ) );
	}
}
