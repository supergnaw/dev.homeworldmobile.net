<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$babbler = new \Supergnaw\Nestbox\Babbler\Babbler();

$response = "";

if (!empty($_POST)) {
    /*
     * ACCOUNT
     */
    $post_var_filter = [
        "username" => 'string',
        "password" => 'string',
        "email" => 'string',
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
        "patch_version" => 'int',
        "minor_version" => 'int',
    ];

    $post = \Supergnaw\FormSecurity\FormSecurity::filter_input("post", $post_var_filter);

    /*
     * USER SESSION
     */
    if ("register" == $post['action']) {
        $tm = new \Supergnaw\Nestbox\Titmouse\Titmouse('users', 'username');

        $user_data = [
            "username" => $post['username'],
            "email" => $post['email']
        ];

        try {
            if ($tm->register_user($user_data, $post['password'])) {
                save_session_alert(text: "Successfully logged in.", classes: "success");
            } else {
                save_session_alert(text: "Failed to log in.", classes: "warning");
            }
        } catch (Exception $e) {
            save_session_alert(text: $e->getMessage(), classes: "error");
        }
    }

    if ("login" == $post['action']) {
        $tm = new \Supergnaw\Nestbox\Titmouse\Titmouse('users', 'username');

        $user = $tm->select_user($post['username']);

        if (!empty($user)) {
            try {
                $tm->login_user($post['username'], $post['password']);
            } catch (\Supergnaw\Nestbox\Exception\NestboxException $e) {
                save_session_alert(text: $e->getMessage(), classes: "error");
            }
        }
    }

    if ("logout" == $post['action']) {
        $tm = new \Supergnaw\Nestbox\Titmouse\Titmouse();
        $tm->logout_user();
        save_session_alert("Successfully logged out!", 'success');
    }

    /*
     * CONTENT EDITS
     */
    if ("page_edit" == ($post['action'] ?? false)) {
        $published = ("manual" == $post['publish_date_type']) ? "{$post['publish_date']} {$post['publish_time']}:00" : "";

        $entry = [
            "title" => $post['page_title'],
            "category" => $post['page_category'],
            "sub_category" => $post['page_sub_category'],
            "content" => $post['page_content'],
            "is_draft" => "is_draft" == $post['is_draft'],
            "published" => $published,
            "page_id" => $post['page_id'] ?? 0,
        ];

        if ("Patch Notes" == $entry['category']) {
            $entry['sub_category'] = str_pad("{$post['major_version']}", 3, "0", STR_PAD_LEFT)
                . "." . str_pad("{$post['minor_version']}", 3, "0", STR_PAD_LEFT)
                . "." . str_pad("{$post['patch_version']}", 3, "0", STR_PAD_LEFT);
            $entry['title'] = "Patch Notes {$post['major_version']}.{$post['minor_version']}.{$post['patch_version']}";
        }

        // VERIFY USER HAS PERMISSIONS TO ACTUALLY MAKE EDITS
        $user = $_SESSION["user_data"];

        try {
            if (0 == intval($entry['page_id'])) {
                $result = $babbler->add_entry(
                    category: $entry['category'] ?? "",
                    sub_category: $entry['sub_category'] ?? "",
                    title: $entry['title'] ?? "",
                    content: $entry['content'] ?? "",
                    author: $user['username'],
                    published: $entry['published'],
                    is_draft: $entry['is_draft']
                );
            } else {
                $result = $babbler->edit_entry(
                    entry_id: $entry['page_id'],
                    editor: $user['username'],
                    category: $entry['category'] ?? "",
                    sub_category: $entry['sub_category'] ?? "",
                    title: $entry['title'] ?? "",
                    content: $entry['content'] ?? "",
                    published: $entry['published'],
                    is_draft: $entry['is_draft']
                );
            }
            if (1 == $result) {
                $_SESSION["alerts"]["New page successfully added!"] = 'success';
            } else {
                $_SESSION["alerts"]["Couldn't add new page: unkown error"] = 'warning';
            }
        } catch (PDOException $e) {
            if (str_contains(haystack: $e->getMessage(), needle: "1062")) {
                $_SESSION["alerts"]["Failed to save page: duplicate entry found"] = 'error';
            }
        }
    }

    if ("page_preview" == ($post['action'] ?? false)) {
        $links = [
            "/\b(officers?)\b/i" => "<a href='/enchiridion/officers/'>\$1</a>",
            "/\b(ores?|gase?s?)\b/i" => "<a href='/compendium/mining/'>\$1</a>",
        ];
        $html = generate_page_html(markdown: $post['page_content'], inline_links: $links);
        $response = "
        <h2>{$post['page_title']} <em>(preview)</em></h2>
        <div id='preview-contaienr' class='hw-border-box'>{$html}</div><hr>\n";
    }

    /*
     * DATABASE MANAGEMENT
     */
    if ("export_json" == ($post['action'] ?? false)) {
        $postVarFilter = ["table_name" => 'string'];
        $post = \Supergnaw\FormSecurity\FormSecurity::filter_input("post", $postVarFilter);

        $nb = new \Supergnaw\Nestbox\Nestbox();
        $tableData = $nb->dump_database(tables: ($_POST['tables'] ?? []));
        $tableCount = count($tableData);

        $_SESSION['database_dump_json'] = ($tableData) ? json_encode($tableData) : "";

        save_session_alert(text: "Successfully exported database JSON for {$tableCount} tables.", classes: "success");
    }

    if ("import_json" == ($post['action'] ?? false)) {
        $post = \Supergnaw\FormSecurity\FormSecurity::filter_input("post", ["input_text" => "string"]);

        $nb = new \Supergnaw\Nestbox\Nestbox();
        $updateCount = $nb->load_database(json_decode($post['input_text'], associative: true));

        $classes = (0 == $updateCount) ? "info" : "success";

        save_session_alert(text: "Successfully completed import with {$updateCount} updates and/or inserts.", classes: $classes);
    }
}

return $response;