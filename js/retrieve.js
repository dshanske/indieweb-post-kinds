jQuery( document ).ready( function( $ ) {

function clearPostProperties() {
	var fieldIds = [
		'url'
	];
		if ( ! confirm( 'Are you sure you want to clear post propertie?' ) ) {
			return;
		}
		$.each( fieldIds, function( count, val ) {
			document.getElementById( val ).value = '';
		});
}


function getLinkPreview() {
	jQuery.ajax({
		type: 'GET',

		// Here we supply the endpoint url, as opposed to the action in the data object with the admin-ajax method
		url: PKAPI.api_url + 'parse/',
		beforeSend: function( xhr ) {

		// Here we set a header 'X-WP-Nonce' with the nonce as opposed to the nonce in the data object with admin-ajax
		xhr.setRequestHeader( 'X-WP-Nonce', PKAPI.api_nonce );
		},
		data: {
			kindurl: jQuery( '#cite_url' ).val()
		},
		success: function( response ) {
			var published;
			var updated;
			if ( 'undefined' === typeof response ) {
				alert( 'Error: Unable to Retrieve' );
				return;
			}
			if ( 'message' in response ) {
				alert( response.message );
				return;
			}
			if ( 'name' in response ) {
				jQuery( '#cite_name' ).val( response.name );
				if ( '' === jQuery( '#title' ).val() ) {
					jQuery( '#title' ).val( response.name );
				}
			}
			if ( 'publication' in response ) {
				jQuery( '#cite_publication' ).val( response.publication ) ;
			}
			if ( 'published' in response ) {
				published = moment.parseZone( response.published );
				jQuery( '#cite_published_date' ).val( published.format( 'YYYY-MM-DD' ) ) ;
				jQuery( '#cite_published_time' ).val( published.format( 'HH:mm:ss' ) ) ;
				jQuery( '#cite_published_offset' ).val( published.format( 'Z' ) );
			}
			if ( 'updated' in response ) {
				updated = moment.parseZone( response.updated );
				jQuery( '#cite_updated_date' ).val( updated.format( 'YYYY-MM-DD' ) ) ;
				jQuery( '#cite_updated_time' ).val( updated.format( 'HH:mm:ss' ) ) ;
				jQuery( '#cite_updated_offset' ).val( updated.format( 'Z' ) );
			}
			if ( 'summary' in response ) {
				jQuery( '#cite_summary' ).val( response.summary ) ;
			}
			if ( 'featured' in response ) {
				jQuery( '#cite_featured' ).val( response.featured ) ;
			}
			if ( ( 'author' in response ) && ( 'string' != typeof response.author ) ) {
				if ( 'name' in response.author ) {
					jQuery( '#cite_author_name' ).val( response.author.name.join( ';' ) ) ;
				}
				if ( 'photo' in response.author ) {
					jQuery( '#cite_author_photo' ).val( response.author.photo.join( ';' ) ) ;
				}
				if ( 'url' in response.author ) {
					jQuery( '#cite_author_url' ).val( response.author.url.join( ';' ) ) ;
				}
			}
			if ( 'category' in response ) {
				jQuery( '#cite_tags' ).val( response.category.join( ';' ) );
			}
		alert( PKAPI.success_message );
		console.log( response );
		},
		fail: function( response ) {
			console.log( response );
			alert( response.message );
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			alert( textStatus );
			console.log( jqXHR );
		}
	});
}

jQuery( document )
	.on( 'click', '.kind-retrieve-button', function( $ ) {
		getLinkPreview();
	});
});
