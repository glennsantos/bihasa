function flashThis(element, callback) { 
	//flash button
	//total animation time: 500

$(element).css("width","100%").fadeOut(80).fadeIn(80).fadeOut(100).fadeIn(100, callback);
	
}

function flashOptions(element) { 
	//flash option button
	var card = element.parents('.card');
	 flashThis(element, function(){
		progressOnward();
		slideCard(card, "left", true);		
		questions.push(question_id);
		card_ct += 1;
		//save answers
		if (card_ct > 4) {
			var json_answers = JSON.stringify(answers);
			$.post("models/save_quiz.php", {user_id: user_id, answers: json_answers}, 		
				function(data){
					if (data) {
						data = JSON.parse(data);
						$.post("models/process_quiz.php", {quiz_id: data.quiz_id, serial_ids: json_ids}, function(d2){ 
							showHome(); 
						});
						setTimeout(function(){	
							$('.progressbar').animate({"width":"2%"},"slow");
							returnCards();
							goReviewMode(data.corrects);
						},2000);
					}
				}
			);			
			card_ct = 1;
		}
	});
}

function reviewTutorial(qid,counter) {
	var blurb = '';
	if (counter == 1) {
		blurb = 'Your answer. Tap to show the correct answer.';
	} else if (counter == 2) {
		blurb = 'Your team gets the points below for each correct answer.';
	} else if (counter == 3) {
		blurb = 'Quizzes have no time limit. Take your time.';
	} else if (counter == 4) {
		blurb = 'Quizzes also earn coins. Use it to buy more questions.';
	}
	
	$("#"+qid).find(".inst").html(blurb);
}

function goReviewMode(corrects) {

	//prep for review mode display
	$(".progress").css("width","0%");
	$('.inst').css("visibility","visible");
	$(".question").prepend('<div class="review_mode">REVIEW MODE</div>');
	$(".inst").html('Tap to show the correct answer.');
	
	//set review mode variables
	reviewMode = true;
	moreProgress = 0;
	
	//get answers
	var answerKey = corrects;
	var question_ids = Object.keys(answerKey);
	answerKey = question_ids.map(function(key){
		return answerKey[key];
	});
		
	
	//convert key and insert into options
	var counter = 1;
	while (answerKey.length) {
		var ans = answerKey.pop();
		var qid = question_ids.pop();
		var theAnswer = $("#"+qid).find("#option"+ans);
		theAnswer.addClass("Correct");
		theAnswer.find(".progress").addClass("flashCorrect");	
		$("#"+qid).find("#option"+answers[qid]).find(".progress").addClass('flashAnswer');
		
		if (answers[qid] == ans) {
			 score.correct += Math.max(50,points[qid]);
			 score.correctCount += 1;
		} else {
			score.incorrect += 10;
		}
		if (firstquiz) {
			reviewTutorial(qid,counter);
			counter += 1;
		}
	}
	
	if (score.correctCount == 4)  {
		score.perfect = 100;
	}
	
	$(".show_scores").html(showScores(score));
	
	//flash first answer
	setTimeout(function(){
		flashThis($("#"+current).find('.flashAnswer'),function(){
			setTimeout(function () {
				$("#"+current).find('.inst').css("visibility","visible");
				allowClick = true;
			}, 200);
			$(".endquiz").remove();
			$(".main").show();
			if ($(".my_quests").length) {
				quest_update = true;
				var quests = $(".my_quests").html();
				$(".quest_update").html(quests);
				$(".my_quests").remove();
			}
		});
		
	}, 1200);
}

function showHome() {
	$(".main").load("models/show_home.php");
}

function progressOnward(){
	moreProgress += 25;
	$('.progressbar').animate({'width': moreProgress+"%"},"slow");
}
		
function slideCard(card, transition, logThis, completeFn) {
	//animate card to slide off screen
	//if (logThis) logSession('slide',question_id,option);
	$(card).css('border-right','solid 3px #000');
	$(card).delay(450).hide("slide", {direction: transition}, 300, completeFn);
}

function returnCards() {
	var last = questions.pop();
	showCard($('#'+last), "left", true);
	if (questions.length) {
		setTimeout(function(){	
			returnCards();
		},100);
	}
}

function showCard(card, transition, completeFn) {
	$(card).delay(450).show("slide", {direction: transition}, 300, completeFn);
}



function greyOptions(options, callback) {
	$(options).delay(200).animate({backgroundColor: '#FDFDFD'}, 800, callback);
}

function fadeOptions(options,callback) {
	$(options).delay(0).hide("blind",800); 
}

function showScores(score) {
	var div = "";
	var link = "Awesome, thanks!";
	if (score.correctCount == 4) {
		div = div+"<h2>Got a perfect score! Great job!</h2>";
	} else if (score.correctCount) {
		div = div+"<h2>Got "+score.correctCount+" in 4 correct!</h2>";
	} else {
		div = div+"<h2>No corrects. :( Keep on trying!</h2>";
		link = "I'll do better";
	}
	
	if (score.correct) {
		div = div + "Corrects: "+score.correct+" Pts<br/>";
	}
	if (score.incorrect) {
		div = div + "Incorrects: "+score.incorrect+" Pts<br/>";
	}
	if (score.perfect) {
		div = div +"Perfect score BONUS: "+score.perfect+" Pts<br/>";
	}
	
	var totalPoints = score.correct + score.incorrect + score.perfect;
	
	div = div +"<h2>Earned <span class='give_reward'>+"+totalPoints+"¢!</span><br/>Team won <span class='give_reward'>+"+totalPoints+" Pts!</span></h2>";
	
	div = div + "<a class='confirm'>"+link+"</a>"; 
	return div;
}