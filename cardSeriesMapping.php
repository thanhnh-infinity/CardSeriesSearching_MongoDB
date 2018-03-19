<?php
define('DATABASE_NAME','Card_Series_TVOD');
define('NUMBER_RECORD_DISPLAY',1000);
define('YEAR_EXPIRED_DATE',2012);
define('MONTH_EXPIRED_DATE',12);
define('DATE_EXPIRED_DATE',30);
define('CARD_VALUE',250);
define('VALID_IP_ADDRESS_1','10.84.70.72');
define('VALID_IP_ADDRESS_2','10.84.2.169');
define('VALID_IP_ADDRESS_3','::1');

$action = $_GET['action'];
if ($action == 'insert_manual'){
	insert_card_series_list_to_database();
} else if ($action == 'check_mongo') {
	check_mongodb();
} else if ($action == 'drop'){
	drop_database();
} else if ($action == 'list_all'){
	//list_all();
} else if ($action == 'list_random_10'){
	list_random_10();
} else if ($action == 'find'){
	$serie = $_GET['serie'];
	find_card_serie($serie);
} else if ($action == 'insert_from_file'){
	$file = $_GET['file'];
	insert_card_series_list_to_database_from_file($file);
} else if ($action == 'count_card'){
	count_card();
}

function check_ip_from_cms($ip_address){
	if ($ip_address==VALID_IP_ADDRESS_1 || $ip_address==VALID_IP_ADDRESS_2 || $ip_address == VALID_IP_ADDRESS_3){
		return true;
	} else {
		return false;
	}
}

function count_card(){

	$ip_address = trim($_SERVER['REMOTE_ADDR']);
	if (!check_ip_from_cms($ip_address)){
		echo "Wront Remote Client !";
		return;
	}

	$conn = new Mongo();
	$db = $conn->selectDB(DATABASE_NAME);
	$list_card = $db->TBL_Card_Series;

	$list_card->ensureindex(array('num'=> 1));

	$cursor = $list_card->find();
	print_r ($cursor->explain());
}

function insert_card_series_list_to_database_from_file($file){

	$ip_address = trim($_SERVER['REMOTE_ADDR']);
	if (!check_ip_from_cms($ip_address)){
		echo "Wront Remote Client !";
		return;
	}

	try {
		$conn = new Mongo();
		$db = $conn->selectDB(DATABASE_NAME);
		//$db->TBL_Card_Series->drop();
		$fp = fopen($file, 'r');
		$expired_date = YEAR_EXPIRED_DATE . "-" . MONTH_EXPIRED_DATE . "-" . DATE_EXPIRED_DATE;
		$temp = strtotime($expired_date);
			
		$i = 0;
		while ($line = fgets($fp)){
			$line = rtrim($line);
			$card = array(
		      'card_serie' => intval($line),
		      'card_status' => 0,
		      'card_value' => CARD_VALUE,
		 	    'card_expired_date' => $temp,
			);
			$db->TBL_Card_Series->save($card);
			$i++;
		}
		$db->TBL_Card_Series->ensureindex(array('card_serie'=> 1));
		echo "Insert Successfully. Insert " . $i . " Card Serie";
	} catch(Exception $ex){
		echo $ex->getMessage();
	}
}
function find_card_serie($serie){

	$ip_address = trim($_SERVER['REMOTE_ADDR']);
	if (!check_ip_from_cms($ip_address)){
		echo "Wront Remote Client !";
		return;
	}

	$conn = new Mongo();
	$db = $conn->selectDB(DATABASE_NAME);
	$list_card = $db->TBL_Card_Series;

	$list_card->ensureindex(array('num'=> 1));

	$compare_serie = new MongoInt32($serie);
	$cursor = $list_card->find(array('card_serie' => $compare_serie));
	 
	//print_r ($cursor->explain());

	$i = 0;
	foreach($cursor as $obj){
		$card_serie = $obj['card_serie'];
		if ($card_serie > 0){
			$i++;
		}
	}

	if ($i >= 1){
		$card_serie = "";
		$card_status = "";
		$card_value = "";
		$id = "";
		foreach($cursor as $obj){
			$id = $obj['_id'];
			$card_serie = $obj['card_serie'];
			$card_status = $obj['card_status'];
			$card_value = $obj['card_value'];
			$card_expired_date = $obj['card_expired_date'];

			/* Update status for all serie card */
			if ($card_status == 0){
				$list_card->update(
				array('_id'=> $id),
				array('$set' => array('card_status'=>1))
				);
			}
		}
		header('Content-Type: application/json');
		$array_data = array('success'=>true,'card_serie'=>$card_serie,'card_status'=>$card_status,'card_value'=>$card_value,'card_expired_date'=>$card_expired_date);
		echo json_encode($array_data);
	} else {
		header('Content-Type: application/json');
		$array_data = array('success'=>false,'reason'=>'SERIE_INVALID');
		echo json_encode($array_data);
	}
}

function list_random_10(){
	$ip_address = trim($_SERVER['REMOTE_ADDR']);
	if (!check_ip_from_cms($ip_address)){
		echo "Wront Remote Client !";
		return;
	}


	$conn = new Mongo();
	$db = $conn->selectDB(DATABASE_NAME);
	$list_card = $db->TBL_Card_Series;

	$db->TBL_Card_Series->ensureindex(array('num'=> 1));
	print_r($list_card->find()->explain());
	$cursor = $list_card->find()->limit(NUMBER_RECORD_DISPLAY)->skip(20);

	echo "<table border=1>";
	$i = 0;
	echo "<tr><td>Seq</td><td>ID</td><td>Card Serie</td><td>Status</td><td>Value</td><td>Expired Date</td></tr>";
	foreach ($cursor as $obj) {
		echo "<tr>";
		echo "<td>" . $i . "</td>";
		echo "<td>" . $obj['_id'] . "</td>";
		echo "<td>" . $obj['card_serie'] . "</td>";
		echo "<td>" . $obj['card_status'] . "</td>";
		echo "<td>" . $obj['card_value'] . "</td>";
		echo "<td>" . $obj['card_expired_date'] . "</td>";
		echo "</tr>";
		$i++;
	}
	echo "</table>";
}
function list_all(){
	$conn = new Mongo();
	$db = $conn->selectDB(DATABASE_NAME);
	$list_card = $db->TBL_Card_Series;

	print_r($list_card->find()->explain());

	$cursor = $list_card->find();
	echo "<table border=1>";
	$i = 0;
	echo "<tr><td>Seq</td><td>ID</td><td>Card Serie</td><td>Status</td><td>Value</td></tr>";
	foreach ($cursor as $obj) {
		echo "<tr>";
		echo "<td>" . $i . "</td>";
		echo "<td>" . $obj['_id'] . "</td>";
		echo "<td>" . $obj['card_serie'] . "</td>";
		echo "<td>" . $obj['card_status'] . "</td>";
		echo "<td>" . $obj['card_value'] . "</td>";
		echo "</tr>";
		$i++;
	}
	echo "</table>";
}

function insert_card_series_list_to_database(){

	$ip_address = trim($_SERVER['REMOTE_ADDR']);
	if (!check_ip_from_cms($ip_address)){
		echo "Wront Remote Client !";
		return;
	}

	try {
	 $conn = new Mongo();
	 $db = $conn->selectDB(DATABASE_NAME);
	 $db->TBL_Card_Series->drop();
	 for($i = 0 ; $i < 400000 ; $i++){
	 	$card = array(
	      'card_serie' => rand(0, 9999999999),
	      'card_status' => 0,
	      'card_value' => 120,
	 	);
	 	$db->TBL_Card_Series->save($card);
	 }
	 $db->TBL_Card_Series->ensureindex(array('card_serie'=> 1));
	 echo "Insert Successfully. Insert " . $i . " Card Serie";
	} catch(Exception $ex){
		echo $ex->getMessage();
	}
}

function check_mongodb(){


	try {
		$conn = new Mongo();
		$db = $conn->selectDB(DATABASE_NAME);
		echo "CardSeries Ready !";
	} catch (Exception $ex){
		echo $ex->getMessage();
	}
}
function drop_database(){

	$ip_address = trim($_SERVER['REMOTE_ADDR']);
	if (!check_ip_from_cms($ip_address)){
		echo "Wront Remote Client !";
		return;
	}

	try {
	 $conn = new Mongo();
	 $db = $conn->selectDB(DATABASE_NAME);
	 $db->TBL_Card_Series->drop();
	 echo "Drop Database Successfully !";
	} catch (Exception $ex){
		echo $ex->getMessage();
	}
}
?>