<?php
/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOActivity
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
	public $act_id;
	public $act_ses;
	public $act_ses_hash;
	public $act_uid;
	public $act_type;
	public $act_pid;
	public $act_url;
	public $act_ref;
	public $act_hits;
	public $act_xid;
	public $act_date;
	public $session;
	
	/**
	 * OOMetrics constructor.
	 * The main plugin actions registered for WordPress
	 */

	public function __construct()
  {

	}
	public function set_session($session){

		global $wpdb;
		$this->session = $session;

		$this->act_hits = 0;
		$this->act_ses = $session->ses_id;
		$this->act_ses_hash = $session->ses_hash;
		$this->act_uid = $session->ses_uid;
		$this->act_type = '';

		global $wp;
		$this->act_url = (!empty($wp->request)) ? $wp->request : str_replace('/micimart/','',$_SERVER['REQUEST_URI']);
		$this->act_url = sanitize_text_field(trim($this->act_url,'/').'/');
		$this->act_ref = $session->ses_referrer;
		$this->act_xid = get_the_ID();
		$this->act_date = time();
		$this->table = $wpdb->prefix.'oometrics_activities';
	}
	public function init($ses_hash = '')
	{
		global $wpdb;
		$session = $this->session;

		if(is_product())
		{
			$this->act_pid = get_the_ID();
		}
		else
		{
			$this->act_pid = 0;
		}

		if(!oo_is_filtered()){
			if($this->is_landed())
			{
				$this->add_landed();
				$session->add_value(1);
			} else {

				if($this->is_visit_exists()){
					$this->add_visit();
					$session->add_value(1);
				} else {
					$this->update_visit();
					$session->add_value(1);
				}
			}
		}


		$session->sync();
	}
	public function action_woocommerce_add_to_cart( $array, $int1, $int2 ) {

			$this->act_type = 'added_to_cart';

			$data['act_ses'] = $this->act_ses;
			$data['act_uid'] = $this->act_uid;
			$data['act_type'] = $this->act_type;
			$data['act_pid'] = $this->act_pid;
			$data['act_url'] = $this->act_url;
			$data['act_ref'] = $this->act_ref;
			$data['act_xid'] = $this->act_xid;
			$data['act_hits'] = $this->act_hits;
			$data['act_date'] = $this->act_date;

			$this->add_activity($data);


			$session = $this->session;

			$session->add_value(3);
	}
	public function add_activity($data = array())
	{
		global $wpdb;
		// echo $this->table.'ssssss';
		$wpdb->insert($this->table,$data);
		$this->act_id = $wpdb->insert_id;
	}
	public function update_activity($data = array())
	{
		global $wpdb;
		$wpdb->update($this->table,$data,array('act_ses'=>$this->act_ses,'act_url'=>$this->act_url));
		// $this->act_id = $wpdb->insert_id;
	}
	public function is_landed()
	{
		global $wpdb;
		$table = $this->table;
		$in_db = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE `act_ses` = '%d'",
						 $this->act_ses
				)
		);
		return ((isset($in_db) && $in_db > 0) ? false : true);
	}
	public function is_visit_exists()
	{
		global $wpdb;
		$table = $this->table;
		if(empty($this->act_url) && is_home()){
			$this->act_url = '/';
		}

		$in_db = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE act_ses = '%d' AND act_url = '%s' AND act_type = '%s'",
						 array($this->act_ses,$this->act_url,'visited')
				)
		);
		$result = ($in_db > 0) ? false : true;
		return $result;
	}
	public function add_visit()
	{
		$this->act_type = 'visited';

		$data['act_ses'] = $this->act_ses;
		$data['act_uid'] = $this->act_uid;
		$data['act_type'] = $this->act_type;
		$data['act_pid'] = $this->act_pid;
		$data['act_url'] = $this->act_url;
		$data['act_ref'] = $this->act_ref;
		$data['act_hits'] = $this->act_hits;
		$data['act_xid'] = $this->act_xid;
		$data['act_date'] = $this->act_date;
		$this->add_activity($data);
		// print_r($this);
	}
	public function update_visit()
	{
		$this->act_type = 'visited';

		global $wpdb;
		$table = $this->table;

		$in_db = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table
					   SET act_hits = act_hits + 1
						 WHERE act_ses = '%d' AND act_url = '%s' AND act_type = '%s'",
						 array($this->act_ses,$this->act_url,'visited')
				)
		);
		$result = ($in_db > 0) ? false : true;
	}
	public function add_landed()
	{
		$this->act_type = 'landed';

		$data['act_ses'] = $this->act_ses;
		$data['act_uid'] = $this->act_uid;
		$data['act_type'] = $this->act_type;
		$data['act_pid'] = $this->act_pid;
		$data['act_url'] = $this->act_url;
		$data['act_ref'] = $this->act_ref;
		$data['act_hits'] = $this->act_hits;
		$data['act_xid'] = $this->act_xid;
		$data['act_date'] = $this->act_date;
		$this->add_activity($data);

	}

	public function render($act_id = 0)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activities';
		$act = $wpdb->get_row(
				$wpdb->prepare(
						"SELECT * FROM $table
						 WHERE `act_id` = '%d'",
						 $act_id
				)
		);

		$html = '';
		if(!empty($act->act_pid)){
			$act_img = get_the_post_thumbnail($act->act_pid,'thumbnail');
			$act_title = get_the_title($act->act_pid);

		}
		$act_title = (!empty($act_title)) ? '<span class="title">'.$act_title.'</span>': '';
		$act_img = (!empty($act_img)) ? '<span class="oo-act-image">'.$act_img.'</span>' : '';
		// $act_pid = (!empty($act->act_pid)) ? '<small class="oo-act-product">'.$act->act_pid.'</small>' : '';
		$act_xid = '';//(!empty($act->act_xid)) ? '<small class="oo-act-xid">'.$act->act_xid.'</small>' : '';
		$act_url = (!empty($act->act_url)) ? '<a class="oo-act-url" target="_blank" href="'.esc_url(get_home_url().'/'.$act->act_url).'">'.urldecode($act->act_url).'</a>' : '';
		$act_hits = ($act->act_hits > 1) ? '<small class="oo-act-hits">X '.$act->act_hits.'</small>' : '';
		$act_time = human_time_diff( $act->act_date, time() );
		if(!empty($act))
		{
			$html .= '<li data-type="'.$act->act_type.'">
									<div class="oo-act-meta">
									<h5 class="oo-act-type">'.$act->act_type.' '.$act_hits.'</h5>
										'.$act_img.'
										<div class="oo-act-data">
										'.$act_xid.'
										'.$act_title.'
										'.$act_url.'
										<strong class="oo-act-time">'.$act_time.'</strong>
										</div>
									</div>
								</li>';
		}

		return $html;

	}


}
