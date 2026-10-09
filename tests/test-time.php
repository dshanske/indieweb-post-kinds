<?php

class TimeTest extends WP_UnitTestCase {
	public function test_safe_datetime_and_interval() {
		$this->assertInstanceOf( DateTimeImmutable::class, kind_safe_datetime( '2026-10-06T10:30:00+02:00' ) );
		$this->assertInstanceOf( DateTime::class, kind_safe_datetime( '2026-10-06', null, false ) );
		$this->assertInstanceOf( DateTimeImmutable::class, kind_safe_datetime( new DateTime( '2026-10-06' ) ) );
		$this->assertFalse( kind_safe_datetime( 'not a date' ) );
		$this->assertFalse( kind_safe_datetime( '' ) );
		$this->assertFalse( kind_safe_datetime( array( '2026-10-06' ) ) );

		$this->assertInstanceOf( DateInterval::class, kind_safe_interval( 'PT3M30S' ) );
		$this->assertFalse( kind_safe_interval( 'three minutes' ) );
		$this->assertFalse( kind_safe_interval( '' ) );
	}

	/**
	 * A post holding malformed values, as old meta or a bad Micropub request can.
	 */
	private function malformed_post() {
		$post = self::factory()->post->create(
			array(
				'meta_input' => array(
					'mf2_start'    => 'not a date',
					'mf2_duration' => array( 'three minutes' ),
				),
			)
		);
		set_post_kind( $post, 'listen' );
		return $post;
	}

	public function test_kind_post_with_malformed_dates() {
		$kind_post = new Kind_Post( $this->malformed_post() );
		$this->assertFalse( $kind_post->get_datetime_property( 'start' ) );
		$this->assertFalse( $kind_post->get( 'start' ) );
		$this->assertFalse( $kind_post->get_duration() );
		$this->assertFalse( $kind_post->set_datetime_property( 'end', 'garbage' ) );
		$this->assertFalse( $kind_post->set_duration( 'garbage' ) );
	}

	public function test_set_datetime_property_accepts_immutable_dates() {
		$kind_post = new Kind_Post( self::factory()->post->create() );
		$this->assertNotFalse( $kind_post->set_datetime_property( 'start', new DateTimeImmutable( '2026-10-06T10:30:00+02:00' ) ) );
		$this->assertSame( '2026-10-06T10:30:00+02:00', $kind_post->get_datetime_property( 'start' )->format( DATE_W3C ) );
	}

	public function test_post_with_malformed_dates_still_renders() {
		$post            = $this->malformed_post();
		$GLOBALS['post'] = get_post( $post );
		setup_postdata( $GLOBALS['post'] );
		$display = Kind_View::get_display( $post );
		wp_reset_postdata();
		$this->assertIsString( $display );
	}

	public function test_display_helpers_with_malformed_input() {
		$this->assertSame( '', Kind_View::display_duration( 'three minutes' ) );
		$this->assertSame( '', Kind_Time::display_formatted_datetime( 'not a date' ) );
		$this->assertFalse( Kind_Time::divide_datetime( 'not a date' ) );
		$this->assertSame( array( 'Y' => 0, 'M' => 0, 'D' => 0, 'H' => 0, 'I' => 0, 'S' => 0 ), Kind_Time::divide_interval( 'three minutes' ) );
		$this->assertNotSame( '', Kind_View::display_duration( 'PT3M30S' ) );
	}

	public function test_offset_to_seconds_sign() {
		$this->assertSame( 19800, tz_offset_to_seconds( '+05:30' ) );
		$this->assertSame( -14400, tz_offset_to_seconds( '-04:00' ) );
		$this->assertSame( 0, tz_offset_to_seconds( '+00:00' ) );
		$this->assertSame( 3600, tz_offset_to_seconds( '+0100' ) );
	}

	public function test_gmt_offsets() {
		$offsets = get_gmt_offsets();
		$this->assertContains( '+00:00', $offsets );
		$this->assertContains( '-05:00', $offsets );
	}

	public function test_gmt_offsets_are_cached_for_the_day() {
		$offsets = get_gmt_offsets();
		$this->assertSame( $offsets, wp_cache_get( 'gmt_offsets_' . gmdate( 'Y-m-d' ), 'post-kinds' ) );
		$this->assertSame( $offsets, get_gmt_offsets() );
	}

	public function test_build_datetime() {
		$this->assertSame( '2026-10-06T10:30:00+02:00', Kind_Time::build_datetime( '2026-10-06', '10:30:00', '+02:00' )->format( DATE_W3C ) );
		$this->assertSame( '2026-10-06T10:30:00+02:00', Kind_Time::build_datetime( '2026-10-06', '10:30', '+02:00' )->format( DATE_W3C ) );
		$this->assertSame( '2026-10-06T10:30:00-05:00', Kind_Time::build_datetime( '2026-10-06', '10:30:00', new DateTimeZone( '-05:00' ) )->format( DATE_W3C ) );
		$this->assertFalse( Kind_Time::build_datetime( '', '10:30:00', '+02:00' ) );
		$this->assertFalse( Kind_Time::build_datetime( 'not-a-date', '10:30:00', '+02:00' ) );
	}

	public function test_build_interval() {
		$this->assertSame( 'P1DT2H', Kind_Time::build_interval( array( 'Y' => 0, 'M' => 0, 'D' => 1, 'H' => 2, 'I' => 0, 'S' => 0 ) ) );
		$this->assertSame( 'P1Y2M', Kind_Time::build_interval( array( 'Y' => 1, 'M' => 2 ) ) );
		$this->assertSame( 'PT3M30S', Kind_Time::build_interval( array( 'I' => 3, 'S' => 30 ) ) );
		$this->assertSame( '', Kind_Time::build_interval( array( 'H' => 0 ) ) );
	}

	public function test_deprecated_global_wrappers() {
		foreach ( array( 'display_formatted_datetime', 'divide_datetime', 'build_datetime', 'get_datetime_offset', 'divide_interval', 'build_interval' ) as $name ) {
			$this->setExpectedDeprecated( $name );
		}
		$date = new DateTimeImmutable( '2026-10-06T10:30:00+02:00' );
		$this->assertSame( Kind_Time::display_formatted_datetime( $date ), display_formatted_datetime( $date ) );
		$this->assertSame( Kind_Time::divide_datetime( $date ), divide_datetime( $date ) );
		$this->assertEquals( Kind_Time::build_datetime( '2026-10-06', '10:30', '+02:00' ), build_datetime( '2026-10-06', '10:30', '+02:00' ) );
		$this->assertSame( '+02:00', get_datetime_offset( $date ) );
		$this->assertSame( Kind_Time::divide_interval( 'PT3M' ), divide_interval( 'PT3M' ) );
		$this->assertSame( 'PT3M', build_interval( array( 'I' => 3 ) ) );
	}
}
