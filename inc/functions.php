<?php
/** Finctions of the bot */

if(!defined('ABSPATH')) die; // Die if accessed directly


/** == Commands == **/
function gwptb_help_command_response($upd_data){
	
	$result = array();	
	
	//get help text from options		
	$result['text'] = get_option('gwptb_help_text');
	$result['text'] = str_replace('%%home%%', "<a href='".home_url()."'>".home_url()."</a>", $result['text']);
	
	$result['text'] = apply_filters('gwptb_output_html', $result['text']);
	$result['parse_mode'] = 'HTML';
		
	return $result;
}

function gwptb_start_command_response($upd_data){
	
	$result = array();	
	
	//get help text from options		
	$result['text'] = get_option('gwptb_start_text');
	
	$username = (!empty($upd_data['user_fname'])) ? $upd_data['user_fname'] : $upd_data['username'];	
	$result['text'] = str_replace('%%username%%', $username, $result['text']);
	
	$result['text'] = apply_filters('gwptb_output_html', $result['text']);	
	$result['parse_mode'] = 'HTML';
		
	return $result;	
}

function gwptb_search_command_response($upd_data){
	
	$result = array();
	
	$per_page = 5; //this will be option
	$args = array(
		'post_type' 		=> apply_filters('gwptb_search_command_post_types', array('post')), 
		'posts_per_page'	=> $per_page,		
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
		$self = Gwptb_Self::get_instance();
		$args['s'] = apply_filters('gwptb_search_term', str_replace(array('@', '/s', $self->get_self_username()), '', $upd_data['content']));
	}
	
	if(empty($args['s'])) {			
		//don't perform empty search
		$result['text'] = apply_filters('gwptb_output_text', __('Unfortunately you\'ve submitted empty or incorrect request - provide the search term after /s command.', 'gwptb'));
		
		return $result;
	}
	
	
	$paged = $args['paged'];	
	$query = new WP_Query($args);
	
	if($query->have_posts()){
		
		if($query->found_posts > $per_page){
			$end = ($paged*$per_page < $query->found_posts) ? $paged*$per_page : $query->found_posts;
			$result['text'] = sprintf(__('Found results: %s / displaying %d - %d', 'gwptb'), $query->found_posts, ($paged*$per_page - $per_page) + 1, $end).chr(10).chr(10);
		}
		else {
			$result['text'] = sprintf(__('Found results: %s', 'gwptb'), $query->found_posts.chr(10).chr(10));
		}
						
		$result['text'] .= gwptb_format_posts_list($query->posts);
		$result['text'] = apply_filters('gwptb_output_html', $result['text']);
		
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
		$result['text'] = apply_filters('gwptb_output_text', $result['text']);
	}
	
	return $result;
}

function gwptb_custom_command_response($upd_data){
	//add command param to $upd_data
	
	if(!isset($upd_data['command']) || empty($upd_data['command'])) { //no command in data
		$result['text'] = apply_filters('gwptb_output_text', __('Unfortunately you\'ve submitted an incorrect request.', 'gwptb'));
		return $result;
	}
	
	$per_page = 5; //this will be option
	
	//command args
	$command = $upd_data['command'];
	$bot = Gwptb_Self::get_instance();
	$command_args = $bot->get_custom_command_args($command);
	
	$title = (!empty($command_args['title'])) ? apply_filters('gwptb_output_text', $command_args['title']) : '';		
	$post_type = (!empty($command_args['post_type'])) ? $command_args['post_type'] : 'post';
	$post_type = array_map('trim', explode(',', $post_type));
	
	$qv = array(
		'post_type' => (array)$post_type,
		'posts_per_page' => $per_page,
		'paged' => 1
	);
			
	if(false !== strpos($upd_data['content'], 'paged=')){ //update
		
		parse_str($upd_data['content'], $a);
		
		if(isset($a['paged'])){
			$qv['paged'] = (int)$a['paged'];
		}
	}	
		
	$paged = $qv['paged'];	
	$query = new WP_Query($qv);
	
	if($query->have_posts()){
		
		if($query->found_posts > $per_page){
			$end = ($paged*$per_page < $query->found_posts) ? $paged*$per_page : $query->found_posts;
			$result['text'] = sprintf(__('%s. Total %d / displaying %d - %d', 'gwptb'), $title, $query->found_posts, ($paged*$per_page - $per_page) + 1, $end).chr(10).chr(10);
		}
		else {
			$result['text'] = $title.chr(10).chr(10);
		}
						
		$result['text'] .= gwptb_format_posts_list($query->posts);
		$result['text'] = apply_filters('gwptb_output_html', $result['text']);
		
		$result['parse_mode'] = 'HTML';
		
		
		if($query->found_posts > $per_page){
			//next/prev			
			$keys = array('inline_keyboard' => array());
			
			if($paged > 1){
				$keys['inline_keyboard'][0][] = array('text' => __('Previous', 'gwptb'), 'callback_data' => $command.'=1&paged='.($paged-1));				
			}
			
			if($paged < ceil($query->found_posts/$per_page)) {
				$keys['inline_keyboard'][0][] = array('text' => __('Next', 'gwptb'), 'callback_data' => $command.'=1&paged='.($paged+1));		
			}
			
			$result['reply_markup'] = json_encode($keys);
		}
	}
	else {
		$result['text'] = __('Unfortunately your request didn\'t match anything.', 'gwptb');
		$result['text'] = apply_filters('gwptb_output_text', $result['text']);
	}
	
	return $result;
}


function gwptb_format_posts_list($posts){
	
	$out = '';
	
	foreach($posts as $p){
		$out .= "<a href='".get_permalink($p)."'>".esc_html(get_the_title($p))."</a>".chr(10).chr(10);		
	} 
	
	return $out;
}