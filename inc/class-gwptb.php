<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class Gwptb_Self {
	
	private static $instance = NULL; //instance store
	private static $commands = array();
	
	protected $token = '';
	protected $api_url = '';
	protected $self_id = null;
	
	
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
	 *	
	 *	@param string $method Telegram API method to use
	 *	@param array  $params Request params according to Telegram API
	 *	@param int    $update_id Connected update ID (optional)
	 *
	 *	@return array Returns array of parsed response data after recording them into log
	 **/
	protected function request_api_json($method = 'getMe', $params = array(), $update_id = 0){
		global $wpdb;
		
			
		$request_args = array('headers' => array("Content-Type" => "application/json"));
		if(!empty($params))
			$request_args['body'] = json_encode($params);
		
		//make remore API request			
		$response = wp_remote_post($this->api_url.$method, $request_args);
		
		//parse response and find body content or error
		$response = $this->validate_api_response($response, $params);
		
		//log data		
		return $this->log_reseived_response($response, $method, $update_id);
	}
	
	/**
	 *	Make Telegram Bot API request to upload files
	 *
	 *	@param string $method Telegram API method to use
	 *	@param array  $params Request params according to Telegram API
	 *	@param int    $update_id Connected update ID (optional)
	 *
	 *	@return array Returns array of parsed response data after recording them into log
	 **/
	protected function request_api_multipart($method = 'getMe', $params = array(), $update_id = 0){		
		global $wpdb;
		
		
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
				elseif($key == 'certificate'){ //certificate should be submitted as file
					$payload .= '--' . $boundary;
					$payload .= "\r\n";
					$payload .= 'Content-Disposition: form-data; name="'.$key.'"; filename="'.$key.'_file"'."\r\n";
					//$payload .= 'Content-Type: image/jpeg' . "\r\n";
					$payload .= "\r\n";
					$payload .= $value;
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
				
		//make remore API request			
		$response = wp_remote_post($this->api_url.$method, $request_args);
		
		//var_dump($response);
		//parse response and find body content or error
		$response = $this->validate_api_response($response, $params);
		
		//log data		
		return $this->log_reseived_response($response, $method, $update_id);		
	}
	
		
	/**
	 * Get correct body of WP remote response
	 * 
	 * @param object/array $response Raw result of remote request as it come to us	 * 
	 * @return object Content of response body on success or WP_Error object in case of incorrect results
	 **/
	protected function validate_api_response($response, $params) {
		
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
		
		if(!is_object($body->result)){
			$res = new stdClass();
			$res->result = $body->result;			
		}
		else{
			$res = $body->result;
		}
		
		$res->description = (isset($body->description)) ? $body->description : '';
		$res->request_params = $params;
		
		return $res;
	}
	
	
	/** == Log == **/
	
	/**
	 * Log arbitrary action
	 *
	 * @param array $data Array of data to write	 *
	 * @return mixed Return false if log failed or 1 on success
	 **/
	protected function log_action($data){
		global $wpdb;
		
		//preapre defaults
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
			'chattype'		=> '',
			'content'		=> '',
			'attachment'	=> '',
			'error'			=> '',
			'count'			=> 0,
		);
		
		$data = wp_parse_args($data, $defaults);
		
		//sanitize
		$data['action'] = apply_filters('gwptb_input_latin', $data['action']);
		$data['method'] = apply_filters('gwptb_input_latin', $data['method']);
		$data['username'] = apply_filters('gwptb_input_latin', $data['username']);
		
		$data['user_fname'] = apply_filters('gwptb_input_text', $data['user_fname']);
		$data['user_lname'] = apply_filters('gwptb_input_text', $data['user_lname']);
		$data['chatname'] = apply_filters('gwptb_input_text', $data['chatname']);
		$data['chatname'] = apply_filters('gwptb_input_text', $data['chattype']);
		$data['error'] = apply_filters('gwptb_input_text', $data['error']);
		$data['attachment'] = $data['attachment']; // ??
		
		$data['update_id'] = (int)$data['update_id'];
		$data['user_id'] = (int)$data['user_id'];
		$data['message_id'] = (int)$data['message_id'];
		$data['chat_id'] = (int)$data['chat_id'];
		$data['count'] = (int)$data['count'];
		
		//prevent empty content fielf in log due to inccorect submissions
		$content = apply_filters('gwptb_input_text', $data['content']);
		if(!empty($data['content']) && empty($content)){
			$data['content'] = __('Invalid content in received message', 'gwptb');
		}
		else {
			$data['content'] = $content;
		}
		
		$table_name = Gwptb_Core::get_log_tablename();
		return $wpdb->insert($table_name, $data, array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s',));		
	}
	
	/**
	 *  Extract info for log from response
	 *
	 *	@param obj $response Response object to work on
	 *	@param string $method API method used
	 *	@param int $update_id Connected update ID
	 *
	 *	@return array Return array of extracted data after write them into log with lod_id
	 */
	
	// extracting logic should be separated in a more abstract way 
	protected function log_reseived_response($response, $method, $update_id = 0){
		global $wpdb;
		
		$log_data = array('method' => $method);
		$log_data['action'] = (in_array($method, array('getMe', 'setWebhook'))) ? 'request' : 'response';
		$log_data['update_id'] = ($update_id > 0) ? (int)$update_id : 0;
				
		
		if(is_wp_error($response)){			
			$log_data['error'] =  $response->get_error_message();						
		}
		elseif($method == 'setWebhook'){ 
			if((bool)$response->result) {
				$msg = (empty($response->request_params['url'])) ? __('Connection removed', 'gwptb') : __('Connection set', 'gwptb');
				if(!empty($response->description))
					$msg .= '. '.$response->description;
					
				$log_data['content'] = $msg;
			}
		}
		elseif($method == 'getMe'){
			
			//correct response
			if(isset($response->id)){
				$log_data['user_id'] = (int)$response->id;
				$log_data['content'] = __('Bot detected', 'gwptb');
			}	
			
			if(isset($response->first_name))
				$log_data['user_fname'] = $response->first_name; //add sanitisation
				
			if(isset($response->username))
				$log_data['username'] = $response->username;//add sanitisation
				
			//error 
		}
		elseif($method == 'sendMessage' || $method == 'editMessageText'){
			
			if(isset($response->message_id))
				$log_data['message_id'] = (int)$response->message_id;
				
			if(isset($response->chat)){
				$chat_data = $this->extract_chat_data_for_log($response->chat);
				$log_data = array_merge($log_data, $chat_data);
			}
			
			if(empty($log_data['user_id']) && in_array($response->chat->type, array('private'))){
				//in some case user_id == chat_id - test for them all
				$log_data['user_id'] = (int)$response->chat->id;
			}
			
			$log_data['content'] = $response->text;
			
			$ents = (array)$response->entities;
			$log_data['attachment'] = maybe_serialize(array_map(array('GWPTB_Filters', 'sanitize_message_entity'), (array)$response->entities));
			
			if(isset($response->entities) && !empty($response->entities)){
				$count = 0;
				foreach($response->entities as $e){
					if($e->type == 'text_link')
						$count++;
				}
				
				$log_data['count'] = $count;
			}
			
			//error ?
		}
		
		//obtain log entry ID
		$log_data['id'] = ($this->log_action($log_data)) ? $wpdb->insert_id : 0;
		
		return $log_data;
	}
	
	
	
	/**
	 * Log update object - detect update type and format it's data
	 *
	 * @param object $update Update object as we received it from Telegram
	 * @return array Return array of extracted data after write them into log with lod_id
	 **/
	// extracting logic should be separated in a more abstract way 
	protected function log_received_update($update){
		global $wpdb;
		
		$log_data = array(
			'action' => 'update',
			'update_id' => (isset($update->update_id)) ? (int)$update->update_id : 0
		);
				
		if(is_wp_error($update)){ //error
			$log_data['method'] = 'error';
			$log_data['error'] = $update->get_error_message();
			
		}
		elseif(isset($update->message)){ //message
						
			$log_data['method'] = 'message';			
			$log_data['message_id'] = (isset($update->message->message_id)) ? (int)$update->message->message_id : 0;
			
			//other cases of user ??
			if(isset($update->message->from)){
				$from_data = $this->extract_user_data_for_log($update->message->from);
				$log_data = array_merge($log_data, $from_data);
			}
			
			//chat
			if(isset($update->message->chat)){
				$chat_data = $this->extract_chat_data_for_log($update->message->chat);
				$log_data = array_merge($log_data, $chat_data);
			}
			
			//content
			if(isset($update->message->text))
				$log_data['content'] = $update->message->text;
			
			if(isset($update->message->entities))
				$log_data['attachment'] = maybe_serialize(array_map(array('GWPTB_Filters', 'sanitize_message_entity'), (array)$update->message->entities));
			
		}
		elseif(isset($update->callback_query)) { //msg update request
						
			$log_data['method'] = 'callback_query';			
			$log_data['message_id'] = (isset($update->callback_query->message->message_id)) ? (int)$update->callback_query->message->message_id : 0;
			
			//content
			if(isset($update->callback_query->data) && !empty($update->callback_query->data)){
				$log_data['content'] = $update->callback_query->data;
			}
			else{
				$log_data['error'] = __('Empty update query', 'gwptb');
			}
			
			//chat
			if(isset($update->callback_query->message->chat)){
				$chat_data = $this->extract_chat_data_for_log($update->callback_query->message->chat);
				$log_data = array_merge($log_data, $chat_data);
			}
		}
		
		//obtain log entry ID
		$log_data['id'] = ($this->log_action($log_data)) ? $wpdb->insert_id : 0;
		
		return $log_data;
	}
	
	
	
	/** == Skeleton for parsing Telegram objects == **/
	
	/**
	 * 	Extract Chat object	
	 *  
	 *  @param object $chat Object represented Telegram chat object
	 *  @return array Returns array of formatted data
	 **/
	public function extract_chat_data_for_log($chat){
		
		$log_data = array();
		
		$log_data['chat_id'] = (isset($chat->id)) ? (int)$chat->id : 0;
		$log_data['chatname'] = '';
		$log_data['chattype'] = '';
		
		//this should take type into consideration
		if(isset($chat->title))
			$log_data['chatname'] = $chat->title;
			
		if(isset($chat->username))
			$log_data['chatname'] = $chat->username;
			
		if(isset($chat->username))
			$log_data['username'] = $chat->username;
		
		if(isset($chat->first_name))
			$log_data['user_fname'] = $chat->first_name;
			
		if(isset($chat->last_name))
			$log_data['user_lname'] = $chat->last_name;
			
		if(isset($chat->type))
			$log_data['chattype'] = $chat->type;
		
		return $log_data;
	}
	
	/**
	 * 	Extract User object	
	 *  
	 *  @param object $user Object represented Telegram user object
	 *   @return array Returns array of formatted data
	 **/
	public function extract_user_data_for_log($user){
		
		$log_data = array();
		
		$log_data['user_id'] = (isset($user->id)) ? (int)$user->id : 0;
		$log_data['username'] = (isset($user->username)) ? $user->username : '';
		$log_data['user_fname'] = (isset($user->first_name)) ? $user->first_name : '';
		$log_data['user_lname'] = (isset($user->last_name)) ? $user->last_name : '';
		
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
		$token = get_option('gwptb_bot_token', '');
		
		if($remove || empty($token)){
			$params['url'] = '';
		}
		else {
			
			//build 
			$params['url'] = home_url('gwptb/'.$token.'/', 'https'); 
			flush_rewrite_rules(false); //flush permalink in case it's first build
			
			//certificate?
			$cert = get_option('gwptb_cert_key');
			if($cert)
				$params['certificate'] = $cert;
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
	 * @param object $update Object that represent received update or WP_Error if update invalid
	 **/
	public function process_update($update){
				
		//log received update
		$upd_data = $this->log_received_update($update);
				
		//reply
		if($upd_data['method'] == 'message'){
			$this->reply_message($upd_data);
		}
		elseif($upd_data['method'] == 'callback_query'){
			$this->update_message($upd_data);
		}
		
		//end
	}
	
	
	/** == Reactions: handles for different types of query == **/
	
	/**
	 * Replay on message
	 *
	 * @param array $upd_data Preformed update data (after logging)
	 **/
	protected function reply_message($upd_data){
		global $wpdb;
		
		//prepare reply
		$reply = $this->get_message_replay($upd_data);
				
		//send reply
		$this->request_api_json('sendMessage', $reply, (int)$upd_data['update_id']);		
	}
	
	protected function get_message_replay($upd_data){
		
		$reply = array();
		
		if(isset($upd_data['chat_id'])){
			$reply['chat_id'] = (int)$upd_data['chat_id'];
		}
		
		if(isset($upd_data['message_id'])){
			//$reply['reply_to_message_id'] = (int)$message->message_id; //do we need it??
		}
				
		$reply_text = $this->get_replay_text_args($upd_data);
		
		$reply = array_merge($reply, (array)$reply_text);
		
		return $reply;
	}
	
	protected function get_replay_text_args($upd_data){
		
		$command = $this->detect_command($upd_data);
		$commands = self::get_supported_commands(); 
		$result = array();
		
		
		if(isset($commands[$command]) && is_callable($commands[$command])){
			$upd_data['command'] = $command;
			$result = call_user_func($commands[$command], $upd_data);
			
		}
		elseif($upd_data['chattype'] == 'private') {			
			//no commands - return search results
			$result = gwptb_search_command_response($upd_data);
		}
		else {
			//rections to group aux messages
		}
		
		
		
		return $result;
	}
	
	/**
	 * Update message (by next/prev buttons)
	 *
	 * @param array $upd_data Preformed update data (after logging)
	 **/	
	protected function update_message($upd_data){
				
		//prepare update
		$reply = $this->get_message_update($upd_data);
		
		//send update
		$this->request_api_json('editMessageText', $reply, (int)$upd_data['update_id']);		
	}
	
	protected function get_message_update($upd_data){
		
		$reply = array();
		
		if(isset($upd_data['chat_id'])){
			$reply['chat_id'] = (int)$upd_data['chat_id'];
		}
		
		if(isset($upd_data['message_id'])){
			$reply['message_id'] = $upd_data['message_id'];
		}
				
		$reply_text = $this->get_update_text_args($upd_data);
		$reply = array_merge($reply, $reply_text);
		
		return $reply;
	}
	
	protected function get_update_text_args($upd_data) {
		
		$result = array(); 	
		$commands = self::get_supported_commands();
		
		if(empty($commands))
			return $result;
		
		//find out type of update
		foreach($commands as $key => $callback){
			if(0 === strpos(trim($upd_data['content']), $key.'=') && is_callable($callback)){
				$upd_data['command'] = $key;				
				$result = call_user_func($callback, $upd_data);				
			}
		}
		
		//empty response?
		if(empty($result)){
			$result['text'] = __('Unfortunately you\'ve submitted an incorrect request.', 'gwptb');
			$result['text'] = apply_filters('gwptb_output_text', $result['text']);
		}
		
		return $result;
	}
	
	
	/** == Commands support **/
	public static function get_supported_commands(){
        
        if (empty(self :: $commands)){
			$default_commands = array(
				'help'		=> 'gwptb_help_command_response',
				'start'		=> 'gwptb_start_command_response',
				's'	        => 'gwptb_search_command_response',
			);
			
			$custom_commands = array();
			$custom_commands_opt = get_option('gwptb_custom_commands');
			
			if(is_array($custom_commands_opt) && !empty($custom_commands_opt)) {
				
				foreach($custom_commands_opt as $i => $opt){
					if(isset($opt['command']) && !empty($opt['command'])){
						$command = esc_attr($opt['command']);
						$custom_commands[$command] = 'gwptb_custom_command_response';
					}
				}
			}
			
			self :: $commands = apply_filters('gwptb_supported_commnds_list', array_merge($default_commands, $custom_commands));
		}
		
		return self :: $commands;
    }
		
	protected function detect_command($upd_data){
		
		$command = false;
		if(!isset($upd_data['attachment']) || empty($upd_data['attachment']))
			return $command; //no entities at all
				
		$entities = maybe_unserialize($upd_data['attachment']); 
		foreach((array)$entities as $ent){
			if($ent->type != 'bot_command')
				continue;
			
			$command = substr($upd_data['content'], $ent->offset, $ent->length);
			$command = trim(str_replace('/', '', $command));
		}
		
		//strip bot name form command
		$self = $this->get_self_username();
		$command = str_replace('@'.$self, '', $command);
		
		return $command;
	}
	
	public function get_custom_command_args($command){
		
		$result = array('post_type' => array(), 'title' => '');
		$custom_commands_opt = get_option('gwptb_custom_commands');
		
		if(!is_array($custom_commands_opt) || empty($custom_commands_opt))
			return $result;
				
		foreach($custom_commands_opt as $i => $opt){
			if(isset($opt['command']) && $opt['command'] == $command){
				$result['post_type'] = (isset($opt['post_type'])) ? $opt['post_type'] : 'post';
				$result['title'] = (isset($opt['title'])) ? $opt['title'] : '';
			}
		}
		
		return $result;
	}
	
	
	/** == Self-ID == **/
	protected function get_self_id() {
		global $wpdb;
				
		if(null === $this->self_id){
			//test for connection set
			$connection = get_option('gwptb_webhook', 0);
			if(!$connection)
				return $this->self_id;
			
			//get name from DB
			$table_name = Gwptb_Core::get_log_tablename();
			$row = $wpdb->get_row("SELECT * FROM {$table_name} WHERE method = 'getMe' ORDER BY id DESC LIMIT 1");
			if(isset($row->username) && !empty($row->username)){
				$this->self_id['username'] = $row->username;
				$this->self_id['name'] = (!empty($row->user_fname)) ? $row->user_fname : '';
			}
		}
		
		return $this->self_id;
	}
	
	public function get_self_link(){
		
		$self = $this->get_self_id();
		$link = '';
		
		if(!$self){
			return __('Set connection for the bot', 'gwptb');			 
		}
		
		$txt = $this->get_self_name();		
		$url = (isset($self['username']) && !empty($self['username'])) ? esc_url('https://telegram.me/'.$self['username']) : '';
		
		if(empty($url))
			return __('Set connection for the bot', 'gwptb');
		
		return "<a href='{$url}' target='_blank'>{$txt}</a>";
	}
	
	public function get_self_name() {
		
		$self = $this->get_self_id();
		$name = '';
		
		if(!isset($self['name']) || !isset($self['username']))
			return '';
		
		$name = (!empty($self['name'])) ? $self['name'] : '';
		$name .= (!empty($self['username'])) ? " (@".$self['username'].")" : '';
		
		return $name;
	}
	
	public function get_self_username() {
		
		$self = $this->get_self_id();
		
		if(!isset($self['username']))
			return '';
				
		return $self['username'];
	}
	
} //class