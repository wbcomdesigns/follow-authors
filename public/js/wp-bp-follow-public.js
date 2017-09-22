(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    $(function () {
        var profileHeader = $("#item-buttons");
        var memberLoop = $("#members-list").parent();
        var groupMemberLoop = $("#member-list").parent();

        profileHeader.on("click", ".follow-button a", function () {
            wp_bp_follow_button_action($(this), 'profile');
            return false;
        });

        memberLoop.on("click", ".follow-button a", function () {
            wp_bp_follow_button_action($(this), 'member-loop');
            return false;
        });

        groupMemberLoop.on("click", ".follow-button a", function () {
            wp_bp_follow_button_action($(this));
            return false;
        });

        function wp_bp_follow_button_action(scope, context) {		
            var link = scope;
            var uid = link.attr('id');
            var nonce = link.attr('href');
            var action = '';

            uid = uid.split('-');
            action = uid[0];
            uid = uid[1];

            nonce = nonce.split('?_wpnonce=');
            nonce = nonce[1].split('&');
            nonce = nonce[0];

            $.post(wp_bp_follow_ajax_obj.ajaxurl, {
                action: 'wp_bp_' + action,
                'uid': uid,
                '_wpnonce': nonce
            },
            function (response) {
                $(link.parent()).fadeOut(200, function () {
                    // toggle classes
                    if (action == 'unfollow') {
                        link.parent().removeClass('following').addClass('not-following');
                    } else {
                        link.parent().removeClass('not-following').addClass('following');
                    }

                    // add ajax response
                    link.parent().html(response);

                    // increase / decrease counts
                    var count_wrapper = false;
                    if (context == 'profile') {
                        count_wrapper = $("#user-members-followers span");

                    } else if (context == 'member-loop') {
                        // a user is on their own profile
                        if (!$.trim(profileHeader.text())) {
                            count_wrapper = $("#user-members-following span");
                            // this means we're on the member directory
                        } else {
                            count_wrapper = $("#members-following span");
                        }
                    }

                    if (count_wrapper.length) {
                        if (action == 'unfollow') {
                            count_wrapper.text((count_wrapper.text() >> 0) - 1);
                        } else if (action == 'follow') {
                            count_wrapper.text((count_wrapper.text() >> 0) + 1);
                        }
                    }					
                    $(this).fadeIn(200);
                });
            });
        }
        $("[id^='wbf-following-post-author-favorits-start']").on('click', function () {
            var author_id = $(this).data().author_id;
            var current_user_id = $(this).data().current_user_id;
            var data = {
                action: 'wp_bp_follow_fav_author_start',
                'author_id': author_id,
                'current_user_id': current_user_id
            };
            $.post(wp_bp_follow_ajax_obj.ajaxurl, data, function (response) {
                location.reload();
            });
        });
        $("[id^='wbf-following-post-author-favorits-stop']").on('click', function () {
            var author_id = $(this).data().author_id;
            var current_user_id = $(this).data().current_user_id;
            var data = {
                action: 'wp_bp_follow_fav_author_stop',
                'author_id': author_id,
                'current_user_id': current_user_id
            };
            $.post(wp_bp_follow_ajax_obj.ajaxurl, data, function (response) {
                location.reload();
            });
        });
    });
})(jQuery);
