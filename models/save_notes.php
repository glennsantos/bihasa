<?php 

@include_once 'functionsModel.php';

$serial_id = @$_POST['serial_id'];
$notes = @$_POST['notes'];
$notes = clean_message($notes);

$saved = save_notes($serial_id, $notes);

if ($saved) {
	echo "success";
	$to = 'glenn@199jobs.com';
	$from = "admin@quizbeta.ml";
	$subject = 'QuizMe Notes, serial_id: '.$serial_id;
	$headers = 'From: '.$from."\r\n".
		'Reply-To: '.$from."\r\n".
		'X-Mailer: PHP/'.phpversion();

	$sent = mail($to, $subject, $notes, $headers);
} else {
	echo "failed";
}
?>