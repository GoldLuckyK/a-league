<?php

	require_once(__DIR__."/library/common.lib.php");

	$target = "https://www.a-league.com.au/ladder";

	function __process_last_5($data) {
		$arr_data = [];
		$data = explode("last5-result--", $data);
		foreach ($data as $key => $value) {
			if($key) {
				$arr_data[] = __get_until_values($value, '"');
			}
		}
		return $arr_data;
	}

	function __process_team_data($data) {
		$team_obj = new \stdClass;

		$insert_check = false;
		$data = explode("ladder-table__", $data);

		foreach ($data as $key => $value) {
			if($key) {
				$key_value = __get_until_values($value, '"');
				$temp_value = explode(" ", $key_value);
				$key_str = $temp_value[0];
				if($key_str == "played") $insert_check = true;
				if($insert_check) {
					$key_data = __get_values($value, ">", "</td>");
					if($key_str == "last5")
						$team_obj->$key_str = __process_last_5(trim($key_data));
					else
						$team_obj->$key_str = trim($key_data);
				}
			}
		}
		return $team_obj;
	}

	function __process_league_data($data) {
		$data = str_replace("ladder-table__teamlogo", "teamlogo", $data);
		$data = explode("ladder-table__team", $data);
		$arr_data = [];

		foreach ($data as $key => $value) {
			if($key) {
				$proc_data = __get_values($value, ">", "</tr>");
				$team_obj = __process_team_data($proc_data);				
				$team_obj->name = trim(__get_values($value, "<span>", "</span>"));
				$team_obj->logo = __get_values($value, '<img src="', '"');
				$arr_data[] = $team_obj;
			}
		}
		return $arr_data;
	}

	function get_ladder_data($__url) {

		$data = @file_get_contents($__url);
		$data = explode("ladder-table__league", $data);
		$arr_data = [];

		foreach ($data as $key => $value) {
			if($key) {
				$league_obj = new \stdClass;
				$league_obj->name = __get_values($value, "hyundai-a-league-", '"');
				$proc_data = __get_values($value, "<table", "</table>");
				$league_obj->teams = __process_league_data($proc_data);
				$arr_data[] = $league_obj;
			}
		}

		return $arr_data;
	}

	function __process_competition_data($data) {
		$competitions = [];
		foreach ($data as $json_data) {
			$competition = new \stdClass;
			$competition->league_id = 5;
			$competition->competition_ref_id = 214;
			//$competition->season_code = substr($json_data->name, 0, 4);
			$competition->season_code = str_replace("-", "/", $json_data->name);
			$competition->round_code = 0;
			$ladders_data = $json_data->teams;
			$ladders = [];

			foreach ($ladders_data as $ladder_obj) {
				$ladder = new \stdClass;

				$ladder->team_id = $ladder_obj->name;
				$ladder->next_team_id = 0;
				$ladder->ladder_position = count($ladders) + 1;
				$ladder->stats_played = $ladder_obj->played;
				$ladder->stats_wins = $ladder_obj->won;
				$ladder->stats_drawn = $ladder_obj->draw;
				$ladder->stats_lost = $ladder_obj->lose;
				$fld_name = "goals-for";
				$ladder->stats_points_for = $ladder_obj->$fld_name;
				$fld_name = "goals-against";
				$ladder->stats_points_against = $ladder_obj->$fld_name;
				$ladder->stats_bonus_points = 0;
				$ladder->stats_points = $ladder_obj->points;

				$ladders[] = $ladder;
			}

			$competition->ladder = $ladders;
			$competitions[] = $competition;

			break;
		}

		return $competitions;
	}

while (true) {
	$result = get_ladder_data($target);
	$competitions = __process_competition_data($result);

	sendLadderToServer($competitions);

	sleep(3);
	
	$game_status = @file_get_contents("/var/www/MatesPicks/public/banter/game_status");
	if(!$game_status) {
		sleep(60);
		continue;
	}
}
?>