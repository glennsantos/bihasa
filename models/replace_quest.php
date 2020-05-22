<?php 

@include_once "functionsModel.php";
@include_once "./views/functionsView.php";

$quest = @$_POST["quest"];
$user_id = @$_POST["user_id"];

if (!@get_reroll($user_id)) return;
update_reroll($user_id);

//remove the old quest
$dailies = get_dailies($user_id);
unset($dailies[$quest]);

//save for later
$old_quests = $dailies;

//topup and save the new quest lineup
$dailies = topup_daily($dailies);
save_dailies($user_id, $dailies);

//to get only the new quests, we get the old quests out of the new dailies
foreach($old_quests as $quest => $val) {
	unset($dailies[$quest]);
}
$new_quest = array_keys($dailies);

//get quest details
$all_quests = get_all_quests();
$return = $all_quests[$new_quest[0]]; //should only be one new quest there
$return["name"] = $new_quest[0];

echo json_encode($return);
?>