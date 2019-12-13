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

var session = {'id':0,'screen':'','rel_id':-1,'admin_ses_id':0};
var interval;
var chat_interval;

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

function session_update()
{
  jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_update_session',
      session : session,
      _wpnonce: oometrics._nonce
    },
    beforeSend:function(){
      if(session.rel_id != -1){
        oo_rel_id = session.rel_id;
      }
      if(oo_rel_id != -1){
        session.rel_id = oo_rel_id;
      }
    },
    success:function(data){
      if(data.chat_badge != ''){
        jQuery('#oo-chat-trigger .oo-badge').html(data.chat_badge).addClass('show');
      }
      if(oo_rel_id == -1 && data.rel_id != -1){
        // session.rel_id = data.rel_id;
        jQuery('#oometrics-chat').addClass('opened');
        chat_update();
      }
      oo_rel_id = data.rel_id;
      if(data.popup != 'none'){
        content = data.popup;
        content = content.replace(/\\/g, "");
        jQuery('body').append(content);
        setTimeout(function(){
          jQuery('#oo-popup-wrapper:not(.consent)').addClass('show');
        },2000);
      }
    }
  });
}

// check for tab change
active_tab(function(){
if(active_tab())
{
  if (!interval)
  {
    interval = setInterval(function(){
          session_update();
    }, oometrics.session_interval);
  }
  if (!chat_interval)
  {
    chat_interval = setInterval(function(){
          chat_update();
    }, oometrics.chat_interval);
  }
}
else
{
  clearInterval(interval);
  clearInterval(chat_interval);
  interval = 0;
  chat_interval = 0;
}
});

// check for window change
jQuery(window).focus(function(){
  if (!interval)
  {
    interval = setInterval(function(){
          session_update();
    }, oometrics.session_interval);
  }
  if (!chat_interval)
  {
    chat_interval = setInterval(function(){
          chat_update();
    }, oometrics.chat_interval);
  }
});
jQuery(window).blur(function(){
  clearInterval(interval);
  interval = 0;
  clearInterval(chat_interval);
  chat_interval = 0;
});


jQuery( document ).ready( function ($) {

  session.rel_id = $('#oo_chat_rel_id').val();
  session.admin_ses_id = $('#oo_admin_ses_id').val();


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

  session.id = $('#oo_ses_id').val();
  // wait for 3 seconds to start
  setTimeout(function(){
    session['screen'] = jQuery(window).width() + 'x' + jQuery(window).height();
    // run interval
    if (!interval)
    {
      interval = setInterval(function(){
            session_update();
      }, oometrics.session_interval);
    }

    if (!chat_interval)
    {
      chat_interval = setInterval(function(){
            chat_update();
      }, oometrics.chat_interval);
    }
  },oometrics.delay);

  $(document).delegate('#oo-show-register','click', function( event ) {
    $('.oo-popup-login').removeClass('active');
    $('.oo-popup-register').addClass('active');
  });
  $(document).delegate('#oo-show-login','click', function( event ) {
    $('.oo-popup-register').removeClass('active');
    $('.oo-popup-login').addClass('active');
  });
  $(document).delegate('#oo-popup-wrapper .oo-popup-close','click',function(){
    $(this).parents('#oo-popup-wrapper').removeClass('show');
  });
  // $(document).delegate('#oo-popup-wrapper .oo-overlay','click',function(){
  //   $('#oo-popup-wrapper').removeClass('show');
  // });

  $(document).delegate('#oo-popup-wrapper .oo-inner a,#oo-popup-wrapper .oo-inner button','click',function(){
    var push_id = $('#oo-popup-wrapper').attr('data-pushid');
    jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_push_clicked',
        push_id : push_id,
        _wpnonce: oometrics._nonce
      }
    });

  });

} );
