<?php
/**
 * Plugin Name:       OOMetrics
 * Description:       WooCommerce Smart Metrics and Live Customer Channel; Set discounts, coupons and pop ups remotely and individually while you are watching statistics!
 * Version:           1.0.1
 * Author:            OOMetrics
 * Author URI:        https://oometrics.com
 * Text Domain:       oometrics
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/oodeveloper/oometrics
 */


/*
 * Plugin constants
 */

// Crawler Detect
use Jaybizzle\CrawlerDetect\CrawlerDetect;

if(!defined('OOMETRICS_PLUGIN_VERSION'))
	define('OOMETRICS_PLUGIN_VERSION', '1.0.0');
if(!defined('OOMETRICS_URL'))
	define('OOMETRICS_URL', plugin_dir_url( __FILE__ ));
if(!defined('OOMETRICS_PATH'))
	define('OOMETRICS_PATH', plugin_dir_path( __FILE__ ));

// Crawler Detect libraries
if(!class_exists('Jaybizzle\\CrawlerDetect\\CrawlerDetect')){
	require_once(OOMETRICS_PATH.'/inc/Fixtures/AbstractProvider.php');
	require_once(OOMETRICS_PATH.'/inc/Fixtures/Crawlers.php');
	require_once(OOMETRICS_PATH.'/inc/Fixtures/Exclusions.php');
	require_once(OOMETRICS_PATH.'/inc/Fixtures/Headers.php');
	require_once(OOMETRICS_PATH.'/inc/crawlerdetect.php');
}
// oometrics libraries
require_once(OOMETRICS_PATH.'/inc/oometrics-class.php');
require_once(OOMETRICS_PATH.'/inc/session-class.php');
require_once(OOMETRICS_PATH.'/inc/activity-class.php');
require_once(OOMETRICS_PATH.'/inc/chat-class.php');
require_once(OOMETRICS_PATH.'/inc/helper-class.php');
require_once(OOMETRICS_PATH.'/inc/ajax-class.php');
require_once(OOMETRICS_PATH.'/inc/report-class.php');
require_once(OOMETRICS_PATH.'/inc/push-class.php');



require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

register_activation_hook( __FILE__, 'do_on_activation');
// register_deactivation_hook( __FILE__, array($this,'do_on_deactivation') );
register_uninstall_hook( __FILE__, 'do_on_uninstallation' );
function do_on_activation()
	{
		// Require WooCommerce plugin
	  if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) and current_user_can( 'activate_plugins' ) ) {
			 // Stop activation redirect and show error
			 wp_die('Sorry, but this plugin requires the WooCommerce Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
	  }

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$session_table_name = $wpdb->prefix . "oometrics_sessions";
		$activity_table_name = $wpdb->prefix . "oometrics_activities";
		$push_table_name = $wpdb->prefix . "oometrics_pushes";
		$chat_table_name = $wpdb->prefix . "oometrics_chats";
		$rel_table_name = $wpdb->prefix . "oometrics_chats_rel";
		$sql = $wpdb->prepare("CREATE TABLE $session_table_name (
			ses_id bigint(20) NOT NULL AUTO_INCREMENT,
		  ses_hash varchar(1000) NOT NULL DEFAULT '',
		  ses_uid bigint(20) NOT NULL DEFAULT '0',
		  ses_value tinyint(4) NOT NULL DEFAULT '0',
		  ses_device tinytext,
		  ses_device_brand tinytext,
		  ses_browser tinytext,
		  ses_resolution tinytext,
		  ses_ip tinytext,
		  ses_referrer tinytext,
		  ses_cart_session text CHARACTER SET utf8 COLLATE utf8_general_ci,
		  ses_expired tinyint(4) NOT NULL DEFAULT '0',
		  ses_label varchar(1000) DEFAULT NULL,
		  ses_date bigint(20) NOT NULL DEFAULT '0',
		  ses_last_act bigint(20) NOT NULL DEFAULT '0',
		  ses_debug text NULL,
		  PRIMARY KEY  (ses_id)
		) $charset_collate;

		CREATE TABLE $activity_table_name (
			act_id bigint(20) NOT NULL AUTO_INCREMENT,
			act_ses bigint(20) NOT NULL,
			act_uid bigint(20) NOT NULL DEFAULT '0',
			act_type varchar(250) NOT NULL DEFAULT '',
			act_pid bigint(20) DEFAULT NULL,
			act_url tinytext,
			act_ref tinytext,
			act_xid tinytext,
			act_hits int(11) NOT NULL DEFAULT '0',
			act_date bigint(20) NOT NULL DEFAULT '0',
		  PRIMARY KEY  (act_id)
		) $charset_collate;

		CREATE TABLE $chat_table_name (
		 chat_id bigint(20) NOT NULL AUTO_INCREMENT,
		 chat_sender_id int(11) DEFAULT '0',
		 chat_receiver_id int(11) DEFAULT '0',
		 chat_ses_id bigint(20) NOT NULL DEFAULT '0',
		 chat_rel_id bigint(20) NOT NULL DEFAULT '0',
		 chat_content text CHARACTER SET utf8 COLLATE utf8_general_ci,
		 chat_content_before text CHARACTER SET utf8 COLLATE utf8_general_ci,
		 chat_attachments varchar(1000) DEFAULT NULL,
		 chat_status int(11) NOT NULL DEFAULT '0' COMMENT '0=unknown, 1=sent,2=delivered,3=seen',
		 chat_edited tinyint(4) DEFAULT NULL,
		 chat_date bigint(20) NOT NULL,
		  PRIMARY KEY  (chat_id)
		) $charset_collate;
		CREATE TABLE $push_table_name (
		  `push_id` int(11) NOT NULL AUTO_INCREMENT,
		  `push_ses_id` int(11) NOT NULL,
		  `push_type` varchar(100) NOT NULL,
		  `push_pid` int(11) DEFAULT '0',
		  `push_xid` int(11) DEFAULT '0',
		  `push_run_time` int(11) NOT NULL,
		  `push_time_gap` int(11) DEFAULT NULL,
		  `push_status` tinyint(4) NOT NULL DEFAULT '0',
		  `push_clicked` tinyint(4) DEFAULT '0',
		  `push_args` text NOT NULL,
		  `push_params` text,
		  `push_alt` text,
		  `push_date` int(11) DEFAULT NULL,
			 PRIMARY KEY  (push_id)
		) $charset_collate;

		CREATE TABLE $rel_table_name (
			crel_id bigint(20) NOT NULL AUTO_INCREMENT,
		  crel_sender_ses_id bigint(20) NOT NULL,
		  crel_receiver_ses_id bigint(20) NOT NULL,
		  crel_date bigint(20) NOT NULL DEFAULT '0',
		  PRIMARY KEY  (crel_id)
		) $charset_collate;
		",array());
		// update_option('oodebug',$sql);


    // $wpdb->query($sql);
		dbDelta($sql);

		add_option('oometrics_session_table','created');
		add_option('oometrics_activity_table','created');
		add_option('oometrics_chat_table','created');
		add_option('oometrics_push_table','created');
		add_option('oometrics_rel_table','created');
		// add_option('oometrics_debug_table','created');
	}

	function do_on_uninstallation()
	{
		global $wpdb;

		$session_table_name = $wpdb->prefix . "oometrics_sessions";
		$activity_table_name = $wpdb->prefix . "oometrics_activities";
		$chat_table_name = $wpdb->prefix . "oometrics_chats";
		$push_table_name = $wpdb->prefix . "oometrics_pushes";
		$rel_table_name = $wpdb->prefix . "oometrics_chats_rel";
		// $debug_table_name = $wpdb->prefix . "oometrics_debug";

		$sql = $wpdb->prepare("DROP TABLE  $session_table_name, $chat_table_name, $activity_table_name,$rel_table_name,$push_table_name",array());
		$wpdb->query( $sql );

		// dbDelta( $sql );

		delete_option('oometrics_session_table');
		delete_option('oometrics_activity_table');
		delete_option('oometrics_push_table');
		delete_option('oometrics_chat_table');
		delete_option('oometrics_rel_table');
		// delete_option('oometrics_debug_table');
		//global options
		delete_option('oometrics_options');
	}

	// remove bot and crawler requests
	function oo_is_bot()
	{

		$CrawlerDetect = new CrawlerDetect();

		 // Check the user agent of the current 'visitor'
		 if($CrawlerDetect->isCrawler()) {
			 return true;
		 }

		if(
			empty($_SERVER['HTTP_USER_AGENT']) ||
			preg_match('/bot|crawl|spider|mediapartners|slurp|patrol/i', $_SERVER['HTTP_USER_AGENT'])
		)
		{
			return true;
		}
		return false;

	}


	// remove filtered request like cronjobs, ajax requests and manually added URLs
	function oo_is_filtered()
	{

		// echo $_SERVER['HTTP_REFERER'];
		 $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';
		 if(isset($referer) && preg_match('/cron|cronjob|wp_cron|get_refreshed_fragments|ajax/i', $referer))
		 {
			 return true;
		 }
		 $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-';
		 if(isset($url) && preg_match('/cron|cronjob|wp_cron|get_refreshed_fragments|ajax/i', $url))
		 {
			 return true;
		 }

		 if(stripos($url, "xmlrpc.php") !== false){ // || stripos($url, "removed_item") !== false){
			 return true;
		 }

		 return false;
	}

	if(!oo_is_bot()){
		// run when everything is set after activation
		$session_table_status = get_option('oometrics_session_table','created');
		$activity_table_status = get_option('oometrics_activity_table','created');
		$chat_table_status = get_option('oometrics_chat_table','created');
		$push_table_status = get_option('oometrics_push_table','created');
		$rel_table_status = get_option('oometrics_rel_table','created');
		// $debug_table_name = get_option('oometrics_debug_table','created');

		if($session_table_status == 'created' && $activity_table_status == 'created' && $chat_table_status == 'created' && $push_table_status == 'created' && $rel_table_status == 'created'){
			$settings = get_option('oometrics_options');

			$now = time();
			if(empty($settings)){
				$settings = array(
					'main_user' => get_current_user_id(),
					'admin_interval' => 10000,
					'chat_interval' => 2000,
					'session_interval' => 5000,
					'session_lifetime' => 300, //ini_get("session.gc_maxlifetime") ? ini_get("session.gc_maxlifetime") : 600,
					'session_value_base' => 15,
					'clean_zero_values' => 'yes',
					'live_sort_by' => 'ses_last_act',
					'chat_editor' => 'simple',
					'chat_enabled' => 'no',
					'tracking_notification' => 'no',
					'tracking_message' => __('For better shopping experience, we will collect none personal data...','oometrics'),
					'period_time' => array(
						'period_type' => 'last_week',
	          'start_time' => $now - 604800,
	          'end_time' => $now
					)
				);
				update_option('oometrics_options',$settings);
			}
				add_action('init',array(new OOMetrics(),'init'),100);
		}
	}
