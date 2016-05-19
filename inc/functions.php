<?php
/** Finctions of the bot */

if(!defined('ABSPATH')) die; // Die if accessed directly


/** == Commands == **/
function gwptb_help_command_response($upd_data){
	
	$result = array();	
	
	//get help text from options	
	$default = __('I can help you to find something useful at %%home%%. Send me _your term_ to perform a search.', 'gwptb');
	$result['text'] = get_option('gwptb_help_text', $default);
	$result['text'] = str_replace('%%home%%', "[".home_url()."](".home_url().")", $result['text']);
	$result['parse_mode'] = 'Markdown';
		
	return $result;
}

function gwptb_start_command_response($upd_data){
	
	$result = array();	
	
	//get help text from options	
	$default = __('Hello, %%uername%%. Let\'s find something useful. Send me _your term_ to perform a search, type /help to get help.', 'gwptb');
	$result['text'] = get_option('gwptb_start_text', $default);
	
	$username = (!empty($upd_data['user_fname'])) ? $upd_data['user_fname'] : $upd_data['username'];
	//still may be empty??
	
	$result['text'] = str_replace('%%uername%%', $username, $result['text']);
	$result['parse_mode'] = 'Markdown';
		
	return $result;	
}

function gwptb_search_command_response($upd_data){
	
	$result = array();
	
	$per_page = 5; //this will be option
	$args = array(
		'post_type' 		=> array('post'), //this should be option
		'posts_per_page'	=> $per_page,		//this too
		's' 				=> '',
		'paged' 			=> 1
	);
	
	//get search term from $upd_data - it may be update data
	if(false !== strpos($upd_data['content'], 's=')){ //update
		
		parse_str($upd_data['content'], $a);
		
		if(isset($a['s']) && isset($a['paged'])){
			$args['s'] = apply_filters('gwptb_search_term', $a['s']);
			$args['paged'] = (int)$a['paged'];
		}
	}	
	else { //init search
		$args['s'] = apply_filters('gwptb_search_term', $upd_data['content']);
	}
	
	if(empty($args['s'])) {
		//don't perform empty search
		$result['text'] = __('Unfortunately your request didn\'t match anything.', 'gwptb');
		return $result;
	}
	
	$paged = $args['paged'];	
	$query = new WP_Query($args);
	
	if($query->have_posts()){
		
		if($query->found_posts > $per_page){
			$result['text'] = sprintf(__('Found results: %s / displaying %d - %d', 'gwptb'), $query->found_posts, ($paged*$per_page - $per_page) + 1, $paged*$per_page).chr(10).chr(10);
		}
		else {
			$result['text'] = sprintf(__('Found results: %s', 'gwptb'), $query->found_posts.chr(10).chr(10));
		}
						
		$result['text'] .= gwptb_formtat_posts_list($query->posts);		
		$result['parse_mode'] = 'HTML';
		
		
		if($query->found_posts > $per_page){
			//next/prev			
			$keys = array('inline_keyboard' => array());
			
			if($paged > 1){
				$keys['inline_keyboard'][0][] = array('text' => __('Previous', 'gwptb'), 'callback_data' => 's='.$args['s'].'&paged='.($paged-1));				
			}
			
			if($paged < ceil($query->found_posts/$per_page)) {
				$keys['inline_keyboard'][0][] = array('text' => __('Next', 'gwptb'), 'callback_data' => 's='.$args['s'].'&paged='.($paged+1));		
			}
			
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



