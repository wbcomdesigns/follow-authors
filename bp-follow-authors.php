<?php
/**
 * Plugin Name: BP Follow Authors
 * Plugin URI: https://wbcomdesigns.com/downloads/peepso-lifterlms-integration/
 * Description: BP Follow Authors
 * Version: 1.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * Requires at least: 4.0
 * Tested up to: 4.9.5
 *
 * Text Domain: bp-follow-authors
 * Domain Path: /languages/
 *
 * @package BP Follow Authors
 * @category Core
 * @author Wbcom Designs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'BP_Follow_Authors' ) ) :

    /**
     * Main BP_Follow_Authors Class.
     *
     * @class BP_Follow_Authors
     *
     * @version 1.0.0
     */
    class BP_Follow_Authors {


        /**
         * BP_Follow_Authors version.
         *
         * @var string
         */
        public $version = '1.0.0';

        /**
         * The single instance of the class.
         *
         * @var BP_Follow_Authors
         * @since 1.0.0
         */
        protected static $_instance = null;

        /**
         * Main BP_Follow_Authors Instance.
         *
         * Ensures only one instance of BP_Follow_Authors is loaded or can be loaded.
         *
         * @since 1.0.0
         * @static
         * @see INSTANTIATE_BP_Follow_Authors()
         * @return BP_Follow_Authors - Main instance.
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }


        /**
         * BP_Follow_Authors Constructor.
         *
         * @since 1.0.0
         */
        public function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
            do_action( 'bp_follow_authors_loaded' );
        }

        /**
         * Hook into actions and filters.
         *
         * @since  1.0.0
         */
        private function init_hooks() {
            add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
            add_filter( 'plugin_action_links_' . BP_Follow_Authors_PLUGIN_BASENAME, array( $this, 'alter_plugin_action_links' ) );
        }

        /**
         * Add plugin settings link.
         *
         * @param string $plugin_links Plugin related links in all plugins listing page.
         *
         * @since  1.0.0
         */
        public function alter_plugin_action_links( $plugin_links ) {
            $settings_link = '<a href="admin.php?page=peepso_config&tab=peepso-lifterlms-addon">Settings</a>';
            array_unshift( $plugin_links, $settings_link );
            return $plugin_links;
        }

        /**
         * Define BP_Follow_Authors Constants.
         *
         * @since  1.0.0
         */
        private function define_constants() {
            $this->define( 'BP_Follow_Authors_PLUGIN_FILE', __FILE__ );
            $this->define( 'BP_Follow_Authors_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
            $this->define( 'BP_Follow_Authors_VERSION', $this->version );
            $this->define( 'BP_Follow_Authors_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
            $this->define( 'BP_Follow_Authors_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
        }

        /**
         * Define constant if not already set.
         *
         * @param  string      $name Define constant name.
         * @param  string|bool $value Define constant value.
         * @since  1.0.0
         */
        private function define( $name, $value ) {
            if ( ! defined( $name ) ) {
                define( $name, $value );
            }
        }

        /**
         * Include required core files used in admin and on the frontend.
         *
         * @since  1.0.0
         */
        public function includes() {
            include_once 'global/bp-follow-authors-functions.php'; 
            include_once 'core/class-bp-follow-authors-frontend-handler.php';
            include_once 'core/class-bp-follow-authors-ajax-handler.php';
        }

        /**
         * Load Localization files.
         *
         * @since  1.0.0
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'bp_follow_authors_plugin_locale', get_locale(), 'bp-follow-authors' );
            load_textdomain( 'bp-follow-authors', BP_Follow_Authors_PLUGIN_DIR_PATH . 'language/peepso-lifterlms-' . $locale . '.mo' );
            load_plugin_textdomain( 'bp-follow-authors', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        }
    }

endif;

/**
 * Main instance of BP_Follow_Authors.
 *
 * Returns the main instance of BP_Follow_Authors to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return BP_Follow_Authors
 */
function instantiate_bp_follow_authors() {
    return BP_Follow_Authors::instance();
}

// Global for backwards compatibility.
$GLOBALS['bp_follow_authors'] = instantiate_bp_follow_authors();