<?php

declare(strict_types=1);


require_once(implode(DIRECTORY_SEPARATOR, [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

function fetch_blueprint(string $item): array
{
    $nb = new \Supergnaw\Nestbox\Nestbox();
    $sql = "SELECT * FROM `fabrication` WHERE `item` = :item;";
    return ($nb->query_execute($sql,['item'=>$item])) ? $nb->results(true) : [];
}

function fetch_fabrication(string $item, int $quantity = 1): array
{
    $blueprint = fetch_blueprint($item);

    $output = [
        'components' => [],
        /** COMPONENTS LAYOUT
         * 'Image'=>"<img src='/img/fabrication/{$type}/{$img}' style='height:100px;'>"
         * ,'Component'=>$item
         * ,'Quantity'=>number_format($qty)
         * ,'Total'=>number_format($qty*$post['qty'])
         * ,'Minimum'=>number_format(round_up_to_any($qty*$post['qty'],25))
         * ,'Fabrication Runs'=>number_format(ceil($qty*$post['qty']/25))
         **/
        'resources' => []
        /** RESOURCES LAYOUT
         * 'Image'=>"<img src='/img/resources/refined/{$img}' style='height:100px;'>"
         * ,'Resource'=>$item
         * ,'Quantity'=>number_format($qty)
         * ,'Total'=>number_format($qty*$post['qty'])
         * ,'Minimum'=>number_format(round_up_to_any($qty*$post['qty'],100))
         * ,'Raw Ore'=>number_format(round_up_to_any($qty*$post['qty']*2,200))
         **/
    ];

    if (empty($blueprint)) return [];

    // calculate base requirements for single item
    if (!empty($blueprint)) {
        for ($i = 1; $i <= 3; $i++) {
            if (!is_null($blueprint["material_{$i}"])) {
                // clean up variables for easier to read code
                $mat = $blueprint["material_{$i}"];
                $qty = $blueprint["quantity_{$i}"];
                $type = (1 === preg_match("/^RU [ABCD] I+$/i", $blueprint["material_{$i}"])) ? 'resources' : 'components';

                // initialize quantity
                if (!array_key_exists($mat, $output[$type])) {
                    $output[$type][$mat]['Quantity'] = 0;
                    $output[$type][$mat]['Total'] = 0;
                    $output[$type][$mat]['Minimum'] = 0;
                    $output[$type][$mat]['Fabrication Runs'] = 0;
                }

                // calculate quantities for materials
                $output[$type][$mat]['Quantity'] += $qty * $quantity;
                $output[$type][$mat]['Total'] += 0;
                $output[$type][$mat]['Minimum'] += 0;
                $output[$type][$mat]['Fabrication Runs'] = 0;

                // if item is not resources, we must go deeper
                if ('components' === $type) {
                    // get the next item
                    $nextQty = (int)ceil($qty / 25);
                    $suboutput = fetch_fabrication(
                        $blueprint["material_{$i}"],
                        $nextQty
                    );

                    // for each type group of items
                    foreach ($suboutput as $t => $items) {
                        // if items is not empty
                        if ($items) {
                            // for each item amount collection
                            foreach ($items as $item => $amnts) {
                                // initialize quantity value for new materials
                                if (!array_key_exists($item, $output[$t])) {
                                    $output[$t][$item]['Quantity'] = 0;
                                    $output[$t][$item]['Total'] = 0;
                                    $output[$t][$item]['Minimum'] = 0;
                                    $output[$t][$item]['Fabrication Runs'] = 0;
                                }

                                // add submaterial quantities to output
                                $output[$t][$item]['Quantity'] += $suboutput[$t][$item]['Quantity'] * $quantity;
                            }
                        }
                    }
                }
            }
        }
    }

    // loop through final output, calculate minimum fabrications, and adjust RUs accordingly
    return $output;
}

function fetch_parent_products(string $item): array
{
    $nb = new \Supergnaw\Nestbox\Nestbox();
    $sql = "SELECT * FROM `fabrication`
            WHERE `material_1` = :item
               OR `material_2` = :item
               OR `material_3` = :item
            ORDER BY `item` ASC;";
    $usedIn = ($nb->query_execute($sql,['item'=>$item])) ? $nb->results() : [];

    $output = [];

    foreach ($usedIn as $row) {
        $imgType = strtolower($row['build_type']);
        $imgItem = preg_replace("/[^A-Za-z0-9]/","",$row['item']);
        $output[] = [
            'Image' => "<img src='/img/fabrication/{$imgType}/{$imgItem}.png' style='max-height: 100px;'>",
            'Blueprint' => "<a href='/abaci/fabrication/{$row['item']}/1/'>{$row['item']}</a>",
            'Required' => $row['quantity_1'],
        ];
    }

    return $output;
}

function calculate_built_requirement_quantities(array $requirements, int $qty): array
{
    $output = [
        'components' => [],
        'resources' => []
    ];

    // calcualte required fabrication runs
    foreach ($requirements['components'] as $item => $quantities) {
        $output['components'][$item]['Quantity'] = $quantities['Quantity'];
        $output['components'][$item]['Total'] = $quantities['Quantity'] * $qty;
        $output['components'][$item]['Minimum'] = round_up_to_any($output['components'][$item]['Total'],25);
        $output['components'][$item]['Fabrication Runs'] = ceil($output['components'][$item]['Minimum']/25);
    }

    // calculate required refined and raw ore amounts
    foreach ($requirements['resources'] as $item => $quantities) {
        $output['resources'][$item]['Quantity'] = $quantities['Quantity'];
        $output['resources'][$item]['Total'] = $quantities['Quantity'] * $qty;
        $output['resources'][$item]['Minimum'] = round_up_to_any($output['resources'][$item]['Total'],100);
        $output['resources'][$item]['Raw Ore'] = round_up_to_any($output['resources'][$item]['Minimum']*2,200);
    }

    return $output;
}

function format_fabrication(string $product, int $qty): array
{
    $materials = fetch_fabrication($product);
    if (empty($materials)) {
        return [];
    } else {
        $materials = calculate_built_requirement_quantities($materials, $qty);
    }

    $output = [
        'components' => [],
        'resources' => []
    ];

    // prepare and format final output
    foreach ($materials['components'] as $item => $quantities) {
        // get component type
        $bp = fetch_blueprint($item);
        $type = strtolower($bp['build_type']);

        // format the image
        $img = preg_replace("/[^A-Za-z0-9]/","",$item) . ".png";
        $output['components'][$item] = [
            'Image' => "<img src='/img/fabrication/parts/{$img}' style='height:100px;'>"
            , 'Component' => "<a href='/abaci/fabrication/{$item}/{$quantities['Fabrication Runs']}/'>{$item}</a>"
            , 'Quantity' => number_format($quantities['Quantity'])
            , 'Total' => number_format($quantities['Total'])
            , 'Minimum' => number_format($quantities['Minimum'])
            , 'Fabrication Runs' => number_format($quantities['Fabrication Runs'])
        ];
    }

    foreach ($materials['resources'] as $item => $quantities) {
        $img = preg_replace("/[^A-Za-z]/","",$item) . ".png";
        $output['resources'][$item] = [
            'Image' => "<img src='/img/resources/refined/{$img}' style='height:100px;'>"
            , 'Resource' => $item
            , 'Quantity' => number_format($quantities['Quantity'])
            , 'Total' => number_format($quantities['Total'])
            , 'Minimum' => number_format($quantities['Minimum'])
            , 'Raw Ore' => number_format($quantities['Raw Ore'])
        ];
    }

    return $output;
}

function round_up_to_any(int|float $n, int $x=5): float
{
    return (round($n)%$x === 0) ? round($n) : round(($n+$x/2)/$x)*$x;
}

$nb = new \Supergnaw\Nestbox\Nestbox();
$requirements = null;

// url directed calculations
if (isset($uri[2]) && isset($uri[3])) {
    $blueprintSelect = urldecode($uri[2]);
    $bp = fetch_blueprint($blueprintSelect);
    $typeSelect = $bp['build_type'];
    $qty = (int)$uri[3];

    $requirements = format_fabrication($blueprintSelect,$qty);
}

// post directed calculations
if (!empty($_POST)) {
    $postFilter = [
        'typeSelect' => 'string',
        'blueprintSelect' => 'string',
        'qty' => 'int'
    ];
    $post = \app\FormSecurity\FormSecurity::filter_post($postFilter);
    $typeSelect = $post['typeSelect'] ?? 'Flagship';
    $blueprintSelect = $post['blueprintSelect'] ?? 'Carrier I';
    $qty = $post['qty'] ?? 1;

    $requirements = format_fabrication($blueprintSelect, (int)$qty);
}

if (!empty($requirements)) {
    $img = preg_replace("/[^A-Za-z0-9]/","",$blueprintSelect);
    $img = str_replace(['Advanced','Elite','Epic'],"",$img);
    $dir = preg_replace("/[^a-z0-9]/","",strtolower($typeSelect));

    $parentProducts = fetch_parent_products($blueprintSelect);

    $output = [
        "<h2>{$blueprintSelect} (x{$qty})</h2>",
        "<div class='hw-blue-box'><p style='text-align: center;'><img src='/img/fabrication/{$dir}/{$img}.png' style='max-width: 100%;'></p></div>",
        "<h3>Sub Compoments</h3>",
        (!empty($requirements['components'])) ? array_2_table($requirements['components'],false) : "<p>No component requirements available</p>",
        "<h3>Resources</h3>",
        (!empty($requirements['resources'])) ? array_2_table($requirements['resources'],false) : "<p>No resource requirements available</p>",
        "<h3>Used In</h3>",
        (!empty($parentProducts)) ? array_2_table($parentProducts,false) : "<p>Not used in production of other items.</p>",
    ];

    $output = implode($output);
} else {
    $typeSelect = 'Flagship';
    $blueprintSelect = 'Carrier I';
    $qty = 1;
    $output = "";
}

$sql = "SELECT
            *,
            COALESCE(`fleet_type`, `build_type`) AS `build_type`
        FROM `fabrication`
        LEFT JOIN `fleet` ON `item` = `ship_name` 
        LEFT JOIN (
            SELECT
                COALESCE(`fleet_type` , `build_type`) AS `type`
                , COUNT(*) as `count`
            FROM `fabrication`
            LEFT JOIN `fleet` ON `item`=`ship_name`
            GROUP BY `type`
        ) `type_count` ON `type_count`.`type` = `build_type`
        ORDER BY `item` ASC;";
$rows = ($nb->query_execute($sql)) ? $nb->results() : [];

$blueprintOptions = [];
$bpJSON = [];
foreach ($rows as $row) {
    // blueprint types
    $typeSelected = ($row['build_type'] == $typeSelect) ? 'selected' : '';
    $typeOptions[$row['build_type']] = "<option value='{$row['build_type']}' {$typeSelected}>{$row['build_type']} ({$row['count']})</option>";

    if ($typeSelect == $row['build_type']) {
        $blueprintOptions[] = ($row['item'] == $blueprintSelect)
            ? "<option value='{$row['item']}' selected>{$row['item']}</option>"
            : "<option value='{$row['item']}'>{$row['item']}</option>";
    }

    $bpJSON[$row['build_type']][$row['item']] = ($blueprintSelect == $row['item']) ? 'selected' : '';
}

$typeOptions = implode("\n", $typeOptions);
$blueprintOptions = implode("\n",$blueprintOptions);
$bpJSON = json_encode($bpJSON);

echo "
    <h2>Fabrication Calculator</h2>
    <form method='post' id='fabrication-calculator' action='/{$uri[0]}/{$uri[1]}/'>
        <div class='grid col3'>
            <div class='no-margin'>
                <label for='typeSelect'>Type</label><br>
                <select name='typeSelect' id='typeSelect'>
                    {$typeOptions}
                </select>
            </div>
            <div class='no-margin'>
                <label for='blueprintSelect'>Blueprint</label><br>
                <select name='blueprintSelect' id='blueprintSelect'>
                    {$blueprintOptions}
                </select>
            </div>
            <div class='no-margin'>
                <label for='qty'>Quantity</label><br>
                <input type='number' name='qty' id='qty' value='{$qty}' min='1'>
            </div>
        </div>
        <div class='hw-nav col3'>
            <div class='hide'></div>
            <a href='#' id='form-btn' onclick=\"submit_form('fabrication-calculator')\"><div id='form-btn-txt' class='btn'>Calculate</div></a>
        </div>
        <script>
            var bpJSON = {$bpJSON};
            
            document.getElementById('typeSelect').onchange = function() {
                let typeItems = this.options[this.selectedIndex].value;
                console.log(typeItems);
                console.log(bpJSON[typeItems]);
                let items = bpJSON[typeItems];
                
                let bpSelect = document.getElementById('blueprintSelect');
                var i, L = bpSelect.options.length - 1;
                for(i = L; i >= 0; i--) {
                    bpSelect.remove(i);
                }

                Object.keys(items).forEach(key => {
                    console.log(key, items[key]);
                    bpSelect.options[bpSelect.options.length] = new Option(key,key);                   
                });
            };
        </script>
        <div class='hw-border-box'><p><p>This calculator uses the values from the <a href='/compendium/fabrication/'>fabrication</a> compendium table. Minimum numbers are calculated based on fabrication and refining quantity limitations. Your mileage may vary depending on pre-existing items in your inventory.</p></div>
    </form>
    {$output}";