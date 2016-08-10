<?php

function gwptb_subscribe_command_response($upd_data){
    global $wpdb;
	
	$result = array();	
	
	$telebot = Gwptb_Self::get_instance();
	$subscription_name = str_replace(array('@', '/subscribe', $telebot->get_self_username()), '', $upd_data['content']);
	$subscription_name = trim($subscription_name);
	
	$available_subscriptions = ['post'];
	if(!empty($subscription_name) && in_array($subscription_name, $available_subscriptions)) {
	    
	    $table_name = Gwptb_Core::get_chat_subscriptions_tablename();
	    $chat_id = (int)$upd_data['chat_id'];
	    
	    $row = $wpdb->get_row($wpdb->prepare( "SELECT * FROM {$table_name} WHERE chat_id = %d AND name = %s LIMIT 1", $chat_id, $subscription_name));
	    
	    if($row) {
	        $result['text'] = __('You have already subscribed.', 'gwptb').chr(10);
	    }
	    else {
	        $data = array('chat_id' => $chat_id, 'name' => $subscription_name);
	        $wpdb->insert($table_name, $data, array('%d', '%s',));
	        $new_rec_id = $wpdb->insert_id;
	        
	        if($new_rec_id){
	            $result['text'] = __('You are successfully subscribed and will receive push notifications!', 'gwptb').chr(10);
	        }
	        else {
	            $result['text'] = __('Subscription failed. Please try again later.', 'gwptb').chr(10);
	        }
	    }
	}
	elseif(empty($subscription_name)) {
		$result['text'] = __('Please provide subscription name', 'gwptb').chr(10);
		$result['text'] .= sprintf(__('/subscribe %s', 'gwptb'), implode('|', $available_subscriptions)).chr(10);
	}
	else {
	    $result['text'] = sprintf(__('You have provided unknown subscription %s', 'gwptb'), $subscription_name).chr(10);
	    $result['text'] .= sprintf(__('/subscribe %s', 'gwptb'), implode('|', $available_subscriptions)).chr(10);
	}
	
	$result['parse_mode'] = 'HTML';
		
	return $result;
}

function gwptb_unsubscribe_command_response($upd_data) {
    global $wpdb;
    
    $result = array();
    
    $telebot = Gwptb_Self::get_instance();
    $subscription_name = str_replace(array('@', '/unsubscribe', $telebot->get_self_username()), '', $upd_data['content']);
    $subscription_name = trim($subscription_name);
    
    $available_subscriptions = ['post'];
    if(!empty($subscription_name) && in_array($subscription_name, $available_subscriptions)) {
         
        $table_name = Gwptb_Core::get_chat_subscriptions_tablename();
        $chat_id = (int)$upd_data['chat_id'];
         
    }
    elseif(empty($subscription_name)) {
        $result['text'] = __('Please provide subscription name or "all" keyword', 'gwptb').chr(10);
        $result['text'] .= sprintf(__('/unsubscribe %s', 'gwptb'), implode('|', $available_subscriptions)).chr(10);
    }
    else {
        $result['text'] = sprintf(__('You have provided unknown subscription %s', 'gwptb'), $subscription_name).chr(10);
        $available_subscriptions_for_command = $available_subscriptions;
        $available_subscriptions_for_command[] = 'all';
        $result['text'] .= sprintf(__('/unsubscribe %s', 'gwptb'), implode('|', $available_subscriptions_for_command)).chr(10);
    }
    
    $result['parse_mode'] = 'HTML';
    
    return $result;
}

function post_published_notification( $ID, $post ) {
    global $wpdb;
    
    $title = $post->post_title;
    $permalink = get_permalink( $ID );
    
    $telebot = Gwptb_Self::get_instance();
    
    $table_name = Gwptb_Core::get_chat_subscriptions_tablename();
    $subscribed_chat_list = $wpdb->get_results($wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s ", $post->post_type));
    
    foreach($subscribed_chat_list as $chat) {
        $message = sprintf(__("New content: %s\nLink: %s", 'gwptb'), $title, $permalink).chr(10);
        $telebot->send_push_notification($chat->chat_id, $message);
    }
}
add_action( 'publish_post', 'post_published_notification', 10, 2 );