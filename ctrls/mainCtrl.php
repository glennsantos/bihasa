<?php 
	include_once './models/functionsModel.php';
	include_once './views/functionsView.php';	
	$user = get_this_user();
	if (!$user) redirect_to('login');
	show_header('Quiz Me!');
	
	if (!get_team($user)) {
		if (@$_POST['team']) assign_team($user);
		$teams = get_open_teams($user);
		show_open_teams($user, $teams);
		die();
	}
	
	show_main($user);
	show_main_menu();
	save_browser_info($user);
?>
