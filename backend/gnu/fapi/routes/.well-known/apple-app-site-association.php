<?php
if (!defined('_GNUBOARD_')) exit;

function GET() {
    $team_id = Config::get('IOS_TEAM_ID', 'YOUR_IOS_TEAM_ID_HERE');
    $app_id = Config::get('APP_ID', 'com.gnuboard.karbon');

    $data = [
        "applinks" => [
            "apps" => [],
            "details" => [
                [
                    "appID" => $team_id . "." . $app_id,
                    "paths" => ["*"]
                ]
            ]
        ]
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
