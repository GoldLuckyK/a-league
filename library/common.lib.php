<?php

	header("Access-Control-Allow-Origin: *");
	header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
	header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ini_set('max_execution_time', 3600);
	set_time_limit(0);

	date_default_timezone_set('UTC');

	require_once(__DIR__."/Simple_html_dom.php");

	function callServerAPI($server_address = "http://149.28.175.73:8081/api/game/list_homepage") {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $server_address,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "",
		  CURLOPT_HTTPHEADER => array(
		    "Cache-Control: no-cache",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		   echo $response;
		}
	}

	function sendFixtureToServer($data, $league_id, $server_address = "http://149.28.175.73:8081/api/game/import") {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $server_address,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "league_id=".$league_id."&game_data=" . json_encode($data),
		  CURLOPT_HTTPHEADER => array(
		    "Cache-Control: no-cache",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		   echo $response;
		}
	}

	function sendTeamsToServer($data, $league_id, $server_address = "http://149.28.175.73:8081/api/game/import_team") {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $server_address,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "league_id=".$league_id."&data=" . json_encode($data),
		  CURLOPT_HTTPHEADER => array(
		    "Cache-Control: no-cache",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		   echo $response;
		}
	}

	function sendLadderToServer($data, $server_address = "http://149.28.175.73:8081/api/game/import_ladder") {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $server_address,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "data=" . json_encode($data),
		  CURLOPT_HTTPHEADER => array(
		    "Cache-Control: no-cache",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		   echo $response;
		}
	}

	function log_game_status($game_date, $game_id, $status_name) {
		$logFile = "game_status.log";
		$data = @file_get_contents($logFile);
		if(strpos($data, $status_name) !== false) return;
		$message = "Game ID :: ".$game_id." [ ".$game_date." ] ".$status_name;
		file_put_contents($logFile, date("Y-m-d H:i:s", time()).' '.$message.PHP_EOL, FILE_APPEND | LOCK_EX);
	}

?>