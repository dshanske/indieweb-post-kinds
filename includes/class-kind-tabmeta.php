<?php
/**
 * Post Kind Post Tabbed MetaBox Class
 *
 * Sets Up Tabbed Metabox in the Posting UI for Kind data.
 */
add_action( 'init' , array( 'Kind_Tabmeta', 'init' ) );
add_action( 'wp_ajax_kind_urlfetch', array( 'Kind_Tabmeta', 'urlfetch' ) );

class Kind_Tabmeta {
	public static function init() {
		// Add meta box to new post/post pages only
		add_action( 'load-post.php', array( 'Kind_Tabmeta', 'kindbox_setup' ) );
		add_action( 'load-post-new.php', array( 'Kind_Tabmeta', 'kindbox_setup' ) );
		add_action( 'save_post', array( 'Kind_Tabmeta', 'save_post' ), 8, 2 );
		add_action( 'transition_post_status', array( 'Kind_Tabmeta', 'transition_post_status' ) ,5,3 );		
	}

	/* Meta box setup function. */
	public static function kindbox_setup() {
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( 'Kind_Tabmeta', 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( 'Kind_Tabmeta', 'enqueue_admin_scripts' ) );

	}

	public static function enqueue_admin_scripts() {
    if ( 'post' === get_current_screen()->id ) {
        wp_enqueue_script(
            'kindmeta-tabs',
            plugins_url( 'indieweb-post-kinds/includes/tabs/tabs.js' ),
            array( 'jquery' ),
            POST_KINDS_VERSION
        );

        wp_enqueue_script(
            'kindmeta-response',
            plugins_url( 'indieweb-post-kinds/includes/tabs/retrieve.js' ),
            array( 'jquery' ),
            POST_KINDS_VERSION
        );
 
    }
	}


	/* Create one or more meta boxes to be displayed on the post editor screen. */
	public static function add_meta_boxes() {
		add_meta_box(
			'tabbox-meta',      // Unique ID
			esc_html__( 'Post Properties', 'Post kind' ),    // Title
			array( 'Kind_Tabmeta', 'display_metabox' ),   // Callback function
			'post',         // Admin page (or post type)
			'normal',         // Context
			'default'         // Priority
		);
	}

	public static function display_metabox( $object, $box ) {
		wp_nonce_field( 'tabkind_metabox', 'tabkind_metabox_nonce' );
		$meta = new kind_meta( $object->ID );
		$kindmeta = $meta->get_meta();
		$author = $meta->get_author();
		include_once( 'tabs/tab-navigation.php' );
	}

	public static function change_title( $data, $postarr ) {

		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! empty( $data['post_title'] ) ) {
			return $data;
		}
		$kind_strings = Kind_Taxonomy::get_strings();
		$kind = get_term_by( taxonomy_id, $_POST['tax_input']['kind'], 'kind' );
		$title = $kind_strings[ $kind->slug ];
		if ( ! empty( $_POST['cite_name'] ) ) {
				$title .= ' - ' . $_POST['cite_name'];
		}
		$data['post_title'] = $title;
		$data['post_name'] = sanitize_title( $data['post_title'] );
		return $data;
	}

	/* Save the meta box's post metadata. */
	public static function save_post( $post_id, $post ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['tabkind_metabox_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['tabkind_metabox_nonce'], 'tabkind_metabox' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		$meta = new Kind_Meta( $post );
		$meta->build_meta( $cite );
		$meta->save_meta( $post );
	}

	public static function transition_post_status( $new, $old, $post ) {
		if ( $new == 'publish' && $old != 'publish' ) {
			self::save_post( $post->ID, $post );
		}
	}

	/**
	 * Retrieves the body of a URL for parsing.
	 *
	 * @param string $url A valid URL.
	 */
	private static function fetch($url) {
		global $wp_version;
		if ( ! isset( $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.' ) );
		}
		$response = wp_safe_remote_get( $url, array(
			'timeout' => 30,
			// Use an explicit user-agent for Post Kinds
			'user-agent' => 'Post Kinds (WordPress/' . $wp_version . '); ' . get_bloginfo( 'url' ),
		) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = wp_remote_retrieve_body( $response );
		return $body;
	}
	/**
	 * Parses marked up HTML.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function parse ($content, $url) {
		$ogpdata = self::ogpparse( $content );
		$mf2data = self::mf2parse( $content, $url);
		$data = array_merge( $ogpdata, $mf2data );
		$data =  array_filter( $data );
		/**
		 * Parse additionally by plugin.
		 *
		 * @param array $data An array of properties.
		 * @param string $content The content of the retrieved page.
		 * @param string $url Source URL
		 */
		return apply_filters ( 'kind_parse_data', $data, $content, $url );
	}

	public static function urlfetch() {
		global $wpdb;
		error_log('I got here' . $_POST['kind_url']);
//		$content = self::fetch($_POST['kind_url']);
//	  wp_send_json( self::parse($content, $_POST['kind_url']) );
			wp_send_json_success();	
		wp_die();
	}

	/* Parses marked up HTML using MF2.
   *
   * @param string $content HTML marked up content.
   */
  private static function mf2parse($content, $url) {
		$data = array();
		$parsed = Mf2\parse($content, $url);
		if(mf2_cleaner::isMicroformatCollection($parsed)) {
      $entries = mf2_cleaner::findMicroformatsByType($parsed, 'h-entry');
			if($entries) {
				$entry = $entries[0];
        if(mf2_cleaner::isMicroformat($entry)) {
        	foreach($entry['properties'] as $key => $value) {
           	$data[$key] = mf2_cleaner::getPlaintext($entry, $key);
          }
					$data['published'] = mf2_cleaner::getPublished($entry);
					$data['updated'] = mf2_cleaner::getUpdated($entry);
				  $data['name'] = mf2_cleaner::getPlaintext($entry, 'name');
  //        $data['content'] = mf2_cleaner::getHtml($entry, 'content');
	//				$data['summary'] = mf2_cleaner::getHtml($entry, 'summary');
						// Temporary measure till next version
					  $data['content'] = mf2_cleaner::getPlaintext($entry, 'summary');
          $data['name'] = trim(preg_replace('/https?:\/\/([^ ]+|$)/', '', $data['name']));
					$author = mf2_cleaner::getAuthor($entry);
         	if ($author) {
							$data['author']=array();
							foreach($author['properties'] as $key => $value) {
								$data['author'][$key] = mf2_cleaner::getPlaintext($author, $key);
							}
							$data['author']=array_filter($data['author']);
          }
				}
			}		
		}
		return array_filter( $data );
	}
	/**
	 * Parses marked up HTML using OGP.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function ogpparse($content) {
		$meta = \ogp\Parser::parse( $content );
		$data = array();
		$data['name'] = ifset( $meta['og:title'] ) ?: ifset( $meta['twitter:title'] ) ?: ifset( $meta['og:music:song'] );
//    $data['summary'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] );
		$data['content'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] );
		$data['site'] = ifset( $meta['og:site'] ) ?: ifset( $meta['twitter:site'] );
		$data['featured'] = ifset( $meta['og:image'] ) ?: ifset( $meta['twitter:image'] );
		$data['publication'] = ifset( $meta['og:site_name'] ) ?: ifset( $meta['og:music:album'] );
		$data['published'] = ifset( $meta['og:article:published_time'] ) ?: ifset( $meta['og:music:release_date'] ) ?: ifset( $meta['og:video:release_date'] );
		$metatags = ifset( $meta['article:tag'] ) ?: ifset( $meta['og:video:tag'] );
		$tags = array();
		if ( is_array( $metatags ) ) {
			foreach ( $metatags as $tag ) {
				$tags[] = str_replace( ',', ' -', $tag );
			}
			$tags = array_filter( $tags );
		}
		$data['tags'] = ifset($data['tags']) ?: implode( ',' ,$tags );
		// Extended Parameters
		$data['audio'] = ifset( $meta['og:audio'] );
		$data['video'] = ifset( $meta['og:video'] );
		$data['duration'] = ifset( $meta['music:duration'] ) ?: ifset( $meta['video:duration'] );
		$data['type'] = ifset( $meta['og:type'] );
		return array_filter( $data );
	}
}
?>
