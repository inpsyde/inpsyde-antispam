<?php 
/*
Plugin Name: Inpsyde Antispam
Plugin URI: 
Description: Simple Antispam honeypot solution.
Author: Inpsyde GmbH
License: GPL
Version: 1.0
*/

namespace Inpsyde\Antispam;

add_action( 'wp', '\Inpsyde\Antispam\init' );
function init() {
	if ( ! is_admin() ) {
		wp_enqueue_script( 'jquery' );
	}
}

add_action( 'comment_form', '\Inpsyde\Antispam\enhance_comment_form' );
function enhance_comment_form( $form ) {
	
	// generate/get expected answer
	$answer = "Spamschutz";

	// split answer
	$parts = array();
	$answer_len = strlen( $answer );
	$answer_splitpoint = rand( 1, $answer_len-1 );
	$parts[0] = substr( $answer, 0, $answer_splitpoint );
	$parts[1] = substr( $answer, $answer_splitpoint );

	$advice = sprintf(
	 	__( 'Please type the following phrase to confirm you are a human: "%s%s%s"', 'inps-antispam' ),
	 	$parts[0],
	 	'<span style="display:none">+</span>',
	 	$parts[1]
	);

	?>
	<div class="hide-if-js-enabled">
		<label for="inpsyde_antispam_answer"><?php echo $advice; ?></label>
		<input type="text" name="inpsyde_antispam_answer" id="inpsyde_antispam_answer">
		<input type="hidden" name="expected_answer[0]" id="expected_answer_0" value="<?php echo $parts[0]; ?>">
		<input type="hidden" name="expected_answer[1]" id="expected_answer_1" value="<?php echo $parts[1]; ?>">
		<script type="text/javascript">
		jQuery(function($) {
			var answer = $("#expected_answer_0").val() + $("#expected_answer_1").val();
			$("#inpsyde_antispam_answer").val(answer);
			$(".hide-if-js-enabled").hide();
		});
		</script>
	</div>
	<?php
}

add_action( 'comment_post', '\Inpsyde\Antispam\comment_post' );
function comment_post( $comment_id ) {
	global $comment_content, $comment_type;

	if ( ! isset( $_POST[ 'inpsyde_antispam_answer' ] ) || ! isset( $_POST[ 'expected_answer' ] ) ) {
		delete_comment( $comment_id );
		return;
	}
}

function delete_comment( $comment_id ) {
	global $wpdb;

	$wpdb->query( "
		DELETE FROM
			{$wpdb->comments}
		WHERE
			comment_ID = {$comment_id}
	" );
	recount_comments();
}

function recount_comments() {
	global $wpdb;

	$post_id = (int) $_POST[ 'comment_post_ID' ];

	$wpdb->query( "
		UPDATE
			{$wpdb->posts}
		SET
			comment_count = (SELECT COUNT(*) from {$wpdb->comments} WHERE comment_post_id = {$post_id} AND comment_approved = '1')
		WHERE
			ID = {$post_id}"
	);
}