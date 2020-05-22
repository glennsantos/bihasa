<?php

//HELPER FUNCTIONS
function redirect_to($page = '',$query = '', $duration = 0){ 
	//redirects stuff to another page
	//current pages: 'home', 'quiz', 'login'
	if($page) $page = $page.'.php';
	header('refresh:'.$duration.';url=./'.$page.$query, true, 303);
	die(); //don't forget to add this!
}

function niceformat_date($date, $words = true) {
	if (!is_numeric($date)) return $date;
	date_default_timezone_set('Asia/Manila');
		
	$date_str = date('M j, g:ia', $date);
	
	if (!$words) return $date_str;
	
	if (($date > strtotime('today')) AND ($date < strtotime('+2 days'))) {
		$date_str = 'Tomorrow, '.date('g:ia', $date);
	}
	
	if (($date < strtotime('tomorrow')) AND ($date > strtotime('today'))){
		$date_str = 'Today, '.date('g:ia', $date);
	}
	
	if (($date < strtotime('today')) AND ($date > strtotime('yesterday'))){
		$date_str = 'Yesterday, '.date('g:ia', $date);
	}
	
	
	return $date_str;
}

//ANIMATION FUNCTIONS

function loading_bars($blurb) {
?>
	<div class="loading"><?php echo $blurb; ?></div>
	<div class="spinner">
  		<div class="rect1"></div>
		<div class="rect2"></div>
		<div class="rect3"></div>
		<div class="rect4"></div>
	</div>
<?php
}

function progress_bar($class = 'progressbar') {
?>
	<div class="bar gradient stripe <?php echo $class; ?>">
		<span class="animate" ></span>
	</div>
<?php
}

/**MAIN SCREEN**/
//note: this breaks a few model-view rules since there are two 
//files using it, to minimize rewrites and mistakes

function show_header($title = 'Quiz Beta') {
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $title; ?></title>
	
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
			
		<meta name="author" content="Glenn Santos">
		<meta name="keywords" content="RE licensure exams">
	
		<link rel="shortcut icon" href="<?php echo './images/favicon.ico'; ?>" type="image/vnd.microsoft.icon"/>
		<link rel="icon" href="<?php echo './images/favicon.ico'; ?>" type="image/x-ico"/>
		
		<link rel="stylesheet" href="<?php echo './style.css'; ?>" type="text/css" media="screen">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
	</head>
<?php
}

function show_main($user_id) {
	check_leaderboard();
	$invited = @$_GET['invited'];
	if ($invited) {
		$announce = "Tap on <strong>Quiz Me</strong> below to start a quiz.";
	} else {
		$announce = "<strong>Invite friends to your team!</strong><br/>Share your invite code: <strong>".get_invite_code($user_id)."</strong>";
	}
?>

	<?php show_notif_bar($user_id); ?>
	<div class="quiz">		
		<div class="top_part">
			<div class='announce'>
				<?php echo $announce; ?>
			</div>
		</div>
	
		<div class="mid_part">
			<?php show_quiz_button(); ?>
		</div>
	
		<div class="bottom_part">
			<?php show_leaderboards($user_id, get_all_teams()); ?>
		</div>
		
		<a href="#" class="feedback">Give Feedback</a>
		<div class="feedback_form">
			<h2>Help us improve the app! Send your suggestions, comments and corrections below.</h2> 
			<textarea rows="10" cols="50"></textarea>
			<button>Send Feedback</button>
			<a class="cancel">Cancel</a>
			<div class="sending"><?php loading_bars('Sending feedback...'); ?></div>
			
		</div>
	</div>
<?php
	$quests = get_my_quests($user_id);
	show_quest_update($quests);
	show_shop($user_id);
	show_team($user_id);
}

function show_quiz_button() {
	//show quiz me again button
?>
	<a href="quiz.php" class="quizme">QUIZ ME!</a>
	<div class="getquiz"><?php echo loading_bars('Fetching your quiz...'); ?></div>
	<script>
		$(".quizme").click(function(e){
			e.preventDefault;
			$(".getquiz").show("scale", "fast");
		});
		$(".getquiz").click(function(){
			window.stop();
			$(".getquiz").hide("scale", "fast");
		});
	</script>
<?php
}

function show_quest_update($quests) {
	if(!array_filter($quests)) return; 
?>
	<div class="my_quests">
		<?php 
		if (@array_filter($quests['done'])):
			foreach ($quests['done'] as $quest_name => $quest): ?>
				<div class='quest_done' id="<?php echo $quest_name; ?>">
					<h2>Quest complete!</h2>
						<?php echo $quest['desc']; ?>
					<h2>Earned <span class='give_reward'><?php echo $quest['coins']; ?>¢!</span><br/>
							Team won <span class='give_reward'><?php echo $quest['coins']; ?></h2>
				</div>
			<?php 
			endforeach; 
		endif; 
		
		if (@array_filter($quests['inprogress'])):
		?>
			<h2>Quests in Progress</h2>
		<?php
			foreach ($quests['inprogress'] as $quest_name => $quest): ?>
				<div class='quest_current' id="<?php echo $quest_name; ?>">
					<strong><?php echo $quest['desc'].': '; ?></strong><?php echo $quest['value'].'/'.$quest['goal']; ?>
				</div>
			<?php 
			endforeach; 
		endif; ?>
		<a class="confirm">Nice!</a>
	</div>
<?php
}

function show_main_menu($addstyle = '') {
	//show the menu at the bottom. uses JS to switch items
?>
	<div class="menu" style="<?php echo $addstyle; ?>">
		<ul>
			<li class="quiz active">QUIZ</li>
			<li class="shop">SHOP</li>
			<li class="team">TEAM</li>
		</ul>
	</div>
	<script src ="./views/js/main.js"></script>
<?php 
}

function show_notif_bar($user_id) {
	$coins =  get_coins($user_id);
	$question_ct = count_deck($user_id);
	$all_q_ct = count_all_cards();
?>
	<div class="header">
		<div class="palayaw">Hello, <span class="name"><?php echo get_palayaw($user_id); ?></span></div>
		<ul>
			<li title="Note: this only includes unique questions, excluding duplicates."><span class="questions" ><?php echo $question_ct; ?></span><?php echo '/'.$all_q_ct.'?s'; ?></li>
			<li><span class="coins"><?php echo $coins; ?></span>¢</li>
		</ul>			
	</div>
<?php
}

/**LOGIN AND INVITE FUNCTIONS**/

function show_login_form() {
?>
	<body>	
		<div class="login">
			<h2 style="margin:30px;">Got an invite code?<br/><a href="invite.php">Click here to get started.</a></h2>
			<form method="post">
				<h2>For existing users, enter your cellphone number to log in:</h2>
				<input type="text" name="cel" width="10" placeholder="09991234567"/>
				<!--
				<h2>Enter password:</h2>
				<input type="password" name="pass" placeholder="Enter password"/>
				-->
				<input type="submit" name="login" value="Let Me In!"/>
			</form>
		</div>
		<p style="margin:10px;">We ♥ Google Chrome. Also, it's best to quiz using WiFi instead of mobile data.</p>
		
	</body>
<?php
	//draw login form
}

function show_invite_form($error = '') {
	$blurb = "Start reviewing with friends!<br/>First, let's make your account:";
	$err_class = '';
	if ($error) {
		$blurb = $error;
		$err_class = 'error_msg';
	} 
	$refer = '';
	$cel = '';
	$email = '';
	$name = '';
	
	if ($_POST) {
		$refer = @$_POST['refer'];
		$cel = @$_POST['cel'];
		$email = @$_POST['email'];
		$name = @$_POST['name'];
	}
?>
	<div class="login invite">
		<form method="post">
			<h2 class="<?php echo $err_class; ?>"><?php echo $blurb; ?></h2>	
			<label for="refer">Enter your invite code:</label>
			<input type="text" name="refer" width="10" placeholder="1234" value="<?php echo $refer; ?>"/>
			
			<label for="cel">Then, your celphone:</label>
			<input type="text" name="cel" width="10" placeholder="09991234567" value="<?php echo $cel; ?>"/><br/>
			
			<label for="email">Next, your email:</label>
			<input type="email" name="email" width="10" placeholder="glenn@gmail.com" value="<?php echo $email; ?>"/><br/>
			
			<label for="name">Last, your palayaw or nickname:</label>
			<input type="text" name="name" width="10" placeholder="Glenn" value="<?php echo $name; ?>"/>
			<!--
			<h2>Enter password:</h2>
			<input type="password" name="pass" placeholder="Enter password"/>
			-->
			<input type="submit" name="invited" value="Make My Account!"/>
		</form>
	</div>
<?php
}

function get_invite_code($user_id) {
	return str_pad($user_id*4*4*4, 4, "0", STR_PAD_LEFT);
}

function show_open_teams($user_id, $teams) {
?>
	<form method="post" class="choose_team">
		<h3>Seems you don't have a team yet.</h3>
		<h2>Join a Team</h2>
	<?php foreach ($teams as $team_id => $team): ?>
		<div style="border:solid 3px <?php echo $team['color']; ?>;background:<?php echo $team['color']; ?>;">
			<input type="radio" name="team" id="<?php echo $team_id; ?>" value="<?php echo $team_id; ?>" />
			<label for="<?php echo $team_id; ?>" style="color:<?php echo $team['color']; ?>;"><?php echo $team['name']; ?></label>
		</div>	
	<?php endforeach; ?>
		<input type="submit" name="submit" value="This Is My Team" />
	</form>
<?php
}

/**QUIZ FUNCTIONS**/

function show_quiz($user, $quiz, $firstquiz = 0) {
	$z = 1;
	$card_ids = array();
	progress_bar();
	foreach($quiz as $card) {
		show_card($card, $z++, $firstquiz);
		$points[$card['card_id']] = $card['points'] + 0;
		$serial_ids[$card['card_id']] = $card['serial_id'] + 0;
		//make sure any surprise content shows up in right place
	}
	show_endquiz($firstquiz);
	show_quiz_js($user, $points, $serial_ids);
}

function show_endquiz($firstquiz = 0) {
?>
	<div class="endquiz">
		<h2>Done! Now, let's review your answers.</h2>	
		<?php loading_bars('Checking answers...'); ?>
	</div>
	<div class="main<?php if ($firstquiz) echo ' firstquiz'; ?>"></div>
	<div class="quest_update"></div>
	<div class="show_scores" id="show_scores"></div>
<?php
}

function show_quiz_js($user_id, $points, $serial_ids) {
	//all the js needed for quiz ui
	//button which fills up on first tap (via js) and flashes when filled on tap again. When it flashes, that means the tap is "locked in".
	//if it's an answer, it's saved
	//if it's an link, the next page now loads
	$card_ids = array_keys($points);
?>
	<script src="./views/js/fx.js"></script>
	<script>
		var card_ct = 1;
		var question_id = 0;
		var option = 0;
		var answers = {};
		var user_id = <?php echo $user_id; ?>;
		var moreProgress = 0;
		var questions = [];
		var loop = 0;
		var return_data = "";
		var firstclick = 0;
		
		var reviewMode = false;
		var cards = <?php echo json_encode($card_ids); ?>;
		var points = <?php echo json_encode($points); ?>;
		var json_ids = '<?php echo json_encode($serial_ids); ?>';
		var current = cards.pop();
		var slideCounter = 1;
		var correctNow = true;
		var slideNow = false;
		var dailies = {};
		var oldnotes = '';
		var score = [];
		var quest_update = false;
		score.correct = 0;
		score.incorrect = 0;
		score.perfect = 0;
		score.correctCount = 0;
		var firstquiz = $(".main").hasClass("firstquiz");
		
		if (firstquiz) {
			$(".inst").css("visibility","visible");
		}
		
		$(".show_scores").click(function(){
			$(this).hide();
		});
		
		$(".quest_update").click(function(){
			$(this).hide();
		});
		
		$("a.save").click(function(){
			var thisNotesDiv = $(this).parent();
			var notes = thisNotesDiv.find("textarea").val();
			if (oldnotes != notes) {
				$(".notes a").hide();
				$(".notes .saving").show();
				var serial_id = parseInt($(this).parents(".card").find(".serial_id").html());
				$.post("models/save_notes.php",{serial_id:serial_id, notes:notes}, function(data){
					if(data == 'success') {
						setTimeout(function(){
							$(".notes .saving").html("Note Added!");
							setTimeout(function(){
								$(".notes .saving").html("Sending...").hide();
								$(".notes a").show();
							}, 900);
						}, 1600);
						oldnotes = notes;
					}	
				});
			}
		});
		
		$(".option").click(function(){
			if (reviewMode) return;
			var progress = $(this).children(".progress");
			//log results
			question_id = $(this).parents(".card").attr('id');
			option = parseInt($(this).attr('id').slice(-1));
			answers[question_id] = option;
			if (firstquiz && !firstclick) {
				 $(this).parents(".card").find(".inst").html("Once the bar is filled up, your answer is sent.");
				 firstclick = 1;
			}
			
			//animations
			progress.stop(true);
			$(".progress").stop(true);
			if (progress.width() > 0) {
				flashOptions(progress);
			} else {
				$(".progress").width(0);
				progress.animate({width: "100%"},4000);
				flashOptions(progress);
			}
		});
		
		//show correct answer and hide the others
		$(".card").click(function(){
			if (!reviewMode) return;
			if (!correctNow) return; //prevents triggering when sliding
			correctNow = false;
			var flashAnswer = $(this).find('.flashAnswer');
			var flashCorrect = $(this).find('.flashCorrect');
			var optbox = $(this).find('.option_box');
			var notCorrect = 	$(this).find('.option').not('.Correct');
			var instructions = $(this).find('.inst');
			var notes = $(this).find('.notes');
			if (flashCorrect.hasClass('flashAnswer')) {
				var notCorrectOne = notCorrect.first();
				var isCorrect = true;
			} else {
				var notCorrectOne = flashAnswer;
				var isCorrect = false;
			}
			
			greyOptions(notCorrect);
			greyOptions(notCorrectOne, function(){
				flashThis(flashCorrect, function(){
					fadeOptions(notCorrect);
					progressOnward();
					if (isCorrect) {
						instructions.html("CORRECT!! Tap to continue."); 		
					} else {
						instructions.html("Incorrect. Tap to continue.");
					}
					notes.delay(900).show("slide",{direction:"up"},"fast");
					setTimeout(function(){
						slideNow = true;
					}, 400);
				});
			});
		});
		
		//slide card to next one then flash the answer
		$(".question_box, .option").click(function(){
			if (!reviewMode) return;
			if (!slideNow) return; //prevents triggering when showing corrects
			slideNow = false;
			if (slideCounter <= 4) {
				var thisCard = $(this).parents(".card");
				slideCard(thisCard, "left", false, function(){
					current = cards.pop();
					var flashAnswer = $("#"+current).find('.flashAnswer');
					setTimeout(function () {
						flashThis(flashAnswer);
						correctNow = true;
					}, 800);
					if (slideCounter > 4) {
						$(".menu").show();
						$(".progressbar").remove();
						$(".show_scores").delay(450).show("scale", "fast", 		
							function(){
								if (firstquiz) {
									firstquiz = false;
									var gift = 400;
									var coinbonus = "<h2>Your first quiz!</h2> Here, take this: <span class='give_gift'>+"+gift+"¢</span>";
									if (!quest_update) {
										coinbonus = coinbonus + "<a class='confirm'>Nice!</a>";
									}
									$(".quest_update").prepend(coinbonus);
									$(".quest_update").show();	
								} 
								if (quest_update) {
									$(".quest_update").show();
								}
							}
						);	
					}
				});
			} 
			slideCounter += 1;
		});
		
	</script>
<?php
}

//QUESTION FUNCTIONS

function show_card($card, $z, $firstquiz) {
?>
	<div class="card" style="<?php echo 'z-index:'.$z.';'; ?>" id="<?php echo $card['card_id']; ?>">
		<?php 
			show_question($card['question']);	
			show_inst($firstquiz, $z);	
			show_options($card['options']);
			show_card_footer($card); 
		?>
	</div>
<?php
}


function show_question($question) {
	//formats the question
?>
	<div class="question_box">
		<div class="question"><?php echo $question; ?></div>
	</div>
<?php 
}

function show_inst($firstquiz, $z) {
	if ($firstquiz) {
		switch ($z):
			case 4:
				$inst = "Let's start! Tap on your answer to the question.";
			break;
		
			case 3:
				$inst = "To change, tap another answer before the bar is filled up.";
			break;
		
			case 2: 
				$inst = "Tap an answer twice to send it immediately.";
			break;
		
			case 1:
				$inst = "Take a quiz as often as you want, even everyday.";
			break;
		endswitch;
	} else {
		$inst = "&nbsp;";
	}
?>
	<div class="inst"><?php echo $inst; ?></div>
<?php
}

function show_options($options, $addclass = array(), $notes ='') {
	$noters = array("Jot down some notes",
							"Write a trick that will help you remember the answer",
							"Note how you arrived at your answer",
							"Connect this answer to a previous learning",
							"Rewrite the answer here to remember it better",
							"Enter a relevant fact to help you remember",
							"Explain why this is the correct answer");
	$key = rand(0,count($noters)-1);
	$noter = $noters[$key];
?>
	<div class="option_box">
		<?php foreach($options as $key => $option): ?>
			<a href="#" class="<?php echo 'option'.@$addclass['option'][$key]; ?>" id="option<?php echo $key; ?>"><div class="<?php echo 'progress'.@$addclass['progress'][$key]; ?>"></div><div class="content"><?php echo $option; ?></div></a>
		<?php endforeach; ?>
		<div class="notes">
			<textarea placeholder="<?php echo $noter; ?>"><?php echo $notes; ?></textarea>
			<div class="saving">Sending...</div> 
			<a class="save">Add Notes</a>
		</div>
	</div>
<?php 
}

function show_answer($card) {
	//formats answer
	//dumped the entire $card for easier passing of var
	$order = $card['order'];
	$options = array();
	$addclass = array();
	for($i=1;$i<=4;$i++) {
		$n = $order[$i-1];
		$options[$i] = $card['option'.$n];
		$addclass['progress'][$i] = '';
		$addclass['option'][$i] = '';
		if ($n == $card['answer']) {
			$addclass['progress'][$i] .= ' flashAnswer';
		}
		if ($n == 1) {
			$addclass['progress'][$i] .= ' flashCorrect';
			$addclass['option'][$i] = ' Correct';
		}
	}
	
	 show_options($options, $addclass, $card['notes']);
}

function show_card_footer($card) {
	//processes other card info and turns it into a footer
	//includes link to settings and chat
	$points = $card['points'];
	$notes = $card['notes'];
	$serial_id = $card['serial_id'];
	$nextdate = $card['nextdate'];
	$div = ' &bull; ';
	if (!$nextdate) { //new card
		$div = '<img src="images/new.gif" /> ';
	}
	
	$div .= $points.' points';
?>
	<div class="question_id"><?php echo 'Question #'.$card['card_id'].$div; ?></div>
	<div class="serial_id"><?php echo $serial_id; ?></div>
<?php
}

function show_helptext($helptext) {
	
?>
	<div class="helptext" title="<?php echo $helptext; ?>">
		<?php echo $helptext; ?>
		<a class="confirm">Tap to hide me.</a>
	</div>
<?php
}

/**SHOP FUNCTIONS**/

function show_shop($user_id) {
	$coins = get_coins($user_id);
	$items = get_shop_items();
	$invited = @$_GET['invited'];
	$announce = '';
	$firstbuy = '';
	if ($invited) {
		$announce = "<h2>You're in!</h2>For joining, we're giving you this special gift:<br/><br/><span class='give_gift'>+45 questions</span> for the quiz<br/><span class='give_gift'>+400¢</span> (coins) to spend";
		$announce .= "<a class='confirm'>Thanks!</a>";
	} 
	if (is_first_buy($user_id)) {
		$firstbuy = "<div class='firstbuy'><h2>Your first purchase!</h2> Take this: <span class='give_gift'>+200¢</span>, a gift from the shopkeep.<a class='confirm'>Thanks!</a></div>";
	}
?>
	<div class="shop_popup">
		You're buying <span></span>
		<p></p>
		<a href='#confirm' class='buying confirm'>Confirm</a>&nbsp;<a href='#cancel' class='cancel'>Cancel</a>
	</div>
	<div class="shop_alert"><?php echo $announce; ?></div>	
	<?php echo $firstbuy; ?>
	<div class="shop">
		<?php show_dailies($user_id); ?>
		<div class="items">
			<?php 
			foreach ($items as $key => $item): 
				if ($item["type"] === "random") {
					$title = "Each question is chosen at random from the entire set of all available questions.<br/><br/><em>(There's a chance you'll get questions you already own)</em>";
				} else if ($item["type"] === "new") {
					$title = "NEW questions are chosen at random from the remaining questions you don't own yet.<br/><br/><strong>Guaranteed all-new questions!</strong>";
				}
			?>
				<p><span class="<?php echo $key; ?>"><?php echo $item["desc"]; ?></span> <a href="#buy" class="<?php echo $key; ?>" title="<?php echo $title; ?>"><?php echo $item["price"]."¢"; ?></a></p>
			<?php endforeach; ?>
		</div>
		
		<!--
		<a href="#tradein">Trade In Duplicates</a>
		<a href="#buycoins">Buy Coins</a>
		-->
	</div>
<?php
	shop_js($user_id);
}

function shop_js($user_id) {
	$alert_on = @$_GET['invited'];
	$firstbuy = is_first_buy($user_id) ? 1: 0;
	
?>
	<script>
		var items = {};
		var user_id = <?php echo $user_id; ?>;
		var coins = parseInt($(".coins").html());
		var question_ct = parseInt($(".questions").html());
		var buy = 'Qx4';
		var price = parseInt($("a."+buy).html());
		var title = '';
		var alert_on = '<?php echo $alert_on; ?>';
		var firstbuy = <?php echo $firstbuy; ?>;
		var blurb = '';
		
		$('a[href="#buy"]').each(function(){
			var thisPrice = parseInt($(this).html());
			if(coins < thisPrice) {
				$(this).attr("href","#off");
			}
		}); 
		
		if (alert_on) {
			$(".shop_alert").show("scale", "fast");
		}
		
		$('a[href="#buy"]').click(function(){
			$(".alert").css("display","none");
			buy = $(this).attr('class');
			price = parseInt($("a."+buy).html());
			item = $("span."+buy).html();
			$(".shop_popup span").html("<strong>"+item+"</strong>");
			title = $(this).attr("title");
			$(".shop_popup p").html(title);
			$(".shop_popup").show("scale", "fast");
		});
		
		$(".cancel").click(function(){
			$(".shop_alert").hide("scale", "fast");
			$(".shop_popup").hide("scale", "fast");
		});  
		
		$(".shop_alert").click(function(){
			$(".shop_alert").hide("scale", "fast");
		}); 
		
		$(".firstbuy").click(function(){
			$(".firstbuy").hide("scale", "fast");
		});  
		
		$(".buying").click(function(){
			$.post("models/buy_stuff.php",{user_id: user_id, buy: buy},
				function(data){ 
					$(".shop_popup").hide("scale", "fast");
					if (data) {
						var response = parseInt(data);
						if (response < 0) {
							blurb = "Not enough coins! Earn or buy some first.<a class='confirm'>Will do</a>";
						} else if (response > 0) {
							if (firstbuy) {
								coins += 200;
								$(".firstbuy").show();
								firstbuy = 0;
							}
							blurb = "<h2>Purchase complete!</h2>Your new cards will show up the next time you take a quiz.<a class='confirm'>Gotcha</a>";
							coins = coins - price;
							var purchase = parseInt(item);
							question_ct = question_ct + purchase;
							$(".coins").html(coins);
							$(".questions").html(question_ct);
						}
					} else {
						blurb = "Can't contact the shop. Try buying again in a few minutes<a class='confirm'>Gotcha</a>";
					}
					$(".shop_alert").html(blurb);
					$(".shop_alert").show("scale", "fast");
				});			
		});
		
		$(".replace").click(function(){
			var questdiv = $(this).parent(".quest");
			var quest = questdiv.attr("id");
			$.post("models/replace_quest.php",{ user_id: user_id, quest: quest }, 
				function(data){
					var new_quest = JSON.parse(data);
					if (new_quest.name) {
						$(questdiv).hide("scale", "fast");
						$(questdiv).attr("id",new_quest.name);
						$(".replace").remove();
						setTimeout(function(){
							$(questdiv).find(".reward").html("+"+new_quest.coins+"¢");
							$(questdiv).find(".desc").html(new_quest.desc);
							$(questdiv).find(".goal").html("0/"+new_quest.goal);
							$(questdiv).show("scale", "fast");
						}, 400);
					}
				}
			);
		});
	</script>
<?php
}

//QUEST FUNCTIONS

function get_all_quests() {
	$quests = array("perfect_score" => 
								array(
									"desc" => "Get a perfect score",
									"coins" => 200,
									"measure" => "perfect",
									"goal" => 1),
											
								"5_quiz" =>
								array(
									"desc" => "Take 5 quizzes",
									"coins" => 200,
									"measure" => "quiz",
									"goal" => 5),
									
								"500_pts" =>
								array(
									"desc" => "Earn 500 points",
									"coins" => 200,
									"measure" => "points",
									"goal" => 500),
								
								"20_questions" =>
								array(
									"desc" => "Answer 20 questions",
									"coins" => 200,
									"measure" => "questions",
									"goal" => 20),
								
								"10_NEW" => 
								array(
									"desc" => "Answer 10 NEW questions",
									"coins" => 200,
									"measure" => "new",
									"goal" => 10),
								
								"10_corrects" =>
								array(
									"desc" => "Get 10 correct answers",
									"coins" => 200,
									"measure" => "corrects",
									"goal" => 10),
											
								"team_invite" =>
								array(
									"desc" => "Invite a friend to your team",
									"coins" => 400,
									"measure" => "invite",
									"goal" => 1),
								
								"1000_pts" =>
								array(
									"desc" => "Earn 1000 points",
									"coins" => 400,
									"measure" => "points",
									"goal" => 1000),
										
								"3_perfect" => 
								array(
									"desc" => "Get 3 perfect scores",
									"coins" => 500,
									"measure" => "perfect",
									"goal" => 3),
									
								"3_streak" => 
								array(
									"desc" => "Get 3 perfect scores in a row",
									"coins" => 800,
									"measure" => "streak",
									"goal" => 3)
								);
		
		return $quests;				
																		
}

function show_dailies($user_id) {
	//get the daily quests of the user
	update_refill($user_id);
	$dailies = get_dailies($user_id);
	$all_quests = get_all_quests();
	$reroll = get_reroll($user_id);
	if (!$dailies) {
		echo "<div class='no_quests'><h2>All quests completed!</h2> More coming tomorrow. For now, why not buy some questions?</div>";
		return false;
	}
?>
	<br/>
	<div class="dailies">
	<?php foreach ($dailies as $quest => $value): ?>
		<div>
			<div class="quest" id="<?php echo $quest; ?>">
				Earn
				<div class="reward">
					+<?php echo $all_quests[$quest]["coins"]; ?>¢
				</div>
				when you
				<div class="desc">
					<?php echo $all_quests[$quest]["desc"]; ?>
				</div>
				<div class="goal">
					<?php echo $value."/".$all_quests[$quest]["goal"]; ?>
				</div>
				<?php if ($reroll) echo '<a href="#" class="replace">Swap</a>'; ?>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
<?php
}

/**LEADERBOARDS**/


function show_leaderboards($user_id, $teams) {
	$team_id = get_team($user_id);
	$size = 1.2;
	$i = 0;
?> 
	<div class="leaderboard">
	<?php 
	foreach ($teams as $team): 
		if ($team['team_id'] === '100') continue;
		$addstyle = 'font-size:'.$size.'em; color:'.$team['color'].';';
		if (!$i) $addstyle .= 'font-weight:bold;';
		$size -= 0.1;
		if($team['team_id'] === $team_id) {
			$addclass = "myteam";
		} else {
			$addclass = "";
		}
	?>
		<div class="<?php echo $addclass; ?>">
			<div class="teamname"><?php echo ++$i; ?>. <span style="<?php echo $addstyle; ?>"><?php echo $team['name']; ?></span></div>
			<div class="points"><?php echo $team['points'].' Pts'; ?></div>
		</div>
	<?php endforeach; ?>
	</div>
<?php
}

/**TEAM FEED**/

function show_team($user_id) {
	//show the team page
	$team = get_team_info($user_id, true);
	$team_id = $team['team_id'];
	$feed = get_team_feed($user_id, $team_id);
	$seenlast = get_feedseenlast($user_id);
	$unseen_ct = 0;
?>
	<div class="team">
		<?php 
			show_team_name($team);
			show_status_box($user_id);
			foreach($feed as $entry) {
				show_feed_entry($user_id, $entry, $seenlast);
				if ($seenlast < $entry['date']) $unseen_ct++;
			}
		?>
	</div>
<?php
	team_js($user_id, $unseen_ct);
}

function show_team_name($team) {
	echo '<div class="teamname" style="color:'.$team['color'].';">'.$team['name'].'</div>';
}

function team_js($user_id, $unseen_ct) {
?>
	<script>
		var user_id = <?php echo $user_id; ?>;
		var unseen_ct = <?php echo $unseen_ct; ?>;
		
		$('.status_box').keypress(function(event) {
			// Check the keyCode and if the user pressed Enter (code = 13) 
			if (event.keyCode == 13) {
				event.preventDefault();
				var message = $(this).val();
				if (!message) return;
				$.post('models/send_message.php',{user_id: user_id, message: message}, 
					function(reply) {
						if(reply !== "-1") {
							var data = JSON.parse(reply);
							var div = formatMessage(data);
							$(".top_box").after(div);
							$('.status_box').val('');
						}
						if(data.first) {
							var coins = parseInt($(".coins").html());
							coins += 400; //first message bonus
							$(".coins").html(coins);
							$(".shop_alert").html("<h2>Sent your first message!</h2> Here's <span class='give_gift'>+400¢</span> for being a team player!<a class='confirm'>Thanks!</a>");
							$(".shop_alert").delay(450).show("scale", "fast");
						}
					}
				);
			}
		});
		
		function formatMessage(data) {
			var avatar = $(".entry_avatar").html();
			var name = $(".palayaw .name").html();
			var div = ''; 
			div = div +	 '<div class="entry" '+data.first+'>';
			div = div +		'<div class="entry_avatar">';
			div = div +			avatar;
			div = div +		'</div>';
			div = div + '<div class="entry_box"><div class="entry_message">';
			div = div +	'<span class="name">'+name+'</span>: ';
			div = div +	data.message;
			div = div + '</div></div>';
			div = div + '<div class="entry_details">';
			div = div + '<div class="entry_actions"></div>';
			div = div +	'<div class="entry_date">';
			div = div +		data.date;
			div = div + '</div></div></div>';
			return div;
		}
		
		window.onload = function() {
			 if (unseen_ct) {
				$('li.team').append('<div class="notif">'+unseen_ct+'</div>');
			}
		};
		
	</script>
<?php
}



//FEED FUNCTIONS

function get_avatar_from_name($user_id) {
	//if no avatar, use this to get the avatar from the user's first initial
	$colorkey = "background:".get_avatar_color($user_id).";";
	$nom = get_palayaw($user_id);
	$nom = strtoupper($nom[0]);
	return '<div class="no_av" style="'.$colorkey.'">'.$nom.'</div>';
}

function get_avatar_color($user_id) {
	//pastel colors
	$colors = array ('#F7977A', '#F9AD81', '#FDC68A', '#C4DF9B', '#A2D39C', '#82CA9D', '#7BCDC8', '#6ECFF6', '#7EA7D8', '#8493CA', '#8882BE', '#A187BE', '#BC8DBF', '#F49AC2', '#F6989D');
	$rand = ($user_id % (count($colors) - 1)) + 1;
	return $colors[$rand];
}

function show_feed_entry($user_id, $entry, $seenlast) {
	//show one status message
	if ($entry['avatar']) {
		$avatar = '<img src="'.$entry['avatar'].'" />';
	} else {
		$avatar = get_avatar_from_name($entry['sender']);
	}
	
	if ($seenlast < $entry['date']) {
		$unseen = ' unseen';
	} else {
		$unseen = '';
	}
	
?>
	<div class="<?php echo 'entry'.$unseen; ?>">
		<div class="entry_avatar"><?php echo $avatar; ?></div>
		<div class="entry_box">
			<div class="entry_message">
				<?php echo $entry['message']; ?>
			</div>
		</div>
		<div class="entry_details">
			<div class="entry_date">
				<?php echo niceformat_date($entry['date']); ?>
			</div>
		</div>
	</div>
<?php
}

function show_status_box($user_id) {
	//show the status update form. It's just a textarea at the top
	$blurbs = array("Post something encouraging!",
							"Give some exam tips to the team!",
							"Say something nice to someone!",
							"Congratulate someone!",
							"Cheer someone on!",
							"Encourage someone to keep trying!",
							"Send a nice greeting to someone",
							"Share a new topic you learned today",
							"Ask the team to clarify an answer for you",
							"Help someone with a question",
							"What's your study goal for today?");
	$key = rand(0,count($blurbs)-1);
	$blurb = $blurbs[$key];
	$avatar = get_avatar_from_name($user_id);
?>
	<div class="entry top_box">
		<div class="entry_avatar"><?php echo $avatar; ?></div>
		<div class="entry_box">
			<div class="entry_message">
				<textarea class="status_box" placeholder="<?php echo $blurb; ?>"></textarea>
			</div>
		</div>
	</div>
		
<?php
}


//TIPS FUNCTIONS

function get_tips(){
	$tips = array("Connect to WiFi instead of mobile data for the best quiz experience",
						"You get at least 50 points for each correct answer, and 10 points for each incorrect answer",
						"Get a bonus 100 points when you get a perfect score of 4 correct answers.",
						"Questions are taken from previous exams and checked for accuracy",
						"Use coins (or ¢) to buy more questions. More questions, means more quizzes and more points.",
						"You earn coins equal to the number of points you got during a quiz",
						"Check the bottom of the screen to see how many points a question gives you",
						"Questions are upgraded whenever you get them correct. More correct answers means more points (and coins).",
						"Wrong answers reset a question's point back to 50",
						"The more often you get a question right, the less often it shows up, to challenge your memory and help you retain .",
						"The more challenging it is to remember the right answer, the better you retain that knowledge.",
						"Explore the app to learn more how it works and to complete hidden quests and earn coins",
						"Earn coins by exploring the app, answering quizzes and completing daily quests",
						"You earn points for your team. Get better than your other teams to win the weekly....",
						"Complete quests that you see in the shop to earn points and coins.",
						"If you've finished today's quests, you'll get more tomorrow",
						"You can swap quests for another one if you don't like it. You can do this once per day"				
						
						);
}
?>