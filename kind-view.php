<?php

// Functions Related to Display


$options = get_option('iwt_options');
   if($options['the_content'] == 1){
		add_filter( 'the_content', 'content_response_top', 20 );
	   }


// Extracts the Domain Name for a URL for presentation purposes
function extract_domain_name($url) {
   $host = parse_url($url, PHP_URL_HOST);
   $host = preg_replace("/^www\./", "", $host);
   return $host;
  }	


function get_kind_response_display() {
	$meta = get_post_meta(get_the_ID(), '_resp_full', true);
	$options = get_option('iwt_options');
	if ( ($options['cacher']!=1) && (!empty($meta)) )
	     {
        	return apply_filters( 'kind-response-display', $meta);
	     }
	$resp = "";
	$c = "";
	$response = get_kind_response(get_the_ID()); 
	$kind = get_post_kind_slug();
	$verbstrings = get_post_kind_verb_strings();
	// If there is no kind or it isn't a response kind, return nothing.
	if ( (!$kind)||(!response_kind($kind)) ) {
            return apply_filters( 'kind-response-display', ""); 
	   }
	// If there is nothing in the context boxes, return nothing.
	if ( empty($response['url']) && empty($response['content']) && empty($response['title']) ) {
            return apply_filters( 'kind-response-display', "");
           }	
	 if (! empty($response['url'])  )
           {
                 $resp .= '<a class="u-url" href="' . $response['url'] . '"></a><strong>' . $verbstrings[$kind] . '</strong>';
		 if (!empty ($response['title']) )
			{
		 		$resp .= ' ' . '<a href="' . $response['url'] . '">' . $response['title'] . '</a>';
			} 
		 else
			{
		 		$resp .= ' ' . '<a href="' . $response['url'] . '">' . get_the_title() . '</a>';
			} 
		
		 if (!empty ($response['author']) )
		 	{
				$resp .= '<span class="p-author h-card"> ' . __("by", "Post kinds") . ' ';
				if (! empty($response['icon']) )
				   {
	   				$resp .= '<img class="u-photo" src="' . $response['icon'] . '" title="' . $response['author'] . '" />';
	   			   }
				$resp .= $response['author'] . '</span>';
		      }
		$resp .= ' (<em>' . extract_domain_name($response['url']) . '</em>)';
		// A URL means a response to an external source
		 if (!empty($response['content']) )
                      {
                         $resp .= '<blockquote class="p-content">' . $response['content'] . '</blockquote>';
		      }
		 else {
			// If there is nothing in the content box, check for embeds
			// If Rich Embeds are on display embed code as applicable
			if($options['embeds'] == 1){
                            $embed_code = new_embed_get($response['url']);
                              }
                         else {
                                $embed_code = false;
                              }
			 // If the embed_code is false, either because it has been disabled or there is no rich embed for the site
			 // Generate the display
			 if ($embed_code == false)
                                {
				}
			// Generate the formatting for the embed version
			else {
				$resp .= '<div class="embeds">' . $embed_code . '</div>';
				

			     }
		      }

	    }
	 // If there is no URL but there is content, that means it is responding to something else
         // use the content/title to generate a response
	 elseif (! empty ($response['content']) )
           {	
		$resp .= '<blockquote class="p-content">' . $response ['content'] . '</blockquote>';
		if (! empty ($response['title']) )
                    {
			$resp .= ' - ' . '<span class="p-name">' . $response ['title'] . '</span>';
		    }
	   }
	 // This means that there is no URL or content, just a title. Which implies something like a tag or a like of a concept	
	 else 
	   {
           }

        // Wrap the entire display in the class response
           $c .= '<div class="' .  implode(' ',get_kind_context_class ( 'h-cite response', 'p' )) . '">' . $resp . '</div>';
	update_post_meta( get_the_ID(), '_resp_full', $c); 
	// Return the resulting display.
	return apply_filters( 'kind-response-display', $c);

}

function invalidate_response($ID, $post)
   {
	delete_post_meta( get_the_ID(), '_resp_full' );
   }

add_action( 'publish_post', 'invalidate_response', 10, 2 );


function kind_response_display() {
	echo get_kind_response_display();
}

function content_response_top ($content ) {
    $c = "";
    $c .= get_kind_response_display();
    $c .= $content;
    return $c;
}

function content_response_bottom ($content ) {
    $c = "";
    $c .= $content;
    $c .= get_kind_response_display();
    return $c;
}

?>
