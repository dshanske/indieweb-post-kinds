<?php

/**
 * Provides the 'Time' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */

$tzlist = self::kind_get_timezones();
$tz_seconds = get_option( 'gmt_offset' ) * 3600;
$offset = self::tz_seconds_to_offset( $tz_seconds );
$start_offset = ifset( $time['start_offset'] ) ?: $offset;
$end_offset = ifset( $time['end_offset'] ) ?: $offset;

?>
	
<div class="inside hidden">
	<p>Time Properties</p>
	<div id="kindmetatab-time">
	  <label for="published"><?php _e( 'Published/Start', 'Post kind' ); ?></label><br/> 
	  <input type="date" name="time[start_date]" id="start_date" value="<?php echo ifset( $time['start_date'] ); ?>"/>
	  <input type="time" name="time[start_time]" id="start_time" step="1" value="<?php echo ifset( $time['start_time'] ); ?>"/>
	<select name="time[start_offset]" id="start_offset">
		<?php
		foreach ( $tzlist as $key => $value ) {
			echo '<option value="' . $value . '"';
			if ( $start_offset == $value ) {
				echo ' selected';
			}
			echo '>GMT' . $value . '</option>';
		}
		?>
	</select>
<br/>
	  <label for="updated"><?php _e( 'Updated/End', 'Post kind' ); ?></label><br/>
	  <input type="date" name="time[end_date]" id="end_date" value="<?php echo ifset( $time['end_date'] ); ?>"/>
	  <input type="time" name="time[end_time]" id="end_time" step="1" value="<?php echo ifset( $time['end_time'] ); ?>"/>
	<select name="time[end_offset]" id="end_offset">
		<?php
		foreach ( $tzlist as $key => $value ) {
			echo '<option value="' . $value . '"';
			if ( $end_offset == $value ) {
				echo ' selected';
			}
			echo '>GMT' . $value . '</option>';

		}
		?>
	</select><br />
	</div><!-- #kindmetatab-time -->

</div>
