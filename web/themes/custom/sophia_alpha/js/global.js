(function ($) {
    $(document).ready(function() {
      $("#entity_type_descrip").click(function(){
        var description = $(".view-id-entity_types").html();
        /*$("#nccemodal").addClass("modal-dialog");*/
        $("#nccemodal").html(description);
        $("#nccemodal thead").css("display", "none");
        $("#nccemodal .views-field-name").css("display", "none");
        $("#nccemodal").dialog({
          modal: true,
          title:"Description - Type of Body",
          width: 600,
        });
      });
     
    /* Below adds a print button on /summary-print page*/ 
    $(".path-summary-print .color-keys p").prepend('<a href="javascript:print()" id="print-icon" class="fa fa-print float-left" title="Print this page"> Print </a>');

    /* Below adds the entity type vocab to keychart on /summary page */ 
    $(".view-id-entity_types").prependTo(".view-display-id-block_3 .view-content.row");
   /*
    * Sidebar first is being hidden through Javascript from base theme even
    * though set to display in current theme. However set to display here is
    * causing delay in loading. reset to force display via css until investigated further.
    *
   $('#sidebar_first').show();
   ********
    */
  
    });
   })(jQuery);