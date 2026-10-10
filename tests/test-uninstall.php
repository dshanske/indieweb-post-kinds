<?php

/**
 * Deleting the plugin removes its settings but keeps posts and their data (#141).
 */
class UninstallTest extends WP_UnitTestCase {
	public function test_uninstall_removes_options_and_events() {
		Kind_Taxonomy::kind_defaultterms();
		$post = self::factory()->post->create();
		set_post_kind( $post, 'like' );
		update_post_meta( $post, 'mf2_like-of', array( 'url' => 'https://example.com/' ) );

		update_option( 'kind_termslist', array( 'like' ) );
		update_option( 'kind_default', 'like' );
		update_option( 'kind_upgrade_version', Kind_Upgrade::VERSION );
		update_option( 'iwt_options', array( 'legacy' => 1 ) );
		update_option( 'unrelated_option', 'kept' );
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'post_kinds_enrich_citation', array( $post, 1 ) );
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, Kind_Upgrade::CRON_HOOK );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'indieweb-post-kinds/indieweb-post-kinds.php' );
		}
		require dirname( __DIR__ ) . '/uninstall.php';

		foreach ( array( 'kind_termslist', 'kind_default', 'kind_upgrade_version', 'iwt_options' ) as $option ) {
			$this->assertFalse( get_option( $option ), $option );
		}
		$this->assertSame( 'kept', get_option( 'unrelated_option' ) );
		$this->assertFalse( wp_next_scheduled( 'post_kinds_enrich_citation', array( $post, 1 ) ) );
		$this->assertFalse( wp_next_scheduled( Kind_Upgrade::CRON_HOOK ) );

		$this->assertSame( 'like', get_post_kind_slug( $post ) );
		$this->assertNotEmpty( get_post_meta( $post, 'mf2_like-of', true ) );
	}
}
