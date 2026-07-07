<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Cwx1227\Esxi\EsxiClient;

$client = EsxiClient::make([
    'host' => getenv('ESXI_HOST'),
    'username' => getenv('ESXI_USER'),
    'password' => getenv('ESXI_PASSWORD'),
    'version' => '67',
    'ssl_verify' => false,
]);

// network 传端口组名称。相同端口组/VLAN 的 VM 处于同一二层网络；
// 换另一个端口组/VLAN 就可以做类似 VPC 的隔离。
$taskInfo = $client->vps()->create([
    'name' => 'vps-demo-001',
    'datastore' => 'datastore1',
    'network' => 'VM Network',
    'num_cpus' => 2,
    'memory_mb' => 2048,
    'disk_gb' => 40,
    'guest_id' => 'ubuntu64Guest',
    'adapter_type' => 'vmxnet3',
]);

print_r($taskInfo);

$client->logout();
