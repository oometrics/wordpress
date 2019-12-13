var chat_interval;
var oo_rel_id = -1;


var chat_s_height = 0;
var chat_height = 0;

function mark_as_seen(elm,chat_id){
  jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_mark_as_seen',
      chat_id : chat_id,
      _wpnonce: oometrics._nonce
    },
    beforeSend:function(){
      elm.addClass('oo-loading');
    },
    success:function(data){
      if(data.status == 1){
        jQuery('.oo-chat-list li[data-chatid="'+chat_id+'"]').html(jQuery(data.bubble).html());
        elm.removeClass('oo-loading');
        elm.removeClass('delivered');
        elm.addClass('seen');
      }

    }
  });
}

function chat_update()
{
  if(oo_rel_id != -1){
    jQuery.ajax({
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
        if(new_count > current_count){
          jQuery('.oo-chat-conversations').append('<button id="go-to-new"></button>');
          if(jQuery('.oo-chat-conversations').length>0){
            chat_s_height = jQuery('.oo-chat-list').height();
            chat_height = jQuery('.oo-chat-conversations').height();
          }
        }
        // console.log(chat_s_height);
        // console.log(chat_height);
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

      }
    });
  }
}

jQuery(document).ready( function ($) {

  if(jQuery('.oo-chat-conversations').length>0){
    chat_s_height = jQuery('.oo-chat-conversations').get(0).clientHeight;
    chat_height = jQuery('.oo-chat-conversations').get(0).scrollHeight;
  }

  //frontend
  $(document).delegate('#oo-chat-trigger','click',function(e){
    e.preventDefault();
    $('#oometrics-chat').toggleClass('opened');
    var img = $(this).find('img')
    var src = img.attr('src');
    if($('#oometrics-chat').hasClass('opened')){
      src = src.replace('start-chat.svg','stop-chat.svg');
    } else{
      src = src.replace('stop-chat.svg','start-chat.svg');
    }
    img.attr('src',src);
    $('#oo-message-text').focus();
  });

  $(document).delegate('#oo-send-message','click',function(e){
    e.preventDefault();
    var t = $(this);
    var message = $('#oo-message-text').val();

    if(typeof message === 'undefined' || message == ''){
      return false;
    }

    var chat_id = t.attr('data-chatid');
    if($(this).hasClass('edit')){
      jQuery.ajax({
        url: oometrics.ajaxurl,
        type:'post',
        data:{
          action:'oo_edit_chat',
          chat_id : chat_id,
          message: message,
          _wpnonce: oometrics._nonce
        },
        beforeSend:function(){
          $('#oo-message-text').blur();
        },
        success:function(data){
          $('#oo-message-text').val('');
          t.removeClass('edit');
          t.removeAttr('data-chatid');
          $('.oo-chat-list li[data-chatid="'+chat_id+'"]').html($(data.bubble).html());
          // $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
        }
      });
    } else {
      jQuery.ajax({
        url: oometrics.ajaxurl,
        type:'post',
        data:{
          action:'oo_send_message',
          rel_id : oo_rel_id,
          message:message,
          _wpnonce: oometrics._nonce
        },
        beforeSend:function(){
          $('#oo-message-text').blur();
        },
        success:function(data){
          oo_rel_id = data.rel_id;
          $('#oo-message-text').val('');
          // $('#oo_chat_rel_id').val(data.rel_id);
          $('.oo-chat-list').append(data.bubble);
          $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
        }
      });
    }

  });

  $('#oo-message-text').keydown(function (e){
    if(e.keyCode == 13){
        $('#oo-send-message').click();
    }
})

  $(document).delegate('.oo-session-profile','click',function(e){
    e.preventDefault();
    var t = $(this);
    oo_rel_id = t.attr('data-relid');
    // $('#oo_chat_rel_id').val(oo_rel_id);
    jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_get_session_chats',
        rel_id : oo_rel_id,
        _wpnonce: oometrics._nonce
      },
      success:function(data){
        $('.oo-chat-list').html(data.chats);
        $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
      }
    });
  });

  $('.oo-chat-conversations').scroll(function(){
    var stop = $(this).scrollTop() + chat_height;
    $('.oo-chat-list li.oo-one:not(.seen):not(.oo-loading)').each(function(i,v){
      var elm = $(this);
      if(stop > elm.position().top){
        $('#go-to-new').remove();
      }
      if(stop > elm.position().top){
        var chat_id = elm.attr('data-chatid');
        jQuery.ajax({
          url: oometrics.ajaxurl,
          type:'post',
          data:{
            action:'oo_mark_as_seen',
            chat_id : chat_id,
            _wpnonce: oometrics._nonce
          },
          beforeSend:function(){
            elm.addClass('oo-loading');
          },
          success:function(data){
            if(data.status == 1){
              $('.oo-chat-list li[data-chatid="'+chat_id+'"]').html($(data.bubble).html());
              elm.removeClass('oo-loading');
              elm.removeClass('delivered');
              elm.addClass('seen');
            }

          }
        });
      }
    });
  });

  $(document).delegate('.oo-chat-action .delete','click',function(e){
    e.preventDefault();
    var chat_id = $(this).attr('data-chatid');
    jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_delete_chat',
        chat_id : chat_id,
        _wpnonce: oometrics._nonce
      },
      success:function(data){
        if(data.status == '1' || data.status == 1){
          $('.oo-chat-list li[data-chatid="'+chat_id+'"]').remove();
        }

      }
    });
  });

  $(document).delegate('.oo-chat-action .edit','click',function(e){
    e.preventDefault();
    var chat_id = $(this).attr('data-chatid');
    var content = $('.oo-chat-list li[data-chatid="'+chat_id+'"]').find('.oo-chat-content').text();
    $('#oo-message-text').val(content.trim());
    $('#oo-send-message').addClass('edit');
    $('#oo-send-message').attr('data-chatid',chat_id);
  });

  $(document).delegate('#go-to-new','click',function(e){
    e.preventDefault();

    var v = $('.oo-chat-list').height();
    $('.oo-chat-conversations').scrollTop(v);
    $(this).remove();
  });

  // chat_interval = setInterval(function(){
  //       chat_update();
  // }, 5000);
  //frontend - end



} );
