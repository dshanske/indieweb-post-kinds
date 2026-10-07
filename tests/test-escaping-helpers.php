<?php

class EscapingHelpersTest extends WP_UnitTestCase {
	const XSS = '<script>alert(1)</script>';

	private function assert_inert( $html ) {
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( '" onerror', $html );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	public function test_hcard_escapes_author_data() {
		$card = Kind_View::get_hcard(
			array(
				'name'  => '<b>Bob</b>' . self::XSS,
				'url'   => 'https://example.com/" onmouseover="x',
				'photo' => 'https://example.com/p.png" onerror="x',
			)
		);
		$this->assert_inert( $card );
		$this->assertStringContainsString( 'h-card', $card );
		$this->assertStringContainsString( 'Bob', $card );
	}

	public function test_hcard_markup() {
		// The format strings used width specifiers (%3s) instead of positions, which
		// padded short values with spaces and left the dimensions unquoted.
		$this->assertSame(
			'<a href="https://example.com/bob" class="h-card p-author"><img class="u-photo" src="https://example.com/p.png" alt="Bob" width="32" height="32" />Bob</a>',
			Kind_View::get_hcard(
				array(
					'name'  => 'Bob',
					'url'   => 'https://example.com/bob',
					'photo' => 'https://example.com/p.png',
				)
			)
		);
	}

	public function test_hcard_drops_unsafe_urls() {
		$card = Kind_View::get_hcard(
			array(
				'name' => 'Bob',
				'url'  => 'javascript:alert(1)',
			)
		);
		$this->assert_inert( $card );
		$this->assertSame( '<span class="h-card p-author">Bob</span>', $card );
	}

	public function test_hcard_with_list_values() {
		$card = Kind_View::get_hcard(
			array(
				'name' => array( 'Ann', 'Bob' ),
				'url'  => array( 'https://example.com/ann' ),
			)
		);
		$this->assertStringContainsString( 'href="https://example.com/ann"', $card );
		$this->assertStringContainsString( 'Ann', $card );
	}

	public function test_cite_title_escapes_name_and_url() {
		$title = Kind_View::get_cite_title(
			array(
				'name' => '<b>Post</b>' . self::XSS,
				'url'  => 'https://example.com/post',
			)
		);
		$this->assert_inert( $title );
		$this->assertStringContainsString( 'href="https://example.com/post"', $title );

		$unsafe = Kind_View::get_cite_title(
			array(
				'name' => 'Post',
				'url'  => 'javascript:alert(1)',
			)
		);
		$this->assertSame( '<span class="p-name">Post</span>', $unsafe );
	}

	public function test_cite_title_without_name_uses_the_url() {
		$this->assertSame(
			'<a href="https://example.com/post" class="p-name u-url">https://example.com/post</a>',
			Kind_View::get_cite_title( array( 'url' => 'https://example.com/post' ) )
		);
	}

	public function test_site_name_is_escaped() {
		$site = Kind_View::get_site_name( array( 'publication' => '<b>Site</b>' . self::XSS ) );
		$this->assert_inert( $site );
		$this->assertStringContainsString( 'Site', $site );
	}

	public function test_summary_is_plain_text() {
		$this->assertSame(
			'<blockquote class="p-summary">Hello world</blockquote>',
			Kind_View::get_summary( array( 'summary' => '<p>Hello <b>world</b></p>' . self::XSS ) )
		);
		$this->assertSame(
			"<blockquote class=\"p-summary\">Line one<br />\nLine two</blockquote>",
			Kind_View::get_summary( array( 'summary' => "Line one\nLine two" ) )
		);
		$this->assertSame(
			'<blockquote class="p-summary">From value</blockquote>',
			Kind_View::get_summary(
				array(
					'summary' => array(
						'html'  => '<p>From html</p>',
						'value' => 'From value',
					),
				)
			)
		);
		$this->assertSame( '&lt;3 &amp; more', strip_tags( Kind_View::get_summary( array( 'summary' => '<3 & more' ) ) ) );
		$this->assertSame( '', Kind_View::get_summary( array( 'summary' => '<p></p>' ) ) );
		$this->assertSame( '', Kind_View::get_summary( array() ) );
	}

	public function test_url_link_is_escaped() {
		$link = Kind_View::get_url_link( 'javascript:alert(1)', '<b>Name</b>' );
		$this->assert_inert( $link );
	}

	public function test_kind_get_the_title_escapes_the_cited_name() {
		$post = self::factory()->post->create(
			array(
				'post_title'   => '',
				'post_content' => '',
				'meta_input'   => array(
					'mf2_bookmark-of' => array(
						array(
							'type'       => array( 'h-cite' ),
							'properties' => array(
								'name' => array( '<b>Bookmarked</b>' . self::XSS ),
								'url'  => array( 'https://example.com/b' ),
							),
						),
					),
				),
			)
		);
		set_post_kind( $post, 'bookmark' );
		$title = kind_get_the_title( $post );
		$this->assert_inert( $title );
		$this->assertStringContainsString( 'Bookmarked', $title );
		$this->assert_inert( kind_get_the_link( $post ) );
	}

	public function test_before_kind_text_is_escaped() {
		register_post_kind(
			'pk-verb',
			array(
				'singular_name' => 'Verb',
				'verb'          => '<b>Did</b>',
			)
		);
		$this->assert_inert( Kind_Taxonomy::get_before_kind( 'pk-verb', 'text' ) );
	}
}
