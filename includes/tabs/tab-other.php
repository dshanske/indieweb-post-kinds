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
	<?php echo self::kind_the_time( 'mf2_start', __( 'Start Time', 'indieweb-post-kinds' ), self::divide_time( $mf2_post->get( 'dt-start', true ) ) ); ?>
	<br />
	<?php echo self::kind_the_time( 'mf2_end', __( 'End Time', 'indieweb-post-kinds' ), self::divide_time( $mf2_post->get( 'dt-end', true ) ) ); ?>
	<br />
	<?php echo self::rsvp_select( $mf2_post->get( 'rsvp', true ) ); ?>

	</div><!-- #kindmetatab-other -->

</div>
