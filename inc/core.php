<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class Gwptb_Core {
	
	private static $instance = NULL; //instance store
			
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
		
		$ver = get_option('gwptb_version');
		
		if(!empty($ver) && $ver < GWPTB_VERSION){
			self::self_upgrade();			
		}
		
		self::create_table();
		update_option('gwptb_version', GWPTB_VERSION);
		
		//stats
		$stat = GWPTB_Stats::get_instance();
		$stat->update_stats();
	}
	
	static function on_deactivation() {
		update_option('gwptb_permalinks_flushed', 0);
		
	}
	
	/** upgrade **/
	static function self_upgrade(){
		global $wpdb;
		
		$db = (defined('DB_NAME'))? DB_NAME : '';
		$table = self::get_log_tablename();
		
		$test = $wpdb->get_results(
"SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = '$db' 
AND TABLE_NAME = '$table' 
AND COLUMN_NAME = 'chattype'");
		
		if(!$test){
			try {
				if(!$wpdb->query("ALTER TABLE $table ADD chattype varchar(255) DEFAULT '' NOT NULL AFTER chatname"))
					throw new Exception("The chattype column could not be added to Log table");
			}
			catch(Exception $e) {
				if(WP_DEBUG_DISPLAY)
					echo $e->getMessage();
					
				error_log($e->getMessage());
			}
			
		}
	}
	
	/** == Log Table == **/
	static function create_table(){
		global $wpdb;			
		
		$table_name = self::get_log_tablename();
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
			$charset_collate = $wpdb->get_charset_collate();
	
			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				action varchar(255) NOT NULL,
				method varchar(255) NOT NULL,
				update_id bigint(20) DEFAULT 0 NOT NULL,
				user_id bigint(20) DEFAULT 0 NOT NULL,
				username varchar(255) DEFAULT '' NOT NULL,
				user_fname text DEFAULT '' NOT NULL,
				user_lname text DEFAULT '' NOT NULL,				
				message_id bigint(20) DEFAULT 0 NOT NULL,
				chat_id bigint(20) DEFAULT 0 NOT NULL,
				chatname varchar(255) DEFAULT '' NOT NULL,
				chattype varchar(255) DEFAULT '' NOT NULL,
				content text DEFAULT '' NOT NULL,
				attachment text DEFAULT '',
				error text DEFAULT '' NOT NULL,
				count bigint(20) DEFAULT 0,
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
		else {
			$token = get_option('gwptb_bot_token');
			if(!empty($token) && $qv == $token){
				do_action('gwptb_update');
				die();
			}			
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
			$path = get_option('gwptb_cert_path');
			if(!file_exists($path)){
				echo 'Invalid certificate path';				
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