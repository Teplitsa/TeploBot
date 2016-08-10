<?php

# subscription
function gwptb_get_post_teaser($post) {
    # get teaser from post content and excerpt
    $short = $post->post_excerpt;
    if(!$short) {
        if( preg_match( '/<!--more(.*?)?-->/', $post->post_content, $matches ) ) {
            $parts = explode( $matches[0], $post->post_content, 2 );
            $short = $parts[0];
        }
        else {
            $short = wp_trim_words($post->post_content);
        }
    }
    $short = apply_filters( 'get_the_excerpt', $short );
    $short = preg_replace("/&#?[a-z0-9]+;/i", "", $short);

    return $short;
}

function gwptb_notify_subscribers($subscription_name, $message) {
    global $wpdb;

    $table_name = Gwptb_Core::get_chat_subscriptions_tablename();
    $subscribed_chat_list = $wpdb->get_results($wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s ", $subscription_name));
    
    $telebot = Gwptb_Self::get_instance();
    foreach($subscribed_chat_list as $chat) {
        $telebot->send_notification(array('chat_id' => $chat->chat_id, 'text' => $message, 'parse_mode' => 'HTML'));
    }
}
