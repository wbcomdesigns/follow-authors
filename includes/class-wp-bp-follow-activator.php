<?php

/**
 * Fired during plugin activation
 *
 * @link       http://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Bp_Follow
 * @subpackage Wp_Bp_Follow/includes
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Wp_Bp_Follow_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $bp, $wpdb;

        $charset_collate = !empty($wpdb->charset) ? "DEFAULT CHARACTER SET $wpdb->charset" : '';
        if (!$table_prefix = $bp->table_prefix)
            $table_prefix = apply_filters('bp_core_get_table_prefix', $wpdb->base_prefix);

        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_prefix}wp_bp_follow (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			leader_id bigint(20) NOT NULL,
			follower_id bigint(20) NOT NULL,
		        KEY followers (leader_id, follower_id)
		) {$charset_collate};";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}
