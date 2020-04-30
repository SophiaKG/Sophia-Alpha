
/**
 * detect IEEdge
 * returns version of IE/Edge or false, if browser is not a Microsoft browser
 */
function detectIEEdge() {
    var ua = window.navigator.userAgent;

    var msie = ua.indexOf('MSIE ');
    if (msie > 0) {
        // IE 10 or older => return version number
        return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
    }

    var trident = ua.indexOf('Trident/');
    if (trident > 0) {
        // IE 11 => return version number
        var rv = ua.indexOf('rv:');
        return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
    }

    // other browser
    return false;
}

if (detectIEEdge()) {

	(function($) {

		$(document).ready(function() {

			$("#sidebar_first > div").css("display", "unset");
			$("#sidebar_first > div").css("position", "relative");

			$(window).scroll(function() {


				var scrollTop     = $(window).scrollTop(),
				elementOffset = $('#main').offset().top,
				distance      = (elementOffset - scrollTop);

				var top = Math.min(-distance + $('#main').offset().top, $('#main').offset().top + $('#main').height() - $("#sidebar_first > div").height() - 500);

				$("#sidebar_first > div").css("top", top + "px");

			});
		})

	})(jQuery);

}