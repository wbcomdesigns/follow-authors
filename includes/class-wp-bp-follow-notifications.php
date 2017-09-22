<?php

/**
 * WP BP Follow Notifications Functions
 *
 * @link       http://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/includes
 */
/**
 * The common functionality of the plugin.
 *
 * Defines the common functions
 *
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/includes
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/** NOTIFICATIONS API ************************************************** */

/**
 * Format on screen notifications into something readable by users.
 *
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function wp_bp_follow_format_notifications($action, $item_id, $secondary_item_id, $total_items, $format = 'string') {
    global $bp;

    do_action('wp_bp_follow_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $format);

    switch ($action) {
        case 'new_follow':
            $link = $text = false;

            if (1 == $total_items) {
				$follower = bp_core_get_user_displayname($item_id);
                $text = __(" $follower is now following you", WPBP_FOLLOW_DOMAIN );
                $link = bp_core_get_user_domain($item_id) . '?wpbpf_read';
            } else {				
                $text = __(" $total_items more users are now following you", WPBP_FOLLOW_DOMAIN );

                if (bp_is_active('notifications')) {
                    $link = bp_get_notifications_permalink();
                } else {
                    $link = bp_loggedin_user_domain() . $bp->follow->followers->slug . '/?new';
                }
            }

            break;
        default :
            $link = apply_filters('wp_bp_follow_extend_notification_link', false, $action, $item_id, $secondary_item_id, $total_items);
            $text = apply_filters('wp_bp_follow_extend_notification_text', false, $action, $item_id, $secondary_item_id, $total_items);
            break;
    }

    if (!$link || !$text) {
        return false;
    }

    if ('string' == $format) {
        return apply_filters('wp_bp_follow_new_followers_notification', '<a href="' . $link . '">' . $text . '</a>', $total_items, $link, $text, $item_id, $secondary_item_id);
    } else {
        $array = array(
            'text' => $text,
            'link' => $link
        );

        return apply_filters('wp_bp_follow_new_followers_return_notification', $array, $item_id, $secondary_item_id, $total_items);
    }
}

/**
 * Adds notification when a user follows another user.
 *
 * @since 1.2.1
 *
 * @param object $follow The Wp_Bp_Follow_Common object.
 */
function wp_bp_follow_notifications_add_on_follow(Wp_Bp_Follow_Common $follow) {
    // Add a screen notification
    //
	// BP 1.9+
    if (bp_is_active('notifications')) {
        bp_notifications_add_notification(array(
            'item_id' => $follow->follower_id,
            'user_id' => $follow->leader_id,
            'component_name' => buddypress()->follow->id,
            'component_action' => 'new_follow',
			'allow_duplicate'   => true
        ));
        // BP < 1.9 - add notifications the old way
    } elseif (!class_exists('BP_Core_Login_Widget')) {
        global $bp;

        bp_core_add_notification(
                $follow->follower_id, $follow->leader_id, $bp->follow->id, 'new_follow'
        );
    }

    // Add an email notification
    wp_bp_follow_new_follow_email_notification(array(
        'leader_id' => $follow->leader_id,
        'follower_id' => $follow->follower_id
    ));
}

add_action('wp_bp_follow_start_following', 'wp_bp_follow_notifications_add_on_follow');

/** EMAIL ************************************************************** */

/**
 * Send an email to the leader when someone follows them.
 *
 * @uses bp_core_get_user_displayname() Get the display name for a user
 * @uses bp_core_get_user_domain() Get the profile url for a user
 * @uses bp_core_get_core_userdata() Get the core userdata for a user without extra usermeta
 * @uses wp_mail() Send an email using the built in WP mail class
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function wp_bp_follow_new_follow_email_notification($args = '') {

    $r = wp_parse_args($args, array(
        'leader_id' => bp_displayed_user_id(),
        'follower_id' => bp_loggedin_user_id()
    ));

    // Don't send email for yourself!
    if ($r['follower_id'] === $r['leader_id']) {
        return false;
    }

    if ('no' == bp_get_user_meta((int) $r['leader_id'], 'notification_starts_following', true))
        return false;

    // Check to see if this leader has already been notified of this follower before
    $has_notified = bp_get_user_meta($r['follower_id'], 'bp_follow_has_notified', true);

    // Already notified so don't send another email
    if (in_array($r['leader_id'], (array) $has_notified))
        return false;

    // Not been notified before, update usermeta and continue to mail
    $has_notified[] = $r['leader_id'];
    bp_update_user_meta($r['follower_id'], 'bp_follow_has_notified', $has_notified);

    $follower_name = wp_specialchars_decode(bp_core_get_user_displayname($r['follower_id']), ENT_QUOTES);
    $follower_link = bp_core_get_user_domain($r['follower_id']) . '?wpbpf_read';

    $leader_ud = bp_core_get_core_userdata($r['leader_id']);

    // Set up and send the message
    $to = $leader_ud->user_email;

    $subject = '[' . wp_specialchars_decode(bp_get_option('blogname'), ENT_QUOTES) . '] ' . sprintf(__('%s is now following you', WPBP_FOLLOW_DOMAIN ), $follower_name);

    $message = sprintf(__('%s is now following your activity. To view %s\'s profile: %s', WPBP_FOLLOW_DOMAIN ), $follower_name, $follower_name, $follower_link);

    // Add notifications link if settings component is enabled
    if (bp_is_active('settings')) {
        $settings_link = bp_core_get_user_domain($r['leader_id']) . BP_SETTINGS_SLUG . '/notifications/';
        $message .= sprintf(__('  To disable these notifications please log in and go to: %s', WPBP_FOLLOW_DOMAIN ), $settings_link);
    }

    // Send the message
    $to = apply_filters('wp_bp_follow_notification_to', $to);
    $subject = apply_filters('wp_bp_follow_notification_subject', $subject, $follower_name);
    $message = apply_filters('wp_bp_follow_notification_message', wp_specialchars_decode($message, ENT_QUOTES), $follower_name, $follower_link);

    wp_mail($to, $subject, $message);
}

/**
 * When we're on the notification's 'read' page, remove 'wpbpf_read' query arg.
 *
 * Since we are already on the 'read' page, notifications on this page are
 * already marked as read.  So, we no longer need to add our special
 * 'wpbpf_read' query argument to each notification to determine whether we
 * need to clear it.
 *
 * @since 1.0.0
 */
function wp_bp_follow_notifications_remove_queryarg_from_userlink($retval) {
    if (bp_is_current_action('read')) {
        // if notifications loop has finished rendering, stop now!
        // this is so follow notifications in the adminbar are unaffected
        if (did_action('bp_after_member_body')) {
            return $retval;
        }

        $retval = str_replace('?wpbpf_read', '', $retval);
    }

    return $retval;
}

add_filter('wp_bp_follow_new_followers_notification', 'wp_bp_follow_notifications_remove_queryarg_from_userlink');

/**
 * Mark notification as read when a logged-in user visits their follower's profile.
 *
 *
 * @since 1.0.0
 */
function wp_bp_follow_notifications_mark_follower_profile_as_read() {
    if (!isset($_GET['wpbpf_read'])) {
        return;
    }

    // mark notification as read
    if (bp_is_active('notifications')) {
        bp_notifications_mark_notifications_by_item_id(bp_loggedin_user_id(), bp_displayed_user_id(), buddypress()->follow->id, 'new_follow');

        // check if we're not on BP 1.9
        // if so, delete notification since marked functionality doesn't exist
    } elseif (!class_exists('BP_Core_Login_Widget')) {
        global $bp;

        bp_core_delete_notifications_by_item_id(bp_loggedin_user_id(), bp_displayed_user_id(), $bp->follow->id, 'new_follow');
    }
}

add_action('bp_members_screen_display_profile', 'wp_bp_follow_notifications_mark_follower_profile_as_read');

//FOR BP FAVOURITE NOTIFICATION

function favourite_user_filter_notifications_get_registered_components($component_names = array()) {

    // Force $component_names to be an array
    if (!is_array($component_names)) {
        $component_names = array();
    }
    array_push($component_names, 'custom');
    return $component_names;
}

add_filter('bp_notifications_get_registered_components', 'favourite_user_filter_notifications_get_registered_components');

function bp_custom_format_buddypress_notifications($action, $item_id, $secondary_item_id, $total_items, $format = 'string') {
	 global $bp;
    // New custom notifications
    if ('new_favourite' === $action) {

        $data = get_option('wp_bp_fav_data');

        $custom_title = get_the_author_meta('display_name', $data['user_id']) . ' mark you as favourite'; //get_the_author_meta('display_name', $post_info->post_author) . ' posted a new post ' . get_the_title($item_id);
        $custom_link = bp_core_get_user_domain($data['user_id']) . '?wpbpfav_read'; //get_post_permalink($item_id);
        $custom_text = get_the_author_meta('display_name', $data['user_id']) . ' mark you as favourite'; //get_the_author_meta('display_name', $post_info->post_author) . ' posted a new post ' . get_the_title($item_id);
        // WordPress Toolbar
        if ('string' === $format) {
          $content_custom_link = esc_url($custom_link);
			$content_custom_title = esc_attr($custom_title);
			$content_custom_text  = esc_html($custom_text);
			$content = __("<a href= '$content_custom_link' title= '$content_custom_title'> $content_custom_text </a>", '');
            $return = apply_filters('new_favourite_filter', 'add fav.' );

            // Deprecated BuddyBar
        } else {
            $return = apply_filters('new_favourite_filter', array(
                'text' => $custom_text,
                'link' => $custom_link
                    ), $custom_link, (int) $total_items, $custom_text, $custom_title);
					//echo "<pre>"; print_r( $return ); echo "</pre>"; 
        }
		
        return $return;
    }
    if ('new_favourite_post' === $action) {

        $post_info = get_post($item_id);

        $custom_title = get_the_author_meta('display_name', $post_info->post_author) . ' posted a new post ' . get_the_title($item_id);
        $custom_link = get_post_permalink($item_id) . '&wpbpfav_posts_read';
        $custom_text = get_the_author_meta('display_name', $post_info->post_author) . ' posted a new post ' . get_the_title($item_id);
		
        // WordPress Toolbar
        if ('string' === $format) {
            $return = apply_filters('new_favourite_post_filter', '<a href="' . esc_url($custom_link) . '" title="' . esc_attr($custom_title) . '">' . esc_html($custom_text) . '</a>', $custom_text, $custom_link);

            // Deprecated BuddyBar
        } else {
            $return = apply_filters('new_favourite_post_filter', array(
                'text' => $custom_text,
                'link' => $custom_link
                    ), $custom_link, (int) $total_items, $custom_text, $custom_title);
        }
		
        return $return;
    }
}

add_filter('bp_notifications_get_notifications_for_user', 'bp_custom_format_buddypress_notifications', 10, 5);

function wp_bp_favourite_start_favourite_notification($author_id, $user_id) {

    if (bp_is_active('notifications')) {
        bp_notifications_add_notification(array(
            'user_id' => $author_id,
            'item_id' => $user_id,
            'component_name' => 'custom',
            'component_action' => 'new_favourite',
            'date_notified' => bp_core_current_time(),
            'is_new' => 1,
			'allow_duplicate'   => true
        ));
        wp_bp_favourite_get_notification_data($author_id, $user_id);
    }
}

add_action('wp_bp_favourite_start_favourite', 'wp_bp_favourite_start_favourite_notification', 99, 2);

function wp_bp_favourite_get_notification_data($author_id, $user_id) {
    $data = array('author_id' => $author_id, 'user_id' => $user_id);
    update_option('wp_bp_fav_data', $data);
}

/**
 * Mark notification as read when a logged-in user visits their profile.
 *
 *
 * @since 1.0.0
 */
function wp_bp_favourite_notifications_mark_favourite_profile_as_read() {
    if (!isset($_GET['wpbpfav_read'])) {
        return;
    }

    // mark notification as read
    if (bp_is_active('notifications')) {
        bp_notifications_mark_notifications_by_item_id(bp_loggedin_user_id(), bp_displayed_user_id(), 'custom', 'new_favourite');

        // check if we're not on BP 1.9
        // if so, delete notification since marked functionality doesn't exist
    } elseif (!class_exists('BP_Core_Login_Widget')) {
        global $bp;

        bp_core_delete_notifications_by_item_id(bp_loggedin_user_id(), bp_displayed_user_id(), 'custom', 'new_favourite');
    }
}

add_action('bp_members_screen_display_profile', 'wp_bp_favourite_notifications_mark_favourite_profile_as_read');

function wp_bp_favourite_notifications_mark_favourite_posts_profile_as_read($query) {
    if (!isset($_GET['wpbpfav_posts_read'])) {
        return;
    }

    if ($query->is_single()) {
        if ($post = get_page_by_path($query->query['name'], OBJECT, 'post')) {
            $pid = $post->ID;
        }
        // mark notification as read
        if (bp_is_active('notifications')) {

            bp_notifications_mark_notifications_by_item_id(bp_loggedin_user_id(), $pid, 'custom', 'new_favourite_post');

            // check if we're not on BP 1.9
            // if so, delete notification since marked functionality doesn't exist
        } elseif (!class_exists('BP_Core_Login_Widget')) {
            global $bp;

            bp_core_delete_notifications_by_item_id(bp_loggedin_user_id(), $pid, 'custom', 'new_favourite_post');
        }
    }
}

add_action('pre_get_posts', 'wp_bp_favourite_notifications_mark_favourite_posts_profile_as_read');

/**
 * When we're on the notification's 'read' page, remove 'wpbpf_read' query arg.
 *
 * Since we are already on the 'read' page, notifications on this page are
 * already marked as read.  So, we no longer need to add our special
 * 'wpbpf_read' query argument to each notification to determine whether we
 * need to clear it.
 *
 * @since 1.0.0
 */
function wp_bp_follow_notifications_remove_queryarg_from_userlink_fav_posts($retval) {
    if (bp_is_current_action('read')) {
        $retval = str_replace('&wpbpfav_post_read', '', $retval);
    }

    return $retval;
}

add_filter('new_favourite_post_filter', 'wp_bp_follow_notifications_remove_queryarg_from_userlink_fav_posts');

function wp_bp_follow_notifications_remove_queryarg_from_userlink_fav($retval) {
	global $bp;
    if (bp_is_current_action('read')) {
        // if notifications loop has finished rendering, stop now!
        // this is so follow notifications in the adminbar are unaffected
        if (did_action('bp_after_member_body')) {
            return $retval;
        }

        $retval = str_replace('?wpbpfav_read', '', $retval);
    }
	echo "<pre>"; print_r( $retval ); echo "</pre>mmm";
    return $retval;
}

add_filter('new_favourite_filter', 'wp_bp_follow_notifications_remove_queryarg_from_userlink_fav');

function wp_bp_post_published_notification($post_id, $post) {
    $author_id = $post->post_author; /* Post author ID. */
    $fav_author_options = get_option('wbf_favorit_authors');
    if (!empty($fav_author_options)) {
        foreach ($fav_author_options as $fav_uid => $data) {
            if (in_array($author_id, $data['author_ids'])) {
                if (bp_is_active('notifications')) {
                    bp_notifications_add_notification(array(
                        'user_id' => $fav_uid,
                        'item_id' => $post_id,
                        'component_name' => 'custom',
                        'component_action' => 'new_favourite_post',
                        'date_notified' => bp_core_current_time(),
                        'is_new' => 1,
						'allow_duplicate'   => true
                    ));
 
                    wp_bp_follow_new_favourite_posts_email_notification(array(
                        'user_id' => $fav_uid,
                        'item_id' => $post_id
                    ));
                }
            }
        }
    }
}

add_action('publish_post', 'wp_bp_post_published_notification', 99, 2);

/**
 * Send an email to the leader when favourite user update new post.
 *
 * @uses bp_core_get_user_displayname() Get the display name for a user
 * @uses bp_core_get_user_domain() Get the profile url for a user
 * @uses bp_core_get_core_userdata() Get the core userdata for a user without extra usermeta
 * @uses wp_mail() Send an email using the built in WP mail class
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function wp_bp_follow_new_favourite_posts_email_notification($args = '') {

    if (count(array_filter($args)) <= 0) {
        return;
    }
    $user_id = $args['user_id'];
    $post_id = $args['item_id'];
    $post = get_post($post_id);
    $follower_name = wp_specialchars_decode(bp_core_get_user_displayname($post->post_author), ENT_QUOTES);
    $post_link = get_permalink($post);
    $user_data = bp_core_get_core_userdata($user_id);
    // Set up and send the message
    $to = $user_data->user_email;
    $subject = '[' . wp_specialchars_decode(bp_get_option('blogname'), ENT_QUOTES) . '] ' . sprintf(__('%s posted a new post ', WPBP_FOLLOW_DOMAIN ), $follower_name);
    $message = sprintf(__('%s is posted a new post. To view %s\'s post: %s', WPBP_FOLLOW_DOMAIN ), $follower_name, $follower_name, $post_link);
    // Send the message
    $to = apply_filters('wp_bp_follow_favourite_posts_notification_to', $to);
    $subject = apply_filters('wp_bp_follow_favourite_posts_notification_subject', $subject, $follower_name);
    $message = apply_filters('wp_bp_follow_favourite_posts_notification_message', wp_specialchars_decode($message, ENT_QUOTES), $follower_name, $follower_link);

    wp_mail($to, $subject, $message);
}
