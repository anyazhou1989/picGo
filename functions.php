<?php

use Utils\Config;

/**
 * 数组转换为string字符串文本
 * @param $arr
 * @param string $tab
 * @param bool|FALSE $bIntKey
 * @return string
 */
function array_convert($arr, $tab = '', $bIntKey = true) {
    $str = '';
    $str .= "[\n";
    $_tab = '  ';

    foreach ($arr as $key => $val) {
        $str .= $tab . $_tab;
        if ($bIntKey && is_integer($key)) {
            $str .= $key . " => ";
        } else {
            $str .= "'" . singleslash($key) . "' => ";
        }

        if (is_array($val)) {
            $str .= array_convert($val, $tab . $_tab, $bIntKey);
        } else if (is_object($val)) {
            if (json_encode($val) === '{}') {
                $str .= '[]';
            }
        } else if (!is_string($val) && is_numeric($val)) {
            $str .= $val;
        } else if (is_bool($val)) {
            $str .= $val ? 'true' : 'false';
        } else {
            $str .= "'" . singleslash($val) . "'";
        }
        $str .= ",\n";
    }
    $str .= $tab . "]";

    return $str;
}

function array_to_lua($arr, $tab = '') {
    $str = "{\n";
    $_tab = '    ';

    $isList = array_values($arr) === $arr;

    foreach ($arr as $key => $val) {
        $str .= $tab . $_tab;

        if (!$isList) {
            $str .= "['" . singleslash($key) . "'] = ";
        }

        if (is_array($val)) {
            $str .= array_to_lua($val, $tab . $_tab);
        } else if (is_object($val)) {
            if (json_encode($val) === '{}') {
                $str .= '{}';
            }
        } else if (!is_string($val) && is_numeric($val)) {
            $str .= $val;
        } else if (is_bool($val)) {
            $str .= $val ? 'true' : 'false';
        } else {
            $str .= "'" . singleslash($val) . "'";
        }
        $str .= ",\n";
    }
    $str .= $tab . "}";

    return $str;
}

/**
 * @param $str
 * @return mixed
 */
function singleslash($str) {
    return str_replace(["\\", "'"], ["\\\\", "\\'"], $str);
}

function json_pretty($str) {
    $json = json_decode($str);
    return json_encode($json, JSON_PRETTY_PRINT);
}

function is_assoc(array $array) {
    return !isset($array[0]);
}

/**
 * Convert unix timestamp(int) to mysql timestamp(string).
 *
 * @param int $unix_timestamp
 * @return string
 */
function ts_unix2mysql($unix_timestamp) {
    return date('Y-m-d H:i:s', $unix_timestamp); // TODO gmdate
}

/**
 * Convert mysql timestamp(string) to unix timestamp(int).
 *
 * @param $mysql_timestamp
 * @return int
 */
function ts_mysql2unix($mysql_timestamp) {
    return strtotime($mysql_timestamp);
}

function num_zero_fill($num, $pad_len = 2) {
    return str_pad($num, $pad_len, '0', STR_PAD_LEFT);
}

function movement_duration($startX, $startY, $endX, $endY, $speed = 1.0) {
    return sqrt((pow($startX - $endX, 2) + pow($startY - $endY, 2))) / $speed;
}

function str_startswith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function str_endswith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function str_icontains($haystack, $needle) {
    return stripos($haystack, $needle) !== false;
}

function get_class_basename($object) {
    if (!is_object($object)) {
        return null;
    }

    $fullQualifiedClassName = explode('\\', get_class($object));
    return $fullQualifiedClassName[count($fullQualifiedClassName) - 1];
}

function array_deep_get(array $arr, $path, $default = null) {
    $path = rtrim($path, '.'); // remove last char if it is '.'
    $pieces = explode('.', $path);
    foreach ($pieces as $piece) {
        if (!is_array($arr) || !isset($arr[$piece])) {
            // not found
            return $default;
        }
        $arr = $arr[$piece];
    }
    return $arr;
}

function array_deep_set(array &$arr, $path, $val) {
    $path = rtrim($path, '.'); // remove last char if it is '.'
    $pieces = explode('.', $path);
    for ($i = 0, $n = count($pieces) - 1; $i < $n; $i++) {
        $arr = &$arr[$pieces[$i]];
    }
    $arr[$pieces[$n]] = $val;
}

function array_deep_del(array &$arr, $path) {
    $path = rtrim($path, '.'); // remove last char if it is '.'
    $pieces = explode('.', $path);
    for ($i = 0, $n = count($pieces) - 1; $i < $n; $i++) {
        $arr = &$arr[$pieces[$i]];
    }
    unset($arr[$pieces[$n]]);
}

/**
 * 对查询出来的rows进行排序，返回排序结果
 * @param $rows
 * @param $orderRules
 */
function sort_rows(&$rows, $orderRules) {
    if (!$rows) {
        return;
    }

    // 非法列校验
    $checkRow = current($rows);
    foreach ($orderRules as $colName => $rule) {
        if (!isset($checkRow[$colName])) {
            exit("要排序的列必须被包含在row中！");
        }
    }

    // 整理排序规则
    $params = [];
    foreach ($orderRules as $colName => $rule) {
        $tmpArr = [];
        foreach ($rows as $row) {
            $tmpArr[] = $row[$colName];
        }
        $params[] = $tmpArr;
        $params[] = $rule;
    }
    $params[] = $rows;

    // 排序
    array_multisort(...$params);
    $rows = end($params);
}

function time_before_today($timestamp) {
    static $todayBeginsAt = null;
    if ($todayBeginsAt === null) {
        $todayBeginsAt = strtotime(gmdate("Y-m-d\T00:00:00\Z")); // FIXME
    }

    if ($timestamp < $todayBeginsAt) {
        return true;
    }
    return false;
}

function in_between($min, $max, $in) {
    if ($min <= $in && $max >= $in) {
        return true;
    }

    return false;
}

function random_string($length) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),
        0, $length);
}

function check_int(...$vals) {
    foreach ($vals as $val) {
        if ($val === '') {
            continue;
        }
        if (!isset($val) || !is_numeric($val) || strpos('.', $val) !== false) {
            throw new Exception('"' . $val . '" is not a int');
        }
    }
}

function check_quest($rows, $idToInternalId, $isAllianceQuest = false) {
    $typeToConfigName = [
        'build_fix' => 'building_type',
        'build' => 'building_type',
        'build_up' => 'building_type',
        'activity_build_up' => 'building_type',
        'start_to_build' => 'building_type',
        'resource_combine' => 'resource_sub_type',
        'start_to_resource_combine' => 'resource_sub_type',
        'train' => 'unit_type',
        'activity_train' => 'unit_type',
        'train_more' => 'unit_type',
        'start_to_train' => 'unit_type',
        'kill_troops' => 'unit_type',
        'cure' => 'unit_type',
        'research' => 'research_group',
        'start_to_research' => 'research_group',
        'gather' => 'resource_main',
        'activity_gather' => 'resource_main',
        'alliance_resource_gather' => 'resource_main',
        'plunder' => 'resource_main',
        'resource_production' => 'resource_main',
        'rural_unlock' => 'rural_slot_group',
        'reclaim' => 'rural_slot_group',
        'rural_exploration' => 'rural_slot_group',
        'talent' => 'lord_talent_group',
        'city_pve' => 'city_pve_main',
        'cure_beast' => 'beast',
        'unlock_beast' => 'beast',
        'intelligence_attack' => 'intelligence_chapter',
        'intel_mainline_pass' => 'intel_mainline_pass',
        'intel_exploration' => 'exploration_level_main',
        'decoration_build' => 'buildings_decoration',
        'survivor_special' => 'survivor',
        'survivor_7day' => 'survivor',
        'soldier_rank_unlock' => 'unit_type',
        'survivor_new' => 'survivor_new',
        'reduce_item' => 'itemlist',
        'reduce_item_repeat' => 'itemlist',
        'npp_get_item' => 'itemlist',
        'intel_event' => 'radar_clue',
        'rural_task' => ['rural_slot_group', 'zone_unlock_clue'],
        'rural_mist' => 'rural_slot_group',
        'plot_wall_fix' => 'rural_slot_group_new',
        'plot_specific_event' => 'zone_unlock_new',
        'plot_land_event' => 'rural_slot_group_new',
        'lord_gem' => 'itemlist',
        'survivor_equip' => 'survivor_equipment',
        'survivor_equip_forge' => 'survivor_equipment',
        'building_production_total' => 'resource_main',
        'alliance_donate_cost' => 'resource_main',
        'alliance_mines_production' => 'resource_main',
        'alliance_mines_num' => 'alliance_mines',
        'roguelike_use' => 'roguelike_item',
        'intel_id_num' => 'radar_clue',
        'carrier_module_level' => 'carrier_base',
        'carrier_star' => 'carrier_base',
        'carrier_score' => 'carrier_base',
        'carrier_pluto_single_day' => 'carrier_base',
        'carrier_pluto_team_day' => 'carrier_base',
        'carrier_pluto_single_level' => 'carrier_base',
        'carrier_pluto_team_time' => 'carrier_base',
        'carrier_any_talent_skill' => 'carrier_base',
        'carrier_all_talent_skill' => 'carrier_base',
        'carrier_skill_num' => 'carrier_base',
        'carrier_appoint_talent_skill' => 'carrier_base',
        'new_period_mission' => 'new_period_mission',
        'upgrade_appointment2_slot_level' => 'survivor_appointment_position',
        'upgrade_appointment2_position_level' => 'survivor_appointment_position',
        'titan_level' => 'behemoth_base',
        'titan_star' => 'behemoth_base',
        'titan_skill_unlock' => 'behemoth_base',
        'titan_skill_level' => 'behemoth_base',
        'pve_story_level' => 'pve_story_level',
        'pve_story_level_star' => 'pve_story_level',
        'plot_wall_fix_new' => 'rural_slot_group_v3',
        'plot_block_clean_new' => 'new_city_trash_cleanup',
        'mainline_intel' => 'mainline_intel',
        'city_resource_npp_num' => 'city_resource_collection_npp',
        'city_resource_npp_times' => 'city_resource_collection_npp',
        'start_city_resource_npp' => 'city_resource_collection_npp',
        'carrier_driving_level' => 'carrier_driving_base',
        'carrier_driving_skill' => 'carrier_driving_base',
        'exploration_level' => 'exploration_level_main',
    ];

    /** @var array $alliance2UserQuest 联盟任务类型和对应的个人任务类型 */
    $alliance2UserQuest = [
        'create_alliance' => 'alliance',
        'alliance_people_num' => 'alliance',
        'alliance_research_num' => '',
        'alliance_resource_gather' => 'gather',
        'alliance_gve_all_and_win' => '',
        'alliance_help_num' => 'alliance_help',
        'aliance_building_num' => '',
        'alliance_gift_num' => '',
        'alliance_power' => '',
        'alliance_donate_times' => 'alliance_donate',
    ];

    foreach ($rows['param'] as $i => $param) {

        $type = $rows['type'][$i];

        if (isset($alliance2UserQuest[$type]) && !$isAllianceQuest) {
            $exStr = '个人任务类型不能填写联盟任务类型，';
            if (!empty($alliance2UserQuest[$type])) {
                $exStr .= $type . '联盟任务对应的个人任务类型为：' . $alliance2UserQuest[$type];
            } else {
                $exStr .= $type . '没有对应的个人任务类型';
            }
            throw new ErrorException($exStr);
        }
        if (!isset($alliance2UserQuest[$type]) && $isAllianceQuest) {
            throw new ErrorException('新增了联盟任务类型' . $type . '请联系后端处理');
        }

        try {
            switch ($type) {
                case 'social_contact':
                case 'alliance':
                case 'bound':
                case 'iap':
                case 'trading':
                case 'fake_fallen_knight':
                case 'set_survivor_garrison':
                case 'chapter_quest_complete':
                case 'rename':
                case 'buy_package_for_coin':
                case 'create_alliance':
                case 'unlock_second_talent':
                case 'div_dialog':
                case 'puzzle':
                case 'second_building_queue':
                    //无参数的
                    break;

                case 'fallen_knight':
                case 'alliance_help':
                case 'activity_alliance_help':
                case 'gve':
                case 'season_boss':
                case 'activity_gve':
                case 'pvp':
                case 'pvp_win':
                case 'lord_level':
                case 'alliance_donate':
                case 'activity_alliance_donate':
                case 'gem_insert':
                case 'talent_point_spend':
                case 'daily_reward_gain':
                case 'dungeon':
                case 'reclaim_num':
                case 'alliance_honer':
                case 'intel_chapter_unlock':
                case 'prosperity':
                case 'teleport':
                case 'alliance_teleport':
                case 'intel_special':
                case 'research_num':
                case 'activity_research_num':
                case 'intel_explore_num':
                case 'activity_intel_explore_num':
                case 'survivor_level_up':
                case 'survivor_skill_up':
                case 'intel_daily_pve':
                case 'intel_daily_dig':
                case 'online_time':
                case 'top_daily_reward_times':
                case 'power_value':
                case 'survivor_equip_research':
                case 'alliance_donate_score':
                case 'alliance_reinforce':
                case 'poison_land_score':
                case 'intel_daily_rally':
                case 'fallen_knight_boss':
                case 'poison_corps':
                case 'poison_chief':
                case 'poison_minor':
                case 'cost_diamond':
                case 'alliance_shop':
                case 'total_login_count':
                case 'miniwonder_win':
                case 'miniwonder_occupy':
                case 'intel_daily':
                case 'intel_item':
                case 'intel_level':
                case 'intel_difficulty_level':
                case 'reduce_endurance':
                case 'alliance_gift':
                case 'time_chest':
                case 'shopping_npc_store':
                case 'plot_invasion_defense':
                case 'equip_score':
                case 'gem_score':
                case 'cost_diamond_for_coin':
                case 'consume_stamina':
                case 'activity_login_num':
                case 'activity_total_charge_diamond':
                case 'alliance_tower':
                case 'alliance_people_num':
                case 'alliance_research_num':
                case 'alliance_gve_all_and_win':
                case 'alliance_help_num':
                case 'kvk_use_treatment_help_ticket_times':
                case 'alliance_power':
                case 'alliance_donate_times':
                case 'red_packet_send':
                case 'red_packet_get':
                case 'lord_equip_power':
                case 'march_num':
                case 'reset_talent':
                case 'prosperity_recover_speed':
                case 'train_capacity':
                case 'gather_speed':
                case 'vip':
                case 'alliance_donate_daily':
                case 'alliance_power_rank':
                case 'alliance_iap':
                case 'friend_num':
                case 'conscription_num':
                case 'dugout_troops_num':
                case 'dugout_time':
                case 'rally_start':
                case 'rally_in':
                case 'rally_damage_total':
                case 'rally_win':
                case 'mobilization_score_num':
                case 'mobilization_task_num':
                case 'wonder_win_times':
                case 'wonder_occupy_time':
                case 'king':
                case 'alliance_craft_join':
                case 'alliance_craft_win':
                case 'alliance_trap':
                case 'alliance_boss_start_rally':
                case 'alliance_boss_damage_value':
                case 'alliance_boss_donate_times':
                case 'march_speed_times_1':
                case 'march_speed_times_2':
                case 'alliance_tower_num':
                case 'alliance_craft_register':
                case 'rally_finish':
                case 'avaleague_bet_num':
                case 'avaleague_bet_win_num':
                case 'suvivor_equip_power':
                case 'use_plasma_core':
                case 'normal_treasure_detect':
                case 'normal_treasure_digged':
                case 'normal_treasure_2v_open_box':
                case 'advance_treasure_detect':
                case 'advance_treasure_digged':
                case 'ava_score':
                case 'ava_win':
                case 'kvk_arm_rank':
                case 'kvk_battle_rank':
                case 'season_climb_personal_score':
                case 'season_climb_personal_rank':
                case 'season_climb_alliance_score':
                case 'season_climb_alliance_rank':
                case 'fittest_total_rank':
                case 'fittest_we_server_group_total_rank':
                case 'migo_info_points':
                case 'abyss_personal_score':
                case 'abyss_personal_rank':
                case 'feature_score':
                case 'crusade_boss_time':
                case 'crusade_boss_max_damage':
                case 'crusade_boss_cumulative_damage':
                case 'crusade_boss_max_score':
                case 'crusade_boss_cumulative_score':
                case 'roguelike_resurrection':
                case 'roguelike_receive_heart':
                case 'gather_times':
                case 'arena_challenge':
                case 'reset_reward':
                case 'kingdom_event_personal_quest_get_reward':
                case 'titan_boss_attack_times':
                case 'titan_boss_once_damage':
                case 'titan_boss_today_damage':
                case 'black_titan_boss_today_damage':
                case 'titan_boss_donation_times':
                case 'titan_boss_titan_power_up':
                case 'local_world_coin_num':
                case 'activity_airship_protection_personal':
                case 'activity_airship_protection_alliance':
                case 'activity_airship_protection_rank':
                case 'activity_airship_protection_bossharmnumbere':
                case 'remove_building':
                case 'change_avatar':
                case 'buy_newfestival_pass_1':
                case 'buy_newfestival_pass_2':
                case 'daily_task_score':
                case 'black_titan_boss_damage':
                case 'black_world_escort':
                case 'change_achievement_num':
                case 'chat_friend_num':
                case 'daily_login':
                case 'special_relocate':
                case 'hero_battle_atk_num':
                case 'sheep_pass_level_stage_times':
                case 'sheep_pass_level_stage_share':
                case 'sheep_play_times':
                case 'sheep_use_item_num':
                case 'sheep_eliminate_pattern_num':
                case 'army_special_training':
                case 'energy_engine_draw':
                case 'kill_enemy_reconstruction':
                case 'parkour_level':
                case 'buy_weekcard':
                    //1个数字的
                    [$iNum] = explode('|', $param);
                    check_int($iNum);
                    break;

                case 'pve':
                case 'city_pve':
                case 'survivor_recurit':
                case 'alliance_boss':
                case 'poison_boss':
                case 'poison_boss_clean':
                case 'iap_gain_diamond':
                case 'iap_equal_gain_diamond':
                case 'pve_and_intel':
                case 'activity_pve_and_intel':
                case 'lord_equip_num':
                case 'kvk_arm_stage_score':
                case 'kvk_arm_stage_reward_times':
                case 'kvk_battle_stage_reward_times':
                case 'fittest_stage_score':
                case 'fittest_stage_rank':
                case 'fittest_grade_reward_times':
                case 'alliance_throwdown_personal_score':
                case 'alliance_throwdown_alliance_rank':
                case 'alliance_throwdown_quest_times':
                case 'roguelike_battle_hero':
                case 'roguelike_count_option':
                case 'roguelike_count_itemlist':
                case 'roguelike_chapter_specific':
                case 'zombie_boss_attack':
                case 'finish_mobiliza_task':
                case 'energy_engine_install':
                case 'energy_engine_refine':
                    //2个数字的
                    [$iLevel, $iNum] = explode('|', $param);
                    check_int($iLevel, $iNum);
                    break;
                case 'roguelike_option':
                case 'build_up':
                case 'activity_build_up':
                    //2个参数，1个字符串一个数字
                    [$_, $iNum] = explode('|', $param);
                    check_int($iNum);
                    break;
                case 'intel_type_num':
                    //3个数字的
                    [$iType, $iLevel, $iNum] = explode('|', $param);
                    check_int($iType, $iLevel, $iNum);
                    break;
                case 'intel_special_num':
                case 'beast':
                    //4个数字的
                    [$iLevel, $iStar, $iRank, $iNum] = explode('|', $param);
                    check_int($iLevel, $iStar, $iRank, $iNum);
                    break;
                case 'roguelike_complete_compare_attr':
                    //4个参数，前2个字符串后2个数字
                    [$sType, $sCompare, $iCount, $iNum] = explode('|', $param);
                    check_int($iCount, $iNum);
                    break;
                case 'survivor':
                case 'survivor_7day':
                    //5个数字的
                    [$iRarity, $iLevel, $iRank, $iSkillLevel, $iNum] = explode('|', $param);
                    check_int($iLevel, $iSkillLevel, $iRank, $iRarity, $iNum);
                    break;

                case 'survivor_new':
                    //6个数字的
                    [$iRarity, $iLevel, $iRank, $iRankSmall, $iSkillLevel, $iNum] = explode('|', $param);
                    check_int($iLevel, $iSkillLevel, $iRank, $iRarity, $iNum, $iRankSmall);
                    break;

                case 'intel_exploration':
                case 'build_fix':
                case 'rural_unlock':
                case 'rural_exploration':
                case 'intelligence_attack':
                case 'intel_mainline_pass':
                case 'intel_event':
                case 'rural_mist':
                case 'plot_wall_fix':
                case 'plot_specific_event':
                case 'lord_gem':
                case 'survivor_equip':
                case 'survivor_equip_forge':
                case 'new_period_mission':
                case 'pve_story_level':
                case 'plot_wall_fix_new':
                case 'start_city_resource_npp':
                    // 只有一个类型的参数
                    $sType = $param;
                    check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;

                case 'rural_task':
                    // 只有2个类型的参数
                    $_typeList = explode('|', $param);
                    if (count($_typeList) != 2) {
                        throw new Exception('必须有2个配置表的id');
                    }
                    foreach ($_typeList as $_idx => $_type) {
                        check_string_id($typeToConfigName[$type][$_idx], $_type, $idToInternalId);
                    }
                    break;

                case 'unlock_beast':
                    //一个类型的
                    [$sType] = explode('|', $param);
                    check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;

                case 'forge':
                case 'survivor_equip_make':
                    //2个数字, 第一个可以为空
                    [$iQuality, $iNum] = explode('|', $param);
                    if ($iQuality) {
                        check_int($iQuality);
                    }
                    check_int($iNum);
                    break;

                case 'resource_production':
                case 'gather':
                case 'activity_gather':
                case 'plunder':
                case 'cure_beast':
                case 'decoration_build':
                case 'soldier_rank_unlock':
                case 'alliance_resource_gather':
                case 'building_production_total':
                case 'alliance_donate_cost':
                case 'alliance_mines_production':
                case 'alliance_mines_num':
                case 'social_share':
                case 'roguelike_use':
                case 'intel_id_num':
                case 'upgrade_appointment2_position_level':
                case 'upgrade_appointment2_slot_level':
                case 'titan_level':
                case 'titan_star':
                case 'titan_skill_unlock':
                case 'pve_story_level_star':
                case 'city_resource_npp_num':
                case 'city_resource_npp_times':
                    //第一个类型接1个数字的，类型可为空
                    [$sType, $iNum] = explode('|', $param);
                    check_int($iNum);
                    $sType && check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;
                case 'titan_skill_level':
                    //第一个类型接1个数字的，类型可为空
                    [$sType, $iNum, $iNum2] = explode('|', $param);
                    check_int($iNum, $iNum2);
                    $sType && check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;

                case 'research':
                case 'talent':
                case 'start_to_research':
                case 'carrier_module_level':
                case 'carrier_star':
                case 'carrier_score':
                case 'carrier_pluto_single_day':
                case 'carrier_pluto_team_day':
                case 'carrier_pluto_single_level':
                case 'exploration_level':
                    //第一个类型接1个数字的，类型不能为空
                    [$sType, $iNum] = explode('|', $param);
                    check_int($iNum);
                    check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;

                case 'resource_combine':
                case 'train':
                case 'activity_train':
                case 'train_more':
                case 'cure':
                case 'activity_cure':
                case 'kill_troops':
                case 'get_beast_equipment':
                case 'start_to_train':
                case 'start_to_resource_combine':
                    //第一个类型接2个数字的，类型可为空
                    [$sType, $iLevel, $iNum] = explode('|', $param);
                    check_int($iLevel, $iNum);
                    $sType && check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;

                case 'build':

                case 'start_to_build':
                case 'plot_land_event':
                case 'carrier_pluto_team_time':
                    //第一个类型接2个数字的，类型不能为空
                    [$sType, $iLevel, $iNum] = explode('|', $param);
                    check_int($iLevel, $iNum);
                    check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;

                case 'survivor_special':
                case 'carrier_any_talent_skill':
                case 'carrier_all_talent_skill':
                case 'carrier_skill_num':
                    //第一个类型接3个数字的，类型不能为空
                    [$sType, $iLevel, $iRank, $iSkillLevel] = explode('|', $param);
                    check_int($iLevel, $iRank, $iSkillLevel);
                    check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    break;

                case 'speedup':
                case 'speedup_repeat':
                case 'powerup':
                case 'survivor_exp_up':
                case 'aliance_building_num':
                case 'alliance_gift_num':
                case 'gain_help':
                case 'help_member':
                case 'research_type_num':
                case 'survivor_type_skill_sum':
                    //第一个字符串接1个数字的，字符串能为空
                    [, $iNum] = explode('|', $param);
                    check_int($iNum);
                    break;

                case 'reduce_item':
                case 'reduce_item_repeat':
                    // 一个类型接一个字符串接2个数字，类型和第一个数字可为空
                    [$sType, , $iQuality, $iNum] = explode('|', $param);
                    $sType && check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    $iQuality && check_int($iQuality);
                    check_int($iNum);
                    break;

                case 'plot_block_clean':
                case 'recruit_get':
                case 'world_event_gve':
                    // int string int
                    [$iType, , $iNum] = explode('|', $param);
                    check_int($iType, $iNum);
                    break;
                case 'alliance_rank':
                case 'rally_damage_rate':
                    //1个字符串
                    [$sType] = explode('|', $param);
                    break;

                case 'carrier_appoint_talent_skill':
                    // 前2个类型接3个数字的，类型不能为空
                    [$sType, $sTalent, $iQuality, $isExclusive, $iNum] = explode('|', $param);
                    check_int($iQuality, $isExclusive, $iNum);
                    check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    if (!empty($sTalent)) {
                        check_string_id('carrier_talent', $sTalent, $idToInternalId);
                    }
                    break;

                case 'npp_get_item':
                    // 3个类型参数
                    [$sId1, $sId2, $sId3] = explode('|', $param);
                    check_string_id($typeToConfigName[$type], $sId1, $idToInternalId);
                    check_string_id($typeToConfigName[$type], $sId2, $idToInternalId);
                    check_string_id($typeToConfigName[$type], $sId3, $idToInternalId);
                    break;

                case 'recipe_achieve':
                    [, , $iNum] = explode('|', $param);
                    check_int($iNum);
                    break;

                case 'plot_block_clean_new':
                case 'mainline_intel':
                    $sTypeList = explode('|', $param);
                    foreach ($sTypeList as $sType) {
                        $sType && check_string_id($typeToConfigName[$type], $sType, $idToInternalId);
                    }
                    break;
                case 'carrier_driving_level':
                    [$sDrivingId, $sLevel] = explode('|', $param);
                    check_string_id($typeToConfigName[$type], $sDrivingId, $idToInternalId);
                    check_int($sLevel);
                    break;
                case 'carrier_driving_skill':
                    [$sDrivingId, $sSkillGroupId, $num, $sLevel] = explode('|', $param);
                    check_string_id($typeToConfigName[$type], $sDrivingId, $idToInternalId);
                    if (!empty($sSkillGroupId)) {
                        check_string_id('carrier_driving_skill_group_new', $sSkillGroupId, $idToInternalId);
                    }
                    check_int($num);
                    check_int($sLevel);
                    break;
                default:
                    throw new Exception('error quest type "' . $type . '"');
                    break;
            }
        } catch (Exception $ex) {
            throw new Exception('任务参数检测失败: ' . $ex->getMessage() . ' === row: ' . $i . ' ===' . ' type=' . $type . ' param ' . json_encode($param));
        }
    }
}

/**
 * 给前端的格式转为string
 * @param $intBetween
 * @return string
 */
function int_between_to_string($intBetween) {
    if (!is_array($intBetween)) {
        return '';
    }
    $strArr = [];
    foreach ($intBetween as $between) {
        $strArr[] = implode('|', $between);
    }

    return implode(',', $strArr);
}

function check_string_id($configName, $id, $idToInternalId) {
    if (!isset($idToInternalId[$configName][$id])) {
        throw new Exception('"' . $id . '" is not in ' . $configName);
    }
}

function get_client_ip() {
    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        // haproxy will pass in this env var
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
        $ip = $_SERVER['HTTP_CLIENTIP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_CLIENTIP')) {
        $ip = getenv('HTTP_CLIENTIP');
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = '127.0.0.1';
    }

    $pos = strpos($ip, ',');
    if ($pos > 0) {
        $ip = substr($ip, 0, $pos);
    }

    return trim($ip);
}

function get_domain() {
    $scheme = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $port = '';
    if ($_SERVER["SERVER_PORT"] != '80') {
        $port = ':' . $_SERVER["SERVER_PORT"];
    }
    $domain = $_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST) . $port;
    return $scheme . $domain;
}

function get_host() {
    $host = $_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
    return $host;
}

/**
 * Get current request context.
 *
 * Client(flash/mobile) will pass in session id(sid) so that we can
 * trace each session's(sid) each request(callee).
 *
 * @return array {'callee':s, 'host':s, 'ip':s, 'seq':i, 'sid':s, 'elapsed':f}
 */
function request_ctx() {
    static $request_ctx = [];
    static $seq = 0; // within a single request

    $request_ctx['seq'] = ++$seq;
    $request_ctx['elapsed'] = 1000 * (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']); // ms

    if (isset($request_ctx['callee'])) {
        return $request_ctx;
    }

    // the following keeps the same within a http request
    $request_ctx['host'] = empty($_SERVER['SERVER_ADDR']) ? 'localhost' : $_SERVER['SERVER_ADDR']; // current web server ip
    $request_ctx['ip'] = get_client_ip();
    $request_ctx['sid'] = empty($_REQUEST['sid']) ? 0 : $_REQUEST['sid']; // client(e,g. flash) passed in session id
    $uri = empty($_SERVER['REQUEST_URI']) ? get_included_files()[0] : $_SERVER['REQUEST_URI'];
    $uri = parse_url($uri, PHP_URL_PATH); // normalize
    $method = empty($_SERVER['REQUEST_METHOD']) ? 'CLI' : $_SERVER['REQUEST_METHOD'];
    $request_ctx['callee'] = $method . '+' . $uri . '+' . dechex(mt_rand());

    return $request_ctx;
}

function array_remove_value(&$arr, $var) {
    foreach ($arr as $key => $value) {
        if ($value == $var)
            unset($arr[$key]);
    }
}


function utf8_filter($data) {
    $str = "";
    for ($n = 0; $n < strlen($data);) {
        $s = substr($data, $n, 1);
        $v = ord($s);
        if ($v >= 127) {
            ++$n;
            $cnt = 0;
            $tmp = $v;
            while ($tmp & 0x80) {
                $tmp = $tmp << 1;
                ++$cnt;
            }
            $x = 0;
            while ($x < $cnt && $n < strlen($data)) {
                $s = substr($data, $n, 1);
                if ((ord($s) & 0xC0) == 0x80) {
                    ++$n;
                    ++$x;
                } else {
                    break;
                }
            }
            if ($x + 1 == $cnt) {
                $str = $str . substr($data, $n - $cnt, $cnt);
            } else {
                while ($n < strlen($data)) {
                    $s = substr($data, $n, 1);
                    if (ord($s) & 0x80) {
                        ++$n;
                    } else {
                        break;
                    }
                }
            }
        } else {
            $str = $str . $s;
            ++$n;
        }
    }
    return $str;
}

/**
 * 检查活动是否存在后端逻辑
 * @param array $rows 配置数据
 * @param array $idToInternalId sId=>iId
 * @param string $activityTypeColName 活动type列名 对应代码中\Model\Activity中的类
 * @cTime 2020-12-17 10:44
 */
function check_activity($rows, $idToInternalId, $activityTypeColName = 'active_type') {
    if (!isset($rows[$activityTypeColName])) {
        throw new ErrorException('找不到活动type列' . $activityTypeColName . '请和后端确认活动type列');
    }

    if (!$rows[$activityTypeColName]) {
        return;
    }

    static $fileResult = [];

    foreach ($rows[$activityTypeColName] as $idx => $fileName) {
        if (!isset($fileResult[$fileName])) {
            $fileResult[$fileName] = file_exists(SERVER_ROOT . '/v3/classes/Model/Activity/' . $fileName . '.php');
        }

        if (!$fileResult[$fileName]) {
            throw new ErrorException('ID[' . $rows['id'][$idx] . ']活动[' . $fileName . ']没有对应的后端逻辑，请检查活动type是否是和后端定好的');
        }
    }
}

/**
 * 检查礼包id长度
 * @param $rows
 * @param $idToInternalId
 * @throws Exception
 * @cTime 2021-04-26 19:34
 */
function check_iap_package_id_length50($rows, $idToInternalId) {
    $lengthLimit = 100;
    foreach ($rows['id'] as $id) {
        if (strlen($id) > $lengthLimit) {
            throw new Exception('礼包ID检测失败，礼包&礼包组ID长度不能超过' . $lengthLimit . '，超长ID：' . $id . '，长度：' . strlen($id));
        }
    }
}


function check_plutosingle_level_info($rows, $idToInternalId) {
    $count = count($rows['is_beast']);
    for ($_i = 0; $_i < $count; $_i++) {
        if (isset($rows['is_beast'][$_i])) {
            if ($rows['is_beast'][$_i]) {
                if ($rows['normal_diff'][$_i] == $rows['beast_diff'][$_i]) {
                    throw new Exception('巨兽关 巨兽难度和普通难度必须不一样|id:' . $rows['id'][$_i]);
                }
            }
        }

    }
}

function check_advance_research_group_target($rows, $checkTarget, $idToInternalId) {
//    if (!isset($rows['research_group_target'])) {
//        throw new Exception('高级科研组相关表必须添加 research_group_target 列');
//    }
//
//    foreach ($rows['research_group_target'] as $tag) {
//        if ($tag != $checkTarget) {
//            throw new Exception('表中所有的 research_group_target 列必须都是' . $checkTarget);
//        }
//    }
}

function check_ava_alliance_match_manual() {
    if (!is_file(TEMP_PHP_PATH . 'ava_alliance_match_manual.php')) {
        return;
    }
    $conf = get_php_config('ava_alliance_match_manual');
    $list = [];
    foreach ($conf['data'] as $_conf) {
        if (in_array($_conf['alliance_id'], $list)) {
            \Utils\Builder::notice('ava_alliance_match_manual.csv错误，配置了相同的联盟');
            die;
        }
        $list[] = $_conf['alliance_id'];
    }
}

function check_sandbox_battle_time() {
    return;
    /*    if (!is_file(TEMP_PHP_PATH . 'dungeons_battle_time.php')) {
            return;
        }
        $conf = require TEMP_PHP_PATH . 'dungeons_battle_time.php';
        if (count($conf['data']) != 5) {
            \Utils\Builder::notice('dungeons_battle_time只能有5个选择时间,你配置了' . count($conf['data']));
            exit(1);
        }*/
}

function check_season_config() {
    if (!is_file(TEMP_PHP_PATH . 'season_climb_coordinate.php')) {
        return;
    }
    $coordConf = get_php_config('season_climb_coordinate');
    $contaConf = get_php_config('season_climb_conta');
    $fogNoNeed = [
        'rank',
        'rank_reward',
        'se_army',
        'se_stamina',
        'tube_num_se',
    ];
    $coordErrorMsg = $contaErrorMsg = $seErrorMsg = $slgErrorMsg = '';
    foreach ($coordConf['data'] as $_k => $_coordConf) {
        $sId = $_coordConf['id'];
        switch ($contaConf['data'][$_coordConf['conta']]['type']) {
            case 'base':
                if (empty($_coordConf['base_num'])) {
                    $coordErrorMsg .= $sId . '--base base_num is empty' . "\n";
                }
                if (!empty($_coordConf['rank'])) {
                    $coordErrorMsg .= $sId . '--base base_num is empty' . "\n";
                }
                break;
            case 'fog':
                if (!empty($_coordConf['rank'])) {
                    $coordErrorMsg .= $sId . '--fog rank not 0' . "\n";
                }
                break;
            case 'small':
                if ($_coordConf['rank'] != 1) {
                    $coordErrorMsg .= $sId . '--small rank not 1' . "\n";
                }
                if (empty($_coordConf['zombie_buff_se'])) {
                    $coordErrorMsg .= $sId . '--zombie_buff_se is empty' . "\n";
                }
                break;
            case 'medium':
            case 'big':
                if ($_coordConf['rank'] != 2) {
                    $coordErrorMsg .= $sId . '--medium or big rank not 2' . "\n";
                }
                if (empty($_coordConf['zombie_buff_se'])) {
                    $coordErrorMsg .= $sId . '--zombie_buff_se is empty' . "\n";
                }
                break;
        }
    }

    if ($coordErrorMsg) {
        $coordErrorMsg = "======season_climb_coordinate.csv error======\n" . $coordErrorMsg;
    }

    foreach ($contaConf['data'] as $_k => $_contaConf) {
        $sId = $_contaConf['id'];
        switch ($_contaConf['type']) {
            case 'fog':
                foreach ($fogNoNeed as $_noNeed) {
                    if (!empty($_contaConf[$_noNeed])) {
                        $contaErrorMsg .= $sId . '--fog不能配置' . $_noNeed . "\n";
                    }
                }
                break;
            case 'small':
            case 'medium':
            case 'big':
                foreach ($fogNoNeed as $_noNeed) {
                    if (empty($_contaConf[$_noNeed])) {
                        $contaErrorMsg .= $sId . '--缺少配置' . $_noNeed . "\n";
                    }
                }
                if ($_contaConf['type'] == 'small') {
                    if ($_contaConf['rank'] != 1) {
                        $contaErrorMsg .= $sId . '--rank 必须是1' . "\n";
                    }
                } elseif ($_contaConf['rank'] != 2) {
                    $contaErrorMsg .= $sId . '--rank 必须是2' . "\n";
                }
                break;
        }
    }
    if ($contaErrorMsg) {
        $contaErrorMsg = "======season_climb_conta.csv error ======\n" . $contaErrorMsg;
    }

    $seConf = get_php_config('season_climb_se');
    $seRewardConf = get_php_config('season_climb_se_reward');
    $slgConf = get_php_config('season_climb_slg');
    $slgRewardConf = get_php_config('season_climb_slg_reward');
    $seGroup = [];
    foreach ($seConf['data'] as $_conf) {
        $seGroup[$_conf['reward_group']] = 1;
    }
    $seRewardGroup = [];
    foreach ($seRewardConf['data'] as $_conf) {
        $seRewardGroup[$_conf['group']] = 1;
    }
    $seDiff = array_diff(array_keys($seGroup), array_keys($seRewardGroup));
    if ($seDiff) {
        $seErrorMsg = "======season_climb_se_reward.csv缺少======" . json_encode($seDiff) . PHP_EOL;
    }
    $slgGroup = [];
    foreach ($slgConf['data'] as $_conf) {
        $slgGroup[$_conf['reward_group']] = 1;
    }
    $slgRewardGroup = [];
    foreach ($slgRewardConf['data'] as $_conf) {
        $slgRewardGroup[$_conf['group']] = 1;
    }
    $slgDiff = array_diff(array_keys($slgGroup), array_keys($slgRewardGroup));
    if ($slgDiff) {
        $slgErrorMsg = "======season_climb_slg_reward.csv缺少======" . json_encode($slgDiff) . PHP_EOL;
    }

    if ($coordErrorMsg || $contaErrorMsg || $seErrorMsg || $slgErrorMsg) {
        $checkErrorMsg = $coordErrorMsg . $contaErrorMsg . $seErrorMsg . $slgErrorMsg;
        \Utils\Builder::notice($checkErrorMsg);
        exit(1);
    }
}

function check_abyss_config() {
    // 获取所有的group的id
    $groupConfig = get_php_config('activity_abyss_group');
    $groupConfigIds = array_flip(array_keys($groupConfig['data']));
    foreach ($groupConfigIds as $groupConfigId => $_) {
        $groupConfigIds[$groupConfigId] = $groupConfig['data'][$groupConfigId]['id'];
    }

    // 配置名对应的检查的情况
    $needCheckConfig = [
        'activity_abyss_expend_reward' => [
            'group_col' => 'group',
        ],
        'activity_abyss_rank_reward' => [
            'group_col' => 'group',
        ],
        'activity_abyss_gather_reward' => [
            'group_col' => 'group',
        ],
        'activity_abyss_special_phase' => [
            'group_col' => 'group',
        ],
        'activity_abyss_block_level' => [
            'group_col' => 'activity_group',
        ],
    ];
    // 遍历配置名检查配置
    $errMsg = '';
    foreach ($needCheckConfig as $configName => $checkParams) {
        $tmpErrorMsg = check_abyss_grouped_config($configName, $groupConfigIds, $checkParams['group_col']);
        if (!$tmpErrorMsg) {
            continue;
        }
        $errMsg .= $tmpErrorMsg . PHP_EOL;
    }

    // 有报错信息报错

    if ($errMsg) {
        \Utils\Builder::notice($errMsg);
        exit(1);
    }
}

function check_abyss_grouped_config($configName, $groupIds, $groupCol = 'group') {
    $errMsg = '';

    $groupIdReferenceTimes = [];

    foreach ($groupIds as $iGroupId => $sGroupId) {
        $groupIdReferenceTimes[$iGroupId] = 0;
    }

    // 获取配置
    $configData = get_php_config($configName);
    $configLines = $configData['data'];

    // 遍历整理数据 组ID对应配置数量
    foreach ($configLines as $configLine) {
        if (!$configLine[$groupCol]) {
            $errMsg .= sprintf('配置表 %s 的 %s 没有配置活动组的ID', $configName, $configLine['id']) . PHP_EOL;
        }
        $groupIdReferenceTimes[$configLine[$groupCol]] += 1;
    }

    // 检查对应配置数量为0的组 返回报错信息
    foreach ($groupIdReferenceTimes as $iGroupId => $times) {
        if ($times == 0) {
            $errMsg .= sprintf('配置表 %s 没有配置活动组 %s 对应的配置', $configName, $groupIds[$iGroupId]) . PHP_EOL;
        }
    }

    return $errMsg;
}

function get_php_config($name) {
    if (!is_file(TEMP_PHP_PATH . $name . '.php')) {
        return [];
    }
    $data = require TEMP_PHP_PATH . $name . '.php';
    $enableIndexMode = Config::get('config.enable_php_config_index_mode', false);
    if ($enableIndexMode) {
        $idMap = require TEMP_PHP_PATH . $name . '.id.php';
    } else {
        $idMap = [];
    }
    return $data + $idMap;
}

function check_kvk_some_config() {
    $kvkPointConvert = get_php_config('kvk_point_convert');
    $kvkStageConfig = get_php_config('kvk_stage_config');
    $kvkRankRewardConfig = get_php_config('kvk_rank_reward');
    $kvkStageGroup = get_php_config('kvk_stage_group');

    check_count_kvk_point_convert_and_kvk_stage_config($kvkPointConvert, $kvkStageConfig);
    check_count_kvk_rank_reward_and_kvk_stage_group($kvkRankRewardConfig, $kvkStageGroup);
    check_kvk_rank_reward_and_kvk_stage_group($kvkRankRewardConfig, $kvkStageConfig, $kvkStageGroup);
}

function check_activity_score_special_req_some_config() {
    $activityScoreSpecialReqConf = get_php_config('activity_score_special_req');
    $activityScoreSpecialMainConf = get_php_config('activity_score_special_main');
    $activityScoreSpecialStepConf = get_php_config('activity_score_special_step');

    check_count_activity_score_special_req($activityScoreSpecialReqConf, $activityScoreSpecialMainConf);
    check_activity_count_score_special_step($activityScoreSpecialStepConf, $activityScoreSpecialMainConf);
}

function check_count_activity_score_special_req($activityScoreSpecialReqConf, $activityScoreSpecialMainConf) {
    // activity_score_special_req表中activity_id列值排重后的值应该完全覆盖activity_score_special_main表中的id值，如果有缺失，报警
    $mainCount = count($activityScoreSpecialMainConf['data']);
    $reqConfCount = 0;
    foreach ($activityScoreSpecialReqConf['data'] as $row) {
        if (isset($activityScoreSpecialMainConf['data'][$row['activity_id']])) {
            $reqConfCount++;
            unset($activityScoreSpecialMainConf['data'][$row['activity_id']]);
        }
    }

    if ($reqConfCount < $mainCount) {
        foreach ($activityScoreSpecialMainConf['data'] as $_row) {
            if ($_row['type'] == 1 || $_row['type'] == 2) {
                $mainCount--;
            }
        }
        if ($reqConfCount < $mainCount) {
            \Utils\Builder::notice("activity_score_special_req 和 activity_score_special_main 检测失败\n
         activity_score_special_main表[activity_id]列去重后数量应该等于activity_score_special_req表中的行数
         ------咨询----伟男------
          activity_score_special_req表:$reqConfCount | activity_score_special_main:$mainCount
         " . json_encode($activityScoreSpecialMainConf['data']));
            exit(1);
        }

    }
}

function check_activity_count_score_special_step($activityScoreSpecialStepConf, $activityScoreSpecialMainConf) {
    // activity_score_special_step表中activity_id列值排重后的值应该完全覆盖activity_score_special_main表中的id值，如果有缺失，报警

    $mainCount = count($activityScoreSpecialMainConf['data']);

    $stepConfCount = 0;
    foreach ($activityScoreSpecialStepConf['data'] as $row) {
        if (isset($activityScoreSpecialMainConf['data'][$row['activity_id']])) {
            $stepConfCount++;
            unset($activityScoreSpecialMainConf['data'][$row['activity_id']]);
        }
    }
    if ($stepConfCount < $mainCount) {
        foreach ($activityScoreSpecialMainConf['data'] as $_row) {
            if ($_row['type'] == 1 || $_row['type'] == 2) {
                $mainCount--;
            }
        }

        if ($stepConfCount < $mainCount) {
            \Utils\Builder::notice("activity_score_special_step 和 activity_score_special_main 检测失败\n
         activity_score_special_step表[activity_id]列去重后数量应该等于activity_score_special_main表中的行数
         ------咨询----伟男------
            activity_score_special_step表:$stepConfCount | activity_score_special_main:$mainCount | =>" . json_encode($activityScoreSpecialMainConf['data']));
            exit(1);
        }
    }
}

function check_count_kvk_point_convert_and_kvk_stage_config($kvkPointConvert, $kvkStageConfig) {
    // 计算kvk_point_convert表里activity_id去重后的行数，
    //不能小于 kvk_stage_config id行数量（kvk_stage_config这里的id都要用？）除kvk_stage_matching、kvk_stage_marched、kvk_stage_recall的所有id值
    $stageConfigCount = count($kvkStageConfig['data'] ?? []);
    $exclude = [
        'kvk_stage_matching',
        'kvk_stage_marched',
        'kvk_stage_recall',
        'kvk_stage_recall_hefu',
    ];
    $stageConfigCount -= count($exclude);
    $pointConvertCount = 0;
    foreach ($kvkPointConvert['data'] as $row) {
        if (isset($kvkStageConfig['data'][$row['activity_id']])) {
            $pointConvertCount++;
            unset($kvkStageConfig['data'][$row['activity_id']]);
        }
    }
    if ($pointConvertCount < $stageConfigCount) {
        \Utils\Builder::notice("kvk_point_convert 和 kvk_point_convert 检测失败\n
         kvk_point_convert表中activity_id列的值数量不能小于kvk_stage_config表中的行数
         ------咨询----伟男------
         ");
        exit(1);
    }

}

function check_count_kvk_rank_reward_and_kvk_stage_group($kvkRankRewardConfig, $kvkStageGroup) {
    // 计算kvk_rank_reward表里kvk_group_id去重后的行数，
    //不能小于 kvk_stage_config id行数量（kvk_stage_config这里的id都要用？）

    $stageGroupCount = count($kvkStageGroup['data'] ?? []);

    $kvkRankRewardData = $kvkRankRewardConfig['data'];

    $rankRewardCount = 0;
    foreach ($kvkRankRewardData as $row) {
        if (isset($kvkStageGroup['data'][$row['kvk_group_id']])) {
            $rankRewardCount++;
            unset($kvkStageGroup['data'][$row['kvk_group_id']]);
        }
    }
    if ($rankRewardCount < $stageGroupCount) {
        \Utils\Builder::notice("kvk_rank_reward 和 kvk_point_convert 检测失败\n
         kvk_rank_reward表中kvk_group_id列的值数量不能小于kvk_stage_config表中的行数
         ------咨询----伟男------
         ");
        exit(1);
    }

}

function check_kvk_rank_reward_and_kvk_stage_group($kvkRankRewardConfig, $kvkStageConfig, $kvkStageGroup) {

    //kvk_stage_speedup,kvk_stage_build_hero,kvk_stage_lord_gem_train,kvk_stage_survivor_equip_hero,kvk_stage_lord_equip_speedup,kvk_stage_pvp
    //kvk_rank_reward 按照index列顺序的行,对应的activity_id列值去重后要和kvk_stage_group表中activity_config列值顺序一致
    //kvk_stage_lord_equip_speedup 和 kvk_stage_pvp中间有18行空值（type列值为3和4的空）

    $kvkRankRewardData = $kvkRankRewardConfig['data'];
    unset($kvkRankRewardConfig);

    sort_rows($kvkRankRewardData, [
        'kvk_group_id' => SORT_ASC,
        'index' => SORT_ASC,
    ]);

    $_tmpRankRwardMap = [];
    foreach ($kvkRankRewardData as $row) {
        $_tmpRankRwardMap[$row['kvk_group_id']][$row['activity_id']][] = $row;
    }

    $exclude = [
        'kvk_stage_matching' => $kvkStageConfig['kvk_stage_matching'],
        'kvk_stage_marched' => $kvkStageConfig['kvk_stage_marched'],
        'kvk_stage_recall' => $kvkStageConfig['kvk_stage_recall'],
        'kvk_stage_recall_hefu' => $kvkStageConfig['kvk_stage_recall_hefu'] ?? [],
    ];

    //$startSearchIndex = 0;
    foreach ($kvkStageGroup['data'] as $iId => $row) {
        if (empty($row['activity_config'])) {
            \Utils\Builder::notice("kvk_stage_group检测失败\n
         kvk_stage_group表中activity_config列不能为空\n
         ------咨询----伟男------
         ");
            exit(1);
        }
        // $_count = $kvkRankRewardData;
//        for ($_i = 0; $_i < $_count; $_i++) {
//            if ($kvkRankRewardData[$_i]['kvk_group_id'] == $iId) {
//                $startSearchIndex = $_i;
//                break;
//            }
//        }

        foreach ($row['activity_config'] as $kvkStageConfigVal) {
            if (in_array($kvkStageConfigVal, $exclude)) {
                continue;
            }

            if (!$_tmpRankRwardMap[$row['Internal_ID']][$kvkStageConfigVal]) {
                \Utils\Builder::notice("kvk_stage_group检测失败\n
                         kvk_rank_reward表[activity_id]列的顺序和kvk_stage_group表[activity_config]列顺序不一致\n
                         在kvk_rank_reward表缺少[activity_id]列值为{$kvkStageConfig['data'][$kvkStageConfigVal]['id']}的行
                         kvk_stage_group:{$row['id']}
                         ------咨询----伟男------
                         ");
                exit(1);
            }
            $_count = count($_tmpRankRwardMap[$row['Internal_ID']][$kvkStageConfigVal]);
            if ($_count < 18) {
                \Utils\Builder::notice("kvk_stage_group检测失败\n
                         kvk_rank_reward排行奖励type=1和type=2相加少于18行 |[$_count]
                         在kvk_rank_reward表中[activity_config]列值为{$kvkStageConfig['data'][$kvkStageConfigVal]['id']}
                         以及[kvk_group_id]列值为{$row['id']}的行
                         ------咨询----伟男------
                         ");
                exit(1);
            }

//            for ($_i = $startSearchIndex; $_i < count($_count); $_i++) {
//                if (empty($kvkRankRewardData[$_i]['activity_id'])
//                    && ($kvkRankRewardData[$_i]['type'] == 3 || $kvkRankRewardData[$_i]['type'] == 4)) {
//                    continue;
//                }
//                if ($kvkRankRewardData[$_i]['activity_id'] != $kvkStageConfigVal) {
//                    //一个都不相等
//                    if ($_i == $startSearchIndex) {
//                        \Utils\Builder::notice("kvk_stage_group检测失败\n
//                         kvk_rank_reward表[activity_config]列的顺序和kvk_stage_group表[activity_id]列顺序不一致\n
//                         在kvk_rank_reward表行=>{$kvkRankRewardData[$_i]['id']}[activity_id=>{$kvkStageConfig['data'][$kvkRankRewardData[$_i]['activity_id']]['id']}]。activity_id应该是{$kvkStageConfig['data'][$kvkStageConfigVal]['id']}
//                         ------咨询----伟男------
//                         ");
//                        exit(1);
//                    }
//                    $startSearchIndex = $_i;
//                    break;
//                }
//            }
        }
    }
}

function check_backpack_recycle($rows, $idToInternalId) {
    // 获取配置行
    foreach ($rows['type'] as $i => $type) {
        // 过滤掉不是活动的行
        if ($type != 1) {
            continue;
        }

        if (empty($rows['activity_id'][$i])) {
            throw new \ErrorException('没有配置活动id');
        }

        // 过滤掉没有限制活动组的行
        if (empty($rows['activity_group'][$i])) {
            continue;
        }

        // 有活动组但是没有配置组表名报错
        if (empty($rows['activity_check'][$i])) {
            throw new \ErrorException('配置了活动组但是没有配置组的表名');
        }

        // 配置表名不正确
        if (!isset($idToInternalId[$rows['activity_check'][$i]])) {
            throw new \ErrorException('表名' . $rows['activity_check'][$i] . '不存在');
        }

        // 检查活动组配置里是否有id的配置
        foreach ($rows['activity_group'][$i] as $sGroupId) {
            check_string_id($rows['activity_check'][$i], $sGroupId, $idToInternalId);
        }
    }
}

function check_activity_control_group() {
    $activityControlCycleServer = get_php_config('activity_control_cycle_server');
    $activityControlNewServer = get_php_config('activity_control_new_server');
    $activityControlCycleSeason = get_php_config('activity_control_cycle_season');

    $groupConfs = [];
    foreach ($activityControlCycleServer['data'] as $row) {
        if (empty($row['active_id']) || empty($row['activity_group'])) {
            continue;
        }

        if (!isset($groupConfs[$row['activity_group']])) {
            if (!is_file(TEMP_PHP_PATH . $row['activity_group'] . '.php')) {
                throw new \ErrorException('activity_control_cycle_server activity_group 列对应的表名' . $row['activity_group'] . '不存在');
            }
            $tmp = get_php_config($row['activity_group']);
            $groupConfs[$row['activity_group']] = $tmp['data'];
        }

        $find = false;
        foreach ($groupConfs[$row['activity_group']] as $groupRow) {
            if ($groupRow['id'] == $row['active_id']) {
                $find = true;
                break;
            }
        }

        if (!$find) {
            \Utils\Builder::notice("activity_control_cycle_server 检测失败\n
            activity_control_cycle_server 的 id = {$row['id']}, active_id = {$row['active_id']} 
            在 {$row['activity_group']} 表中没有找到对应的配置
            ");
            exit(1);
        }
    }

    foreach ($activityControlNewServer['data'] as $row) {
        if (empty($row['active_id']) || empty($row['activity_group'])) {
            continue;
        }

        if (!isset($groupConfs[$row['activity_group']])) {
            if (!is_file(TEMP_PHP_PATH . $row['activity_group'] . '.php')) {
                throw new \ErrorException('activity_control_new_server activity_group 列对应的表名' . $row['activity_group'] . '不存在');
            }
            $tmp = get_php_config($row['activity_group']);
            $groupConfs[$row['activity_group']] = $tmp['data'];
        }

        $find = false;
        foreach ($groupConfs[$row['activity_group']] as $groupRow) {
            if ($groupRow['id'] == $row['active_id']) {
                $find = true;
                break;
            }
        }

        if (!$find) {
            \Utils\Builder::notice("activity_control_new_server 检测失败\n
            activity_control_new_server 的 id = {$row['id']}, active_id = {$row['active_id']} 
            在 {$row['activity_group']} 表中没有找到对应的配置
            ");
            exit(1);
        }
    }

    foreach ($activityControlCycleSeason['data'] as $row) {
        if (empty($row['active_id']) || empty($row['activity_group'])) {
            continue;
        }

        if (!isset($groupConfs[$row['activity_group']])) {
            if (!is_file(TEMP_PHP_PATH . $row['activity_group'] . '.php')) {
                throw new \ErrorException('activity_control_cycle_season activity_group 列对应的表名' . $row['activity_group'] . '不存在');
            }
            $tmp = get_php_config($row['activity_group']);
            $groupConfs[$row['activity_group']] = $tmp['data'];
        }

        $find = false;
        foreach ($groupConfs[$row['activity_group']] as $groupRow) {
            if ($groupRow['id'] == $row['active_id']) {
                $find = true;
                break;
            }
        }

        if (!$find) {
            \Utils\Builder::notice("activity_control_cycle_season 检测失败\n
            activity_control_cycle_season 的 id = {$row['id']}, active_id = {$row['active_id']} 
            在 {$row['activity_group']} 表中没有找到对应的配置
            ");
            exit(1);
        }
    }
}

function check_survivor_climb_commision() {
    $survivorConfig = get_php_config('survivor');
    $climbCommision = get_php_config('activity_climb_commision_survivor');

    // 不需要检查的英雄的ID
    $excludeISurvivorId = [
        $survivorConfig['survivor_assault_default'],
        $survivorConfig['survivor_chief_female'],
        $survivorConfig['survivor_chief_male'],
        $survivorConfig['survivor_sniper_default'],
        $survivorConfig['survivor_zealots_default'],
    ];

    // $climbCommision 里面有的英雄的ID
    $hasCommisionISurvivorIds = array_column($climbCommision['data'], 'survivor');

    // 所有英雄的ID
    $allISurvivorIds = array_keys($survivorConfig['data']);

    // 需要配置但是没有配置的英雄的ID列表
    $diffISurvivorIds = array_diff($allISurvivorIds, array_merge($hasCommisionISurvivorIds, $excludeISurvivorId));

    // pk转ID
    $diffSSurvivorIds = [];
    foreach ($diffISurvivorIds as $diffISurvivorId) {
        $diffSSurvivorIds[$diffISurvivorId] = $survivorConfig['data'][$diffISurvivorId]['id'];
    }

    if ($diffISurvivorIds) {
        \Utils\Builder::notice(sprintf("activity_climb_commision_survivor 检测失败\nactivity_climb_commision_survivor 里缺少英雄(%s)的配置\n", implode(', ', $diffSSurvivorIds)));
        exit(1);
    }
}

function check_emperor_map_buildings() {
    // 获取所有的group的id
    $groupConfig = get_php_config('emperor_map_buildings')['data'] ?? [];

    // 遍历检查配置
    $errMsg = '';
    foreach ($groupConfig as $_conf) {
        $buildingNpcNum = count($_conf['building_npc']);
        $npcRevivalNum = count($_conf['npc_revival']);
        if ($buildingNpcNum !== $npcRevivalNum) {
            $errMsg .= "配置行:{$_conf['id']},列(building_npc)与列(npc_revival)数据个数不一致" . PHP_EOL;
        }
    }

    // 有报错信息报错
    if ($errMsg) {
        \Utils\Builder::notice(sprintf("emperor_map_buildings 检测失败\n%s==============\n", $errMsg));
        exit(1);
    }
}

function getExcludeConfig() {
    static $excludeConfig;
    if (empty($excludeConfig)) {
        $excludeConfig = Config::get('config.exclude_config', ['researches', 'shop', 'research', 'gameplay_effects']);
    }
    return $excludeConfig ?? [];
}
