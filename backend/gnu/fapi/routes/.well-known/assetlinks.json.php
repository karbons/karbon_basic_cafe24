<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * Android Asset Links
 * GET /api/.well-known/assetlinks.json
 */
function GET() {
    $app_id = Config::get('APP_ID', 'com.gnuboard.karbon');
    $sha256 = Config::get('ANDROID_SHA256', 'YOUR_ANDROID_SHA256_FINGERPRINT_HERE');

    $data = [
        [
            "relation" => ["delegate_permission/common.handle_all_urls"],
            "target" => [
                "namespace" => "android_app",
                "package_name" => $app_id,
                "sha256_cert_fingerprints" => [$sha256]
            ]
        ]
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
