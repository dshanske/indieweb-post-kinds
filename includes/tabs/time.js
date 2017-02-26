(function($) {
	$(function() {
	  var elem = document.createElement('input');
 	 	elem.setAttribute('type', 'date');
 	 	if ( elem.type === 'text' ) {
    	  $('#mf2_start_date').datepicker({
					dateFormat: "yy-mm-dd"
				});
				$('#mf2_end_date').datepicker({
					dateFormat: "yy-mm-dd"
				});
    	  $('#cite_published_date').datepicker({
					dateFormat: "yy-mm-dd"
				});
				$('#cite_updated_date').datepicker({
					dateFormat: "yy-mm-dd"
				});
  	}
		elem.setAttribute('type', 'time');
		if ( elem.type === 'text' ) {
				$('#mf2_start_time').timepicker({
					timeFormat: "H:i:s",
					step: 15
				});
				$('#mf2_end_time').timepicker({
					timeFormat: "H:i:s",
					step: 15
				});
				$('#cite_published_time').timepicker({
					timeFormat: "H:i:s",
					step: 15
				});
				$('#cite_updated_time').timepicker({
					timeFormat: "H:i:s",
					step: 15
				});

		}
	});
}(jQuery));
