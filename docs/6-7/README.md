# ESXi 6.7 调用文档

> 版本目录：`docs/6-7`  
> 对应 PHP 命名空间：`Cwx1227\Esxi\Version\V67`

## 目录

- [1. 初始化客户端](#1-初始化客户端)
- [2. 返回格式与异常](#2-返回格式与异常)
- [3. VPS / VM 服务](#3-vps--vm-服务)
- [4. Host 宿主机服务](#4-host-宿主机服务)
- [5. Network 网络服务](#5-network-网络服务)
- [6. Monitor 监控服务](#6-monitor-监控服务)
- [7. Storage 存储与文件服务](#7-storage-存储与文件服务)
- [8. Task 任务查询服务](#8-task-任务查询服务)
- [9. Log 日志查询服务](#9-log-日志查询服务)
- [10. Inventory 清单服务](#10-inventory-清单服务)
- [11. VPC / VLAN 使用建议](#11-vpc--vlan-使用建议)
- [12. 高危操作提醒](#12-高危操作提醒)

---

## 1. 初始化客户端

```php
use Cwx1227\Esxi\EsxiClient;

$client = EsxiClient::make([
    'host' => '192.168.127.106',
    'username' => 'root',
    'password' => 'your-password',
    'version' => '67',
    'ssl_verify' => false,
]);
```

也可以使用兼容快捷方式：

```php
$client = EsxiClient::connect('192.168.127.106', 'root', 'your-password', [
    'version' => '67',
    'ssl_verify' => false,
]);
```

### 初始化参数

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|---|---|---:|---|---|
| `host` | string | 是 | - | ESXi 主机 IP 或域名，可带或不带 `https://` |
| `username` | string | 是 | - | ESXi 用户名 |
| `password` | string | 是 | - | ESXi 密码 |
| `version` | string | 否 | `67` | 接口版本，ESXi 6.7 使用 `67` |
| `ssl_verify` | bool | 否 | `false` | 是否校验证书；测试环境通常为自签证书 |
| `timeout` | float/int | 否 | `60` | 请求超时时间，单位秒 |
| `connect_timeout` | float/int | 否 | `10` | 连接超时时间，单位秒 |
| `endpoint` | string | 否 | `https://<host>/sdk` | 自定义 SOAP endpoint |
| `soap_action` | string | 否 | `urn:vim25/6.7.3` | SOAPAction |
| `auto_login` | bool | 否 | `true` | 是否创建客户端时自动登录 |
| `locale` | string/null | 否 | `null` | 登录 locale |

### 鉴权登录服务

`auto_login=true` 时创建客户端会自动登录；如果关闭自动登录，可以手动调用：

```php
$client->auth()->login();
$session = $client->auth()->session();
$client->auth()->logout();
```

兼容快捷方式：

```php
$client->login();
$client->logout();
```

---

## 2. 返回格式与异常

Service 层返回稳定数组：

```php
[
    'success' => true,
    'data' => [...],
]
```

异步 Task 操作，如果 `$wait = true`，成功返回：

```php
[
    'success' => true,
    'task' => [
        'id' => 'haTask-xxx',
        'state' => 'success',
    ],
    'data' => [...],
]
```

如果 `$wait = false`，立即返回 Task MoRef：

```php
[
    'success' => true,
    'task' => [
        'type' => 'Task',
        'value' => 'haTask-xxx',
    ],
    'data' => [...],
]
```

异常类型：

| 异常 | 说明 |
|---|---|
| `EsxiException` | SDK 通用异常 |
| `SoapFaultException` | ESXi SOAP Fault |
| `TaskFailedException` | ESXi Task 执行失败 |

---

## 3. VPS / VM 服务

入口：

```php
$vps = $client->vps();
```

### 3.1 VPS 列表

```php
$result = $client->vps()->list();
```

自定义读取属性：

```php
$result = $client->vps()->list([
    'name',
    'runtime.powerState',
    'summary.config.numCpu',
    'summary.config.memorySizeMB',
]);
```

### 3.2 获取原始列表行

```php
$rows = $client->vps()->rows();
```

说明：`rows()` 不包装 `success/data`，主要给 SDK 内部解析 MoRef 使用。

### 3.3 VPS 配置信息

```php
$result = $client->vps()->info('Ubuntu18');
```

支持传入：

| 参数形式 | 示例 |
|---|---|
| VM 名称 | `'Ubuntu18'` |
| VM MoID | `'1'` |
| MoRef 数组 | `['type' => 'VirtualMachine', 'value' => '1']` |
| `ManagedObjectReference` | `$mor` |

自定义属性：

```php
$result = $client->vps()->info('Ubuntu18', [
    'name',
    'config.hardware.device',
    'guest.ipAddress',
]);
```

### 3.4 获取原始配置信息

```php
$info = $client->vps()->rawInfo('Ubuntu18');
```

说明：`rawInfo()` 不包装 `success/data`，适合内部组合调用。

### 3.5 创建 VPS

创建新磁盘：

```php
$result = $client->vps()->create([
    'name' => 'vps-demo-001',
    'datastore' => 'datastore1',
    'network' => 'VM Network',
    'num_cpus' => 2,
    'memory_mb' => 2048,
    'disk_gb' => 40,
    'guest_id' => 'ubuntu64Guest',
    'adapter_type' => 'vmxnet3',
]);
```

参数：

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|---|---|---:|---|---|
| `name` | string | 是 | - | VPS / VM 名称 |
| `datastore` | string | 是 | - | datastore 名称 |
| `network` | string | 是 | - | PortGroup 名称 |
| `num_cpus` | int | 是 | - | vCPU 数，必须为正整数 |
| `memory_mb` | int | 是 | - | 内存 MB，必须为正整数 |
| `disk_gb` | int | 新建磁盘时必填 | - | 新建系统盘容量 GB；使用现有 VMDK 时可用于换算 `capacity_kb` |
| `use_existing_disk` | bool | 否 | `false` | 是否使用已经存在的 VMDK，不创建新磁盘文件 |
| `existing_disk` | bool | 否 | `false` | `use_existing_disk` 的别名 |
| `disk_file_operation` | string/bool/null | 否 | `create` | 传 `existing`、`use_existing`、`none`、`false` 或 `null` 表示使用现有 VMDK |
| `disk_path` | string | 使用现有 VMDK 时必填 | 自动生成 | VMDK datastore 路径，例如 `[datastore1] vm-a/Ubuntu18.vmdk` |
| `capacity_kb` | int | 使用现有 VMDK 时必填之一 | - | 现有磁盘容量 KB；也可以传 `disk_gb` 让 SDK 换算 |
| `guest_id` | string | 否 | `otherGuest64` | ESXi guestId，例如 `ubuntu64Guest` |
| `hardware_version` | string | 否 | ESXi 默认 | 虚拟硬件版本，例如 `vmx-15`；别名：`vmx_version`、`version` |
| `scsi_controller` | string | 否 | `lsilogic` | `lsilogic`、`lsisas`、`buslogic`、`pvscsi` |
| `adapter_type` | string | 否 | `vmxnet3` | `vmxnet3`、`e1000`、`e1000e` |
| `thin_provision` | bool | 否 | `true` | 新建磁盘时是否精简置备 |
| `vm_path` | string | 否 | 自动生成 | VMX datastore 路径 |
| `folder` | array/MoRef | 否 | `ha-folder-vm` | VM Folder |
| `resource_pool` | array/MoRef | 否 | `ha-root-pool` | Resource Pool |
| `host` | array/MoRef | 否 | `ha-host` | Host |

使用模板 VMDK 创建 VPS 的典型链路：

```php
$vmName = 'vps-demo-001';
$vmDir = '[datastore1] ' . $vmName;
$diskPath = $vmDir . '/Ubuntu18.vmdk';

$client->storage()->makeDirectory($vmDir);

// 源和目标都是 .vmdk 时，copyFile() 会自动使用 CopyVirtualDisk_Task，避免只复制描述文件。
$client->storage()->copyFile('[datastore1] template/Ubuntu18.vmdk', $diskPath, true);

$client->vps()->create([
    'name' => $vmName,
    'datastore' => 'datastore1',
    'network' => 'vpc-100',
    'num_cpus' => 1,
    'memory_mb' => 1024,
    'disk_path' => $diskPath,
    'vm_path' => $vmDir . '/' . $vmName . '.vmx',
    'use_existing_disk' => true,
    'capacity_kb' => 41943040,
    'guest_id' => 'ubuntu64Guest',
    'hardware_version' => 'vmx-15',
]);
```

### 3.6 修改 CPU / 内存

```php
$result = $client->vps()->resize('vps-demo-001', [
    'cpu' => 4,
    'memory_mb' => 4096,
]);
```

也支持：

```php
$result = $client->vps()->resize('vps-demo-001', [
    'num_cpus' => 4,
]);
```

### 3.7 原始 ReconfigVM_Task 能力

```php
$result = $client->vps()->reconfigure('vps-demo-001', [
    'annotation' => 'created by 1227cwx/esxi-php-sdk',
]);
```

如果需要更复杂的 ESXi 数据对象，可以传 `DataObject`：

```php
use Cwx1227\Esxi\Value\DataObject;

$spec = DataObject::typed('VirtualMachineConfigSpec', [
    'annotation' => 'custom config',
]);

$result = $client->vps()->reconfigure('vps-demo-001', $spec);
```

### 3.8 电源操作

```php
$client->vps()->powerOn('vps-demo-001');
$client->vps()->powerOff('vps-demo-001');
$client->vps()->reset('vps-demo-001');
$client->vps()->suspend('vps-demo-001');
```

不等待 Task：

```php
$result = $client->vps()->powerOn('vps-demo-001', false);
```

### 3.9 Guest OS 操作

```php
$client->vps()->shutdownGuest('vps-demo-001');
$client->vps()->rebootGuest('vps-demo-001');
```

> `shutdownGuest()` 和 `rebootGuest()` 只封装 ESXi Guest OS 操作接口，不自动等待、不自动重试。调用者应根据自身业务决定是否先读取 `guest.toolsRunningStatus`，确认 VMware Tools 通道可用后再调用。

### 3.10 查看 VPS 网卡

```php
$result = $client->vps()->nics('vps-demo-001');
```

### 3.11 绑定 VPS 到指定 PortGroup

```php
$result = $client->vps()->setNetwork('vps-demo-001', 'vpc-100');
```

高级参数：

```php
$result = $client->vps()->setNetwork('vps-demo-001', 'vpc-100', [
    'adapter_type' => 'vmxnet3',
    'start_connected' => true,
    'allow_guest_control' => true,
    'connected' => true,
    'address_type' => 'generated',
]);
```

### 3.12 VPS 状态 / 配置 / 用量

```php
$client->vps()->status('vps-demo-001');
$client->vps()->config('vps-demo-001');
$client->vps()->usage('vps-demo-001');
$client->vps()->metrics('vps-demo-001');
```

### 3.13 修改 VPS 配置

```php
$client->vps()->modifyConfig('vps-demo-001', [
    'cpu' => 4,
    'memory_mb' => 4096,
    'disk_gb' => 80,
]);
```

独立快捷方法：

```php
$client->vps()->resizeDisk('vps-demo-001', 80);

$client->vps()->addDisk('vps-demo-001', [
    'disk_gb' => 100,
    'datastore' => 'datastore1',
]);

$client->vps()->addNetwork('vps-demo-001', 'vpc-100', [
    'adapter_type' => 'vmxnet3',
]);
```

创建 VPS 时可选择 SCSI 控制器：

```php
$client->vps()->create([
    'name' => 'vps-demo-002',
    'datastore' => 'datastore1',
    'network' => 'vpc-100',
    'num_cpus' => 2,
    'memory_mb' => 2048,
    'disk_gb' => 40,
    'scsi_controller' => 'pvscsi',
]);
```

`scsi_controller` 支持：`lsilogic`、`lsisas`、`buslogic`、`pvscsi`。

### 3.14 删除 VPS

```php
$client->vps()->delete('vps-demo-001');
$client->vps()->destroy('vps-demo-001');
```

---

## 4. Host 宿主机服务

入口：

```php
$host = $client->host();
```

### 4.1 ESXi 版本信息

```php
$result = $client->host()->info();
```

也可以直接：

```php
$about = $client->about();
```

### 4.2 Host 列表

```php
$hosts = $client->host()->list();
```

自定义属性：

```php
$hosts = $client->host()->list([
    'name',
    'summary.hardware',
    'summary.quickStats',
]);
```

### 4.3 Host 监控摘要

```php
$result = $client->host()->metrics();
```

指定 Host：

```php
$result = $client->host()->metrics('ha-host');
```

### 4.4 Host 网络配置

```php
$config = $client->host()->networkConfig();
```

读取字段包含：

- `config.network.vswitch`
- `config.network.portgroup`
- `config.network.pnic`
- `config.network.vnic`

### 4.5 Host 配置 / 性能 / 存储 / 任务 / 日志

```php
$client->host()->config();
$client->host()->performance();
$client->host()->network();
$client->host()->virtualMachines();
$client->host()->storage();
$client->host()->tasks(50);
$client->host()->logDescriptions();
$client->host()->logs('hostd', 0, 200);
```

`performance()` 返回 CPU、内存、存储用量摘要；`logs()` 默认读取 ESXi `hostd` 日志。

---

## 5. Network 网络服务

入口：

```php
$network = $client->network();
```

### 5.1 标准交换机列表

```php
$switches = $client->network()->listVirtualSwitches();
```

### 5.2 PortGroup 列表

```php
$portGroups = $client->network()->listPortGroups();
```

### 5.3 物理网卡列表

```php
$pnics = $client->network()->listPhysicalNics();
```

### 5.4 VMkernel 网卡列表

```php
$vmkNics = $client->network()->listVmKernelNics();
```

### 5.5 创建标准交换机

```php
$result = $client->network()->createVirtualSwitch([
    'name' => 'vSwitch1',
    'mtu' => 1500,
]);
```

绑定物理网卡：

```php
$result = $client->network()->createVirtualSwitch([
    'name' => 'vSwitch1',
    'mtu' => 1500,
    'pnics' => ['vmnic1'],
]);
```

### 5.6 删除标准交换机

```php
$result = $client->network()->removeVirtualSwitch('vSwitch1');
```

### 5.7 创建 PortGroup

```php
$result = $client->network()->createPortGroup([
    'name' => 'vpc-100',
    'vswitch' => 'vSwitch0',
    'vlan_id' => 100,
    'security' => [
        'allow_promiscuous' => false,
        'mac_changes' => false,
        'forged_transmits' => false,
    ],
]);
```

参数：

| 参数 | 类型 | 必填 | 说明 |
|---|---|---:|---|
| `name` | string | 是 | PortGroup 名称 |
| `vswitch` | string | 是 | 所属 vSwitch 名称 |
| `vlan_id` | int | 否 | VLAN ID，默认 `0` |
| `security.allow_promiscuous` | bool | 否 | 是否允许混杂模式 |
| `security.mac_changes` | bool | 否 | 是否允许 MAC 地址更改 |
| `security.forged_transmits` | bool | 否 | 是否允许伪传输 |

### 5.8 更新 PortGroup

```php
$result = $client->network()->updatePortGroup([
    'name' => 'vpc-100',
    'vswitch' => 'vSwitch0',
    'vlan_id' => 101,
    'security' => [
        'allow_promiscuous' => false,
        'mac_changes' => false,
        'forged_transmits' => false,
    ],
]);
```

### 5.9 删除 PortGroup

```php
$result = $client->network()->removePortGroup('vpc-100');
```

### 5.10 PortGroup 带宽限制

创建或更新 PortGroup 时可以传 `bandwidth_limit_bps` 快捷设置平均/峰值带宽：

```php
$client->network()->createPortGroup([
    'name' => 'vpc-100',
    'vswitch' => 'vSwitch0',
    'vlan_id' => 100,
    'bandwidth_limit_bps' => 100 * 1000 * 1000,
]);
```

也可以传完整 shaping 策略：

```php
$client->network()->updatePortGroup([
    'name' => 'vpc-100',
    'vswitch' => 'vSwitch0',
    'shaping' => [
        'enabled' => true,
        'average_bandwidth' => 100 * 1000 * 1000,
        'peak_bandwidth' => 100 * 1000 * 1000,
        'burst_size' => 1024 * 1024,
    ],
]);
```

---

## 6. Monitor 监控服务

入口：

```php
$monitor = $client->monitor();
```

### 6.1 VPS 监控摘要

```php
$result = $client->monitor()->vm('Ubuntu18');
```

默认读取：

- `runtime.powerState`
- `summary.quickStats.overallCpuUsage`
- `summary.quickStats.overallCpuDemand`
- `summary.quickStats.hostMemoryUsage`
- `summary.quickStats.guestMemoryUsage`
- `summary.quickStats.uptimeSeconds`
- `summary.storage.committed`
- `summary.storage.uncommitted`
- `summary.guest.ipAddress`
- `summary.guest.toolsStatus`

### 6.2 Host 监控摘要

```php
$result = $client->monitor()->host();
```

## 7. Storage 存储与文件服务

入口：

```php
$storage = $client->storage();
```

### 7.1 存储列表 / 详情 / 用量

```php
$client->storage()->list();
$client->storage()->info('datastore1');
$client->storage()->usage();
$client->storage()->usage('datastore1');
```

### 7.2 文件查询

```php
$result = $client->storage()->files('[datastore1] ISO', [
    'recursive' => false,
    'match_pattern' => ['*.iso'],
    'file_types' => ['iso', 'folder'],
]);
```

### 7.3 创建 datastore 目录

```php
$client->storage()->makeDirectory('[datastore1] vps-demo-001');
```

方法签名：

```php
makeDirectory(string $name, bool $createParentDirectories = true, array $options = []): array
```

### 7.4 复制 datastore 文件

```php
$client->storage()->copyFile(
    '[datastore1] ISO/a.iso',
    '[datastore1] backup/a.iso',
    true
);
```

方法签名：

```php
copyFile(string $sourceName, string $destinationName, bool $force = false, bool $wait = true, array $options = []): array
```

说明：源和目标都以 `.vmdk` 结尾时，`copyFile()` 默认自动走 `CopyVirtualDisk_Task`，用于完整复制虚拟磁盘；普通文件仍走 `CopyDatastoreFile_Task`。如果明确要按普通文件复制 VMDK，可以传：

```php
$client->storage()->copyFile($source, $destination, true, true, [
    'virtual_disk' => false,
]);
```

### 7.5 复制虚拟磁盘

```php
$client->storage()->copyVirtualDisk(
    '[datastore1] template/Ubuntu18.vmdk',
    '[datastore1] vps-demo-001/Ubuntu18.vmdk',
    true
);
```

方法签名：

```php
copyVirtualDisk(string $sourceName, string $destinationName, bool $force = false, bool $wait = true, array $options = []): array
```

---

## 8. Task 任务查询服务

入口：

```php
$task = $client->task();
```

### 8.1 最近任务 / 任务详情 / 等待任务

```php
$client->task()->recent(50);
$client->task()->list(50);
$client->task()->info('haTask-1');
$client->task()->wait('haTask-1', 300, 1000);
```

---

## 9. Log 日志查询服务

入口：

```php
$logs = $client->logs();
```

### 9.1 日志描述 / 日志内容

```php
$client->logs()->descriptions();
$client->logs()->browse('hostd', 0, 200);
$client->host()->logs('vmkernel', 0, 200);
```

---

## 10. Inventory 清单服务

入口：

```php
$inventory = $client->inventory();
```

### 10.1 VM 清单

```php
$vms = $client->inventory()->virtualMachines();
```

### 10.2 Host 清单

```php
$hosts = $client->inventory()->hosts();
```

---

## 11. VPC / VLAN 使用建议

ESXi 单机没有云厂商意义上的完整 VPC，但可以用 **PortGroup + VLAN** 实现基础二层隔离。

推荐流程：

```php
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

$client->vps()->create([
    'name' => 'vps-a',
    'datastore' => 'datastore1',
    'network' => 'vpc-100',
    'num_cpus' => 1,
    'memory_mb' => 1024,
    'disk_gb' => 20,
]);

$client->vps()->setNetwork('vps-a', 'vpc-100');
```

说明：

- 相同 PortGroup 的 VPS 位于同一二层网络。
- 不同 PortGroup + 不同 VLAN 可实现基础隔离。
- 如果要让 VLAN 网络访问外网，物理交换机、上联口和网关必须支持对应 VLAN。
- 如果要做 IP/端口级安全组，需要 NSX、上游防火墙或 Guest OS 防火墙。

---

## 12. 高危操作提醒

以下接口会修改 ESXi 环境或影响业务，请谨慎调用：

- `vps()->create()`
- `vps()->resize()`
- `vps()->reconfigure()`
- `vps()->powerOff()`
- `vps()->reset()`
- `vps()->suspend()`
- `vps()->setNetwork()`
- `network()->createVirtualSwitch()`
- `network()->removeVirtualSwitch()`
- `network()->createPortGroup()`
- `network()->updatePortGroup()`
- `network()->removePortGroup()`
