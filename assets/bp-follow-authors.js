
jQuery( document ).ready( function() {
	jQuery( document.body ).on( 'click', '.wbcom-bp-follow-author-btn button', function() {
		
		var thisRef = jQuery( this );
		var functionToTrigger = jQuery( this ).siblings( '.bp_follow_authors_functionToTrigger' ).val();
		var authorID = jQuery( this ).siblings( '.bp_follow_authors_authorID' ).val();
		
		jQuery.ajax( {
			url : bp_follow_authors_js_params.ajax_url,
			type : 'post',
			data : {
				action : 'bp_follow_authors_handler',
				functionToTrigger : functionToTrigger,
				authorID : authorID,
			},
			success : function( response ) {
				alert("DONE");

				if( functionToTrigger == 'follow' ) {
					jQuery( thisRef ).siblings( '.bp_follow_authors_functionToTrigger' ).val( 'unfollow' );
					jQuery( thisRef ).text( bp_follow_authors_js_params.unfollow_text );
				}
				else {
					jQuery( thisRef ).siblings( '.bp_follow_authors_functionToTrigger' ).val( 'follow' );
					jQuery( thisRef ).text( bp_follow_authors_js_params.follow_text );
				}
				
				// jQuery('#ced_cwsm_send_loading').hide();
				// if(response == "success") {
				// 	jQuery("div#ced_cwsm_mail_success").show().delay(2000).fadeOut(function(){
				// 		jQuery('#ced_cwsm_suggestion_title').val('');
				// 		jQuery('#ced_cwsm_suggestion_detail').val('');
				// 	});
				// }
				// else {
				// 	jQuery("div#ced_cwsm_mail_failure").show().delay(2000).fadeOut(function(){
				// 	});
				// }
			}
		});	

	} );
} );