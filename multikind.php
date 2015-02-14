<?php
// Multi-Kind Selector and Functions


add_action( 'admin_menu', 'kind_remove_meta_box');
function kind_remove_meta_box(){
  remove_meta_box('kinddiv', 'post', 'normal');
}

//Add new taxonomy meta box
 add_action( 'add_meta_boxes', 'kind_add_meta_box');
 function kind_add_meta_box() {
     add_meta_box( 'kind_select', 'Post Kinds','kind_select_metabox','post' ,'side','core');
 }
 
  function kind_select_metabox( $post ) {
     $strings=get_post_kind_strings();
     $exclude = explode(",", POST_KIND_EXCLUDE);
     $default = get_term_by('slug', 'note', 'kind');
     $terms = get_terms('kind', array('hide_empty' => 0) );
     $postterms = get_the_terms( $post->ID, 'kind' );
     $current = ($postterms ? array_pop($postterms) : false);
     $current = ($current ? $current->term_id : $default->term_id);
     echo '<div id="kind-all">';
     echo '<ul id="kindchecklist" class="list:kind categorychecklist form-no-clear">';
     foreach($terms as $term){
                    $id = 'kind-' . $term->term_id;
		    $slug = $term->slug;
		    if (!in_array($slug, $exclude) )
			{
                   		 echo "<li id='$id' class='kind-$slug'><label class='selectit'>";
                    		echo "<input type='checkbox' id='in-$id' name='tax_input[kind]'".checked($current,$term->term_id,false)."value='$term->term_id' />$strings[$slug]<br />";
                   		echo "</label></li>";
			}
                }
      echo '</ul></div>';
  }
