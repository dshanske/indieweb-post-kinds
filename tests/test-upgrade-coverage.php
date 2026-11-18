<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Records what Kind_Upgrade::cli() reports. WP-CLI isn't loaded in tests, and
	 * the plugin only registers its command when the WP_CLI constant is set.
	 */
	class WP_CLI {
		/**
		 * Messages logged, in order.
		 *
		 * @var string[]
		 */
		public static $messages = array();

		/**
		 * Records a log message.
		 *
		 * @param string $message Message.
		 */
		public static function log( $message ) {
			self::$messages[] = $message;
		}

		/**
		 * Records a success message.
		 *
		 * @param string $message Message.
		 */
		public static function success( $message ) {
			self::$messages[] = 'Success: ' . $message;
		}
	}
}

/**
 * Kind_Upgrade: the WP-CLI command and the less common upgrade paths (#521).
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
		WP_CLI::$messages = array();
	}

	public function tear_down() {
		wp_clear_scheduled_hook( Kind_Upgrade::CRON_HOOK );
		parent::tear_down();
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

		Kind_Upgrade::cli( array(), array( 'post' => $id, 'dry-run' => true ) );
		$this->assertEmpty( get_post_kind_slug( $id ) );
		$this->assertSame( 'Success: 1 of 1 posts would change.', end( WP_CLI::$messages ) );
		$this->assertStringStartsWith( "Post {$id}: ", WP_CLI::$messages[0] );

		WP_CLI::$messages = array();
		Kind_Upgrade::cli( array(), array( 'post' => (string) $id ) );
		$this->assertSame( 'like', get_post_kind_slug( $id ) );
		$this->assertSame( 'Success: 1 of 1 posts changed.', end( WP_CLI::$messages ) );
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

		Kind_Upgrade::cli( array(), array() );
		remove_all_filters( 'kind_upgrade_batch_size' );

		foreach ( $ids as $id ) {
			$this->assertSame( 'article', get_post_kind_slug( $id ) );
		}
		$this->assertFalse( Kind_Upgrade::is_pending() );
		$this->assertSame( array( 'reply', 'article' ), get_option( 'kind_firehose' ) );
		$this->assertContains( 'Added Article to the kinds shown on the home page, so posts that had no kind stay there.', WP_CLI::$messages );
		$this->assertMatchesRegularExpression( '/^Success: \d+ of \d+ posts changed\.$/', end( WP_CLI::$messages ) );
	}
}
