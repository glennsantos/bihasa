<?php
	/**********************************************
	How quiz works
	1. load quiz cards then review cards (latter should be under quiz) then results
	2. on answer, log it and fetch the right answer.
	3. modify the review cards as the correct answer is received
	4. the js should also trigger the backend processing of the answer (stats, etc), so that it's processed as the answers are made
	5. before showing review, check first if the answers have been logged. if not, resend them
	6. Let's do everything in html this time
	
	Submit the answer and process answer WHILE loading the review. Js submits the answer, form posts it to the next screen
	
	************************************************/
	include_once './views/functionsView.php';
	include_once './models/functionsModel.php';
	
	$user = get_this_user();
	if (!$user) redirect_to('login');	
	
	$quiz = create_quiz($user);
	if (!$quiz) redirect_to('','?show=shop');	
	$firstquiz = is_first_quiz($user);
	show_header('Quiz Me!');
	show_quiz($user, $quiz, $firstquiz);
	save_quiz($user,$quiz);
	show_main_menu("display:none;");
?>