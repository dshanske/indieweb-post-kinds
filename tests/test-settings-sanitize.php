<?php

class SettingsSanitizeTest extends WP_UnitTestCase {
	public function test_kind_lists_keep_only_registered_kinds() {
		update_option( 'kind_termslist', array( 'reply', 'bookmark', 'bogus', '<x>', 'reply' ) );
		$this->assertSame( array( 'reply', 'bookmark' ), get_option( 'kind_termslist' ) );
		update_option( 'kind_firehose', 'not-an-array' );
		$this->assertSame( array(), get_option( 'kind_firehose' ) );
	}

	public function test_default_kind_must_be_registered() {
		update_option( 'kind_default', 'reply' );
		$this->assertSame( 'reply', get_option( 'kind_default' ) );
		update_option( 'kind_default', 'bogus"><x>' );
		$this->assertSame( 'note', get_option( 'kind_default' ) );
	}

	public function test_display_uses_an_allowlist() {
		update_option( 'kind_display', 'both' );
		$this->assertSame( 'both', get_option( 'kind_display' ) );
		update_option( 'kind_display', 'evil' );
		$this->assertSame( 'icon', get_option( 'kind_display' ) );
	}

	public function test_checkboxes_are_stored_as_integers() {
		update_option( 'kind_embeds', 'yes' );
		$this->assertSame( 1, get_option( 'kind_embeds' ) );
		update_option( 'kind_bottom', '0' );
		$this->assertSame( 0, get_option( 'kind_bottom' ) );
		update_option( 'kind_title', '' );
		$this->assertSame( 0, get_option( 'kind_title' ) );
	}

	public function test_kses_must_be_a_json_object() {
		update_option( 'kind_kses', '{"a":{"href":true}}' );
		$this->assertSame( '{"a":{"href":true}}', get_option( 'kind_kses' ) );
		update_option( 'kind_kses', 'not json' );
		$this->assertSame( '{"a":{"href":true}}', get_option( 'kind_kses' ) );
	}
}
