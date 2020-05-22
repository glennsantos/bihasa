<?php 

@include_once 'functionsModel.php';
@include_once "../views/functionsView.php";

$user_id = @$_POST['user_id'];
$message = @$_POST['message'];
$message = clean_message($message);
$name = get_palayaw($user_id);
$avatar = '';
$data = array();
$data['first'] = 0;

$data['message'] = $message;
$message = '<span class="name">'.$name.'</span>: '.$message;

if (is_first_message($user_id)) {
	update_coins($user_id, 400); //for first message
	$data['first'] = 1;
}
$sent = add_to_feed($user_id, $avatar, $message, false);
//add avatar just for return html
$data['date'] = niceformat_date(now());

if ($sent) {
	echo json_encode($data);
} else {
	echo "-1";
}
?>