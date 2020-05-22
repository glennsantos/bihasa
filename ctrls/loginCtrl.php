<?php
	include_once './models/functionsModel.php';
	include_once './views/functionsView.php';
	
	$user = get_this_user();
	if ($user OR ($user = process_login())) {
		redirect_to();
	}
	//show login screen
	show_header('Log In');
	show_login_form();

?>