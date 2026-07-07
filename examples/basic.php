<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Cwx1227\Esxi\EsxiClient;

$client = EsxiClient::make([
    'host' => getenv('ESXI_HOST') ?: '192.168.127.106',
    'username' => getenv('ESXI_USER') ?: 'root',
    'password' => getenv('ESXI_PASSWORD') ?: '',
    'version' => '67',
    'ssl_verify' => false,
]);

print_r($client->about());

$vms = $client->vps()->list();
foreach ($vms['data'] as $vm) {
    echo sprintf(
        "%s\t%s\t%s CPU\t%s MB\t%s\n",
        $vm['name'] ?? '-',
        $vm['runtime.powerState'] ?? '-',
        $vm['summary.config.numCpu'] ?? '-',
        $vm['summary.config.memorySizeMB'] ?? '-',
        $vm['summary.guest.ipAddress'] ?? '-'
    );
}

$client->logout();
