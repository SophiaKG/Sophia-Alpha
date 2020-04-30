(function ($) {

	$(document).ready(function(){

		$("div.rm-title").click(function(){

			$(this).next(".collapse").first().collapse("toggle");

		});
	});

}(jQuery));