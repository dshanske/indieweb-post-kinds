<?php

/**
 * Provides the 'Other' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
?>
	
<div class="inside hidden">
<p><?php _e( 'Other Properties', 'indieweb-post-kinds' ); ?></p>
	<div id="kindmetatab-other">

	<?php echo self::kind_the_time( 'start', __('Start Time', 'indieweb-post-kinds' ), $time ); ?>
	<br />
	<?php echo self::kind_the_time( 'end', __('End Time', 'indieweb-post-kinds' ), $time ); ?>
	<br />
	<?php echo self::rsvp_select( 'yes' ); ?>

	</div><!-- #kindmetatab-time -->

</div>
