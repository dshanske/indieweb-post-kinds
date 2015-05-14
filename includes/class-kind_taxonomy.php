<?php

add_action( 'init' , array('kind_taxonomy', 'init' ) );
// Register Kind Taxonomy
add_action( 'init', array( 'kind_taxonomy' , 'register' ), 1 );


// On Activation, add terms
register_activation_hook( __FILE__, array( 'kind_taxonomy' , 'activate_kinds' ) );

// The kind_taxonomy class sets up the kind taxonomy and its behavior
class kind_taxonomy {
	public static function init() {
		// Semantic Linkbacks Override for Comments
		add_action( 'init', array( 'kind_taxonomy' , 'remove_semantics' ), 11 );

		// Add Kind Permalinks
		add_filter('post_link', array( 'kind_taxonomy' , 'kind_permalink' ) , 10, 3 );
		add_filter('post_type_link', array( 'kind_taxonomy' , 'kind_permalink') , 10 , 3);

		// Add Classes to Post and Body
		add_filter( 'post_class', array( 'kind_taxonomy', 'post_class') );
		add_filter( 'body_class', array( 'kind_taxonomy', 'body_class') );


		// Trigger Webmention on Change in Post Status
		add_filter('transition_post_status', 'transition', 10, 3);


		// Return Kind Meta as part of the JSON Rest API
		add_filter( 'json_prepare_post' , array( 'kind_taxonomy' , 'json_rest_add_kindmeta' ) , 10 , 3);

		// Add the Correct Archive Title to Kind Archives
		add_filter('get_the_archive_title', array( 'kind_taxonomy' , 'kind_archive_title' ) , 10 , 3);
		// Remove the built-in meta box selector in place of a custom one
		add_action( 'admin_menu', array( 'kind_taxonomy', 'remove_meta_box') );
		add_action( 'add_meta_boxes', array( 'kind_taxonomy', 'add_meta_box') );


	}

	public static function activate_kinds() {
		if ( function_exists('iwt_plugin_notice') ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
    	wp_die( 'You have Indieweb Taxonomy activated. Post Kinds replaces this plugin. Please disable Taxonomy before activating' );
  	}
		if (!get_option('iwt_options') ) {
			$option = array (
				'embeds' => '1',
				'cacher' => '0',
				'disableformats' => '0',
				'protection' => '0',
				'intermediate' => '0',
        'watchlisten' => '0'
			);
			update_option('iwt_options', $option);
  	}
  	self::register();
  	self::kind_defaultterms();
	}

	public static function register() {
		$labels = array( 
			'name' => _x( 'Kinds', 'Post kind' ),
			'singular_name' => _x( 'Kind', 'Post kind' ),
			'search_items' => _x( 'Search Kinds', 'Post kind' ),
			'popular_items' => _x( 'Popular Kinds', 'Post kind' ),
			'all_items' => _x( 'All Kinds', 'Post kind' ),
			'parent_item' => _x( 'Parent Kind', 'Post kind' ),
			'parent_item_colon' => _x( 'Parent Kind:', 'Post kind' ),
			'edit_item' => _x( 'Edit Kind', 'Post kind' ),
			'update_item' => _x( 'Update Kind', 'Post kind' ),
			'add_new_item' => _x( 'Add New Kind', 'Post kind' ),
			'new_item_name' => _x( 'New Kind', 'Post kind' ),
			'separate_items_with_commas' => _x( 'Separate kinds with commas', 'Post kind' ),
			'add_or_remove_items' => _x( 'Add or remove kinds', 'Post kind' ),
			'choose_from_most_used' => _x( 'Choose from the most used kinds', 'Post kind' ),
			'menu_name' => _x( 'Kinds', 'Post kind' ),
		);

		$args = array( 
			'labels' => $labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => false,
			'show_tagcloud' => true,
			'show_admin_column' => true,
			'hierarchical' => true,
			'rewrite' => true,
			'query_var' => true
		);
		register_taxonomy( 'kind', array('post'), $args );
	}

	// Sets up some starter terms...unless terms already exist 
	// or any of the existing terms are defined
	public static function kind_defaultterms () {
		if (!term_exists('like', 'kind')) {
			wp_insert_term('like', 'kind', 
				array(
					'description'=> 'Like',
					'slug' => 'like',
			) );
		}  
		if (!term_exists('favorite', 'kind')) {
			wp_insert_term('favorite', 'kind',
				array(
					'description'=> 'Favorite',
					'slug' => 'favorite',
				) );
		} 
		if (!term_exists('reply', 'kind')) {
			wp_insert_term('reply', 'kind',
				array(
					'description'=> 'Reply',
					'slug' => 'reply',
			) );
		}
		if (!term_exists('rsvp', 'kind')) {
			wp_insert_term('rsvp', 'kind',
				array(
					'description'=> 'RSVP for Event',
					'slug' => 'rsvp',
			) );
		}
		if (!term_exists('repost', 'kind')) {
			wp_insert_term('repost', 'kind',
			array(
				'description'=> 'Repost',
				'slug' => 'repost',
			) );
    }
		if (!term_exists('bookmark', 'kind')) {
			wp_insert_term('bookmark', 'kind',
				array(
					'description'=> 'Sharing a Link',
					'slug' => 'bookmark',
      ) );
		}
		if (!term_exists('tag', 'kind')) {
			wp_insert_term('Tag', 'kind',
				array(
					'description'=> 'Tagging a Post',
					'slug' => 'tag',
			) );
		}
		if (!term_exists('article', 'kind')) {
			wp_insert_term('article', 'kind',
				array(
					'description'=> 'Longer Content',
					'slug' => 'article',
			) );
		}
		if (!term_exists('note', 'kind')) {
			wp_insert_term('note', 'kind',
				array(
					'description'=> 'Short Content',
					'slug' => 'note',
				) );
		}
		if (!term_exists('photo', 'kind')) {
			wp_insert_term('photo', 'kind',
				array(
					'description'=> 'Image Post',
					'slug' => 'photo',
			) );
		}
		if (!term_exists('listen', 'kind')) {
			wp_insert_term('listen', 'kind',
				array(
					'description'=> 'Listen',
					'slug' => 'listen',
				) );
		}
		if (!term_exists('watch', 'kind')) {
			wp_insert_term('watch', 'kind',
				array(
					'description'=> 'Watch',
					'slug' => 'watch',
			) );
		}
		if (!term_exists('checkin', 'kind')) {
			wp_insert_term('checkin', 'kind',
				array(
					'description'=> 'Checkin',
					'slug' => 'checkin',
				) );
		}
		if (!term_exists('play', 'kind')) {
			wp_insert_term('play', 'kind',
				array(
					'description'=> 'Game Play',
					'slug' => 'play',
				) );
		}
		if (!term_exists('wish', 'kind')) {
			wp_insert_term('wish', 'kind',
				array(
					'description'=> 'Wish or Desire',
					'slug' => 'wish',
			) );
		}
		if (!term_exists('weather', 'kind')) {
			wp_insert_term('weather', 'kind',
				array(
					'description'=> 'Weather',
					'slug' => 'weather',
			) );
		}
		if (!term_exists('exercise', 'kind')) {
			wp_insert_term('exercise', 'kind',
				array(
					'description'=> 'Exercise',
					'slug' => 'exercise',
				) );
		}
		if (!term_exists('travel', 'kind')) {
			wp_insert_term('travel', 'kind',
				array(
					'description'=> 'Trip or Travel',
					'slug' => 'travel',
			) );
		}
		// Allows for extensions to add terms to the plugin
		do_action('kind_add_term');
	}
	public static function kind_permalink($permalink, $post_id, $leavename) {
		if (strpos($permalink, '%kind%') === FALSE) return $permalink;

		// Get post
		$post = get_post($post_id);
		if (!$post) return $permalink;

		// Get taxonomy terms
		$terms = wp_get_object_terms($post->ID, 'kind');   
		if (!is_wp_error($terms) && !empty($terms) && is_object($terms[0])) $taxonomy_slug = $terms[0]->slug;
 		else $taxonomy_slug = 'note';
    return str_replace('%kind%', $taxonomy_slug, $permalink);
	}   
	public static function kind_archive_title($title) {
		$strings = self::get_strings_plural();
		if ( is_tax( 'kind' ) ) {
			foreach ($strings as $key => $string) { 
				if ( is_tax( 'kind', $key) ) { 
					$title = $string;
					return $title;
				}
			}
	 }
   return $title;
  }

	public static function remove_meta_box() {
		remove_meta_box('kinddiv', 'post', 'normal');
	}

	public static function add_meta_box() {
		if (MULTIKIND=='0') {
			add_meta_box( 'kind_select', 'Post Kinds', array('kind_taxonomy', 'select_metabox'),'post' ,'side','core');
		}
		else {
			// Add Multi-Select Box for MultiKind support
			add_meta_box( 'kind_select', 'Post Kinds', array('kind_taxonomy', 'multiselect_metabox'),'post' ,'side','core');
		}
	}

	public static function select_metabox( $post ) {
		$strings=self::get_strings();
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
			if (in_array($slug, $include) ) {
				echo "<li id='$id' class='kind-$slug'><label class='selectit'>";
				echo "<input type='radio' id='in-$id' name='tax_input[kind]'".checked($current,$term->term_id,false)."value='$term->term_id' />$strings[$slug]<br />";
				echo "</label></li>";
			}
		}
		echo '</ul></div>';
	}

	/**
	 * Returns an array of post kind slugs to their translated and pretty display versions
	 *
	 * @return array The array of translated post kind names.
	 */
	public static function get_strings() {
		$strings = array(
			'article' => _x( 'Article', 'Post kind' ),
			'note'    => _x( 'Note',    'Post kind' ),
			'reply'     => _x( 'Reply',     'Post kind' ),
			'repost'  => _x( 'Repost',  'Post kind' ),
			'like'     => _x( 'Like',     'Post kind' ),
			'favorite'    => _x( 'Favorite',    'Post kind' ),
			'bookmark'    => _x( 'Bookmark',    'Post kind' ),
			'photo'   => _x( 'Photo',   'Post kind' ),
			'tag'    => _x( 'Tag',    'Post kind' ),
			'rsvp'    => _x( 'RSVP',    'Post kind' ),
			'listen'   => _x( 'Listen', 'Post kind' ),
			'watch'   => _x( 'Watch', 'Post kind' ),
			'checkin'   => _x( 'Checkin', 'Post kind' ),
			'wish'   => _x( 'Wish', 'Post kind' ),
			'play'   => _x( 'Play', 'Post kind' ),
			'weather'   => _x( 'Weather', 'Post kind' ),
			'exercise'   => _x( 'Exercise', 'Post kind' ),
			'travel'   => _x( 'Travel', 'Post kind' )
		);
		return apply_filters( 'kind_strings', $strings );
 }

	/**
   * Returns an array of post kind slugs to their pluralized translated and pretty display versions
	 *
	 * @return array The array of translated post kind names.
	 */
	public static function get_strings_plural() {
		$strings = array(
			'article' => _x( 'Articles', 'Post kind' ),
			'note'    => _x( 'Notes',    'Post kind' ),
			'reply'     => _x( 'Replies',     'Post kind' ),
			'repost'  => _x( 'Reposts',  'Post kind' ),
			'like'     => _x( 'Likes',     'Post kind' ),
			'favorite'    => _x( 'Favorites',    'Post kind' ),
			'bookmark'    => _x( 'Bookmarks',    'Post kind' ),
			'photo'   => _x( 'Photos',   'Post kind' ),
			'tag'    => _x( 'Tags',    'Post kind' ),
			'rsvp'    => _x( 'RSVPs',    'Post kind' ),
			'listen'   => _x( 'Listens', 'Post kind' ),
			'watch'   => _x( 'Watches', 'Post kind' ),
			'checkin'   => _x( 'Checkins', 'Post kind' ),
			'wish'   => _x( 'Wishlist', 'Post kind' ),
			'play'   => _x( 'Plays', 'Post kind' ),
			'weather'   => _x( 'Weather', 'Post kind' ),
			'exercise'   => _x( 'Exercises', 'Post kind' ),
			'travel'   => _x( 'Travels', 'Post kind' )
		);
		return apply_filters( 'kind_strings_plural', $strings );
}

	/**
	 * Returns an array of post kind slugs to their translated verbs
	 *
	 * @return array The array of translated post kind verbs.
	 */
	public static function get_verb_strings() {
		$strings = array(
			'article' => _x( ' ', 'Post kind' ),
			'note'    => _x( ' ',    'Post kind' ),
			'reply'     => _x( 'In Reply To',     'Post kind' ),
			'repost'  => _x( 'Reposted',  'Post kind' ),
			'like'     => _x( 'Liked',     'Post kind' ),
			'favorite'    => _x( 'Favorited',    'Post kind' ),
			'bookmark'    => _x( 'Bookmarked',    'Post kind' ),
			'photo'   => _x( ' ',   'Post kind' ),
			'tag'    => _x( 'Tagged',    'Post kind' ),
			'rsvp'    => _x( 'RSVPed',    'Post kind' ),
			'listen'    => _x( 'Listened to ',    'Post kind' ),
			'watch'   => _x( 'Watched', 'Post kind' ),
			'checkin'   => _x( 'Checked In At', 'Post kind' ),
			'wish'   => _x( 'Desires', 'Post kind' ),
			'play'   => _x( 'Played', 'Post kind' ),
			'weather'   => _x( 'Weathered', 'Post kind' ),
			'exercise'   => _x( 'Exercised', 'Post kind' ),
			'travel'   => _x( 'Traveled', 'Post kind' )
		);
		return apply_filters( 'kind_verbs', $strings );
	}

	/**
	 * Uses an array of post kind slugs with the author terminologies
	 *
	 * @return array The appropriate post kind author string.
	 */
	public static function get_author_string($verb) {
		$strings = array(
			'article' => _x( 'by', 'Post kind' ),
		);
		$strings = apply_filters( 'kind_author_string', $strings );
		if (array_key_exists($verb, $strings) ) {
			return $strings[$verb];
		} 
		else {
			return _x('by', 'Post kind');
		} 
	} 

	/**
	 * Returns the publication terminology for the publication
	 *
	 * @return array The post kind publication string.
	 */
	public static function get_publication_string($verb) {
		$strings = array(
 			'article' => _x( 'on', 'Post kind' ),
			'listen' => _x( '-', 'Post kind' ),
			'watch' => _x( '-', 'Post kind' )
		);
		$strings = apply_filters( 'kind_publication_string', $strings );
		if (array_key_exists($verb, $strings) ) {
			return $strings[$verb];
		}
		else {
			return _x('on', 'Post kind');
		}
	} 

	/**
	 * Returns an array of domains with the post type terminologies
	 *
	 * @return array A translated post type string for specific domain or 'a post'
	 */
	public static function get_post_type_string($url) {
		$strings = array(
			'twitter.com' => _x( 'a tweet', 'Post kind' ),
			'vimeo.com' => _x( 'a video', 'Post kind' ),
			'youtube.com'   => _x( 'a video', 'Post kind' )
		);
		$domain = extract_domain_name($url);
		if (array_key_exists($domain, $strings) ) {
			return apply_filters( 'kind_post_type_string', $strings[$domain] );
		}
		else {
			return _x('a post', 'Post kind');
		}
	}

	public static function remove_semantics() {
		if (class_exists('SemanticLinkbacksPlugin') ) {
			remove_filter('comment_text', array('SemanticLinkbacksPlugin', 'comment_text_excerpt'),12);
			add_filter('comment_text', array('kind_taxonomy' , 'comment_text_excerpt') , 12 , 3 );
  	}
	}

	// Replacement for the Semantic Linkbacks Comment Excerpt
	public static function comment_text_excerpt($text, $comment = null, $args = array()) {
  	// only change text for pingbacks/trackbacks/webmentions
    if (!$comment || $comment->comment_type == "" || !get_comment_meta($comment->comment_ID, "semantic_linkbacks_canonical", true)) {
			return $text;
		}
		// check comment type
		$comment_type = get_comment_meta($comment->comment_ID, "semantic_linkbacks_type", true);
		if (!$comment_type || !in_array($comment_type, array_keys(SemanticLinkbacksPlugin::get_comment_type_strings()))) {
			$comment_type = "mention";
		}
		$_kind = get_the_terms( $comment->comment_post_ID, 'kind' );
		if (!empty($_kind)) {   
			$kind = array_shift($_kind);
			$kindstrings = self::get_strings();
			$post_format = $kindstrings[$kind->slug];
		}
		else {
			$post_format = get_post_format($comment->comment_post_ID);
			// replace "standard" with "Article"
			if (!$post_format || $post_format == "standard") {
				$post_format = "Article";
			} else {
			$post_formatstrings = get_post_format_strings();
			// get the "nice" name
			$post_format = $post_formatstrings[$post_format];
			}
   	}
		// generate the verb, for example "mentioned" or "liked"
		$comment_type_excerpts = SemanticLinkbacksPlugin::get_comment_type_excerpts();
		// get URL canonical url...
		$url = get_comment_meta($comment->comment_ID, "semantic_linkbacks_canonical", true);
		// ...or fall back to source
		if (!$url) {
			$url = get_comment_meta($comment->comment_ID, "semantic_linkbacks_source", true);
		}
		// parse host
		$host = parse_url($url, PHP_URL_HOST);
		// strip leading www, if any
		$host = preg_replace("/^www\./", "", $host);
		// generate output
		$text = sprintf($comment_type_excerpts[$comment_type], get_comment_author_link($comment->comment_ID), $post_format, $url, $host);
		return apply_filters("semantic_linkbacks_excerpt", $text);
	}

	public static function publish ( $ID, $post=null) {
		$cites = get_post_meta($ID, 'mf2_cite', true);
		if (empty($cites)) { return; }   
		foreach ($cites as $cite) {
			if (!empty($cite) && isset($cite['url'])) {
				send_webmention(get_permalink($ID), $cite['url']);
      }
		}
	}

	public static function transition($old,$new,$post){
  	self::publish($post->ID,$post);
	} 

	public static function json_rest_add_kindmeta($_post,$post,$context) {
		$response = get_post_meta( $post["ID"], 'mf2_cite');
		if (!empty($response)) { $_post['mf2_cite'] = $response; }
		return $_post;
	}

	public static function kinds_as_type($classes) {
		$kind = get_post_kind_slug();
		switch ($kind) {
			case "note":
				$classes[] = 'h-as-note';
				break;
			case 'article':
				$classes[] = 'h-as-article';
				break;
			case 'photo':
				$classes[] = 'h-as-image';
				break;
			case 'bookmark':
				$classes[] = 'h-as-bookmark';
				break;
		}
		return $classes;
	}

	public static function post_class($classes) {
		// Adds kind classes to post
		if (!is_singular() ) {
			$classes = self::kinds_as_type($classes);
		} 
		$classes[] = 'kind-' . get_post_kind_slug();
		return $classes;
	}

	public static function body_class($classes) {
		// Adds kind classes to body
		if (is_singular() ) {
			$classes = self::kinds_as_type($classes);
		}
		return $classes;
	}


} // End Class kind_taxonomy

?>
