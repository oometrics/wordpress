<?php
/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOAjax
{

	private $_nonce = 'oometrics_nonce';
	private $option_name = 'oometrics_options';

	public $session;
	public function __construct(){

	}
	public function set_session($session){
		$this->session = $session;
	}
	public function update_session()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$ses = new OOSession();
		$session = $ses->get();
		if($session == 'direct'){
			$this->ses_hash = $_SESSION['oometrics_ses_id'];
			$session = $ses->add();
			$this->set_session($session);
		} else {
			$session = $this->session;
		}

		// get session data as an array and sanitize them individualy
		$session_data = $_POST['session'];


		$session->set('ses_resolution',sanitize_text_field($session_data['screen']));
		// $session->set('ses_last_act',time());
		// print_r($session)
		$session->update();

		$chat_badge = '';
		if($session_data['rel_id'] == -1){
			$chat_count = $session->chat_count(sanitize_text_field($session_data['id']),sanitize_text_field($session_data['rel_id']));
			$new_chat_count = $session->new_chat_count(sanitize_text_field($session_data['id']),sanitize_text_field($session_data['rel_id']));
			$crel = $session->get_active_rel_by_ses_id(sanitize_text_field($session_data['id']),sanitize_text_field($session_data['admin_ses_id']));
			$rel_id = $crel->crel_id;
			if(empty($rel_id)){
				$rel_id = sanitize_text_field($session_data['rel_id']);
			}
		} else {
			$chat_count = $session->chat_count(sanitize_text_field($session_data['admin_ses_id']),sanitize_text_field($session_data['rel_id']));
			$new_chat_count = $session->new_chat_count(sanitize_text_field($session_data['admin_ses_id']),sanitize_text_field($session_data['rel_id']));
			$rel_id = $session_data['rel_id'];
		}
			// echo 'ssss'.$session_data['admin_ses_id'];

			if($chat_count > 0){
				if($new_chat_count > 0){
					$chat_badge = '<span class="oo-new-chat-badge">'.$new_chat_count.'</span>';
				} else {
					$chat_badge = '<span class="oo-new-chat-badge off">'.$chat_count.'</span>';
				}
			}


		$now = time();
		$push = new OOPush();
		$ses_push = $push->get_session_open_popup_push(sanitize_text_field($session_data['id']));
		if(!empty($ses_push)){
			if($ses_push->push_time_gap < $now ){
				$this->change_status($ses_push->push_id,1);
				return true;
			}
			$args = unserialize($ses_push->push_args);
			// print_r($args);
			$popup_type = $args['popup_type'];
			$popup_content = $args['popup_content'];
			if($popup_type == 'promotional'){
				$popup =  $push->render_promotianl_popup($ses_push->push_id,$popup_content,true,$args);
				$push->change_status($ses_push->push_id,1);
			} else if($popup_type == 'register'){
				$popup = $push->render_register_popup($ses_push->push_id,true);
				$push->change_status($ses_push->push_id,1);
			} else if($popup_type == 'ooarea'){
				$popup = $push->render_ooarea_popup($ses_push->push_id,true);
				$push->change_status($ses_push->push_id,1);
			}
		}
		$popup = empty($popup) ? 'none' : $popup;
		wp_send_json( array('rel_id'=>$rel_id,'chat_badge'=>$chat_badge,'popup'=>$popup) );
	}

	public function get_live_sessions()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$ses = new OOSession();
		$session = $ses->get();
		if($session == 'direct'){
			$this->ses_hash = $_SESSION['oometrics_ses_id'];
			$session = $ses->add();
			$this->set_session($session);
		} else {
			$session = $this->session;
		}

		$ses_obj = $this->session;
		$sessions = $ses_obj->get_live();
		// print_r($sessions);
		$html = '';
		if(!empty($sessions)){
			foreach ($sessions as $key => $session) {
				$session = $ses_obj->get($session->ses_id);
				$html .= $session->render();
			}
		} else {
			$html = '<div class="oo-no-live-session">'.__("No one is online now",'oometrics').'</div>';
		}

		$session = $ses_obj->get();
		$total_sale = $session->get_total_sales_day();
		$overview['total_sales'] = (!empty($total_sale)) ? $total_sale : 0;
		// $overview['total_sales'] = wp_price($overview['total_sales']);
		$overview['online_users'] = $session->get_online();
		$overview['unique_users'] = $session->get_unique_users();
		$overview['pageviews'] = $session->get_pageviews();
		wp_send_json( array('content'=>$html,'overview'=>$overview) );
	}
	public function get_session()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		// get the data as an array and sanitize them individually
		$session_data = $_POST['session'];

		$ses = $this->session;
		$session = $ses->get();
		$session->set('ses_resolution',sanitize_text_field($session_data['screen']));
		// $session->set('ses_last_act',time());
		// print_r($session)
		$session->update();
		// die('updated!');
		unset($ses);
		unset($session);

		$ses_id = (int)(sanitize_text_field($_POST['ses_id']));
		$rel_id = (int)(sanitize_text_field($_POST['rel_id']));
		$ses_obj = $this->session;
		$session = $ses_obj->get($ses_id);
		$activities = $session->get_activities(true);
		$profile_data = $session->get_profile();
		$profile_clean = [];

		if(isset($profile_data->ses_uid)){
				$billing_phone = get_user_meta( $profile_data->ses_uid, 'billing_phone', true );
				$shipping_state = get_user_meta( $profile_data->ses_uid, 'shipping_state', true );
				$shipping_city = get_user_meta( $profile_data->ses_uid, 'shipping_city', true );
				$profile_clean['billing_phone'] = isset($billing_phone) ? $billing_phone : 'na';
				$profile_clean['shipping_state'] = isset($shipping_state) ? $shipping_state : 'na';
				$profile_clean['shipping_city'] = isset($shipping_city) ? $shipping_city : 'na';
		}

		$profile_clean['display_name'] = isset($profile_data->display_name) ? $profile_data->display_name : $profile_data->ses_hash;
		$profile_clean['user_email'] = isset($profile_data->user_email) ? $profile_data->user_email : 'na';
		$profile_clean['user_id'] = isset($profile_data->user_id) ? $profile_data->user_id : 0;



		$session_content_raw = $session->ses_cart_session;
		$session_obj = unserialize($session_content_raw);
		$session_key = $session_obj['key_hash'];
		$session_content = $session_obj['session'];
		$cart_data = !empty($session_content['cart']) ? unserialize($session_content['cart']) : [];

		$totals = !empty($session_content['cart_totals']) ? unserialize($session_content['cart_totals']) : 0;
		$cart['cart_items'] = (empty($cart_data)) ? 0 : count($cart_data);
		$cart['cart_total'] = wc_price($totals['total']);

		if($session->ses_uid == 0 ){
			$cart['purchased_items'] = '?';
			$cart['purchased_total'] = '?';
		} else {
			$customer_orders = get_posts( array(
			    'numberposts' => -1,
			    'meta_key'    => '_customer_user',
			    'meta_value'  => $session->ses_uid,
			    'post_type'   => wc_get_order_types(),
			    'post_status' => array_keys( wc_get_order_statuses() ),
			) );

			$cart['purchased_items'] = count($customer_orders);
			$cart['purchased_total'] = wc_price(wc_get_customer_total_spent( $session->ses_uid ));
		}

		if(!empty($cart_data)){
				$html = '';
			foreach ($cart_data as $key => $cart_item) {
				$quantity = $cart_item['quantity'];
				// simple product
				if($cart_item['variation_id'] == 0){
					$pid = $cart_item['product_id'];
					$product = wc_get_product( $pid ); // The WC_Product object
					if( ! $product->is_on_sale() ){
							$price = get_post_meta( $pid, '_price', true ); // Update active price
							$sale_price = get_post_meta($pid,'_sale_price',true);
					} else {
						$price = get_post_meta( $pid, '_regular_price', true ); // Update active price
						$sale_price = '';
					}

					if(!empty($sale_price)){
						$price_html = wc_price($price).'-'.wc_price($sale_price);
					} else {
						$price_html = wc_price($price);
					}
					$post_title = $product->get_title();
					$p_thumb = get_the_post_thumbnail($pid,'thumbnail');
					$html .='<div data-pid="'.$pid.'" data-vid="0" data-key="'.$cart_item['key'].'" data-qty="'.$quantity.'" class="oo-search-result-item"><span class="oo-remove-selected">x</span><input type="number" class="oo-quantity" value="'.$quantity.'"/>'.$p_thumb.'<h5>'.$post_title.'</h5><br />'.$price_html.'</div>';
				} else {
					$pid = $cart_item['product_id'];
					$vid = $cart_item['variation_id'];
					$product = wc_get_product( $pid ); // The WC_Product object
					$atts = $cart_item['variation'];
					foreach ($atts as $key => $att) {
						$term = ltrim($key,'attribute_');

						$att_term = get_term_by('id',$att,$term);

						$variation_selected = $att_term->name;
					}
			    if( ! $product->is_on_sale() ){
			        $price = get_post_meta( $pid, '_price', true ); // Update active price
							$sale_price = get_post_meta($pid,'_sale_price',true);
			    } else {
						$price = get_post_meta( $pid, '_regular_price', true ); // Update active price
						$sale_price = '';
					}
					if(!empty($sale_price)){
						$price_html = wc_price($price).'-'.wc_price($sale_price);
					} else {
						$price_html = wc_price($price);
					}
					$post_title = $product->get_title();
					$p_thumb = get_the_post_thumbnail($vid,'thumbnail');
					if(empty($p_thumb)){
						$p_thumb = get_the_post_thumbnail($pid,'thumbnail');
					}
					$html .='<div data-pid="'.$pid.'" data-vid="'.$vid.'" data-key="'.$cart_item['key'].'" data-qty="'.$quantity.'" class="oo-search-result-item"><span class="oo-remove-selected">x</span><input type="number" class="oo-quantity" value="'.$quantity.'"/>'.$p_thumb.'<h5>'.$post_title.'</h5><br />'.$variation_selected.' '.$price_html.'</div>';
				}
			}
			$cart['cart_items_html'] = $html;
		}

		$chat = new OOChat();
		if(empty($cart['cart_items_html'])){
			$cart['cart_items_html'] = __("Cart is empty for now",'oometrics');
		}

		$rels = $chat->get_conversations(true,array('ses_id'=>$ses_id));

		$session_debug = print_r(unserialize($session->ses_debug),true);
		wp_send_json( array('session'=>$session,'rels'=>$rels,'activity'=>$activities,'cart'=>$cart,'info'=>array(),'chats'=>'empty','profile'=>$profile_clean,'overview'=>$overview,'debug'=>$session_debug) );
	}
	public function send_message()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$rel_id = (int)(sanitize_text_field($_POST['rel_id']));
		$ses_id = (isset($_POST['ses_id'])) ? (int)(sanitize_text_field($_POST['ses_id'])) : -1;
		$chat_message = htmlentities(stripslashes(sanitize_text_field($_POST['message'])));
		$chat_obj = new OOChat();
		$result = $chat_obj->send_message(array('rel_id'=>$rel_id,'ses_id'=>$ses_id,'chat_content'=>$chat_message));
		if($rel_id <= -1 ){
			$rel_id = $result['rel_id'];
		}
		$status = (!empty($result)) ? 1 : 0;
		$status_label = $chat_obj->get_status_label($status);
		$bubble = $chat_obj->render_chat($result['chat_id']);
		wp_send_json( array('status'=>$status,'status_label'=>$status_label,'rel_id'=>$rel_id,'bubble'=>$bubble,'chat_id'=>$result['chat_id']));
	}
	public function get_session_chats()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$rel_id = sanitize_text_field($_POST['rel_id']);
		$chat_obj = new OOChat();
		$chats = $chat_obj->get_session_chats($rel_id,'',true);
		wp_send_json( array('chats'=>$chats['html'],'total'=>$chats['total']));
	}

	public function update_chat()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$rel_id = (isset($_POST['rel_id']) && $_POST['rel_id'] != -1) ? (int)(sanitize_text_field($_POST['rel_id'])) : 0;
		$chat_obj = new OOChat();
		$chats = $chat_obj->get_session_chats($rel_id,'',true);
		wp_send_json( array('chats'=>$chats['html'],'total'=>$chats['total']));
	}
	public function mark_as_seen()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$chat_id = (int)(sanitize_text_field($_POST['chat_id']));
		$chat_obj = new OOChat();
		$chat = $chat_obj->mark_as_seen($chat_id);
		$status = (!empty($chat)) ? 0 : 1;
		$bubble = $chat_obj->render_chat($chat_id);
		wp_send_json( array('status'=>$status,'bubble'=>$bubble));
	}

	public function delete_chat()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$chat_id = (int)(sanitize_text_field($_POST['chat_id']));
		$chat_obj = new OOChat();
		$chat = $chat_obj->delete_chat($chat_id);
		$status = (empty($chat)) ? 0 : 1;
		wp_send_json( array('status'=>$status));
	}
	public function edit_chat()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$chat_id = (int)(sanitize_text_field($_POST['chat_id']));
		$message = htmlentities(stripslashes(sanitize_text_field($_POST['message'])));
		$chat_obj = new OOChat();
		$chat = $chat_obj->edit_chat($chat_id,$message);
		$bubble = $chat_obj->render_chat($chat_id);
		$status = (empty($chat)) ? 0 : 1;
		wp_send_json( array('status'=>$status,'bubble'=>$bubble));
	}

	public function chat_add_attachment()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$chat_id = (int)(sanitize_text_field($_REQUEST['chat_id']));

		if ($_FILES) {


			// EACH ULOADED FILE
			foreach ($_FILES as $file => $array) {

				// IF CONTAINS ERROR DIE
				if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
					ajax_response('danger',__("<strong>Error!</strong> upload failed.",'rotail'));
				}


				// add_filter('upload_dir', 'image_profile_dir');
				$attach_id = media_handle_upload( $file,0);
				// remove_filter('upload_dir', 'image_profile_dir');

					// IF FILE COULDNT BE UPLOAD
					if(!isset($attach_id) || $attach_id==''){
						wp_send_json( array('status'=>0,'chat_id'=>$chat_id));
					} else {
						$attachments = get_attached_file($attach_id);
						$chat_obj = new OOChat();
						$chat_attachments = $chat_obj->get_attachments($chat_id);
						$chat_attachments[] = $attach_id;
						$chat_attachments = array_unique($chat_attachments);
						$chat_obj->update_attachments($chat_id,$chat_attachments);
						foreach ($chat_attachments as $key => $attach) {
							$html .= $chat_obj->render_attachments($attach);
						}

						wp_send_json( array('status'=>1,'chat_id'=>$chat_id,'html'=>$html));

					}
				}
			}


		wp_send_json( array('status'=>$status,'bubble'=>$bubble));
	}

	public function set_global_order_by()
	{
		if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}

		$orderby = sanitize_text_field($_REQUEST['orderby']);
		if($orderby == 'live'){
			$orderby = 'ses_last_act';
		} else if($orderby == 'value'){
			$orderby = 'ses_value';
		}else if($orderby == 'intelligence'){
			$orderby = 'ses_last_act';
		}else{
			$orderby = 'ses_last_act';
		}
		$options = get_option($this->option_name);

		$options['live_sort_by'] = $orderby;

		update_option($this->option_name,$options);
		wp_send_json( array('status'=>1));
	}


			public function get_report_session()
			{
				if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
				{
					die('Invalid Request! Reload your page please.');
				}


				$ses_id = (int)(sanitize_text_field($_POST['ses_id']));
				$ses_obj = $this->session;
				$session = $ses_obj->get($ses_id);
				$activities = $session->get_activities(true);

				$session_content_raw = $session->ses_cart_session;
				$session_obj = unserialize($session_content_raw);
				$session_key = $session_obj['key_hash'];
				$session_content = $session_obj['session'];
				$cart['cart_items'] = 0;
				$cart['cart_totals'] = 0;
				if(!empty($session_content['cart']) && !empty($session_content['cart_totals'])){
					$cart_data = unserialize($session_content['cart']);

					$totals = unserialize($session_content['cart_totals']);
					$cart['cart_items'] = (empty($cart_data)) ? 0 : count($cart_data);
					$cart['cart_total'] = wc_price($totals['total']);
				}

				if($session->ses_uid == 0 ){
					$cart['purchased_items'] = '?';
					$cart['purchased_total'] = '?';
				} else {
					$customer_orders = get_posts( array(
							'numberposts' => -1,
							'meta_key'    => '_customer_user',
							'meta_value'  => $session->ses_uid,
							'post_type'   => wc_get_order_types(),
							'post_status' => array_keys( wc_get_order_statuses() ),
					) );

					$cart['purchased_items'] = count($customer_orders);
					$cart['purchased_total'] = wc_price(wc_get_customer_total_spent( $session->ses_uid ));
				}

				$chat = new OOChat();
				$rels = $chat->get_conversations(true,array('ses_id'=>$ses_id));
				$rels = '<h3 class="oo-reports-sidebar-title">'.__('Conversations','oometrics').'</h3><ul class="oo-chat-list">'.$rels.'</ul>';

				wp_send_json( array('session'=>$session,'activity'=>$activities,'cart'=>$cart,'info'=>array(),'profile'=>$profile_clean,'overview'=>$overview,'rels'=>$rels) );
			}



			public function get_report(){

				$period = sanitize_text_field($_POST['period']);
				$start = strtotime(sanitize_text_field($_POST['start_date']));
				$end = strtotime(sanitize_text_field($_POST['end_date']));

				$options = get_option('oometrics_options');
				$options['period_time']['period_type'] = $period;
				if($period == 'custom'){
						$options['period_time']['start_time'] = $start;
						$options['period_time']['end_time'] = $end;
				}
				update_option('oometrics_options',$options);

				$report = new OOReport();
				$total_sales = wc_price($report->get_total_sales());
				$total_sessions = $report->get_total_sessions();
				$total_sessions = (empty($total_sessions)) ? 0 : $total_sessions;

				$total_uniques = $report->get_total_uniques();
				$total_uniques = (empty($total_uniques)) ? 0 : $total_uniques;

				$total_orders = $report->get_total_orders();
				$total_orders = (empty($total_orders)) ? 0 : $total_orders;

				$total_activities = $report->get_total_activities();
				$total_activities = (empty($total_activities)) ? 0 : $total_activities;

				$session_html = '';
				$ses = $this->session;
		    $sessions = $report->get_sessions();
		    foreach ($sessions as $key => $session) {
		      $session_data = $ses->get($session->ses_id);
					$session_html .= $session_data->render();
		    }

				$activity_overview = $report->get_ativities_overview();

				$data = $activity_overview;
				$data['sessions'] = $session_html;

				$data['total_sessions'] = $total_sessions;
				$data['total_sales'] = $total_sales;
				$data['total_uniques'] = $total_uniques;
				$data['total_orders'] = $total_orders;
				$data['total_activities'] = $total_activities;
				wp_send_json($data);

			}
			public function get_report_sessions(){

				$page = (int)(sanitize_text_field($_POST['page']));
				$number = 20;
				$report = new OOReport();
				$ses = $this->session;

		    $sessions = $report->get_sessions(array('page'=>$page));
		    foreach ($sessions as $key => $session) {
		      $session_data = $ses->get($session->ses_id);
					$session_html .= $session_data->render();
		    }
				$total_sessions = $report->get_total_sessions();
				// echo $total_sessions;
				if(($number * ($page + 1)) >= $total_sessions ){
					$page = -1;
				} else {
					$page++;
				}
				wp_send_json(array('sessions'=>$session_html,'page'=>$page));
			}

			public function search_product(){
				$query = sanitize_text_field($_POST['query']);
				$args = array(
				    'posts_per_page'   => -1,
				    'orderby'          => 'title',
				    'post_type'        => 'product',
				    'post_status'      => 'publish',
						's' => $query
				);

				$products = get_posts( $args );
				$html = '';
				foreach ($products as $key => $product) {
					$pid = $product->ID;
					$product = wc_get_product( $pid ); // The WC_Product object
			    if( ! $product->is_on_sale() ){
			        $price = get_post_meta( $pid, '_price', true ); // Update active price
							$sale_price = get_post_meta($pid,'_sale_price',true);
			    } else {
						$price = get_post_meta( $pid, '_regular_price', true ); // Update active price
						$sale_price = '';
					}

					if(!empty($sale_price)){
						$price_html = wc_price($price).'-'.wc_price($sale_price);
					} else {
						$price_html = wc_price($price);
					}
					if($product->is_type( 'variable' )){

						$variations = $product->get_available_variations();
						foreach ($variations as $key => $variation) {
							// $html .='<div data-pid="'.$pid.'" data-vid="'.$vid.'" class="oo-search-result-item">';global $woocommerce;

							$cart = $woocommerce->cart;
							$vid = $variation['variation_id'];
							$v_thumb = $variation['image']['url'];
							if(!empty($v_thumb)){
								$v_thumb = '<img src="'.$v_thumb.'" />';
							} else{
								$v_thumb = get_the_post_thumbnail($pid,'thumbnail');
							}
							foreach ($variation['attributes'] as $key => $att) {
								$term = ltrim($key,'attribute_');
								// print_r($term);
								$att_term = get_term_by('id',$att,$term);
								// print_r($att_term);
								$variation_selected = $att_term->name;
							}
							$html .= '<div data-pid="'.$pid.'" data-vid="'.$vid.'" class="oo-search-result-item"> '.$v_thumb.'<h5>'.$post_title.'</h5><br />'.$variation_selected.' '.$price_html.'</div>';
						}
					} else {
						$html .='<div data-pid="'.$pid.'" data-vid="0" data-key="0" data-qty="1" class="oo-search-result-item">';
						$post_title = $product->get_title();
						$p_thumb = get_the_post_thumbnail($pid,'thumbnail');
						$html .= $p_thumb.'<h5>'.$post_title.'</h5><br />'.$price_html.'</div>';
					}
				}
				wp_send_json(array('suggestions'=>$html));
			}

			public function push_clicked(){

				$push_id = (int)(sanitize_text_field($_POST['push_id']));
				$push = new OOPush();
				$push->set_clicked($push_id);
				die();
			}

			public function oo_send_push(){
				if(wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
				{
					die('Invalid Request! Reload your page please.');
				}

				$push_type = sanitize_text_field($_POST['push_type']);
				$push_ses_id = (int)(sanitize_text_field($_POST['ses_id']));
				$push_duration = sanitize_text_field($_POST['push_duration']);
				$sale_amount = (int)(sanitize_text_field($_POST['sale_amount']));
				$sale_percent = (int)(sanitize_text_field($_POST['sale_percent']));

				$now = strtotime('now');

				if($push_duration == 'end'){
					$push_run_time = strtotime('+1 day',$now);
					$push_time_gap = $now + 86400;
				} else if($push_duration == 'fivemin'){
					$push_run_time = strtotime('+5 minutes',$now);
					$push_time_gap = $now + 300;
				} else if($push_duration == 'tenmin'){
					$push_run_time = strtotime('+10 minutes',$now);
					$push_time_gap = $now + 600;
				} else if($push_duration == 'onehour'){
					$push_run_time = strtotime('+1 hour',$now);
					$push_time_gap = $now + 3600;
				}

				$push = new OOPush();


				$args = array(
					'push_ses_id' => $push_ses_id,
					'push_run_time' =>$push_run_time,
					'push_time_gap' =>$push_time_gap,
					'push_status' => 0,
					'push_date' => $now,
				);

				if($push_type == 'sale_price'){
					$pid_str = rtrim(sanitize_text_field($_POST['pid_str']),',');
					$pids = explode(',',$pid_str);
					$args['push_args'] = serialize(array('sale_amount'=>$sale_amount,'sale_percent'=>$sale_percent));
					if(!empty($pids)){
						$args['push_type'] = 'sale_price';

						foreach ($pids as $key => $pid) {
							$args['push_pid'] = $pid;
							$args['push_xid'] = $pid;
							$push->add_push($push_ses_id,$pid,$args);
						}
					}
				} else if($push_type == 'apply_coupon'){
					$args['push_type'] = 'apply_coupon';
					$push_coupons = sanitize_text_field($_POST['push_coupons']);
					$args['push_args'] = serialize(array('coupon_code'=>$push_coupons));
					$push->add_push($push_ses_id,0,$args);
				} else if($push_type == 'open_popup'){
					$args['push_type'] = 'open_popup';
					$popup_type = sanitize_text_field($_POST['popup_type']);
					$popup_content = sanitize_text_field($_POST['popup_content']);

					$popup_btn_1_label = sanitize_text_field($_POST['oo_popup_btn_1_label']);
					$popup_btn_1_href = sanitize_text_field($_POST['oo_popup_btn_1_href']);

					$popup_btn_2_href = sanitize_text_field($_POST['oo_popup_btn_2_href']);
					$popup_btn_2_label = sanitize_text_field($_POST['oo_popup_btn_2_label']);

					$args['push_args'] = serialize(array('popup_type'=>$popup_type,'popup_content'=>$popup_content,'popup_btn_1_label'=>$popup_btn_1_label,'popup_btn_2_label'=>$popup_btn_2_label,'popup_btn_1_href'=>$popup_btn_1_href,'popup_btn_2_href'=>$popup_btn_2_href));
					$push->add_push($push_ses_id,0,$args);
				}

				wp_send_json(array('status'=>1));
			}
			public function change_cart()
			{
				global $woocommerce;

				$ses_id = (int)(sanitize_text_field($_POST['ses_id']));
				$pid_str = sanitize_text_field(rtrim($_POST['pid_str'],','));
				$vid_str = sanitize_text_field(rtrim($_POST['vid_str'],','));
				$key_str = sanitize_text_field(rtrim($_POST['key_str'],','));
				$qtys_str = sanitize_text_field(rtrim($_POST['qty_str'],','));
				//
				$ses = $this->session;
				$session_data = $ses->get_by('ses_id',$ses_id,array('ses_expired'=>'true'));

				$session_content_raw = $session_data->ses_cart_session;
				$session_obj = unserialize($session_content_raw);

				$session_key = $session_obj['key_hash'];
				$session_content = $session_obj['session'];
				$session_cart = empty($session_content['cart']) ? null : unserialize($session_content['cart']);

				$cart_item_keys = [];
				if(!empty($session_cart)){
					foreach ($session_cart as $key => $cart_item) {
						$cart_item_keys[$cart_item['key']] = $cart_item['key'];
					}



					if($pid_str == ',' || empty($pid_str)){

						foreach ($cart_item_keys as $key => $cart_item_key) {
							// if(!in_array($cart_item_key,$keys)){
								unset($session_cart[$cart_item_key]);
							// }
						}
						$result = $ses->update_actual_cart($session_key,$data);
						wp_send_json( array('status'=>$result) );
					}
					$pids = explode(',',$pid_str);
					$vids = explode(',',$vid_str);
					$keys = explode(',',$key_str);
					$qtys = explode(',',$qtys_str);


					reset($session_cart);
					$first_key = key($session_cart);
					$clone_item = $session_cart[$first_key];

					foreach ($keys as $key => $item_key) {

						// update the item
						if(in_array($item_key,$cart_item_keys)){
							$session_cart[$item_key]['quantity'] = $qtys[$key];
							unset($cart_item_keys[$item_key]);
						} else if($item_key == 0){
							$random_number = mt_rand(1111111,99999999);
							$now = time();
							$new_cart_key = wp_hash($now.'X'.$random_number);
							$product = wc_get_product($pids[$key]);
							$price = $product->get_price();
							$session_cart[$new_cart_key] = $clone_item;
							$session_cart[$new_cart_key]['key'] = $new_cart_key;
							$session_cart[$new_cart_key]['product_id'] = $pids[$key];
							if($vids[$key] > 0){
								$session_cart[$new_cart_key]['variation_id'] = $vids[$key];
							}
							$session_cart[$new_cart_key]['line_subtotal'] = $price * $qtys[$key];
							$session_cart[$new_cart_key]['line_total'] = $price * $qtys[$key];
							$session_cart[$new_cart_key]['quantity'] = $qtys[$key];
						}
					}



					// remove existing item
					foreach ($cart_item_keys as $key => $cart_item_key) {
						if(!in_array($cart_item_key,$keys)){
							unset($session_cart[$cart_item_key]);
						}
					}



					$session_content['cart'] = serialize($session_cart);
					$data = serialize($session_content);

					$result = $ses->update_actual_cart($session_key,$data);
					if(isset($result) && $result > 0){
						$status = 1;
					} else {
						$status = 0;
					}

					wp_send_json( array('status'=>$status) );
				}



			}

			public function delete_push(){
				$push_id = (int)(sanitize_text_field($_POST['push_id']));
				$push = new OOPush();
				$push->delete_push($push_id);
				die();
			}
}

new OOAjax();
