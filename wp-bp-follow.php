<?php
/**
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://wbcomdesigns.com
 * @since             1.0.0
 * @package           Wp_Bp_Follow
 *
 * @wordpress-plugin
 * Plugin Name:       WP BP Follow
 * Plugin URI:        http://wbcomdesigns.com
 * Description:       This plugin will add an extended feature to the big name “BuddyPress” that will allow to follow members on your BuddyPress site. The plugin works similar to the friends component, however the connection does not need to be accepted by the person being followed like twitter.
 * Version:           1.0.0
 * Author:            Wbcom Designs
 * Author URI:        http://wbcomdesigns.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-bp-follow
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('WPBP_FOLLOW_DOMAIN', 'wp-bp-follow' );
/**
 *  Checking for buddypress whether it is active or not
 */
if (!in_array('buddypress/bp-loader.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
    $wpbp_follow_plugin = plugin_basename(__FILE__);
    $wpbp_follow_key = array_search($wpbp_follow_plugin, $active_plugins);
    if (isset($wpbp_follow_key) && in_array($wpbp_follow_plugin, $active_plugins)) {
        unset($active_plugins[$wpbp_follow_key]);
        add_action('admin_notices', 'buddypress_inactive_notice');
        update_option('active_plugins', $active_plugins);
        if (isset($_GET['activate']))
            unset($_GET['activate']);
    }
    return;
}

/**
 * Notice about not activating this plugins
 */
function buddypress_inactive_notice() {
    ?>
    <div class="error notice">
        <p><?php _e('WPBP Follow plugin can not be activated because to work it, BuddyPress plugin must be activated', 'wp-bp-follow'); ?></p>
    </div>
    <?php
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-bp-follow-activator.php
 */
function activate_wp_bp_follow() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-bp-follow-activator.php';
    Wp_Bp_Follow_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-bp-follow-deactivator.php
 */
function deactivate_wp_bp_follow() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-bp-follow-deactivator.php';
    Wp_Bp_Follow_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_bp_follow');
register_deactivation_hook(__FILE__, 'deactivate_wp_bp_follow');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wp-bp-follow.php';

/**
 * Only load the plugin code if BuddyPress is activated.
 */
function wp_bp_follow_init() {

// some pertinent defines
    define('WP_BP_FOLLOW_DIR', dirname(__FILE__));
    define('WP_BP_FOLLOW_URL', plugin_dir_url(__FILE__));	
    require_once WP_BP_FOLLOW_DIR . '/includes/class-wp-bp-follow-core.php';
}

add_action('bp_include', 'wp_bp_follow_init');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_bp_follow() {

    $plugin = new Wp_Bp_Follow();
    $plugin->run();
}

run_wp_bp_follow();
