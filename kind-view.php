<?php

// Functions Related to Display


$options = get_option('iwt_options');
   if($options['the_content'] == 1){
		add_filter( 'the_content', 'content_response_top', 20 );
	   }

function get_response_display() {
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
			$resp .= '<div class="' . implode(' ',get_kind_class ( 'h-cite', 'p' )) . '">';
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
				   $resp .= '<a class="' . implode(' ',get_kind_class ( '', 'u' )) . '" href="' . $response['url'] . '">' . $response['title'] . '</a>';
				}
			else{
				$resp .= '<br />' . '<div class="embeds">' . $embed_code . '</div>';
				$resp .= '<br /><a class="' . implode(' ',get_kind_class ( 'h-cite empty', 'u' )) . '" href="' . $response['url'] . '"></a>';
			   }
		  	$c = '<div class="response">' . $resp . '</div>';
		     }
	   }
	elseif (! empty ($response['quote']) )
	   {
		// No Response URL means use the quote/title to generate a response and mark up p-
		$resp .= '<strong>' . implode(' and ', get_kind_verbs()) . '</strong>';
		$resp .= '<div class="' . implode(' ',get_kind_class ( 'h-cite', 'p' )) . '"><blockquote>';
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
