<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$api = new \app\PlayFab\PlayFab(PLAYFAB_APP_ID);

$uri = array_filter(explode("/", $_SERVER['REQUEST_URI']));

$imgs = [];
$glob = glob("img/research/*");
$img_path = "img/research/";
foreach ($glob as $img) {
    preg_match(pattern: '/.*\/([^\/]*)\.png/', subject: $img, matches: $matches);
    $imgs[$matches[1]] = $matches[0];
}

$categories = [];
$research_projects = [];

$research_data = $api->get_title_data(keys: "ResearchData");
$string_data = $api->get_title_data(keys: "StringData");
$item_data = $api->get_title_data(keys: "ItemData");

$output = [];
var_dump($string_data);

foreach ($research_data as $key => $item) {

    $string_key = (!property_exists($string_data, $key))
        ? preg_replace(pattern: '/(.*_t)\d$/', replacement: '$1x', subject: $key)
        : $key;

    if (str_ends_with(haystack: $key, needle: "t5")) continue;
    if (str_contains(haystack: $key, needle: "_debug_")) continue;
    if (empty($item_data->$key->icon_override ?? '') and false) continue;
    var_dump($string_key);
    /////// THERE IS A PROBLEM HERE
    /// string_data apparently never has $string_key so it defaults to skipping the item
    if (str_starts_with(haystack: $string_data->$string_key->en ?? "OLD", needle: "OLD")) continue;


    $tech = (object)array_merge((array)$research_data->$key, (array)$string_data->$string_key, (array)$item_data->$key);
    $tech->identifier = $key;
    $tech->name = $string_data->$string_key->en;
    $tech->description = $string_data->{"desc_{$string_key}"}->en;
    // parse the time
    $tech->time_parsed = parse_seconds($tech->time);
    // parse requirements
    $tech->requirements_raw = $tech->requirements ?? null;
    $tech->requirements = parse_requirements($tech->requirements_raw ?? "");
    foreach ($tech->requirements as $req_id => $level) {
        if (!$req_id) continue;
        $req_key = (!property_exists($string_data, $req_id))
            ? preg_replace('/(.*_t)\d$/', '$1x', $req_id)
            : $req_id;
        preg_match('/_t(\d)(?!=[_$])/', $req_id, $tier);
        $tech->requirements[$req_id] = (object)[
            'name' => $string_data->{$req_key}->en ?? "-Missing Data-",
            'tier' => $tier[1],
            'level' => $level
        ];
    }
    $tech->rarity = $tech->rarity ?? '0';
    $tech->rarity_name = $rarities[$tech->rarity]; // <-- this doesn't exist? where did it go????

    $tech->tier = $tech->tier ?? '1';
    $tech->scaling_mod = $tech->scaling_mod ?? '1';
    $tech->duration_scaling_mod = $tech->duration_scaling_mod ?? '1';

    $level_table = [];
    for ($l = 0; $l < 5; $l++) {
        $lvl = $l + 1;
        $cr = round($tech->c_r_val * ($tech->duration_scaling_mod ** $l));
        $cv1 = round($tech->cost_val1 * ($tech->duration_scaling_mod ** $l));
        $t = parse_seconds(intval(round($tech->time * ($tech->duration_scaling_mod ** $l))));
        if ($l < $tech->levels) {
            $level_table[] = "<tr><td>{$lvl}</td><td>{$cr}</td><td>{$cv1}</td><td>{$t}</td></tr>";
        } else {
//                    $level_table[] = "<tr><td>{$lvl}</td><td><img src='/img/ui/no.png'></td><td><img src='/img/ui/no.png'></td><td><img src='/img/ui/no.png'></td></tr>";
        }

    }

    for ($i = 1; $i < 4; $i++) {
        $tech->{"cost_id_{$i}"} = $tech->{"cost_id{$i}"} ?? "";
        $tech->{"cost_id_{$i}_abbrv"} = trim($tech->{"cost_id_{$i}"}, "curr_") ?? "";
        $tech->{"cost_id_{$i}_name"} = $string_data->{$tech->{"cost_id_{$i}_abbrv"}}->en ?? "";
        $tech->{"cost_id_{$i}_description"} = $string_data->{"desc_" . trim($tech->{"cost_id_{$i}"}, "curr_")}->en ?? "";
    }

    $img_full_path = "{$img_path}/{$tech->icon_override}.png";
    $output[$img_full_path] = $output[$img_full_path] ?? [];
    if (!in_array("<tr><th colspan='4'>{$tech->name}</th></tr>", $output[$img_full_path]['table'] ?? [])) {
        $output[$img_full_path]['table'] = ["<tr><th colspan='4'>{$tech->name}</th></tr>"];
    }
    $output[$img_full_path]['table'][] = "<tr>
                                <td><img class='{$tech->rarity_name} in-line' src='/img/ui/tier/{$tech->tier}.png'></td>
                                <td><img class='in-line' src='/img/ui/currency/CR.png'></td>
                                <td><img class='in-line' src='/img/ui/currency/RP.png'></td>
                                <td><img class='in-line' src='/img/ui/currency/time.png'></td>
                            </tr>";
    $output[$img_full_path]['table'] = array_merge($output[$img_full_path]['table'], $level_table);
    $output[$img_full_path]['description'] = $tech->description;

    $research_projects[$tech->category][$tech->name]['tier'][] = $tech->tier;
    unset($tech->data_type);

    $tech = json_decode(json_encode($tech), true);
    ksort($tech);
    $tech = json_decode(json_encode($tech));

    if ($item->icon_override ?? false) {
        // blacklist elements
        if (str_contains($key, "_debug_")) continue;
        if (str_ends_with($key, "t5")) continue;


        if (str_starts_with(haystack: $string_data->$strKey->en ?? "OLD", needle: "OLD")) continue;

        $research_projects[$tech->Category][$tech->Name]['Tier'][] = $tech->Tier;
        // clear out unused properties
        unset($tech->DataType);

        $tech = json_decode(json_encode($tech), true);
        ksort($tech);
        $tech = json_decode(json_encode($tech));

        $level_table = [];
        for ($l = 0; $l < 5; $l++) {
            $lvl = $l + 1;
            $cr = round($tech->c_r_val * ($tech->scaling_mod ** $l));
            $cv1 = round($tech->cost_val1 * ($tech->scaling_mod ** $l));
            $t = parse_seconds(intval(round($tech->Time * ($tech->scaling_mod ** $l))));
            if ($l < $tech->Levels) {
                $level_table[] = "<tr><td>{$lvl}</td><td>{$cr}</td><td>{$cv1}</td><td>{$t}</td></tr>";
            }

        }

        $output[$imgs[$item->icon_override]] = $output[$imgs[$item->icon_override]] ?? [];
        if (!in_array("<tr><th colspan='4'>{$tech->Name}</th></tr>", $output[$imgs[$item->icon_override]])) {
            $output[$imgs[$item->icon_override]][] = "<tr><th colspan='4'>{$tech->Name}</th></tr>";
        }
        $output[$imgs[$item->icon_override]][] = "
                            <tr>
                                <td><img class='{$tech->RarityName} in-line' src='/img/ui/tier/{$tech->Tier}.png'></td>
                                <td><img class='in-line' src='/img/ui/currency/CR.png'></td>
                                <td><img class='in-line' src='/img/ui/currency/RP.png'></td>
                                <td><img class='in-line' src='/img/ui/currency/time.png'></td>
                            </tr>";
        $output[$imgs[$item->icon_override]] = array_merge($output[$imgs[$item->icon_override]], $level_table);
    }
}

$html = "<div style='display: grid; grid-template-columns: 60% 40%;'>";

foreach ($output as $icon => $data) {
    $levels_table = implode($data['table']);
    $html .= "
            <div>
                <div class='hw-blue-box'>
                    <table style=''>
                        <thead>
                        </thead>
                        <tbody>
                        {$levels_table}
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <div class='hw-inner-box hw-research-box' style='position: relative;'>
                    <img src='/{$icon}' class='hw-research-img'>
                    <div class='hw-research-text'>{$data['description']}</div>
                </div>
            </div>";
}
$html .= "</div>";

return $html;