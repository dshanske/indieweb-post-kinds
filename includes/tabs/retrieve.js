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
			if ( 'code' in response['data'][0] ) {
					alert(response['data'][0]['message']);
			}
			if ( 'name' in response['data'] ) {
				jQuery("#cite_name").val(response['data']['name']) ;
			}
      if ( 'publication' in response['data'] ) {
        jQuery("#cite_publication").val(response['data']['publication']) ;
      }

      if ( 'published' in response['data'] ) {
				var published = moment.parseZone( response['data']['published'] );
        jQuery("#start_date").val( published.format('YYYY-MM-DD') ) ;
				jQuery("#start_time").val(published.format('HH:mm:ss') ) ;
				jQuery("#start_offset").val(published.format('Z') );
      }
      if ( 'updated' in response['data'] ) {
        var updated = moment.parseZone( response['data']['updated'] );
        jQuery("#end_date").val( updated.format('YYYY-MM-DD') ) ;
        jQuery("#end_time").val(updated.format('HH:mm:ss') ) ;
        jQuery("#end_offset").val(updated.format('Z') );  
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
        if ( 'url' in response['data']['author'] ) {
          jQuery("#author_url").val(response['data']['author']['url']) ;
        }

			}
			console.log(response);
		},
	  error: function(request, status, error){
			alert(request.responseText);
		}
	});
})
