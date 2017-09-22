<?php

/**
 * WP BP Follow Action Functions
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
 * Defines the action functions
 *
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/includes
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
/** AJAX ACTIONS ************************************************** */

/**
 * AJAX callback when clicking on the "Follow" button to follow a user.
 *
 * @uses check_admin_referer() Checks to make sure the WP security nonce matches.
 * @uses wp_bp_follow_start_following() Starts a user following another user.
 * @uses wp_bp_follow_is_following() Checks to see if a user is following another user already.
 */
function wp_bp_follow_ajax_action_start() {

    check_admin_referer('start_following');
    $uid = sanitize_text_field( $_POST['uid'] );	
    // successful follow
    if (wp_bp_follow_start_following(array('leader_id' => $uid, 'follower_id' => bp_loggedin_user_id()))) {
// output unfollow button
        $output = wp_bp_follow_get_add_follow_button(array(
            'leader_id' => $uid,
            'follower_id' => bp_loggedin_user_id(),
            'wrapper' => false
        ));
		
        // failed follow
    } else {
        // output fallback invalid button
        $args = array(
            'id' => 'invalid',
            'link_href' => 'javascript:;',
            'component' => 'follow',
            'wrapper' => false
        );

        if (wp_bp_follow_is_following(array('leader_id' => $uid, 'follower_id' => bp_loggedin_user_id()))) {
            $output = bp_get_button(array_merge(
                            array('link_text' => __('Already following', WPBP_FOLLOW_DOMAIN )), $args
            ));
        } else {
            $output = bp_get_button(array_merge(
                            array('link_text' => __('Error following user', WPBP_FOLLOW_DOMAIN )), $args
            ));
        }
    }
    echo $output;
    exit();
}

add_action('wp_ajax_wp_bp_follow', 'wp_bp_follow_ajax_action_start');

/**
 * AJAX callback when clicking on the "Unfollow" button to unfollow a user.
 *
 * @uses check_admin_referer() Checks to make sure the WP security nonce matches.
 * @uses wp_bp_follow_stop_following() Stops a user following another user.
 * @uses wp_bp_follow_is_following() Checks to see if a user is following another user already.
 */
function wp_bp_follow_ajax_action_stop() {

    check_admin_referer('stop_following');
    $uid = sanitize_text_field( $_POST['uid'] );
	$output = '';
    // successful unfollow
    if (wp_bp_follow_stop_following(array('leader_id' => $uid, 'follower_id' => bp_loggedin_user_id()))) {
        // output follow button
        $output = wp_bp_follow_get_add_follow_button(array(
            'leader_id' => $uid,
            'follower_id' => bp_loggedin_user_id(),
            'wrapper' => false
        ));

        // failed unfollow
    } else {
        // output fallback invalid button
        $args = array(
            'id' => 'invalid',
            'link_href' => 'javascript:;',
            'component' => 'follow',
            'wrapper' => false
        );

        if (!wp_bp_follow_is_following(array('leader_id' => $uid, 'follower_id' => bp_loggedin_user_id()))) {
            $output = bp_get_button(array_merge(
                            array('link_text' => __('Not following', WPBP_FOLLOW_DOMAIN )), $args
            ));
        } else {
            $output = bp_get_button(array_merge(
                            array('link_text' => __('Error unfollowing user', WPBP_FOLLOW_DOMAIN )), $args
            ));
        }
    }

    echo $output;

    exit();
}

add_action('wp_ajax_wp_bp_unfollow', 'wp_bp_follow_ajax_action_stop');

function wp_bp_follow_fav_author_start() {
    if (isset($_POST['action']) && $_POST['action'] == 'wp_bp_follow_fav_author_start') {
        $author_id = $_POST['author_id'];
        $current_user_id = $_POST['current_user_id'];
        $fav_author_options = get_option('wbf_favorit_authors');
        if (!empty($fav_author_options)) {
            if (!in_array($author_id, $fav_author_options[$current_user_id]['author_ids'])) {
                $fav_author_options[$current_user_id]['author_ids'][] = $author_id;
                update_option('wbf_favorit_authors', $fav_author_options);
                do_action('wp_bp_favourite_start_favourite', $author_id, bp_loggedin_user_id());
            }
        } else {
            $fav_author = array(
                $current_user_id => array(
                    'author_ids' => array($author_id))
            );
            update_option('wbf_favorit_authors', $fav_author);
            do_action('wp_bp_favourite_start_favourite', $author_id, bp_loggedin_user_id());
        }
    }
    exit();
}

add_action('wp_ajax_wp_bp_follow_fav_author_start', 'wp_bp_follow_fav_author_start');

function wp_bp_follow_fav_author_stop() {
    if (isset($_POST['action']) && $_POST['action'] == 'wp_bp_follow_fav_author_stop') {
        $author_id = $_POST['author_id'];
        $current_user_id = $_POST['current_user_id'];
        $fav_author_options = get_option('wbf_favorit_authors');
        if (!empty($fav_author_options)) {
            $count_fav = count($fav_author_options[$current_user_id]['author_ids']);
            foreach ($fav_author_options as $cid => $auth_arr) {
                foreach ($auth_arr['author_ids'] as $index => $auth_values) {
                    if ($author_id == $auth_values) {
                        unset($fav_author_options[$cid]['author_ids'][$index]);
                        update_option('wbf_favorit_authors', $fav_author_options);
                    }
                }
            }
        }
    }
    exit();
}

add_action('wp_ajax_wp_bp_follow_fav_author_stop', 'wp_bp_follow_fav_author_stop');
