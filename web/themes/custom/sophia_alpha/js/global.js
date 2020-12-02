(function ($) {
    $(document).ready(function() {
      $('#sidebar_first').show();
   /*
     $('.cce').click(function() {  
       $(".view-id-entity_types").slideDown(1500);
        
        //Modal declaration start
          $(".view-id-entity_types").dialog({
            title: "Entity Types",
            modal: true,
            width: 480,
            height: 320,
            buttons: {
              Close: 
                function(){
                  $(this).dialog('close');
                }
              
            },
          });
          //modal declaration finish
      }); 
     
     $('.view-id-entity_types').mouseleave(function() {  
      
          $(".view-id-entity_types").slideUp(1000);
      });
      */

      
/*
      $('.view-id-summary_key').hover(function() {  
      
        $(".view-id-summary_key .table span a").css(color, red);
      });

    $('.ncce').click(function() {  
      var et = $('.view-id-entity_type');
      $("#entitytypeinfo").html(et + "To come from view block. so the ids are displayed automatically as updated in database");
      $(".view-id-entity_types").dialog();
  });
*/
flipKeyInsert();
function flipKeyInsert () {
  $(".view-id-entity_types").prependTo(".view-display-id-block_3 .view-content.row");
      
//var flipKey = $('.view-id-summary_key .table span a').val();

 // if ( flipKey == 'I') {
  //  $(".view-id-summary_key .table span a").addClass("I_Key");
  //}
  //else {
  //  $(".view-id-summary_key .table span a").addClass("summaryFlipKeys");
  //};
  

};
    

    });
   })(jQuery);