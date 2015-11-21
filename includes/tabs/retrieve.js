jQuery( document ).on( 'click', '.kind-retrieve-button', function() {
	var post_id = jQuery(this).data('id');
	jQuery.ajax({
		url : ajaxurl,
		type : 'post',
		data : {
			action : 'kind_urlfetch',
			post_id : jQuery(this).val('input:url')
		},
		success : function( response ) {
			alert( response.join('\n') );
		}
	});
})
