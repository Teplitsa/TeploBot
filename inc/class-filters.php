<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class GWPTB_Filters {
	
	private static $instance = NULL;
	
	private function __construct() {
				
		//add default filters
		add_filter('gwptb_sanitize_latin', array('GWPTB_Filters', 'media_upload_callback'));
	}
	
	
	/** instance */
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }     
	
	
	/** == Input == **/
	public static function sanitize_email($input){
		//Remove all characters except letters, digits and !#$%&'*+-=?^_`{|}~@.[]. 
		
		return filter_var($input, FILTER_SANITIZE_EMAIL);
	}
	
	
	
	/** == Output == **/
	
	
} //class

GWPTB_Filters::get_instance();
