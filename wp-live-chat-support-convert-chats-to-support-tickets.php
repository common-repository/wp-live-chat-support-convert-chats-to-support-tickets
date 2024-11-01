<?php
/*
Plugin Name: WP Live Chat Support - Convert Chats to Support Tickets
Plugin URL: http://wp-livechat.com
Description: Easily convert your WP Live Chat Support chat sessions to tickets on Sola Support Tickets
Version: 1.0.1
Author: WPLiveChat
Author URI: http://wp-livechat.com
Contributors: WPLiveChat,CodeCabin_, NickDuncan, Jarryd Long
Text Domain: wp-live-chat-support-convert-chats-to-support-tickets
Domain Path: /languages
*/


/*
* 1.0.1 - 20 September 2016
* Tested on WordPress 4.6.1
*
* 1.0.0
* Launch
*/


if(!defined('WPLC_CCTT_PLUGIN_DIR')) {
	define('WPLC_CCTT_PLUGIN_DIR', dirname(__FILE__));
}

global $wplc_cctt_version;
global $current_chat_id;
$wplc_cctt_version = "1.0.1";

/* hooks */
add_action('wplc_hook_admin_visitor_info_display_after','wplc_cctt_add_admin_button');
add_action("wplc_hook_admin_menu_layout","wplc_cctt_check_if_plugins_active");
add_action('wplc_hook_admin_javascript_chat','wplc_cctt_admin_javascript');
add_action('wplc_hook_admin_settings_main_settings_after','wplc_cctt_settings');
add_action('wplc_hook_admin_settings_save','wplc_cctt_save_settings');

/* ajax callbacks */
add_action('wp_ajax_wplc_cctt_admin_convert_chat', 'wplc_cctt_callback');


/* init */
add_action("init","wplc_cctt_first_run_check");




/**
* Check if Sola Support Tickets is installed and active
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_cctt_check_if_plugins_active() {
	if (!is_plugin_active('sola-support-tickets/sola-support-tickets.php')) {
		
		echo "<div class='error'>";
		echo "<p>".sprintf( __( '<strong><a href="%1$s" title="Install Sola Support Tickets">Sola Support Tickets</strong></a> is required for the <strong>WP Live Chat Support - Convert Chat to Ticket</strong> add-on to work. Please install and activate it.', 'wp-live-chat-support-convert-chats-to-support-tickets' ),
            "plugin-install.php?tab=search&s=sola+support+tickets"
            )."</p>";
        echo "</div>";
        
	}

}


/**
* Check if this is the first time the user has run the plugin. If yes, set the default settings
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_cctt_first_run_check() {
	if (!get_option("WPLC_CCTT_FIRST_RUN")) {
		/* set the default settings */
		$wplc_cctt_data['wplc_cctt_enable'] = 1;
      

        update_option('WPLC_CCTT_SETTINGS', $wplc_cctt_data);
        update_option("WPLC_CCTT_FIRST_RUN",true);
	}
}


/**
* Adds the convert to ticket button to the visitor box in the active chat window
*
* @since       1.0.0
* @param       int $cid The current chat ID
* @return
*
*/
function wplc_cctt_add_admin_button($cid) {
	$wplc_cctt_settings = get_option("WPLC_CCTT_SETTINGS");
	$wplc_enable = $wplc_cctt_settings['wplc_cctt_enable'];
	if (isset($wplc_enable) && $wplc_enable == 1 && is_plugin_active('sola-support-tickets/sola-support-tickets.php')) {

		/* check if we have a ticket created for this chat already */
		$args = array(
		    'meta_key' => 'ticket_chat_id',
		    'meta_value' => $cid,
		    'post_type' => 'sola_st_tickets',
		    'post_status' => 'any',
		    'posts_per_page' => 1
		);
		$posts = get_posts($args);
		
		if ($posts) {
			echo "<p><a href=\"post.php?post=".$posts[0]->ID."&action=edit\" class=\"button button-secondary\" title=\"".__("View support ticket","wp-live-chat-support-convert-chats-to-support-tickets")."\" id=\"wplc_cctt_view_ticket\">".__("View support ticket","wp-live-chat-support-convert-chats-to-support-tickets")."</a></p>";
		} else {
			echo "<p><a href=\"javascript:void(0);\" cid='".sanitize_text_field($cid)."' class=\"wplc_admin_convert_chat_to_ticket button button-secondary\" title=\"".__("Convert to support ticket","wp-live-chat-support-convert-chats-to-support-tickets")."\" id=\"wplc_admin_convert_chat_to_ticket\">".__("Convert to support ticket","wp-live-chat-support-convert-chats-to-support-tickets")."</a></p>";
		}
	}
}




/**
* Adds the javascript calls to the chat window which handles the ajax requests
*
* @since       [1.0.0]
* @param       
* @return
*
*/
function wplc_cctt_admin_javascript() {
	$wplc_cctt_ajax_nonce = wp_create_nonce("wplc_cctt_nonce");
    wp_register_script('wplc_cctt_convert_admin', plugins_url('js/wplc_cctt.js', __FILE__), null, '', true);
    wp_enqueue_script('wplc_cctt_convert_admin');
    wp_localize_script( 'wplc_cctt_convert_admin', 'wplc_cctt_nonce', $wplc_cctt_ajax_nonce);
	$wplc_cctt_string_loading = __("Creating ticket...","wp-live-chat-support-convert-chats-to-support-tickets");
    $wplc_cctt_string_ticket_created = __("Ticket created","wp-live-chat-support-convert-chats-to-support-tickets");
    $wplc_cctt_string_ticket_link_text = __("Click to view","wp-live-chat-support-convert-chats-to-support-tickets");
    $wplc_cctt_string_error1 = sprintf(__("There was a problem creating the ticket. Please <a target='_BLANK' href='%s'>contact support</a>.","wp-live-chat-support-convert-chats-to-support-tickets"),"http://wp-livechat.com/contact-us/?utm_source=plugin&utm_medium=link&utm_campaign=error_creating_ticket");
    wp_localize_script( 'wplc_cctt_convert_admin', 'wplc_cctt_string_ticket_created', $wplc_cctt_string_ticket_created);
    wp_localize_script( 'wplc_cctt_convert_admin', 'wplc_cctt_string_ticket_link_text', $wplc_cctt_string_ticket_link_text);
    wp_localize_script( 'wplc_cctt_convert_admin', 'wplc_cctt_string_error1', $wplc_cctt_string_error1);
    wp_localize_script( 'wplc_cctt_convert_admin', 'wplc_cctt_string_loading', $wplc_cctt_string_loading);

}





/**
* Ajax callback handler
*
* @since       	1.0.0
* @param       
* @return 		void
*
*/
function wplc_cctt_callback() {
	$check = check_ajax_referer( 'wplc_cctt_nonce', 'security' );
	if ($check == 1) {

        if ($_POST['action'] == "wplc_cctt_admin_convert_chat") {
        	if (isset($_POST['cid'])) {
        		$cid = intval($_POST['cid']);
        		echo json_encode(wplc_cctt_convert_chat(sanitize_text_field($cid)));
        	} else {
        		echo json_encode(array("error"=>"no CID"));
        	}
        	wp_die();
        }

        wp_die();
    }
    wp_die();
}


/**
* Converts the chat to the ticket
*
* @since 		1.0.0
* @param 		int $cid Chat ID
* @return 		array Returns either true or an error with a description of the error
*
*/
function wplc_cctt_convert_chat($cid) {
	
	if (!$cid) { return array("errorstring"=>"no CID"); return; }


 	global $wpdb;
    global $wplc_tblname_chats;
    $results = $wpdb->get_results("SELECT * FROM $wplc_tblname_chats WHERE `id` = '$cid' LIMIT 1");
    
    foreach ($results as $result) {
         $email = $result->email;
         $name = $result->name;
    }
    if (!$email) { return array("error"=>"no email"); }



	if( email_exists( $email )) {
          $post_user_data = get_user_by('email',$email);
          $post_user = $post_user_data->ID;


    } else {
        if (!$name) { $username = $email; } else { $username = $name.rand(0,9).rand(0,9).rand(0,9); }
        $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
        $post_user = wp_create_user( $username, $random_password, $email );
		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail($email,__('Your support desk log in details','sola_st'), __("Login URL:","sola_st"). " ". wp_login_url(). " <br/><br/> ".__("Username:","sola_st"). " ".$username. " <br/><br/> ".__("Password:","sola_st")." ".$random_password,$headers);	
    }

    $content = wplc_cctt_get_transcript($cid);
	$data = array(
        'post_content' => $content,
        'post_status' => 'publish',
        'post_title' => sprintf(__("Chat transcript with %s (%s)","wp-live-chat-support-convert-chats-to-support-tickets"),$name,$email),
        'post_type' => 'sola_st_tickets',
        'post_author' => $post_user,
        'comment_status' => 'closed',
        'ping_status' => 'closed'
    );
    $post_id = wp_insert_post($data);
	$default_user = get_option("sola_st_default_assigned_to");
	add_post_meta( $post_id, 'ticket_status', '0', true );
	if ($default_user)  { add_post_meta( $post_id, 'ticket_assigned_to', $default_user, true ); } else { add_post_meta( $post_id, 'ticket_assigned_to', '0', true ); } /* 0 is default administrator */
    add_post_meta( $post_id, 'ticket_public', '0', true );
	add_post_meta( $post_id, 'ticket_chat_id', $cid, true );

    
    if (function_exists("sola_st_notification_control")) { sola_st_notification_control('ticket', $post_id, $post_user,false,false,$content); }
    return array("success"=>$post_id);

}


/**
* Return the body of the chat transcript
*
* @since       	1.0.0
* @param       
* @return		string Transcript HTML
*
*/
function wplc_cctt_get_transcript($cid) {
    if (intval($cid) > 0) { 
		return wplc_return_chat_messages(intval($cid),true,false);
	} else {
		return "0";
	}

}


/**
* Latch onto the default POST handling when saving live chat settings
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_cctt_save_settings() {
	if (isset($_POST['wplc_save_settings'])) {
        if (isset($_POST['wplc_cctt_enable'])) {
            $wplc_cctt_data['wplc_cctt_enable'] = esc_attr($_POST['wplc_cctt_enable']);
        } else {
        	$wplc_cctt_data['wplc_cctt_enable'] = 0;
        }
        update_option('WPLC_CCTT_SETTINGS', $wplc_cctt_data);

    }
}

/**
* Display the chat conversion settings section
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_cctt_settings() {
	$wplc_cctt_settings = get_option("WPLC_CCTT_SETTINGS");
	echo "<hr />";
	echo "<h3>".__("Chat To Ticket Conversion Settings",'wp-live-chat-support-convert-chats-to-support-tickets')."</h3>";
	echo "<table class='form-table' width='700'>";
	echo "	<tr>";
	echo "		<td width='400' valign='top'>".__("Enable conversion:","wp-live-chat-support-convert-chats-to-support-tickets")."</td>";
	echo "		<td>";
	echo "			<input type=\"checkbox\" value=\"1\" name=\"wplc_cctt_enable\" ";
	if(isset($wplc_cctt_settings['wplc_cctt_enable'])  && $wplc_cctt_settings['wplc_cctt_enable'] == 1 ) { echo "checked"; }
	echo " />";
	echo "		</td>";
	echo "	</tr>";


	echo "</table>";
}