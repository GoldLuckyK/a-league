<?php

	function __get_token() {
		$file_name = "afl.token";
		if(file_exists($file_name)) {
			if(filemtime($file_name) + 30 > time()) {
				return @file_get_contents($file_name);
			}
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.afl.com.au/api/cfs/afl/WMCTok",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if($response) {
			$response = json_decode($response);
			$token = $response->token;
			@file_put_contents($file_name, $token);

			return $token;
		}

		return false;
	}

	function __get_live_score($competition, $season, $round, $game_index, $token) {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.afl.com.au/api/cfs/afl/matchScore/CD_M".sprintf("$season$competition%02d%02d", $round, $game_index),
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "x-media-mis-token: ".$token
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		return $response;
	}

	function __get_live_ladder($competition, $season, $round, $token) {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.afl.com.au/api/cfs/afl/liveLadder/round/CD_R".sprintf("$season$competition%02d", $round),
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "x-media-mis-token: ".$token
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		return $response;
	}
	
	$token = __get_token();
	//$data = __get_live_score("014", "2019", 1, 2, $token);
	$data = __get_live_ladder("014", "2019", 1, $token);
	echo($data);
