/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

 	'use strict';
 	function archiveLinks(link) {
 		$(link).attr('target','_blank')
 			   .not("div.view-publications " + link)
 			   .not("div.view-commonwealth-monthly-financial-statements " + link)
 			   .after(' <span class="pillDefault pillArchived">Archived</span>');
 	}

 	function externalLinks(link) {
 		$(link).not('a[href*="' + window.location.hostname + '"]')
 			   .not('a[href*=webarchive]')
 			   .not('div.lead-in-cards a')
 			   .not('div.front-page-card a')
 			   .not('div.views-field-field-community-title-and-link a')
 			   .not('div.paragraph--type--key-links a')
 			   .attr('target','_blank')
 			   .after(' <i class="fa fa-external-link-alt greenExt"></i>');

 		// Lead in cards need their external link located inside the card
 		$("div.lead-in-cards " + link).not('a[href*="' + window.location.hostname + '"]')
 			   .not('a[href*=webarchive]')
 			   .attr('target','_blank')
 			   .find("h3 i")
 			   .before(' <i class="fa fa-external-link-alt greenExt"></i>');

 		// Key Links need their external link located inside the card
 		$("div.paragraph--type--key-links " + link).not('a[href*="' + window.location.hostname + '"]')
 			   .not('a[href*=webarchive]')
 			   .attr('target','_blank')
 			   .each(function(){
 			   		$(this).html($(this).html() + ' <i class="fa fa-keylinks-external fa-external-link-alt greenExt"></i>');
 			   });
 		//Community Title and Link card
 		$("div.views-field-field-community-title-and-link " + link).not('a[href*="' + window.location.hostname + '"]')
 			   .not('a[href*=webarchive]')
 			   .attr('target','_blank')
 			   .find("h2 i")
 			   .before(' <i class="fa fa-external-link-alt greenExt"></i>');

 		//Community Title and Link card
 		$("div.front-page-card " + link).not('a[href*="' + window.location.hostname + '"]')
 			   .not('a[href*=webarchive]')
 			   .attr('target','_blank')
 			   .append('<i class="fa fa-external-link-alt greenExt"></i>');

 	}

 	// function recursiveHide(section) {

 	// 	$("div[data-wizard-section='" + section +"']").hide();

 	// 	$("div[data-wizard-section='" + section +"']").find(".wizard-button").each(function(){
 	// 		var showTargets = $(this).attr("data-wizard-target").split(",");

 	// 		showTargets.forEach(function(target){

 	// 			$("div[data-wizard-section='" + target +"']").hide();

 	// 		});
 	// 	});
 	// }

 	$(document).ready(function() {
 		// Adds in External Links
 		externalLinks('a[href^="http://"]');
 		externalLinks('a[href^="https://"]');

 		// Adds in Archive Pill to pages
 		archiveLinks('a[href^="http://webarchive"]');
 		archiveLinks('a[href^="https://webarchive"]');

 		// Move modal into content for CSS
 		$("#cm-full-width").detach().appendTo("#content");

 		$("a.buyright-collapse-link").click(function(){

 			var chevron = $(this).find(".chevron");
 			var target = $($(this).attr("data-collapse-target"));
 			var active = chevron.attr('data-active') == "true";

 			if (!target.hasClass("collapsing")) {	 				
	 			if (active) {
	 				target.collapse("hide");
	 				chevron.attr('data-active', "false");
	 				chevron.animate(
				    	{ deg: 0 },
					    {
					      duration: 200,
					      step: function(now) {
					        chevron.css({ transform: 'rotate(' + now + 'deg)' });
					    }
					});
	 			} else {
	 				target.collapse("show");
	 				chevron.attr('data-active', "true");
	 				chevron.animate(
				    	{ deg: 90 },
					    {
					      duration: 200,
					      step: function(now) {
					        chevron.css({ transform: 'rotate(' + now + 'deg)' });
					    }
				    });
	 			}
 			}

 		});

 		$("#redirect").each(function(){
 			var url = $(this).attr("data-redirect-to");
 			setTimeout(function(){ 
 				window.location = url;
 			}, 3000);
 		});

 		$(".wizard-section").first().show();

 		// ID of the root section of a wizard
 		var rootID = $(".wizard-section").attr("data-wizard-section");
 		var wizardTracking = [];

 		$(".wizard-button").click(function(){

 			var showTargets = $(this).attr("data-wizard-target").split(",");
 			var sectionID = $(this).attr("data-wizard-button-section");
 			var currentClick = JSON.stringify([showTargets, sectionID]);
 			var lastClick = JSON.stringify(wizardTracking[wizardTracking.length - 1]);
 			console.log(currentClick);
 			console.log(lastClick);
 			console.log(false);
 			if (currentClick == lastClick) {
 				$(this).prop('checked', false);
 				wizardTracking.pop();
 			} else {
	 			wizardTracking.push([showTargets, sectionID]);
	 		}
 			// console.log(wizardTracking);
 			var newTracking = [];
 			var i = 0;

 			// Calculate path to the current section
 			while (i < wizardTracking.length) {

 				var node = wizardTracking[i];

 				var nodeShow = node[0];
 				var nodeSection = node[1];

 				if (sectionID == nodeSection) {
 					newTracking.push([showTargets, sectionID]);
 					break;
 				} else {
 					newTracking.push(node);
 				}

 				i += 1; 

 			}

 			wizardTracking = newTracking;

 			var i = 0;
 			var allShownNodes = [rootID];
 			var allHiddenNodes = [];

 			// Find visible nodes to the current section
 			while (i < wizardTracking.length) {

 				var node = wizardTracking[i];

 				node[0].forEach(function(section){

 					if (allShownNodes.indexOf(section) === -1) {
 						allShownNodes.push(section);
 					}

 				});

 				i += 1;

 			}

 			// Hide anything that shouldn't be shown
 			$(".wizard-section").each(function(){
 				var id = $(this).attr("data-wizard-section");

				if (allShownNodes.indexOf(id) === -1) {
					$(this).hide();
					$(this).find("input[type='radio']").each(function(){
						$(this).prop('checked', false);
					});
				} else {
					if ($(this).is(':hidden')){ 
						$(this).show();
					}
				}

 			});

 		});

 		$(".content-modal-button").click(function(){

 			var target = $($(this).attr("data-target-cm"));
 			var title = $(this).attr("data-modal-title");

 			var html = $(this).html();

 			target.find(".modal-body").html(html);
 			target.find(".sticky-zoom").detach();
 			target.find(".modal-title").text(title);

 			if (target.html().length) {
 				target.modal("show");
 			}

 		});

 		$(".content-modal-button").each(function(){
 			$(this).prepend('<i class="float-right sticky-zoom fas fa-search-plus"></i>')
 		});

 	});

 	Drupal.behaviors.finance = {
 		attach: function (context, settings) {

 		}
 	};
 	$(function() {
 		$('nav#block-mainnavigation').mmenu({
 			"extensions": [
 			"pagedim-black",
 			"position-left"],
 			 offCanvas: {
		      position: "right"
		   	}
 		});

 		$("#mm-1 > div > a").before('<i id="close-mmenu" class="fa fa-window-close"></i>');
 	
		$("#close-mmenu").click(function(){
			$('nav#block-mainnavigation').data("mmenu").close();
	    });
 	});

 	$("#sidebar_second .block").first().prepend('<a href="javascript:print()" id="print-icon" class="fa fa-print float-right" title="Print this page"></a>');

 	$("#copyContent").click(function(){
 		var $temp = $("<textarea>");
 		$("body").append($temp);
 		$temp.val($("#main-content .col-12").html()).select();
 		document.execCommand("copy");
 		$temp.remove();
 	});

 	if ($('#content .region-content h2, #content .region-content h3').length >= 3) {
	 	$('.content-nav-wrapper nav').html('');
	    // To generate the scrollspy menu on the LHS...
	    $('#content .region-content').anchorific({
			navigation: '.content-nav', // position of navigation
			headers: 'h2, h3', // headers that you wish to target
			speed: 200, // speed of sliding back to top
			anchorClass: 'anchor', // class of anchor links
			anchorText: '#', // prepended or appended to anchor headings
			top: '.top', // back to top button or link class
			spy: true, // scroll spy
			position: 'append', // position of anchor text
			spyOffset: 20 // specify heading offset for spy scrolling
	  	});
    	$('nav.content-nav > ul').append('<li><a href="#top" class="top">Back to top</a></li>');
	} else {
		$('#sidebar_first').hide();
	}

	var clipboard = new ClipboardJS('.copy-button');

})(jQuery, Drupal);

