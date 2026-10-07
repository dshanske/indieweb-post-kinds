<?php

class EscapingWidgetsSettingsTest extends WP_UnitTestCase {
	const XSS = '<script>alert(1)</script>';

	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
		register_post_kind(
			'pk-hostile',
			array(
				'singular_name' => 'Hostile' . self::XSS,
				'name'          => 'Hostiles' . self::XSS,
				'description'   => 'Described' . self::XSS,
				'show'          => true,
			)
		);
		update_option( 'kind_termslist', array( 'reply', 'pk-hostile' ) );
	}

	private function widget_args() {
		return array(
			'before_widget' => '<section>',
			'after_widget'  => '</section>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
	}

	public function test_post_widget_form_markup() {
		ob_start();
		( new Kind_Post_Widget() )->form( array( 'title' => 'My "Posts"' ) );
		$html = ob_get_clean();
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertMatchesRegularExpression( '/name="[^"]*\[title\]" id=/', $html );
		$this->assertStringContainsString( 'value="My &quot;Posts&quot;"', $html );
		$this->assertStringNotContainsString( '<br />', $html );
	}

	public function test_menu_widget_form_markup() {
		ob_start();
		( new Kind_Menu_Widget() )->form( array( 'title' => 'Kinds & more' ) );
		$html = ob_get_clean();
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertMatchesRegularExpression( '/name="[^"]*\[title\]" id=/', $html );
		$this->assertStringContainsString( 'value="Kinds &amp; more"', $html );
	}

	public function test_menu_widget_output() {
		$post = self::factory()->post->create();
		set_post_kind( $post, 'pk-hostile' );
		ob_start();
		( new Kind_Menu_Widget() )->widget(
			$this->widget_args(),
			array(
				'title'     => 'Kinds & more',
				'termslist' => array( 'pk-hostile' ),
				'count'     => 1,
				'all'       => 1,
			)
		);
		$html = ob_get_clean();
		$this->assertStringNotContainsString( '<script', $html );
		// Core's widget_title filter escapes the title once.
		$this->assertStringContainsString( '<h2>Kinds &#038; more</h2>', $html );
		$this->assertMatchesRegularExpression( '/<a title="[^"]* Feed" type="text\/html"/', $html );
	}

	public function test_post_widget_output() {
		$post = self::factory()->post->create( array( 'post_title' => 'A reply' ) );
		set_post_kind( $post, 'reply' );
		ob_start();
		( new Kind_Post_Widget() )->widget(
			$this->widget_args(),
			array(
				'title'  => 'Recent',
				'kind'   => 'reply',
				'number' => 5,
			)
		);
		$html = ob_get_clean();
		$this->assertStringContainsString( '<h2>Recent</h2>', $html );
		$this->assertStringContainsString( 'A reply', $html );
	}

	public function test_settings_fields_escape_kind_names() {
		ob_start();
		Kind_Config::termcheck_callback();
		Kind_Config::kindmultiselect_callback( array( 'name' => 'kind_firehose' ) );
		Kind_Config::defaultkind_callback();
		Kind_Config::radio_callback(
			array(
				'name'    => 'kind_display',
				'class'   => '',
				'options' => array( 'icon' => 'Icon' . self::XSS ),
			)
		);
		$html = ob_get_clean();
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( 'Hostile&lt;script&gt;', $html );
		$this->assertStringNotContainsString( '/>Hostile', $html );
	}

	public function test_textbox_escapes_the_option() {
		update_option( 'kind_kses', '</textarea>' . self::XSS );
		ob_start();
		Kind_Config::textbox_callback( array( 'name' => 'kind_kses' ) );
		$html = ob_get_clean();
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertSame( 1, substr_count( $html, '</textarea>' ) );

		update_option( 'kind_kses', array( 'a' => array( 'href' => true ) ) );
		ob_start();
		Kind_Config::textbox_callback( array( 'name' => 'kind_kses' ) );
		$this->assertStringContainsString( '&quot;href&quot;: true', ob_get_clean() );
	}
}
