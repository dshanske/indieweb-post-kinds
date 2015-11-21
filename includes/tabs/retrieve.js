jQuery( document ).on( 'click', '.kind-retrieve-button', function($) {
	jQuery.post(ajaxurl, { 
			action: 'kind_urlfetch',
			kind_url: jQuery("#kind-url").val()
		},
		function( response ) {
			if ( 'name' in response['data'] ) {
				jQuery("#mf2_name").val(response['data']['name']) ;
			}
      if ( 'publication' in response['data'] ) {
        jQuery("#publication").val(response['data']['publication']) ;
      }

      if ( 'published' in response['data'] ) {
        jQuery("#published").val(response['data']['published']) ;
      }
      if ( 'updated' in response['data'] ) {
        jQuery("#updated").val(response['data']['updated']) ;
      }

      if ( 'summary' in response['data'] ) {
        jQuery("#cite_content").val(response['data']['summary']) ;
      }
      if ( 'featured' in response['data'] ) {
        jQuery("#featured").val(response['data']['featured']) ;
      }
      if ( 'duration' in response['data'] ) {
        jQuery("#duration").val(response['data']['duration']) ;
      }

      if ( 'author' in response['data'] ) {
	      if ( 'name' in response['data']['author'] ) {
  	      jQuery("#author_name").val(response['data']['author']['name']) ;
    	  }
        if ( 'photo' in response['data']['author'] ) {
          jQuery("#author_photo").val(response['data']['author']['photo']) ;
        }
			}
			console.log(response);
		}
	);
})
