/* global PKAPI, moment, wp */
/* eslint-disable no-alert -- The metabox uses alert() and confirm() for feedback by design. */
jQuery( document ).ready( function( $ ) {
	var KindWindow;

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
			'mf2_rating',
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
			'duration_seconds',
		];
		if ( ! confirm( PKAPI.clear_message ) ) {
			return;
		}
		$.each( fieldIds, function( count, val ) {
			document.getElementById( val ).value = '';
		} );
		$( '#kind-media-container' ).addClass( 'hidden' );
		$( '#kind-media-container' ).children( 'img' ).hide();
		$( '#add-kind-media' ).show();
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

		return pattern.test( url );
	}

	// Fills the Author tab from one author or a list. Each field holds one value
	// per author, separated by semicolons, so the values stay paired by position.
	function fillAuthors( authors ) {
		var fields = { name: [], url: [], photo: [] };
		if ( ! Array.isArray( authors ) ) {
			authors = [ authors ];
		}
		$.each( authors, function( index, author ) {
			if ( 'string' === typeof author ) {
				author = checkUrl( author ) ? { url: author } : { name: author };
			}
			if ( ! author || 'object' !== typeof author ) {
				return;
			}
			$.each( fields, function( key, values ) {
				var value = author[ key ];
				if ( Array.isArray( value ) ) {
					value = value.join( '; ' );
				} else if ( value && 'object' === typeof value ) {
					value = value.value;
				}
				values.push( 'string' === typeof value ? value : '' );
			} );
		} );
		$.each( fields, function( key, values ) {
			$( '#cite_author_' + key ).val( values.join( '' ) ? values.join( '; ' ) : '' );
		} );
	}

	function getLinkPreview() {
		if ( '' === $( '#cite_url' ).val() ) {
			return;
		}
		$.ajax( {
			type: 'GET',

			// Here we supply the endpoint url, as opposed to the action in the data object with the admin-ajax method
			url: PKAPI.api_url + 'parse/',
			beforeSend: function( xhr ) {
				// Here we set a header 'X-WP-Nonce' with the nonce as opposed to the nonce in the data object with admin-ajax
				xhr.setRequestHeader( 'X-WP-Nonce', PKAPI.api_nonce );
			},
			data: {
				url: $( '#cite_url' ).val(),
				follow: true,
			},
			success: function( response ) {
				var published, updated, duration;
				if ( 'undefined' === typeof response ) {
					alert( PKAPI.error_message );
					return;
				}
				if ( 'message' in response ) {
					alert( response.message );
					return;
				}
				if ( 'name' in response ) {
					$( '#cite_name' ).val( response.name );
				}
				if ( 'published' in response ) {
					published = moment.parseZone( response.published );
					$( '#cite_published_date' ).val( published.format( 'YYYY-MM-DD' ) );
					$( '#cite_published_time' ).val( published.format( 'HH:mm:ss' ) );
					$( '#cite_published_offset' ).val( published.format( 'Z' ) );
				}
				if ( 'updated' in response ) {
					updated = moment.parseZone( response.updated );
					$( '#cite_updated_date' ).val( updated.format( 'YYYY-MM-DD' ) );
					$( '#cite_updated_time' ).val( updated.format( 'HH:mm:ss' ) );
					$( '#cite_updated_offset' ).val( updated.format( 'Z' ) );
				}
				if ( 'duration' in response ) {
					duration = moment.duration( response.duration );
					$( '#duration_years' ).val( duration.years() );
					$( '#duration_months' ).val( duration.months() );
					$( '#duration_days' ).val( duration.days() );
					$( '#duration_hours' ).val( duration.hours() );
					$( '#duration_minutes' ).val( duration.minutes() );
					$( '#duration_seconds' ).val( duration.seconds() );
				}

				if ( 'summary' in response ) {
					$( '#cite_summary' ).val( response.summary );
				}
				if ( 'featured' in response ) {
					$( '#cite_featured' ).val( response.featured );
				}
				if ( 'author' in response ) {
					fillAuthors( response.author );
				}
				if ( 'publication' in response && ( 'string' !== typeof response.publication ) ) {
					if ( 'name' in response.publication ) {
						$( '#cite_publication' ).val( response.publication.name );
					}
				} else {
					$( '#cite_publication' ).val( response.publication );
				}

				if ( 'category' in response ) {
					if ( 'object' === typeof response.category ) {
						$( '#cite_tags' ).val( response.category.join( ';' ) );
					}
				}
				alert( PKAPI.success_message );
			},
			error: function( jqXHR ) {
				if ( jqXHR.responseJSON && jqXHR.responseJSON.message ) {
					alert( jqXHR.responseJSON.message );
				} else {
					alert( PKAPI.error_message );
				}
			},
			complete: hideLoadingSpinner,
		} );
	}

	function changeSettings() {
		var kind = $( 'input[name=\'tax_input[kind]\']:checked' ).val();
		switch ( kind ) {
			case 'note':
				hideTitle();
				hideReply();
				hideRSVP();
				hideRating();
				hideTime();
				hideMedia();
				break;
			case 'article':
				showTitle();
				hideReply();
				hideRSVP();
				hideRating();
				hideTime();
				hideMedia();
				break;
			case 'issue':
				showTitle();
				showReply();
				hideRSVP();
				hideRating();
				hideTime();
				hideMedia();
				break;
			case 'listen':
			case 'jam':
			case 'watch':
			case 'read':
			case 'play':
				hideTitle();
				showReply();
				hideRSVP();
				showRating();
				showTime();
				hideMedia();
				break;
			case 'photo':
			case 'video':
			case 'audio':
				hideTitle();
				showReply();
				hideRSVP();
				hideRating();
				showTime();
				showMedia();
				break;
			case 'rsvp':
				showReply();
				hideTitle();
				hideTime();
				showRSVP();
				hideRating();
				hideMedia();
				break;
			case 'review':
			case 'drink':
			case 'eat':
				showReply();
				hideTitle();
				hideTime();
				hideRSVP();
				showRating();
				hideMedia();
				break;
			default:
				showReply();
				hideTime();
				hideTitle();
				hideRSVP();
				hideRating();
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

	function showRating() {
		$( '#rating-option' ).removeClass( 'hide-if-js' );
	}

	function hideRating() {
		$( '#rating-option' ).addClass( 'hide-if-js' );
	}

	function showTime() {
		$( '#kind-time' ).removeClass( 'hide-if-js' );
	}

	function hideTime() {
		$( '#kind-time' ).addClass( 'hide-if-js' );
	}

	function handleKindMediaWindow() {
		'use strict';

		var json;

		/**
		 * If an instance of KindWindow already exists, then we can open it
		 * rather than creating a new instance.
		 */
		if ( undefined !== KindWindow ) {
			KindWindow.open();
			return;
		}

		KindWindow = wp.media.frames.KindWindow = wp.media( {
			title: PKAPI.media_title,
			button: {
				text: PKAPI.media_button,
			},
			multiple: false,
		} );

		KindWindow.on( 'select', function() {
			json = KindWindow.state().get( 'selection' ).first().toJSON();
			if ( ! json.url ) {
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
		} );
		KindWindow.open();
	}

	jQuery( document )
		.on( 'change', '#taxonomy-kind', function( event ) {
			changeSettings();
			event.preventDefault();
		} )
		.on( 'blur', '#cite_url', function( event ) {
			if ( '' !== $( '#cite_url' ).val() ) {
				if ( false === checkUrl( $( '#cite_url' ).val() ) ) {
					alert( PKAPI.invalid_url );
				} else if ( '' === $( '#cite_name' ).val() ) {
					showLoadingSpinner();
					getLinkPreview();
				}
				event.preventDefault();
			}
		} )
		.on( 'click', '.clear-kindmeta-button', function( event ) {
			clearPostProperties();
			event.preventDefault();
		} )
		.on( 'click', 'a.show-kind-details', function( event ) {
			if ( $( '#kind-details' ).is( ':hidden' ) ) {
				$( '#kind-author' ).slideUp( 'fast' ).siblings( 'a.show-kind-author' );
				$( '#kind-details' ).slideDown( 'fast' ).siblings( 'a.hide-kind-details' ).show().focus();
			} else {
				$( '#kind-details' ).slideUp( 'fast' ).siblings( 'a.show-kind-details' ).focus();
			}
			event.preventDefault();
		} )
		.on( 'click', 'a.show-kind-author-details', function( event ) {
			if ( $( '#kind-author' ).is( ':hidden' ) ) {
				$( '#kind-details' ).slideUp( 'fast' ).siblings( 'a.show-kind-details' );
				$( '#kind-author' ).slideDown( 'fast' ).siblings( 'a.hide-kind-author' ).show().focus();
			} else {
				$( '#kind-author' ).slideUp( 'fast' ).siblings( 'a.show-kind-author' ).focus();
			}
			event.preventDefault();
		} )
		.on( 'click', '#add-kind-media', function( event ) {
			event.preventDefault();
			handleKindMediaWindow();
		} );
} );
