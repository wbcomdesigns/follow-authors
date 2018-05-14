<?php
/**
 * Includes functions to manage follow unfollow operations
 *
 * @package BP Follow Authors
 *
 * @author Wbcom Designs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'BP_Follow_Authors_Frontend_Handler' ) ) :

	/**
	 * Includes functions to manage follow unfollow operations
	 *
	 * @class BP_Follow_Authors_Frontend_Handler
	 */
	class BP_Follow_Authors_Frontend_Handler {


		/**
		 * The single instance of the class.
		 *
		 * @var BP_Follow_Authors_Frontend_Handler
		 */
		protected static $_instance = null;
		
		/**
		 * Main BP_Follow_Authors_Frontend_Handler Instance.
		 *
		 * Ensures only one instance of BP_Follow_Authors_Frontend_Handler is loaded or can be loaded.
		 *
		 * @return BP_Follow_Authors_Frontend_Handler - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * BP_Follow_Authors_Frontend_Handler Constructor.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {
			$this->init_hooks();
		}

		/**
		 * Hook into actions and filters.
		 */
		private function init_hooks() {

			/**
			* Action to manage follow unfollow operations.
			*/
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_follow_authors_scripts' ) );

			add_filter( 'the_content', array( $this, 'add_follow_authors_button' ), 80, 1 );

			add_shortcode( 'bp_follow_authors', array( $this, 'render_bp_follow_authors_button' ) );
		}

		/**
		* Action to manage follow unfollow operations.
		*/
		public function render_bp_follow_authors_button() {
			ob_start();
			$authorID = get_the_author_meta( 'ID' ) ;
            $userID = get_current_user_id();
            if( class_exists( 'BP_Follow_Component' ) ) {
                if ( bp_follow_is_following( array( 'leader_id' => $authorID, 'follower_id' => bp_loggedin_user_id() ) ) ) {
                    $functionToTrigger = 'unfollow';
                    $follow_unfollow_text = __( 'Unfollow', 'bp-follow-authors' );
                }
                else {
                    $functionToTrigger = 'follow';
                    $follow_unfollow_text = __( 'Follow', 'bp-follow-authors' );
                }
            }
            else {

            }
            ?>
            <div class="wbcom-bp-follow-author-btn">
            	<input type="hidden" class="bp_follow_authors_authorID" value="<?php echo $authorID; ?>" />
            	<input type="hidden" class="bp_follow_authors_functionToTrigger" value="<?php echo $functionToTrigger; ?>" />
            	<button><?php echo $follow_unfollow_text; ?></button>
            </div>

            <?php
			$html = ob_get_clean();
			return $html;
		}

		/**
		* Action to manage follow unfollow operations.
		*/
		public function add_follow_authors_button( $content ) {
            if( !is_user_logged_in() ) {
                do_action( 'wbcom_bp_follow_authors_logged_out_content' );
                return;
            }

            $authorID = get_the_author_meta( 'ID' ) ;
            $userID = get_current_user_id();
            if( $userID === $authorID ) {
                do_action( 'wbcom_bp_follow_authors_same_users_content' );
                return;
            }

            $custom_html = do_shortcode( '[bp_follow_authors]' );
            $content .= $custom_html;
            
            return $content;
        }

        /**
		* Action to manage follow unfollow operations.
		*/
        public function enqueue_follow_authors_scripts() {
            global $post;
            if ( TRUE || is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'ignify_pap_crm' ) ) ) {
                wp_register_script(
                    $handle  = 'bp_follow_authors_js',
                    $src     = BP_Follow_Authors_PLUGIN_DIR_URL . 'assets/bp-follow-authors.js',
                    $deps    = array( 'jquery' ),
                    $ver     = time(),
                    $in_footer = true
                );
                wp_localize_script(
                    'bp_follow_authors_js',
                    'bp_follow_authors_js_params',
                    array(
                        'ajax_url'   => admin_url( 'admin-ajax.php' ),
                        'home_url'   => get_home_url(),
                        'follow_text' => __( 'Follow', 'bp-follow-authors' ),
                        'unfollow_text' => __( 'Unfollow', 'bp-follow-authors' )
                    )
                );
                wp_enqueue_script( 'bp_follow_authors_js' );
            }
        }

	}

endif;

/**
 * Main instance of BP_Follow_Authors_Frontend_Handler.
 *
 * @return BP_Follow_Authors_Frontend_Handler
 */
BP_Follow_Authors_Frontend_Handler::instance();