/**
 * Tools > Parse This: sends the form to the parse route with the REST nonce
 * in a header and shows the JSON result.
 *
 * @package Parse_This
 */

/* global parseThisDebug */
( function () {
	var form = document.getElementById( 'parse-this-debug' );
	var output = document.getElementById( 'parse-this-result' );
	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		var endpoint = parseThisDebug.endpoint;
		var query = new URLSearchParams( new FormData( form ) ).toString();
		output.textContent = parseThisDebug.parsing;
		fetch( endpoint + ( -1 === endpoint.indexOf( '?' ) ? '?' : '&' ) + query, {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': parseThisDebug.nonce }
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				output.textContent = JSON.stringify( data, null, 2 );
			} )
			.catch( function ( error ) {
				output.textContent = String( error );
			} );
	} );
} )();
