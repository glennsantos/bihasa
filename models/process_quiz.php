<?php 

@include_once 'functionsModel.php';

$serial_ids = clean_answers(@$_POST['serial_ids']);
if (!$serial_ids) return;
$quiz_id = @$_POST['quiz_id'];
if (!$quiz_id) return;
$user_id = get_user_by_serial(current($serial_ids));
if (!$user_id) return;

//compute points
$quiz = get_quiz_info($quiz_id);
$card_points = get_card_points($user_id, $serial_ids);
$quiz_info = get_points_earned($user_id, $quiz, $serial_ids, $card_points);
$points = $quiz_info["total"];

//update dailues points
$dailies = get_dailies($user_id);
if ($dailies) {
	$has_perfectscore_quest = isset($dailies['perfect_score']);
	$data = update_dailies($user_id, $dailies, $quiz_info);
	$points += $data['points']; //add points from completed quests
	unset($data['points']); //remove so we only have dailies in the array
	$dailies = $data;
	if(@array_filter($dailies)) save_dailies($user_id, $dailies);
}

echo 'success';
$coins = $points;
if (count_quiz($user_id) === 1) $coins += 400; //bonus on first quiz

update_points($user_id, $points);
update_coins($user_id, $coins);
update_team_points($user_id, $points);

//update questions' points and set next dates
if (isset($quiz_info['updated'])) {
	update_cards($user_id, $quiz_info['updated'], $quiz['date']);
}

//add to feed if perfect score
if (($quiz_info["correct"] >= $quiz_info["questions"]) AND $has_perfectscore_quest) {
	$avatar = get_avatar("perfect_score");
	$name = get_palayaw($user_id);
	$message = '<span class="name">'.$name."</span> just got a perfect score!";
	add_to_feed($user_id, $avatar, $message, true);
}
?>