<?php

/**
 * WP BP Follow common Functions
 *
 * @link       http://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/public
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

/**
 * Get the total followers and total following counts for a user.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to grab follow counts for.
 * }
 * @return array [ followers => int, following => int ]
 */
function wp_bp_follow_total_follow_counts($args = '') {

    $r = wp_parse_args($args, array(
        'user_id' => bp_loggedin_user_id()
    ));

    $count = false;

    /* try to get locally-cached values first */

    // logged-in user
    if ($r['user_id'] == bp_loggedin_user_id() && is_user_logged_in()) {
        global $bp;

        if (!empty($bp->loggedin_user->total_follow_counts)) {
            $count = $bp->loggedin_user->total_follow_counts;
        }

        // displayed user
    } elseif ($r['user_id'] == bp_displayed_user_id() && bp_is_user()) {
        global $bp;

        if (!empty($bp->displayed_user->total_follow_counts)) {
            $count = $bp->displayed_user->total_follow_counts;
        }
    }

    // no cached value, so query for it
    if ($count === false) {
        $count = Wp_Bp_Follow_Common::get_counts($r['user_id']);
    }

    return apply_filters('wp_bp_follow_total_follow_counts', $count, $r['user_id']);
}

/**
 * Start following a user's activity.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to follow.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function wp_bp_follow_start_following($args = '') {
    global $bp;

    $r = wp_parse_args($args, array(
        'leader_id' => bp_displayed_user_id(),
        'follower_id' => bp_loggedin_user_id()
    ));

    $follow = new Wp_Bp_Follow_Common($r['leader_id'], $r['follower_id']);

    // existing follow already exists
    if (!empty($follow->id)) {
        return false;
    }

    if (!$follow->save()) {
        return false;
    }

    do_action_ref_array('wp_bp_follow_start_following', array(&$follow));

    return true;
}

/**
 * Check if a user is already following another user.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to check.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function wp_bp_follow_is_following($args = '') {

    $r = wp_parse_args($args, array(
        'leader_id' => bp_displayed_user_id(),
        'follower_id' => bp_loggedin_user_id()
    ));

    $follow = new Wp_Bp_Follow_Common($r['leader_id'], $r['follower_id']);

    return apply_filters('wp_bp_follow_is_following', (int) $follow->id, $follow);
}

/**
 * Stop following a user's activity.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to stop following.
 *     @type int $follower_id The user ID initiating the unfollow request.
 * }
 * @return bool
 */
function wp_bp_follow_stop_following($args = '') {

    $r = wp_parse_args($args, array(
        'leader_id' => bp_displayed_user_id(),
        'follower_id' => bp_loggedin_user_id()
    ));

    $follow = new Wp_Bp_Follow_Common($r['leader_id'], $r['follower_id']);

    if (empty($follow->id) || !$follow->delete()) {
        return false;
    }

    do_action_ref_array('wp_bp_follow_stop_following', array(&$follow));

    return true;
}

/**
 * Fetch the user IDs of all the users a particular user is following.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to fetch following user IDs for.
 * }
 * @return array
 */
function wp_bp_follow_get_following($args = '') {

    $r = wp_parse_args($args, array(
        'user_id' => bp_displayed_user_id()
    ));

    return apply_filters('wp_bp_follow_get_following', Wp_Bp_Follow_Common::get_following($r['user_id']));
}

/**
 * Fetch the user IDs of all the followers of a particular user.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to get followers for.
 * }
 * @return array
 */
function wp_bp_follow_get_followers($args = '') {

    $r = wp_parse_args($args, array(
        'user_id' => bp_displayed_user_id()
    ));

    return apply_filters('wp_bp_follow_get_followers', Wp_Bp_Follow_Common::get_followers($r['user_id']));
}
