jQuery( document ).on( 'click', '.kind-retrieve-button', function($) {
	jQuery.ajax({ 
			type: 'GET',
	        	// Here we supply the endpoint url, as opposed to the action in the data object with the admin-ajax method
			url: rest_object.api_url + 'parse/', 
			beforeSend: function ( xhr ) {
			       	// Here we set a header 'X-WP-Nonce' with the nonce as opposed to the nonce in the data object with admin-ajax
				xhr.setRequestHeader( 'X-WP-Nonce', rest_object.api_nonce );
			},
			data: { 
				kindurl: jQuery("#cite_url").val()
			},
		success : function( response ) {
			if ( 'name' in response ) {
				jQuery("#cite_name").val(response['name']);
				if ( jQuery('#title').val() === '' ) {
					jQuery("#title").val(response['name']);
				}
			}
 		     	if ( 'publication' in response ) {
				jQuery("#cite_publication").val(response['publication']) ;
   	  	 	}
			if ( 'published' in response ) {
				var published = moment.parseZone( response['published'] );
				jQuery("#start_date").val( published.format('YYYY-MM-DD') ) ;
				jQuery("#start_time").val(published.format('HH:mm:ss') ) ;
				jQuery("#start_offset").val(published.format('Z') );
 	 	    	}
   		   	if ( 'updated' in response ) {
				var updated = moment.parseZone( response['updated'] );
        			jQuery("#end_date").val( updated.format('YYYY-MM-DD') ) ;
        			jQuery("#end_time").val(updated.format('HH:mm:ss') ) ;
        			jQuery("#end_offset").val(updated.format('Z') );  
      			}
			if ( 'summary' in response ) {
        			jQuery("#cite_summary").val(response['summary']) ;
      		}
      		if ( 'featured' in response ) {
        		jQuery("#cite_featured").val(response['featured']) ;
      		}
      		if ( 'duration' in response ) {
        		jQuery("#duration").val(response['duration']) ;
      		}

      		if ( 'author' in response ) {
	      		if ( 'name' in response['author'] ) {
  	      		jQuery("#author_name").val(response['author']['name']) ;
    	  		}
        		if ( 'photo' in response['author'] ) {
          		jQuery("#author_photo").val(response['author']['photo']) ;
        		}
        		if ( 'url' in response['author'] ) {
          		jQuery("#author_url").val(response['author']['url']) ;
        		}
		}
			console.log(response);
		},
	  fail: function(request, status, error){
		  	console.log( response );
			alert(request.message);
		}
	});
})
