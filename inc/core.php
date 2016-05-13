<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class Gwptb_Core {
	
	private static $instance = NULL; //instance store
	private $options = NULL;
		
	private function __construct() {
				
		add_action('init', array($this,'custom_query_vars') );
		add_action('template_redirect', array($this, 'custom_templates_redirect'));
		add_action('gwptb_service',  array($this, 'service_process'));
		add_action('gwptb_update',  array($this, 'webhook_update_process'));
		
	}
	
	
	/** instance */
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }       
	
	static function on_activation() {
		update_option('gwptb_permalinks_flushed', 0);  
		//self::create_table();
	}
	
	static function on_deactivation() {
		update_option('gwptb_permalinks_flushed', 0);  
	}
	
	static function create_table(){
		global $wpdb;			
		
		$table_name = self::get_log_tablename();
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
			$charset_collate = $wpdb->get_charset_collate();
	
			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				object tinytext NOT NULL,
				status varchar(255) NOT NULL,
				data text DEFAULT '' NOT NULL,
				tlgrm_id bigint(20) DEFAULT 0 NOT NULL,
				connected_id bigint(20) DEFAULT 0 NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";		

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}
	
	static function get_log_tablename(){
		global $wpdb;
		
		return $wpdb->prefix . 'gwptb_log';
	}
	
	/** options **/
	public function get_option_value($key){
		
		$value = '';
		
		if(empty($key))
			return $value;
		
		if(NULL === $this->options){
			$this->options = get_option('gwptb_settings');
		}
		
		if(isset($this->options[$key]))
			$value = $this->options[$key];
			
		return $value;
	}
	
	public function get_options(){
		
		if(NULL == $this->options){
			$this->options = get_option('gwptb_settings', array());
		}
		
		return $this->options;
	}
	
	
	/** service page */	
	public function custom_query_vars(){
        global $wp;
        
        $wp->add_query_var('gwptb');
		
		//url domain.com/teplobot/service/
		add_rewrite_rule('^gwptb/([^/]*)/?', 'index.php?gwptb=$matches[1]', 'top');
		
		//flush permalinks
		if( !get_option('gwptb_permalinks_flushed') ) {
			
            flush_rewrite_rules(false);
            update_option('gwptb_permalinks_flushed', 1);           
        }
    }
	
	public function custom_templates_redirect(){		
        
		$qv = get_query_var('gwptb'); 
		
		if('service' == $qv){			
			do_action('gwptb_service');
			die();
		}
		elseif('update' ==  $qv) {
			do_action('gwptb_update');
			die();
		}
	}
	
	/**
	 * method to received updates
	 * through webhook
	 **/
	
	public function webhook_update_process(){
		global $wpdb;
				
		//record post
		$update = file_get_contents('php://input');
		$update = ($update) ? json_decode($update) : new WP_Error('invalid_update', __('Invalid update received by webhook', 'gwptb'));
			
		$bot = Gwptb_Self::get_instance(); 
		$bot->process_update($update);
		
		echo 'Thank you!';
	}
	
	/** method to test communications
	 * as for now just print everything on the screen
	 **/
	public function service_process(){
		
		$action = (isset($_GET['action'])) ? trim($_GET['action']) : '';
		
		if(empty($action))
			return; //to do
		
		if($action == 'getme'){
			$bot = Gwptb_Self::get_instance();
			$test = $bot->self_test();
			var_dump($test);
			
		}
		elseif($action == 'get_update') {
			$bot = Gwptb_Self::get_instance();			
			$upd = $bot->get_update();
			echo "<pre>";
			print_r($upd);
			echo "</pre>";
		}
		elseif($action == 'last_reply'){
			
			$bot = Gwptb_Self::get_instance();
			$upd = $bot->get_update(); 
			
			echo "<pre>";
			print_r($upd);
			echo "</pre>";
					
			if($upd){
				$result = $bot->process_update($upd);
				echo "<pre>";
				print_r($result);
				echo "</pre>";
			}			
		}
		elseif($action == 'set_webhook'){
			
			//test for options
			$path = gwptb_get_option('cert_path');
			if(!file_exists($path)){
				echo 'Invalid certificate path';
				die();
			}
			
			$bot = Gwptb_Self::get_instance();
			$upd = $bot->set_webhook(); 
			
			echo "<pre>";
			print_r($upd);
			echo "</pre>";
		}
		elseif($action == 'remove_webhook'){
			$bot = Gwptb_Self::get_instance();
			$upd = $bot->set_webhook(true); 
			
			echo "<pre>";
			print_r($upd);
			echo "</pre>";
		}
	}
	
	
} //class