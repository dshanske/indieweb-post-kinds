jQuery( document ).on( 'click', '.kind-retrieve-button', function($) {
	jQuery.ajax({ 
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'kind_urlfetch',
				kind_url: jQuery("#cite_url").val()
			},
		success : function( response ) {
      if ( typeof response == 'undefined' )
        alert ('Undefined Response');
			if ( 'name' in response['data'] ) {
				jQuery("#cite_name").val(response['data']['name']) ;
			}
      if ( 'publication' in response['data'] ) {
        jQuery("#cite_publication").val(response['data']['publication']) ;
      }

      if ( 'published' in response['data'] ) {
        jQuery("#cite_published").val(response['data']['published']) ;
      }
      if ( 'updated' in response['data'] ) {
        jQuery("#cite_updated").val(response['data']['updated']) ;
      }

      if ( 'summary' in response['data'] ) {
        jQuery("#cite_summary").val(response['data']['summary']) ;
      }
      if ( 'featured' in response['data'] ) {
        jQuery("#cite_featured").val(response['data']['featured']) ;
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
		},
	  error: function(request, status, error){
			alert(request.responseText);
		}
	});
})
