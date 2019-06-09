jQuery( document ).ready( function( $ ) {
        var elem = document.createElement( 'input' );

         elem.setAttribute( 'type', 'date' );
         if ( 'text' === elem.type ) {
                        $( '#mf2_start_date' ).datepicker({
                                dateFormat: 'yy-mm-dd'
                        });
                        $( '#mf2_end_date' ).datepicker({
                                dateFormat: 'yy-mm-dd'
                        });
                        $( '#cite_published_date' ).datepicker({
                                dateFormat: 'yy-mm-dd'
                        });
                        $( '#cite_updated_date' ).datepicker({
                                dateFormat: 'yy-mm-dd'
                        });

                }
                elem.setAttribute( 'type', 'time' );
                if ( 'text' === elem.type ) {
                                $( '#mf2_start_time' ).timepicker({
                                        timeFormat: 'H:i:s',
                                        step: 15
                                });
                                $( '#mf2_end_time' ).timepicker({
                                        timeFormat: 'H:i:s',
                                        step: 15
                                });
                                $( '#cite_published_time' ).timepicker({
                                        timeFormat: 'H:i:s',
                                        step: 15
                                });
                                $( '#cite_updated_time' ).timepicker({
                                        timeFormat: 'H:i:s',
                                        step: 15
                                });

                }
	$( '#duration' ).datepair();
	changeSettings();


function clearPostProperties() {
	var fieldIds = [
		'cite_url',
		'cite_name',
		'cite_summary',
		'cite_tags',
		'cite_media',
		'cite_author_name',
		'cite_author_url',
		'cite_author_photo',
		'cite_featured',
		'cite_publication',
		'mf2_rsvp',
		'mf2_start_time',
		'mf2_start_date',
		'mf2_end_time',
		'mf2_end_date',
		'cite_published_time',
		'cite_published_date',
		'cite_updated_time',
		'cite_updated_date',
		'duration_years',
		'duration_months',
		'duration_days',
		'duration_hours',
		'duration_minutes',
		'duration_seconds'
	];
		if ( ! confirm( PKAPI.clear_message ) ) {
			return;
		}
		$.each( fieldIds, function( count, val ) {
			document.getElementById( val ).value = '';
		});
		$( '#kind-media-container' ).addClass( 'hidden' );
		$( '#kind-media-container' ).children( 'img' ).hide();
		$( '#add-kind-media' ).show();

}

function addhttp( url ) {
	if ( ! /^(?:f|ht)tps?\:\/\//.test( url ) ) {
		url = 'http://' + url;
	}
	return url;
}

function showLoadingSpinner() {
	$( '#replybox-meta' ).addClass( 'is-loading' );
}

function hideLoadingSpinner() {
	$( '#replybox-meta' ).removeClass( 'is-loading' );
}

//function used to validate website URL
function checkUrl( url ) {

    //regular expression for URL
    var pattern = /^(http|https)?:\/\/[a-zA-Z0-9-\.]+\.[a-z]{2,4}/;

    if ( pattern.test( url ) ) {
        return true;
    } else {
        return false;
    }
}

function getLinkPreview() {
	if ( '' === $( '#cite_url' ).val() ) {
		return;
	}
	$.ajax({
		type: 'GET',

		// Here we supply the endpoint url, as opposed to the action in the data object with the admin-ajax method
		url: PKAPI.api_url + 'parse/',
		beforeSend: function( xhr ) {

		// Here we set a header 'X-WP-Nonce' with the nonce as opposed to the nonce in the data object with admin-ajax
		xhr.setRequestHeader( 'X-WP-Nonce', PKAPI.api_nonce );
		},
		data: {
			url: $( '#cite_url' ).val(),
			kind: $( 'input[name=\'tax_input[kind]\']:checked' ).val(),
			follow: true
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
				$( '#cite_name' ).val( response.name );
			}
			if ( 'publication' in response ) {
				$( '#cite_publication' ).val( response.publication ) ;
			}
			if ( 'published' in response ) {
				published = moment.parseZone( response.published );
				$( '#cite_published_date' ).val( published.format( 'YYYY-MM-DD' ) ) ;
				$( '#cite_published_time' ).val( published.format( 'HH:mm:ss' ) ) ;
				$( '#cite_published_offset' ).val( published.format( 'Z' ) );
			}
			if ( 'updated' in response ) {
				updated = moment.parseZone( response.updated );
				$( '#cite_updated_date' ).val( updated.format( 'YYYY-MM-DD' ) ) ;
				$( '#cite_updated_time' ).val( updated.format( 'HH:mm:ss' ) ) ;
				$( '#cite_updated_offset' ).val( updated.format( 'Z' ) );
			}
			if ( 'duration' in response ) {
				$( '#duration_years' ).val( moment.duration( response.duration ) ).years();
				$( '#duration_months' ).val( moment.duration( response.duration ) ).months();
				$( '#duration_days' ).val( moment.duration( response.duration ) ).days();
				$( '#duration_hours' ).val( moment.duration( response.duration ) ).hours();
				$( '#duration_minutes' ).val( moment.duration( response.duration ) ).minutes();
				$( '#duration_seconds' ).val( moment.duration( response.duration ) ).seconds();
			}

			if ( 'summary' in response ) {
				$( '#cite_summary' ).val( response.summary ) ;
			}
			if ( 'featured' in response ) {
				$( '#cite_featured' ).val( response.featured ) ;
			}
			if ( ( 'author' in response ) && ( 'string' != typeof response.author ) ) {
				if ( 'name' in response.author ) {
					if ( 'string' === typeof response.author.name ) {
						$( '#cite_author_name' ).val( response.author.name );
					} else {
						$( '#cite_author_name' ).val( response.author.name.join( ';' ) ) ;
					}
				}
				if ( 'photo' in response.author ) {
					if ( 'string' === typeof response.author.name ) {
						$( '#cite_author_photo' ).val( response.author.photo );
					} else {
						$( '#cite_author_photo' ).val( response.author.photo.join( ';' ) ) ;
					}
				}
				if ( 'url' in response.author ) {
					if ( 'string' === typeof response.author.url ) {
						$( '#cite_author_url' ).val( response.author.url );
					} else {
						$( '#cite_author_url' ).val( response.author.url.join( ';' ) ) ;
					}
				}
			}
			if ( 'category' in response ) {
				if ( 'object' === typeof response.category ) {
					$( '#cite_tags' ).val( response.category.join( ';' ) );
				}
			}
		alert( PKAPI.success_message );
		console.log( response );
		},
		fail: function( response ) {
			console.log( response );
			alert( response.message );
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			alert( jqXHR.responseJSON.message );
			console.log( jqXHR );
		},
		always: hideLoadingSpinner()
	});
}

function changeSettings() {
	kind = $( 'input[name=\'tax_input[kind]\']:checked' ).val();
	switch ( kind ) {
		case 'note':
			hideTitle();
			hideReply();
			hideRSVP();
			hideTime();
			hideMedia();
			break;
		case 'article':
			showTitle();
			hideReply();
			hideRSVP();
			hideTime();
			hideMedia();
			break;
		case 'issue':
			showTitle();
			showReply();
			hideRSVP();
			hideTime();
			hideMedia();
			break;
		case 'listen':
		case 'jam':
		case 'watch':
			hideTitle();
			showReply();
			hideRSVP();
			showTime();
			hideMedia();
			break;
		case 'photo':
		case 'video':
		case 'audio':
			hideTitle();
			showReply();
			hideRSVP();
			showTime();
			showMedia();
			break;
		case 'rsvp':
			showReply();
			hideTitle();
			hideTime();
			showRSVP();
			hideMedia();
			break;
		default:
			showReply();
			hideTime();
			hideTitle();
			hideRSVP();
			hideMedia();
	}
}

function showReply() {
	$( '#replybox-meta' ).removeClass( 'hidden' );
}

function hideReply() {
	$( '#replybox-meta' ).addClass( 'hidden' );
}

function showMedia() {
	$( '#add-kind-media' ).removeClass( 'hidden' );
}

function hideMedia() {
	$( '#add-kind-media' ).addClass( 'hidden' );
}


function showTitle() {
	var titlediv = $( '#titlediv' ).detach();
	titlediv.prependTo( '#post-body-content' );
}

function hideTitle() {
	var titlediv = $( '#titlediv' ).detach();
	titlediv.insertAfter( '#postdivrich' );
}

function showRSVP() {
	$( '#rsvp-option' ).removeClass( 'hide-if-js' );
}

function hideRSVP() {
	$( '#rsvp-option' ).addClass( 'hide-if-js' );
}

function showTime() {
	$( '#kind-time' ).removeClass( 'hide-if-js' );
}

function hideTime() {
	$( '#kind-time' ).addClass( 'hide-if-js' );
}

function handleKindMediaWindow() {
	'use strict';

        var KindWindow, ImageData, json;

	/**
	 * If an instance of KindWindow already exists, then we can open it
	 * rather than creating a new instance.
	 */
	if ( undefined !== KindWindow ) {
		KindWindow.open();
		return;
	}

	KindWindow = wp.media.frames.KindWindow = wp.media({
		title: 'Attach',
		button: {
			text: 'Use this media'
		},
		multiple: false
	});

	KindWindow.on( 'select', function() {
		json = KindWindow.state().get( 'selection' ).first().toJSON();
		console.log( json );
		if ( 0 > $.trim( json.url.length ) ) {
			return;
		}
		$( '#cite_name' ).val( json.title );
		$( '#cite_url' ).val( json.url );
		$( '#cite_media' ).val( json.id );
		$( '#cite_summary' ).val( json.description );
		$( '#kind-media-container' )
			.children( 'img' )
			.attr( 'src', json.url )
			.attr( 'alt', json.caption )
			.attr( 'title', json.title )
			.show()
			.parent()
			.removeClass( 'hidden' );

		$( '#add-kind-media' ).hide();
	});
	KindWindow.open();

}

jQuery( document )
	.on( 'change', '#taxonomy-kind', function( event ) {
		changeSettings();
		event.preventDefault();
	})
	.on( 'blur', '#cite_url', function( event ) {
		if ( '' !== $( '#cite_url' ).val() ) {
			if ( false == checkUrl( $( '#cite_url' ).val() ) ) {
				alert( 'Invalid URL' );
			} else if ( '' === $( '#cite_name' ).val() ) {
				showLoadingSpinner();
				getLinkPreview();
			}
			event.preventDefault();
		}
	})
	.on( 'click', '.clear-kindmeta-button', function( event ) {
		clearPostProperties();
		event.preventDefault();
	})
	.on( 'click', 'a.show-kind-details', function( event ) {
		if ( $( '#kind-details' ).is( ':hidden' ) ) {
			$( '#kind-author' ).slideUp( 'fast' ).siblings( 'a.show-kind-author' );
			$( '#kind-details' ).slideDown( 'fast' ).siblings( 'a.hide-kind-details' ).show().focus();
		} else {
			$( '#kind-details' ).slideUp( 'fast' ).siblings( 'a.show-kind-details' ).focus();
		}
		event.preventDefault();
	})
	.on( 'click', 'a.show-kind-author-details', function( event ) {
		if ( $( '#kind-author' ).is( ':hidden' ) ) {
			$( '#kind-details' ).slideUp( 'fast' ).siblings( 'a.show-kind-details' );
			$( '#kind-author' ).slideDown( 'fast' ).siblings( 'a.hide-kind-author' ).show().focus();
		} else {
			$( '#kind-author' ).slideUp( 'fast' ).siblings( 'a.show-kind-author' ).focus();
		}
		event.preventDefault();
	})
	.on( 'click', '#add-kind-media', function( event ) {
		event.preventDefault();
		handleKindMediaWindow();
	});
});
