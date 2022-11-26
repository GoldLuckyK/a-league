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

	$start_time = microtime_float();

	$APP_KEY = "pYrZwUeW8ANSFJlJ";
	$SESSION_TOKEN = "";
	$USER_NAME = "JENNYBET53";
	$USER_PASS = "Britta11";

	function microtime_float()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	function __get_values($__str_data, $__pre_pattern, $__post_pattern) {
	    if(!($__pre_pattern)) return false;
	    if(!($__post_pattern)) return false;
	    $__pos = strpos($__str_data, $__pre_pattern);
	    if($__pos !== false){
	        $__str_data = substr($__str_data, $__pos + strlen($__pre_pattern));
	        $__pos = strpos($__str_data, $__post_pattern);
	        if($__pos !== false) {
	            return substr($__str_data, 0, $__pos);
	        } else 
	            return false;
	    } else
	    return false;
	}

	function __get_until_values($__str_data, $__post_pattern){
	    if(!($__post_pattern)) return false;
	    $__pos = strpos($__str_data, $__post_pattern);
	    if($__pos !== false)
	        return substr($__str_data, 0, $__pos);
	    return false;    
	}

	function __get_after_values($__str_data, $__post_pattern){
	    if(!($__post_pattern)) return false;
	    $__pos = strpos($__str_data, $__post_pattern);
	    if($__pos !== false)
	        return substr($__str_data, $__pos + strlen($__post_pattern));
	    return false;
	}

	function __get_last_values($__str_data, $__post_pattern){
	    if(!($__post_pattern)) return false;
	    $__pos = strrpos($__str_data, $__post_pattern);
	    if($__pos !== false)
	        return substr($__str_data, $__pos + strlen($__post_pattern));
	    return false;
	}

	function betfair_login_session($user_name, $user_pass) {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://identitysso.betfair.com.au/api/login",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "username=".$user_name."&password=".$user_pass,
		  CURLOPT_HTTPHEADER => array(
		    "Accept: application/json",
		    "X-Application: GTX_Auto",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		    return false;
		} else {
		    $resp = json_decode($response);
		    if($resp)
		        if(isset($resp->token)) {
		            return $resp->token;
		        }
		}

		return false;
	}

	function generate_session($user_name, $user_pass) {
		$file_name = "betfair_api_session.token.".$user_name;

		if(@file_exists($file_name)) {
			if(filemtime($file_name) + 3600 > time())
				return @file_get_contents($file_name);
		}
		$user_token = betfair_login_session($user_name, $user_pass);
		if($user_token) {
			@file_put_contents($file_name, $user_token);
			@chmod($file_name, 0777);
			return $user_token;
		}
		return false;
	}

	function sportsApingRequest($appKey, $sessionToken, $operation, $params)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, "https://api.betfair.com/exchange/betting/json-rpc/v1");
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'X-Application: ' . $appKey,
	        'X-Authentication: ' . $sessionToken,
	        'Accept: application/json',
	        'Content-Type: application/json'
	    ));

	    $postData =
	        '[{ "jsonrpc": "2.0", "method": "SportsAPING/v1.0/' . $operation . '", "params" :' . $params . ', "id": 1}]';

	    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

	    $resp = curl_exec($ch);
	    $response = json_decode($resp);

	    curl_close($ch);

	    if (isset($response[0]->error)) {
	    	return false;
	    } else {
	        return $response;
	    }
	}

	function getSoccerMarket($appKey, $sessionToken, $str_date)
	{

	    $params = '{"filter":{"eventTypeIds":["61420"],
	              "marketTypeCodes":["MATCH_ODDS"],
	              "marketStartTime":{"from":"' . date('c', strtotime($str_date)) . '"}},
	              "sort":"FIRST_TO_START",
	              "maxResults":"999",
	              "marketProjection":["EVENT", "MARKET_START_TIME", "RUNNER_DESCRIPTION", "RUNNER_METADATA"]}';

	    $jsonResponse = sportsApingRequest($appKey, $sessionToken, 'listMarketCatalogue', $params);

	    if(!$jsonResponse) return false;

	    $result = $jsonResponse[0]->result;

	    $meetings = [];

	    foreach ($result as $marketNode) {
	    	if(isset($marketNode->event)) {
		    	$meeting_obj = new \stdClass;
		    	$meeting_obj->id = $marketNode->event->id;
		    	$meeting_obj->name = $marketNode->event->name;
		    	$meeting_obj->openDate = $marketNode->event->openDate;
		    	$meeting_obj->marketId = $marketNode->marketId;
		    	$meeting_obj->marketName = $marketNode->marketName;

			    $runners = [];
			    foreach ($marketNode->runners as $runner) {
					$runner_info = new \stdClass;
					$runner_info->selectionId = $runner->selectionId;
					$runner_info->runnerName = $runner->runnerName;
					$runners[] = $runner_info;
				}

			    $meeting_obj->runners = $runners;
			    $meetings[] = $meeting_obj;
		    }
	    }

	    return $meetings;
	}

	function generateSoccerMarket($appKey, $sessionToken, $str_date)
	{
		$file_name = "betfair_soccer_api_meetings.".date("Ymd", strtotime($str_date));

		if(@file_exists($file_name)) {
			$meeting_data = json_decode(@file_get_contents($file_name));
			return $meeting_data;
		}
		$meeting_data = getSoccerMarket($appKey, $sessionToken, $str_date);

		if($meeting_data) {
			@file_put_contents($file_name, json_encode($meeting_data));
			@chmod($file_name, 0777);
			return $meeting_data;
		}
		return false;
	}

	function calc_current_diff_time($minutes = 0)
	{
		return date("Y-m-d", time() + $minutes * 60)."T".date("H:i:s.000", time() + $minutes * 60)."Z";
	}

	function getMarketPrices($appKey, $sessionToken, $market_ids)
	{
		$market_param = '';
		foreach ($market_ids as $key => $market_id) {
			if($key) $market_param .= ", ";
			$market_param .= '"'.$market_id.'"';
		}

	    $params = '{"marketIds":['.$market_param.'],"priceProjection":{"priceData":["EX_ALL_OFFERS", "SP_AVAILABLE"]}}';

	    $jsonResponse = sportsApingRequest($appKey, $sessionToken, 'listMarketBook', $params);

	    if($jsonResponse)
	    	return $jsonResponse[0]->result;
	    return false;
	}

	function generateOddsSP($appKey, $sessionToken, $marketData, $minutes = 0)
	{
		$curr_time = calc_current_diff_time(-1 * $minutes);
		$next_time = calc_current_diff_time($minutes);

		$arrMarkets = [];

		foreach ($marketData as $market_info) {
		//	if(($market_info->openDate > $curr_time) && ($market_info->openDate < $next_time)){
				$arrMarkets[] = $market_info->marketId;
		//	}
		}

		if(!($arrMarkets)) return [];
		if(count($arrMarkets) == 0) return [];

		$result = [];
		$arrMarket_id = [];
		for($i = 0; $i < count($arrMarkets); $i++) {
			if(($i) && ($i % 10 == 0)) {
				$result = array_merge($result, getMarketPrices($appKey, $sessionToken, $arrMarket_id));
				$arrMarket_id = [];
			}
			$arrMarket_id[] = $arrMarkets[$i];
		}
		$result = array_merge($result, getMarketPrices($appKey, $sessionToken, $arrMarket_id));

		return $result;
	}

	function mergeRunners($runners, $odds)
	{
		foreach ($runners as $key => $runner_info) {
			foreach ($odds as $odds_info) {
				if($runner_info->selectionId == $odds_info->selectionId) {
					if(isset($odds_info->status)) $runners[$key]->status = $odds_info->status;
					if(isset($odds_info->ex) && isset($odds_info->ex->availableToBack) && count($odds_info->ex->availableToBack) && isset($odds_info->ex->availableToBack[0]->price))
						$runners[$key]->odds = $odds_info->ex->availableToBack[0]->price;
					if(isset($odds_info->ex->availableToLay[0]->price))
						$runners[$key]->lay = $odds_info->ex->availableToLay[0]->price;
					if(isset($odds_info->sp) && isset($odds_info->sp->actualSP) && ($odds_info->sp->actualSP != "NaN"))
						$runners[$key]->sp = $odds_info->sp->actualSP;
				}
			}
		}
		return $runners;
	}

	function mergeOddsSP($marketData, $oddsData)
	{
		$arrMarkets = [];
		foreach ($marketData as $market_info) {
			$market_id = $market_info->marketId;
			foreach ($oddsData as $market_data) {
				if($market_id == $market_data->marketId) {
					$market_info->runners = mergeRunners($market_info->runners, $market_data->runners);
					$arrMarkets[] = $market_info;
				}
			}
		}
		return $arrMarkets;
	}	

	function send_odds_to_server($meetings)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL,"http://racingjapan.com/gtx/scrapper/betfair_soccer_api_odds_import");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
		            "&data=".json_encode($meetings));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec($ch);
		curl_close ($ch);

		return $server_output;
	}

	$SESSION_TOKEN = generate_session($USER_NAME, $USER_PASS);

	$marketData = generateSoccerMarket($APP_KEY, $SESSION_TOKEN, date("Y-m-d"));

	$result = generateOddsSP($APP_KEY, $SESSION_TOKEN, $marketData, 10);

	$result = mergeOddsSP($marketData, $result);

	echo json_encode($result);

//	echo send_odds_to_server($result);

//	echo "Duration: ".round(microtime_float() - $start_time, 3)." seconds";

?>