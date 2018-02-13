<div id="kind-details" class="hide-if-js">
        <h4><?php _e( 'Information on what you are responding to', 'indieweb-post-kinds' ); ?></h4>
	<label for="cite_summary">
		<?php _e( 'Summary/Quote', 'indieweb-post-kinds' ); ?>
	<textarea name="cite_summary" id="cite_summary" data-role="none" class="widefat"><?php echo ifset( $cite['summary'] );?></textarea>
	</label>
	<label for="cite_tags">
		<?php _e( 'Tags (semicolon separated)', 'indieweb-post-kinds' ); ?>
	<textarea name="cite_tags" id="cite_tags" data-role="none" class="widefat"><?php echo ifset( $tags );?></textarea>
	</label>
	<p class="field-row">
	<label for="cite_featured" class="three-quarters">
		<?php _e( 'Featured Image', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_featured" id="cite_featured" class="widefat" value="<?php echo ifset( $cite['featured'] ); ?>" />
	</label>
	</p>
	<p class="field-row">
	<label for="cite_publication" class="three-quarters">
		<?php _e( 'Site Name/Publication/Album', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_publication" id="cite_publication" class="widefat" value="<?php echo ifset( $cite['publication'] ); ?>" />
	</label>
	</p>
	<p class="field-row">
 		<?php echo Kind_Metabox::kind_the_time( 'cite_published', __( 'Published/Released', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( ifset( $cite['published'] ) ), 'published' ); ?>
		<?php echo Kind_Metabox::kind_the_time( 'cite_updated', __( 'Updated', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( ifset( $cite['updated'] ) ), 'updated' ); ?>
	</p>
        <h4> <?php _e( 'Start Time and End Time will be Used to Calculate Duration', 'indieweb-post-kinds' ); ?> </h4>
	<p class="field-row" id="duration">
		<?php echo Kind_Metabox::kind_the_time( 'mf2_start', __( 'Start Time', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( $mf2_post->get( 'dt-start', true ) ), 'start' ); ?>
		<?php echo Kind_Metabox::kind_the_time( 'mf2_end', __( 'End Time', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( $mf2_post->get( 'dt-end', true ) ), 'end' ); ?>
	</p>
</div>
