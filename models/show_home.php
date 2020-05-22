<?php 
include_once 'functionsModel.php';
include_once '../views/functionsView.php';

$user = get_this_user();
$reroll = get_reroll($user);
show_main($user, $reroll);

?>