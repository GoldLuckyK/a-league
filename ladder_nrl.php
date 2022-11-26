<?php

	require_once(__DIR__."/library/common.lib.php");

	$root = "https://www.nrl.com/ladder/data?";

	$competitions = json_decode('[{"name":"Telstra Premiership","value":111,"seasons":1998,"key":"nrl-premiership"},{"name":"Holden Women\'s Premiership","value":161,"seasons":2018,"key":"nrl-womens-premiership"},{"name":"Intrust Super Premiership","value":113,"seasons":2014,"key":"intrust-super-premiership"},{"name":"Intrust Super Cup","value":114,"seasons":2014,"key":"intrust-super-cup"}]');

	// Get Latest Data
	function multiRequest() {
		global $competitions, $root;

		$urls = [];
		foreach ($competitions as $id => $d) {
			$urls[] = $root."competition=".$d->value;
		}

		return __multi_process_urls($urls);
	}

	function __get_lateset_rounds() {
		global $competitions;

		$rounds = json_decode(@file_get_contents("nrl_round.json"));
		$data = [];
		foreach ($rounds as $round) {
			for($i=1; $i<=$round->round; $i++) {
				$file_name = "nrl/ladder_".($round->id)."_".($round->season)."_".($i).".json";
				if(file_exists($file_name)) continue;
				$round_data = __get_round_data($round->id, $round->season, $i);
				@file_put_contents($file_name, json_encode($round_data));
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
		
		$competition = new \stdClass;
		$competition->id = $json_data->selectedCompetitionId;
		$competition->season = $json_data->selectedSeasonId;
		$competition->round = $json_data->selectedRoundId;
		$ladders = $json_data->ladder;
		/*
		$ladder = [];
		foreach ($ladders as $key => $value) {
			$team_obj = new \stdClass;
			if(isset($value->teamNickName)) $team_obj->name = $value->teamNickName;
			if(isset($value->theme->key)) $team_obj->key = $value->theme->key;
			$team_obj->stats = $value->stats;
			$team_obj->next = $value->nextTeam;
			$ladder[] = $team_obj;
		}
		$competition->ladder = $ladder;
		*/
		$competition->ladder = $ladders;

		return $competition;
	}

	function __get_competition_data($__id) {
		global $root;

		$data = @file_get_contents($root."competition=".$__id);
		
		return __process_competition_data($data);
	}

	function __get_season_data($__id, $__season) {
		global $root;

		$data = @file_get_contents($root."competition=".$__id."&season=".$__season);
		
		return __process_competition_data($data);
	}

	function __get_round_data($__id, $__season, $__round) {
		global $root;

		$data = @file_get_contents($root."competition=".$__id."&season=".$__season."&round=".$__round);
		
		return __process_competition_data($data);
	}

	function __get_team_id($ladder_arr, $team_nickName) {
		foreach ($ladder_arr as $ladder_obj) {
			if($ladder_obj->teamNickName == $team_nickName) {
				if(isset($ladder_obj->next->teamId))
					return $ladder_obj->next->teamId;
			}
		}
		return false;
	}


	function __reprocess_competition_data($data) {
		$competitions = [];
		
		foreach ($data as $json_data) {
			$competition = new \stdClass;
			$competition->league_id = 1;
			$competition->competition_ref_id = $json_data->id;
			$competition->season_code = $json_data->season;
			$competition->round_code = $json_data->round;
			
			$ladders = [];

			foreach ($json_data->ladder as $ladder_obj) {
				$ladder = new \stdClass;

				$ladder->team_id = $ladder_obj->teamNickName;
				$ladder->next_team_id = (isset($ladder_obj->next->nickName)?$ladder_obj->next->nickName:0);
				$ladder->ladder_position = count($ladders) + 1;
				$ladder->stats_played = $ladder_obj->stats->played;
				$ladder->stats_wins = $ladder_obj->stats->wins;
				$ladder->stats_drawn = $ladder_obj->stats->drawn;
				$ladder->stats_lost = $ladder_obj->stats->lost;
				$fld_name = "points for";
				$ladder->stats_points_for = $ladder_obj->stats->$fld_name;
				$fld_name = "points against";
				$ladder->stats_points_against = $ladder_obj->stats->$fld_name;
				$ladder->stats_bonus_points = 0;
				$ladder->stats_points = $ladder_obj->stats->points;

				if($json_data->id == 113 && $ladder->team_id == "Magpies") {
					if(isset($ladder_obj->theme->key) && $ladder_obj->theme->key == "western-suburbs-magpies") {
						$ladder->team_id = 500125;
					} else if(isset($ladder_obj->theme->key) && $ladder_obj->theme->key == "wentworthville") {
						$ladder->team_id = 500130;
					}
				}

				if($json_data->id == 114 && $ladder->team_id == "Seagulls") {
					if(isset($ladder_obj->theme->key) && $ladder_obj->theme->key == "wynnum-seagulls") {
						$ladder->team_id = 500074;
					} else if(isset($ladder_obj->theme->key) && $ladder_obj->theme->key == "tweed-heads-seagulls") {
						$ladder->team_id = 500075;
					}
				}

				$ladders[] = $ladder;
			}

			$competition->ladder = $ladders;
			$competitions[] = $competition;
		}

		return $competitions;
	}

while (true) {
	$competition =  multiRequest();
	$competitions = __reprocess_competition_data($competition);
		
	sendLadderToServer($competitions);

	sleep(3);
	
	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if(!$game_status) {
		sleep(60);
		continue;
	}
}
?>