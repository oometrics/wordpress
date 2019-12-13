var current_ses_id = -1;
var session = {'id':0,'screen':''};
var interval;
var oo_rel_id = -1;
var editor_status = 0;

var update_session_x;
var update_chat_x;
var live_sessions_x;

var chat_s_height = 0;
var chat_height = 0;

function oo_set_cookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function oo_get_cookie(cname) {
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

var delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

var active_tab = (function(){
    var stateKey, eventKey, keys = {
        hidden: "visibilitychange",
        webkitHidden: "webkitvisibilitychange",
        mozHidden: "mozvisibilitychange",
        msHidden: "msvisibilitychange"
    };
    for (stateKey in keys) {
        if (stateKey in document) {
            eventKey = keys[stateKey];
            break;
        }
    }
    return function(c) {
        if (c) document.addEventListener(eventKey, c);
        return !document[stateKey];
    }
})();

// function session_update()
// {
//   jQuery.ajax({
//     url: oometrics.ajaxurl,
//     type:'post',
//     data:{
//       action:'oo_update_session',
//       session : session,
//       _wpnonce: oometrics._nonce
//     }
//   });
// }

// check for tab change
active_tab(function(){
if(active_tab())
{
  if (!interval)
  {
    interval = setInterval(function(){
          session_update();
          //chat_update();
    }, oometrics.interval);
  }
}
else
{
  clearInterval(interval);
  interval = 0;
}
});

// check for window change
jQuery(window).focus(function(){
  if (!interval)
  {
    interval = setInterval(function(){
          session_update();
          //chat_update();
    }, oometrics.interval);
  }
});
jQuery(window).blur(function(){
  clearInterval(interval);
  interval = 0;
});


function session_update()
{
  if(current_ses_id != -1 ){
    get_session(current_ses_id);
  } else{
    var ses_id = jQuery('#oo_ses_id').val(); //edited var added
    if(ses_id != -1 && ses_id != '-1'){
      get_session(ses_id);
    }
  }
  // // update admin_session
  // jQuery.ajax({
  //   url: oometrics.ajaxurl,
  //   type:'post',
  //   data:{
  //     action:'admin_update_session',
  //     session : session,
  //     _wpnonce: oometrics._nonce
  //   }
  // });

}
function chat_update()
{
  // var if_sessions = jQuery('.oo-chat-list .oo-session-profile').length;
  if(oo_rel_id != -1){
    update_chat_x = jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_update_chat',
        rel_id : oo_rel_id,
        _wpnonce: oometrics._nonce
      },
      success:function(data){
        var current_count = jQuery('.oo-chat-list li').length;
        var new_count = data.total;
        jQuery('.oo-chat-list').html(data.chats);
        if(chat_s_height == chat_height){
          jQuery('.oo-chat-conversations .oo-chat-list li:not(.seen):not(.oo-two)').each(function(i,v){
              var elm = jQuery(this);
              if(stop > elm.offset().top){
                $('#go-to-new').remove();
              }
              var chat_id = elm.attr('data-chatid');
              mark_as_seen(elm,chat_id);
          });
        }
        if(new_count > current_count){
          jQuery('.oo-dashboard-left-left-body').append('<button id="go-to-new"></button>');
        }
      }
    });
  }
}


  // wait for 3 seconds to start
  function get_sessions()
  {
    live_sessions_x = jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'get_live_sessions',
        _wpnonce: oometrics._nonce
      },
      beforeSend:function(){
        jQuery('.oo-session-list').addClass('oo-loading');
      },
      success:function(data)
      {
        jQuery('.oo-session-list').removeClass('oo-loading');
        jQuery('.oo-session-list').html(data.content);
        jQuery('.oo-dashboard-sidebar-body li[data-sesid="'+current_ses_id+'"]').addClass('active');
        var overview = data.overview;
        jQuery('.oo-total-sales strong').html(overview.total_sales);
        jQuery('.oo-total-online strong').html(overview.online_users);
        jQuery('.oo-total-users strong').html(overview.unique_users);
        jQuery('.oo-total-views strong').html(overview.pageviews);

      }
    });
  }

  setTimeout(function(){
      // run interval
    setInterval(function(){
      get_sessions();
    }, oometrics.interval);

  },1000);

  get_sessions();

  function get_session(ses_id){
    var ses_data;
    update_session_x = jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'get_session',
        ses_id: ses_id,
        rel_id:oo_rel_id,
        _wpnonce: oometrics._nonce
      },
      success:function(data)
      {
        // oo_rel_id = -1;
        jQuery('#oo_ses_id').val(ses_id);
        current_ses_id = ses_id;
        update_session_ui(data);

      }
    });
    return ses_data;
  }


  function update_session_ui(data)
  {
    var session_data = data.session;
    var info_html = data.info;
    var activity_html = data.activity;
    var cart_html = data.cart.cart_items_html;
    // var overview = data.overview;
    var profile = data.profile;
    if(data.rels == 'empty'){
      var chat_html = data.chats.html;
    } else {
      var chat_html = data.rels;
    }


    if(editor_status == 0){
      if(data.rels == '' && data.chats == 'empty'){
        jQuery('.oo-dashboard-reply').addClass('block');
        jQuery('.oo-dashboard-reply').addClass('hide');
      } else if(data.rels != '' && oo_rel_id == -1 && data.chats == 'empty'){
        jQuery('.oo-dashboard-reply').addClass('block');
        jQuery('.oo-dashboard-reply').removeClass('hide');
      }
    }

    jQuery('.session-value strong').html(session_data.ses_value);
    jQuery('.device-type strong').html(session_data.ses_device);
    jQuery('.device-brand strong').html(session_data.ses_device_brand);
    jQuery('.device-browser strong').html(session_data.ses_browser);
    jQuery('.device-resolution strong').html(session_data.ses_resolution);
    jQuery('.connection-ip strong').html(session_data.ses_ip);
    jQuery('.connection-referrer strong').html(session_data.ses_referrer);
    jQuery('.server-variable div').html('<pre>'+data.debug+'</pre>');

    jQuery('.profile-info .name').html(profile.display_name);
    jQuery('.profile-info .email').html(profile.user_email);
    jQuery('.profile-info .state').html(profile.shipping_state);
    jQuery('.profile-info .city').html(profile.shipping_city);
    jQuery('.profile-info .oo-call').attr('href','tel:'+profile.billing_phone);
    if(jQuery(profile.socials == '')){
      jQuery('.profile-info .social').html('');
      jQuery('.profile-info .social').hide();
    } else {
      jQuery('.profile-info .social').html(profile.socials);
      jQuery('.profile-info .social').show();
    }

    // cart
    jQuery('.location .state').html(profile.shipping_state);
    jQuery('.location .city').html(profile.shipping_city);
    jQuery('.oo-cart-items').html(data.cart.cart_items);
    jQuery('.oo-cart-total').html(data.cart.cart_total);
    jQuery('.oo-cart-overlay .oo-search-selected').html('');
    jQuery('.oo-cart-overlay .oo-current-cart-items').html('');
    jQuery('.oo-cart-overlay .oo-current-cart-items').html(cart_html);
    jQuery('.oo-purchased-items').html(data.cart.purchased_items);
    jQuery('.oo-purchased-total').html(data.cart.purchased_total);


    // jQuery('.oo-tab-content').html(info_html);
    if(oo_rel_id == -1){
      jQuery('.oo-chat-list').html(chat_html);
      jQuery('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
    }
    chat_update();
    if(oo_get_cookie('oo_tracking_consent') == 'disagreed'){
      activity_html = 'Said NO to tracking';
    }
    jQuery('#customer-activities .oo-info-details').html(activity_html);
  }

jQuery(document).ready( function ($) {

  setTimeout(function(){
    // run interval
    if (!interval)
    {
      interval = setInterval(function(){
            session_update();
            // chat_update();
      }, oometrics.interval);
    }
  },oometrics.delay);




  if(jQuery('.oo-chat-conversations').length > 0){
    chat_s_height = jQuery('.oo-chat-conversations').get(0).clientHeight;
    chat_height = jQuery('.oo-chat-conversations').get(0).scrollHeight;
  }

  $(document).delegate('.oo-product-search','keyup',function(e){
		var t = $(this);
			var query = $(this).val();
			if(query.length >= 3){
				delay(function(){
					$.ajax({
						url: oometrics.ajaxurl,
						type:'post',
						data:{
							'action': 'oo_product_search',
							'query': query,
							'_wpnonce': oometrics._nonce
						},
						// beforeSend:function(){
						// 	$('.suggestion-result').addClass('loading');
						// },
						success:function(data){
							if(data.suggestions != ''){
								t.parents('.oo-search-field').find('.oo-search-results').addClass('show');
								t.parents('.oo-search-field').find('.oo-search-results').html(data.suggestions);
							}

						}
					});
				},500);
			}
		});

    $(document).delegate('.oo-search-result-item','click',function(e){
  		var t = $(this);
  			var id = t.attr('data-pid');
  			var vid = t.attr('data-vid');
  			var kid = t.attr('data-kid');
  			var qty = t.attr('data-qty');
        if(typeof kid === 'undefined') kid = 0;
        $('#oo-product-id').val(id);
        var html = t.html();
        html = '<div data-pid="'+id+'" data-vid="'+vid+'" data-key="'+kid+'" data-qty="'+qty+'" class="oo-search-result-item"><span class="oo-remove-selected">x</span><input type="number" class="oo-quantity" value="1"/>'+html+'</div>';
        t.parents('.oo-search-field').find('.oo-search-selected').append(html);
        t.parents('.oo-search-field').find('.oo-search-results').removeClass('show')

  		});
    $(document).delegate('.oo-remove-selected','click',function(e){
  		  var t = $(this);
  			var p = t.parent();
        $('#oo-product-id').val(0);
        p.remove();

  		});
    $(document).delegate('.oo-quantity','click',function(e){
  		  var t = $(this);
  			var p = t.parent();
        var val = t.val();
        p.attr('data-qty',val);
  		});

      $(document).delegate('#oo_change_cart','click',function(e){
  		  var t = $(this);

        var pid_str = '';
        var vid_str = '';
        var key_str = '';
        var qty_str = '';
        $('.oo-cart-overlay .oo-current-cart-items .oo-search-result-item,.oo-cart-overlay .oo-search-selected .oo-search-result-item').each(function(i,v){
           pid_str += $(this).attr('data-pid')+',';
           vid_str += $(this).attr('data-vid')+',';
           key_str += $(this).attr('data-key')+',';
           qty_str += $(this).attr('data-qty')+',';
        });
        jQuery.ajax( {
            url: oometrics.ajaxurl,
            type: 'post',
            data: {
              action:'oo_change_cart',
              pid_str:pid_str,
              ses_id:current_ses_id,
              vid_str:vid_str,
              key_str:key_str,
              qty_str:qty_str,
              _wpnonce:oometrics._none
            },
            success: function (response) {
               jQuery('.oo-overlay-cart .oo-notification').html(response).addClass('show');
               setTimeout(function(){
                 $('.oo-add-tocart-remotely').click();
               },1000)
            }
        } );

  		});
    $(document).delegate('#oo-open-push-to-session','click',function(e){
      e.preventDefault();
  		  $('.oo-push-overlay').removeClass('hide');
  		});
    $(document).delegate('.oo-add-tocart-remotely','click',function(e){
      e.preventDefault();
  		  $('.oo-cart-overlay').toggleClass('hide');
  		});

      $(document).delegate('#oo-close-send-the-push','click',function(e){
        e.preventDefault();
        $('.oo-push-overlay').addClass('hide');
        $('#oo-send-the-push').removeClass('yes button-primary');
        $('#oo-send-the-push').html('Push to the session');

      });
      $(document).delegate('.oo-push-delete','click',function(e){
        e.preventDefault();
        var pushid = $(this).attr('data-pushid');
        jQuery.ajax( {
            url: oometrics.ajaxurl,
            type: 'post',
            data: {
              action:'oo_delete_push',
              push_id: pushid,
              _wpnonce:oometrics._none
            },
            success: function (response) {
               $('#oo-push-item-'+pushid).remove();
            }
        } );

      });
      $(document).delegate('#oo_popup_type','click',function(e){
        e.preventDefault();
        var popup_type = $(this).val();
        if(popup_type == 'promotional'){
          $('#wp-oo-popup-text-wrap').show();
          $('.oo-popup-actions').show();
        } else{
          $('#wp-oo-popup-text-wrap').hide();
          $('.oo-popup-actions').hide();
        }
      });
      $(document).delegate('#oo-send-the-push','click',function(e){
        e.preventDefault();
        var t = $(this);
        var data;
        var push_duration = $('#oo_push_duration').val();
        var push_type = $('#oo-choose-push').val();
        if(push_type == ''){
          $('#oo-choose-push').addClass('danger');
          return false;
        } else {
          $('#oo-choose-push').removeClass('danger');
        }
        if(push_duration == ''){
          $('#oo_push_duration').addClass('danger');
          return false;
        } else {
          $('#oo_push_duration').removeClass('danger');
        }
        var pid_str = '';
        if(push_type == 'sale_price'){
          if($('#oo_product_search').val() == ''){
            $('#oo_product_search').addClass('danger');
            return false;
          } else {
            $('#oo_product_search').removeClass('danger');
          }
          $('.oo-search-selected .oo-search-result-item').each(function(i,v){
             pid_str += $(this).attr('data-pid')+',';
          });
          var sale_amount = $('#oo_sale_amount').val();
          var sale_percent = $('#oo_sale_percent').val();
          if(sale_amount == '' && sale_percent == ''){
            if(sale_amount == ''){
              $('#oo_sale_amount').addClass('danger');
              return false;
            } else {
              $('#oo_sale_amount').removeClass('danger');
            }
            if(sale_percent == ''){
              $('#oo_sale_percent').addClass('danger');
              return false;
            } else {
              $('#oo_sale_percent').removeClass('danger');
            }

          } else {
            $('#oo_sale_percent').removeClass('danger');
            $('#oo_sale_amount').removeClass('danger');
          }
          data = {
            action:'oo_send_push',
            push_type:push_type,
            push_duration:push_duration,
            ses_id:current_ses_id,
            pid_str:pid_str,
            sale_amount:sale_amount,
            sale_percent:sale_percent,
            _wpnonce:oometrics._nonce
          }
        } else if(push_type == 'apply_coupon'){
          var push_coupons = $('#oo-coupons').val();
          if(push_coupons == ''){
            $('#oo-coupons').addClass('danger');
            return false;
          } else {
            $('#oo-coupons').removeClass('danger');
          }
          data = {
            action:'oo_send_push',
            push_type:push_type,
            push_duration:push_duration,
            ses_id:current_ses_id,
            push_coupons:push_coupons,
            _wpnonce:oometrics._nonce
          }
        } else if(push_type == 'open_popup'){
          var popup_type = $('#oo_popup_type').val();
          var oo_popup_btn_1_label = $('#oo_popup_btn_1_label').val();
          var oo_popup_btn_2_label = $('#oo_popup_btn_2_label').val();
          var oo_popup_btn_1_href = $('#oo_popup_btn_1_href').val();
          var oo_popup_btn_2_href = $('#oo_popup_btn_2_href').val();
          var content = tinymce.activeEditor.getContent();
          data = {
            action:'oo_send_push',
            push_type:push_type,
            ses_id:current_ses_id,
            push_duration:push_duration,
            popup_type:popup_type,
            oo_popup_btn_1_label :oo_popup_btn_1_label,
            oo_popup_btn_2_label : oo_popup_btn_2_label,
            oo_popup_btn_1_href : oo_popup_btn_1_href,
            oo_popup_btn_2_href : oo_popup_btn_2_href,
            popup_content:content,
            _wpnonce:oometrics._nonce
          }
        }


        if(t.hasClass('yes')){

          jQuery.ajax( {
              url: oometrics.ajaxurl,
              type: 'post',
              data: data,
              beforeSend:function(){
                t.html('Sending ...');
              },
              success: function (response) {
                 if(response.status == 1){
                   $('.oo-push-overlay').addClass('hide');
                   $('#oo-choose-push').val('');
                   $('.oo-push-option').removeClass('active');
                   t.toggleClass('yes button-primary');
                     t.html('Push to the session');
                 }
              }
          } );
        } else {
          t.toggleClass('yes button-primary');
          t.html('Really sure? click for yes');
        }
    		});

  $(document).delegate('.oo-upload-media','click', function( event ) {
    var t = $(this);
    var chat_id = t.attr('data-chatid');
    $('#oo-chat-upload-'+chat_id).click();
  });

  $(document).delegate('.oo-chat-upload-input','change', function( event ) {
    var t = $(this);
    var chat_id = t.attr('data-chatid');
    var input_id = $('#oo-chat-upload-'+chat_id)[0];
    var data = new FormData();
    var file = event.target.files;
		// var parent = $("#" + event.target.id).parent();
		$.each(file, function(key, value)
			{
  			data.append("chat_file", value);
			});
    data.append('action', 'oo_chat_add_attachment');
    // data.append('chat_file', input_id);
    data.append('chat_id', chat_id);
    data.append('_wpnonce', oometrics._nonce);
    jQuery.ajax({
      url: oometrics.ajaxurl,
      method:'post',
      type:'post',
      processData: false,
      // cache: false,
      contentType: false, //'multipart/form-data; charset=utf-8; boundary=' + Math.random().toString().substr(2),
      data:data,
      success:function(data){
        t.parents('li[data-chatid="'+chat_id+'"]').find('.oo-chat-attachments').html(data.html);
      }
    });

  });

  $(document).delegate('.oo-session-list li','click',function(){
    editor_status = 0;
    var t = $(this);
    $('.oo-session-list li').removeClass('active');
    t.addClass('active');
    var ses_id = t.attr('data-sesid');
    oo_rel_id = -1;
    // if(typeof update_chat_x !== 'undefined') update_chat_x.abort();
    // if(typeof update_session_x !== 'undefined')  update_session_x.abort();
    // clearInterval(interval);
    // $('.oo-dashboard-reply').addClass('block');
    get_session(ses_id);
    jQuery('.oo-tab').removeClass('active');
    jQuery('#customer-activities').addClass('active');
  });

  $(document).delegate('.refresh-now','click',function(e){
    e.preventDefault();
    get_sessions();
  });






  $(document).delegate('.oo-info-nav li a','click',function(e){
    e.preventDefault();
    if(!$('#tab-default').hasClass('active')){
      var t = $(this);
      $('.oo-info-nav li').removeClass('active');
      $('.oo-cart-overlay').addClass('hide');
      t.parent().addClass('active');
      var id = t.attr('href');
      $('.oo-tab').removeClass('active');
      $(id).addClass('active');
    }

  });


  $(document).delegate('.start-new-chat,.start-new-conv','click',function(e){
    e.preventDefault();
    var t = $(this);
    $('.oo-dashboard-reply').removeClass('block hide');
    editor_status = 1;
    $('#oo-message-text').focus();
  });


  $(document).delegate('#oo-choose-push','change',function(e){
    e.preventDefault();
    var val = $(this).val();
    $('.oo-push-option').removeClass('active');
    $('#'+val).addClass('active');
  });


  jQuery( document ).on( 'click', '.oo-session-list-nav a', function ( e ) {

       e.preventDefault();
       var t = $(this);
       var order_by = t.attr('data-orderby');
       $('.oo-session-list-nav li').removeClass('active');
       t.parent().addClass('active');
       // We make our call
       jQuery.ajax( {
           url: oometrics.ajaxurl,
           type: 'post',
           data: {
             action: 'oo_set_global_order_by',
             orderby: order_by,
             _wpnonce:oometrics._nonce
           },
           success: function (response) {
              if(response.status == 1){
                get_sessions();
              }
           }
       } );

   } );

} );
