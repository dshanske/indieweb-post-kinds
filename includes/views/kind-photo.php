<?php
/*
 * Photo Template
 *
 */

 $kind = get_post_kind_slug( get_the_ID() );
 $meta = new Kind_Meta( get_the_ID() );
 $author = Kind_View::get_hcard( $meta->get_author() );
 $site_name = Kind_View::get_site_name( $meta->get_cite(), $meta->get_url() );
 $title = Kind_View::get_cite_title( $meta->get_cite(), $meta->get_url() );
 $photos = $meta->get( 'photo' );

?>
<section>
<header>
<?php echo Kind_Taxonomy::get_icon( $kind );

	if ( $title ) {
		echo $title;
	}
	if ( $author ) {
		echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( $site_name ) {
		echo '<em>(<span class="p-publication">' . $site_name . '</span>)</em>';
	}

?>
</header>
<p>
<?php
for ($i = 0; $i < count($photos); $i++) { ?>

		<img src="<?php echo esc_url( $photos[$i] ); ?>" class="u-photo">
<?php } ?>
</p>
</section>

<?php
