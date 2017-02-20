<?php

/**
 * Provides the 'Other' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
?>
	
<div class="inside hidden">
<h4><?php _e( 'Other Properties', 'indieweb-post-kinds' ); ?></h4>
	<div id="kindmetatab-other">

	<p> <?php _e( 'Start Time and End Time will be Used to Calculate Duration', 'indieweb-post-kinds' ); ?> </p>
	<?php echo self::kind_the_time( 'start', __('Start Time', 'indieweb-post-kinds' ), $time ); ?>
	<br />
	<?php echo self::kind_the_time( 'end', __('End Time', 'indieweb-post-kinds' ), $time ); ?>
	<br />
	<?php echo self::rsvp_select( 'yes' ); ?>

	</div><!-- #kindmetatab-other -->

</div>
