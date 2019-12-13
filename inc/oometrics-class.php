<?php
use Jaybizzle\CrawlerDetect\CrawlerDetect;

/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOMetrics
{

	/**
	 * The security nonce
	 *
	 * @var string
	 */
	private $_nonce = 'oometrics_nonce';

	/**
	 * The option name
	 *
	 * @var string
	 */

	private $option_name = 'oometrics_options';
	public $session;

	/**
	 * OOMetrics constructor.
	 * The main plugin actions registered for WordPress
	 */

	public function __construct()
    {
			// Admin page calls
			add_action('admin_menu',                array($this,'add_admin_menu'));
			add_action('wp_ajax_oo_store_admin_data',  array($this,'store_admin_data'));
			add_action('admin_enqueue_scripts',     array($this,'add_admin_scripts'));
			add_action('wp_enqueue_scripts',     array($this,'add_scripts'));

			add_action( 'widgets_init', array($this,'ooarea_sidebar') );
	}



	/**
	 * Returns the saved options data as an array
     *
     * @return array
	 */

	private function get_data()
  {
    return get_option($this->option_name, array());
  }
  public function init()
	{

			if ( class_exists( 'WooCommerce' ) ) {

				$settings = get_option($this->option_name);

				// create or get session
				$ses = new OOSession();
				$session = $ses->get();
				$this->session = $session;
				$session->update_all();

				// Checks tracking consent permission
				if(!isset($_COOKIE['oo_tracking_consent']) || $_COOKIE['oo_tracking_consent'] == 'agreed'){
					$activity = new OOActivity();
					$activity->set_session($session);
					add_action( 'woocommerce_add_to_cart', array($activity,'action_woocommerce_add_to_cart'), 10, 3 );
					add_action( 'wp_loaded', array($activity,'init'),99);
				}



				$ajax = new OOAjax();
				$ajax->set_session($session);

				// FRONT-END and BACK-END
				add_action( 'wp_ajax_oo_update_session', array( $ajax, 'update_session' ) );
				add_action( 'wp_ajax_nopriv_oo_update_session', array( $ajax, 'update_session' ) );

				add_action( 'wp_ajax_oo_admin_update_session', array( $ajax, 'admin_update_session' ) );
				add_action( 'wp_ajax_nopriv_oo_admin_update_session', array( $ajax, 'admin_update_session' ) );

				add_action( 'wp_ajax_oo_send_message', array( $ajax, 'send_message' ) );
				add_action( 'wp_ajax_nopriv_oo_send_message', array( $ajax, 'send_message' ) );

				add_action( 'wp_ajax_oo_get_session_chats', array( $ajax, 'get_session_chats' ) );
				add_action( 'wp_ajax_nopriv_oo_get_session_chats', array( $ajax, 'get_session_chats' ) );

				add_action( 'wp_ajax_oo_mark_as_seen', array( $ajax, 'mark_as_seen' ) );
				add_action( 'wp_ajax_nopriv_oo_mark_as_seen', array( $ajax, 'mark_as_seen' ) );

				add_action( 'wp_ajax_oo_update_chat', array( $ajax, 'update_chat' ) );
				add_action( 'wp_ajax_nopriv_oo_update_chat', array( $ajax, 'update_chat' ) );

				add_action( 'wp_ajax_oo_delete_chat', array( $ajax, 'delete_chat' ) );
				add_action( 'wp_ajax_nopriv_oo_delete_chat', array( $ajax, 'delete_chat' ) );

				add_action( 'wp_ajax_oo_edit_chat', array( $ajax, 'edit_chat' ) );
				add_action( 'wp_ajax_nopriv_oo_edit_chat', array( $ajax, 'edit_chat' ) );

				add_action( 'wp_ajax_oo_chat_add_attachment', array( $ajax, 'chat_add_attachment' ) );
				add_action( 'wp_ajax_nopriv_oo_chat_add_attachment', array( $ajax, 'chat_add_attachment' ) );


				add_action( 'wp_ajax_oo_set_global_order_by', array( $ajax, 'set_global_order_by' ) );


				if(is_admin()){
					add_action( 'wp_ajax_get_live_sessions', array( $ajax, 'get_live_sessions' ) );
					add_action( 'wp_ajax_get_session', array( $ajax, 'get_session' ) );


					// PUSH
					add_action( 'wp_ajax_oo_product_search', array( $ajax, 'search_product' ) );
					add_action( 'wp_ajax_oo_send_push', array( $ajax, 'oo_send_push' ) );
					add_action( 'wp_ajax_oo_delete_push', array( $ajax, 'delete_push' ) );
					add_action( 'wp_ajax_oo_change_cart', array( $ajax, 'change_cart' ) );

					// REPORTS
					add_action( 'wp_ajax_get_report_session', array( $ajax, 'get_report_session' ) );
					add_action( 'wp_ajax_get_report_sessions', array( $ajax, 'get_report_sessions' ) );
					add_action( 'wp_ajax_get_report', array( $ajax, 'get_report' ) );
				}

					$push = new OOPush();
					$push->set_session($session);
					add_filter( 'woocommerce_product_get_price', array($push,'custom_sale_price'), 99, 2 );
					add_filter( 'woocommerce_before_calculate_totals', array($push,'add_coupon'), 99, 2 );
					add_filter( 'wp_footer', array($push,'add_popup'), 99, 2 );
					add_action( 'wp_ajax_nopriv_oo_push_clicked', array( $ajax, 'push_clicked' ) );


					if($settings['chat_enabled'] == 'yes'){
						add_action('wp_footer',     array($this,'oo_add_footer_chat_button'));
					}

					if($settings['tracking_notification'] == 'yes' && !isset($_COOKIE['oo_tracking_consent'])){
						add_action('wp_footer',     array($this,'oo_add_consent_notification'));
					}
			}

  }

	// registers a new sidebar
	public function ooarea_sidebar() {
	    register_sidebar( array(
	        'name' => __( 'OOArea Sidebar', 'oometrics' ),
	        'id' => 'ooarea-1',
	        'description' => __( 'Widgets in this area will be shown as pushed popup content', 'oometrics' ),
	        'before_widget' => '<div id="%1$s" class="%2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h2 class="oo-widge-ttitle">',
					'after_title'   => '</h2>',
				    ) );
	}

	// saving admin settings data
	public function store_admin_data()
    {

		if (wp_verify_nonce(sanitize_text_field($_POST['security']), $this->_nonce ) === false)
			die('Invalid Request! Reload your page please.');

		$data = $this->get_data();

		foreach ($_POST as $field=>$value) {
		    if (substr($field, 0, 10) !== "oometrics_")
				continue;

		    if (empty($value))
		        unset($data[$field]);

		    // We remove the oometrics_ prefix to clean things up
		    $field = substr($field, 10);

			$data[$field] = esc_attr__(sanitize_text_field($value));

		}

		update_option($this->option_name, $data);

		echo esc_html(__('Settings saved successfully!', 'oometrics'));
		die();

	}

	/**
	 * Adds Admin Scripts for the Ajax call
	 */
	public function add_admin_scripts()
    {
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-datepicker');
			$screen = get_current_screen();
			if($screen->id == 'oometrics_page_oometrics-reports' || $screen->id == 'toplevel_page_oometrics'){
			  wp_enqueue_style('oometrics-admin', OOMETRICS_URL. 'assets/css/admin.css', false, '1.0.4');
			}

			if($screen->id == 'toplevel_page_oometrics'){
				wp_enqueue_script('oometrics-admin', OOMETRICS_URL. 'assets/js/admin.js', array('jquery'), '1.0.4');
				wp_enqueue_script('oometrics-chats', OOMETRICS_URL. 'assets/js/admin-chats.js', array('jquery'), '1.0.3');
			}

			if($screen->id == 'oometrics_page_oometrics-reports'){
				wp_enqueue_script('oometrics-reports', OOMETRICS_URL. 'assets/js/admin-reports.js', array('jquery'), '1.0.3');
			}

			if($screen->id == 'oometrics_page_oometrics-settings'){
				wp_enqueue_style('oometrics-admin', OOMETRICS_URL. 'assets/css/admin-settings.css', false, '1.0.4');
				wp_enqueue_script('oometrics-admin', OOMETRICS_URL. 'assets/js/admin-settings.js', array('jquery'), '1.0.4');
			}


			$settings = get_option('oometrics_options');
			$admin_interval = (!empty($settings['admin_interval'])) ? $settings['admin_interval'] : 10000;
			$session_interval = (!empty($settings['session_interval'])) ? $settings['session_interval'] : 10000;
			$chat_interval = (!empty($settings['chat_interval'])) ? $settings['chat_interval'] : 5000;
			$admin_options = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'interval' => $admin_interval,
				'session_interval' => $session_interval,
				'chat_interval' => $chat_interval,
				'delay' => 1000,
				'_nonce'   => wp_create_nonce( $this->_nonce ),
			);

			wp_localize_script('jquery', 'oometrics', $admin_options);

	}

	public function add_scripts()
    {
			wp_enqueue_style('oometrics-style', OOMETRICS_URL. 'assets/css/oometrics.css', false, '1.0.2');
			wp_enqueue_script('oometrics-script', OOMETRICS_URL. 'assets/js/oometrics.js', array('jquery'), '1.0.3');
			wp_enqueue_script('oometrics-chats', OOMETRICS_URL. 'assets/js/chats.js', array('jquery'), '1.0.3');

			$settings = get_option('oometrics_options');
			$session_interval = (!empty($settings['session_interval'])) ? $settings['session_interval'] : 10000;
			$chat_interval = (!empty($settings['chat_interval'])) ? $settings['chat_interval'] : 5000;

			$options = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'session_interval' => $session_interval,
				'chat_interval' => $chat_interval,
				'delay' => 1000,
				'_nonce'   => wp_create_nonce( $this->_nonce ),
			);

			wp_localize_script('oometrics-script', 'oometrics', $options);

	}

	/**
	 * Adds the OOMetrics label to the WordPress Admin Sidebar Menu
	 */
	public function add_admin_menu()
    {
		add_menu_page(
			__( 'OOMetrics', 'oometrics' ),
			__( 'OOMetrics', 'oometrics' ),
			'manage_options',
			'oometrics',
			array($this, 'dashboard_layout'),
			OOMETRICS_URL.'/assets/images/oometrics-dashicon.svg',
			2
		);
    add_submenu_page(
      'oometrics',
			__( 'Reports', 'oometrics' ),
			__( 'Reports', 'oometrics' ),
			'manage_options',
			'oometrics-reports',
			array($this, 'reports_layout'),
			'dashicons-analytics'
		);
		add_submenu_page(
      'oometrics',
			__( 'Settings', 'oometrics' ),
			__( 'Settings', 'oometrics' ),
			'manage_options',
			'oometrics-settings',
			array($this, 'settings_layout'),
			'dashicons-analytics'
		);
		// add_submenu_page(
    //   'oometrics',
		// 	__( 'Debug', 'oometrics' ),
		// 	__( 'Debug', 'oometrics' ),
		// 	'manage_options',
		// 	'oometrics-debug',
		// 	array($this, 'debug_layout'),
		// 	'dashicons-analytics'
		// );
	}

	/**
     * Get a Dashicon for a given status
     *
	 * @param $valid boolean
     *
     * @return string
	 */
    private function get_status_icon($valid)
    {

        return ($valid) ? '<span class="dashicons dashicons-yes success-message"></span>' : '<span class="dashicons dashicons-no-alt error-message"></span>';

    }

	/**
	 * Outputs the Admin Dashboard layout containing the form with all its options
     *
     * @return void
	 */
   public function dashboard_layout()
     {
       require_once(OOMETRICS_PATH.'/templates/dashboard/dashboard.php');
     }
	public function settings_layout()
    {

		$data = $this->get_data();


	    $has_wc = (class_exists('WooCommerce'));
      require_once(OOMETRICS_PATH.'/templates/settings.php');
	}

	public function reports_layout()
    {

		$data = $this->get_data();


	    $has_wc = (class_exists('WooCommerce'));
      require_once(OOMETRICS_PATH.'/templates/reports/dashboard.php');
	}
	public function debug_layout()
    {

      require_once(OOMETRICS_PATH.'/templates/debug.php');
	}



	public function oo_add_footer_chat_button() {
		$settings = get_option('oometrics_options');
		$session = $this->session;
		$ses_id = $session->ses_id;
		$chat = new OOChat();
		$chats = $chat->get_conversations(true,array('ses_id'=>$session->ses_id));
		// print_r($session);
		$admin_ses = $session->get_by('ses_uid',$session->receiver_id,array('ses_expired'=>'true'));
		$profile = $session->render_profile($settings['main_user'],true);
		$crel = $session->get_active_rel_by_ses_id($session->ses_id,$admin_ses->ses_id);
		$rel_id = $crel->crel_id;
		$rel_id = (!empty($rel_id)) ? $rel_id : -1;

		$icon_img = OOMETRICS_URL.'/assets/images/start-chat.svg';
	    echo '
			<input id="oo_ses_id" value="'.$ses_id.'" type="hidden"/>
			<input id="oo_chat_rel_id" value="'.$rel_id.'" type="hidden"/>
			<input id="oo_admin_ses_id" value="'.$admin_ses->ses_id.'" type="hidden"/>
			<div id="oometrics-chat">
				<button id="oo-chat-trigger" title="'.__('Ask Something').'"><i class="oo-icon start-chat"><img src="'.$icon_img.'" /></i><span class="oo-badge"></span></button>
				<div class="oo-chat-wrapper oo-box">
					<header>'.$profile.'</header>
						<div class="oo-chat-conversations">
						  <ul class="oo-chat-list">
								<li class="oo-chat-start">
									<div class="oo-start-inner">
										'.__('Talk to us!','oometrics').'
									</div>
								</li>
						    '.$chats.'
						  </ul>
						</div>
					<footer>
					<textarea id="oo-message-text"></textarea>
					<button id="oo-send-message">'.__('Send','oometrics').'</button>
					</footer>
				</div>
			</div>
			';
			// $session->add_activity_init();
	}

	public function oo_add_consent_notification() {
		$settings = get_option('oometrics_options');
		?>
		<div id="oo-popup-wrapper" class="consent">
			<div class="oo-overlay"></div>
			<div class="oo-inner">
				<div class="oo-inner-content">
					<?php echo $settings['tracking_message'];?>
				</div>
				<br />
				<button type="button" class="button button-primary" id="oo-i-agree"><?php _e('Yes','oometrics');?></button>
				<a id="oo-i-disagree" href="#"><?php _e('No','oometrics');?></a>

			</div>
		</div>
		<script>
		jQuery(document).ready(function($){
			jQuery('#oo-popup-wrapper').addClass('show');
			$(document).delegate('#oo-i-agree','click',function(){
				oo_set_cookie('oo_tracking_consent','agreed',365);
		    $('#oo-popup-wrapper').removeClass('show');
		  });
			$(document).delegate('#oo-i-disagree','click',function(){
				oo_set_cookie('oo_tracking_consent','disagreed',7);
		    $('#oo-popup-wrapper').removeClass('show');
		  });
		});
		</script>
		<?php
	}
}
