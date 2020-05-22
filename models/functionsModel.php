<?php


//GENERAL FUNCTIONS
function connect_db() { 
	//setup DB
	$mysqli = new mysqli("localhost", "junxi", "brown77.pews", "quizbeta");
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
	}
	return $mysqli;
}

function now() {
	date_default_timezone_set('Asia/Manila');
	$now = time();
	return $now;
}

function schedule_nextdate($days,$date = 0) {
	if (!$date) $date = now();
	$date = $date + ($days*3600*24);
	return $date;
}

function clean_message($message) {
	$message = trim(htmlspecialchars(strip_tags($message)));
	return $message;
}

function send_sms ($cel, $message) {
	if ($cel[0] === '0') {
		$cel = ltrim($cel,"0");
		$cel = "+63".$cel;
	}
	$fields = array();
	$fields["api"] = "nqDvxS8zHmtr1ibDq2tx"; //199jobs API key
	$fields["number"] = $cel; //safe use 63
	$fields["message"] = $message;
	$fields["from"] = "QuizBeta.ml";
	$fields_string = http_build_query($fields);
	$outbound_endpoint = "http://api.semaphore.co/api/sms";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $outbound_endpoint);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function save_browser_info($user_id) {
	$mysqli = connect_db();
	$browser = @get_browser(null, true);
	if (!$browser) {
		$browser = $_SERVER['HTTP_USER_AGENT'];
	} else {
		$browser = mysqli_real_escape_string($mysqli, json_encode($browser));
	}
	$sql = "UPDATE users SET browser_info='".$browser."' WHERE user_id=".$user_id;
	if ($mysqli->query($sql) !== TRUE) {
		echo "Error: " . $sql . "<br>" . $mysqli->error;
	} 
	$mysqli->close(); 
}

function stripNonAlpha($string) {
    return preg_replace("/[^a-z]/i", "", $string);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) 
        && preg_match('/@.+\./', $email);
}


/**USER FUNCTIONS**/

function get_this_user() {
	if (!isset($_COOKIE['quizbeta'])) {
		return false;
	} else {
		$user_id = $_COOKIE['quizbeta'];
		if (is_numeric($user_id)) {
			return $user_id;
		}
	}
	return false;
}

function get_user_by_cel($cel) {
	//find user_id by cellphone
	$mysqli = connect_db(); 
	$query = "SELECT user_id FROM users WHERE cel=".$cel." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 
	if (isset($row) AND isset($row['user_id'])) {
		return $row['user_id'];
	} 
	return false;
}

function get_user_by_serial($serial_id) {
	//find user_id by cellphone
	$mysqli = connect_db(); 
	$query = "SELECT user_id FROM deck WHERE serial_id=".$serial_id." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 
	if (isset($row) AND isset($row['user_id'])) {
		return $row['user_id'];
	} 
	return false;
}

function get_palayaw($user_id) {
	$mysqli = connect_db(); 
	$query = "SELECT fname FROM users WHERE user_id=".$user_id." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 
	
	return $row['fname'];
}

function process_login() {
	$loggedin = @$_POST['login'];
	if (!$loggedin) return false;
	$cel = @$_POST['cel'];
	//$password = @$_POST['pass'];
	$user = get_user_by_cel($cel);
	if (!$user) return false;
	//if (!check_user_pass($user,$password)) return false;
	add_login_cookie($user);
	return $user;
}

function add_login_cookie($user) {
	//add login cookie to $user's browser
	//possibly a session_id which we match to see who this user is
	setcookie('quizbeta', $user, (now()+2592000));			
}

function process_invite() {
	//process the invite form and add to team and table if all okay
	//NOTE: $user = invited user; $row['user_id'] = inviter;
	$mysqli = connect_db(); 
	
	$invited = @$_POST['invited'];
	if (!$invited) return "Unknown Error";
	$refer = @$_POST['refer'];
	if (!is_numeric($refer)) return "Referrer invalid";
	$cel = @$_POST['cel'];
	if (!$cel OR get_user_by_cel($cel)) return "Cellphone invalid";
	$email = @$_POST['email'];
	if (!isValidEmail($email)) return "Email invalid";
	$name = @$_POST['name'];
	$name = stripNonAlpha($name);
	if (!$name) return "Name invalid";
	$nextrefill = now();
	
	$refer = $refer/(4*4*4);	
	$query = "SELECT user_id, team_id FROM users WHERE user_id=".$refer." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	
	if (!$row['user_id']) return "Referrer invalid";
	
	//check if the inviter has the team invite quest
	$dailies = get_dailies($row['user_id']);
	if(isset($dailies["team_invite"]) AND !$dailies["team_invite"]) {
		@include_once "../views/functionsView.php";
		$all_quests = get_all_quests();
		$coins = $all_quests["team_invite"]["coins"];
		update_coins($row['user_id'], $coins);
		unset($dailies["team_invite"]);
		save_dailies($row['user_id'], $dailies);
		//also include a notification to the inviter. for now, let's settle with an update to the team feed
	}

	//create the user
	$team = $row['team_id'];
	$coins = 400; //since it's a referral
	
	//give first daily quests
	$dailies = topup_daily(); 
	$dailies = encode_dailies($dailies);
	
	$sql = "INSERT INTO users (team_id, fname, email, cel, coins, browser_info, quests, nextrefill) VALUES (".$team.", '".$name."', '".$email."', '".$cel."', ".$coins.", '', '".$dailies."', ".$nextrefill.")";
	if ($mysqli->query($sql) !== TRUE) {
		echo "Error: " . $sql . "<br>" . $mysqli->error;
	}
	
	//log in the user 
	//$password = @$_POST['pass'];
	$user = get_user_by_cel($cel);
	if (!$user) return false;
	//if (!check_user_pass($user,$password)) return false;
	
	add_team_welcome_msg($user);

	//process question giving
	give_questions($user, 'random', 45); //20q for regular reg, 5q for referrals, 20q for joining a team
	
	add_login_cookie($user);
	return $user;
}

/** QUIZ FUNCTIONS **/

function is_first_quiz($user_id) {
	$mysqli = connect_db();
	$query = "SELECT EXISTS (SELECT 1 FROM quiz WHERE user_id=".$user_id.")";
	$result = $mysqli->query($query);
	$has_quiz = mysqli_fetch_array($result);

	if ($has_quiz[0]) return 0;

	$mysqli->close(); 
	
	return 1;
}

function count_quiz($user_id) {
	//counts all quizzes a user has, excluding unanswered quizzes
	$mysqli = connect_db();
	$query = "SELECT DISTINCT COUNT(*), quiz_id FROM quiz WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close();
	
	return $row['COUNT(*)'] + 0;
}

function count_deck($user_id) {
	//counts all cards a user has, excluding duplicates
	$mysqli = connect_db();
	$query = "SELECT DISTINCT COUNT(*), card_id FROM deck WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close();
	
	return $row['COUNT(*)'];
}

function count_all_cards() {
	$mysqli = connect_db();
	$query = "SELECT COUNT(*), card_id FROM cards";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	
	return $row['COUNT(*)'];
}

function get_all_cards() {
	$mysqli = connect_db();
	$query = "SELECT card_id FROM cards";
	$result = $mysqli->query($query);
	while ($row = mysqli_fetch_assoc($result)) {
		$cards[] = $row['card_id'] + 0;
	}
	
	return $cards;
}

function create_quiz($user_id, $quiz_ct = 4) {
	//create a quiz from the user's cards
	$deck = get_deck($user_id, now());
	$cards = array();
	
	if(count($deck) < $quiz_ct) return false;

	//get new cards first. 50% should be new cards
	while (count($cards) < ceil($quiz_ct/2)) {		
		$questions = array_keys($deck);
		do {
			$key = rand(0,count($questions)-1);
			$question = $questions[$key];
		} while ($deck[$question]['points'] > 50);
		$cards[$question] = $deck[$question];
		unset($deck[$question]);
	}
	
	//get all the rest of the cards
	while (count($cards) < $quiz_ct) {
		$questions = array_keys($deck);
		$key = rand(0,count($questions)-1);
		$question = $questions[$key];
		$cards[$question] = $deck[$question];
		unset($deck[$question]);
	}

	$quiz = @cards_to_quiz($user_id, $cards);
	
	return $quiz;
}

function get_deck($user_id, $today = 0) {
	//get all info on $user's deck aka owned cards usable for quizzing
	$cards = array();
	$mysqli = connect_db();
	$query = "SELECT * FROM deck WHERE user_id=".$user_id." AND nextdate <= ".$today;
	$result = $mysqli->query($query);
	while ($row = mysqli_fetch_assoc($result)) {
		$cards[$row['card_id']] = $row;
	}
	$mysqli->close();
	
	return $cards;
}

function get_my_card_ids($user_id) {
	//get ALL unique card_ids on $user's deck aka owned cards
	$cards = array();
	$mysqli = connect_db();
	$query = "SELECT card_id FROM deck WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	while ($row = mysqli_fetch_assoc($result)) {
		$cards[$row['card_id']] = $row['card_id'] + 0;
	}
	$mysqli->close();
	
	return $cards;
}

function clean_answers($answers) {
	//clean string
	return json_decode(strip_tags(trim($answers)),true);
}

function shuffle_options($row) {
	//takes the entire card row as input so I don't have to make a new array
	//shuffles the options and returns the new order as well as the correct answer
	
	//determine the order of the answers
	$r = range(1,4);
	$i = 1;
	shuffle($r);
	$order = implode("",$r);
	//assign the options to the new array
	foreach($r as $n) {
		$options[$i++] = $row['option'.$n];
	}
	$opt['order'] = $order;
	$opt['options'] = $options;

	return $opt;
}

function cards_to_quiz($user_id, $cards) {
	//converts a $pack (which is just a reference to card_id's) into actual $cards
	//only works on single packs
	if (!is_array($cards)) return;
	$mysqli = connect_db();
	$query = "SELECT card_id, question, option1, option2, option3, option4 FROM cards WHERE card_id IN (".implode(",",array_keys($cards)).")";
	$result = $mysqli->query($query);
	
	while ($row = mysqli_fetch_assoc($result)) {	
		//shuffling options
		$opt = shuffle_options($row);
		$row['options'] = $opt['options'];
		$row['order'] = $opt['order'];
		//add points
		$card_id = $row['card_id'];
		$row['points'] =  $cards[$card_id]['points'];
		$row['nextdate'] =  $cards[$card_id]['nextdate'];
		$row['notes'] =  $cards[$card_id]['notes'];
		$row['serial_id'] =  $cards[$card_id]['serial_id'];
		$quiz[] = $row;
	}
	$mysqli->close(); 
		
	return $quiz;
}

function save_quiz($user_id,$cards) {
	//saves the $cards as a quiz in db
	$mysqli = connect_db(); 
	$sql = "INSERT INTO quiz (user_id, date, question1, options1, question2, options2, question3, options3, question4, options4) VALUES ('".$user_id."', '".now()."', '".$cards[0]['card_id']."', '".$cards[0]['order']."', '".$cards[1]['card_id']."', '".$cards[1]['order']."', '".$cards[2]['card_id']."', '".$cards[2]['order']."', '".$cards[3]['card_id']."', '".$cards[3]['order']."')";
	
	
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: " . $sql . "<br>" . $mysqli->error;
		$quiz_id = 0;
	} else {
		$quiz_id = $mysqli->insert_id;
	}
	
	$mysqli->close(); 
	return $quiz_id;
}

function save_quiz_answers($user_id, $answers) {
	//write answers part of query 
	$i = 0;
	$a_set = '';
	if (!is_array($answers)) return false;
	foreach ($answers as $answer) {
		if ($a_set) $a_set .= ", ";
		$a_set .= "answer".++$i."=".$answer;
	}
	
	//write questions part of query
	$questions = array_keys($answers);
	$i = 0;
	$q_where = '';
	foreach ($questions as $question) {
		$q_where .= " AND question".++$i."=".$question;
	}
	 
	//add answers to quiz table
	$mysqli = connect_db(); 
	$sql = "UPDATE quiz SET ".$a_set." WHERE user_id=".$user_id.$q_where." ORDER BY date DESC LIMIT 1";
	
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: Quiz not saved. " . $sql . "<br>" . $mysqli->error;
		return false;
	} 
		
	//return quiz if recorded
	$query = "SELECT * FROM quiz WHERE user_id=".$user_id.$q_where." ORDER BY date DESC LIMIT 1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 

	return $row;
}

function get_quiz_info($quiz_id) {
	$mysqli = connect_db(); 
	$query = "SELECT * FROM quiz WHERE quiz_id=".$quiz_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 
	
	return $row;
}

function get_card_points($user_id, $serial_ids) {
	//get points the card gives
	$mysqli = connect_db(); 
	$query = "SELECT card_id, points FROM deck WHERE serial_id IN (".implode(",",$serial_ids).")";
	$result = $mysqli->query($query);
	while ($row = mysqli_fetch_assoc($result)) {
		$points[$row['card_id']] = max(50,($row['points']+0));
	}
	return $points;
}

function get_points_earned($user_id, $quiz, $serial_ids, $card_points, $questions = 4) {
	//compute the stats for the quiz.
	$quiz_info = array();	
	$quiz_info["total"] = 0;
	$quiz_info["correct"] = 0;
	$quiz_info["updated"] = array();
	$quiz_info["new"] = 0;
	for($i=1;$i<=$questions;$i++) {
		$o = $quiz['answer'.$i]-1;
		$ans = $quiz['options'.$i][$o];
		$question_id = $quiz['question'.$i];
		$serial_id = $serial_ids[$question_id];
		$points = $card_points[$question_id];
		if($ans === '1') {
			$quiz_info["total"] += $points;
			$quiz_info["updated"][$serial_id] = $points+50;
			$quiz_info["correct"] += 1;
		} else {
			$quiz_info["total"] += 10; //for incorrects
			$quiz_info["updated"][$serial_id] = 50;
		}
		if ($points <= 50) { //new card
			$quiz_info["new"]++;
		}
	}
	//bonus for perfect score
	if ($quiz_info["correct"] >= $questions) {
		$quiz_info["total"] += 100;
	}
	
	$quiz_info["questions"] = $questions;
	
	return $quiz_info;
}

function update_points($user_id, $points) {
	//add points to self. not shown publicly for now
	$mysqli = connect_db(); 
	$sql = "UPDATE users SET points=points+".$points." WHERE user_id=".$user_id;
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: " . $sql . "<br>" . $mysqli->error;
		return false;
	} 
	$mysqli->close();
	
	return true;
}

function update_cards($user_id, $updated, $date) {
	//add points to cards, as well as nextdate
	$mysqli = connect_db(); 
	$msg = '';
	if (!$updated OR !is_array($updated)) return false;
	foreach ($updated as $serial_id => $points) {
		$days = max(0,floor($points/50) - 1);
		$nextdate = schedule_nextdate($days,$date);
		$sql = "UPDATE deck SET points=".$points.", nextdate=".$nextdate." WHERE serial_id=".$serial_id;
		if ($mysqli->query($sql) !== TRUE) {
			$msg .= "Error: " .$serial_id." not updated. ". $sql . "<br>" . $mysqli->error;
			return false;
		}
	}
	$mysqli->close();
	
	return true;
}

/**TEAM FUNCTIONS**/

function get_team($user_id) {
	//get the team_id of the user
	$mysqli = connect_db(); 
	$query = "SELECT team_id FROM users WHERE user_id=".$user_id." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 
	
	return $row['team_id'];
}

function get_team_members($team_id) {
	$mysqli = connect_db();
	$query = "SELECT user_id FROM users WHERE team_id=".$team_id;
	$result = $mysqli->query($query);
	while ($row = mysqli_fetch_array($result)) {
		$members[] = $row['user_id'];
	}
	
	return $members;
}

function get_team_info($id, $is_user = false) {
	//get the team_id of the user
	$mysqli = connect_db(); 
	
	if ($is_user) {
		//get team from user first
		$id = get_team($id);
	}
	$query = "SELECT * FROM teams WHERE team_id=".$id." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 
	
	return  $row;
}

function get_all_teams() {
	//get list of all teams
	$mysqli = connect_db();
	$team = array();
	$query = "SELECT * FROM teams ORDER BY points DESC";
	$result = $mysqli->query($query);
	while ($row = mysqli_fetch_assoc($result)) {
		$teams[$row['team_id']] = $row;
	}
	$mysqli->close(); 
	
	return $teams;
}

function get_team_name($user_id) {
	//what is the user's team name?
	$mysqli = connect_db(); 
	$team_id = get_team($user_id);
	
	$query = "SELECT name FROM teams WHERE team_id=".$team_id." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	
	if (!$row['name']) return "None";
	$mysqli->close(); 
	
	return $row['name'];
}

function get_team_points($user_id) {
	$mysqli = connect_db(); 
	
	$query = "SELECT team_id FROM users WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$team_id = $row['team_id'];
	
	$query = "SELECT points FROM teams WHERE team_id=".$team_id." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$mysqli->close(); 
	
	return $row['points'];
}

function get_open_teams($user_id){
	$all_teams = get_all_teams();
	shuffle($all_teams);
	foreach ($all_teams as $team) {
		$team_id = $team['team_id'];
		$mysqli = connect_db(); 
		$query = "SELECT COUNT(*), user_id FROM users WHERE team_id=".$team_id." LIMIT 0,1";
		$result = $mysqli->query($query);
		$row = mysqli_fetch_assoc($result);
		if(!$row['COUNT(*)']) $teams[$team_id] = $team;
	}	
	
	return $teams;
}

function update_team_points($user_id, $points) {
	//add points to team
	$mysqli = connect_db(); 
	$team_id = get_team($user_id);
	$sql = "UPDATE teams SET points=points+".$points." WHERE team_id=".$team_id;
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: " . $sql . "<br>" . $mysqli->error;
		return false;
	} 
	$mysqli->close();
	
	return true;
}

function assign_team($user_id) {
	$team_id = @$_POST['team'];
	if (is_numeric($team_id) AND @$_POST['submit']) {
		$mysqli = connect_db();
		$sql = "UPDATE users SET team_id=".$team_id." WHERE user_id=".$user_id;
		if ($mysqli->query($sql) !== TRUE) {
			$msg = "Error: Team not joined" . $sql . "<br>" . $mysqli->error;
			return false;
		}
		$mysqli->close(); 
	}
	add_team_welcome_msg($user_id);
	redirect_to('');
}

function add_team_welcome_msg($user_id) {
	$avatar = get_avatar('new_teammate');
	$message = 'Welcome to the team, <span class="name">'.get_palayaw($user_id).'</span>!';
	add_to_feed($user_id, $avatar, $message, true);
}

//FEED FUNCTIONS

function get_team_feed($user_id, $team_id) {
	//get the team's feed. limit to 20 entries
	$feed = array();
	$mysqli = connect_db(); 
	$query = "SELECT * FROM feed WHERE team_id=".$team_id." ORDER BY date DESC LIMIT 0,20";
	if ($team_id === '100') {
		//admin feed
		$query = "SELECT * FROM feed ORDER BY date DESC LIMIT 0, 50";
	}
	$result = $mysqli->query($query);
	while ($row = mysqli_fetch_assoc($result)) {
		$feed[$row['feed_id']] = $row;
	}
	$mysqli->close(); 
	
	return $feed;
}

function get_avatar($type) {
	//get the avatar for the feed. normally this should be the user's avatar if it's a message
	switch ($type) {
		case 'quest_complete':
			$img = 'images/ribbon.png';
			//thenounproject.com/term/ribbon/18586/
		break;
		
		case 'new_teammate':
			$img = 'images/hello.png';
			//thenounproject.com/term/happy/43890/
		break;
		
		case 'perfect_score':
			$img = 'images/medal.png';
			//thenounproject.com/term/medal/13720/
		break;
		
		case 'first':
			$img = 'images/first.png';
			//thenounproject.com/term/trophy/41058/
		break;
		
		case 'second':
			$img = 'images/second.png';
			//thenounproject.com/term/trophy/41058/
		break;
		
		case 'third':
			$img = 'images/third.png';
			//thenounproject.com/term/trophy/41058/
		break;
	}
	
	return $img;	
}

function is_first_message($user_id) {
	$mysqli = connect_db();
	$query = "SELECT EXISTS (SELECT 1 FROM feed WHERE sender=".$user_id.")";
	$result = $mysqli->query($query);
	$has_message = mysqli_fetch_array($result);

	if ($has_message[0]) return 0;

	$mysqli->close(); 
	
	return 1;
}

function get_feedseenlast($user_id) {
	$mysqli = connect_db();
	$query = "SELECT feedseenlast FROM users WHERE user_id=".$user_id." LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_array($result);
	$mysqli->close(); 
	
	return $row['feedseenlast'];
}

function update_feedseenlast($user_id) {
	$now = now();
	$mysqli = connect_db();
	$sql = "UPDATE users SET feedseenlast=".$now." WHERE user_id=".$user_id;
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: Feedseenlast not updated. " . $sql . "<br>" . $mysqli->error;
		return false;
	}
	$mysqli->close(); 
	
	return true;
}


function add_to_feed($user_id, $avatar, $message, $robot = false) {
	//update the db streak in user table
	$mysqli = connect_db(); 
	$date = now();
	$team_id = get_team($user_id);
	if ($robot) {
		$user_id = 0;
	}
	$message = mysqli_real_escape_string($mysqli, $message);
	
	$sql = "INSERT INTO feed (team_id, date, sender, avatar, message) VALUES ('".$team_id."', '".$date."', '".$user_id."', '".$avatar."', '".$message."')";
	
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: Entry not added. " . $sql . "<br>" . $mysqli->error;
		return false;
	} 
	$mysqli->close(); 

	return true;
}

//LEADERBOARD
function check_leaderboard(){
	$mysqli = connect_db();
	$query = "SELECT nextreset FROM teams LIMIT 0,1";
	$result = $mysqli->query($query);
	$row = mysqli_fetch_array($result);
	
	$new_reset = strtotime("next Monday", now());
	
	if ($row['nextreset'] < $new_reset) {
		$all_teams = get_all_teams(); //arranged in descending order
		$ranks = array('third','second','first');
		$rewards = array(500,1000,2000);
		$lastscore = 1;
		
		foreach ($all_teams as $team_id => $team) {
			if ($lastscore !== $all_teams[$team_id]['points']) {
				//for ties, give all teams same reward
				if (empty($ranks)) break;
				$rank = array_pop($ranks);
				$reward = array_pop($rewards);
			}
			$all_teams[$team_id][$rank] += 1;
			$members = @get_team_members($team_id);
			if ($members) {
				foreach ($members as $user_id) {
					update_coins($user_id, $reward);
				}
				$message = 'Congrats team, you got <strong>'.$rank.' place</strong> last week! Enjoy your <span class="give_reward">+'.$reward.'¢</span> prize!';
				$avatar = get_avatar($rank);
				add_to_feed($user_id, $avatar, $message, true);
			}
			$lastscore = $all_teams[$team_id]['points'];
		}		
		
		//save results
		foreach ($all_teams as $team_id => $team) {
			$first = $all_teams[$team_id]['first'];
			$second = $all_teams[$team_id]['second'];
			$third = $all_teams[$team_id]['third'];
			$sql = "UPDATE teams SET points=0, nextreset=".$new_reset.", first=".$first.", second=".$second.", third=".$third." WHERE team_id=".$team_id;
			if ($mysqli->query($sql) !== TRUE) {
				$msg = "Error: ".$all_teams[$team_id]['name']." not reset. " . $sql . "<br>" . $mysqli->error;
				return false;
			}
		}
	}
}



/**SHOP FUNCTIONS**/

function is_first_buy($user_id){
	//is this the user's first time to buy from the shop?
	$mysqli = connect_db();
	$query = "SELECT EXISTS(SELECT 1 FROM purchases WHERE user_id=".$user_id.")";
	$result = $mysqli->query($query);
	$has_purchase = mysqli_fetch_array($result);
	$mysqli->close(); 
	
	return !$has_purchase[0];
}

function get_shop_items() {
	//what do we have for sale?
	$items = array("Qx4" => array("desc" => "4 Questions",
													"type" => "random",
													"questions" => 4, 
													"price" => "250"),
						"Qx10" => array("desc" => "10 Questions",
													"type" => "random",
													"questions" => 10, 
													"price" => "500"),
						"Qx20" => array("desc" => "20 Questions",
												"type" => "random",
												 "questions" => 20,
												 "price" => "700"),
						"NQx10" => array("desc" => "10 NEW Questions",
												"type" => "new",
												"questions" => 10,
												"price" => "850"),
						"NQx20" => array("desc" => "20 NEW Questions",
												"type"=>"new",
												"questions" => 20,
												"price" => "1500"),
						"NQx50" => array("desc" => "50 NEW Questions",
												"type" => "new",
												"questions" => 50,
												"price" => "3200")
							);
	return $items;
}

function get_coins($user_id) {
	//get user's coins
	$mysqli = connect_db();
	$query = "SELECT coins FROM users WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$coins = $row['coins'];
	$mysqli->close(); 
	
	return $coins;
}

function give_questions($user_id, $type, $count = 4) {
	//add questions to the user, default is 4
	$mysqli = connect_db();
	$question_ids = array();
	
	//get own $deck and all available cards ($all_q)
	$deck = get_my_card_ids($user_id);
	$all_qs = get_all_cards();
	
	while (count($question_ids) < $count) {
		$key = rand(0,count($all_qs)-1); //random key from all cards
		$this_id = $all_qs[$key]; //random question_id
		if ($type === "new") {
			unset($all_qs[$this_id]); //remove so we don't pick it again 
			$all_qs = array_values($all_qs); //reindex since we get keys
			if (isset($deck[$this_id])) continue;  //found in $deck? try again
			$question_ids[$this_id] = $this_id; //ensure unique result
		} else {
			//if ($type === "random")
			$question_ids[] = $this_id; //don't really care if unique
		}
	}
	
	foreach ($question_ids as $question_id) {
		$add_qs[] = "(".$user_id.", ".$question_id.", '')"; 
	}

	$sql = "INSERT INTO deck (user_id, card_id, notes)
				VALUES ".implode(", ",$add_qs);
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error:  Cards not added. " . $sql . "<br>" . $mysqli->error;
	} 
	
	$mysqli->close(); 
	
	return true;
}



function save_purchase($user_id, $item, $price) {
	//add current purchase to table
	$mysqli = connect_db();
	$date = now();
	$sql = "INSERT INTO purchases (date, user_id, item, price) VALUES (".$date.", ".$user_id.", '".$item."', '".$price."')";
	if ($mysqli->query($sql) !== TRUE) {
		echo "Error: " . $sql . "<br>" . $mysqli->error;
		return false;
	}
	$mysqli->close(); 
	
	return true;
}


function update_coins($user_id, $amount) {
	//add or subtract coins (if amount is negative)
	$mysqli = connect_db();
	$sql = "UPDATE users SET coins=coins+".$amount." WHERE user_id=".$user_id;
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: Coins not deducted" . $sql . "<br>" . $mysqli->error;
		return false;
	}
	$mysqli->close(); 
	
	return true;
}

//QUEST FUNCTIONS

function encode_dailies($dailies) {
	//json encode daily quests for saving to table	
	return json_encode($dailies);
}

function get_dailies($user_id) {
	//get all the user's daily quests
	//array format is $quest['quest_name'] = 'current_count'
	$mysqli = connect_db();
	$query = "SELECT quests FROM users WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_array($result);
	$dailies = @json_decode($row['quests'],true);
	if (!$dailies) return array();
	$mysqli->close(); 
	
	return $dailies;
}

function get_my_quests($user_id) {
	//get all the user's daily quests, including done ones
	//but it also saves them, removing the done quests
	$myquests = get_dailies($user_id);
	if (!$myquests OR !is_array($myquests)) return array();
	$all_quests = get_all_quests();
	foreach ($myquests as $quest => $val) {
		$data['value'] = $val;
		$data['desc'] = $all_quests[$quest]["desc"];
		$data['coins'] = $all_quests[$quest]["coins"];
		$data['goal'] = $all_quests[$quest]["goal"];
		if ($val === 'done') {
			$quests['done'][$quest] = $data;
		} else {
			$quests['inprogress'][$quest] = $data;
			$dailies[$quest] = $val;
		}
	}
	//update dailies sans done quests
	save_dailies($user_id, $dailies);
	
	return $quests;
}

function get_measure_value($measure, $quiz_info, $dailies, $invite = 0) {
	//what values does this quiz contribute to the user's dailies?
	$streak = (isset($dailies["3_streak"]) AND ($dailies["3_streak"] > 0)) ? $dailies["3_streak"] : 0;
	switch ($measure) {
		case 'perfect':
			 if ($quiz_info["correct"] >= $quiz_info["questions"])
			 	return 1;
			 else
			 	return 0;
		break;
		
		case 'quiz':
			return 1;
		break;
		
		case 'points':
			return $quiz_info["total"];
		break;
		
		case 'questions':
			return $quiz_info["questions"];
		break;
		
		case 'new':
			return $quiz_info["new"];
		break;
		
		case 'corrects':
			return $quiz_info["correct"];
		break;
		
		case 'invite':
			return $invite;
		break;
		
		case 'streak':
			if ($quiz_info["correct"] >= $quiz_info["questions"])
				return $streak + 1;
			else 
				return 0;
		break;
	}
	return 0;
}

function get_refill($user_id) {
	//get the last time the dailies were topped up
	$mysqli = connect_db();
	$query = "SELECT nextrefill FROM users WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_array($result);
	$mysqli->close(); 
	
	return $row['nextrefill'];
}

function get_reroll($user_id) {
	//find out if user can reroll for new daily
	$mysqli = connect_db();
	$query = "SELECT reroll FROM users WHERE user_id=".$user_id;
	$result = $mysqli->query($query);
	$row = mysqli_fetch_array($result);
	$mysqli->close(); 
	
	return $row['reroll'];
}

function topup_daily($dailies = array()) {
	//add a daily quest to the user, either when replacing or on next day
	@include_once "../views/functionsView.php";
	$max_dailies = 2;
	if (count($dailies) > $max_dailies) return array_slice($dailies,0,2);
	$all_quests = get_all_quests();
	while (count($dailies) < $max_dailies) {
		do {
			$key = array_rand($all_quests);
			unset($all_quests[$key]);
		} while (isset($dailies[$key]));
		$dailies[$key] = 0;
	}
	
	return $dailies;
}


function save_dailies($user_id, $dailies) {
	//save daily quest to table
	$dailies = encode_dailies($dailies);
	$mysqli = connect_db();
	$sql = "UPDATE users SET quests='".$dailies."' WHERE user_id=".$user_id;
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: Quests not saved. " . $sql . "<br>" . $mysqli->error;
		return false;
	}
	$mysqli->close(); 
	
	return true;
}

function update_dailies($user_id, $dailies, $quiz_info) {
	//update stats on dailies and return if there are any completed
	@include_once "../views/functionsView.php";
	$all_quests = get_all_quests();
	$data = array();
	$points = 0; 
	foreach ($dailies as $quest => $val) {
		if (is_numeric($val)) {
			$measure = $all_quests[$quest]["measure"];
			$goal = $all_quests[$quest]["goal"];
			$new_val = $val + get_measure_value($measure, $quiz_info, $dailies);
			if ($new_val >= $goal) {
				$new_val = 'done';
				$coins = $all_quests[$quest]["coins"];
				$desc = $all_quests[$quest]["desc"];
				$points += $coins;
				
				//add to feed
				$avatar = get_avatar('quest_complete');
				$name =  get_palayaw($user_id);
				$message = '<span class="name">'.$name.'</span> completed a quest: <span class="quest_feed">'.$desc.'</span>';
				add_to_feed($user_id, $avatar, $message, true);
			}
		} else {
			$new_val = 'done';
		}
		$data[$quest] = $new_val;
	}
	$data['points'] = $points;
	
	return $data;
}

function update_reroll($user_id, $count = 0) {
	//add or deduct rerolls for new dailies
	$mysqli = connect_db();
	if ($count) $count = 1;
	$sql = "UPDATE users SET reroll=".$count." WHERE user_id=".$user_id;
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: Reroll not updated. " . $sql . "<br>" . $mysqli->error;
		return false;
	}
	$mysqli->close(); 
	
	return true;
}

function update_refill($user_id) {
	$nextrefill = get_refill($user_id);
	$now = now();
	if ($nextrefill < $now) {
		
		//give new reroll
		update_reroll($user_id, 1);
		
		//top up daily quests
		$dailies = get_dailies($user_id);
		$dailies = topup_daily($dailies);
		save_dailies($user_id, $dailies);
		
		//update refill time
		$nextrefill = strtotime("tomorrow", now());
		$mysqli = connect_db();
		$sql = "UPDATE users SET nextrefill=".$nextrefill." WHERE user_id=".$user_id;
		if ($mysqli->query($sql) !== TRUE) {
			$msg = "Error: Refill not updated. " . $sql . "<br>" . $mysqli->error;
			return false;
		}
		$mysqli->close();
		
		return true;
	}

	return false;
} 

/**NOTES FUNCTIONS**/
function save_notes($serial_id, $notes) {
	$mysqli = connect_db();
	$sql = "UPDATE deck SET notes='".$notes."' WHERE serial_id=".$serial_id;
	if ($mysqli->query($sql) !== TRUE) {
		$msg = "Error: Notes not saved. " . $sql . "<br>" . $mysqli->error;
		return false;
	}
	$mysqli->close();
	
	return true;
}
?>
