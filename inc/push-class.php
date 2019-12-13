<?php
/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOPush
{

	/**
	 * The security nonce
	 *
	 * @var string
	 */
	private $_nonce = 'oometrics_adminnonce';

	/**
	 * The option name
	 *
	 * @var string
	 */

	private $option_name = 'oometrics_options';
	public $table;
	public $start_time;
	public $end_time;
	public $ses_id;
	public $act_id;
	public $sortby;
	public $session;

	/**
	 * OOMetrics constructor.
	 * The main plugin actions registered for WordPress
	 */

	public function __construct()
  {

		global $wpdb;
			$this->table = $wpdb->prefix.'oometrics_pushes';

		}

		public function set_session($session){
			$this->session = $session;
			$this->ses_id = $session->ses_id;
		}
		public function add_push($ses_id,$pid,$args = array()){
			global $wpdb;

			$now = time();
			$push_type = empty($args['push_type']) ? 'sale_price' : $args['push_type'];
			$push_time_gap = !empty($args['push_time_gap']) ? $args['push_time_gap'] : 180;
			$push_pid = !empty($args['push_pid']) ? $args['push_pid'] : 0;
			$push_xid = !empty($args['push_xid']) ? $args['push_xid'] : 0;
			$push_run_time = !empty($args['push_run_time']) ? ($now+$push_time_gap) : 0;
			$push_status = !empty($args['push_status']) ? $args['push_status'] : 0;
			$push_args = !empty($args['push_args']) ? $args['push_args'] : '';
			$push_params = !empty($args['push_params']) ? $args['push_params'] : '';
			$push_alt = !empty($args['push_alt']) ? $args['push_alt'] : '';
			$push_date = !empty($args['push_date']) ? $args['push_date'] : $now;

			$data['push_ses_id'] = $ses_id;
			$data['push_type'] = $push_type;
			$data['push_pid'] = $push_pid;
			$data['push_xid'] = $push_xid;
			$data['push_run_time'] = $push_run_time;
			$data['push_time_gap'] = $push_time_gap;
			$data['push_status'] = $push_status;
			$data['push_args'] = $push_args;
			$data['push_params'] = $push_params;
			$data['push_alt'] = $push_alt;
			$data['push_date'] = $push_date;

			$wpdb->insert($this->table,$data);
			return $wpdb->insert_id;
		}

		public function delete_push($push_id){
			global $wpdb;
			$seen = $wpdb->delete($this->table,array('push_id'=>$push_id));
			return $seen;
		}

		public function get_pushes($push_id = 0,$ses_id = 0,$status = 0){
			global $wpdb;
			$now = time();
			$pushes = $wpdb->get_results(
				$wpdb->prepare(
						"SELECT * FROM {$this->table}
						 WHERE push_id = '%d' AND push_ses_id = '%d' AND push_status = '%d'",
						 array($push_id,$ses_uid,$status)
				)
			);
			return $pushes;
		}

		public function get_session_sale_price_push($ses_id = 0,$pid = 0,$status = 0){
			global $wpdb;
			$now = time();
			$pushes = $wpdb->get_row(
				$wpdb->prepare(
						"SELECT * FROM {$this->table}
						 WHERE push_type = '%s' AND push_pid = '%d' AND push_ses_id = '%d' AND push_status = '%d'",
						 array('sale_price',$pid,$ses_id,$status)
				)
			);
			return $pushes;
		}
		public function get_session_apply_coupon_push($ses_id = 0){
			global $wpdb;
			$now = time();
			$pushes = $wpdb->get_row(
				$wpdb->prepare(
						"SELECT * FROM {$this->table}
						 WHERE push_type = '%s' AND push_ses_id = '%d' AND push_status = '%d'",
						 array('apply_coupon',$ses_id,0)
				)
			);
			return $pushes;
		}
		public function get_session_open_popup_push($ses_id = 0){
			global $wpdb;
			$now = time();
			$pushes = $wpdb->get_row(
				$wpdb->prepare(
						"SELECT * FROM {$this->table}
						 WHERE push_type = '%s' AND push_ses_id = '%d' AND push_status = '%d'",
						 array('open_popup',$ses_id,0)
				)
			);
			return $pushes;
		}

		public function change_status($push_id,$status = 0){
			global $wpdb;
			$pushes = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE {$this->table}
						 SET push_status = '%d'
						 WHERE push_id = '%d'",
						 array($status,$push_id)
				)
			);
			return $pushes;
		}

		public function set_clicked($push_id){
			global $wpdb;
			// $table = $wpdb->prefix.'oometrics_pushes';
			$pushes = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE {$this->table}
						 SET push_clicked = '%d'
						 WHERE push_id = '%d'",
						 array(1,$push_id)
				)
			);
			return $pushes;
		}

		public function run_push($pushes){
			global $wpdb;
			$now = time();
			// $pushes = $this->get_pushes($push_id,$ses_uid);

			foreach ($pushes as $key => $push) {
				if($push->push_type == 'sale_price'){

				}

			}
			return $pushes;
		}
		## This goes outside the constructor ##

		// Utility function to change the prices with a multiplier (number)
		public function get_price_multiplier() {
		    return 2; // x2 for testing
		}

	public function custom_sale_price( $price, $product ) {
			$now = time();
			$current_pid = $product->get_id();
			$ses_push = $this->get_session_sale_price_push($this->ses_id,$current_pid,0);

			if(!empty($ses_push)){
				if($ses_push->push_time_gap < $now ){
					$this->change_status($ses_push->push_id,1);
					return true;
				}
				$args = unserialize($ses_push->push_args);
				// print_r($args);
				$sale_amount = $args['sale_amount'];
				$sale_percent = $args['sale_percent'];
				if(!empty($sale_percent)){
					$new_price = ( $sale_percent * $price ) / 100;
					$new_price = $price - $new_price;
				} else {
					$new_price = $price - $sale_amount;
				}
				$product->set_sale_price($new_price);
				return $new_price;
			}	else {
				return $price;
		}
}

		public function custom_variable_price( $price, $variation, $product ) {
		    return $price * $this->get_price_multiplier();
		}

		public function add_price_multiplier_to_variation_prices_hash( $hash ) {
		    $hash[] = $this->get_price_multiplier();
		    return $hash;
		}
		public function add_coupon($cart){
			global $woocommerce;
			$now = time();
			$ses_push = $this->get_session_apply_coupon_push($this->ses_id);
			if(!empty($ses_push)){
				$args = unserialize($ses_push->push_args);
				$coupon_code = $args['coupon_code'];
				if($ses_push->push_time_gap < $now ){
					if($woocommerce->cart->has_discount(sanitize_text_field($coupon_code))){
						$woocommerce->cart->remove_coupons(sanitize_text_field($coupon_code));
					}
					$this->change_status($ses_push->push_id,1);
					return true;
				}
				$args = unserialize($ses_push->push_args);

				if(!empty($coupon_code)){
					if(!$woocommerce->cart->has_discount(sanitize_text_field($coupon_code))){
						$woocommerce->cart->add_discount( sanitize_text_field( $coupon_code ));
					}
				}
			}

		}

		public function add_popup(){
			$ses_push = $this->get_session_open_popup_push($this->ses_id);
			if(!empty($ses_push)){
				if($ses_push->push_time_gap < $now ){
					$this->change_status($ses_push->push_id,1);
					return true;
				}
				$args = unserialize($ses_push->push_args);

				$popup_type = $args['popup_type'];
				$popup_content = stripslashes($args['popup_content']);
				$popup_btn_1_label = $args['popup_btn_1_label'];
				$popup_btn_2_label = $args['popup_btn_2_label'];
				$popup_btn_1_href = $args['popup_btn_1_href'];
				$popup_btn_2_href = $args['popup_btn_2_href'];

				if($popup_type == 'promotional'){
					$this->render_promotianl_popup($ses_push->push_id,$popup_content,false,$args);
					$this->change_status($ses_push->push_id,1);
				} else if($popup_type == 'register'){
					$this->render_register_popup($ses_push->push_id);
					$this->change_status($ses_push->push_id,1);
				} else if($popup_type == 'ooarea'){
					$this->render_ooarea_popup($ses_push->push_id);
					$this->change_status($ses_push->push_id,1);
				}
				?>
				<script>
				jQuery(document).ready(function($){
					setTimeout(function(){
						$('#oo-popup-wrapper').addClass('show');
					},3000);


				});
				</script>
				<?php
			}

		}

		public function render_promotianl_popup($push_id,$popup_content,$html = false,$args = array()){
			if(!empty($args)){
				$actoin_html = '<div class="oo-popup-action">';
				$popup_btn_1_label = $args['popup_btn_1_label'];
				$popup_btn_1_href = $args['popup_btn_2_href'];
				if(!empty($popup_btn_1_label)){
					$actoin_html .='<a href="'.$popup_btn_1_href.'" class="oo-popup-action-primary button">'.$popup_btn_1_label.'</a>';
				}

				$popup_btn_2_href = $args['popup_btn_2_href'];
				$popup_btn_2_label = $args['popup_btn_2_label'];
				if(!empty($popup_btn_2_label)){
					$actoin_html .='<a href="'.$popup_btn_2_href.'" class="oo-popup-action-secondary button">'.$popup_btn_2_label.'</a>';
				}
			}
			$actoin_html .= '</div>';
			$html_content = '
			<div id="oo-popup-wrapper" data-pushid="'.$push_id.'">
				<div class="oo-overlay"></div>
				<div class="oo-inner">
					'.$popup_content.'
					<span class="oo-popup-close"><img src="'.OOMETRICS_URL.'/assets/images/close-popup.svg"/></span>';
				if(!empty($args)){
					$html_content .=$actoin_html;
				}


				$html_content .='</div>
			</div>';

			if($html){
				return $html_content;
			} else {
				echo $html_content;
			}

		}

		public function render_register_popup($push_id,$html = false){
			if(!is_user_logged_in()){
			$html_content = '
			<div id="oo-popup-wrapper" data-pushid="'.$push_id.'">
				<div class="oo-overlay"></div>
				<div class="oo-inner">
					<div class="oo-popup-login active">
						<h3>'.__('Please Login','oometrics').'</h3>
						<div class="oo-form-field">
							<label for="oo-login-username" >'.__('Username','oometrics').'</label>
							<input type="text" id="oo-login-username" placeholder="'.__('or Email','oometrics').'">
						</div>
						<div class="oo-form-field">
							<label for="oo-login-passwrod" >'.__('Password','oometrics').'</label>
							<input type="password" id="oo-login-passwrod" placeholder="'.__('******','oometrics').'">
						</div>
						<button type="button" class="button button-primary" id="oo-popup-login">'.__('Login','oometrics').'</button>
						<a href="#" id="oo-show-register">'.__('or Register','oometrics').'</a>
					</div>
					<div class="oo-popup-register">
						<h3>'.__('Please Register','oometrics').'</h3>
						<div class="oo-form-field">
							<label for="oo-register-username" >'.__('Username','oometrics').'</label>
							<input type="text" id="oo-register-username" placeholder="'.__('or Email','oometrics').'">
						</div>
						<div class="oo-form-field">
							<label for="oo-register-passwrod" >'.__('Password','oometrics').'</label>
							<input type="password" id="oo-register-passwrod" placeholder="'.__('******','oometrics').'">
						</div>
						<button type="button" class="button button-primary" id="oo-popup-login">'.__('Register','oometrics').'</button>
						<a href="#" id="oo-show-login">'.__('or Login','oometrics').'</a>
					</div>
					<span class="oo-popup-close"><img src="'.OOMETRICS_URL.'/assets/images/close-popup.svg"/></span>
				</div>

			</div>';
			if($html){
				return $html_content;
			} else {
				echo $html_content;
			}
			}
		}

		public function render_ooarea_popup($push_id,$html = false){

			$html_content = '<div id="oo-popup-wrapper" data-pushid="'.$push_id.'">
				<div class="oo-overlay"></div>
				<div class="oo-inner">';
					ob_start();
					dynamic_sidebar( 'ooarea-1' );
					$html_content .= ob_get_contents();
					ob_end_clean();
					$html_content .='<span class="oo-popup-close"><img src="'.OOMETRICS_URL.'/assets/images/close-popup.svg"/></span>
				</div>

			</div>';
			if($html){
				return $html_content;
			} else {
				echo $html_content;
			}

		}
}

// new OOPush();
