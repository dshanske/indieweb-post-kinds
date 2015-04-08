<?php



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
     $include = explode(",", POST_KIND_INCLUDE);
     $include = array_merge($include, array ( 'note', 'reply', 'article', 'photo') );
     // If Simple Location is Enabled, include the check-in type
     if (function_exists('sloc_init') ) {
        $include[] = 'checkin';
     }
     $option = get_option('iwt_options');
     if ($option['linksharing']==1) {
        $include = array_merge($include, array ( 'like', 'bookmark', 'favorite', 'repost') );
     }
     if ($option['mediacheckin']==1) {
        $include = array_merge($include, array ( 'watch', 'listen') );
          }

     // Filter Kinds
     $include = array_unique(apply_filters('kind_include', $include));
     // Note cannot be removed or disabled without hacking the code
     if (!in_array('note', $include) ) {
      $include[]='note';
     }
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
		    if (in_array($slug, $include) )
			{
                   		 echo "<li id='$id' class='kind-$slug'><label class='selectit'>";
                    		echo "<input type='radio' id='in-$id' name='tax_input[kind]'".checked($current,$term->term_id,false)."value='$term->term_id' />$strings[$slug]<br />";
                   		echo "</label></li>";
			}
                }
      echo '</ul></div>';
  }
