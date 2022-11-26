<?php

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	ini_set('max_execution_time', 3600);
	set_time_limit(0);
	
	require_once(__DIR__."/library/common.lib.php");

	$root = "https://www.afl.com.au/aflrender/get?service=ladder&";

	$seasons = json_decode('[{"name":"NAB AFLW Competition","value":"264","seasons":2017,"rounds":7,"key":"AFLW Competition"},{"name":"Toyota AFL Premiership Season","value":"014","seasons":2012,"rounds":23,"key":"AFL Premiership"}]');

	// Get Latest Data
	function multiRequest() {
		global $seasons, $root;

		$urls = [];
		foreach ($seasons as $id => $d) {
			$urls[] = $root."serviceParamNames=seasonId&site=AFL&params=seasonId:CD_S".date("Y").$d->value;
		}

		return __multi_process_urls($urls);
	}

	function __get_lateset_rounds() {
		global $seasons;

		foreach ($seasons as $id => $season) {
			for($i=$season->seasons; $i<=2018; $i++) {
				for($j=1; $j<=$season->rounds; $j++) {
					$file_name = "afl/ladder_".($season->value)."_".($i)."_".($j).".json";
					if(file_exists($file_name)) continue;
					$round_data = __get_round_data($season->value, $i, $j);
					@file_put_contents($file_name, json_encode($round_data));
				}
			}
		}
	}

	function __multi_process_urls($__urls) {
		$curly = array();
		$mh = curl_multi_init();

		foreach ($__urls as $id => $url) {
	 
		    $curly[$id] = curl_init();
	 
			curl_setopt_array($curly[$id], array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
				  "cache-control: no-cache",
				  "Accept: application/json",
				  "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36"
				),
			));
		 
			curl_multi_add_handle($mh, $curly[$id]);
		}
	 
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while($running > 0);

		$competition_datas = [];
		foreach($curly as $id => $c) {
			$competition_datas[] = __process_competition_data(curl_multi_getcontent($c));
			curl_multi_remove_handle($mh, $c);
		}
	 
		curl_multi_close($mh);
		return $competition_datas;
	}

	function __process_competition_data($data) {
		$json_data = json_decode($data);
		if(!($json_data)) return false;

		$competition = new \stdClass;
		$competition->league_id = 3;
		$competition->competition_ref_id = substr($json_data->competitionId, -3);
		$competition->season_code = substr($json_data->seasonId, -7, 4);
		$competition->round_code = intval(substr($json_data->roundId, -2));
		$ladders_data = $json_data->positions;
		$ladders = [];

		foreach ($ladders_data as $ladder_obj) {
			if(!isset($ladder_obj->teamName->teamId)) continue;
			if(!isset($ladder_obj->nextOpponent->teamId)) continue;
			if(!isset($ladder_obj->thisSeasonRecord->winLossRecord)) continue;

			$ladder = new \stdClass;

			$ladder->team_id = substr($ladder_obj->teamName->teamId, 4);
			$ladder->next_team_id = substr($ladder_obj->nextOpponent->teamId, 4);
			$ladder->ladder_position = $ladder_obj->thisSeasonRecord->ladderPosition;
			$ladder->stats_played = $ladder_obj->gamesPlayed;
			$ladder->stats_wins = $ladder_obj->thisSeasonRecord->winLossRecord->wins;
			$ladder->stats_drawn = $ladder_obj->thisSeasonRecord->winLossRecord->draws;
			$ladder->stats_lost = $ladder_obj->thisSeasonRecord->winLossRecord->losses;
			$ladder->stats_points_for = $ladder_obj->pointsFor;
			$ladder->stats_points_against = $ladder_obj->pointsAgainst;
			$ladder->stats_bonus_points = 0;
			$ladder->stats_points = $ladder_obj->thisSeasonRecord->aggregatePoints;

			$ladders[] = $ladder;
		}

		$competition->ladder = $ladders;

		return $competition;
	}

	function __get_season_data($__id, $__season) {
		global $root;

		$data = @file_get_contents($root."serviceParamNames=seasonId&site=AFL&params=seasonId:CD_S".$__season.$__id);
		
		return __process_competition_data($data);
	}

	function __get_round_data($__id, $__season, $__round) {
		global $root;

		$data = @file_get_contents($root."serviceParamNames=seasonId,roundId&site=AFL&params=seasonId:CD_S".$__season.$__id.",roundId:CD_R".$__season.$__id.sprintf("%02d", $__round));
		
		return __process_competition_data($data);
	}

	function __get_last_round($__competition, $__year) {
		$file_name = "afl_".$__competition.".round";
		if(file_exists($file_name)) {
			if(filemtime($file_name)) {
				if(time() < filemtime($file_name) + 86400) {
					return @file_get_contents($file_name);
				}
			}
		}
		$url = "https://www.afl.com.au/aflrender/get?service=ladder&serviceParamNames=seasonId&site=AFL&params=seasonId:CD_S".$__year.$__competition;
		$data = @file_get_contents($url);
		$round_info = json_decode($data);
		if($round_info) {
			@file_put_contents($file_name, $round_info->roundNumber);
			return $round_info->roundNumber;
		}
		return 1;
	}

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

	function __process_live_data($data) {
		$json_data = json_decode($data);
		if(!($json_data)) return false;

		$competition = new \stdClass;
		$competition->league_id = 3;
		$competition->competition_ref_id = substr($json_data->competitionId, -3);
		$competition->season_code = substr($json_data->competitionId, -7, 4);
		$competition->round_code = intval($json_data->roundNumber);
		$ladders_data = $json_data->liveLadderPositions;
		$ladders = [];

		foreach ($ladders_data as $ladder_obj) {
			if(!isset($ladder_obj->teamName)) continue;
			if(!isset($ladder_obj->thisSeasonRecord->winLossRecord)) continue;

			$ladder = new \stdClass;

			$ladder->team_id = substr($ladder_obj->teamId, 4);
			$ladder->next_team_id = 0;
			$ladder->ladder_position = $ladder_obj->thisSeasonRecord->ladderPosition;
			$ladder->stats_played = $ladder_obj->gamesPlayed;
			$ladder->stats_wins = $ladder_obj->thisSeasonRecord->winLossRecord->wins;
			$ladder->stats_drawn = $ladder_obj->thisSeasonRecord->winLossRecord->draws;
			$ladder->stats_lost = $ladder_obj->thisSeasonRecord->winLossRecord->losses;
			$ladder->stats_points_for = $ladder_obj->pointsFor;
			$ladder->stats_points_against = $ladder_obj->pointsAgainst;
			$ladder->stats_bonus_points = 0;
			$ladder->stats_points = $ladder_obj->thisSeasonRecord->aggregatePoints;

			$ladders[] = $ladder;
		}

		$competition->ladder = $ladders;

		return $competition;
	}

while (true) {
	if(@file_get_contents("afl_live.status")) {
		$competition = [];
		$data = __get_live_ladder("014", date("Y"), __get_last_round("014", date("Y")), __get_token());
		$competition[] = __process_live_data($data);
		$data = __get_live_ladder("264", date("Y"), __get_last_round("264", date("Y")), __get_token());
		$competition[] = __process_live_data($data);
	} else {
		$competition =  multiRequest();
	}

	sendLadderToServer($competition);

	sleep(3);
	
	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if(!$game_status) {
		sleep(60);
		continue;
	}
}
?>