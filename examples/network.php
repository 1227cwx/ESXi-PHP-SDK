<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use WebmanVps\Esxi\EsxiClient;

$client = EsxiClient::make([
    'host' => getenv('ESXI_HOST'),
    'username' => getenv('ESXI_USER'),
    'password' => getenv('ESXI_PASSWORD'),
    'version' => '67',
    'ssl_verify' => false,
]);

// 创建一个新的端口组作为“VPC 网络”。
// 前提：物理交换机/上联口允许对应 VLAN，否则只能在 ESXi 内部互通。
$client->network()->createPortGroup([
    'name' => 'vpc-100',
    'vswitch' => 'vSwitch0',
    'vlan_id' => 100,
    'security' => [
        'allow_promiscuous' => false,
        'mac_changes' => false,
        'forged_transmits' => false,
    ],
]);

// 把某台 VM 的网卡绑定到这个端口组。
$client->vps()->setNetwork('vps-demo-001', 'vpc-100');

print_r($client->network()->listPortGroups());

$client->logout();
