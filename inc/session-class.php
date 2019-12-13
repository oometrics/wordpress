<?php
/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOSession
{

	public $table;

	public $ses_id;
	public $ses_hash;
	public $ses_uid;
	public $ses_value;
	public $ses_device;
	public $ses_device_brand;
	public $ses_browser;
	public $ses_resolution;
	public $ses_ip;
	public $ses_referrer;
	public $ses_expired;
	public $ses_cart_session;
	public $ses_date;
	public $ses_last_act;
	public $logged;
	public $receiver_id;

	private $option_name = 'oometrics_options';


	public function __construct(){
		global $wpdb;
		$settings = get_option($this->option_name);
		$this->receiver_id = $settings['main_user'];
		$this->table = $wpdb->prefix.'oometrics_sessions';
		$this->ses_uid = get_current_user_id();
		$this->ses_hash = '';
		$this->ses_value = 0;
		$this->ses_device = '';
		$this->ses_device_brand = '';
		$this->ses_browser = '';
		$this->ses_resolution = '';
		$this->ses_ip = '';
		$this->ses_referrer = '';
		$this->ses_cart_session = '0:{}';
		$this->ses_date = time();
		$this->ses_last_act = time();
		$this->ses_expired = 0;
		$this->ses_debug = '';

	}

	public function get($ses_id = 0){

		$this->ses_uid = get_current_user_id();
		if($ses_id > 0){
			$session = $this->get_by('ses_id',$ses_id);
			if($session != false)
			{
					$this->ses_id = $session->ses_id;
					$this->ses_uid = $session->ses_uid;
					$this->ses_hash = $session->ses_hash;
					$this->ses_value = $session->ses_value;
					$this->ses_device = $session->ses_device;
					$this->ses_device_brand = $session->ses_device_brand;
					$this->ses_browser = $session->ses_browser;
					$this->ses_resolution = $session->ses_resolution;
					$this->ses_ip = $session->ses_ip;
					$this->ses_referrer = $session->ses_referrer;
					$this->ses_cart_session = $session->ses_cart_session;
					$this->ses_date = $session->ses_date;
					$this->ses_last_act = $session->ses_last_act;
					$this->ses_debug = $session->ses_debug;
					$this->ses_expired = 0;
					// $this->ses_last_act = time();
					return $this;
			}
		}


		$now = time();
		$session_lifetime = ini_get("session.gc_maxlifetime");
		$exp_diff = $now - $session_lifetime;
		if($this->var_exists()){
				session_start();
				$session = $this->get_by('ses_hash',$_SESSION['oometrics_ses_id'],array('ses_expired'=>'true'));
				if($session != false)
				{
						$this->ses_id = $session->ses_id;
					  $this->ses_uid = $session->ses_uid;
						$this->ses_hash = $session->ses_hash;
						$this->ses_value = $session->ses_value;
						$this->ses_device = $session->ses_device;
						$this->ses_device_brand = $session->ses_device_brand;
						$this->ses_browser = $session->ses_browser;
						$this->ses_resolution = $session->ses_resolution;
						$this->ses_ip = $session->ses_ip;
						$this->ses_referrer = $session->ses_referrer;
						$this->ses_cart_session = $session->ses_cart_session;
						$this->ses_date = $session->ses_date;
						$this->ses_last_act = $session->ses_last_act;
						$this->ses_debug = $session->ses_debug;
						$this->ses_expired = 0;
						return $this;
				}
				else {

						$this->ses_hash = $_SESSION['oometrics_ses_id'];
						if($this->db_exists()){
							$random_number = mt_rand(1111111,99999999);
							$this->ses_hash = wp_hash($now.'X'.$random_number);
							$_SESSION['oometrics_ses_id'] = $this->ses_hash;
						}

						$this->add();
						return $this;
				}


			} else {


				if($this->ses_uid > 0){
					$session = $this->get_by('ses_uid',$this->ses_uid,array('ses_expired' => 'true'));

					if($session != false && ($session->ses_last_act > $exp_diff ))
					{
						$this->ses_id = $session->ses_id;
						$this->ses_uid = $session->ses_uid;
						$this->ses_hash = $session->ses_hash;
						$this->ses_value = $session->ses_value;
						$this->ses_device = $session->ses_device;
						$this->ses_device_brand = $session->ses_device_brand;
						$this->ses_browser = $session->ses_browser;
						$this->ses_resolution = $session->ses_resolution;
						$this->ses_ip = $session->ses_ip;
						$this->ses_referrer = $session->ses_referrer;
						$this->ses_cart_session = $session->ses_cart_session;
						$this->ses_date = $session->ses_date;
						$this->ses_last_act = $session->ses_last_act;
						$this->ses_debug = $session->ses_debug;
						$this->ses_expired = 0;
						// $this->ses_last_act = time();
						$_SESSION['oometrics_ses_id'] = $this->ses_hash;
						return $this;
					}
				}
				session_start();
				$random_number = mt_rand(1111111,99999999);
				$this->ses_hash = wp_hash($now.$random_number);
				// $this->ses_hash = wp_get_session_token();
				$_SESSION['oometrics_ses_id'] = $this->ses_hash;

				if($this->db_exists()){
					$this->ses_hash = wp_hash($now.$random_number.'x'.$now);
					$_SESSION['oometrics_ses_id'] = $this->ses_hash;
				}
				$this->add();
				return $this;
			}
		return $this;
	}


	public function var_exists()
	{
		session_start();
		if(isset($_SESSION['oometrics_ses_id']) && !empty($_SESSION['oometrics_ses_id']))
		{
			return true;
		}

		return false;
	}

	public function db_exists($expired = -1)
	{
		global $wpdb;
		if($expired != -1)
		{
			$in_db = $wpdb->get_var(
			    $wpdb->prepare(
			        "SELECT COUNT(*) FROM $this->table
			         WHERE ses_hash = '%s' AND ses_expired = '%d'",
			         $this->ses_hash,$expired
			    )
			);
		}
		else {
			$in_db = $wpdb->get_var(
			    $wpdb->prepare(
			        "SELECT COUNT(*) FROM $this->table
			         WHERE ses_hash = '%s'",
			         $this->ses_hash
			    )
			);
		}

		if((int)$in_db > 0)
		{
			return true;
		}
		return false;
	}

	public function set($key,$value)
	{
		$this->$key = $value;
	}

	public function get_by($column,$value,$args = array()){
		global $wpdb;

		$expired = !empty($args['ses_expired']) ? ' AND ses_expired = \'0\'' : '';

		if($column == 'ses_id')
		{
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table
					 WHERE ses_id = '%d'".$expired,
					 $value
			);
		} else if($column == 'ses_hash')
		{
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table
					 WHERE ses_hash = '%s'".$expired,
					 $value
			);
		} else if($column == 'ses_uid')
		{
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table
					 WHERE ses_uid = '%d'".$expired,
					 $value
			);
		}

		$session_data = $wpdb->get_row($query);

		if(!empty($session_data->ses_id)){
			// $session = $this->get($session_data->ses_id);
			return $session_data;
		}
		return false;
	}

	public function add(){

		global $wpdb;
		$now = time();
		session_start();
		$random_number = mt_rand(1111111,99999999);
		$this->ses_hash = wp_hash($now.$random_number);
		// $this->ses_hash = wp_get_session_token();
		$_SESSION['oometrics_ses_id'] = $this->ses_hash;

		if($this->db_exists()){
			$this->ses_hash = wp_hash($now.$random_number.'x'.$now);
			$_SESSION['oometrics_ses_id'] = $this->ses_hash;
		}

		$data['ses_hash'] = $this->ses_hash;
		$data['ses_uid'] = $this->ses_uid;
		$data['ses_value'] = $this->ses_value;
		$data['ses_expired'] = $this->ses_expired;
		$data['ses_date'] = $now;
		$data['ses_last_act'] = $now;
		$data['ses_debug'] = ''; //serialize($_SERVER);


		$helper = new OOHelper();
		$request_info = $helper->get_request_info();
		$device_info = $helper->get_device_info();

		$this->ses_device = $data['ses_device'] = $device_info['device'];
		$this->ses_device_brand = $data['ses_device_brand'] = $device_info['brand'];
		$this->ses_browser = $data['ses_browser'] = $device_info['browser'];
		$this->ses_resolution = $data['ses_resolution'] = $device_info['resolution'];
		$this->ses_ip = $data['ses_ip'] = $request_info['ip'];
		$this->ses_referrer = $data['ses_referrer'] = $request_info['referer'];

		// checkes for cart content


		$woo_cart_session = $this->get_cart_session();
		// }
		if(!empty($woo_cart_session)){
			$this->ses_cart_session = $woo_cart_session;
		}
		$this->ses_last_act = $data['ses_last_act'] = $now;
		$wpdb->insert($this->table,$data);
		$this->ses_id = $wpdb->insert_id;

		return $this;
	}

	// public function debug(){
	// 	global $wpdb;
	// 	$this->ses_uid = get_current_user_id();
	// 	$debug_table = $wpdb->prefix.'oometrics_debug';
	// 	$server_var = serialize($_SERVER);
	// 	$request_var = serialize($_REQUEST);
	// 	$in_db = $wpdb->get_var(
	// 			$wpdb->prepare(
	// 					"SELECT COUNT(*) FROM $debug_table
	// 					 WHERE debug_ses_id = '%d'",
	// 					 $this->ses_id
	// 			)
	// 	);
	// 	if($in_db <= 0){
	// 		$in_db = $wpdb->replace($debug_table,array('debug_ses_id'=>$this->ses_id,'debug_server_var'=>$server_var,'debug_request_var'=>$request_var,'debug_date'=>time()));
	// 	} else {
	// 		$in_db = $wpdb->update($debug_table,array('debug_server_var'=>$server_var,'debug_request_var'=>$request_var,'debug_date'=>time()),array('debug_ses_id'=>$this->ses_id));
	// 	}
	//
	// }
	public function sync(){

		if((is_user_logged_in()) && $this->ses_uid == 0){
				global $wpdb;
				$this->ses_uid = get_current_user_id();
				$s_table = $wpdb->prefix.'oometrics_chats';
				$in_db = $wpdb->get_var(
						$wpdb->prepare(
								"UPDATE $s_table
								 SET chat_sender_id = '%d'
								 WHERE chat_ses_id = '%d' AND chat_sender_id = '%d'",
								 array($this->ses_uid,$this->ses_id,0)
						)
				);
				$in_db = $wpdb->get_var(
						$wpdb->prepare(
								"UPDATE $s_table
								 SET chat_receiver_id = '%d'
								 WHERE chat_ses_id = '%d' AND chat_receiver_id = '%d'",
								 array($this->ses_uid,$this->ses_id,0)
						)
				);
		}
		$cart_data = $this->get_cart_session();
		// print_r(unserialize($cart_data));
		$this->set('ses_cart_session',$cart_data);
		$this->set('ses_last_act',time());
		$this->update();
	}

	public function update(){

		global $wpdb;
		$data['ses_uid'] = $this->ses_uid;
		$data['ses_value'] = $this->ses_value;
		$data['ses_device'] = $this->ses_device;
		$data['ses_device_brand'] = $this->ses_device_brand;
		$data['ses_browser'] = $this->ses_browser;
		$data['ses_resolution'] = $this->ses_resolution;
		$data['ses_ip'] = $this->ses_ip;
		$data['ses_referrer'] = $this->ses_referrer;
		$data['ses_cart_session'] = $this->ses_cart_session;
		$data['ses_expired'] = $this->ses_expired;
		$data['ses_date'] = $this->ses_date;
		$data['ses_last_act'] = $this->ses_last_act;

		$where = array('ses_id'=>$this->ses_id);
		$result = $wpdb->update( $this->table, $data, $where );

		if( $result > 0){
			return true;
		} else {
			return false;
		}

	}

	public function update_all()
	{
		// echo $this->sesh_hash;
		// session_start();
		// echo $_SESSION['oometrics_ses_id'];
		$now = time();
		$last_update = get_option('OOMetrics_last_run',0);

		global $wpdb;
		if(($now - $last_update) > 50)
		{

			// check and set current sessions
			$session_lifetime = ini_get("session.gc_maxlifetime");
			$expiration_time = $now - $session_lifetime;
			$wpdb->query(
			    $wpdb->prepare(
			        "UPDATE $this->table
			         SET ses_expired = '1' WHERE ses_last_act < '%d'",
			         array($expiration_time)
			    )
			);

			$push_table = $wpdb->prefix.'oometrics_pushes';
			$wpdb->query(
			    $wpdb->prepare(
			        "UPDATE $push_table
			         SET push_status = '1' WHERE push_time_gap < '%d'",
			         array($now)
			    )
			);
			update_option('OOMetrics_last_run',$now);
		}

		$settings = get_option('oometrics_options');
		if($settings['clean_zero_values'] == 'yes'){
			$wpdb->query(
			    $wpdb->prepare(
			        "DELETE FROM $this->table
			         WHERE ses_value <= '%d' && ses_date < '%d'",
			         array(0,$now - 20)
			    )
			);
		}

		$wpdb->query(
				$wpdb->prepare(
						"DELETE FROM $this->table
						 WHERE ses_resolution IS NULL && ses_date < '%d'",
						 array($now - 30)
				)
		);

		return true;

	}

	public function add_value($value = 1)
	{
		global $wpdb;

		$this->ses_value = $this->ses_value + $value;
		$this->update();
		return $this->ses_value;
	}
	public function add_activity_init()
	{

		$activity = new OOActivity();
		$activity->init($this->ses_hash);
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
	public function referrer_detect()
	{

		$ses_referrer = $this->ses_referrer;
		if(isset($ses_referrer) && preg_match('/cron|cronjob/i', $ses_referrer))
		{
			return __('WP Cronjob','oometrics');
		}
		return false;
	}
	public function activities_count()
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activities';
		$count = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE act_ses = '%d'",
						 $this->ses_id
				)
		);
		return $count;
	}
	public function chat_count($ses_id = 0,$rel_id = -1)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';
		if( $rel_id > 0){
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE chat_ses_id = '%d' AND chat_rel_id = '%d'",
							 $ses_id,$rel_id
					)
			);
		} else {
			$session_id = ($ses_id > 0) ? $ses_id : $this->ses_id;
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE chat_ses_id = '%d'",
							 $session_id
					)
			);
		}

		return $count;
	}
	public function new_chat_count($ses_id = 0, $rel_id = -1)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chats';
		if( $rel_id > 0){
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE chat_ses_id = '%d' AND chat_rel_id = '%d' AND chat_status != '%d'",
							 array($ses_id,$rel_id,3)
					)
			);
		} else {
			$session_id = ($ses_id > 0) ? $ses_id : $this->ses_id;
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE chat_ses_id = '%d' AND chat_status != '%d'",
							 array($session_id,3)
					)
			);
		}

		return $count;
	}

	public function get_total_sales_day()
	{
    global $wpdb;
		$sales = $wpdb->get_var( "
        SELECT DISTINCT SUM(pm.meta_value)
        FROM {$wpdb->prefix}posts as p
        INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
        WHERE p.post_type LIKE 'shop_order'
        AND p.post_status IN ('wc-processing','wc-completed')
        AND UNIX_TIMESTAMP(p.post_date) >= (UNIX_TIMESTAMP(NOW()) - (86400))
        AND pm.meta_key LIKE '_order_total'
    " );
    return wc_price($sales);

	}
	public function get_woo_session_value($session_hash = ''){
		global $wpdb;
		$table = $wpdb->prefix.'woocommerce_sessions';
			$woo_session = $wpdb->get_row(
					$wpdb->prepare(
							"SELECT session_value FROM $table
							 WHERE session_key = '%s'",
							 array($session_hash)
					)
			);

			return $woo_session;

	}
	public function update_actual_cart($session_hash,$session_data){
		global $wpdb;
		$table = $wpdb->prefix.'woocommerce_sessions';
			$woo_session = $wpdb->get_row(
					$wpdb->prepare(
							"UPDATE $table
							SET session_value = '%s'
							 WHERE session_key = '%s'",
							 array($session_data,$session_hash)
					)
			);
			if($woo_session > 0){
				$this->sync();
			}
			return $woo_session;
	}
	public function get_cart_session(){
		if(class_exists('WooCommerce')){
			// $woo_session = new WC_Session_Handler();
			$woo_session = WC()->session;
			if(!empty($woo_session)){
				$woo_session_id = $woo_session->get_customer_id();
				$session_value = $this->get_woo_session_value($woo_session_id);
				$session_value_content = unserialize($session_value->session_value);
			}

			if(!empty($session_value_content))
			{
				$oo_session_value_content['session'] = $session_value_content;
			} else {
				$oo_session_value_content['session'] = '0:{}';
			}
			$oo_session_value_content['key_hash'] = $woo_session_id;
			$oo_cart_ses = serialize($oo_session_value_content);
			return $oo_cart_ses;


		}
	}
	public function get_activities($echo = false)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activities';
		$activities = $wpdb->get_results(
				$wpdb->prepare(
						"SELECT * FROM $table
						 WHERE act_ses = '%d'
						 ORDER BY
						 act_date
						 DESC",
						 $this->ses_id
				)
		);
		if($echo)
		{
			$html = '';
			foreach ($activities as $key => $act) {
				$act_obj = new OOActivity();
				$html .= $act_obj->render($act->act_id);
			}
			return $html;
		}
		return $activities;
	}

	public function get_profile()
	{
		global $wpdb;
		$utable = $wpdb->prefix.'users';
		$query = $wpdb->prepare(
				"SELECT * FROM $this->table as ses
				 INNER JOIN $utable as user ON ses.ses_uid = user.ID
				 WHERE ses_hash = '%s'",
				 array($this->ses_hash)
		);
		$profile_data = $wpdb->get_row($query);
		if(empty($profile_data)){
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table
					 WHERE ses_hash = '%s'",
					 array($this->ses_hash)
			);
			$profile_data = $wpdb->get_row($query);
		}
		return $profile_data;
	}
	public function get_session_pushes($status){
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_pushes';
		$now = time();
		$pushes = $wpdb->get_results(
			$wpdb->prepare(
					"SELECT * FROM $table
					 WHERE push_ses_id = '%d' AND push_status = '%d'",
					 array($this->ses_id,$status)
			)
		);
		return $pushes;
	}
	public function render_profile($uid = 0,$html = false)
	{
		if($uid > 0){
			$user = get_user_by('id',$uid);
			$profile_data['display_name'] = $user->display_name;
			$profile_data['avatar'] = get_avatar($uid);
			$activity = '';
		} else {
			$profile_data['display_name'] = __('You','oometrics');
			$profile_data['avatar'] = '<img src="'.OOMETRICS_URL.'/assets/images/anon-avatar.svg"/>';
			$activity = '';
		}
		$rendered = '
		<div class="profile-info">
			'.$profile_data['avatar'].'
			<ul class="profile-data">
				<li class="name"><strong>'.$profile_data['display_name'].'</strong></li>
				<li class="name">'.$activity.'</li>
			</ul>
		</div>
		';
		if(!$html){
			return $profile_data;
		} else {
			return $rendered;
		}

	}

	public function get_live()
	{
		global $wpdb;
		$settings = get_option('oometrics_options');
		$lifetime = (!empty($settings['session_lifetime'])) ? $settings['session_lifetime'] : 300;
		$diff = time() - $lifetime;
		$settings = get_option($this->option_name);
		$order_by = (empty($settings['live_sort_by'])) ? 'ses_last_act' : $settings['live_sort_by'];
		$sessions = $wpdb->get_results(
		    $wpdb->prepare(
		        "SELECT * FROM $this->table
		         WHERE ses_expired = '%d' AND ses_uid != '%d' AND ses_last_act > '%d' AND ses_value >= '%d' AND ses_resolution IS NOT NULL
						 ORDER BY $order_by DESC",
		         array(0,$this->receiver_id,$diff,0)
		    )
		);

		return $sessions;
	}

	public function render($admin = true)
	{
		if(!empty($this->ses_uid) && $this->ses_uid > 0)
		{
			$user = get_user_by('id',$this->ses_uid);
			$ses_name = $user->display_name;
			if(empty($ses_name))
			{
				$ses_name = $user->user_login;
			}
			// Check for known referres


			$ses_avatar = get_avatar($this->ses_uid,40);
		}
		else
		{
			$ses_name = $this->ses_hash;
			$ses_avatar = '<img class="avatar" src="'.OOMETRICS_URL.'/assets/images/anon-avatar.svg" />';
		}

		$known_referrer = $this->referrer_detect($this->ses_referrer);
		if($known_referrer)
		{
			$ses_name = $known_referrer;
		}

		$session_pushes = $this->get_session_pushes(0);
		if(!empty($session_pushes)){
			$pushes_html = '<hr /><div class="oo-push-items">';
			foreach ($session_pushes as $key => $session_pushe) {

				$time_left = human_time_diff( $session_pushe->push_time_gap, time() );
				$pushes_html .= '<div class="oo-push-item '.$session_pushe->push_type.'" id="oo-push-item-'.$session_pushe->push_id.'">
					<img src="'.OOMETRICS_URL.'assets/images/'.$session_pushe->push_type.'.svg" />'.
					$time_left.' '.__('left','oometrics').
					'<div class="oo-push-delete" data-pushid="'.$session_pushe->push_id.'">
						<img src="'.OOMETRICS_URL.'/assets/images/close-popup.svg" />
					</div>
				</div>';
			}
			$pushes_html .= '</div>';
		}

		$time = human_time_diff( $this->ses_last_act, time() );
		$ses_referrer = trim($this->ses_referrer,'http://');
		$ses_referrer = trim($ses_referrer,'https://');
		$ses_referrer = explode('/',$ses_referrer);

		$ses_value = $this->ses_value;
		// $html = '';
		$activities_count = $this->activities_count();
		$chat_count = $this->chat_count();
		$new_chat_count = $this->new_chat_count();
		if($chat_count > 0){
			if($new_chat_count > 0){
				$chat = '<span class="oo-new-chat-badge">'.$new_chat_count.'</span>';
			} else {
				$chat = '<span class="oo-new-chat-badge off">'.$chat_count.'</span>';
			}

		}
		$html = '
		<li data-sesid="'.$this->ses_id.'" class="oo-session-profile">
      '.$ses_avatar.'
      <div class="oo-session-info">
				'.$chat.'
        <strong>'.$ses_name.'</strong>';
				if($admin){
					$html .= '
	        <span>Activities: <b>'.$activities_count.'</b></span>
	        <span>Value: <b>'.$ses_value.'</b></span><br />
					<span>Referrer: <b>'.$ses_referrer[0].'</b></span><br />';
				}

				$html .= '
        <em><i class="oo-icon clock"></i>'.$time.'</em>
				'.$pushes_html.'
      </div>
    </li>
		';
		return $html;
	}

	public function get_online()
	{
		global $wpdb;
		$settings = get_option('oometrics_options');
		$lifetime = (!empty($settings['session_lifetime'])) ? $settings['session_lifetime'] : 300;
		$diff = time() - $lifetime;
		$online = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT COUNT(*) FROM $this->table
		         WHERE ses_expired = '%d' AND ses_uid != '%d' AND ses_last_act > '%d' AND ses_value >= '%d' AND ses_resolution IS NOT NULL
						 ORDER BY ses_last_act DESC",
		         array(0,$this->receiver_id,$diff,0)
		    )
		);
		return $online;
	}
	public function get_pageviews()
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activities';
		$day = time() - 86400;
		$count = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE act_date > '%d'
						 ",
						 $day
				)
		);
		return $count;
	}

	public function get_unique_users()
	{
		global $wpdb;
		$unique = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT COUNT(*) FROM $this->table
		         WHERE ses_date > '%d' AND ses_value > '%d' AND ses_resolution IS NOT NULL
						 ORDER BY ses_last_act DESC",
		         array(time() - 86400,0)
		    )
		);
		return $unique;
	}
}
