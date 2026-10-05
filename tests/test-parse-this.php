<?php

class ParseThisTest extends WP_UnitTestCase {
	public function test_bundled_parse_this_is_current() {
		$this->assertTrue( defined( 'PARSE_THIS_VERSION' ) );
		$this->assertTrue( Post_Kinds_Plugin::parse_this_is_current() );
	}

	public function test_namespaced_api_is_available() {
		$this->assertTrue( function_exists( 'ParseThis\\mf2_to_jf2' ) );
		$this->assertTrue( class_exists( 'ParseThis\\MF2_Utils' ) );
		$jf2 = \ParseThis\mf2_to_jf2(
			array(
				'type'       => array( 'h-cite' ),
				'properties' => array( 'name' => array( 'Example' ) ),
			)
		);
		$this->assertSame( 'Example', $jf2['name'] );
	}

	public function test_parse_route_is_registered() {
		$this->assertArrayHasKey( '/parse-this/1.0/parse', rest_get_server()->get_routes() );
	}

	public function test_parse_this_source_names_the_loading_plugin() {
		$this->assertNotSame( '', Post_Kinds_Plugin::parse_this_source() );
	}

	public function test_admin_notice_markup() {
		// Uses core's wp_get_admin_notice() on WordPress 6.4+ and the polyfill on 6.2-6.3.
		$this->assertSame(
			'<div class="notice notice-warning is-dismissible"><p>Hello</p></div>',
			wp_get_admin_notice(
				'Hello',
				array(
					'type'        => 'warning',
					'dismissible' => true,
				)
			)
		);
	}

	public function test_admin_notice_outputs_escaped_markup() {
		ob_start();
		wp_admin_notice( 'Hi <script>x</script>', array( 'type' => 'error' ) );
		$output = ob_get_clean();
		$this->assertStringContainsString( 'class="notice notice-error"', $output );
		$this->assertStringNotContainsString( '<script>', $output );
	}
}
