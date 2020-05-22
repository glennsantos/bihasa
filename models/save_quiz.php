<?php

@include_once 'functionsModel.php';

$mysqli = connect_db();
$user_id = @$_POST['user_id'];
if (!$user_id) return;
$answers = clean_answers(@$_POST['answers']);
if (!$answers AND !is_array($answers)) return;
$questions = array_keys($answers);

$quiz = save_quiz_answers($user_id, $answers);
if ($quiz) {
	for($i=1;$i<=4;$i++) {
		$options = $quiz['options'.$i];
		$corrects[$quiz['question'.$i]] = strpos($options,"1") + 1;
	}
	//save data for later
	$data['quiz_id'] = $quiz['quiz_id'];
	$data['corrects'] = $corrects;
	echo json_encode($data);
}
?>