<?php
/*
 * Photo Template
 *
 */

$meta = new Kind_Meta( get_the_ID() );
$photos = get_attached_media( 'image', get_the_ID() );
?>
<section class="response">
<header>
<?php echo Kind_Taxonomy::get_icon( 'photo' );
?>
</header>
</section>
<p>
<?php 
if ( has_post_thumbnail( get_the_ID() ) ) {
	the_post_thumbnail( 'large', array( 'class' => 'u-featured' ) );
}
elseif ( $photos ) {
	echo gallery_shortcode( 
		array( 
			'id' => get_the_ID() , 
			'size' => 'large', 
			'columns' => 1,
			'link' => 'file'
		) );
}
?>
</p>
<?php
