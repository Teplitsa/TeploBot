<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class GWPTB_Filters {
	
	private static $instance = NULL;
	
	private function __construct() {
				
		//add default filters
		add_filter('gwptb_input_latin', array('GWPTB_Filters', 'sanitize_email'));
		add_filter('gwptb_input_text', array('GWPTB_Filters', 'sanitize_string'));
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
	
	
	public static function sanitize_string($input){
		//Strip tags, strip special characters
		
		return filter_var($input, FILTER_SANITIZE_STRING);
	}
	
	public static function sanitize_text($input){
		//HTML-escape '"<>& and characters with ASCII value less than 32
		
		$input = strip_tags($input); //no html at all
		return filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
	}
	
	
	public static function sanitize_url($input){
		//Remove all characters except letters, digits and $-_.+!*'(),{}|\\^~[]`<>#%";/?:@&=. 
		
		return filter_var($input, FILTER_SANITIZE_URL);
	}
	
	/** == Output == **/
	
	
	/** == Special filters == **/
	public static function sanitize_message_entity($ent){
		
		if(isset($ent->type))
			$ent->type = self::sanitize_email($ent->type);
		
		if(isset($ent->offset))
			$ent->offset = (int)$ent->offset;
			
		if(isset($ent->length))
			$ent->length = (int)$ent->length;
		
		if(isset($ent->url))
			$ent->type = self::sanitize_url($ent->type);
			
		return $ent;
	}
	
} //class

GWPTB_Filters::get_instance();