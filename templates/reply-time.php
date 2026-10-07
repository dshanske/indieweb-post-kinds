<?php $duration = divide_interval( $kind_post->get_duration() ); ?>

<div id="kind-time" class="hide-if-js">
	<h4><?php esc_html_e( 'Duration', 'indieweb-post-kinds' ); ?></h4>
	<p class="field-row">
	<label for="duration" class="eighth">
		<?php esc_html_e( 'Years', 'indieweb-post-kinds' ); ?>
			<input type="number" name="duration_years" id="duration_years" size="3" value="<?php echo absint( $duration['Y'] ); ?>" />
	</label>
	<label for="duration" class="eighth">
		<?php esc_html_e( 'Months', 'indieweb-post-kinds' ); ?>
			<input type="number" name="duration_months" id="duration_months" size="2" min="0" max="12" value="<?php echo absint( $duration['M'] ); ?>" />
	</label>
	<label for="duration" class="eighth">
		<?php esc_html_e( 'Days', 'indieweb-post-kinds' ); ?>
			<input type="number" name="duration_days" id="duration_days" size="2" min="0" max="30" value="<?php echo absint( $duration['D'] ); ?>" />
	</label>
	<p>
	<p class="field-row">
	<label for="duration" class="eighth">
		<?php esc_html_e( 'Hours', 'indieweb-post-kinds' ); ?>
			<input type="number" name="duration_hours" id="duration_hours" size="2" min="0" max="12" value="<?php echo absint( $duration['H'] ); ?>" />
	</label>
	<label for="duration" class="eighth">
		<?php esc_html_e( 'Minutes', 'indieweb-post-kinds' ); ?>
			<input type="number" name="duration_minutes" id="duration_minutes" size="2" min="0" max="60" value="<?php echo absint( $duration['I'] ); ?>" />
	</label>
	<label for="duration" class="eighth">
		<?php esc_html_e( 'Seconds', 'indieweb-post-kinds' ); ?>
			<input type="number" name="duration_seconds" id="duration_seconds" size="2" min="0" max="60" value="<?php echo absint( $duration['S'] ); ?>" />
	</label>
	</p>
	<h4> <?php esc_html_e( 'Start Time and End Time will be Used to Calculate Duration if Duration Not Set.', 'indieweb-post-kinds' ); ?> </h4>
	<p class="field-row">
                <?php echo Kind_Metabox::kind_the_time( 'mf2_start', __( 'Start Time', 'indieweb-post-kinds' ), $kind_post->get_datetime_property( 'start' ), 'start' ); ?>
                <?php echo Kind_Metabox::kind_the_time( 'mf2_end', __( 'End Time', 'indieweb-post-kinds' ), $kind_post->get_datetime_property( 'end' ), 'end' ); ?>
        </p>
</div>
