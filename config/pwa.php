<?php

$twaPackageName = trim((string) env('TWA_PACKAGE_NAME', ''));
$twaSha256Fingerprints = array_values(array_filter(array_map(
    static fn (string $fingerprint): string => trim($fingerprint),
    explode(',', (string) env('TWA_SHA256_CERT_FINGERPRINTS', '')),
)));

$assetlinksStatements = [];

if ($twaPackageName !== '' && $twaSha256Fingerprints !== []) {
    $assetlinksStatements[] = [
        'relation' => ['delegate_permission/common.handle_all_urls'],
        'target' => [
            'namespace' => 'android_app',
            'package_name' => $twaPackageName,
            'sha256_cert_fingerprints' => $twaSha256Fingerprints,
        ],
    ];
}

return [
    'purchase_mode' => env('PWA_PURCHASE_MODE', 'web'),

    'twa' => [
        'package_name' => $twaPackageName,
        'sha256_cert_fingerprints' => $twaSha256Fingerprints,
    ],

    'assetlinks' => [
        'statements' => $assetlinksStatements,
    ],
];
