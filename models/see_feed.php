<?php 

@include_once 'functionsModel.php';

//$user_id = @$_POST['user_id'];
$user_id = get_this_user();
update_feedseenlast($user_id);
?>