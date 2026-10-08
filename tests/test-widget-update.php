<?php

class WidgetUpdateTest extends WP_UnitTestCase {
	public function test_post_widget_sanitizes_its_settings() {
		$widget = new Kind_Post_Widget();
		$this->assertSame(
			array(
				'title'  => 'My Posts',
				'kind'   => 'note',
				'number' => 5,
			),
			$widget->update(
				array(
					'title'  => 'My <b>Posts</b>',
					'kind'   => 'bogus',
					'number' => 'abc',
				),
				array()
			)
		);
		$this->assertSame(
			array(
				'title'  => 'Recent & new',
				'kind'   => 'reply',
				'number' => 3,
			),
			$widget->update(
				array(
					'title'  => 'Recent & new',
					'kind'   => 'reply',
					'number' => '3',
				),
				array()
			)
		);
	}

	public function test_menu_widget_sanitizes_its_settings() {
		$widget = new Kind_Menu_Widget();
		$this->assertSame(
			array(
				'title'     => 'Kinds',
				'termslist' => array( 'reply' ),
				'count'     => 1,
				'all'       => 0,
			),
			$widget->update(
				array(
					'title'     => 'Kinds<script>alert(1)</script>',
					'termslist' => array( 'reply', 'bogus', array( 'x' ), 'reply' ),
					'count'     => '1',
					'all'       => '0',
				),
				array()
			)
		);
		$this->assertSame(
			array(
				'title'     => '',
				'termslist' => array(),
				'count'     => 0,
				'all'       => 0,
			),
			$widget->update( array(), array() )
		);
	}
}
