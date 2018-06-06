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

if ( ! class_exists( 'BP_Follow_Authors_Shortcode' ) ) :

	/**
	 * Includes functions to manage follow unfollow operations
	 *
	 * @class BP_Follow_Authors_Shortcode
	 */
	class BP_Follow_Authors_Shortcode {

		/**
		 * The single instance of the class.
		 *
		 * @var BP_Follow_Authors_Shortcode
		 */
		protected static $_instance = null;
		
		/**
		 * Main BP_Follow_Authors_Shortcode Instance.
		 *
		 * Ensures only one instance of BP_Follow_Authors_Shortcode is loaded or can be loaded.
		 *
		 * @return BP_Follow_Authors_Shortcode - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * BP_Follow_Authors_Shortcode Constructor.
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
			add_shortcode( 'bp_followed_author', array( $this, 'render_bp_followed_author_html' ) );
		}


		public function render_bp_followed_author_html( $attr = array() ) {
			$unique_id = time();
			$unique_id = 'wbbpfa-'.$unique_id;

			$per_row = isset( $attr['per_row'] ) ? intval( $attr['per_row'] ) : 4;
			$width = floatval( 100 / $per_row );
			$width = isset( $attr['width'] ) ? $attr['width'] : $width.'%';
			
			$args = array(
				'public'   => true
			);
			$post_types = get_post_types( $args, 'objects' );

			$wbbpfa_allowed_post_types =  get_option( 'wbbpfa_allowed_post_types', array() );
			if( !empty( $wbbpfa_allowed_post_types ) ) {
				$default_post_type = $post_types[$wbbpfa_allowed_post_types[0]]->name;
			}
			else {
				$default_post_type = '';
			}
			
			$wb_bp_followed_authors = wb_bp_get_following_authors();
			$active_post_type = isset( $_GET['type'] ) ? $_GET['type'] : $default_post_type;
			
			$args = array(
				'posts_per_page'	=> 100,
				'post_type'	=> $active_post_type,
				'post_status'	=> 'publish',
				'author__in'	=> $wb_bp_followed_authors,
				'suppress_filters'	=> 0
			);
			$args = apply_filters( 'wb_bp_follow_author_post_args', $args );

			$posts = new WP_Query( $args );

			ob_start();
			?>
			<style type="text/css">
				#<?php echo $unique_id; ?> .wbbpfa-post-item {
					width: <?php echo $width; ?>;
				}
			</style>
			<?php
			echo '<div class="wbbpfa-posts-listing" id="' . $unique_id . '">';
				echo '<ul class="wbbpfa-post-type_listing">';
				foreach ( $wbbpfa_allowed_post_types as $key => $post_type ) {
					$post_type_info = $post_types[$post_type];
					$slug = $post_type_info->name;
					$activeClass = ( $active_post_type == $slug ) ? 'active-tab' : '';
					echo '<li class="' . $activeClass . '"><a href="?type=' . $slug . '">' . $post_type_info->label . '</a></li>';
				}
				echo '</ul>';
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

			$html = ob_get_clean();
			return $html;
		}
   		
	}

endif;

/**
 * Main instance of BP_Follow_Authors_Shortcode.
 *
 * @return BP_Follow_Authors_Shortcode
 */
BP_Follow_Authors_Shortcode::instance();