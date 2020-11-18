(function ($) {
    $(document).ready(function() {
   
     $('.cce').click(function() {  
          
          $(".view-id-entity_types").show();
     });
     
     $('.view-id-entity_types').mouseleave(function() {  
      
          $(".view-id-entity_types").hide();
      });

      $('.view-id-summary_key').hover(function() {  
      
        $(".view-id-summary_key .table span a").css(color, red);
    });
 

    });
   })(jQuery);