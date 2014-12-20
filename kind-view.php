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


function get_response_display() {
	$resp = "";
	$c = "";
	$response = get_kind_response(get_the_ID()); 
	$kind = get_post_kind(get_the_ID());
	$verbstrings = get_post_kind_verb_strings();
        $options = get_option('iwt_options');
	// If there is no kind or it isn't a response kind, return nothing.
	if ( (!$kind)||(!response_kind($kind)) ) {
            return apply_filters( 'response-display', ""); 
	   }
	// If there is nothing in the context boxes, return nothing.
	if ( empty($response['url']) && empty($response['content']) && empty($response['title']) ) {
            return apply_filters( 'response-display', "");
           }	
	 if (! empty($response['url'])  )
           {
                 $resp .= '<a class="' . implode(' ',get_kind_context_class ( 'h-cite', 'u' )) . '" href="' . $response['url'] . '"></a><strong>' . $verbstrings[$kind] . '</strong>';
		 if (!empty ($response['title']) )
			{
		 		$resp .= ' - ' . $response['title'];
			}
		 $resp .= ' - ' . '<a href="' . $response['url'] . '">' . extract_domain_name($response['url']) . '</a>';

		// A URL means a response to an external source
		 if (!empty($response['content']) )
                      {
                         $resp .= '<blockquote>' . $response['content'] . '</blockquote>';
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
         // use the content/title to generate a response and mark up p-
	 elseif (! empty ($response['content']) )
           {
		$resp .= '<cite class="h-cite">';
		
		$resp .= '<blockquote class="p-content">' . $response ['content'] . '</blockquote>';
		if (! empty ($response['title']) )
                    {
			$resp .= ' - ' . '<span class="p-name">' . $response ['title'] . '</span>';
		    }
		$resp .= '</cite>';
	   }
	 // This means that there is no URL or content, just a title. Which implies something like a tag or a like of a concept	
	 else 
	   {
           }
        // Wrap the entire display in the class response
           $c .= '<div class="response">' . $resp . '</div>';
	// Return the resulting display.
	return apply_filters( 'response-display', $c);

}

function get_response_display2() {
	$resp = "";
	$c = "";
	$response = get_post_meta(get_the_ID(), 'response', true);
	if (!get_the_terms(get_the_ID(), 'kind')) { 
		return apply_filters( 'response-display', ""); }
 	if ( empty ($response['title']) )
	    // If there is no user entered title, use the post title field instead
	   {
		$response['title'] = get_the_title();
	   }

	// Don't generate the response if all the fields are empty as that means nothing is being responded to
	if (! empty($response['url'])  )
	    {
		// Means a response to an external source
		if ( !empty($response['content']) )
		    {
		   	// Format based on having a citation
			$resp .= '<div class="' . implode(' ',get_kind_context_class ( 'h-cite', 'p' )) . '">';
			$resp .= '<strong>' . implode(' and ', get_kind_verbs()) . '</strong>';
			$resp .= '<blockquote class="p-content">'.$response['content'] . '</blockquote>';
			$resp .= ' - ' . '<a class="u-url" href="' . $response['url'] . '">' . $response['title'] . '</a>';
			$resp .= '</div>';
			$c = '<div class="response">' . $resp . '</div>';
		    }
		else {
			$resp .= '<strong>' . implode(' and ', get_kind_verbs()) . '</strong>';
		    // An empty citation means use a reply-context or an embed
		    	 $options = get_option('iwt_options');
   		    	 if($options['embeds'] == 1){
				$embed_code = new_embed_get($response['url']);
				}
			 else {
				$embed_code = false;
			      }
			if ($embed_code == false)
				{
				   $resp .= '<a class="' . implode(' ',get_kind_context_class ( '', 'u' )) . '" href="' . $response['url'] . '">' . $response['title'] . '</a>';
				}
			else{
				$resp .= '<br />' . '<div class="embeds">' . $embed_code . '</div>';
				$resp .= '<br /><a class="' . implode(' ',get_kind_context_class ( 'h-cite empty', 'u' )) . '" href="' . $response['url'] . '"></a>';
			   }
		  	$c = '<div class="response">' . $resp . '</div>';
		     }
	   }
	elseif (! empty ($response['quote']) )
	   {
		// No Response URL means use the quote/title to generate a response and mark up p-
		$resp .= '<strong>' . implode(' and ', get_kind_verbs()) . '</strong>';
		$resp .= '<div class="' . implode(' ',get_kind_context_class ( 'h-cite', 'p' )) . '"><blockquote>';
		$resp .= esc_attr($response['quote']);
		$resp .= '</blockquote> - <em>' . $response['title'] . '</em></div>';
		$c = '<div class="response">' . $resp . '</div>';
	   }
	return apply_filters( 'response-display', $c);
}

function response_display() {
	return get_response_display();
}

function content_response_top ($content ) {
    $c = "";
    $c .= get_response_display();
    $c .= $content;
    return $c;
}

function content_response_bottom ($content ) {
    $c = "";
    $c .= $content;
    $c .= get_response_display();
    return $c;
}

?>
