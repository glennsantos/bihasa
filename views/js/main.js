var activeClass = "quiz";

var vars = [], hash;
	var q = document.URL.split('#')[0];
    var q = document.URL.split('?')[1];
    if(q != undefined){
        q = q.split('&');
        for(var i = 0; i < q.length; i++){
            hash = q[i].split('=');
            vars.push(hash[1]);
            vars[hash[0]] = hash[1];
        }
}

$(".menu li").click(function(){
	if ($(this).hasClass("active")) return;
	$("div."+activeClass).hide();
	$(".shop_popup").hide();
	$(".shop_alert").hide();
	$("li."+activeClass).removeClass("active");
	activeClass = $(this).attr("class");
	$("div."+activeClass).show();
	$("li."+activeClass).addClass("active");
	if($(this).hasClass("team") && $(".notif").html()) {
		$(".notif").html("");
		$(".notif").css("visibility","hidden");
		$.post("models/see_feed.php");
	}
});

if (vars.show) {
	$("div."+activeClass).hide();
	$("li."+activeClass).removeClass("active");
	activeClass = vars.show;
	$("div."+activeClass).show();
	$("li."+activeClass).addClass("active");
	var message = "Seems you ran out of questions for today. Wait until tomorrow of buy some more questions to continue.<a class='confirm' >Gotcha!</a>";
	$(".shop_alert").html(message);
	$(".shop_alert").show();
}

$("a.feedback").click(function(){
	$(".feedback_form").show();
});

$(".feedback_form .cancel").click(function(){
	$(".feedback_form").hide();
});

$(".feedback_form button").click(function(){
	$(".feedback_form button").hide();
	$(".feedback_form .cancel").hide();
	$(".feedback_form textarea").hide();
	$(".feedback_form .sending").show();
	var message = $(".feedback_form textarea").val();
	$.post("models/send_feedback.php", {message: message},  function(data){
		if (data == "success") {
			$(".feedback_form textarea").val("");
			$(".feedback_form textarea").show();
			$(".feedback_form button").show();
			$(".feedback_form .cancel").show();
			$(".feedback_form .sending").hide();
			$(".feedback_form").hide();
			$(".shop_alert").html("Feedback sent<a class='confirm'>Gotcha</a>");
			$(".shop_alert").show();
		}
	});
});