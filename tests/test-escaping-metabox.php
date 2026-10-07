<?php

class EscapingMetaboxTest extends WP_UnitTestCase {
	const BREAKOUT = '"><script>alert(1)</script>';

	public function test_metabox_fields_cannot_break_out() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$post = self::factory()->post->create(
			array(
				'meta_input' => array(
					'mf2_in-reply-to' => array(
						array(
							'type'       => array( 'h-cite' ),
							'properties' => array(
								'url'         => array( 'https://example.com/' . self::BREAKOUT ),
								'name'        => array( 'Name' . self::BREAKOUT ),
								'summary'     => array( '<p>Quoted &lt;3 text</p></textarea><script>alert(1)</script>' ),
								'publication' => array( 'Site' . self::BREAKOUT ),
								'featured'    => array( 'https://example.com/f.png' . self::BREAKOUT ),
								'category'    => array( 'tag</textarea><script>alert(1)</script>' ),
								'author'      => array(
									array(
										'type'       => array( 'h-card' ),
										'properties' => array(
											'name'  => array( 'Ann' . self::BREAKOUT ),
											'url'   => array( 'https://example.com/ann' . self::BREAKOUT ),
											'photo' => array( 'https://example.com/ann.png' . self::BREAKOUT ),
										),
									),
								),
							),
						),
					),
				),
			)
		);
		set_post_kind( $post, 'reply' );
		$GLOBALS['post'] = get_post( $post );
		setup_postdata( $GLOBALS['post'] );
		set_current_screen( 'post' );

		ob_start();
		Kind_Metabox::reply_metabox( $GLOBALS['post'], array() );
		$html = ob_get_clean();
		wp_reset_postdata();

		$this->assertStringContainsString( 'name="cite_url"', $html );
		$this->assertStringNotContainsString( '<script', $html );
		// The payload never appears unescaped (ordinary markup also contains "><).
		$this->assertStringNotContainsString( self::BREAKOUT, $html );
		$this->assertStringNotContainsString( '</textarea><', $html );
		// Values survive, escaped, for editing.
		$this->assertStringContainsString( 'value="Name&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html );
		// The summary is plain text: tags stripped, entities shown as characters.
		$this->assertMatchesRegularExpression( '#<textarea name="cite_summary"[^>]*>Quoted &lt;3 text</textarea>#', $html );
	}

	public function test_select_helpers_escape_values() {
		$this->assertStringContainsString( '<option value="yes" selected>', Kind_Metabox::rsvp_choice( 'yes' ) );
		$time = Kind_Metabox::kind_the_time( 'cite_published', '<b>Label</b>', '2026-10-06T10:30:00+02:00', 'published"><x' );
		$this->assertStringNotContainsString( '<b>', $time );
		$this->assertStringNotContainsString( '"><x', $time );
		$this->assertStringContainsString( 'value="2026-10-06"', $time );
	}
}
