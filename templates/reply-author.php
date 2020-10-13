	<div id="kind-author" class="hide-if-js">
	<h4> <?php _e( 'Information on the Author or Artist of the Piece', 'indieweb-post-kinds' ); ?></h4>
		<?php _e( '(Multiple Entries separated by semicolon)', 'indieweb-post-kinds' ); ?><BR />
	<p class="field-row">
	<label for="cite_author_name" class="three-quarters">
		<?php _e( 'Author', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_author_name" id="cite_author_name" class="widefat" value="<?php echo $cite['author']['name']; ?>" />
	</label>
	</p>
	<p class="field-row">
	<label for="cite_author_url" class="three-quarters">
		<?php _e( 'Author URL', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_author_url" id="cite_author_url" class="widefat" value="<?php echo $cite['author']['url']; ?>" />
	</label>
	</p>
	<p class="field-row">
	<label for="cite_author_photo" class="three-quarters">
		<?php _e( 'Author Photo URL', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_author_photo" id="cite_author_photo" class="widefat" value="<?php echo $cite['author']['photo']; ?>" />
	</label>
	</p>
	</div>
