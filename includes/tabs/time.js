(function($) {
	$(function() {
	  var elem = document.createElement('input');
 	 	elem.setAttribute('type', 'date');
 	 	if ( elem.type === 'text' ) {
    	  $('#start_date').datepicker({
					dateFormat: "yy-mm-dd"
				});
				$('#end_date').datepicker({
					dateFormat: "yy-mm-dd"
				});
  	}
		elem.setAttribute('type', 'time');
		if ( elem.type === 'text' ) {
				$('#start_time').timepicker({
					timeFormat: "H:i:s",
					step: 15
				});
				$('#end_time').timepicker({
					timeFormat: "H:i:s",
					step: 15
				});
		}
	});
}(jQuery));
