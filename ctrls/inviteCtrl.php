<?php
	include_once './models/functionsModel.php';
	include_once './views/functionsView.php';
	
	show_header('Create Account (By Invitation Only)');
	$user = get_this_user();
	$error = '';
	if ($user) {
		redirect_to();
	} else if ($_POST) {
		$user = process_invite();
		if (is_numeric($user)) {
			redirect_to('','?invited=success');
		}
	}
	$error = $user;
	
	show_invite_form($error);
?>