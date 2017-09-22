<?php
/**
 * WP BP Follow Hooks
 *
 * @link       http://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/public
 */
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/public
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */

/**
 * Add a "Follow User/Stop Following" button to the profile header for a user.
 *
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_bp_follow_is_following() Check the following status for a user
 * @uses bp_is_my_profile() Return true if you are looking at your own profile when logged in.
 * @uses is_user_logged_in() Return true if you are logged in.
 */
function wp_bp_follow_add_profile_follow_button() {
    if (bp_is_my_profile()) {
        return;
    }

    wp_bp_follow_add_follow_button();
}

add_action('bp_member_header_actions', 'wp_bp_follow_add_profile_follow_button');

/**
 * Add a "Follow User/Stop Following" button to each member shown in the
 * members loop.
 *
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $members_template The members template object containing all fetched members in the loop
 * @uses is_user_logged_in() Return true if you are logged in.
 */
function wp_bp_follow_add_listing_follow_button() {
    global $members_template;

    if ($members_template->member->id == bp_loggedin_user_id())
        return false;

    $current_user_id = $members_template->member->id;
    if (!user_can($current_user_id, 'edit_posts')) {
        return false;
    }
    
    wp_bp_follow_add_follow_button('leader_id=' . $members_template->member->id);
}

add_action('bp_directory_members_actions', 'wp_bp_follow_add_listing_follow_button');

/**
 * Add a "Follow User/Stop Following" button to each member shown in a group
 * members loop.
 *
 * @author Wbcom Designs
 * @since 1.0.0
 *
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $members_template The members template object containing all fetched members in the loop
 */
function wp_bp_follow_add_group_member_follow_button() {
    global $members_template;

    if ($members_template->member->user_id == bp_loggedin_user_id() || !bp_loggedin_user_id())
        return false;

    wp_bp_follow_add_follow_button('leader_id=' . $members_template->member->user_id);
}

add_action('bp_group_members_list_item_action', 'wp_bp_follow_add_group_member_follow_button');

/** DIRECTORIES ********************************************************* */

/**
 * Adds a "Following (X)" tab to the activity directory.
 *
 * This is so the logged-in user can filter the activity stream to only users
 * that the current user is following.
 *
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_bp_follow_total_follow_counts() Get the following/followers counts for a user.
 */
function wp_bp_follow_add_activity_tab() {

    $counts = wp_bp_follow_total_follow_counts(array('user_id' => bp_loggedin_user_id()));

    if (empty($counts['following']))
        return false;
    ?>
    <li id="activity-following"><a href="<?php echo bp_loggedin_user_domain() . WP_BP_ACTIVITY_SLUG . '/' . WP_BP_FOLLOWING_SLUG . '/' ?>" title="<?php _e('The public activity for everyone you are following on this site.', 'wp-bp-follow') ?>"><?php printf(__('Following <span>%d</span>', 'wp-bp-follow'), (int) $counts['following']) ?></a></li><?php
}

add_action('bp_before_activity_type_tab_friends', 'wp_bp_follow_add_activity_tab');

/**
 * Add a "Following (X)" tab to the members directory.
 *
 * This is so the logged-in user can filter the members directory to only
 * users that the current user is following.
 *
 * @uses wp_bp_follow_total_follow_counts() Get the following/followers counts for a user.
 */
function wp_bp_follow_add_following_tab() {

    if (!is_user_logged_in()) {
        return;
    }

    $counts = wp_bp_follow_total_follow_counts(array('user_id' => bp_loggedin_user_id()));

    if (empty($counts['following']))
        return false;
    ?>
    <li id="members-following"><a href="<?php echo bp_loggedin_user_domain() . WP_BP_FOLLOWING_SLUG ?>"><?php printf(__('Following <span>%d</span>', WPBP_FOLLOW_DOMAIN ), $counts['following']) ?></a></li><?php
}

add_action('bp_members_directory_member_types', 'wp_bp_follow_add_following_tab');

/** LOOP INJECTION ****************************************************** */

/**
 * Inject $members_template global with follow status for each member in the
 * members loop.
 *
 * Once the members loop has queried and built a $members_template object,
 * fetch all of the member IDs in the object and bulk fetch the following
 * status for all the members in one query.
 *
 * This is significantly more efficient that querying for every member inside
 * of the loop.
 *
 * @since 1.0
 * @todo Use {@link BP_User_Query} introduced in BP 1.7 in a future version
 *
 * @global $members_template The members template object containing all fetched members in the loop
 * @uses Wp_Bp_Follow_Common::bulk_check_follow_status() Check the following status for more than one member
 * @param $has_members Whether any members where actually returned in the loop
 * @return $has_members Return the original $has_members param as this is a filter function.
 */
function wp_bp_follow_inject_member_follow_status($has_members) {
    global $members_template;

    if (empty($has_members))
        return $has_members;

    $user_ids = array();

    foreach ((array) $members_template->members as $i => $member) {
        if ($member->id != bp_loggedin_user_id())
            $user_ids[] = $member->id;

        $members_template->members[$i]->is_following = false;
    }

    if (empty($user_ids))
        return $has_members;

    $following = Wp_Bp_Follow_Common::bulk_check_follow_status($user_ids);

    if (empty($following))
        return $has_members;

    foreach ((array) $following as $is_following) {
        foreach ((array) $members_template->members as $i => $member) {
            if ($is_following->leader_id == $member->id)
                $members_template->members[$i]->is_following = true;
        }
    }
    return $has_members;
}

add_filter('bp_has_members', 'wp_bp_follow_inject_member_follow_status');

/**
 * Modify the querystring passed to the members loop to return only users
 * that the current user is following.
 *
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_bp_get_following_ids() Get the user_ids of all users a user is following.
 */
function wp_bp_follow_add_member_directory_filter($qs, $object, $filter, $scope) {
    global $bp;

    // Only filter on directory pages (no action) and the following scope on members object.
    if (!empty($bp->current_action) || 'following' != $scope || 'members' != $object)
        return $qs;
    $qs .= '&include=' . wp_bp_get_following_ids(array('user_id' => bp_loggedin_user_id()));
    return apply_filters('wp_bp_follow_add_member_directory_filter', $qs, $filter);
}

add_filter('bp_dtheme_ajax_querystring', 'wp_bp_follow_add_member_directory_filter', 10, 4);
add_filter('bp_legacy_theme_ajax_querystring', 'wp_bp_follow_add_member_directory_filter', 10, 4);

/**
 * Filter the members loop on a user's "Following" or "Followers" page.
 *
 * This is done so we can return the users that:
 *   - the current user is following; or
 *   - the users that are following the current user
 *
 * @author Wbcom Designs
 * @since 1.0.0
 *
 * @param str $qs The querystring for the BP loop
 * @param str $object The current object for the querystring
 * @return str Modified querystring
 */
function wp_bp_follow_add_member_scope_filter($qs, $object) {

    // not on the members object? stop now!
    if ($object != 'members')
        return $qs;

    // not on a user page? stop now!
    if (!bp_is_user())
        return $qs;

    // filter the members loop based on the current page
    switch (bp_current_action()) {
        case 'following':
            $args = array(
                'include' => wp_bp_get_following_ids(),
                'per_page' => apply_filters('wp_bp_follow_per_page', 20)
            );

            // make sure we add a separator if we have an existing querystring
            if (!empty($qs))
                $qs .= '&';

            // add our follow parameters to the end of the querystring
            $qs .= build_query($args);

            return $qs;

            break;

        case 'followers' :
            $args = array(
                'include' => wp_bp_get_follower_ids(),
                'per_page' => apply_filters('wp_bp_follow_per_page', 20)
            );

            // make sure we add a separator if we have an existing querystring
            if (!empty($qs))
                $qs .= '&';

            // add our follow parameters to the end of the querystring
            $qs .= build_query($args);

            return $qs;

            break;

        default :
            return $qs;

            break;
    }
}

add_filter('bp_ajax_querystring', 'wp_bp_follow_add_member_scope_filter', 20, 2);


/** GETTEXT ************************************************************* */

/**
 * Add gettext filter when no activities are found and when using follow scope.
 *
 * @since 1.0.0
 *
 * @author Wbcom Designs
 * @param bool $has_activities Whether the current activity loop has activities.
 * @return bool
 */
function wp_bp_follow_has_activities($has_activities) {
    global $bp;

    if (!empty($bp->follow->activity_scope_set) && !$has_activities) {
        add_filter('gettext', 'wp_bp_follow_no_activity_text', 10, 2);
    }

    return $has_activities;
}

add_filter('bp_has_activities', 'wp_bp_follow_has_activities', 10, 2);

/**
 * Modifies 'no activity found' text to be more specific to follow scope.
 *
 * @since 1.0.0
 *
 * @author Wbcom Designs
 * @see wp_bp_follow_has_activities()
 * @param string $translated_text The translated text.
 * @param string $untranslated_text The unmodified text.
 * @return string
 */
function wp_bp_follow_no_activity_text($translated_text, $untranslated_text) {
    if ($untranslated_text == 'Sorry, there was no activity found. Please try a different filter.') {
        if (!bp_is_user() || bp_is_my_profile()) {
            $follow_counts = wp_bp_follow_total_follow_counts(array(
                'user_id' => bp_loggedin_user_id()
            ));

            if ($follow_counts['following']) {
                return __("You are following some users, but they haven't posted yet.", WPBP_FOLLOW_DOMAIN );
            } else {
                return __("You are not following anyone yet.", WPBP_FOLLOW_DOMAIN );
            }
        } else {
            global $bp;

            if (!empty($bp->displayed_user->total_follow_counts['following'])) {
                return __("This user is following some users, but they haven't posted yet.", WPBP_FOLLOW_DOMAIN );
            } else {
                return __("This user isn't following anyone yet.", WPBP_FOLLOW_DOMAIN );
            }
        }
    }

    return $translated_text;
}

/**
 * Removes custom gettext filter when using follow scope.
 *
 * @since 1.0.0
 *
 * @author Wbcom Designs
 * @see wp_bp_follow_has_activities()
 */
function wp_bp_follow_after_activity_loop() {
    global $bp;

    if (!empty($bp->follow->activity_scope_set)) {
        remove_filter('gettext', 'wp_bp_follow_no_activity_text', 10, 2);
        unset($bp->follow->activity_scope_set);
    }
}

add_action('bp_after_activity_loop', 'wp_bp_follow_after_activity_loop');

//---------------------
add_action('admin_init', 'following_posts_admin_init');

function following_posts_admin_init() {
//    delete_option('following_posts_installed');
    if (!get_option('following_posts_installed')) {
        $new_page_id = wp_insert_post(array(
            'post_title' => 'Following Posts',
            'post_type' => 'page',
            'post_name' => 'Following Posts',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => get_user_by('id', 1)->user_id,
            'menu_order' => 0,
        ));
        if ($new_page_id && !is_wp_error($new_page_id)) {
            update_post_meta($new_page_id, '_wp_page_template', 'class-wp-bp-follow-activity-following-posts.php');
        }
        update_option('following_posts_installed', true);
    }
}
