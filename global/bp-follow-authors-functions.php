<?php

function wb_bp_get_following_authors() {
	$wb_bp_followed_authors = array();
	if( class_exists( 'BP_Follow_Component' ) ) {
		$wb_bp_followed_authors = bp_follow_get_following( array( 'user_id' => bp_displayed_user_id() ) );
	}
	else {
		$wb_bp_followed_authors = get_user_meta( get_current_user_id(), 'wb_bp_followed_authors', true );
		if( is_array( $wb_bp_followed_authors ) ) {
			$wb_bp_followed_authors = array_keys( $wb_bp_followed_authors );
		}
	}
	return $wb_bp_followed_authors;
}


function wb_bp_follow_author_is_following( $authorID, $userID ) {
	$following = false;
	if( class_exists( 'BP_Follow_Component' ) ) {
		if ( bp_follow_is_following( array( 'leader_id' => $authorID, 'follower_id' => $userID ) ) ) {
			$following = true;
		}
		else {
			$following = false;
		}
	}
	else {
		$wb_bp_followed_authors = get_user_meta( $userID, 'wb_bp_followed_authors', true );
		if( !empty( $wb_bp_followed_authors ) && is_array( $wb_bp_followed_authors ) ) {
			if( isset( $wb_bp_followed_authors[$authorID] ) ) {
				$following = true;
			}
			else {
				$following = false;
			}
		}
		else {
			$following = false;
		}
	}
	return apply_filters( 'wb_bp_follow_author_is_following', $following, $authorID, $userID );
}

// function wb_bp_follow_author_start_following( $authorID, $userID ) {
// 	$following = false;
// 	if( class_exists( 'BP_Follow_Component' ) ) {
// 		if ( bp_follow_is_following( array( 'leader_id' => $authorID, 'follower_id' => $userID ) ) ) {
// 			$following = true;
// 		}
// 		else {
// 			$following = false;
// 		}
// 	}
// 	else {
// 		$wb_bp_followed_authors = get_user_meta( $userID, 'wb_bp_followed_authors', true );
// 		if( !empty( $wb_bp_followed_authors ) && is_array( $wb_bp_followed_authors ) ) {
// 			if( isset( $wb_bp_followed_authors[$authorID] ) ) {
// 				$following = true;
// 			}
// 			else {
// 				$following = false;
// 			}
// 		}
// 		else {
// 			$following = false;
// 		}
// 	}
// 	return apply_filters( 'wb_bp_follow_author_is_following', $following, $authorID, $userID );
// }
