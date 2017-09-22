<?php

/**
 * WP BP Follow Core
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
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * Core class for WP BP Follow.
 *
 * Extends the {@link BP_Component} class.
 *
 * @package Wp_Bp_Follow
 * @subpackage Classes
 *
 * @since 1.0.0
 */
class Wp_Bp_Follow_Core extends BP_Component {

    public function __construct() {
        global $bp;

        // setup misc parameters
        $this->params = array(
            'adminbar_myaccount_order' => apply_filters('wp_bp_follow_following_nav_position', 61)
        );

        // let's start the show!
        parent::start(
                'follow', __('Follow', WPBP_FOLLOW_DOMAIN ), constant('WP_BP_FOLLOW_DIR') . '/includes', $this->params
        );
        $this->includes();
        // register our component as an active component in BP
        $bp->active_components[$this->id] = '1';
    }

    public function includes($includes = array()) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-bp-follow-common.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-bp-follow-actions.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-bp-follow-screens.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-bp-follow-hooks.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-bp-follow-template-tags.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-bp-follow-functions.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-bp-follow-notifications.php';
    }

    /**
     * Setup globals.
     *
     * @global obj $bp BuddyPress instance
     */
    public function setup_globals($args = array()) {
        global $bp;

        if (!defined('WP_BP_FOLLOWERS_SLUG'))
            define('WP_BP_FOLLOWERS_SLUG', 'followers');

        if (!defined('WP_BP_FOLLOWING_SLUG'))
            define('WP_BP_FOLLOWING_SLUG', 'following');

        // Set up the $globals array
        $globals = array(
            'notification_callback' => 'wp_bp_follow_format_notifications',
            'global_tables' => array(
                'table_name' => $bp->table_prefix . 'wp_bp_follow',
            )
        );

        // Let BP_Component::setup_globals() do its work.
        parent::setup_globals($globals);

        // register other globals since BP isn't really flexible enough to add it
        // in the setup_globals() method
        //
		// would rather do away with this, but keeping it for backpat
        $bp->follow->followers = new stdClass;
        $bp->follow->following = new stdClass;
        $bp->follow->followers->slug = constant('WP_BP_FOLLOWERS_SLUG');
        $bp->follow->following->slug = constant('WP_BP_FOLLOWING_SLUG');

        // locally cache total count values for logged-in user
        if (is_user_logged_in()) {
            $bp->loggedin_user->total_follow_counts = wp_bp_follow_total_follow_counts(array(
                'user_id' => bp_loggedin_user_id()
            ));
        }

        // locally cache total count values for displayed user
        if (bp_is_user() && ( bp_loggedin_user_id() != bp_displayed_user_id() )) {
            $bp->displayed_user->total_follow_counts = wp_bp_follow_total_follow_counts(array(
                'user_id' => bp_displayed_user_id()
            ));
        }
    }

    /**
     * Setup profile / BuddyBar navigation
     */
    public function setup_nav($main_nav = array(), $sub_nav = array()) {
        global $bp;

        // Need to change the user ID, so if we're not on a member page, $counts variable is still calculated
        $user_id = bp_is_user() ? bp_displayed_user_id() : bp_loggedin_user_id();
        $counts = wp_bp_follow_total_follow_counts(array('user_id' => $user_id));

        // BuddyBar compatibility
        $domain = bp_displayed_user_domain() ? bp_displayed_user_domain() : bp_loggedin_user_domain();

        /** FOLLOWING NAV  *********************************************** */
        bp_core_new_nav_item(array(
            'name' => sprintf(__('Following <span>%d</span>', WPBP_FOLLOW_DOMAIN ), $counts['following']),
            'slug' => $bp->follow->following->slug,
            'position' => $this->params['adminbar_myaccount_order'],
            'screen_function' => 'wp_bp_follow_screen_following',
            'default_subnav_slug' => 'following',
            'item_css_id' => 'members-following'
        ));

        /** FOLLOWERS NAV*********************************************** */
        bp_core_new_nav_item(array(
            'name' => sprintf(__('Followers <span>%d</span>', WPBP_FOLLOW_DOMAIN ), $counts['followers']),
            'slug' => $bp->follow->followers->slug,
            'position' => apply_filters('bp_follow_followers_nav_position', 62),
            'screen_function' => 'wp_bp_follow_screen_followers',
            'default_subnav_slug' => 'followers',
            'item_css_id' => 'members-followers'
        ));

        /** ACTIVITY SUBNAV ********************************************* */
        // Add activity sub nav item
        if (bp_is_active('activity') && apply_filters('wp_bp_follow_show_activity_subnav', true)) {

            bp_core_new_subnav_item(array(
                'name' => _x('Following', 'Activity subnav tab', WPBP_FOLLOW_DOMAIN ),
                'slug' => constant('WP_BP_FOLLOWING_SLUG'),
                'parent_url' => trailingslashit($domain . bp_get_activity_slug()),
                'parent_slug' => bp_get_activity_slug(),
                'screen_function' => 'wp_bp_follow_screen_activity_following',
                'position' => 21,
                'item_css_id' => 'activity-following'
            ));
        }

        // BuddyBar compatibility
        add_action('bp_adminbar_menus', array($this, 'group_buddybar_items'), 3);

        do_action('wp_bp_follow_setup_nav');
    }

    /**
     * Groups follow nav items together in the BuddyBar.
     *
     * For BP Follow, we use separate nav items for the "Following" and
     * "Followers" pages, but for the BuddyBar, we want to group them together.
     *
     * Because of the way BuddyPress renders both the BuddyBar and profile nav
     * with the same code, to alter just the BuddyBar, you need to resort to
     * hacking the $bp global later on.
     *
     * This will probably break in future versions of BP, when that happens we'll
     * remove this entirely.
     *
     * If the WP Toolbar is in use, this method is skipped.
     *
     * @global object $bp BuddyPress global settings
     * @uses bp_follow_total_follow_counts() Get the following/followers counts for a user.
     */
    public function group_buddybar_items() {
        // don't do this if we're using the WP Admin Bar / Toolbar
        if (defined('BP_USE_WP_ADMIN_BAR') && BP_USE_WP_ADMIN_BAR)
            return;

        if (!bp_loggedin_user_id())
            return;

        global $bp;

        // get follow nav positions
        $following_position = $this->params['adminbar_myaccount_order'];
        $followers_position = apply_filters('wp_bp_follow_followers_nav_position', 62);

        // clobberin' time!
        unset($bp->bp_nav[$following_position]);
        unset($bp->bp_nav[$followers_position]);
        unset($bp->bp_options_nav['following']);
        unset($bp->bp_options_nav['followers']);

        // Add the "Follow" nav menu
        $bp->bp_nav[$following_position] = array(
            'name' => _x('Follow', 'Adminbar main nav', WPBP_FOLLOW_DOMAIN ),
            'link' => trailingslashit(bp_loggedin_user_domain() . $bp->follow->following->slug),
            'slug' => 'follow',
            'css_id' => 'follow',
            'position' => $following_position,
            'show_for_displayed_user' => 1,
            'screen_function' => 'wp_bp_follow_screen_followers'
        );

        // "Following" subnav item
        $bp->bp_options_nav['follow'][10] = array(
            'name' => _x('Following', 'Adminbar follow subnav', WPBP_FOLLOW_DOMAIN ),
            'link' => trailingslashit(bp_loggedin_user_domain() . $bp->follow->following->slug),
            'slug' => $bp->follow->following->slug,
            'css_id' => 'following',
            'position' => 10,
            'user_has_access' => 1,
            'screen_function' => 'wp_bp_follow_screen_following'
        );

        // "Followers" subnav item
        $bp->bp_options_nav['follow'][20] = array(
            'name' => _x('Followers', 'Adminbar follow subnav', WPBP_FOLLOW_DOMAIN ),
            'link' => trailingslashit(bp_loggedin_user_domain() . $bp->follow->followers->slug),
            'slug' => $bp->follow->followers->slug,
            'css_id' => 'followers',
            'position' => 20,
            'user_has_access' => 1,
            'screen_function' => 'wp_bp_follow_screen_followers'
        );

        // Resort the nav items to account for the late change made above
        ksort($bp->bp_nav);
    }

    /**
     * Set up WP Toolbar / Admin Bar.
     *
     * @global obj $bp BuddyPress instance
     */
    public function setup_admin_bar($wp_admin_nav = array()) {

        // Menus for logged in user
        if (is_user_logged_in()) {
            global $bp;

            // "Follow" parent nav menu
            $wp_admin_nav[] = array(
                'parent' => $bp->my_account_menu_id,
                'id' => 'my-account-' . $this->id,
                'title' => _x('Follow', 'Adminbar main nav', WPBP_FOLLOW_DOMAIN ),
                'href' => trailingslashit(bp_loggedin_user_domain() . $bp->follow->following->slug)
            );

            // "Following" subnav item
            $wp_admin_nav[] = array(
                'parent' => 'my-account-' . $this->id,
                'id' => 'my-account-' . $this->id . '-following',
                'title' => _x('Following', 'Adminbar follow subnav', WPBP_FOLLOW_DOMAIN ),
                'href' => trailingslashit(bp_loggedin_user_domain() . $bp->follow->following->slug)
            );

            // "Followers" subnav item
            $wp_admin_nav[] = array(
                'parent' => 'my-account-' . $this->id,
                'id' => 'my-account-' . $this->id . '-followers',
                'title' => _x('Followers', 'Adminbar follow subnav', WPBP_FOLLOW_DOMAIN ),
                'href' => trailingslashit(bp_loggedin_user_domain() . $bp->follow->followers->slug)
            );

            // "Activity > Following" subnav item
            if (bp_is_active('activity') && apply_filters('wp_bp_follow_show_activity_subnav', true)) {
                $wp_admin_nav[] = array(
                    'parent' => 'my-account-activity',
                    'id' => 'my-account-activity-following',
                    'title' => _x('Following', 'Adminbar activity subnav', WPBP_FOLLOW_DOMAIN ),
                    'href' => trailingslashit(bp_loggedin_user_domain() . bp_get_activity_slug() . '/' . $bp->follow->following->slug)
                );
            }
        }

        parent::setup_admin_bar(apply_filters('wp_bp_follow_toolbar', $wp_admin_nav));
    }

}

/**
 * Loads the Follow component into the $bp global
 *
 * @package BP-Follow
 * @global obj $bp BuddyPress instance
 * @since 1.2
 */
function wp_bp_follow_setup_component() {
    global $bp;
    $bp->follow = new Wp_Bp_Follow_Core;
}

add_action('bp_loaded', 'wp_bp_follow_setup_component');
