(function( $ ) {
	'use strict';
	$(function() {

		// Grab the wrapper for the Navigation Tabs
		var navTabs = $( '#kindproperties-navigation').children( '.nav-tab-wrapper' ),
			tabIndex = null;

		/* Whenever each of the navigation tabs is clicked, check to see if it has the 'nav-tab-active'
		 * class name. If not, then mark it as active; otherwise, don't do anything (as it's already
		 * marked as active.
		 *
		 * Next, when a new tab is marked as active, the corresponding child view needs to be marked
		 * as visible. We do this by toggling the 'hidden' class attribute of the corresponding variables.
		 */
		navTabs.children().each(function() {

			$( this ).on( 'click', function( evt ) {

				evt.preventDefault();

				// If this tab is not active...
				if ( ! $( this ).hasClass( 'nav-tab-active' ) ) {

					// Unmark the current tab and mark the new one as active
					$( this ).siblings( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
					$( this ).addClass( 'nav-tab-active' );

					// Save the index of the tab that's just been marked as active. It will be 0 - 3.
					tabIndex = $( this ).index();

					// Hide the old active content
					$( '#kindproperties-navigation' )
						.children( 'div:not( .inside.hidden )' )
						.addClass( 'hidden' );

					$( '#kindproperties-navigation' )
						.children( 'div:nth-child(' + ( tabIndex ) + ')' )
						.addClass( 'hidden' );

					// And display the new content
					$( '#kindproperties-navigation' )
						.children( 'div:nth-child( ' + ( tabIndex + 2 ) + ')' )
						.removeClass( 'hidden' );


				}


			});
		});

	});

})( jQuery );
