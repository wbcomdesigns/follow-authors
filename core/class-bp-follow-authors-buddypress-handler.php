<?php
/**
 * Includes functions to manage tab on BuddyPress profile section to show follow unfollow.
 *
 * @package BP Follow Authors
 *
 * @author Wbcom Designs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'BP_Follow_Authors_BuddyPress_Handler' ) ) :

	/**
	 * Includes functions to manage follow unfollow operations
	 *
	 * @class BP_Follow_Authors_BuddyPress_Handler
	 */
	class BP_Follow_Authors_BuddyPress_Handler {

		/**
		 * The single instance of the class.
		 *
		 * @var BP_Follow_Authors_BuddyPress_Handler
		 */
		protected static $_instance = null;
		
		/**
		 * Main BP_Follow_Authors_BuddyPress_Handler Instance.
		 *
		 * Ensures only one instance of BP_Follow_Authors_BuddyPress_Handler is loaded or can be loaded.
		 *
		 * @return BP_Follow_Authors_BuddyPress_Handler - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * BP_Follow_Authors_BuddyPress_Handler Constructor.
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
			add_action( 'bp_setup_nav', array( $this, 'add_menu_to_bp_profile' ) );
		}


		public function add_menu_to_bp_profile() {

			$args = array(
				'public'   => true
			);
			$post_types = get_post_types( $args, 'objects' );
			$wbbpfa_allowed_post_types =  get_option( 'wbbpfa_allowed_post_types', array() );
			if( !empty( $wbbpfa_allowed_post_types ) ) {
				$default_subnav_slug = $post_types[$wbbpfa_allowed_post_types[0]]->name;
			}
			else {
				$default_subnav_slug = 'nothing';
			}
			


			global $bp;
			$profile_menu_label = __( 'Followed Authors', 'bp-follow-authors' );
			$profile_menu_slug  = 'followed-authors';
			$name     = bp_get_displayed_user_username();
			$tab_args = array(
				'name'                    => $profile_menu_label,
				'slug'                    => $profile_menu_slug,
				'screen_function'         => array( $this, 'bp_parent_lms_notes_show_screen' ),
				'position'                => 75,
				'default_subnav_slug'     => $default_subnav_slug,
				'show_for_displayed_user' => true,
			);
			bp_core_new_nav_item( $tab_args );
			$parent_slug = $profile_menu_slug;


			foreach ( $wbbpfa_allowed_post_types as $key => $post_type ) {
				$post_type_info = $post_types[$post_type];
				$slug = $post_type_info->name;
				bp_core_new_subnav_item(
					array(
						'name'            => $post_type_info->label,
						'slug'            => $slug,
						'parent_url'      => $bp->loggedin_user->domain . $parent_slug . '/',
						'parent_slug'     => $parent_slug,
						'screen_function' => array( $this, 'lcn_bp_notes_show_screen' ),
						'position'        => 200,
						'link'            => site_url() . "/members/$name/$parent_slug/$slug/",
					)
				);
			}

		}   



   		/**
	    * Screen function to add My Notes menu item at buddypress profile page.
	    */
		public function lcn_bp_notes_show_screen() {
			// add_action( 'bp_template_title', array( $this, 'lcn_notes_tab_function_to_show_title' ) );
			add_action( 'bp_template_content', array( $this, 'lcn_notes_tab_function_to_show_content' ) );
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
		}

		/**
	    * Function to show title for course notes tab at buddypress profile page.
	    */
		public function lcn_notes_tab_function_to_show_title() {
			echo __( 'Notes List ', 'bp-follow-authors' );
		}

		/**
	    * Function to show content for course notes tab at buddypress profile page.
	    */
		public function lcn_notes_tab_function_to_show_content( $abs ) {

			$post_type = bp_current_action();
			
			$args = array(
				'public'   => true
			);
			$post_types = get_post_types( $args, 'objects' );
			// print_r($post_types);

			$wb_bp_followed_authors = wb_bp_get_following_authors();
			// print_r($wb_bp_followed_authors);
			
			$args = array(
				'posts_per_page'	=> 100,
				'post_type'	=> $post_type,
				'post_status'	=> 'publish',
				'author__in'	=> $wb_bp_followed_authors,
				'suppress_filters'	=> 0
			);
			$args = apply_filters( 'wb_bp_follow_author_post_args', $args );

			$posts = new WP_Query( $args );

			// var_dump(count( $posts ) );
			echo '<div class="wbbpfa-posts-listing">';
			if( $posts->have_posts() ) :
				while ( $posts->have_posts() ) :
					$posts->the_post();
					?>
					<div class="wbbpfa-post-item">
						<div class="wbbpfa-thumbnail">
							<a href="<?php the_permalink(); ?>">
								<?php
								if( has_post_thumbnail() ) {
									the_post_thumbnail();
								}
								else {
									$default_img_url = BP_Follow_Authors_PLUGIN_DIR_URL . 'assets/placeholder-image.jpg';
									?>
									<img class="wbbpfa-default-thumbnail attachment-post-thumbnail size-post-thumbnail wp-post-image" src="<?php echo $default_img_url; ?>" />
									<?php
								}
								?>
							</a>
						</div>
						<div class="wbbpfa-post-details">
							<div class="wbbpfa-author">
								<a class="wbbpfa-author-avatar" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
									<?php echo get_avatar( get_the_author_meta( 'user_email' ), 50 ); ?>
								</a>
								<a class="wbbpfa-author-name" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
									<?php echo get_the_author(); ?>
								</a>
							</div>
							<div class="wbbpfa-button">
								<a class="post-title" href="<?php the_permalink(); ?>">
									<?php the_title(); ?>
								</a>
							</div>
						</div>
					</div>
					<?php
				endwhile;
			else:
				?>
				<div class="wbbpfa-no-posts">
					<p>
						<?php esc_html_e( 'No data found. Perhaps searching can help.', 'reign' ); ?>
					</p>
					<?php get_search_form(); ?>
				</div>
				<?php
			endif;	
			echo '</div>';

			wp_reset_postdata();
		}

	}

endif;

/**
 * Main instance of BP_Follow_Authors_BuddyPress_Handler.
 *
 * @return BP_Follow_Authors_BuddyPress_Handler
 */
BP_Follow_Authors_BuddyPress_Handler::instance();