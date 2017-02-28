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
				jQuery("#cite_published_date").val( published.format('YYYY-MM-DD') ) ;
				jQuery("#cite_published_time").val(published.format('HH:mm:ss') ) ;
				jQuery("#cite_published_offset").val(published.format('Z') );
 	 	    	}
   		   	if ( 'updated' in response ) {
				var updated = moment.parseZone( response['updated'] );
        			jQuery("#cite_updated_date").val( updated.format('YYYY-MM-DD') ) ;
        			jQuery("#cite_updated_time").val(updated.format('HH:mm:ss') ) ;
        			jQuery("#cite_updated_offset").val(updated.format('Z') );  
      			}
			if ( 'summary' in response ) {
        			jQuery("#cite_summary").val(response['summary']) ;
      			}
  	    		if ( 'featured' in response ) {
  	 	     		jQuery("#cite_featured").val(response['featured']) ;
      			}
      			if ( ( 'author' in response ) && ( typeof response['author'] != 'string' ) ) {
	      			if ( 'name' in response['author'] ) {
  	      				jQuery("#cite_author_name").val(response['author']['name']) ;
    		  		}
        			if ( 'photo' in response['author'] ) {
          			jQuery("#cite_author_photo").val(response['author']['photo']) ;
        			}
        			if ( 'url' in response['author'] ) {
          			jQuery("#cite_author_url").val(response['author']['url']) ;
        			}
			}
			if ( 'category' in response ) {
				jQuery("#cite_tags").val( response['category'].join(";") );
			}
		alert( rest_object.link_preview_success_message );
		console.log( response );
		},
	  fail: function( response ) {
		  	console.log( response );
			alert(response.message);
		},
	  error: function() {
		  alert( 'Error' );
		}
	});
})
