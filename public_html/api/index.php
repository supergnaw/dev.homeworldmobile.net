<?php

declare(strict_types=1);

function is_list(array $array): bool {
    $expectedKey = 0;
    foreach ($array as $i => $_) {
        if ($i !== $expectedKey) {
            return false;
        }
        $expectedKey++;
    }
    return true;
}

function load_data(string $target_file): mixed {
    if (!file_exists($target_file)) {
        return false;
    }
    // Read the JSON file
    $json = file_get_contents($target_file);
    // Decode the JSON file
    return json_decode($json,true);
}

function filter_results(array $filters, array $results) {
    return $results;
}

function _filter_eq(string $search, array $list): array {
    return [];
}

function _filter_ne(string $search, array $list): array {
    return [];
}

function _filter_in(string $search, array $list): array {
    return [];
}

function _filter_ni(string $search, array $list): array {
    return [];
}

function _filter_lt(string $search, array $list): array {
    return [];
}

function _filter_gt(string $search, array $list): array {
    return [];
}

/*
 * STATUS CODES
 * 200 - OK
 * 204 - No content
 * 400 - Bad request
 * 403 - Forbidden
 * 404 - Not found
 * 405 - Method not allowed
 * */

// DEFAULTS
$header_msg = "OK";
$status_code = 200;
$object_type = "list";
$data = false;
$continue = true;

$valid_endpoints = [
    "/api/fabrication" => [
        "description" => "ship and part building requirements"
    ],
    "/api/kiith" => [
        "description" => "details on kitth"
    ],
    "/api/signals" => [
        "description" => "possible results for system scans"
    ],
    "/api/systems" => [
        "description" => "information on starsystems"
    ],
    "/api/research" => [
        "description" => "research project data"
    ]
];

// PARSE API ENDPOINT
if (preg_match_all("/\/(?!api\b)([\w\=\!\<\>\.]+)/", $_SERVER['REQUEST_URI'], $matches)) {
    $endpoint = $matches[1];

    if (array_key_exists("/api/{$endpoint[0]}", $valid_endpoints)) {
        $data = load_data("compendium/{$endpoint[0]}.json");
    } else {
        $header_msg = "Bad request";
        $status_code = 400;
        $object_type = "error";
    }

    if (!is_bool($data)) {
        if (1 == count($data)) {
            $data = $data[0];
            $object_type = "object";
        }

        if (0 == count($data)) {
            $object_type = "error";
            $header_msg = "No content";
            $status_code = 204;
        }
    }
} else {
    $data = ["valid_endpoints" => $valid_endpoints];
}

$output = [
    "status" => $status_code,
    "message" => $header_msg,
    "object" => $object_type,
];

if ($data) {
    if (is_list($data)) { $output["total"] = count($data); }
    $output["compendium"] = $data;

    header("Content-Type: application/json");
    echo json_encode($output);
} else {
    header("Content-Type: application/json");
    echo json_encode($output);
}