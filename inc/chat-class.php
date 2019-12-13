<?php
/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOChat
{

	/**
	 * The security nonce
	 *
	 * @var string
	 */
	private $_nonce = 'oometrics_adminnonce';
	private $option_name = 'oometrics_options';
	public $table;
	public $chat_id;
	public $chat_sender_id;
	public $chat_receiver_id;
	public $chat_receiver_ses_id;
	public $chat_sender_ses_id;
	public $chat_ses_id;
	public $rel_id;
	public $chat_content;
	public $chat_content_before;
	public $chat_attachments;
	public $chat_status;
	public $chat_edited;
	public $chat_date;


	public function __construct()
  {
		// dev user as defaut
		$settings = get_option($this->option_name);
		if(get_current_user_id() != $settings['main_user']){
			$this->chat_receiver_id = $settings['main_user'];
		}
		$this->chat_status = 0;
		$this->chat_attachments = '0:{}';
	}



	public function init()
	{

	}

	public function has_active_conv()
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats_rel';
		$ctable = $wpdb->prefix.'oometrics_chats';
		$stable = $wpdb->prefix.'oometrics_sessions';

		$in_db = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT crel_id FROM $table as rels
						 INNER JOIN $stable sessions ON chats.chat_ses_id = sessions.ses_id
						 INNER JOIN $ctable chats ON rels.crel_sender_ses_id = chats.chat_ses_id OR rels.crel_receiver_ses_id = chats.chat_ses_id
						 WHERE sessions.ses_expired = '%d'
						 GROUP BY rels.crel_id",
		         array(0)
		    )
		);
		if($in_db > 0)
		{
			return true;
		}
		return false;
	}

	public function get_active_conv()
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats_rel';
		$ctable = $wpdb->prefix.'oometrics_chats';
		$stable = $wpdb->prefix.'oometrics_sessions';

		$in_db = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT crel_id FROM $table as rels
						 INNER JOIN $ctable chats ON rels.crel_sender_ses_id = chats.chat_ses_id OR rels.crel_receiver_ses_id = chats.chat_ses_id
						 INNER JOIN $stable sessions ON chats.chat_ses_id = sessions.ses_id
						 WHERE sessions.ses_expired = '%d'
						 GROUP BY rels.crel_id",
		         array(0)
		    )
		);

		if($in_db > 0)
		{
			return $in_db;
		}
		return false;
	}

	public function get_rel_by_id($crel_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats_rel';

		$crel = $wpdb->get_row(
		    $wpdb->prepare(
		        "SELECT * FROM $table
		         WHERE crel_id = '%d'",
		         $crel_id
		    )
		);

		return $crel;
	}
	public function get_active_rel_by_ses_id($sender_ses_id,$receiver_ses_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats_rel';
		$stable = $wpdb->prefix.'oometrics_sessions';

		$crel = $wpdb->get_row(
		    $wpdb->prepare(
		        "SELECT * FROM $table as rels
						 INNER JOIN $stable as sessions ON rels.crel_sender_ses_id = sessions.ses_id OR rels.crel_receiver_ses_id = sessions.ses_id
		         WHERE ((rels.crel_sender_ses_id = '%d' AND rels.crel_receiver_ses_id = '%d') OR (rels.crel_sender_ses_id = '%d' AND rels.crel_receiver_ses_id = '%d')) AND sessions.ses_expired = '0'",
		         array($sender_ses_id,$receiver_ses_id,$receiver_ses_id,$sender_ses_id)
		    )
		);

		return $crel;
	}
	public function add_conversation()
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats_rel';

		$ses_obj = new OOSession();
		$receiver_ses = $ses_obj->get_by('ses_id',$this->chat_receiver_ses_id,array('ses_expired'=>'true'));
		// $receiver_session = $ses_obj->get($receiver_ses->ses_id);
		// $receiver_session = $ses->get($receiver_ses->ses_id);

		$data['crel_sender_ses_id'] = $this->chat_ses_id;
		$data['crel_receiver_ses_id'] = $receiver_ses->ses_id;
		// $data['crel_receiver_id'] = $receiver_ses->ses_uid;
		$data['crel_date'] = time();
		$result = $wpdb->insert($table,$data);
		if($result){
			$ses_obj->add_value(2);
			return $wpdb->insert_id;
		} else{
			return false;
		}

  }
	public function add_chat($rel_id,$args)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';


		$data['chat_sender_id'] = $this->chat_sender_id;
		$data['chat_receiver_id'] = $this->chat_receiver_id;
		$data['chat_ses_id'] = $this->chat_ses_id;
		$data['chat_content'] = $args['chat_content'];
		$data['chat_content_before'] = '';
		$data['chat_attachments'] = $this->chat_attachments;
		$data['chat_edited'] = 0;
		$data['chat_rel_id'] = $this->chat_rel_id;
		$data['chat_status'] = $this->chat_status;
		$data['chat_date'] = time();
		$result = $wpdb->insert($table,$data);
		if($result){
			$sent = $wpdb->get_var(
					$wpdb->prepare(
							"UPDATE $table SET chat_status = '1' WHERE chat_id = '%d'",
							 // array($this->chat_sender_id,$this->chat_sender_id,$this->chat_receiver_id,$this->chat_receiver_id)
							 array($wpdb->insert_id)
					)
			);
			return $wpdb->insert_id;
		} else{
			return false;
		}

  }
	public function send_message($args)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';

		$rel_id = (!empty($args['rel_id'])) ? (int)$args['rel_id'] : -1;
		$ses_id = (!empty($args['ses_id'])) ? (int)$args['ses_id'] : 0;

			$ses = new OOSession();
			$session = $ses->get();
			// print_r($session);
			$this->chat_ses_id = $session->ses_id;
			$this->chat_sender_ses_id = $session->ses_id;
			if(!empty($session->ses_uid)){
				$this->chat_sender_id = $session->ses_uid;
			} else {
				$this->chat_sender_id = 0;
			}

		// when there is no rel available and some one will start and pop the chat
		if($rel_id == -1){
			// when a session selected but there is no conversation(chat relation)
			if($ses_id > 0){
				$this->chat_receiver_ses_id = $ses_id;
				$crel = $this->get_active_rel_by_ses_id($this->chat_receiver_ses_id,$this->chat_sender_ses_id);
				$rel_id = $crel->crel_id;
				if(empty($crel)){
					$rel_id = $this->add_conversation();
					$crel = $this->get_rel_by_id($rel_id);
				}
				// when there is no session selecte or conversation opened
				// so get the admin live session
			} else{
				// get admin live session
				$admin_session = $ses->get_by('ses_uid',$this->chat_receiver_id,array('ses_expired'=>'true'));
				if($admin_session){
					$this->chat_receiver_ses_id = $admin_session->ses_id;
				} else {
					$this->chat_receiver_ses_id = 0;
				}
				$crel = $this->get_active_rel_by_ses_id($this->chat_receiver_ses_id,$this->chat_sender_ses_id);
				$rel_id = $crel->crel_id;
				if(empty($crel)){
					$rel_id = $this->add_conversation();
					$crel = $this->get_rel_by_id($rel_id);
				}
			}
		}else if($rel_id > 0){
			$crel = $this->get_rel_by_id($rel_id);
			$crel = $this->get_active_rel_by_ses_id($crel->crel_receiver_ses_id,$crel->crel_sender_ses_id);
			if(empty($crel)){
				$rel_id = $this->add_conversation();
				$crel = $this->get_rel_by_id($rel_id);
			}
		}

		$this->chat_rel_id = $rel_id;


		if($this->chat_ses_id == $crel->crel_sender_ses_id){
			$receiver_ses_id = $crel->crel_receiver_ses_id;
		} else {
			$receiver_ses_id = $crel->crel_sender_ses_id;
		}


		$rses_data = $ses->get_by('ses_id',$receiver_ses_id);
		// $session = $ses->get($rses_data->ses_id);
		$this->chat_receiver_id = $rses_data->ses_uid;


		// $data['chat_rel_id'] = $args['chat_content'];
		$data['chat_content'] = $args['chat_content'];
		$data['chat_ses_id'] = $this->chat_ses_id;

		$new_chat_id = $this->add_chat($rel_id,$data);
		return array('rel_id'=>$rel_id,'chat_id'=>$new_chat_id);
	}
	public function remove_chat($args)
  {

  }
	public function update_chat($args)
  {

  }
	public function get_conversations($html = false,$args = array())
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats_rel';
		$ctable = $wpdb->prefix.'oometrics_chats';

		$ses_id = (isset($args['ses_id']) && !empty($args['ses_id'])) ? $args['ses_id'] : 0;

		if(!empty($ses_id)){
			$ses = new OOSession();
			$session_data = $ses->get_by('ses_id',$ses_id);

			if($session_data->ses_uid != 0){
				$rels = $wpdb->get_results(
					$wpdb->prepare(
							"SELECT crel_id FROM $ctable as chats
							INNER JOIN $table as rels ON chats.chat_rel_id = rels.crel_id
							WHERE chats.chat_sender_id = '%d' OR chats.chat_receiver_id = '%d'
							GROUP BY crel_id
							ORDER BY chats.chat_date DESC",
							 array($session_data->ses_uid,$session_data->ses_uid)
					)
				);

			} else {
				$rels = $wpdb->get_results(
				    $wpdb->prepare(
				        "SELECT crel_id FROM $table WHERE crel_sender_ses_id = '%d' OR crel_receiver_ses_id = '%d'",
				         array($ses_id,$ses_id)
				    )
				);
			}

		}

		if(!$html){
			return $rels;
		} else{
			$html = '';
			foreach ($rels as $key => $rel) {
				// $session_data = $session_obj->ses_get_by('ses_id',$session->chat_ses_id);
				$html .= $this->render_rels($rel->crel_id,true);
			}
			return $html;
		}
	}
	public function render_rels($rel_id,$html = false)
	{
		$crel = $this->get_rel_by_id($rel_id);
		if($this->chat_ses_id == $crel->crel_sender_ses_id){
			$receiver_ses_id = $crel->crel_receiver_ses_id;
		} else {
			$receiver_ses_id = $crel->crel_sender_ses_id;
		}

		$ses = new OOSession();
		$session_data = $ses->get_by('ses_id',$receiver_ses_id);
		if(!empty($session_data->ses_uid) && $session_data->ses_uid > 0)
		{
			$user = get_user_by('id',$session_data->ses_uid);
			$ses_name = $user->display_name;
			if(empty($ses_name))
			{
				$ses_name = $user->user_login;
			}
			$ses_name = '<small>'.__('Chat with:','oometrics').'</small> '.$ses_name;
			// Check for known referres


			$ses_avatar = get_avatar($session_data->ses_uid,40);
		}
		else
		{
			$ses_name = $session_data->ses_hash;
			$ses_avatar = '<img class="avatar" src="'.OOMETRICS_URL.'/assets/images/anon-avatar.svg" />';
		}


		$time = human_time_diff( $session_data->ses_last_act, time() );

		// $html = '';
		$html = '
		<li data-relid="'.$rel_id.'" class="oo-session-profile">
      '.$ses_avatar.'
      <div class="oo-session-info">
        <strong>'.$ses_name.'</strong>';
		$html .= '
        <em>'.$time.'</em>
      </div>
    </li>
		';
		return $html;
	}
	public function get_current_chats($html = false)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';

		if(!empty($this->chat_sender_id)){
			$sessions = $wpdb->get_results(
			    $wpdb->prepare(
			        "SELECT chat_ses_id FROM $table
			         WHERE `chat_sender_id` = '%d' OR `chat_receiver_id` = '%d'
							 GROUP BY `chat_ses_id`",
			         array($this->chat_sender_id,$this->chat_sender_id)
			    )
			);
		} else {
			$sessions = $wpdb->get_results(
			    $wpdb->prepare(
			        "SELECT chat_ses_id FROM $table
			         WHERE `chat_ses_id` = '%d'",
			         array($this->chat_ses_id)
			    )
			);
		}
		if(!$html){
			return $sessions;
		} else{
			$html = '';
			foreach ($sessions as $key => $session) {
				$session_obj = new OOSession();
				$session_data = $session_obj->get_by('ses_id',$session->chat_ses_id);
				$html .= $session_obj->render($session_data->ses_id,false);
			}
			return $html;
		}

  }
	public function render_chat($cid)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';

		$chat = $wpdb->get_row(
				$wpdb->prepare(
						"SELECT * FROM $table
						 WHERE chat_id = '%d'",
						 array($cid)
				)
		);
		$class  = "";

		$class = ($chat->chat_ses_id == $this->chat_ses_id) ? "two" : "one";
		// if(empty($class)){
		// 	$class = ($chat->chat_sender_id == $this->chat_sender_id) ? "two" : "one";
		// }
		// if(empty($class)){
		// 	$class = ($chat->chat_ses_id == $this->chat_ses_id) ? "two" : "one";
		// }
		$edited = ($chat->chat_edited == 1) ? '<span class="edited">'.__('Edited','oometrics').'</span>' : "";
		$status = $this->get_status_label($chat->chat_status,'html');
		$status_class = $this->get_status_label($chat->chat_status,'class');
		$chat_date = human_time_diff( $chat->chat_date, time() );

		$chat_attachments = $this->get_attachments($chat->chat_id);
		$attach_html = '';
		if(!empty($chat_attachments)){
			foreach ($chat_attachments as $key => $attach) {
				$attach_html .= $this->render_attachments($attach);
			}
		}

		$html = '
		<li data-chatid="'.$chat->chat_id.'" class="oo-'.$class.' '.$status_class.'">
			<div class="oo-chat-bubble">
				<div class="oo-chat-content">
					'.make_clickable(esc_html($chat->chat_content)).'
				</div>
				<div class="oo-chat-meta">
				'.$status.'
				'.$edited.'
				<em>'.$chat_date.'</em>
				</div>';
				if($class == 'two' || current_user_can('manage_options')){
					$html .='
					<div class="oo-chat-action">
						<span class="oo-icon edit" data-chatid="'.$chat->chat_id.'"></span>
						<span class="oo-icon delete" data-chatid="'.$chat->chat_id.'"></span>
					</div>';
				}
				$html.='</div>
								<div class="oo-chat-attachments">'.$attach_html.'</div>';
				if($class == 'two' || current_user_can('manage_options')){
					$html .= '<span class="oo-icon upload oo-upload-media" data-chatid="'.$chat->chat_id.'"><i class="oo-icon oo-attach"></i></span>
					<input type="file" class="oo-chat-upload-input" id="oo-chat-upload-'.$chat->chat_id.'" data-chatid="'.$chat->chat_id.'"/>';
				}
				$html .= '</li>';
		return $html;
  }
	public function get_status_label($c_status,$type = 'html')
  {
		if($type == 'html'){
			if($c_status == 0){
				return '<span class="oo-chat-status unknow" title="'.__('Unknow','oometrics').'"></span>';
			} else if($c_status == 1){
				return '<span class="oo-chat-status sent" title="'.__('Sent','oometrics').'"></span>';
			} else if($c_status == 2){
				return '<span class="oo-chat-status delivered" title="'.__('Delivered','oometrics').'"></span>';
			} else if($c_status == 3){
				return '<span class="oo-chat-status seen" title="'.__('Seen','oometrics').'"></span>';
			}
		} else if($type == 'label') {
			if($c_status == 0){
				return __('Unknow','oometrics');
			} else if($c_status == 1){
				return __('Sent','oometrics');
			} else if($c_status == 2){
				return __('Delivered','oometrics');
			} else if($c_status == 3){
				return __('Seen','oometrics');
			}
		} else if($type == 'class') {
			if($c_status == 0){
				return 'unknow';
			} else if($c_status == 1){
				return 'sent';
			} else if($c_status == 2){
				return 'delivered';
			} else if($c_status == 3){
				return 'seen';
			}
		}
	}
	public function get_session_chats($rel_id,$where,$html = false)
  {

		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats_rel';
		$ctable = $wpdb->prefix.'oometrics_chats';

		$chats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $ctable WHERE chat_rel_id = '%d'",
				array($rel_id))
		);

		$delivered = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $ctable SET chat_status = '2' WHERE chat_status < 3 AND chat_ses_id != '%d' AND chat_rel_id = '%d'",
						 // array($this->chat_sender_id,$this->chat_sender_id,$this->chat_receiver_id,$this->chat_receiver_id)
						 array($this->chat_ses_id,$rel_id)
				)
		);
		$ses = new OOSession();
		$session = $ses->get();
		$this->chat_ses_id = $session->ses_id;
		$this->chat_sender_ses_id = $session->ses_id;
		if(!$html){
			return $chats;
		} else {
			foreach ($chats as $key => $chat) {
				$html_code .= $this->render_chat($chat->chat_id);
			}
			return array('html'=>$html_code,'total'=>count($chats));
		}

  }
	public function mark_as_seen($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';
		$seen = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table SET chat_status = '3' WHERE chat_id = '%d'",
						 array($chat_id)
				)
		);

		if($seen){
			return true;
		} else {
			return false;
		}


  }

	public function delete_chat($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';
		$seen = $wpdb->delete($table,array('chat_id'=>$chat_id));
		if($seen > 0){
			return true;
		} else {
			return false;
		}
  }
	public function edit_chat($chat_id,$message)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';
		$delivered = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table SET chat_content = '%s', chat_edited = '1', chat_date = '%d' WHERE chat_id = '%d'",
						 // array($this->chat_sender_id,$this->chat_sender_id,$this->chat_receiver_id,$this->chat_receiver_id)
						 array($message,time(),$chat_id)
				)
		);

		if(empty($seen)){
			return true;
		} else {
			return false;
		}
  }
	public function get_attachments($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';
		$chat = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT chat_attachments FROM $table WHERE chat_id = '%d'",
						 // array($this->chat_sender_id,$this->chat_sender_id,$this->chat_receiver_id,$this->chat_receiver_id)
						 array($chat_id)
				)
		);

		if(!empty($chat)){
			return unserialize($chat);
		} else {
			return false;
		}
  }
	public function update_attachments($chat_id,$chat_attachments)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';
		$chat = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table SET chat_attachments = '%s' WHERE chat_id = '%d'",
						 // array($this->chat_sender_id,$this->chat_sender_id,$this->chat_receiver_id,$this->chat_receiver_id)
						 array(serialize($chat_attachments),$chat_id)
				)
		);

		if($chat > 0){
			return true;
		} else {
			return false;
		}
  }
	public function render_attachments($attach_id)
  {
		$attach_url = wp_get_attachment_image_src($attach_id,'thumbnail');
		if(empty($attach_url)){
			$attach_url = wp_get_attachment_url($attach_id,'thumbnail');
		} else {
			$attach_url = $attach_url[0];
		}

		$format = explode('.', $attach_url);
		$format = end($format);
		if(
				preg_match('/jpg|JPG|jpeg|JPEG|png|PNG|SVG|svg|gif|GIF/i', $format)
		){
			$html = '<a target="_blank" class="oo-chat-attach-dl" href="'.$attach_url.'" title="'.__("Download",'oometrics').'"><img width="40" src="'.$attach_url.'" /><i class="oo-icon oo-download imged"></i></a>';
		}else if(
				preg_match('/pdf|PDF/i', $format)
		){
			$html = '<a target="_blank" class="oo-chat-attach-dl" href="'.$attach_url.'" title="'.__("Download",'oometrics').'"><i class="oo-icon oo-pdf imged"></i></a>';
		} else {
			$html = '<a target="_blank" class="oo-chat-attach-dl" href="'.$attach_url.'" title="'.__("Download",'oometrics').'"><i class="oo-icon oo-download"></i></a>';
		}

		return $html;
  }

}
