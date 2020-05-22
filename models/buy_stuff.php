<?php 
	$buy = @$_POST['buy'];
	$user_id = @$_POST['user_id'];
	
	//if (!$user_id OR !$buy) return;
	
	@include_once "functionsModel.php";
	
	$deck = array();
	$bonus = 0;
	$items = get_shop_items();
	$mysqli = connect_db();

	$coins = get_coins($user_id);
	
	$type = $items[$buy]["type"];
	$questions = $items[$buy]["questions"];
	$price = $items[$buy]["price"];
	
	if ($coins < $price) {
		echo "-1";
		return;
	}
	
	if ($questions) {
		
		if (is_first_buy($user_id)) $bonus = 200;
		
		save_purchase($user_id, $buy, $price);
		
		update_coins($user_id, $bonus-$price);
		give_questions($user_id, $type, $questions);

		echo "2";
	}
	
	//Trade in cards
	
	//Purchase coins
	
	/**
	Purchase features
	1) Change team
	2) Change team name
	**/
	
	
	
	
	$mysqli->close(); 
?>