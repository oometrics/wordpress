<?php
if(!class_exists('Mobile_Detect')){
	require_once('mobile-detect.php');
}
class OOHelper
{

	private $ses_hash;
	private $user_id;
	private $logged;

	public function __construct(){

	}

	public function get_request_info($args = array())
	{
		$info = [];
		// $server = $_SERVER;

		// getting correct IP
		$ip_tmp = $_SERVER['SERVER_ADDR'];
		if(empty($ip_tmp)){
			$info['ip'] = $ip_tmp;
		}
		else
		{
			$info['ip'] = $_SERVER['REMOTE_ADDR'];
			$info['ip'] = !empty($info['ip']) ? $info['ip'] : '-';
		}

		// getting correct IP
		$info['referer'] = isset($_SERVER['HTTP_REFERER']) ? urldecode($_SERVER['HTTP_REFERER']) : '-';
		$info['resolution'] = '-';




		return $info;
	}
	public function get_device_info($args = array())
	{
		// User device type: Mobile, Tablet, Desktop
		$detect = new Mobile_Detect();
		$info = [];

		// Any mobile device (phones or tablets).
		if ( $detect->isMobile() && !$detect->isTablet() )
		{
		 	$info['device'] = 'mobile';
		}
		else if ( $detect->isTablet() )
		{
		 	$info['device'] = 'tablet';
		}
		else
		{
			$info['device'] = 'desktop';
		}

		// Detect device brand
		if($detect->isiPhone()) $info['brand'] = 'iphone';
		else if($detect->isBlackBerry()) $info['brand'] = 'blackberry';
		else if($detect->isHTC()) $info['brand'] = 'htc';
		else if($detect->isNexus()) $info['brand'] = 'nexus';
		else if($detect->isiPhone()) $info['brand'] = 'iphone';
		else if($detect->isDell()) $info['brand'] = 'dell';
		else if($detect->isMotorola()) $info['brand'] = 'motorola';
		else if($detect->isSamsung()) $info['brand'] = 'samsung';
		else if($detect->isLG()) $info['brand'] = 'lg';
		else if($detect->isSony()) $info['brand'] = 'sony';
		else if($detect->isAsus()) $info['brand'] = 'asus';
		else $info['brand'] = 'etc';

		// Detect browser
		if($detect->isChrome()) $info['browser'] = 'chrome';
		else if($detect->isFirefox()) $info['browser'] = 'firefox';
		else if($detect->isIE()) $info['browser'] = 'ie';
		else if($detect->isSafari()) $info['browser'] = 'safari';
		else if($detect->isOper()) $info['browser'] = 'opera';
		else $info['browser'] = 'etc';

		// $ses = new OOSession();
		// $session = $ses->get();
		// $info['resolution'] = $session->ses_resolution;
		return $info;
	}
	public function get_customer_info($args = array())
	{

	}

}
