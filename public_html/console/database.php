<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

function snake_to_normal(string $input): string
{
    $output = [];
    foreach (explode("_", $input) as $word) {
        $output[] = ucfirst($word);
    }

    return implode(" ", $output);
}

$nb = new \Supergnaw\Nestbox\Nestbox();

$uri = array_filter(explode("/", $_SERVER['REQUEST_URI']));

$loadForm = "";

$checkboxes = "";
foreach (array_keys($nb->table_schema()) as $table) {
    $checkboxes .= "<label for='{$table}'><input type='checkbox' id='{$table}' name='tables[]' value='{$table}'> " . snake_to_normal($table) . "</label>";
}

if (!$_POST || ($_POST['action'] ?? "") != 'export_json') {
    unset($_SESSION['database_dump_json']);
}
$exportOutput = ($_SESSION['database_dump_json'] ?? "")
    ? "<label for='export_output'>Output JSON</label><textarea id='export_output' style='height: 500px; font-size: 10px; line-height: 10px;'>{$_SESSION['database_dump_json']}</textarea><hr>"
    : "";

$html = "
    <h2>Database Porting</h2>
    
    <div class='grid col2'>
        <div>        
            <h3>Export</h3>
            {$exportOutput}
            <form id='export_json' method='post'>
                <input type='hidden' name='action' value='export_json'>
                {$checkboxes}
                <div class='grid'>
                    <input type='button' value='Export JSON' onclick='submit_form(\"export_json\")'>
                </div>
            </form>
        </div>
        
        <div>
            <h3>Import</h3>
            <form id='import_json' method='post' enctype='multipart/form-data'>
                <input type='hidden' name='action' value='import_json'>
                <label for='input_file'>Input JSON</label>
                <textarea id='input_text' name='input_text' style='height: 500px; font-size: 10px; line-height: 10px;'></textarea>
                <div class='grid'>
                    <input type='button' value='Import JSON' onclick='submit_form(\"import_json\")'>
                </div>
            </form>
        </div>
    </div>
    ";

return $html;