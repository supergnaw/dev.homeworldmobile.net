<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

//$nestbox = new \app\Nestbox\Nestbox();
//$babbler = new \app\Nestbox\Babbler\Babbler();
$api = new \app\PlayFab\Playfab(PLAYFAB_APP_ID);

//
///*
// * Star Systems
// */
//$results = json_decode($api->get_title_data(keys: "StarSystemData")[0]['data_content']);
//$string_data = json_decode($api->get_title_data(keys: "StringData")[0]['data_content']);
//
//$table = ["
//            <table>"];
//$table[] = "
//                <thead>
//                    <tr>
//                        <th>Name</th>
//                        <th>Tier</th>
//                        <th>Faction</th>
//                        <th>Scan Req.</th>
//                        <th>Jump Req.</th>
//                        <th>Liaison</th>
//                        <th>Shipyard</th>
//                    </tr>
//                </thead>
//                <tbody>";
//
//$has = [
//    0 => "<img class=\"in-line\" src=\"/img/no.png\" alt-text='\"no\"'>",
//    1 => "<img class=\"in-line\" src=\"/img/yes.png\" alt-text='\"yes\"'>",
//];
//
//foreach ($results as $system) {
//    $name = $system->Name;
//    $tier = "<img class=\"in-line\" src=\"/img/ui/tier/{$system->Tier}.png\" alt-text=\"{$system->Tier}\"/>";
//    $faction = $system->Faction;
//    $faction = $string_data->$faction->en;
//    $jump = $system->JumpDifficulty;
//    $liaison = $has[str_contains(haystack: strtoupper($system->TemplateTags), needle: "FACTION_OFFICE:FACTION_MARKET")];
//    $shipyard = $has[str_contains(haystack: strtoupper($system->TemplateTags), needle: "SHIPYARD")];
//    list($partial, $full) = explode(",", $system->ScanDifficulty);
////        if (!in_array(needle: $system->Tier, haystack: [0,1,2,3,4])) continue;
//    $class = strtolower($faction);
//    $table[] = "
//                    <tr class=\"{$class}\">
//                        <td>{$name}</td>
//                        <td>{$tier}</td>
//                        <td><img class='in-line' src='/img/faction/{$faction}.png'> {$faction}</td>
//                        <td>{$system->ScanDifficulty}</td>
//                        <td>{$jump}</td>
//                        <td>{$liaison}</td>
//                        <td>{$shipyard}</td>
//                    </tr>";
//}
//$table[] = "</tbody></table>";
//$table = implode(separator: "\n", array: $table);
//return $table;


$sort_whitelist = [
    'system_name',
    'faction',
    'tier',
    'shipyard',
    'signal_total',
    'belts',
    'jovian_total',
    'moons'
];
$column = (in_array(needle: $uri[2] ?? 'false', haystack: $sort_whitelist)) ? $uri[2] : 'system_name';
$order = (in_array(($uri[3] ?? false), ['asc', 'desc'])) ? $uri[3] : 'asc';

$additional_sorting = [
    'system_name' => ", `faction` {$order}, `tier` {$order}",
    'tier' => ", `faction` {$order}, `tier` {$order}",
    'faction' => ", `system_name` {$order}, `tier` {$order}",
    'shipyard' => ", `faction` {$order}, `system_name` {$order}"
];
$additional_sorting = $additional_sorting[$column] ?? ", `faction` {$order}, `tier` {$order}, `system_name` {$order}";

$sql = "SELECT *,
            (`cangacian_total` + `tanoch_total` + `yaot_total` + `amassari_total` + `kiithless_total` + `relic_total`
                 + `progenitor_total` + `activities_total` + `distress_total` + `special_total` + `trader_total`) AS `signal_total`,
            (`e_total` + `f_total` + `g_total` + `h_total`) / 3 AS `jovian_total`
            FROM (
                SELECT `raw_data_systems`.*,
                    (SUBSTRING_INDEX(`cangacian`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`cangacian`,':',2),':',-1) + SUBSTRING_INDEX(`cangacian`,':',-1)) AS `cangacian_total`,
                    (SUBSTRING_INDEX(`tanoch`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`tanoch`,':',2),':',-1) + SUBSTRING_INDEX(`tanoch`,':',-1)) AS `tanoch_total`,
                    (SUBSTRING_INDEX(`yaot`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`yaot`,':',2),':',-1) + SUBSTRING_INDEX(`yaot`,':',-1)) AS `yaot_total`,
                    (SUBSTRING_INDEX(`amassari`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`amassari`,':',2),':',-1) + SUBSTRING_INDEX(`amassari`,':',-1)) AS `amassari_total`,
                    (SUBSTRING_INDEX(`kiithless`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`kiithless`,':',2),':',-1) + SUBSTRING_INDEX(`kiithless`,':',-1)) AS `kiithless_total`,
                    (SUBSTRING_INDEX(`relic`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`relic`,':',2),':',-1) + SUBSTRING_INDEX(`relic`,':',-1)) AS `relic_total`,
                    (SUBSTRING_INDEX(`progenitor`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`progenitor`,':',2),':',-1) + SUBSTRING_INDEX(`progenitor`,':',-1)) AS `progenitor_total`,
                    (SUBSTRING_INDEX(`activities`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`activities`,':',2),':',-1) + SUBSTRING_INDEX(`activities`,':',-1)) AS `activities_total`,
                    (SUBSTRING_INDEX(`distress`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`distress`,':',2),':',-1) + SUBSTRING_INDEX(`distress`,':',-1)) AS `distress_total`,
                    (SUBSTRING_INDEX(`special`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`special`,':',2),':',-1) + SUBSTRING_INDEX(`special`,':',-1)) AS `special_total`,
                    (SUBSTRING_INDEX(`trader`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`trader`,':',2),':',-1) + SUBSTRING_INDEX(`trader`,':',-1)) AS `trader_total`,
                	(`m` + `a` + `b` + `c`) AS `belts`,
                    (SUBSTRING_INDEX(`e`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`e`,':',2),':',-1) + SUBSTRING_INDEX(`e`,':',-1)) AS `e_total`,
                    (SUBSTRING_INDEX(`f`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`f`,':',2),':',-1) + SUBSTRING_INDEX(`f`,':',-1)) AS `f_total`,
                    (SUBSTRING_INDEX(`g`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`g`,':',2),':',-1) + SUBSTRING_INDEX(`g`,':',-1)) AS `g_total`,
                    (SUBSTRING_INDEX(`h`,':',1) + SUBSTRING_INDEX(SUBSTRING_INDEX(`h`,':',2),':',-1) + SUBSTRING_INDEX(`h`,':',-1)) AS `h_total`,
                	(SUBSTRING_INDEX(`e`,':',1) +SUBSTRING_INDEX(`f`,':',1) + SUBSTRING_INDEX(`g`,':',1) + SUBSTRING_INDEX(`h`,':',1)) AS `gas_3`,
                	(SUBSTRING_INDEX(SUBSTRING_INDEX(`e`,':',2),':',-1)
                        + SUBSTRING_INDEX(SUBSTRING_INDEX(`f`,':',2),':',-1)
                        + SUBSTRING_INDEX(SUBSTRING_INDEX(`g`,':',2),':',-1)
                        + SUBSTRING_INDEX(SUBSTRING_INDEX(`h`,':',2),':',-1)) AS `gas_4`,
                	(SUBSTRING_INDEX(`e`,':',-1) + SUBSTRING_INDEX(`f`,':',-1) + SUBSTRING_INDEX(`g`,':',-1) + SUBSTRING_INDEX(`h`,':',-1)) AS `gas_5`
                FROM `raw_data_systems`
            ) `sigs`
            ORDER BY `{$column}` {$order} {$additional_sorting};";
$sql = "SELECT * FROM `google_data_systems`";
$api->sql_exec($sql);
$results = $api->results();
var_dump($results);

$order = ('asc' == $order) ? 'desc' : 'asc';

$ore_img_path = "/img/assets/Art/Textures/UI/Overhaul/ResourceIconSprites/ui_res_icon_crude.png";
$ast_img_path = "/img/assets/Art/Textures/UI/Overhaul/MapIconSprites/ui_map_asteroirds_01.png";
$gas_img_path = "/img/assets/Art/Textures/UI/Overhaul/MapIconSprites/UI_map_jovian_01.png";
$msn_img_path = "/img/assets/Art/Textures/UI/Overhaul/AssignmentUISprites/ui_mission_side.png";
$shp_img_path = "/img/assets/Art/Textures/UI/Overhaul/InternalFlagshipMenuSprites/FeatureIcon_Shipyard.png";
$mun_img_path = "/img/assets/Art/Textures/UI/Overhaul/RoundedFrame.png";
$mun_img_path = "/img/assets/Art/Textures/UI/Overhaul/SystemOutOfRangeIcon.png";
$out_img_path = "/img/assets/Art/Textures/UI/Overhaul/HUDUISprites/StationMenu_Market.png";
$res_img_path = "/img/assets/Art/Textures/UI/Alpha/TacticalIndicators/ui_tactical_ship_icon_utility_00.png";

$table = ["<table>"];
$table[] = "
                <thead>
                    <tr>
                        <th>Name <a href='/{$uri[0]}/{$uri[1]}/system_name/{$order}'><br><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <th>Tier <a href='/{$uri[0]}/{$uri[1]}/tier/{$order}'><br><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <th>Faction <a href='/{$uri[0]}/{$uri[1]}/faction/{$order}'><br><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <!--th>Resource<br>Availability</th-->
                        <th><img src='{$res_img_path}' class='header-icon' style='height: calc(var(--body-font-size) * 3);' alt='resources'/></th>
                        <th><img src='{$shp_img_path}' class='header-icon' alt='shipyard'/><br><a href='/{$uri[0]}/{$uri[1]}/shipyard/{$order}'><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <th><img src='{$out_img_path}' class='header-icon' alt='outpost'/><br><a href='/{$uri[0]}/{$uri[1]}/outpost/{$order}'><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <th><img src='{$msn_img_path}' class='header-icon' alt='missions'/><br><a href='/{$uri[0]}/{$uri[1]}/signal_total/{$order}'><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <th><img src='{$ast_img_path}' class='header-icon' alt='asteroids'/><br><a href='/{$uri[0]}/{$uri[1]}/belts/{$order}'><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <th><img src='{$gas_img_path}' class='header-icon' alt='jovian'/><br><a href='/{$uri[0]}/{$uri[1]}/jovian/{$order}'><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                        <th><img src='{$mun_img_path}' class='header-icon' alt='moon'/><br><a href='/{$uri[0]}/{$uri[1]}/moons/{$order}'><img src='/img/up-dn-arrow.svg' class='in-line' style='margin-left: 5px;'/></a></th>
                    </tr>
                </thead>
                <tbody>";

$css1 = "font-size: calc(var(--body-font-size) - .4em); font-family: var(--banner-font); border: 0px solid transparent;";
$css2 = "display: inline-block; height: calc(var(--body-font-size) - .25em); margin-bottom: -.125em; border: 0px solid transparent;";
$css3 = "display: inline-block; width: 1.25em; border: 0px solid transparent;";

$css1 = "display: block; position: relative; color: white; height: calc(var(--body-font-size)); width: 2.5em; margin: 3px 0 0 0; font-family: var(--banner-font);";
$css2 = "display: block; position: absolute; top: 10%; left: 5%; height: 80%;";
$css3 = "display: block; position: absolute; top: -10%; right: 5%; width: 50%; height: 80%;";

$asteroid_tags = [
    'm' => "<span class='tier-tag' style='background-color: var(--ru-m-dark); color: white; {$css1}'>
                    <img class='' src='{$ore_img_path}' alt-text='raw ore icon\' style='{$css2}'/>
                    <span style='{$css3}'>M</span>
                </span> ",
    'a' => "<span class='tier-tag' style='background-color: var(--ru-a-dark); color: white; {$css1}'>
                    <img class='' src='{$ore_img_path}' alt-text='raw ore icon\' style='{$css2}'/>
                    <span style='{$css3}'>A</span>
                </span> ",
    'b' => "<span class='tier-tag' style='background-color: var(--ru-b-dark); color: white; {$css1}'>
                    <img class='' src='{$ore_img_path}' alt-text='raw ore icon\' style='{$css2}'/>
                    <span style='{$css3}'>B</span>
                </span> ",
    'c' => "<span class='tier-tag' style='background-color: var(--ru-c-dark); color: white; {$css1}'>
                    <img class='' src='{$ore_img_path}' alt-text='raw ore icon\' style='{$css2}'/>
                    <span style='{$css3}'>C</span>
                </span> ",
    'd' => "<span class='tier-tag' style='background-color: var(--ru-d-dark); color: white; {$css1}'>
                    <img class='' src='{$ore_img_path}' alt-text='raw ore icon\' style='{$css2}'/>
                    <span style='{$css3}'>D</span>
                </span> "
];

$css4 = "border: 3px solid white; color: white; display: inline-block; grid-template-columns: 1fr; width: 2.45em; margin: 3px 0 0 0; text-align: center;";
$css4 = "display: block; position: relative; color: white; height: calc(var(--body-font-size) - 4px); width: calc(2.5em - 4px); border: 2px solid white; margin: 3px 0 0 0; font-family: var(--banner-font);";
$css5 = "display: block; text-align: center; margin-top: -0.25em;";
//    $css5 = "display: block; height: 80%; width: calc(100% - 4px); height: calc(100% - 4px); border: 2px solid white;";

$gas_tags = [
    'e' => "<span style='{$css4} background-color: var(--gu-e-dark); border-color: var(--gu-e-light);'><span style='{$css5}'>E</span></span>",
    'f' => "<span style='{$css4} background-color: var(--gu-f-dark); border-color: var(--gu-f-light);'><span style='{$css5}'>F</span></span>",
    'g' => "<span style='{$css4} background-color: var(--gu-g-dark); border-color: var(--gu-g-light);'><span style='{$css5}'>G</span></span>",
    'h' => "<span style='{$css4} background-color: var(--gu-h-dark); border-color: var(--gu-h-light);'><span style='{$css5}'>H</span></span>",
];

# /img/assets/Art/Textures/UI/Overhaul/ResourceIconSprites/ui_res_icon_crude.png

$has = [
    0 => "<img class=\"in-line\" src=\"/img/no.png\" alt-text='\"no\"'>",
    1 => "<img class=\"in-line\" src=\"/img/yes.png\" alt-text='\"yes\"'>",
];

foreach ($results as $system) {
    $faction = $system['faction'];
    $class = strtolower($faction);
    $shipyard = $has[$system['liaison_market']];

    $asteroids = [];
    if (0 < ($system['m'] ?? -1)) {
        $asteroids[] = "<span>&nbsp;</span>";
        $asteroids[] = $asteroid_tags['m'];
        $asteroids[] = "<span>&nbsp;</span>";
    } else {
        $asteroids[] = (0 < ($system['a'] ?? -1)) ? $asteroid_tags['a'] : "<span>&nbsp;</span>";
        $asteroids[] = (0 < ($system['b'] ?? -1)) ? $asteroid_tags['b'] : "<span>&nbsp;</span>";
        $asteroids[] = (0 < ($system['c'] ?? -1)) ? $asteroid_tags['c'] : "<span>&nbsp;</span>";
        $asteroids[] = (0 < ($system['d'] ?? -1)) ? $asteroid_tags['d'] : "";
    }

    $asteroid_grid = "grid col7";// . min(count($asteroids), 7);
    $asteroids = implode($asteroids);

    $gases = [];
    $gases[] = (0 < ($system['e_total'] ?? -1)) ? $gas_tags['e'] : "<span>&nbsp;</span>";
    $gases[] = (0 < ($system['f_total'] ?? -1)) ? $gas_tags['f'] : "<span>&nbsp;</span>";
    $gases[] = (0 < ($system['g_total'] ?? -1)) ? $gas_tags['g'] : "<span>&nbsp;</span>";
    $gases[] = (0 < ($system['h_total'] ?? -1)) ? $gas_tags['h'] : "<span>&nbsp;</span>";
    $gases = implode($gases);

    $liaison_market = "/img/assets/Art/Textures/UI/Overhaul/StationMenusLiasonSprites/StationMenu_{$faction}Requisition.png";
    $css6 = "max-height: var(--body-font-size); display: inline; margin: 5px 0 -5px 0;";
    $market = (1 == $system['liaison_market']) ? "<img src='{$liaison_market}' style='{$css6}'>" : "<img src='/img/no.png' class='in-line'>";


    /*
     *
                        <span class='tier-tag' style='background-color: #3771bd; color: white; font-family: \"Michroma\"; display: inline-block; position: relative;'>
                            <img style='display: inline-block; margin-bottom: -5px; height: 100%;' src='/img/assets/Art/Textures/UI/Overhaul/TierSprites/UI_tier_{$system['tier']}.png' alt-text='tier {$system['tier']}\'/>
                            <span style='display: inline-block; position: relative; bottom: 3px; right: 4px;'>{$system['level']}</span>
                        </span>
     * */
    $resources = [];
    if (0 < ($system['m'] ?? -1)) $resources[] = "<span class='resource_m'>M</span>";
    if (0 < ($system['a'] ?? -1)) $resources[] = "<span class='resource_a'>A</span>";
    if (0 < ($system['b'] ?? -1)) $resources[] = "<span class='resource_b'>B</span>";
    if (0 < ($system['c'] ?? -1)) $resources[] = "<span class='resource_c'>C</span>";
//        if (0 < ($system['d'] ?? -1)) $resources[] = "<span class='resource_d'>D</span>";
    if (0 < $system['belts'] and 3 <= $system['tier']) $resources[] = "<span class='resource_d'>D</span>";
    if (0 < ($system['e_total'] ?? -1)) $resources[] = "<span class='resource_e'>E</span>";
    if (0 < ($system['f_total'] ?? -1)) $resources[] = "<span class='resource_f'>F</span>";
    if (0 < ($system['g_total'] ?? -1)) $resources[] = "<span class='resource_g'>G</span>";
    if (0 < ($system['h_total'] ?? -1)) $resources[] = "<span class='resource_h'>H</span>";
    $resources = implode($resources);

    $system_json = json_encode($system);
    $kabob_name = preg_replace(pattern: '/\s+/', replacement: '-', subject: strtolower($system['system_name']));

    $table[] = "
                    <tr class=\"{$class}\" id='{$kabob_name}' data-json='{$system_json}'>
                        <td>{$system['system_name']}</td>
                        <td>
                            <span class='tier-tag'>
                                <img src='/img/assets/Art/Textures/UI/Overhaul/TierSprites/UI_tier_{$system['tier']}.png' alt-text='tier {$system['tier']}\'/>
                                <span>{$system['level']}</span>
                            </span>
                        </td>
                        <td class='faction-icon-container {$faction}'>{$system['faction']}</td>
                        <!--td class='{$asteroid_grid}' style='grid-column-gap: 3px;'>{$asteroids}{$gases}</td-->
                        <td>$resources</td>
                        <td>{$shipyard}</td>
                        <td>{$market}</td>
                        <td>{$system['signal_total']}</td>
                        <td>{$system['belts']}</td>
                        <td>{$system['jovian']}</td>
                        <td>{$system['moons']}</td>
                    </tr>";

    if (0 == rand(0, 0)) {
        $table[] = "
            <tr id='{$kabob_name}-details' class='{$class} row-detail hide'>
                <td colspan='10'>
            
                </td>
            </tr>";
    }
}

$table[] = "
                <style>
                    .tier-tag {
                        background-color: #3771bd;
                        color: white;
                        font-family: 'Michroma';
                        display: inline-block;
                        position: relative;
                        height: var(--body-font-size);
                        overflow: hidden;
                        margin-top: 4px;
                        margin-bottom: -4px;
                    }
                    .tier-tag img {
                        display: inline-block;
                        margin-bottom: -0px;
                        height: 100%;
                    }
                    .tier-tag span {
                        display: inline-block;
                        position: relative;
                        bottom: 5px;
                        right: 2px;
                    }
                    [class^=\"resource_\"] {
                        display: inline-block;
                        border: 1px solid transparent;
                        font-family: 'Michroma';
                        margin: 2px 2px -2px 2px;
                        color: white;
                        padding: 0 5px 0 5px;
                    }
                    .resource_m { border-color: var(--ru-m-light); background-color: var(--ru-m-dark); }
                    .resource_a { border-color: var(--ru-a-light); background-color: var(--ru-a-dark); }
                    .resource_b { border-color: var(--ru-b-light); background-color: var(--ru-b-dark); }
                    .resource_c { border-color: var(--ru-c-light); background-color: var(--ru-c-dark); }
                    .resource_d { border-color: var(--ru-d-light); background-color: var(--ru-d-dark); }
                    .resource_e { border-color: var(--gu-e-light); background-color: var(--gu-e-dark); }
                    .resource_f { border-color: var(--gu-f-light); background-color: var(--gu-f-dark); }
                    .resource_g { border-color: var(--gu-g-light); background-color: var(--gu-g-dark); }
                    .resource_h { border-color: var(--gu-h-light); background-color: var(--gu-h-dark); }
                    .row-detail { overflow: hidden; }
                    .row-detail.hide { display: none; }
                </style>
                </tbody>
            </table>";

if ($uri[2] ?? '') {
    $system = urldecode($uri[2]);
    foreach ($results as $result) {
        if ($system == $result['system_name']) {
            $system_details = $result;
        }
    }
} else {
    $system_details = $results[rand(0, count($results) - 1)];
}

$signal_type = [
    "cangacian",
    "tanoch",
    "yaot",
    "amassari",
    "kiithless",
    "relic",
    "progenitor",
    "activities",
    "distress",
    "special",
    "trader",
];
foreach ($signal_type as $signal_type) {
    $system_details[$signal_type] = explode(":", $system_details[$signal_type]);
    $system_details["{$signal_type}_available"] = (0 < $system_details["{$signal_type}_total"]) ? "yes" : "no";
}
$system_details_json = json_encode($system_details, JSON_PRETTY_PRINT);

foreach (['m', 'a', 'b', 'c'] as $ore_type) {
    $system_details["{$ore_type}_available"] = (0 < $system_details[$ore_type]) ? "ore-available" : "ore-available";// : "ore-unavailable";
    $system_details["{$ore_type}_difficulty"] = [];
    foreach (explode(":", $system_details["{$ore_type}x"]) as $level => $qty) {
        if (0 < $qty) {
            $sub_tier = $level + 1;
            $system_details["{$ore_type}_difficulty"][] = str_repeat(string: "<span class='tier-tag resource_{$ore_type}'><img src='/img/assets/Art/Textures/UI/Overhaul/TierSprites/UI_tier_{$system_details['tier']}.png'> <span>{$sub_tier}</span></span> ", times: intval($qty));
        }
    }
    $system_details["{$ore_type}_difficulty"] = implode($system_details["{$ore_type}_difficulty"]);
}

foreach (['e', 'f', 'g', 'h'] as $gas_type) {
    $system_details["{$gas_type}_split"] = implode("</td><td>", explode(":", $system_details["{$gas_type}x"]));
    $system_details["{$gas_type}_split"] = str_replace("0", "<img class='in-line' src='/img/no.png'>", $system_details["{$gas_type}_split"]);
    $system_details["{$gas_type}_split"] = preg_replace("/\d+/", "<img class='in-line' src='/img/yes.png'>", $system_details["{$gas_type}_split"]);
}

$html = "
        <hr>
            <style>
                .system-details:before{
                    position: absolute;
                    content: '';
                    top: 0;
                    left: 5px;
                    height: 100%;
                    width: calc(100% - 5px);
                    opacity: 15%;
                    text-decoration: none;
                    background-color: transparent;
                    background-size: contain;
                    background-position: top;
                    background-repeat: no-repeat;
                    background-image: url('/img/faction/{$system_details['faction']}.png');
                    filter: blur(1em);
                    pointer-events: none;
                }
                
                .system-details tr.yes { display: auto; }
                .system-details tr.no { display: none; }
                .system-details tr.ore-available { display: auto; }
                .system-details tr.ore-unavailable { display: none; }
            </style>
            <div class='system-details' style='position: relative;'>
                <h2>{$system_details['system_name']}</h2>
                <h3><img class='in-line' src='/img/assets/Art/Textures/UI/Overhaul/AssignmentUISprites/ui_mission_side.png'> Signal Distribution ({$system_details['signal_total']})</h3>
                <div class='available-signals'>
                    <table>
                        <thead> 
                            <tr>
                                <th>Signal Type</th> 
                                <th>1st</th> 
                                <th>2nd</th> 
                                <th>3rd</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <tr class='{$system_details['cangacian_available']}'> 
                                <td style='text-align: left;'>Cangacian</td>
                                <td>{$system_details['cangacian'][0]}</td>
                                <td>{$system_details['cangacian'][1]}</td>
                                <td>{$system_details['cangacian'][2]}</td>
                            </tr>
                            <tr class='{$system_details['tanoch_available']}'>
                                <td style='text-align: left;'>Tanoch</td>
                                <td>{$system_details['tanoch'][0]}</td>
                                <td>{$system_details['tanoch'][1]}</td>
                                <td>{$system_details['tanoch'][2]}</td>
                            </tr>
                            <tr class='{$system_details['yaot_available']}'>
                                <td style='text-align: left;'>Yaot</td>
                                <td>{$system_details['yaot'][0]}</td>
                                <td>{$system_details['yaot'][1]}</td>
                                <td>{$system_details['yaot'][2]}</td>
                            </tr>
                            <tr class='{$system_details['amassari_available']}'>
                                <td style='text-align: left;'>Amassari</td>
                                <td>{$system_details['amassari'][0]}</td>
                                <td>{$system_details['amassari'][1]}</td>
                                <td>{$system_details['amassari'][2]}</td>
                            </tr>
                            <tr class='{$system_details['kiithless_available']}'> 
                                <td style='text-align: left;'>Kiithless</td>
                                <td>{$system_details['kiithless'][0]}</td>
                                <td>{$system_details['kiithless'][1]}</td>
                                <td>{$system_details['kiithless'][2]}</td>
                            </tr>
                            <tr class='{$system_details['relic_available']}'> 
                                <td style='text-align: left;'>Relic</td>
                                <td>{$system_details['relic'][0]}</td>
                                <td>{$system_details['relic'][1]}</td>
                                <td>{$system_details['relic'][2]}</td>
                            </tr>
                            <tr class='{$system_details['progenitor_available']}'> 
                                <td style='text-align: left;'>Progenitor</td>
                                <td>{$system_details['progenitor'][0]}</td>
                                <td>{$system_details['progenitor'][1]}</td>
                                <td>{$system_details['progenitor'][2]}</td>
                            </tr>
                            <tr class='{$system_details['activities_available']}'> 
                                <td style='text-align: left;'>Activities</td>
                                <td>{$system_details['activities'][0]}</td>
                                <td>{$system_details['activities'][1]}</td>
                                <td>{$system_details['activities'][2]}</td>
                            </tr>
                            <tr class='{$system_details['distress_available']}'> 
                                <td style='text-align: left;'>Distress</td>
                                <td>{$system_details['distress'][0]}</td>
                                <td>{$system_details['distress'][1]}</td>
                                <td>{$system_details['distress'][2]}</td>
                            </tr>
                            <tr class='{$system_details['special_available']}'> 
                                <td style='text-align: left;'>Special</td>
                                <td>{$system_details['special'][0]}</td>
                                <td>{$system_details['special'][1]}</td>
                                <td>{$system_details['special'][2]}</td>
                            </tr>
                            <tr class='{$system_details['trader_available']}'> 
                                <td style='text-align: left;'>Trader</td>
                                <td>{$system_details['trader'][0]}</td>
                                <td>{$system_details['trader'][1]}</td>
                                <td>{$system_details['trader'][2]}</td>
                            </tr> 
                        </tbody> 
                    </table>
                </div>
                <h3><img class='in-line' src='/img/assets/Art/Textures/UI/Overhaul/MapIconSprites/ui_map_asteroirds_01.png'> Asteroid Clusters ({$system_details['belts']})</h3>
                <div class='available-ore'>
                    <table> 
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Difficulty Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class='{$system_details['m_available']}' style='background-color: var(--ru-m-transparent);'>
                                <td>M</td>
                                <td>" . $system_details['m'] . "</td>
                                <td>{$system_details['m_difficulty']}</td>
                            </tr>
                            <tr class='{$system_details['a_available']}' style='background-color: var(--ru-a-transparent);'>
                                <td>A</td>
                                <td>{$system_details['a']}</td>
                                <td>{$system_details['a_difficulty']}</td>
                            </tr>
                            <tr class='{$system_details['b_available']}' style='background-color: var(--ru-b-transparent);'>
                                <td>B</td>
                                <td>{$system_details['b']}</td>
                                <td>{$system_details['b_difficulty']}</td>
                            </tr>
                            <tr class='{$system_details['c_available']}' style='background-color: var(--ru-c-transparent);'>
                                <td>C</td>
                                <td>{$system_details['c']}</td>
                                <td>{$system_details['c_difficulty']}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <h3><img class='in-line' src='/img/assets/Art/Textures/UI/Overhaul/MapIconSprites/UI_map_jovian_01.png'> Jovian Planets ({$system_details['jovian']})</h3>
                <div class='available-gas'>
                    <table> 
                        <thead>
                            <tr>
                                <th>Gas</th>
                                <th><img class='header-icon' src='/img/assets/Art/Textures/UI/Overhaul/TierSprites/UI_tier_3.png'></th>
                                <th><img class='header-icon' src='/img/assets/Art/Textures/UI/Overhaul/TierSprites/UI_tier_4.png'></th>
                                <th><img class='header-icon' src='/img/assets/Art/Textures/UI/Overhaul/TierSprites/UI_tier_5.png'></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class='' style='background-color: var(--gu-e-transparent);'>
                                <td>E</td>
                                <td>{$system_details['e_split']}</td>
                            </tr>
                            <tr class='' style='background-color: var(--gu-f-transparent);'>
                                <td>F</td>
                                <td>{$system_details['f_split']}</td>
                            </tr>
                            <tr class='' style='background-color: var(--gu-g-transparent);'>
                                <td>G</td>
                                <td>{$system_details['g_split']}</td>
                            </tr>
                            <tr class='' style='background-color: var(--gu-h-transparent);'>
                                <td>H</td>
                                <td>{$system_details['h_split']}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!--<pre id='console-log'>{$system_details_json}</pre><!---->
            </div>
            <div class='xdebug-error'>Data lovingly curated by <a href='https://docs.google.com/spreadsheets/d/1eTCM4KNb7lv7mtFmMx9WVOud2pg7EnqcDP40n9S-5go/edit' target='_blank'>players like you</a>.</div>
        <hr>
        <div id='systems-table-container' style=''>";
$html .= implode($table);
$html .= "</div>";

if (true == false) {
    $table = "google_data_systems";
    $ingest_uri = "https://sheets.googleapis.com/v4/spreadsheets/1eTCM4KNb7lv7mtFmMx9WVOud2pg7EnqcDP40n9S-5go/values/HWM+Website+Ingest!A1:CJ151?key=" . GOOGLE_SHEETS;
    $json = array_filter(json_decode(file_get_contents($ingest_uri))->values);
    $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
                `system_name` VARCHAR(16) PRIMARY KEY,
                `faction` VARCHAR(12),
                `location` VARCHAR(12),
                `level` INT(1),
                `tier` INT(1),
                `liaison_market` INT(1),
                `cangacian` VARCHAR(5),
                `cangacian_total` INT(1),
                `tanoch` VARCHAR(5),
                `tanoch_total` INT(1),
                `yaot` VARCHAR(5),
                `yaot_total` INT(1),
                `amassari` VARCHAR(5),
                `amassari_total` INT(1),
                `kiithless` VARCHAR(5),
                `kiithless_total` INT(1),
                `relic` VARCHAR(5),
                `relic_total` INT(1),
                `progenitor` VARCHAR(5),
                `progenitor_total` INT(1),
                `activities` VARCHAR(5),
                `activities_total` INT(1),
                `distress` VARCHAR(5),
                `distress_total` INT(1),
                `special` VARCHAR(5),
                `special_total` INT(1),
                `trader` VARCHAR(5),
                `trader_total` INT(1),
                `signal_total` INT(2),
                `m` INT(2),
                `a` INT(2),
                `b` INT(2),
                `c` INT(2),
                `belts` INT(2),
                `mx` VARCHAR(17),
                `ax` VARCHAR(17),
                `bx` VARCHAR(17),
                `cx` VARCHAR(17),
                `e` INT(1),
                `f` INT(1),
                `g` INT(1),
                `h` INT(1),
                `jovian` INT(1),
                `ex` VARCHAR(5),
                `fx` VARCHAR(5),
                `gx` VARCHAR(5),
                `hx` VARCHAR(5),
                `rocky` INT(1),
                `terran` INT(1),
                `gas` INT(1),
                `moons` INT(2),
                `last_update` DATE
           ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $api->sql_exec($sql);
    $rows = [];
    foreach ($json as $r => $row) {
        if (0 == $r) continue;
        $new_row = [];
        foreach ($row as $c => $col) $new_row[strtolower(str_replace(search: " ", replace: "_", subject: $json[0][$c]))] = $col;
        $babbler->insert(table: $table, params: $new_row, update: true);
    }
}

return $html;