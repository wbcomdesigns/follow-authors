<?php get_header(); ?>

<div class="wrap">
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <?php
            $current_user_id = bp_loggedin_user_id();
            $has_fav = get_option('wbf_favorit_authors');
            $following = wp_bp_follow_get_following(array('user_id' => $current_user_id));
            if (!empty($following)) {
                foreach ($following as $key => $uid) {
                    $author_obj = get_user_by('id', $uid);
                    $aname = $author_obj->first_name . ' ' . $author_obj->last_name;
                    if (empty($author_obj->first_name)) {
                        $aname = $author_obj->display_name;
                    }
                    if(empty($aname)){
                        $aname = $author_obj->nickname;
                    }
                    echo '<div class="wbf-row"><div class="wbf-following-post-author-title wbf-col-9"><h3>';
					_e('Posts By: ', WPBP_FOLLOW_DOMAIN );
					echo '<a href="' . bp_core_get_user_domain($uid) . '"><i>' . $aname . '</i></a></h3></div>';
                    if (!empty($has_fav[$current_user_id]['author_ids']) && in_array($uid, $has_fav[$current_user_id]['author_ids'])) {
                        echo '<div class="wbf-following-post-author-favorits-stop wbf-col-3"><a href="javascript:void(0)" id="wbf-following-post-author-favorits-stop-' . $uid . '" class="wbf-following-post-author-favorits" data-current_user_id="' . $current_user_id . '" data-author_id="' . $uid . '">';
						_e('Remove Favourite', WPBP_FOLLOW_DOMAIN );
						echo '</a><i class="fa fa-refresh fa-spin fa-following-spinner fa-spinner fa-fw"></i><span class="sr-only">Loading...</span></div>';
                    } else {
                        echo '<div class="wbf-following-post-author-favorits-start wbf-col-3"><a href="javascript:void(0)" id="wbf-following-post-author-favorits-start-' . $uid . '" class="wbf-following-post-author-favorits" data-current_user_id="' . $current_user_id . '" data-author_id="' . $uid . '">';
						_e( 'Add Favourite', WPBP_FOLLOW_DOMAIN );
						echo '</a><i class="fa fa-refresh fa-spin fa-following-spinner fa-spinner fa-fw"></i><spn class="sr-only">Loading...</span></div>';
                    }
                    echo '</div><div id="wbi-content">';
                    $query = new WP_Query(array('author' => $uid));
                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            ?>
                            <div class="wbf-article-content wbf-row">
                                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                                    <div class="wbf-author-post">
                                        <a href="<?php the_permalink(); ?>">
                                            <div class="wbf-col-3 following-img">
                                                <?php
                                                if (has_post_thumbnail()) {
                                                    the_post_thumbnail('thumbnail');
                                                } else {
                                                    ?>
                                                    <img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>admin/image/default-post-thumbnail.png" alt="<?php the_title(); ?>"/>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            <div class="wbf-col-9">
                                                <h2 class="wbf-ellipsis"><?php echo strlen(get_the_title()) > 40 ? substr(get_the_title(), 0, 40) . "..." : get_the_title(); ?></h2>
                                                <div class="wbf-authorpost-content">
                                                    <?php
                                                        the_excerpt();
                                                    //echo strlen(get_the_excerpt()) > 60 ? substr(wordwrap(get_the_excerpt(), 60, "<br />\n"), 0, 100) . '...' : get_the_excerpt();
                                                    ?>
                                                </div>
                                                <div class="wbi-row">
                                                    <div class="wbf-authorpost">
                                                        <p> <i class="fa fa-pencil following-page-icon" aria-hidden="true"></i>
                                                            <?php _e('by ', WPBP_FOLLOW_DOMAIN );
														    echo $author_obj->first_name . ' ' . $author_obj->last_name; ?></p>
                                                    </div>
                                                    <div class="wbf-authorpost">
                                                        <p><i class="fa fa-calendar following-page-icon" aria-hidden="true"></i><?php echo get_the_date('d-m-Y', get_the_ID()); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </article>
                            </div>
                            <div style="clear: both;"></div>
                            <?php
                        }
                    } else {
                        echo '<div>';
						_e('There is no posts from this user.', WPBP_FOLLOW_DOMAIN );
						echo '</div>';
                    }
                    wp_reset_postdata();
                    echo '</div>';
                }
            } else {
				_e('You have not following any user.', WPBP_FOLLOW_DOMAIN );
            }
            ?>
        </main><!-- #main -->
    </div><!-- #primary -->
    <?php get_sidebar(); ?>
</div><!-- .wrap -->
<?php
get_footer();

