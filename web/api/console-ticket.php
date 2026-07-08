<?php

declare(strict_types=1);

use Cwx1227\Esxi\EsxiClient;

require __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$host = getenv('ESXI_HOST') ?: '';
$username = getenv('ESXI_USER') ?: '';
$password = getenv('ESXI_PASSWORD') ?: '';

$vm = isset($_GET['vm']) ? trim((string) $_GET['vm']) : 'test';
$type = isset($_GET['type']) ? trim((string) $_GET['type']) : 'webmks';

try {
    if ($host === '' || $username === '' || $password === '') {
        throw new RuntimeException('Missing ESXI_HOST, ESXI_USER or ESXI_PASSWORD environment variable.');
    }
    if ($vm === '') {
        throw new InvalidArgumentException('Missing vm parameter.');
    }

    $client = EsxiClient::connect($host, $username, $password, [
        'version' => '67',
        'ssl_verify' => false,
        'timeout' => 120,
        'connect_timeout' => 10,
    ]);

    try {
        $result = $client->vps()->consoleTicket($vm, ['type' => $type]);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } finally {
        $client->logout();
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'class' => $e::class,
            'message' => $e->getMessage(),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
