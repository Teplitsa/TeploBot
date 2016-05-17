<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class Gwptb_Self {
	
	private static $instance = NULL; //instance store
	private static $commands = array();
	
	protected $token = '';
	protected $api_url = '';
	
	
	private function __construct() {
		
		//set token
		$token = get_option('gwptb_bot_token');
		if(!$token && defined('BOT_TOKEN'))
			$token = BOT_TOKEN;
			
		$this->token = $token;
		$this->api_url = trailingslashit('https://api.telegram.org/bot'.$this->token);
	}
	
	
	/** instance */
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }
	
	
	/** == API request == **/
		
	/**
	 *	Make Telegram Bot API request
	 *	@method string Telegram API method
	 *	@params array - request params according to Telegram API
	 **/
	protected function request_api_json($method = 'getMe', $params = array()){
		global $wpdb;
		
		$action = (in_array($method, array('getMe', 'setWebhook'))) ? 'request' : 'response';
		
		$log_data = array(
			'action' => $action,
			'method' => $method			
		);
		
		$request_args = array('headers' => array("Content-Type" => "application/json"));
		if(!empty($params))
			$request_args['body'] = json_encode($params);
		
		//make remore API request			
		$response = wp_remote_post($this->api_url.$method, $request_args);
		
		//parse response and find body content or error
		$response = $this->validate_api_response($response);
		
		//prepare log data		
		if(is_wp_error($response)){			
			$log_data['error'] =  $response->get_error_message();						
		}
		else {			
			$log_content = $this->extract_response_for_log($response, $method);	
			$log_data = array_merge($log_data, $log_content);
		}
		
		//obtain log entry ID
		$log_data['id'] = ($this->log_action($log_data)) ? $wpdb->insert_id : 0;
				
		return $log_data;
	}
	
	/**
	 *	Make Telegram Bot API request to upload files
	 *	@method string Telegram API method
	 *	@params array - request params according to Telegram API
	 **/
	protected function request_api_multipart($method = 'getMe', $params = array()){		
		global $wpdb;
		
		$action = (in_array($method, array('getMe', 'setWebhook'))) ? 'request' : 'response';
		
		$log_data = array(
			'action' => $action,
			'method' => $method			
		);
		
		$boundary = wp_generate_password( 24 );
		$request_args = array('headers' => array("Content-Type" => "multipart/form-data; boundary=".$boundary));
		
		//prepare params to be file contents
		$payload = '';
		if(!empty($params)) {			
			foreach($params as $key => $value) {
				
				if(file_exists($value)){
					$payload .= '--' . $boundary;
					$payload .= "\r\n";
					$payload .= 'Content-Disposition: form-data; name="'.$key.'"; filename="'.basename($value).'"'."\r\n";
					//$payload .= 'Content-Type: image/jpeg' . "\r\n";
					$payload .= "\r\n";
					$payload .= file_get_contents($value);
					$payload .= "\r\n";
				}
				else  {
					$payload .= '--' . $boundary;
					$payload .= "\r\n";
					$payload .= 'Content-Disposition: form-data; name="'.$key.'"'."\r\n\r\n";
					$payload .= $value;
					$payload .= "\r\n";
				}	
			}
			
			$payload .= '--' . $boundary . '--';
		}
		
		$request_args['body'] = $payload;
		//var_dump($params);
		//echo"<br><br>";
		//var_dump($request_args);
		//echo"<br><br>";
		
		//make remore API request			
		$response = wp_remote_post($this->api_url.$method, $request_args);
		
		//var_dump($response);
		//parse response and find body content or error
		$response = $this->validate_api_response($response);
		
		//prepare log data		
		if(is_wp_error($response)){			
			$log_data['error'] =  $response->get_error_message();						
		}
		elseif($method == 'setWebhook'){
			if((bool)$response) {
				$log_data['content'] = (empty($params['url'])) ? __('Connection removed', 'gwptb') : __('Connection set', 'gwptb');
			}
		}
		else {			
			$log_content = $this->extract_response_for_log($response, $method);	
			$log_data = array_merge($log_data, $log_content);
		}
		
		//obtain log entry ID
		$log_data['id'] = ($this->log_action($log_data)) ? $wpdb->insert_id : 0;
				
		return $log_data;
	}
	
		
	/**
	 * Get correct body of response or error
	 * @response object/array - raw result of remote request as it come to us
	 **/
	protected function validate_api_response($response) {
		
		$resp_error = null;
		if(is_wp_error($response)){ //error of request
			$resp_error = new WP_Error('invalid_request', sprintf(__('Invalid request with error: %s', 'gwptb'), $response->get_error_message()));			
			return $resp_error;
		}
		
		$body = wp_remote_retrieve_body($response);
		if(!$body){ //no body in response
			$resp_error = new WP_Error('invalid_response', sprintf(__('Invalid response with code: %s', 'gwptb'), wp_remote_retrieve_response_code( $response )));
			return $resp_error;
		}
			
		$body = json_decode($body);
		if(!isset($body->ok) || !$body->ok){ //no OK status in body
			if($body->description){
				$msg = sprintf(__('Invalid content in response: %s', 'gwptb'), $body->description);
			}
			else{
				$msg = __('Invalid content in response', 'gwptb');
			}
			
			$resp_error = new WP_Error('invalid_content', $msg, $body);
			return $resp_error;
		}
		
		return $body->result;
	}
	
	
	/** == Log == **/
	protected function log_action($data){
		global $wpdb;
		
		$defaults = array(
			'time'   		=> current_time('mysql'), 
			'action' 		=> '',
			'method' 		=> '',
			'update_id'		=> 0,
			'user_id'		=> 0,
			'username'		=> '',
			'user_fname'	=> '',
			'user_lname'	=> '',
			'message_id'	=> 0,
			'chat_id'		=> 0,
			'chatname'		=> '',
			'content'		=> '',
			'attachment'	=> '',
			'error'			=> '',
		);
		
		$data = wp_parse_args($data, $defaults);
		
		//sanitize
		$data['action'] = apply_filters('gwptb_sanitize_latin', $data['action']);
		$data['method'] = apply_filters('gwptb_sanitize_latin', $data['method']);
		$data['username'] = apply_filters('gwptb_sanitize_latin', $data['username']);
		
		$data['user_fname'] = apply_filters('gwptb_sanitize_text', $data['user_fname']);
		$data['user_lname'] = apply_filters('gwptb_sanitize_text', $data['user_lname']);
		$data['chatname'] = apply_filters('gwptb_sanitize_text', $data['chatname']);
		
		$data['content'] = apply_filters('gwptb_sanitize_rich_text', $data['content']);
		$data['error'] = apply_filters('gwptb_sanitize_rich_text', $data['error']);
		$data['attachment'] = apply_filters('gwptb_sanitize_rich_text', $data['attachment']);
		
		$data['update_id'] = (int)$data['update_id'];
		$data['user_id'] = (int)$data['update_id'];
		$data['message_id'] = (int)$data['update_id'];
		$data['chat_id'] = (int)$data['update_id'];
		
		$table_name = Gwptb_Core::get_log_tablename();
		return $wpdb->insert($table_name, $data, array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s',));
	}
	
	//extract info for log from response
	protected function extract_response_for_log($response, $method){
		
		$log = array();
		if($method == 'getMe'){
			//correct response
			if(isset($response->id)){
				$log['user_id'] = (int)$response->id;
				$log['content'] = __('Bot detected', 'gwptb');
			}	
			
			if(isset($response->first_name))
				$log['user_fname'] = $response->first_name; //add sanitisation
				
			if(isset($response->username))
				$log['username'] = $response->username;//add sanitisation
				
			//error 
		}
				
		return $log;
	}
	
	
	//shortcut to log received update object 
	protected function log_received_update($update){
		global $wpdb;
		
		$log_data = array(
			'action' => 'update',
			'update_id' => (isset($update->update_id)) ? (int)$update->update_id : 0
		);
		$original_object = '';
		
		if(is_wp_error($update)){ //error
			$log_data['method'] = 'error';
			$log_data['error'] = $update->get_error_message();
			
		}
		elseif(isset($update->message)){ //message
			$original_object = $update->message;
			
			$log_data['method'] = 'message';			
			$log_data['message_id'] = (isset($update->message->message_id)) ? (int)$update->message->message_id : 0;
			
			//other cases of user ??
			if(isset($update->message->from)){
				$log_data['user_id'] = (isset($update->message->from->id)) ? (int)$update->message->from->id : 0;
				$log_data['username'] = (isset($update->message->from->username)) ? (int)$update->message->from->username : '';
				$log_data['user_fname'] = (isset($update->message->from->first_name)) ? (int)$update->message->from->first_name : '';
				$log_data['user_lname'] = (isset($update->message->from->last_name)) ? (int)$update->message->from->last_name : '';
			}
			
			//chat
			if(isset($update->message->chat)){
				$log_data['chat_id'] = (isset($update->message->chat->id)) ? (int)$update->message->chat->id : 0;
				$log_data['chatname'] = '';
				
				if(isset($update->message->chat->title))
					$log_data['chatname'] = $update->message->chat->title;
					
				if(isset($update->message->chat->username))
					$log_data['chatname'] = $update->message->chat->username;
				
				if(empty($log_data['user_fname']) && isset($update->message->chat->first_name))
					$log_data['user_fname'] = $update->message->chat->first_name;
					
				if(empty($log_data['user_lname']) && isset($update->message->chat->last_name))
					$log_data['user_lname'] = $update->message->chat->last_name;
			}
			
			//content
			if(isset($update->message->text))
				$log_data['content'] = $update->message->text;
			
			if(isset($update->message->entities))
				$log_data['attachment'] = maybe_serialize($update->message->entities);
			
		}
		elseif(isset($update->callback_query)) { //msg update request
			$log_data['method'] = 'callback_query';
			
			//to-do
		}
		
		//obtain log entry ID
		$log_data['id'] = ($this->log_action($log_data)) ? $wpdb->insert_id : 0;
		
		return $log_data;
	}
	
	
	
	/** == Communications: wrappers for api request methods == **/
	
	/**
	 * Test communication with Bot API
	 **/
	public function self_test(){
		
		return $this->request_api_json('getMe');		
	}
	
	/**
	 * Set or remove webhook
	 **/
	public function set_webhook($remove = false){
		
		$params = array();
		if($remove){
			$params['url'] = '';
		}
		else {
			$params['url'] = home_url('gwptb/update/', 'https'); //support for custom slug in future
			$cert_path = get_option('gwptb_cert_path');
			if($cert_path)
				$params['certificate'] = $cert_path;
		}
		
		//api request		
		$upd = $this->request_api_multipart('setWebhook', $params);
		
		//record option
		if(empty($upd['error']) && !$remove){
			update_option('gwptb_webhook', 1);  
		}
		else {
			update_option('gwptb_webhook', 0);  
		}
		
		return $upd;
	}
	
	
	/**
	 * Process update stack
	 * @update object/WP_Error - received update or Error object
	 **/
	public function process_update($update){
				
		//log received update
		$upd_data = $this->log_received_update($update);
				
		//reply
		if($upd_data['method'] == 'message'){
			$this->reply_message($upd_data);
		}
		elseif($upd_data['method'] == 'callback_query'){
			
		}
		
		//end
	}
	
	
	/** == Reactions: handles for different types of query == **/
	
	/* replay on message **/
	protected function reply_message($upd_data){
		global $wpdb;
		
		//prepare reply
		$reply = $this->get_message_replay($upd_data);
				
		//send reply
		$this->request_api_json('sendMessage', $reply);		
	}
	
	protected function get_message_replay($upd_data){
		
		$reply = array();
		
		if(isset($upd_data['chat_id'])){
			$reply['chat_id'] = (int)$upd_data['chat_id'];
		}
		
		if(isset($upd_data['message_id'])){
			//$reply['reply_to_message_id'] = (int)$message->message_id; //do we need it??
		}
				
		$reply_text = $this->get_replay_text_args($message);
		
		$reply = array_merge($reply, $reply_text);
		
		return $reply;
	}
	
	protected function get_replay_text_args($upd_data){
		
		$command = $this->detect_command($upd_data); 
		$commands = self::get_supported_commands(); 
		$result = array();
		
		if(isset($commands[$command]) && is_callable($commands[$command])){
			$result = call_user_func($commands[$command], $upd_data);
		}
		else {
			//no commands - return search results
			$result = gwptb_search_command_response($upd_data);
		}
		
		return $result;
	}
	
	
	/** update message **/
	protected function update_message($upd_data){
				
		//prepare update
		$reply = $this->get_message_update($upd_data);
		
		//send update
		$send = $this->request_api_json('editMessageText', $reply);		
	}
	
	protected function get_message_update($upd_data){
		
		$reply = array(); 	
		if(isset($upd_data['chat->id'])){
			$reply['chat_id'] = (int)$upd_data['chat->id'];
		}
		
		if(isset($upd_data['message_id'])){
			$reply['message_id'] = $upd_data['message_id'];
		}
				
		$reply_text = $this->get_update_text_args($upd_data['content']);
		$reply = array_merge($reply, $reply_text);
		
		return $reply;
	}
	
	protected function get_update_text_args($data) {
		
		parse_str($data, $a);
		$result = array();
		
		
		if(isset($a['s']) && isset($a['paged']))
			$result = gwptb_search_command_response($a['s'], (int)$a['paged']);
		else
			$result['text'] =  __('Unfortunately your request didn\'t match anything.', 'gwptb');
			
		return $result;
	}
	
	/** == Commands support **/
	public static function get_supported_commands(){
        
        if (empty(self :: $commands)){
			self :: $commands = apply_filters('gwptb_supported_commnds_list', array(
				'help'		=> 'gwptb_help_command_response',
				'start'		=> 'gwptb_start_command_response',
				'search'	=> 'gwptb_search_command_response',
			));
		}
		
		return self :: $commands;
    }
	
	protected function detect_command($message){
		
		$command = false;
		if(!isset($message->entities))
			return $command; //no entities at all
				
		foreach($message->entities as $ent){
			if($ent->type != 'bot_command')
				continue;
			
			$command = substr($message->text, $ent->offset, $ent->length);
			$command = trim(str_replace('/', '', $command));
		}
		
		return $command;
	}
	
	
	
	
	
	
	/** old **/
	/**
	 * Get update stack by polling 
	 **/
	public function get_update(){
		
		//detect the latest logged update
		$latest_upd_id = $this->get_latest_update_id();
		$params = array();
		
		if($latest_upd_id > 0) //we need next update
			$params['offset'] = $latest_upd_id + 1;  //+1
		
		//api request		
		$update = $this->request_api_json('getUpdates', $params);
		
		//log results - it could be bunch of updates
		$update = $this->log_received_update($update);
		
		//return formatted results with log IDs for each update
		return $update;
	}
		
	/**
	 * Find latest registered update ID (assigned by Telegram)
	 **/
	protected function get_latest_update_id(){
		global $wpdb;
		
		$table_name = Gwptb_Core::get_log_tablename();
		$row = $wpdb->get_row("SELECT * FROM $table_name WHERE object = 'update' AND status = 'received' ORDER BY tlgrm_id DESC LIMIT 1" );
		
		$upd_id = 0; 
		if($row)
			$upd_id = (int)$row->tlgrm_id;
				
		return $upd_id;
	}
	
	
	protected function process_formatted_update($update){
		global $wpdb;
		
		//log data for whole request process entry		
		$log_data = array(
			'object' => 'update_processed',
			'status' => '',
			'data' => array(),
			'connected_id' => (isset($update['log_id'])) ? (int)$update['log_id'] : 0
		);
		
		if(!is_wp_error($update['response']) && !empty($update['response'])){
			$results = array();
			
			foreach($update['response'] as $i => $upd){
				//test for type of response
				if(isset($upd->message)){
					$results[$i] = $this->reply_message($upd);					
				}
				elseif(isset($upd->callback_query)){
					$results[$i] = $this->update_message($upd);	
				}
				
				//others types
			}
			
			$log_data['status'] = 'success';
			$log_data['data'] = $results;
		}
		elseif(!is_wp_error($update['response']) && empty($update['response'])){
			$log_data['status'] = 'empty_update';
			$log_data['data'] = $update['response'];
		}
		else {
			$log_data['status'] = 'error';
			$log_data['data'] = $update['response']->get_message();
		}
		
		$log_data['id'] = ($this->log_action($log_data)) ? $wpdb->insert_id : false;
				
		return $log_data;
	}
	
} //class