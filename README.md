# 1227cwx/esxi-php-sdk

PHP Composer 包，用于直接对接 **单台 ESXi 6.7** 的 `vim25` SOAP API。

> 适用于 webman、ThinkPHP、Laravel、普通 PHP CLI 等任意 Composer 项目。

## 支持能力

- ESXi 登录/退出、版本信息读取
- VM/VPS 列表、配置读取、监控摘要读取
- VM 创建、CPU/内存改配
- VM 开机、关机、重启、挂起、Guest OS 关机/重启
- Host 监控摘要读取
- 标准 vSwitch、PortGroup、物理网卡、VMkernel 网卡读取
- 创建/删除 vSwitch
- 创建/更新/删除 PortGroup
- PortGroup VLAN 与安全策略：`allowPromiscuous`、`macChanges`、`forgedTransmits`
- VM 网卡绑定到指定 PortGroup

## 安装

发布到 Packagist 后，直接安装：

```bash
composer require 1227cwx/esxi-php-sdk
```

本地开发调试时，可以临时使用 path repository：

```bash
composer config repositories.esxi-php-sdk path E:/webman-vps/esxi-php-sdk
composer require 1227cwx/esxi-php-sdk:*
```

## 快速使用

```php
use WebmanVps\Esxi\EsxiClient;

$client = EsxiClient::make([
    'host' => '192.168.127.106',
    'username' => 'root',
    'password' => 'password',
    'version' => '67',
    'ssl_verify' => false, // 测试环境自签证书
]);

$about = $client->about();
$vms = $client->vps()->list();
$metrics = $client->monitor()->vm('Ubuntu18');

$client->vps()->powerOn('Ubuntu18');
$client->vps()->shutdownGuest('Ubuntu18'); // 依赖 VMware Tools
$client->vps()->powerOff('Ubuntu18');      // 强制断电

$client->logout();
```

## 创建 VPS

```php
$client->vps()->create([
    'name' => 'vps-demo-001',
    'datastore' => 'datastore1',
    'network' => 'VM Network',      // PortGroup 名称
    'num_cpus' => 2,
    'memory_mb' => 2048,
    'disk_gb' => 40,
    'guest_id' => 'ubuntu64Guest',
    'adapter_type' => 'vmxnet3',
]);
```

默认使用单机 ESXi 的常见对象：

- VM Folder：`ha-folder-vm`
- Resource Pool：`ha-root-pool`
- Host：`ha-host`

如果你的环境不同，可以传：

```php
'folder' => ['type' => 'Folder', 'value' => '...'],
'resource_pool' => ['type' => 'ResourcePool', 'value' => '...'],
'host' => ['type' => 'HostSystem', 'value' => '...'],
```

## 修改配置

```php
// 改 CPU/内存
$client->vps()->resize('vps-demo-001', [
    'cpu' => 4,
    'memory_mb' => 4096,
]);

// 原始 ConfigSpec 能力
$client->vps()->reconfigure('vps-demo-001', [
    'annotation' => 'created by 1227cwx/esxi-php-sdk',
]);
```

## VPC/局域网隔离逻辑

ESXi 单机没有云厂商意义上的 VPC，但可以通过 **PortGroup + VLAN** 做基础二层隔离：

```php
// 创建一个 PortGroup，VLAN 100
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

// 创建 VPS 时绑定到同一个 PortGroup
$client->vps()->create([
    'name' => 'vps-a',
    'datastore' => 'datastore1',
    'network' => 'vpc-100',
    'num_cpus' => 1,
    'memory_mb' => 1024,
    'disk_gb' => 20,
]);

// 已存在 VM 换网络
$client->vps()->setNetwork('vps-a', 'vpc-100');
```

说明：

- 传同一个 `network` / PortGroup 名称，VM 会接入同一二层网络。
- 换不同 PortGroup + 不同 VLAN，可以实现互不互通。
- 要“能上网”，物理交换机、上联口和网关必须支持对应 VLAN。
- ESXi 标准交换机的“安全组”能力主要是端口组安全策略；如果要按 IP/端口做防火墙，需要 NSX、上游防火墙或 guest OS 防火墙。

## 网络接口

```php
$client->network()->listVirtualSwitches();
$client->network()->listPortGroups();
$client->network()->listPhysicalNics();
$client->network()->listVmKernelNics();

$client->network()->createVirtualSwitch([
    'name' => 'vSwitch1',
    'mtu' => 1500,
    // 'pnics' => ['vmnic1'],
]);

$client->network()->removeVirtualSwitch('vSwitch1');
```

## 只读 smoke 测试

```powershell
$env:ESXI_HOST='192.168.127.106'
$env:ESXI_USER='root'
$env:ESXI_PASSWORD='<PASSWORD>'
php tests/smoke.php
Remove-Item Env:\ESXI_PASSWORD
```

## 注意

- ESXi 6.7 单机没有 vCenter REST/JSON 管理接口，本包走 `/sdk` SOAP。
- 写操作已实现，但请先在测试 VM 上验证。
- 不要把 root 密码写入代码；建议创建专用 API 用户。
- 生产环境建议启用证书校验或固定证书指纹。
