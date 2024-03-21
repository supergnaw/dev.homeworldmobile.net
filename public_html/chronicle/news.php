<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$api = new \Supergnaw\PlayFabPhp\PlayFab(PLAYFAB_APP_ID);
$api->login_with_email(email: PLAYFAB_EMAIL, password: PLAYFAB_PASSWORD);

// Process Request URI
$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

/*
 * NEWS POSTS
 */
$news = $api->get_news(count: 20);
var_dump($news);
$posts = [];
foreach ($news as $post) {
    $body = preg_replace('/\\n\\n/mis', "</p>\n<p>", $post['news_body']);
    $published = gmdate(format: "l, F jS, Y H:i", timestamp: strtotime($post['news_timestamp']));
    $posts[] = "<h2>{$post['news_title']}</h2>\n<h4>{$published} (UTC)</h4><p>{$body}</p>\n";
}
$html = "<h1>News</h1>" . implode("\n<hr>\n", $posts) . "\n<hr><p>Last updated: {$api->last_endpoint_call(endpoint: '/Client/GetTitleNews')}</p>";

return $html;