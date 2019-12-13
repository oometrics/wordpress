jQuery(document).ready(function($){
  jQuery( document ).on( 'submit', '#oometrics-admin-form', function ( e ) {

       e.preventDefault();

       // We inject some extra fields required for the security
       jQuery(this).append('<input type="hidden" name="action" value="oo_store_admin_data" />');
       jQuery(this).append('<input type="hidden" name="security" value="'+ oometrics._nonce +'" />');

       // We make our call
       jQuery.ajax( {
           url: oometrics.ajaxurl,
           type: 'post',
           data: jQuery(this).serialize(),
           beforeSend:function(){
             jQuery('.oo-settings-notification').html('Saving ...').addClass('loading');
           },
           success: function (response) {
              jQuery('.oo-settings-notification').html(response).removeClass('loading').addClass('show');
           }
       } );

   } );
});
