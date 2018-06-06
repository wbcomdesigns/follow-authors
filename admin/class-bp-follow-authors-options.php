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

if ( ! class_exists( 'BP_Follow_Authors_BuddyPress_Options' ) ) :

	/**
	 * Includes functions to manage follow unfollow operations
	 *
	 * @class BP_Follow_Authors_BuddyPress_Options
	 */
	class BP_Follow_Authors_BuddyPress_Options {


		/**
		 * The single instance of the class.
		 *
		 * @var BP_Follow_Authors_BuddyPress_Options
		 */
		protected static $_instance = null;
		
		/**
		 * Main BP_Follow_Authors_BuddyPress_Options Instance.
		 *
		 * Ensures only one instance of BP_Follow_Authors_BuddyPress_Options is loaded or can be loaded.
		 *
		 * @return BP_Follow_Authors_BuddyPress_Options - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * BP_Follow_Authors_BuddyPress_Options Constructor.
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
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'wp_loaded', array( $this, 'save_options' ) );
		}


		public function admin_menu() {
			add_options_page(
				__( 'BP Follow Authors', 'bp-follow-authors' ),
				__( 'BP Follow Authors', 'bp-follow-authors' ),
				'manage_options',
				'bp-follow-authors',
				array(
					$this,
					'settings_page'
				)
			);
		}

		public function  settings_page() {
			$args = array(
				'public'   => true
			);
			$post_types = get_post_types( $args, 'objects' );
			
			$wbbpfa_allowed_post_types =  get_option( 'wbbpfa_allowed_post_types', array() );
			
			if( isset( $_POST['bp-follow-authors-submit'] ) ) {
				?>
				<div class="updated notice is-dismissible">
					<p>
						<?php _e( 'Options updated successfully.', 'bp-follow-authors' ); ?>
					</p>
				</div>
				<?php
			}
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php _e( 'BP Follow Authors - Settings', 'bp-follow-authors' ); ?></h1>
				<form method="POST">
					<?php
					foreach ( $post_types as $post_type ) {
						$checked = '';
						if( in_array( $post_type->name, $wbbpfa_allowed_post_types ) ) {
							$checked = 'checked="checked"';
						}
						?>
						<p>
							<input type="checkbox" name="wbbpfa_allowed_post_types[]" id="<?php echo $post_type->name; ?>" value="<?php echo $post_type->name; ?>" <?php echo $checked; ?> />
							<label for="<?php echo $post_type->name; ?>">
								<?php echo $post_type->label; ?>
							</label>
						</p>
						<?php
					}
					?>
					<p class="submit">
						<input name="bp-follow-authors-submit" id="submit" class="button button-primary" value="<?php _e( 'Update Options', 'bp-follow-authors' ); ?>" type="submit">
					</p>
				</form>
			</div>	
			<?php
		}


		public function save_options() {
			if( isset( $_POST['bp-follow-authors-submit'] ) ) {
				if( isset( $_POST['wbbpfa_allowed_post_types'] ) ) {
					update_option( 'wbbpfa_allowed_post_types', $_POST['wbbpfa_allowed_post_types'] );
				}
			}
		}

	}

endif;

/**
 * Main instance of BP_Follow_Authors_BuddyPress_Options.
 *
 * @return BP_Follow_Authors_BuddyPress_Options
 */
BP_Follow_Authors_BuddyPress_Options::instance();