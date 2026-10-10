<?php

/**
 * The Response Properties box must not stay hidden through Screen Options (#168).
 */
class MetaboxScreenOptionsTest extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		Kind_Metabox::kindbox_setup();
	}

	public function tear_down() {
		remove_filter( 'hidden_meta_boxes', array( 'Kind_Metabox', 'hidden_meta_boxes' ), 10 );
		parent::tear_down();
	}

	public function test_saved_hidden_response_properties_is_ignored() {
		update_user_option( get_current_user_id(), 'metaboxhidden_post', array( 'replybox-meta', 'postcustom' ), true );
		$this->assertSame( array( 'postcustom' ), get_hidden_meta_boxes( WP_Screen::get( 'post' ) ) );
	}

	public function test_other_screens_are_untouched() {
		$hidden = array( 'replybox-meta', 'postcustom' );
		$this->assertSame( $hidden, Kind_Metabox::hidden_meta_boxes( $hidden, WP_Screen::get( 'page' ) ) );
		$this->assertSame( 'not an array', Kind_Metabox::hidden_meta_boxes( 'not an array', WP_Screen::get( 'post' ) ) );
	}
}
