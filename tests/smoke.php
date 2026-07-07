<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use WebmanVps\Esxi\EsxiClient;

$host = getenv('ESXI_HOST');
$user = getenv('ESXI_USER');
$password = getenv('ESXI_PASSWORD');

if (!$host || !$user || !$password) {
    fwrite(STDERR, "Set ESXI_HOST, ESXI_USER and ESXI_PASSWORD first.\n");
    exit(2);
}

$client = EsxiClient::make([
    'host' => $host,
    'username' => $user,
    'password' => $password,
    'version' => '67',
    'ssl_verify' => false,
]);

$about = $client->about();
$vms = $client->vps()->list();
$switches = $client->network()->listVirtualSwitches();
$portGroups = $client->network()->listPortGroups();
$hosts = $client->host()->list();

echo json_encode([
    'about' => [
        'fullName' => $about['fullName'] ?? null,
        'apiType' => $about['apiType'] ?? null,
        'apiVersion' => $about['apiVersion'] ?? null,
    ],
    'counts' => [
        'hosts' => count($hosts),
        'vms' => count($vms['data']),
        'switches' => count($switches),
        'portGroups' => count($portGroups),
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

$client->logout();
