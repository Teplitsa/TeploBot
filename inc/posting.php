<?php
/** Posting of the bot */

if(!defined('ABSPATH')) die; // Die if accessed directly

/** == Commands == **/
function gwptb_post_command_response($upd_data){
	
	$result = array();
	
	//create post to store info at
	$postdata = array(
		'post_status' => 'draft',
		'post_type' => 'post',
		'post_title' => __('Draft', 'gwptb'),
		'meta_input' => array(
			'_gwptb_chat_id' => $upd_data['chat_id'],
			'_gwptb_stage' => 'init'
		)
	);
	
	$post_id = wp_insert_post($postdata);
	
	if($post_id){
		$result['text'] = __('OK. Please provide the title and text for your message with following format:', 'gwptb').chr(10).chr(10);
		$result['text'] .= __('/pc Post title @@ Post content', 'gwptb');
	}
	else {
		$result['text'] = __('Our posting service is temporary unavailable. Please try again later', 'gwptb');
	}
	//$result['text'] = $post_id;	
	$result['parse_mode'] = 'HTML';
		
	return $result;
}


function gwptb_pc_command_response($upd_data){
	
	$result = array();	
	
	//parse content
	$raw = trim(str_replace('/pc ', '', $upd_data['content']));
	$raw = explode('@@', $raw);
	
	
	if(count($raw) == 2){
		//prepare data
		$postdata = array(
			'post_status' => 'draft',
			'post_type' => 'post',
			'post_title' => apply_filters('gwptb_input_text', trim($raw[0])),
			'post_content' => apply_filters('gwptb_input_text', trim($raw[1])),
			'meta_input' => array(
				'_gwptb_chat_id' => $upd_data['chat_id'],
				'_gwptb_stage' => 'final'
			)
		);
		
		//find ID
		$post = get_posts(array(
			'posts_per_page' => 1,
			'post_status' => 'draft',
			'post_type' => 'post',
			'orderby' => 'date',
			'order' => 'DESC',
			'meta_query' => array(
				array(
					'key' => '_gwptb_chat_id',
					'value' => $upd_data['chat_id']
				),
				array(
					'key' => '_gwptb_stage',
					'value' => 'init'
				)
			)
		));
		
		if(!empty($post)){
			$postdata['ID'] = (int)$post[0]->ID;
		}
		
		//save
		if(wp_insert_post($postdata)){
			$result['text'] = __('Thank you! Your message has been accepted', 'gwptb');	
		}
		else {
			$result['text'] = __('Our posting service is temporary unavailable. Please try again later', 'gwptb');
		}
	}
	else {
		$result['text'] = __('Unfortunately, you have provided wrong data, please format your message as following:', 'gwptb').chr(10).chr(10);
		$result['text'] .= __('/pc Post title @@ Post content', 'gwptb');
	}
	//find draft 
	//$result['text'] = $upd_data;	
	
	
	$result['parse_mode'] = 'HTML';
		
	return $result;
}