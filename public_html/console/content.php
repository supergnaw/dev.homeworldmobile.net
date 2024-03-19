<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$babbler = new \app\Nestbox\Babbler\Babbler();

$uri = array_filter(explode("/", $_SERVER['REQUEST_URI']));

$defaultCol = 'item_name';

$html = "";

if ("edit" == ($uri[3] ?? "")) {
    // initiate form default values
    $save_disabled = "disabled";
    if (empty($_POST)) {
        // if not a preview
        $entry = ($uri[4] ?? false)
            ? $babbler->fetch_entry(intval($uri[4]))
            : [
                "title" => '',
                "category" => '',
                "sub_category" => '',
                "content" => '',
            ];
        if (preg_match("/^(\d{3})\.(\d{3})\.(\d{3})$/", $entry['sub_category'], $version)) {
            $entry['major_version'] = intval($version[1]);
            $entry['minor_version'] = intval($version[2]);
            $entry['patch_version'] = intval($version[3]);
        } else {
            $entry['major_version'] = 0;
            $entry['minor_version'] = 0;
            $entry['patch_version'] = 0;
        }
        $entry['page_id'] = $entry['entry_id'] ?? 0;
    } else {
        // if a preview
        $vars = [
            "action" => 'string',
            "page_id" => 'int',
            "page_title" => 'string',
            "page_category" => 'string',
            "page_sub_category" => 'string',
            "page_content" => 'string',
            "is_draft" => 'string',
            "publish_date_type" => 'string',
            "publish_date" => 'date',
            "publish_time" => 'time',
            "major_version" => 'int',
            "minor_version" => 'int',
            "patch_version" => 'int'
        ];
        $post = \app\FormSecurity\FormSecurity::filter_post($vars);
        $entry = [
            "title" => $post["page_title"] ?? '',
            "category" => $post["page_category"] ?? '',
            "sub_category" => $post["page_sub_category"] ?? '',
            "content" => $post["page_content"] ?? '',
            "publish_date_type" => $post["publish_date_type"] ?? '',
            "publish_date" => $post["publish_date"] ?? '',
            "publish_time" => $post["publish_time"] ?? '',
            "major_version" => intval($post['major_version']),
            "minor_version" => intval($post['minor_version']),
            "patch_version" => intval($post['patch_version']),
            "page_id" => intval($post['page_id']),
        ];
        if ("page_preview" == $post['action']) {
            $save_disabled = "";
        }
    }

    $is_draft = ("is_draft" == ($post['is_draft'] ?? false)) ? "checked" : '';
    $draft_disable = ("" == $is_draft) ? "" : "disabled";

    // set default categories
    $categories = [
        "Patch Notes" => [],
        "FAQ" => ["General", "Missions", "Officers", "Research", "Upgrades", "Mining", "Fabrication", "Strikes"],
        "Gameplay" => ["Missions", "Officers", "Research", "Upgrades", "Mining", "Fabrication", "Strikes"],
        "Lore" => ["Hiigaran", "Iyatequa", "Tanoch", "Yaot", "Amassari", "Cangacian", "Kiithless", "Progenitor", "Ghost Stories"],
    ];
    $sub_categories = [];

    $categories_json = json_encode($categories);

    $category_select = "<select style='grid-column: 0/4;' id='page_category' name='page_category'>\n";
    foreach ($categories as $category => $this_sub_categories) {
        $category_select .= ($category == $entry['category'])
            ? "            <option value='{$category}' selected>{$category}</option>\n"
            : "            <option value='{$category}'>{$category}</option>\n";
        if ($category == $entry['category']) {
            $sub_categories = $this_sub_categories;
        }
    }
    $category_select .= "        </select>";

    $sub_category_select = (0 < count($sub_categories))
        ? "<select id='page_sub_category' name='page_sub_category'>\n"
        : "<select id='page_sub_category' name='page_sub_category' disabled>\n";
    foreach ($sub_categories as $sub_category) {
        $sub_category_select .= ($sub_category == $entry['sub_category'])
            ? "            <option value='{$sub_category}' selected>{$sub_category}</option>\n"
            : "            <option value='{$sub_category}'>{$sub_category}</option>\n";
    }
    $sub_category_select .= "        </select>";

    $html = "
        <form id='page-edit' method='post' action='/console/content/'>
            <div class='grid'>
                <label for='page_category'>Category</label>
                {$category_select}
            </div>
            <div class='grid' id='sub-category-container'>
                <label for='page_sub_category' id='page_sub_category_label'>Sub-Category</label>
                {$sub_category_select}
            </div>
            <div class='grid' id='version-container'>
                <fieldset>
                    <legend>Version Number</legend>
                    <div class='grid col3'>
                        <label for='major_version'>Major</label>
                        <label for='minor_version'>Minor</label>
                        <label for='patch_version'>Patch</label>
                        <input type='number' id='major_version' name='major_version' min=0 value={$entry['major_version']} />
                        <input type='number' id='minor_version' name='minor_version' min=0 value={$entry['minor_version']} />
                        <input type='number' id='patch_version' name='patch_version' min=0 value={$entry['patch_version']} />
                    </div>
                </fieldset>
            </div>
            <div class='grid'>
                <fieldset>
                    <legend>Publish Settings</legend>
                    <div class='grid col2'>
                        <label for='is_draft' style='grid-column: 1 / 3;'><input type='checkbox' id='is_draft' name='is_draft' value='is_draft' {$is_draft}/> Save As Draft</label>
                        <label for='publish_date_manual'><input type='radio' id='publish_date_manual' name='publish_date_type' value='manual' {$draft_disable}/> Define Publish Date</label>
                        <label for='publish_date_auto'><input type='radio' id='publish_date_auto' name='publish_date_type' value='automatic' checked {$draft_disable}/> Automatically Publish On Save</label>
                        <label for='publish_date'>Date <input type='date' style='grid-column: 1 / 2;' name='publish_date' id='publish_date' disabled/></label>
                        <label for='publish_time'>Time <input type='time' style='grid-column: 1 / 2;' name='publish_time' id='publish_time' disabled/></label>
                    </div>
                </fieldset>
            </div>
            <div class='grid'>
                <label for='page_title'>Title</label>
                <input type='text' id='page_title' name='page_title' value='{$entry['title']}'>
            </div>";
    $html .= "
            <div class='grid'>
                <label for='page_content'>Content</label>
                <p>This text supports <a href='https://www.markdownguide.org/basic-syntax/' target='_blank'>Markdown</a> syntax.</p>
                <div class='hw-nav md-edit'>
                    <a id='md-bold' value='bold' data-action='bold' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-bold'></span>&nbsp;</div>
                    </a>
                    <a id='md-italic' value='italic' data-action='italic' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-italic'></span>&nbsp;</div>
                    </a>
                    <a id='md-strikethrough' value='strikethrough' data-action='strikethrough' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-strikethrough'></span>&nbsp;</div>
                    </a>
                    <div class='separator'></div>
                    <a id='md-header' value='header' data-action='header' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-header'></span>&nbsp;</div>
                    </a>
                    <a id='md-quote' value='quote' data-action='quote' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-quote'></span>&nbsp;</div>
                    </a>
                    <a id='md-bulletlist' value='bulletlist' data-action='bulletlist' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-bulletlist'></span>&nbsp;</div>
                    </a>
                    <a id='md-numberlist' value='numberlist' data-action='numberlist' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-numberlist'></span>&nbsp;</div>
                    </a>
                    <a id='md-indent' class='disabled' value='indent' data-action='indent' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-indent'></span>&nbsp;</div>
                    </a>
                    <a id='md-dedent' class='disabled' value='dedent' data-action='dedent' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-dedent'></span>&nbsp;</div>
                    </a>
                    <div class='separator'></div>
                    <a id='md-link' value='link' data-action='link' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-link'></span>&nbsp;</div>
                    </a>
                    <a id='md-image' value='image' data-action='image' data-target='page_content' onclick='md_button_action(this)'>
                        <div class='btn'><span class='icon-image'></span>&nbsp;</div>
                    </a>
                </div>
                <textarea id='page_content' name='page_content'>{$entry['content']}</textarea>
            </div>
            <input type='hidden' name='action' id='form_action' value='page_edit'>
            <input type='hidden' name='page_id' value={$entry['page_id']}>
            <div class='grid col5'>
                <div></div>
                <input type='button' value='Preview' onclick='preview()'>
                <input type='button' value='Cancel' onclick='cancel(\"/console/content/\")'>
                <input type='button' value='Save' id='save_button' {$save_disabled} onclick='save()'>
            </div>
        </form>";
    $html .= "
        <script>
            // Cateogires json
            var categories = {$categories_json};
            
            document.onreadystatechange = function(event) {
                md_initialize('page_content');
                if (document.readyState === 'complete') {
                    
                    document.getElementById('page_category')
                    .addEventListener('change', function(){ toggle_sub_categories(); });
                    
                    document.getElementById('page_content')
                    .addEventListener('change', function(){ force_preview(); });
                    
                    document.getElementById('publish_date_manual')
                    .addEventListener('change', function (){ toggle_publish_date(); });
                    document.getElementById('publish_date_auto')
                    .addEventListener('change', function (){ toggle_publish_date(); });
                    
                    document.getElementById('is_draft')
                    .addEventListener('change', function (){ toggle_draft(); });
                    
                    document.getElementById('major_version')
                    .addEventListener('change', function (){ update_patch_notes_title(); });
                    document.getElementById('minor_version')
                    .addEventListener('change', function (){ update_patch_notes_title(); });
                    document.getElementById('patch_version')
                    .addEventListener('change', function (){ update_patch_notes_title(); });
                    document.getElementById('page_title')
                    .addEventListener('change', function (){ update_patch_notes_title(); });
                    
                    toggle_sub_categories();
                    console.log('loaded');
                }
            };
            
            function toggle_sub_categories() {
                let category_select = document.getElementById('page_category');
                let sub_category_select = document.getElementById('page_sub_category');
                
                let current_options = sub_category_select.getElementsByTagName('option');
                for (let i=current_options.length; i--;) {
                    sub_category_select.removeChild(current_options[i]);
                }
                
                for (let i = categories[category_select.value].length; i--;) {
                    let option = document.createElement('option');
                    option.text = categories[category_select.value][i];
                    option.value = categories[category_select.value][i];
                    sub_category_select.add(option);
                }
                
                if (0 == sub_category_select.length) {
                    sub_category_select.setAttribute('disabled', 'disabled');
                } else {
                    sub_category_select.removeAttribute('disabled');
                }
                
                if ('Patch Notes' == category_select.value) {
                    document.getElementById('sub-category-container').style.display = 'none';
                    document.getElementById('version-container').style.display = 'grid';
                    update_patch_notes_title();
                } else {
                    document.getElementById('version-container').style.display = 'none';
                    document.getElementById('sub-category-container').style.display = 'grid';
                }
            }
            
            function update_patch_notes_title() {
                if ('Patch Notes' != document.getElementById('page_category').value) {
                    return;
                }
                let major_version = document.getElementById('major_version').value;
                let minor_version = document.getElementById('minor_version').value;
                let patch_version = document.getElementById('patch_version').value;
                document.getElementById('page_title').value = `Patch Notes \${major_version}.\${minor_version}.\${patch_version}`;
            }
            
            function toggle_publish_date() {
                let time_input = document.getElementById('publish_time');
                let date_input = document.getElementById('publish_date');
                if (document.getElementById('publish_date_manual').checked) {
                    date_input.disabled = null;
                    time_input.disabled = null;
                } else {
                    date_input.setAttribute('disabled', 'disabled');
                    time_input.setAttribute('disabled', 'disabled');
                }
            }
            
            function toggle_draft() {
                let time_input = document.getElementById('publish_time');
                let date_input = document.getElementById('publish_date');
                let publish_manual = document.getElementById('publish_date_manual');
                let publish_auto = document.getElementById('publish_date_auto');
                if (document.getElementById('is_draft').checked) {
                    publish_manual.setAttribute('disabled', 'disabled');
                    publish_auto.setAttribute('disabled', 'disabled');
                    date_input.setAttribute('disabled', 'disabled');
                    time_input.setAttribute('disabled', 'disabled');
                } else {
                    publish_manual.disabled = null;
                    publish_auto.disabled = null;
                    if (publish_manual.checked) {
                        date_input.disabled = null;
                        time_input.disabled = null;
                    }
                }
            }
            
            function preview() {
                document.getElementById('page-edit').action = '/console/content/edit/{$entry['page_id']}';
                document.getElementById('form_action').value = 'page_preview';
                submit_form(\"page-edit\");
            }
            
            function force_preview() {
                let save_button = document.getElementById('save_button');
                save_button.setAttribute('disabled', 'disabled');
                save_button.onclick = null;
            }
            
            function cancel(url) {
                window.location = url;
            }
            
            function save() {
                submit_form(\"page-edit\");
            }
        </script>";
    $html .= "
        <script>
            /*
                Markdown Editor
             */
            
            function md_initialize(element_id) {
                let md_editor = document.getElementById(element_id);
                ['input', 'keydown', 'keyup', 'click'].forEach( event =>
                    md_editor.addEventListener(event, function () {
                        md_update_gui_buttons();
                    })
                );
            }
            
            /*
            capture keyboard shortcuts:
            https://stackoverflow.com/questions/3680919/overriding-browsers-keyboard-shortcuts
            
            paste clipboard with rich text to markdown
            https://github.com/euangoddard/clipboard2markdown
            
            other notes:
            - url-encode links when being addded
            */
            
            function md_update_gui_buttons() {
                const text_area = document.getElementById('page_content');
                const cursor_start = text_area.selectionStart;
                const cursor_end = text_area.selectionEnd;
                if (cursor_start != cursor_end) { return; }
                const content_before = text_area.value.substring(0, text_area.selectionStart);
                const content_after = text_area.value.substring(text_area.selectionEnd);
                const line_before = content_before.substring(content_before.lastIndexOf('\\n'));
                const line_after = content_after.substring(0, content_after.indexOf('\\n'));
                const this_line = (line_before + line_after).trim();
                
                // font styles
                if ((content_before.match(/\*{2}/g) || []).length % 2 && (content_after.match(/\*{2}/g) || []).length % 2) {
                    document.getElementById('md-bold').classList.add('active');
                } else {
                    document.getElementById('md-bold').classList.remove('active');
                }
                
                if ((content_before.match(/\*/g) || []).length % 2 && (content_after.match(/\*/g) || []).length % 2) {
                    document.getElementById('md-italic').classList.add('active');
                } else {
                    document.getElementById('md-italic').classList.remove('active');
                }
                
                if ((content_before.match(/~~/g) || []).length % 2 && (content_after.match(/~~/g) || []).length % 2) {
                    document.getElementById('md-strikethrough').classList.add('active');
                } else {
                    document.getElementById('md-strikethrough').classList.remove('active');
                }
                
                // block elements
                if (/\d+\.\s+.*$/.test(this_line.trim())) {
                    document.getElementById('md-numberlist').classList.add('active');
                    md_dent_enable();
                } else {
                    document.getElementById('md-numberlist').classList.remove('active');
                    md_dent_disable();
                }
                
                if (/\-\s+.*$/.test(this_line.trim())) {
                    document.getElementById('md-bulletlist').classList.add('active');
                    md_dent_enable();
                } else {
                    document.getElementById('md-bulletlist').classList.remove('active');
                    md_dent_disable();
                }
                
                if (/>\s+.*$/.test(this_line.trim())) {
                    document.getElementById('md-quote').classList.add('active');
                    md_dent_enable();
                } else {
                    document.getElementById('md-quote').classList.remove('active');
                    md_dent_disable();
                }
                
                if (/#\s+.*$/.test(this_line.trim())) {
                    document.getElementById('md-header').classList.add('active');
                    md_dent_enable();
                } else {
                    document.getElementById('md-header').classList.remove('active');
                    md_dent_disable();
                }
                
                if (/!?\[[^\]]*\]\([^\)]*\)/.test(this_line)) {
                    let link_start = line_before.lastIndexOf('[') -1;
                    if (0 < link_start && '!' == line_before.substring(link_start - 1)) {
                        link_start--;
                    }
                    const link_end = line_after.indexOf(')');
                    const link_string = this_line.substring(link_start, link_end + line_before.length);
                    console.log(link_string);
                    if (0 <= link_start && 0 <= link_end) {
                        if (/^\[[^\]]*\]\([^\)]*\)$/.test(link_string)) {
                            document.getElementById('md-link').classList.add('active');
                        } else {
                            document.getElementById('md-link').classList.remove('active');
                        }
                    } else {
                        document.getElementById('md-link').classList.remove('active');
                    }
                } else {
                    document.getElementById('md-link').classList.remove('active');
                }
            }
            
            function md_dent_enable() {
                document.getElementById('md-indent').classList.remove('disabled');
                document.getElementById('md-dedent').classList.remove('disabled');
            }
            
            function md_dent_disable() {
                document.getElementById('md-indent').classList.add('disabled');
                document.getElementById('md-dedent').classList.add('disabled');
            }
            
            function md_button_action(event) {
                // get all the cursor selection data
                const action = event.getAttribute('data-action');
                const text_area = document.getElementById(event.getAttribute('data-target'));
                const cursor_start = text_area.selectionStart;
                const cursor_end = text_area.selectionEnd;
                const content_before = text_area.value.substring(0, text_area.selectionStart);
                const content_selected = (cursor_start < cursor_end) ? text_area.value.substring(cursor_start, cursor_end) : '';
                const content_after = text_area.value.substring(text_area.selectionEnd)
                const start_nl_index = content_before.lastIndexOf('\\n');
                const end_nl_index = content_after.indexOf('\\n');
                const applicable_lines = '\\n' + ((content_before.substring(start_nl_index) + content_selected + content_after.substring(0, end_nl_index)).trim());
                let selection_start_offset = 0;
                let selection_end_offset = 0;
                
                if ('header' == action) {
                    let altered_lines = applicable_lines.replaceAll('\\n', '\\n# ').replaceAll('# #', '##').replaceAll('\\n####### ', '\\n');
                    let output = content_before.substring(0, start_nl_index + 1) + altered_lines.trim() + content_after.substring(end_nl_index);
                    let line_len_diff = altered_lines.length - applicable_lines.length;
                    console.log(line_len_diff);
                    text_area.value = output;
                    if (0 < line_len_diff) {
                        selection_start_offset += ('# ' == altered_lines.substring(1, 2)) ? 2 : 1;
                        selection_end_offset += line_len_diff * (altered_lines.split('\\n').length - 1);
                    } else {
                        selection_start_offset -= 7;
                        selection_end_offset -= 7 * (altered_lines.split('\\n').length - 1);
                    }
                }
                if ('quote' == action) {
                    let altered_lines = applicable_lines.replaceAll('\\n', '\\n> ').replaceAll('\\n> > ', '\\n');
                    let output = content_before.substring(0, start_nl_index + 1) + altered_lines.trim() + content_after.substring(end_nl_index);
                    let line_len_diff = altered_lines.length - applicable_lines.length;
                    console.log(line_len_diff);
                    text_area.value = output;
                    if (0 < line_len_diff) {
                        selection_start_offset += 2;
                        selection_end_offset += line_len_diff;
                    } else {
                        selection_start_offset -= 2;
                        selection_end_offset += line_len_diff;
                    }
                }
                
                text_area.focus();
                text_area.setSelectionRange(cursor_start + selection_start_offset, cursor_end + selection_end_offset);
                
                md_update_gui_buttons();
            }
        </script>";
}
if ("view" == ($uri[3] ?? "")) {
    $entry = $babbler->fetch_entry(intval($uri[4]));

    $links = [
        "/\b(officers?)\b/i" => "<a href='/enchiridion/officers/'>\$1</a>",
        "/\b(ores?|gase?s?)\b/i" => "<a href='/compendium/mining/'>\$1</a>",
    ];
    $content = generate_page_html(markdown: $entry['content'], inline_links: $links);
    $html = "
        <h2><em>(Preview)</em></h2>
        <div id='preview-contaienr' class='hw-border-box'>{$content}</div><hr>\n";
}

$output = [];

$entries = $babbler->fetch_entry_table('created', 'asc');
foreach ($entries as $entry) {
    $actions = [
        "<a class='action edit' href='/console/content/edit/{$entry['entry_id']}'></a>&nbsp;",
        "<a class='action versions' href='/console/content/versions/{$entry['entry_id']}'></a>&nbsp;",
        "<a class='action hide' a href='#'></a>&nbsp;",
    ];
    $output[] = [
        'Title' => "<a href='/console/content/view/{$entry['entry_id']}'>{$entry['title']}</a>",
        'Category' => $entry['category'],
        'Sub-Category' => $entry['sub_category'],
        'Last Edit' => date("j M y H:i", strtotime($entry['edited'])),
        'Last Author' => "<a href='/console/users/{$entry['edited_by']}/'>{$entry['edited_by']}</a>",
        'Actions' => implode(separator: " ", array: $actions)
    ];
}

$content = $html;
$content .= ("edit" != ($uri[3] ?? false))
    ? "
            <div class='hw-nav col7'>
                <a style='grid-column: 4 / 5;' href='/console/content/edit/'><div id='form-btn-txt' class='btn'>Add Page</div></a>
            </div>\n"
    : "";
$content .= array_2_table($output);

return $content;