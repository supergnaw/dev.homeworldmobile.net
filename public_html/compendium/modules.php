<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$nb = new \Supergnaw\Nestbox\Nestbox();

$uri = array_filter(explode("/",$_SERVER['REQUEST_URI']));

$defaultCol = 'module';

$uri[3] = $uri[3] ?? $defaultCol;
$uri[4] = $uri[4] ?? 'asc';

$col = str_replace('-','_',$uri[3]);
$col = ($nb->valid_schema($table,$col)) ? $col : $defaultCol; // <-- somethings up with this and I don't know why

$rarities = [
    0 => "common",
    1 => "uncommon",
    2 => "rare",
    3 => "epic",
    4 => "legendary"
];

$dir = (in_array($uri[4],['asc','desc'])) ? $uri[4] : 'asc';

$sql = "SELECT * FROM `{$table}` ORDER BY `{$col}` {$dir};";
$rows = ($nb->query_execute($sql)) ? $nb->results() : [];

$col = str_replace('-','_',$col) ?? null;
$dir = ('desc' == $dir) ? 'asc' : 'desc';

$output = "
    <table>
        <thead>
            <tr>
                <th>Module <a href='/compendium/{$table}/module/{$dir}/'>&varr;</a></th>
                <th>Size <a href='/compendium/{$table}/size/{$dir}/'>&varr;</a></th>
                <th>Group <a href='/compendium/{$table}/group/{$dir}/'>&varr;</a></th>
            </tr>
        </thead>
        <tbody>";
foreach ($rows as $row) {
    $item = ($nb->query_execute("SELECT * FROM `fabrication` WHERE `item` = :item",['item'=>$row['module']]))
        ? $nb->results(true) : [];
    $rowItem = (!empty($item)) ? "<a href='/abaci/fabrication/{$item['item']}/1/'>{$item['item']}</a>" : $row['module'];
    $output .= "
            <tr>
                <td>{$rowItem}</td>
                <td>{$row['size']}</td>
                <td>{$row['module_group']}</td>
            </tr>";
}
$output .= "
        </tbody>
    </table>";

echo $output;


$string_data = $api->get_title_data(keys: "StringData");
$item_data = $api->get_title_data(keys: "ItemData");

$active_internal = '';
$active_external = '';
$active_orbital = '';

$table = [];
$body = "";

$row_classes = [];

if ('internal' == ($uri[2] ?? '')) {
    $sql = "SELECT *
                FROM `playfab_data_internal_module_data`
                LEFT JOIN `playfab_data_item_data` USING (`data_id`)
                LEFT JOIN `playfab_data_string_data` ON `playfab_data_string_data`.`data_id` = REGEXP_REPLACE(`playfab_data_internal_module_data`.`data_id`, '(_[curel]_t[0123456789]|_t[0123456789])$', '_tx')
                WHERE
                    `valid_compartments` LIKE '%Front%'
                    OR `valid_compartments` LIKE '%Middle%'
                    OR `valid_compartments` LIKE '%Back%';";

    $sql = "SELECT
                    `s1`.`data_id`,
                    `s1`.`en` as 'item_name',
                    `s2`.`en` as 'item_description'
                FROM `playfab_data_string_data` `s1`
                LEFT JOIN `playfab_data_string_data` `s2`
                ON `s2`.`data_id` = CONCAT('desc_', `s1`.`data_id`);";

    $sql = "SELECT *, REGEXP_REPLACE(`item_data`.`cost_id1`, '^curr_', '') as `temp_currency_test1`
                FROM `playfab_data_internal_module_data` `module_data`
                LEFT JOIN `playfab_data_item_data` `item_data` USING (`data_id`)
                LEFT JOIN (
                    SELECT
                        `s1`.`data_id` as `str_data_id`,
                        `s1`.`en` as 'item_name',
                        `s2`.`en` as 'item_description'
                    FROM `playfab_data_string_data` `s1`
                    LEFT JOIN `playfab_data_string_data` `s2`
                    ON `s2`.`data_id` = CONCAT('desc_', `s1`.`data_id`)
                ) `string_data_1` ON `string_data_1`.`str_data_id` = REGEXP_REPLACE(`module_data`.`data_id`, '(_[curel]_t[0123456789]|_t[0123456789])$', '_tx')
                LEFT JOIN (
                    SELECT
                        `s1`.`data_id` as `cost_id_1`,
                        `s1`.`en` as 'cost_name1',
                        `s2`.`en` as 'cost_description1'
                    FROM `playfab_data_string_data` `s1`
                    LEFT JOIN `playfab_data_string_data` `s2`
                    ON `s2`.`data_id` = CONCAT('desc_', `s1`.`data_id`)
                ) `string_data_2` ON `string_data_2`.`cost_id_1` = REGEXP_REPLACE(`item_data`.`cost_id1`, '^curr_', '')
                LEFT JOIN (
                    SELECT
                        `s1`.`data_id` as `cost_id_2`,
                        `s1`.`en` as 'cost_name2',
                        `s2`.`en` as 'cost_description2'
                    FROM `playfab_data_string_data` `s1`
                    LEFT JOIN `playfab_data_string_data` `s2`
                    ON `s2`.`data_id` = CONCAT('desc_', `s1`.`data_id`)
                ) `string_data_3` ON `string_data_3`.`cost_id_2` = REGEXP_REPLACE(`item_data`.`cost_id2`, '^curr_', '')
                LEFT JOIN (
                    SELECT
                        `s1`.`data_id` as `cost_id_3`,
                        `s1`.`en` as 'cost_name3',
                        `s2`.`en` as 'cost_description3'
                    FROM `playfab_data_string_data` `s1`
                    LEFT JOIN `playfab_data_string_data` `s2`
                    ON `s2`.`data_id` = CONCAT('desc_', `s1`.`data_id`)
                ) `string_data_4` ON `string_data_4`.`cost_id_3` = REGEXP_REPLACE(`item_data`.`cost_id3`, '^curr_', '')
                WHERE
                (
                    `valid_compartments` LIKE '%Front%'
                    OR `valid_compartments` LIKE '%Middle%'
                    OR `valid_compartments` LIKE '%Back%'
                )
                AND (
                    `item_data`.`tier` < 5
                    AND `item_data`.`tier` > 0
                    AND `string_data_1`.`item_name` IS NOT NULL
--                    `string_data_1`.`item_name` IS NULL
                )
                ORDER BY
                    `item_data`.`tier` ASC,
                    `item_name` ASC,
                    `rarity` ASC;";
    $api->sql_exec($sql);
    $results = $api->results();
    $test_table = [];
    $table = [];
    $row_classes = [];

    $icons = [
        'front' => "<img class='in-line yes' src='/img/Assets/Art/Textures/UI/Overhaul/InternalFlagshipMenuSprites/CompartmentIcon_Front.png'>&nbsp;",
        'middle' => "<img class='in-line yes' src='/img/Assets/Art/Textures/UI/Overhaul/InternalFlagshipMenuSprites/CompartmentIcon_Middle.png'>&nbsp;",
        'back' => "<img class='in-line yes' src='/img/Assets/Art/Textures/UI/Overhaul/InternalFlagshipMenuSprites/CompartmentIcon_Back.png'>",
    ];

    foreach ($results as $result) {
        $row_classes[] = $rarities[$result['rarity'] ?? 0];
        $test_table[] = [
//                'data_id' => $result['data_id'],
            'item_name' => $result['item_name'],
            'item_description' => $result['item_description'],
//                'module_class' => $result['module_class'],
            'tier' => $result['tier'],
            'compartments' => $result['valid_compartments'],
//                'base_price' => $result['base_price'],
//                'cost_id1' => $result['cost_id1'],
//                'cost_name1' => $result['cost_name1'],
//                'cost_val1' => $result['cost_val1'],
//                'cost_name2' => $result['cost_name2'],
//                'cost_val2' => $result['cost_val2'],
//                'cost_name3' => $result['cost_name3'],
//                'cost_val3' => $result['cost_val3'],
//                'time' => $result['time'],
            'not_buildable' => $result['not_buildable'],
        ];
        $table[] = [
            'Name' => $result['item_name'],
            'Tier' => $result['tier'],
            'Description' => $result['item_description'],
//                'Size' => $result['size'],
//                'Compartments' => (str_contains($result['valid_compartments'], needle: 'Front') ? $icons['front'] : '')
//                    . (str_contains($result['valid_compartments'], needle: 'Middle') ? $icons['middle'] : '')
//                    . (str_contains($result['valid_compartments'], needle: 'Back') ? $icons['back'] : ''),
            'Compartments' =>
                "<img class='in-line " . (str_contains($result['valid_compartments'], needle: 'Front') ? 'no' : 'yes') . "' src='/img/Assets/Art/Textures/UI/Overhaul/InternalFlagshipMenuSprites/CompartmentIcon_Front.png'>&nbsp;" .
                "<img class='in-line " . (str_contains($result['valid_compartments'], needle: 'Middle') ? 'no' : 'yes') . "' src='/img/Assets/Art/Textures/UI/Overhaul/InternalFlagshipMenuSprites/CompartmentIcon_Middle.png'>&nbsp;" .
                "<img class='in-line " . (str_contains($result['valid_compartments'], needle: 'Back') ? 'no' : 'yes') . "' src='/img/Assets/Art/Textures/UI/Overhaul/InternalFlagshipMenuSprites/CompartmentIcon_Back.png'>&nbsp;",
            'Buildable' => ('false' == $result['not_buildable']) ? "<img class='in-line' src='/img/yes.png'>" : "<img class='in-line' src='/img/no.png'>",
        ];
    }
    echo $api->html_table(table: $table, row_class: $row_classes);
    echo $api->html_table($results);
    die;

    $active_internal = 'active';
    $module_data = $api->get_title_data(keys: "InternalModuleData");
    foreach ($module_data as $module_id => $module_datum) {
        if (str_ends_with(haystack: $module_id, needle: "t5")) continue;
        if (!str_contains(haystack: "{$module_datum->valid_compartments}", needle: 'Front')
            and !str_contains(haystack: "{$module_datum->valid_compartments}", needle: 'Middle')
            and !str_contains(haystack: "{$module_datum->valid_compartments}", needle: 'Back')) {
//                echo "Skipping {$module_id}: {$module_datum->valid_compartments}<br>";
            continue;
        }
        $string_key = (!property_exists($string_data, $module_id))
            ? preg_replace(pattern: '/(.*_t)\d$/', replacement: '$1X', subject: $module_id)
            : $module_id;
        $string_key = (!property_exists($string_data, $module_id))
            ? preg_replace(pattern: '/(.*_t)\d$/', replacement: '$1x', subject: $module_id)
            : $module_id;
        $string_key = (!property_exists($string_data, $string_key))
            ? preg_replace('/_[urel]_(tx$)/i', '$2_$1', $string_key)
            : $string_key;
        if (!property_exists($string_data, $string_key)) {
//                echo "Skipping $module_id | $string_key<br>";
            continue;
        }

//            preg_match(pattern: '/^.*_([urel])_t\d$/', subject: $module->data_id, matches: $rarity);


        $module = (object)array_merge((array)$module_data->$module_id, (array)$string_data->$string_key, (array)$item_data->$module_id);
        $module->identifier = $module_id;
        $module->description = $string_data->{"desc_{$string_key}"}->en;
        $body .= "<hr><pre>";
        $body .= print_r($module, true);
        $body .= "</pre>";
        $table[] = [
            'Module Name' => $module->en,
            'Tier' => $module->tier,
            'Rarity' => $rarities[$module->rarity ?? 0],
//                'Data ID' => $module->data_id,
//                'Description' => $module->description,
            'Size' => $module->size,
            'Compartments' => $module->valid_compartments,
        ];
        $row_classes[] = $rarities[$module->rarity ?? 0];
    }
} elseif ('external' == ($uri[2] ?? '')) {
    $active_external = 'active';
    $module_data = $api->get_title_data(keys: "InternalModuleData");
} elseif ('orbital' == ($uri[2] ?? '')) {
    $active_orbital = 'active';
    $module_data = $api->get_title_data(keys: "InternalModuleData");
} else {
    $body .= "<img src='/img/guidestone.jpg'  style='width: 100%;max-width: 100%;'>";
}

$body = $api->html_table(table: $table, row_class: $row_classes) . $body;

$html = "<hr>
        <div class='hw-nav col3'>
            <a href='/compendium/modules/internal' class='{$active_internal}'>
                <div class='btn'><span class='icon-internal'></span>Internal</div>
            </a>
            <a href='/compendium/modules/external' class='{$active_external}'>
                <div class='btn'><span class='icon-external'></span>External</div>
            </a>
            <a href='/compendium/modules/orbital' class='{$active_orbital}'>
                <div class='btn'><span class='icon-orbital'></span>Orbital</div>
            </a>
        </div>
        {$body}";

return $html;