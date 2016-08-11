<?php

function gwptb_subscribe_command_response($upd_data){
    global $wpdb;
	
	$result = array();	
	
	$telebot = Gwptb_Self::get_instance();
	$subscription_name = str_replace(array('@', '/sub', $telebot->get_self_username()), '', $upd_data['content']);
	$subscription_name = trim($subscription_name);
	
	$available_subscriptions = gwptb_get_enabled_subscriptions();
	$command_example = sprintf(__('/sub %s', 'gwptb'), implode(' | ', $available_subscriptions));
	
	if(!empty($subscription_name) && in_array($subscription_name, $available_subscriptions)) {
	    
	    $table_name = Gwptb_Core::get_chat_subscriptions_tablename();
	    $chat_id = (int)$upd_data['chat_id'];
	    
	    $row = $wpdb->get_row($wpdb->prepare( "SELECT * FROM {$table_name} WHERE chat_id = %d AND name = %s LIMIT 1", $chat_id, $subscription_name));
	    
	    if($row) {
	        $result['text'] = sprintf(__('You have already subscribed to %s subscription.', 'gwptb'), $subscription_name).chr(10);
	    }
	    else {
	        $data = array('chat_id' => $chat_id, 'name' => $subscription_name, 'moment' => current_time( 'mysql' ));
	        $wpdb->insert($table_name, $data, array('%d', '%s', '%s',));
	        $new_rec_id = $wpdb->insert_id;
	        
	        if($new_rec_id){
	            $result['text'] = sprintf(__('You are successfully subscribed to %s subscription and will receive updates!', 'gwptb'), $subscription_name).chr(10);
	        }
	        else {
	            $result['text'] = __('Subscription failed. Please try again later.', 'gwptb').chr(10);
	        }
	    }
	}
	elseif(empty($available_subscriptions)) {
	    $result['text'] = __('Sorry, but no subscriptions are available.', 'gwptb').chr(10);
	}
	elseif(empty($subscription_name)) {
		$result['text'] = __('Please provide subscription name.', 'gwptb').chr(10);
		$result['text'] .= $command_example.chr(10);
	}
	else {
	    $result['text'] = sprintf(__('You have provided unknown subscription %s.', 'gwptb'), $subscription_name).chr(10);
	    $result['text'] .= $command_example.chr(10);
	}
	
	$result['parse_mode'] = 'HTML';
		
	return $result;
}

function gwptb_unsubscribe_command_response($upd_data) {
    global $wpdb;
    
    $result = array();
    
    $telebot = Gwptb_Self::get_instance();
    $subscription_name = str_replace(array('@', '/unsub', $telebot->get_self_username()), '', $upd_data['content']);
    $subscription_name = trim($subscription_name);
    
    $available_subscriptions = gwptb_get_enabled_subscriptions();
    $available_subscriptions[] = 'all';
    
    $command_example = sprintf(__('/unsub %s', 'gwptb'), implode(' | ', $available_subscriptions));
    
    if(!empty($subscription_name) && in_array($subscription_name, $available_subscriptions)) {
         
        $table_name = Gwptb_Core::get_chat_subscriptions_tablename();
        $chat_id = (int)$upd_data['chat_id'];
        
        if($subscription_name == 'all') {
            $qres = $wpdb->query($wpdb->prepare( "DELETE FROM {$table_name} WHERE chat_id = %d", $chat_id));
            if($qres !== false) {
                $result['text'] = __('You have successfully unsubscribed from all subscriptions.', 'gwptb').chr(10);
            }
            else {
                $result['text'] = __('Unsubscription failed. Please try again later.', 'gwptb').chr(10);
            }
        }
        else {
            $row = $wpdb->get_row($wpdb->prepare( "SELECT * FROM {$table_name} WHERE chat_id = %d AND name = %s LIMIT 1", $chat_id, $subscription_name));
            if($row) {
                $qres = $wpdb->query($wpdb->prepare( "DELETE FROM {$table_name} WHERE chat_id = %d AND name = %s", $chat_id, $subscription_name));
                if($qres !== false) {
                    $result['text'] = sprintf(__('You have successfully unsubscribed from %s subscription.', 'gwptb'), $subscription_name).chr(10);
                }
                else {
                    $result['text'] = __('Unsubscription failed. Please try again later.', 'gwptb').chr(10);
                }
            }
            else {
                $result['text'] = sprintf(__('You are not subscribed to %s subscription.', 'gwptb'), $subscription_name).chr(10);
            }
        }
    }
    elseif(empty($subscription_name)) {
        $result['text'] = __('Please provide subscription name or "all" keyword.', 'gwptb').chr(10);
        $result['text'] .= $command_example.chr(10);
    }
    else {
        $result['text'] = sprintf(__('You have provided unknown subscription %s.', 'gwptb'), $subscription_name).chr(10);
        $result['text'] .= $command_example.chr(10);
    }
    
    $result['parse_mode'] = 'HTML';
    
    return $result;
}

function gwptb_post_published_notification( $ID, $post ) {
    $title = $post->post_title;
    $permalink = get_permalink( $ID );
    $link = "<a href='".$permalink."'>".$title."</a>";
    $short = gwptb_get_post_teaser($post);
    
    $message = sprintf(__("%s\n%s", 'gwptb'), $link, $short).chr(10);
    gwptb_notify_subscribers($post->post_type, $message);
}

function gwptb_get_available_post_types() {
    $post_types = get_post_types(array('public' => true, 'capability_type' => 'post'));
    
    if(($key = array_search('attachment', $post_types)) !== false) {
        unset($post_types[$key]);
    }
        
    return $post_types;
}

function gwptb_get_enabled_subscriptions() {
    $value = get_option('gwptb_subscriptions');
    $sub_list = explode(',', trim($value));
    $res_sub_list = array();
    foreach($sub_list as $sub) {
        $sub = trim($sub);
        if($sub) {
            $res_sub_list[] = $sub;
        }
    }
    return $res_sub_list;
}