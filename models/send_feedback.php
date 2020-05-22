<?php 

@include_once 'functionsModel.php';

$user_id = get_this_user();
$mysqli = connect_db(); 
$query = "SELECT * FROM users WHERE user_id=".$user_id." LIMIT 0,1";
$result = $mysqli->query($query);
$row = mysqli_fetch_assoc($result);
$mysqli->close(); 

$to = 'glenn@199jobs.com';
$from = $row['email'];
$subject = 'QuizMe Feedback from '.get_palayaw($user_id); 
$message = @$_POST['message'];
$headers = 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n".
    'X-Mailer: PHP/'.phpversion();

$sent = mail($to, $subject, $message, $headers);

if ($sent) {
	echo "success";
} else {
	echo "failed";
}


?>