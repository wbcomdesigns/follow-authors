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

if ( ! class_exists( 'BP_Follow_Authors_Ajax_Handler' ) ) :

	/**
	 * Includes functions to manage follow unfollow operations
	 *
	 * @class BP_Follow_Authors_Ajax_Handler
	 */
	class BP_Follow_Authors_Ajax_Handler {


		/**
		 * The single instance of the class.
		 *
		 * @var BP_Follow_Authors_Ajax_Handler
		 */
		protected static $_instance = null;
		
		/**
		 * Main BP_Follow_Authors_Ajax_Handler Instance.
		 *
		 * Ensures only one instance of BP_Follow_Authors_Ajax_Handler is loaded or can be loaded.
		 *
		 * @return BP_Follow_Authors_Ajax_Handler - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * BP_Follow_Authors_Ajax_Handler Constructor.
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
			add_action( 'wp_ajax_bp_follow_authors_handler', array( $this, 'bp_follow_authors_handler' ) );
		}

		/**
		* Action to manage follow unfollow operations.
		*/
		public function bp_follow_authors_handler() {
            if( isset( $_POST['functionToTrigger'] ) && isset( $_POST['authorID'] ) ) {
                $functionToTrigger = $_POST['functionToTrigger'];
                $authorID = $_POST['authorID'];
                $userID = get_current_user_id();

                $result = '';
                switch ( $functionToTrigger ) {
                    case 'follow':
                        if( class_exists( 'BP_Follow_Component' ) ) {
                            if ( bp_follow_start_following( array( 'leader_id' => $authorID, 'follower_id' => bp_loggedin_user_id() ) ) ) {
                                $result = __( 'You are now following Dr. Stephen Strange.', 'bp-follow-authors' );
                            }
                            else {
                                if ( bp_follow_is_following( array( 'leader_id' => $authorID, 'follower_id' => bp_loggedin_user_id() ) ) ) {
                                    $result = __( 'Already following.', 'bp-follow-authors' );
                                }
                                else {
                                    $result = __( 'Error following user.', 'bp-follow-authors' );
                                }
                            }
                        }
                        else {
							$wb_bp_followed_authors = get_user_meta( $userID, 'wb_bp_followed_authors', true );
							if( !empty( $wb_bp_followed_authors ) && is_array( $wb_bp_followed_authors ) ) {
								if( isset( $wb_bp_followed_authors[$authorID] ) ) {
									$result = __( 'Already following.', 'bp-follow-authors' );
								}
								else {
									$wb_bp_followed_authors[$authorID] = array(
										'authorID'	=> $authorID,
										'time'		=> time()	
									);
									update_user_meta( $userID, 'wb_bp_followed_authors', $wb_bp_followed_authors );
									$result = __( 'You are now following Dr. Stephen Strange.', 'bp-follow-authors' );
								}
							}
							else {
								$wb_bp_followed_authors = array(
									$authorID => array(
										'authorID'	=> $authorID,
										'time'		=> time()	
									)
								);
								update_user_meta( $userID, 'wb_bp_followed_authors', $wb_bp_followed_authors );
								$result = __( 'You are now following Dr. Stephen Strange.', 'bp-follow-authors' );
							}
						}
                        break;

                    case 'unfollow':
                        if( class_exists( 'BP_Follow_Component' ) ) {
                            if ( bp_follow_stop_following( array( 'leader_id' => $authorID, 'follower_id' => bp_loggedin_user_id() ) ) ) {
                                $result = __( 'You are not following Dr. Stephen Strange anymore.', 'bp-follow-authors' );
                            }
                            else {
                                if ( bp_follow_is_following( array( 'leader_id' => $authorID, 'follower_id' => bp_loggedin_user_id() ) ) ) {
                                    $result = __( 'Not following.', 'bp-follow-authors' );
                                }
                                else {
                                    $result = __( 'Error unfollowing user.', 'bp-follow-authors' );
                                }
                            }
                        }
                        else {
							$wb_bp_followed_authors = get_user_meta( $userID, 'wb_bp_followed_authors', true );
							if( !empty( $wb_bp_followed_authors ) && is_array( $wb_bp_followed_authors ) ) {
								if( isset( $wb_bp_followed_authors[$authorID] ) ) {
									unset( $wb_bp_followed_authors[$authorID] );
									update_user_meta( $userID, 'wb_bp_followed_authors', $wb_bp_followed_authors );
									$result = __( 'You are not following Dr. Stephen Strange anymore.', 'bp-follow-authors' );
								}
								else {
									$result = __( 'Error unfollowing user.', 'bp-follow-authors' );
								}
							}
							else {
								$result = __( 'Not following.', 'bp-follow-authors' );
							}
						}
                        break;
                    
                    default:
                        add_action( 'wbcom_bp_follow_authors_ajax_handler' );
                        break;
                }
            } 
            echo $result;          
            wp_die();
        }

	}

endif;

/**
 * Main instance of BP_Follow_Authors_Ajax_Handler.
 *
 * @return BP_Follow_Authors_Ajax_Handler
 */
BP_Follow_Authors_Ajax_Handler::instance();