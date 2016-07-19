<div id="kindproperties-navigation">
    <h2 class="nav-tab-wrapper current">
        <a class="nav-tab nav-tab-active" href="javascript:;">Response</a>
        <a class="nav-tab" href="javascript:;">Citation</a>
        <a class="nav-tab" href="javascript:;">Time</a>
				<?php
					// Add Extra Location Tab to be Enabled only if Class Exists
				if ( class_exists( 'class-sloc-meta' ) ) {
					// echo '<a class="nav-tab" href="javascript:;">Location</a>';
				}
				?>
	</h2>
	<?php
		include_once( 'tab-response.php' );
		include_once( 'tab-citation.php' );
		include_once( 'tab-time.php' );
	if ( class_exists( 'class-sloc-meta' ) ) {
		// include_once( 'tab-location.php' );
	}
	?>
</div>
