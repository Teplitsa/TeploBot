<?php
/** Finctions of the bot */

if(!defined('ABSPATH')) die; // Die if accessed directly


/** == Commands == **/
function gwptb_help_command_response($message){
	
	$result = array();	
	
	//get help text from options	
	$default = __('I can help you to find something useful at %%home%%. Type /search _your term_ to perform a search.', 'gwptb');
	$result['text'] = get_option('gwptb_help_text', $default);
	$result['text'] = str_replace('%%home%%', "[".home_url()."](".home_url().")", $result['text']);
	$result['parse_mode'] = 'Markdown';
		
	return $result;
}

function gwptb_start_command_response($message){
	
	$result = array();	
	
	//get help text from options	
	$default = __('Hello, %%uername%%. Let\'s find something useful. Type /search to perform a search, type /help to get help.', 'gwptb');
	$result['text'] = get_option('gwptb_start_text', $default);
	
	$username = (isset($message->from->first_name)) ? $message->from->first_name : $message->from->username;
	
	$result['text'] = str_replace('%%uername%%', "_".$username."_", $result['text']);
	$result['parse_mode'] = 'Markdown';
		
	return $result;	
}

function gwptb_search_command_response($data, $paged = 1){
	
	$result = array();
	$args = array(
		'post_type' => array('post'), //this should be option
		'posts_per_page' => 5,		
	);
	
	//get search term from data
	if(is_string($data) && !empty($data)){
		$search = $data;
	}
	elseif(isset($data->text)) {		
		$search = trim(str_replace('/search', '', $data->text));		
	}
	
	
	if(!empty($search))
		$args['s'] = $search;
	
	if((int)$paged > 1)
		$args['paged'] = (int)$paged;
	
	
	$query = new WP_Query($args);
	if($query->have_posts()){
		
		$result['text'] = sprintf(__('Found results: %s / displaying %d - %d', 'gwptb'), $query->found_posts, ($paged*5 - 5) + 1, $paged*5).chr(10).chr(10);		
		$result['text'] .= gwptb_formtat_posts_list($query->posts);		
		$result['parse_mode'] = 'HTML';
		
		if($query->found_posts > 5){
			$paged++;
			$keys = array(
				'inline_keyboard' => array(array(array('text' => 'Next', 'callback_data' => 's='.$search.'&paged='.$paged)))
			);	
			$result['reply_markup'] = json_encode($keys);
		}
	}
	else {
		$result['text'] = __('Unfortunately your request didn\'t match anything.', 'gwptb');
	}
	
	return $result;
}


function gwptb_formtat_posts_list($posts){
	
	$out = '';
	
	foreach($posts as $p){
		$out .= "<a href='".get_permalink($p)."'>".get_the_title($p)."</a>".chr(10).chr(10);		
	} 
	
	return $out;
}


