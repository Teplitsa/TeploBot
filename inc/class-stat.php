<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class GWPTB_Stats {
	#code		
	
	private static $instance = NULL;
	
	protected $stats = array(); 
	
	
	private function __construct() {
		
		add_action('', array($this, 'calculate_stats'));
	}
	
	
	/** instance */
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }
	
	public function update_stats() {
		
		$this->stats = $this->calculate_stats();
		update_option('gwptb_stats', $this->stats);		
	}
	
	public function get_stats() {
		
		if(empty($this->stats)){
			$def = $this->get_stats_defaults();
			$this->stats = get_option('gwptb_stats', $def);
		}
		
		return $this->stats;
	}
	
	protected function calculate_stats(){
		global $wpdb;
		
		$table_name = Gwptb_Core::get_log_tablename();		
		$data = $this->get_stats_defaults();
		
		$data['log_total'] = (int)$wpdb->get_var("SELECT COUNT(id) FROM {$table_name}");
		$data['updates_total'] = (int)$wpdb->get_var("SELECT COUNT(id) FROM {$table_name} WHERE action = 'update'");
		$data['returns_total'] = (int)$wpdb->get_var("SELECT SUM(count) FROM {$table_name} WHERE action = 'response'");
		
		return $data;
	}
	
	protected function get_stats_defaults(){
		
		return array(
			'log_total'		=> 0,
			'updates_total'	=> 0,
			'returns_total'	=> 0
		);
	}
	
	
} //class

GWPTB_Stats::get_instance();
