<?php
/** Posting of the bot */

if(!defined('ABSPATH')) die; // Die if accessed directly

/** == Main command == **/
function gwptb_post_command_response($upd_data){
	
	$result = array();	
	
	$target_pt = get_option('gwptb_post_target_posttype');
	if(!$target_pt || $target_pt == 'none') 	
		return $result; //no support for /post command
	
		
	if(false !== strpos($upd_data['content'], 'post=')){ //update - store notification meta
		
		$post_id = str_replace('post=', '', $upd_data['content']);
		$post = get_post((int)$post_id);
		if($post){
			update_post_meta($post_id, '_gwptb_notify', 1);			
		}
		
		$result['text'] = __('OK! I tell you when your message will be published.', 'gwptb').chr(10);
	}
	else {
		$self = Gwptb_Self::get_instance();
		$post_content = str_replace(array('@', '/post', $self->get_self_username()), '', $upd_data['content']);
		$post_content = apply_filters('gwptb_post_draft_content', $post_content, $upd_data);
		
		if(!empty($post_content)) {
			//username
			$un = '';
			if(!empty($upd_data['user_fname']) || !empty($upd_data['user_lname'])){
				$un = $upd_data['user_fname'].' '.$upd_data['user_lname'];
			}
			
			if(!empty($upd_data['username'])){
				$un .= ' (@'.$upd_data['username'].')';
			}
			
			$un = apply_filters('gwptb_input_text', trim($un));
			$post_title = sprintf(__('Message from %s at %s', 'gwptb'), $un, date_i18n('d.m.Y - H:i', strtotime('now')));
			$post_title = apply_filters('gwptb_post_draft_title', $post_title, $upd_data);
						
			$postdata = array(
				'post_status' => 'draft',
				'post_type' => $target_pt,
				'post_title' => $post_title,
				'post_content' => $post_content,				
				'meta_input' => array(
					'_gwptb_chat_id' => (int)$upd_data['chat_id'],					
					'_gwptb_user_id' => (int)$upd_data['user_id'],
					'_gwptb_notify'  => 0,
					'_gwptb_user_fname'  => $upd_data['user_fname'],
					'telegram_user'  => $un	
				)
			);
			
			$post_id = wp_insert_post($postdata);
			
			if($post_id){
				$result['text'] = __('Thank you! Your message has been accepted.', 'gwptb').chr(10);
				$result['text'] .= __('Would you like to be notified, when your message is published?', 'gwptb').chr(10);
				
				$keys = array('inline_keyboard' => array());			
				$keys['inline_keyboard'][0][] = array('text' => __('Yes, notify me', 'gwptb'), 'callback_data' => 'post='.$post_id);
				
				$result['reply_markup'] = json_encode($keys);
			}
			else {
				$result['text'] = __('Our posting service is temporary unavailable. Please try again later', 'gwptb');
			}
		}
		else {
			$result['text'] = __('Unfortunately, you have provided wrong data, please format your message as following:', 'gwptb').chr(10).chr(10);
			$result['text'] .= __('/post Message text', 'gwptb');
		}
	}
	
	
	$result['parse_mode'] = 'HTML';
		
	return $result;
}




