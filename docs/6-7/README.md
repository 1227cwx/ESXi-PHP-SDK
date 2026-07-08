# ESXi 6.7 调用文档

> 版本目录：`docs/6-7`
> PHP 命名空间：`Cwx1227\Esxi\Version\V67`
> 调用入口：`Cwx1227\Esxi\EsxiClient`

## 目录

- [1. 初始化客户端](#1-初始化客户端)
- [2. 通用规则](#2-通用规则)
  - [2.1 Service 入口](#21-service-入口)
  - [2.2 对象参数格式](#22-对象参数格式)
  - [2.3 properties 属性路径](#23-properties-属性路径)
  - [2.4 返回格式](#24-返回格式)
  - [2.5 Task 等待规则](#25-task-等待规则)
  - [2.6 异常说明](#26-异常说明)
- [3. Auth 鉴权服务](#3-auth-鉴权服务)
- [4. Host 宿主机服务](#4-host-宿主机服务)
- [5. VPS / VM 虚拟机服务](#5-vps--vm-虚拟机服务)
- [6. Network 网络服务](#6-network-网络服务)
- [7. Storage 存储与文件服务](#7-storage-存储与文件服务)
- [8. Task 任务服务](#8-task-任务服务)
- [9. Monitor 监控服务](#9-monitor-监控服务)
- [10. Log 日志服务](#10-log-日志服务)
- [11. Inventory 清单服务](#11-inventory-清单服务)
- [12. 模板创建 VPS 调用链路](#12-模板创建-vps-调用链路)

---

## 1. 初始化客户端

### 请求

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

也可以使用快捷方式：

```php
$client = EsxiClient::connect('192.168.127.106', 'root', 'your-password', [
    'version' => '67',
    'ssl_verify' => false,
]);
```

### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 是 | string | - | `192.168.1.10`、`https://192.168.1.10` | ESXi 主机 IP 或域名。 |
| `username` | 是 | string | - | ESXi 用户名 | 登录 ESXi 的账号。 |
| `password` | 是 | string | - | ESXi 密码 | 登录 ESXi 的密码。 |
| `version` | 否 | string | `67` | 当前使用 `67` | 选择 SDK 接口版本。 |
| `ssl_verify` | 否 | bool | `false` | `true` / `false` | 是否校验 HTTPS 证书。自签证书测试环境通常传 `false`。 |
| `timeout` | 否 | int/float | `60` | 秒 | 请求超时时间。 |
| `connect_timeout` | 否 | int/float | `10` | 秒 | 建立连接超时时间。 |
| `endpoint` | 否 | string | `https://<host>/sdk` | 完整 URL | 自定义 ESXi SOAP endpoint。 |
| `soap_action` | 否 | string | `urn:vim25/6.7.3` | SOAPAction 字符串 | 特殊环境才需要覆盖。 |
| `auto_login` | 否 | bool | `true` | `true` / `false` | 创建客户端时是否自动登录。 |
| `locale` | 否 | string/null | `null` | 如 `zh_CN`、`en_US` | 登录会话 locale。 |

### 返回说明

`EsxiClient::make()` 和 `EsxiClient::connect()` 返回 `Cwx1227\Esxi\Version\V67\V67Client` 对象。后续通过 `$client->vps()`、`$client->host()` 等 Service 调用功能。

### 调用示例

```php
$client = EsxiClient::connect('192.168.127.106', 'root', 'your-password', [
    'version' => '67',
    'ssl_verify' => false,
]);

$about = $client->auth()->session();
```

---

## 2. 通用规则

### 2.1 Service 入口

| 入口 | Service | 说明 |
|---|---|---|
| `$client->auth()` | `AuthService` | 登录、退出、会话信息。 |
| `$client->host()` | `HostService` | 宿主机配置、监控、网络、存储、日志聚合查询。 |
| `$client->vps()` | `VpsService` | 虚拟机列表、创建、配置修改、电源控制、系统控制、网卡、删除。 |
| `$client->network()` | `NetworkService` | vSwitch、PortGroup、物理网卡、VMkernel 网卡、VLAN、安全策略、限速策略。 |
| `$client->storage()` | `StorageService` | Datastore 列表、用量、文件查询、目录创建、文件 / VMDK 复制。 |
| `$client->task()` | `TaskService` | 最近任务、任务详情、等待任务完成。 |
| `$client->tasks()` | `TaskService` | `$client->task()` 的别名。 |
| `$client->monitor()` | `MonitorService` | VM、宿主机、存储监控聚合。 |
| `$client->logs()` | `LogService` | 日志类型查询、日志内容读取。 |
| `$client->inventory()` | `InventoryService` | VM、Host、Datastore 清单聚合。 |

### 2.2 对象参数格式

多个方法使用 `mixed $vm`、`mixed $host`、`mixed $datastore`、`mixed $task`。这些参数用于定位 ESXi 对象。

#### VM 参数 `$vm`

| 可传格式 | 示例 | 说明 |
|---|---|---|
| VM 名称 | `'Ubuntu18'` | SDK 会按名称查找虚拟机。 |
| VM MoID | `'123'`、`'vm-123'` | 直接按 `VirtualMachine` MoRef 处理。 |
| 带 `mor` 的行数据 | `$row` | 例如 `vps()->rows()` 返回的一行。 |
| MoRef 数组 | `['type' => 'VirtualMachine', 'value' => '123']` | 显式传 ManagedObjectReference。 |
| `ManagedObjectReference` 对象 | `$mor` | SDK 内部值对象。 |

#### Host 参数 `$host`

| 可传格式 | 示例 | 说明 |
|---|---|---|
| `null` | `null` | 使用第一台 Host；单台 ESXi 通常就是当前宿主机。 |
| Host 名称 | `'localhost.localdomain'` | 按名称查找。 |
| 默认 Host MoID | `'ha-host'` | 单台 ESXi 常见 Host MoRef。 |
| 带 `mor` 的行数据 / MoRef 数组 / MoRef 对象 | 同上 | 显式传 HostSystem 引用。 |

#### Datastore 参数 `$datastore`

| 可传格式 | 示例 | 说明 |
|---|---|---|
| `null` | `null` | 使用第一个 Datastore。 |
| Datastore 名称 | `'datastore1'` | 按名称查找。 |
| Datastore MoID | `'datastore-12'` | 直接按 `Datastore` MoRef 处理。 |
| 带 `mor` 的行数据 / MoRef 数组 / MoRef 对象 | 同上 | 显式传 Datastore 引用。 |

#### Task 参数 `$task`

| 可传格式 | 示例 | 说明 |
|---|---|---|
| Task ID | `'haTask-1-vim.VirtualMachine.powerOn-123'` | 直接按 `Task` MoRef 处理。 |
| MoRef 数组 | `['type' => 'Task', 'value' => 'haTask-xxx']` | 显式传 Task 引用。 |
| `ManagedObjectReference` 对象 | `$mor` | SDK 内部值对象。 |

### 2.3 properties 属性路径

`array $properties = []` 表示要从 ESXi PropertyCollector 读取的属性路径。为空时使用 SDK 默认字段；传入数组时只读取指定字段。

例如：

```php
$result = $client->vps()->list([
    'name',
    'runtime.powerState',
    'summary.config.numCpu',
    'summary.config.memorySizeMB',
]);
```

| 属性路径 | 中文说明 | 常见返回值 |
|---|---|---|
| `name` | 虚拟机名称。 | `Ubuntu18` |
| `runtime.powerState` | 虚拟机电源状态。 | `poweredOn`、`poweredOff`、`suspended` |
| `summary.config.numCpu` | 虚拟机配置的 vCPU 数量。 | `1`、`2`、`4` |
| `summary.config.memorySizeMB` | 虚拟机配置的内存大小，单位 MB。 | `1024`、`2048` |

#### 常用 VM 属性路径

| 属性路径 | 说明 |
|---|---|
| `name` | VM 名称。 |
| `config.uuid` | VM BIOS UUID。 |
| `config.instanceUuid` | VM 实例 UUID。 |
| `config.files.vmPathName` | VMX 配置文件 datastore 路径。 |
| `summary.config.vmPathName` | 摘要中的 VMX 配置文件 datastore 路径。 |
| `config.guestFullName` | Guest OS 完整名称。 |
| `summary.config.guestFullName` | 摘要中的 Guest OS 完整名称。 |
| `config.guestId` | Guest OS ID，例如 `ubuntu64Guest`。 |
| `config.version` | VM 硬件版本，例如 `vmx-14`。 |
| `config.hardware.numCPU` | VM 配置的 vCPU 数。 |
| `summary.config.numCpu` | VM 摘要中的 vCPU 数。 |
| `config.hardware.memoryMB` | VM 配置的内存 MB。 |
| `summary.config.memorySizeMB` | VM 摘要中的内存 MB。 |
| `config.hardware.device` | VM 磁盘、网卡、控制器等虚拟硬件设备数组。 |
| `runtime.powerState` | 电源状态。 |
| `runtime.connectionState` | 连接状态。 |
| `runtime.bootTime` | 开机时间。 |
| `guest.guestState` | Guest OS 状态。 |
| `guest.ipAddress` | Guest OS IP 地址。 |
| `guest.toolsStatus` | VMware Tools 状态。 |
| `guest.toolsRunningStatus` | VMware Tools 运行状态，可由调用者按需读取。 |
| `summary.guest.ipAddress` | 摘要中的 Guest IP 地址。 |
| `summary.guest.toolsStatus` | 摘要中的 VMware Tools 状态。 |
| `summary.quickStats.overallCpuUsage` | VM CPU 使用量，单位 MHz。 |
| `summary.quickStats.overallCpuDemand` | VM CPU 需求量，单位 MHz。 |
| `summary.quickStats.hostMemoryUsage` | 宿主机侧占用内存，单位 MB。 |
| `summary.quickStats.guestMemoryUsage` | Guest OS 侧占用内存，单位 MB。 |
| `summary.quickStats.uptimeSeconds` | VM 运行时间，单位秒。 |
| `summary.storage.committed` | 已提交存储空间，单位字节。 |
| `summary.storage.uncommitted` | 未提交存储空间，单位字节。 |

#### 常用 Host 属性路径

| 属性路径 | 说明 |
|---|---|
| `name` | 宿主机名称。 |
| `hardware.systemInfo` | 硬件厂商、型号、UUID 等信息。 |
| `hardware.cpuInfo` | CPU 包、核心、线程等信息。 |
| `hardware.memorySize` | 宿主机总内存，单位字节。 |
| `config.product` | ESXi 产品版本信息。 |
| `config.network` | 宿主机网络完整配置。 |
| `config.network.vswitch` | 标准虚拟交换机列表。 |
| `config.network.portgroup` | 端口组列表。 |
| `config.network.pnic` | 物理网卡列表。 |
| `config.network.vnic` | VMkernel 网卡列表。 |
| `config.storageDevice` | 存储适配器、磁盘等设备信息。 |
| `summary.hardware` | 宿主机硬件摘要。 |
| `summary.quickStats` | 宿主机 CPU / 内存快速统计。 |
| `runtime.connectionState` | 宿主机连接状态。 |
| `runtime.powerState` | 宿主机电源状态。 |
| `runtime.inMaintenanceMode` | 是否处于维护模式。 |

#### 常用 Datastore 属性路径

| 属性路径 | 说明 |
|---|---|
| `name` | Datastore 名称。 |
| `summary.name` | Datastore 摘要名称。 |
| `summary.type` | 存储类型，例如 `VMFS`、`NFS`。 |
| `summary.url` | 存储 URL。 |
| `summary.capacity` | 总容量，单位字节。 |
| `summary.freeSpace` | 可用容量，单位字节。 |
| `summary.uncommitted` | 未提交容量，单位字节。 |
| `summary.accessible` | 是否可访问。 |
| `summary.maintenanceMode` | 维护模式状态。 |
| `browser` | HostDatastoreBrowser 引用，用于文件搜索。 |

#### 常用 Task 属性路径

| 属性路径 | 说明 |
|---|---|
| `info.key` | Task 唯一 key。 |
| `info.name` | Task 名称。 |
| `info.descriptionId` | Task 描述 ID。 |
| `info.entity` | Task 关联对象引用。 |
| `info.entityName` | Task 关联对象名称。 |
| `info.state` | Task 状态：`queued`、`running`、`success`、`error`。 |
| `info.cancelled` | 是否已取消。 |
| `info.cancelable` | 是否允许取消。 |
| `info.progress` | 进度百分比。 |
| `info.queueTime` | 入队时间。 |
| `info.startTime` | 开始时间。 |
| `info.completeTime` | 完成时间。 |
| `info.error` | 失败信息。 |
| `info.result` | Task 结果。 |

### 2.4 返回格式

多数 Service 方法返回统一包装：

```php
[
    'success' => true,
    'data' => [],
]
```

部分 `rows()`、`rawInfo()`、`network()->list*()`、`host()->list()`、`host()->networkConfig()` 方法返回 ESXi 原始数组，不额外包裹 `success/data`。每个方法下面会单独说明。

原始对象行通常包含：

| 字段 | 类型 | 说明 |
|---|---|---|
| `mor` | `ManagedObjectReference` | ESXi 对象引用。 |
| `moid` | string | 对象 ID。 |
| `type` | string | 对象类型，例如 `VirtualMachine`。 |
| 其他属性路径 | mixed | 由 `properties` 参数决定，例如 `name`、`runtime.powerState`。 |

### 2.5 Task 等待规则

创建、修改、删除、电源等 ESXi 异步任务方法一般有 `bool $wait = true` 参数。

#### `$wait = true`

SDK 会等待 Task 完成，成功返回：

```php
[
    'success' => true,
    'task' => [
        'id' => 'haTask-xxx',
        'state' => 'success',
    ],
    'data' => [
        'info.state' => 'success',
        'info.result' => null,
    ],
]
```

#### `$wait = false`

SDK 立即返回 Task 引用：

```php
[
    'success' => true,
    'task' => [
        'type' => 'Task',
        'value' => 'haTask-xxx',
    ],
    'data' => [],
]
```

### 2.6 异常说明

| 异常 | 说明 |
|---|---|
| `InvalidArgumentException` | 参数为空、类型不合法、数值范围不合法。 |
| `Cwx1227\Esxi\Exception\EsxiException` | SDK 通用异常，例如对象不存在、等待超时。 |
| `Cwx1227\Esxi\Exception\SoapFaultException` | ESXi SOAP Fault。 |
| `Cwx1227\Esxi\Exception\TaskFailedException` | ESXi Task 执行失败。 |

---

## 3. Auth 鉴权服务

### 3.1 `auth()->login()`

#### 方法说明

登录 ESXi，建立 Session。`auto_login=true` 时创建客户端会自动登录；关闭自动登录后可手动调用该方法。

#### 请求

```php
$client->auth()->login(?string $locale = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `locale` | 否 | string/null | `null` | `zh_CN`、`en_US` 或 `null` | ESXi 会话语言区域。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.session` | array/null | 当前登录 Session 信息。 |
| `data.about` | array | ESXi ServiceContent 中的 about 信息，包括产品名、版本、build 等。 |

#### 调用示例

```php
$result = $client->auth()->login('zh_CN');
```

### 3.2 `auth()->logout()`

#### 方法说明

退出当前 ESXi Session。

#### 请求

```php
$client->auth()->logout(): array
```

#### 参数说明

无参数。

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data` | array | 空数组。 |

#### 调用示例

```php
$client->auth()->logout();
```

### 3.3 `auth()->session()`

#### 方法说明

读取当前 Session 和 ESXi about 信息，不重新登录。

#### 请求

```php
$client->auth()->session(): array
```

#### 参数说明

无参数。

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.session` | array/null | 当前 Session 信息。 |
| `data.about` | array | ESXi about 信息。 |

#### 调用示例

```php
$session = $client->auth()->session();
```

---

## 4. Host 宿主机服务

### 4.1 `host()->info()`

#### 方法说明

读取 ESXi ServiceContent 的 about 信息，适合用于判断 ESXi 版本、build、API 类型。

#### 请求

```php
$client->host()->info(): array
```

#### 参数说明

无参数。

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 产品名称。 |
| `data.fullName` | string | ESXi 完整产品名。 |
| `data.version` | string | ESXi 版本。 |
| `data.build` | string | ESXi build 号。 |
| `data.apiType` | string | API 类型，单机 ESXi 通常为 `HostAgent`。 |
| `data.apiVersion` | string | API 版本。 |

#### 调用示例

```php
$info = $client->host()->info();
```

### 4.2 `host()->config()`

#### 方法说明

读取宿主机硬件、产品、网络、存储、运行状态等配置。

#### 请求

```php
$client->host()->config(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机；单台 ESXi 可不传。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 宿主机名称。 |
| `data.hardware.systemInfo` | array | 硬件厂商、型号、UUID。 |
| `data.hardware.cpuInfo` | array | CPU 信息。 |
| `data.hardware.memorySize` | int | 总内存，字节。 |
| `data.config.product` | array | ESXi 产品信息。 |
| `data.config.network` | array | 网络配置。 |
| `data.config.storageDevice` | array | 存储设备配置。 |
| `data.config.fileSystemVolume` | array | 文件系统卷信息。 |
| `data.summary.hardware` | array | 硬件摘要。 |
| `data.summary.config` | array | 配置摘要。 |
| `data.runtime.connectionState` | string | 连接状态。 |
| `data.runtime.powerState` | string | 电源状态。 |
| `data.runtime.inMaintenanceMode` | bool | 是否维护模式。 |

#### 调用示例

```php
$config = $client->host()->config();
```

### 4.3 `host()->list()`

#### 方法说明

读取 HostSystem 列表。单台 ESXi 一般只返回一条。

#### 请求

```php
$client->host()->list(array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `properties` | 否 | array | 默认 Host 摘要字段 | ESXi Host 属性路径数组 | 指定要返回的 Host 属性。 |

默认读取字段：

| 字段 | 说明 |
|---|---|
| `name` | 宿主机名称。 |
| `summary.hardware` | 硬件摘要。 |
| `summary.quickStats` | CPU / 内存快速统计。 |
| `summary.runtime.connectionState` | 连接状态。 |
| `summary.runtime.powerState` | 电源状态。 |
| `summary.runtime.inMaintenanceMode` | 是否维护模式。 |

#### 返回说明

该方法返回原始数组，不包裹 `success/data`。

| 字段 | 类型 | 说明 |
|---|---|---|
| `0.mor` | `ManagedObjectReference` | HostSystem 引用。 |
| `0.moid` | string | Host 对象 ID。 |
| `0.type` | string | 固定为 `HostSystem`。 |
| `0.<property>` | mixed | `properties` 指定的属性值。 |

#### 调用示例

```php
$hosts = $client->host()->list([
    'name',
    'summary.quickStats.overallCpuUsage',
]);
```

### 4.4 `host()->metrics()`

#### 方法说明

读取宿主机原始硬件、快速统计和运行状态。

#### 请求

```php
$client->host()->metrics(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 宿主机名称。 |
| `data.summary.hardware` | array | 硬件摘要。 |
| `data.summary.quickStats` | array | CPU / 内存快速统计。 |
| `data.summary.runtime` | array | 运行状态摘要。 |

#### 调用示例

```php
$metrics = $client->host()->metrics();
```

### 4.5 `host()->performance()`

#### 方法说明

读取宿主机 CPU、内存、存储用量，并换算成更适合业务使用的结构。

#### 请求

```php
$client->host()->performance(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string/null | 宿主机名称。 |
| `data.cpu.total_mhz` | int | CPU 总 MHz，按 `cpuMhz * numCpuCores` 计算。 |
| `data.cpu.used_mhz` | int | 已使用 CPU MHz。 |
| `data.cpu.used_percent` | float/null | CPU 使用率百分比。 |
| `data.memory.total_bytes` | int | 总内存字节。 |
| `data.memory.used_bytes` | int | 已用内存字节。 |
| `data.memory.used_mb` | int | 已用内存 MB。 |
| `data.memory.used_percent` | float/null | 内存使用率百分比。 |
| `data.storage` | array | `storage()->usage()` 的结果。 |
| `data.raw` | array | ESXi 原始 Host 属性。 |

#### 调用示例

```php
$perf = $client->host()->performance();
```

### 4.6 `host()->monitor()`

#### 方法说明

`host()->performance()` 的别名。

#### 请求

```php
$client->host()->monitor(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

同 `host()->performance()`。

#### 调用示例

```php
$monitor = $client->host()->monitor();
```

### 4.7 `host()->networkConfig()`

#### 方法说明

读取宿主机网络原始配置，包括标准虚拟交换机、端口组、物理网卡、VMkernel 网卡。

#### 请求

```php
$client->host()->networkConfig(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

该方法返回原始数组，不包裹 `success/data`。

| 字段 | 类型 | 说明 |
|---|---|---|
| `name` | string | 宿主机名称。 |
| `config.network.vswitch` | array | 标准虚拟交换机列表。 |
| `config.network.portgroup` | array | 端口组列表。 |
| `config.network.pnic` | array | 物理网卡列表。 |
| `config.network.vnic` | array | VMkernel 网卡列表。 |

#### 调用示例

```php
$networkConfig = $client->host()->networkConfig();
```

### 4.8 `host()->network()`

#### 方法说明

读取宿主机网络配置，返回值会包裹 `success/data`。

#### 请求

```php
$client->host()->network(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 宿主机名称。 |
| `data.config.network.vswitch` | array | 标准虚拟交换机列表。 |
| `data.config.network.portgroup` | array | 端口组列表。 |
| `data.config.network.pnic` | array | 物理网卡列表。 |
| `data.config.network.vnic` | array | VMkernel 网卡列表。 |

#### 调用示例

```php
$network = $client->host()->network();
```

### 4.9 `host()->virtualMachines()`

#### 方法说明

宿主机入口下查询虚拟机列表，内部调用 `vps()->list()`。

#### 请求

```php
$client->host()->virtualMachines(array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `properties` | 否 | array | `vps()->list()` 默认字段 | VM 属性路径数组 | 指定虚拟机返回字段。 |

#### 返回说明

同 `vps()->list()`。

#### 调用示例

```php
$vms = $client->host()->virtualMachines(['name', 'runtime.powerState']);
```

### 4.10 `host()->storage()`

#### 方法说明

宿主机入口下查询存储列表，内部调用 `storage()->list()`。

#### 请求

```php
$client->host()->storage(array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `properties` | 否 | array | `storage()->list()` 默认字段 | Datastore 属性路径数组 | 指定存储返回字段。 |

#### 返回说明

同 `storage()->list()`。

#### 调用示例

```php
$storage = $client->host()->storage();
```

### 4.11 `host()->files()`

#### 方法说明

宿主机入口下查询 datastore 文件，内部调用 `storage()->files()`。

#### 请求

```php
$client->host()->files(string $datastorePath, array $params = [], bool $wait = true): array
```

#### 参数说明

同 `storage()->files()`。

#### 返回说明

同 `storage()->files()`。

#### 调用示例

```php
$files = $client->host()->files('[datastore1] template', [
    'recursive' => false,
]);
```

### 4.12 `host()->tasks()`

#### 方法说明

宿主机入口下查询最近任务，内部调用 `task()->recent()`。

#### 请求

```php
$client->host()->tasks(int $limit = 50): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `limit` | 否 | int | `50` | 正整数 | 最多返回的任务数量。 |

#### 返回说明

同 `task()->recent()`。

#### 调用示例

```php
$tasks = $client->host()->tasks(20);
```

### 4.13 `host()->logDescriptions()`

#### 方法说明

宿主机入口下查询可读取的日志类型，内部调用 `logs()->descriptions()`。

#### 请求

```php
$client->host()->logDescriptions(mixed $host = null): array
```

#### 参数说明

同 `logs()->descriptions()`。

#### 返回说明

同 `logs()->descriptions()`。

#### 调用示例

```php
$descriptions = $client->host()->logDescriptions();
```

### 4.14 `host()->logs()`

#### 方法说明

宿主机入口下读取日志内容，内部调用 `logs()->browse()`。

#### 请求

```php
$client->host()->logs(string $key = 'hostd', int $start = 0, int $lines = 200, mixed $host = null): array
```

#### 参数说明

同 `logs()->browse()`。

#### 返回说明

同 `logs()->browse()`。

#### 调用示例

```php
$logs = $client->host()->logs('hostd', 0, 100);
```

---

## 5. VPS / VM 虚拟机服务

### 5.1 `vps()->list()`

#### 方法说明

查询虚拟机列表，适合业务侧分页、筛选前先拉取基础字段。

#### 请求

```php
$client->vps()->list(array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `properties` | 否 | array | 默认 VM 列表字段 | VM 属性路径数组 | 指定返回字段；为空读取默认字段。 |

默认读取字段：

| 字段 | 说明 |
|---|---|
| `name` | VM 名称。 |
| `config.uuid` | VM BIOS UUID。 |
| `config.instanceUuid` | VM 实例 UUID。 |
| `summary.config.vmPathName` | VMX 配置文件路径。 |
| `summary.config.guestFullName` | Guest OS 名称。 |
| `summary.config.numCpu` | vCPU 数。 |
| `summary.config.memorySizeMB` | 内存 MB。 |
| `runtime.powerState` | 电源状态。 |
| `summary.guest.ipAddress` | Guest IP。 |
| `summary.guest.toolsStatus` | VMware Tools 状态。 |
| `summary.quickStats.overallCpuUsage` | CPU 使用 MHz。 |
| `summary.quickStats.hostMemoryUsage` | 宿主机侧内存使用 MB。 |
| `summary.quickStats.guestMemoryUsage` | Guest 内存使用 MB。 |
| `summary.quickStats.uptimeSeconds` | 运行秒数。 |
| `summary.storage.committed` | 已提交磁盘字节。 |
| `summary.storage.uncommitted` | 未提交磁盘字节。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data` | array | VM 行数组。 |
| `data.*.mor` | `ManagedObjectReference` | VM 引用。 |
| `data.*.moid` | string | VM 对象 ID。 |
| `data.*.type` | string | 固定为 `VirtualMachine`。 |
| `data.*.<property>` | mixed | 请求的 VM 属性。 |

#### 调用示例

```php
$result = $client->vps()->list([
    'name',
    'runtime.powerState',
    'summary.config.numCpu',
    'summary.config.memorySizeMB',
]);
```

### 5.2 `vps()->rows()`

#### 方法说明

查询虚拟机原始列表，不包裹 `success/data`。适合 SDK 内部或需要直接使用 `mor` 的场景。

#### 请求

```php
$client->vps()->rows(array $properties = []): array
```

#### 参数说明

同 `vps()->list()`。

#### 返回说明

返回 VM 行数组，结构等同 `vps()->list()['data']`。

#### 调用示例

```php
$rows = $client->vps()->rows(['name']);
$vm = $rows[0] ?? null;
```

### 5.3 `vps()->info()`

#### 方法说明

查询指定虚拟机信息。支持按 VM 名称、MoID、MoRef 定位。

#### 请求

```php
$client->vps()->info(mixed $vm, array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要查询的虚拟机。 |
| `properties` | 否 | array | 默认 VM 详情字段 | VM 属性路径数组 | 指定返回字段。 |

默认读取字段：

| 字段 | 说明 |
|---|---|
| `name` | VM 名称。 |
| `config.uuid` | VM BIOS UUID。 |
| `config.instanceUuid` | VM 实例 UUID。 |
| `config.files.vmPathName` | VMX 文件路径。 |
| `config.guestFullName` | Guest OS 名称。 |
| `config.hardware.numCPU` | vCPU 数。 |
| `config.hardware.memoryMB` | 内存 MB。 |
| `config.hardware.device` | 虚拟硬件设备。 |
| `runtime.powerState` | 电源状态。 |
| `guest.ipAddress` | Guest IP。 |
| `summary.quickStats` | CPU / 内存快速统计。 |
| `summary.storage` | 存储统计。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.mor` | `ManagedObjectReference` | VM 引用。 |
| `data.moid` | string | VM 对象 ID。 |
| `data.type` | string | 固定为 `VirtualMachine`。 |
| `data.<property>` | mixed | 请求的 VM 属性。 |

#### 调用示例

```php
$info = $client->vps()->info('Ubuntu18', [
    'name',
    'config.hardware.device',
    'guest.ipAddress',
]);
```

### 5.4 `vps()->rawInfo()`

#### 方法说明

查询指定虚拟机原始信息，不包裹 `success/data`。

#### 请求

```php
$client->vps()->rawInfo(mixed $vm, array $properties = []): array
```

#### 参数说明

同 `vps()->info()`。

#### 返回说明

返回结构等同 `vps()->info()['data']`。

#### 调用示例

```php
$raw = $client->vps()->rawInfo('Ubuntu18', ['name', 'runtime.powerState']);
```

### 5.5 `vps()->status()`

#### 方法说明

查询虚拟机状态字段，包括电源状态、连接状态、启动时间、Guest 状态、Tools 状态、IP、整体健康状态。

#### 请求

```php
$client->vps()->status(mixed $vm): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要查询的虚拟机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | VM 名称。 |
| `data.runtime.powerState` | string | 电源状态。 |
| `data.runtime.connectionState` | string | 连接状态。 |
| `data.runtime.bootTime` | string/null | 启动时间。 |
| `data.guest.guestState` | string/null | Guest 状态。 |
| `data.guest.toolsStatus` | string/null | VMware Tools 状态。 |
| `data.guest.ipAddress` | string/null | Guest IP。 |
| `data.overallStatus` | string/null | 整体状态。 |

#### 调用示例

```php
$status = $client->vps()->status('Ubuntu18');
```

### 5.6 `vps()->config()`

#### 方法说明

查询虚拟机配置字段，包括 UUID、VMX 文件、Guest ID、硬件版本、虚拟硬件、备注。

#### 请求

```php
$client->vps()->config(mixed $vm): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要查询的虚拟机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | VM 名称。 |
| `data.config.uuid` | string | BIOS UUID。 |
| `data.config.instanceUuid` | string | 实例 UUID。 |
| `data.config.files` | array | VM 文件路径信息。 |
| `data.config.guestFullName` | string | Guest OS 名称。 |
| `data.config.guestId` | string | Guest OS ID。 |
| `data.config.version` | string | 虚拟硬件版本。 |
| `data.config.hardware` | array | CPU、内存、磁盘、网卡、控制器等硬件配置。 |
| `data.config.annotation` | string/null | 备注。 |

#### 调用示例

```php
$config = $client->vps()->config('Ubuntu18');
```

### 5.7 `vps()->usage()`

#### 方法说明

查询虚拟机 CPU、内存、磁盘、运行时间，并整理为业务易用结构。

#### 请求

```php
$client->vps()->usage(mixed $vm): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要查询的虚拟机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string/null | VM 名称。 |
| `data.power_state` | string/null | 电源状态。 |
| `data.cpu.num_cpu` | int/null | vCPU 数。 |
| `data.cpu.used_mhz` | int/null | CPU 使用 MHz。 |
| `data.cpu.demand_mhz` | int/null | CPU 需求 MHz。 |
| `data.memory.configured_mb` | int/null | 配置内存 MB。 |
| `data.memory.host_used_mb` | int/null | 宿主机侧内存使用 MB。 |
| `data.memory.guest_used_mb` | int/null | Guest 侧内存使用 MB。 |
| `data.disk.committed_bytes` | int/null | 已提交磁盘字节。 |
| `data.disk.uncommitted_bytes` | int/null | 未提交磁盘字节。 |
| `data.uptime_seconds` | int/null | 运行秒数。 |
| `data.raw` | array | ESXi 原始属性。 |

#### 调用示例

```php
$usage = $client->vps()->usage('Ubuntu18');
```

### 5.8 `vps()->metrics()`

#### 方法说明

`vps()->usage()` 的别名。

#### 请求

```php
$client->vps()->metrics(mixed $vm): array
```

#### 参数说明

同 `vps()->usage()`。

#### 返回说明

同 `vps()->usage()`。

#### 调用示例

```php
$metrics = $client->vps()->metrics('Ubuntu18');
```

### 5.9 `vps()->monitor()`

#### 方法说明

`vps()->usage()` 的别名。

#### 请求

```php
$client->vps()->monitor(mixed $vm): array
```

#### 参数说明

同 `vps()->usage()`。

#### 返回说明

同 `vps()->usage()`。

#### 调用示例

```php
$monitor = $client->vps()->monitor('Ubuntu18');
```

### 5.10 `vps()->create()`

#### 方法说明

创建虚拟机。支持两种磁盘模式：

1. 新建 VMDK：传 `disk_gb`，SDK 会创建新虚拟磁盘。
2. 使用已有 VMDK：传 `use_existing_disk=true` 和 `disk_path`，适合从模板 VMDK 复制后创建 VPS。

#### 请求

```php
$client->vps()->create(array $params, bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `params.name` | 是 | string | - | 非空字符串 | VM 名称，同时默认用于 VMX / VMDK 文件夹名。 |
| `params.datastore` | 是 | string | - | `datastore1` | 目标 Datastore 名称。 |
| `params.network` | 是 | string | - | PortGroup 名称 | VM 网卡绑定的端口组。相同端口组通常处于同一二层网络。 |
| `params.memory_mb` | 是 | int/string | - | 正整数 | VM 内存大小，单位 MB。 |
| `params.num_cpus` | 是 | int/string | - | 正整数 | VM vCPU 数。 |
| `params.disk_gb` | 新建磁盘必填；已有磁盘用于容量时必填其一 | int/string | - | 正整数 | 新建磁盘容量 GB；已有磁盘模式下也可用于换算 `capacityInKB`。 |
| `params.capacity_kb` | 已有磁盘模式下与 `disk_gb` 二选一 | int/string | - | 正整数 | 磁盘容量 KB。 |
| `params.use_existing_disk` | 否 | bool | `false` | `true` / `false` | 是否使用已有 VMDK。 |
| `params.existing_disk` | 否 | bool | `false` | `true` / `false` | `use_existing_disk` 的兼容别名。 |
| `params.disk_file_operation` | 否 | string/bool/null | `create` | `create`、`new`、`existing`、`use_existing`、`none`、`false`、`null` | 控制磁盘 fileOperation。`existing/use_existing/none/false/null` 表示使用已有磁盘。 |
| `params.disk_path` | 已有磁盘必填；新建磁盘可选 | string | `[datastore] name/name.vmdk` | `[datastore1] vm/vm.vmdk` | VMDK datastore 路径。 |
| `params.vm_path` | 否 | string | `[datastore] name/name.vmx` | `[datastore1] vm/vm.vmx` | VMX datastore 路径。 |
| `params.guest_id` | 否 | string | `otherGuest64` | `ubuntu64Guest` 等 | ESXi GuestId。 |
| `params.hardware_version` | 否 | string | - | `vmx-14` 等 | VM 硬件版本。 |
| `params.vmx_version` | 否 | string | - | `vmx-14` 等 | `hardware_version` 别名。 |
| `params.version` | 否 | string | - | `vmx-14` 等 | `hardware_version` 别名。 |
| `params.scsi_controller` | 否 | string | `lsilogic` | `lsilogic`、`lsisas`、`buslogic`、`pvscsi` 或 VMware 原始类型名 | SCSI 控制器类型。 |
| `params.scsi_controller_type` | 否 | string | `lsilogic` | 同上 | `scsi_controller` 别名。 |
| `params.adapter_type` | 否 | string | `vmxnet3` | `vmxnet3`、`e1000`、`e1000e` 或 VMware 原始类型名 | 网卡类型。 |
| `params.thin_provision` | 否 | bool | `true` | `true` / `false` | 新建磁盘是否精简置备；已有磁盘模式不设置。 |
| `params.folder` | 否 | mixed | `ha-folder-vm` | Folder MoRef 格式 | VM Folder。单机 ESXi 默认即可。 |
| `params.resource_pool` | 否 | mixed | `ha-root-pool` | ResourcePool MoRef 格式 | 资源池。单机 ESXi 默认即可。 |
| `params.host` | 否 | mixed | `ha-host` | Host MoRef 格式 | 目标 Host。单机 ESXi 默认即可。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待创建任务完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。`data` 中额外包含：

| 字段 | 类型 | 说明 |
|---|---|---|
| `data.name` | string | 创建的 VM 名称。 |
| `data.datastore` | string | 目标 Datastore。 |
| `data.network` | string | 绑定的端口组。 |

#### 调用示例

新建磁盘：

```php
$result = $client->vps()->create([
    'name' => 'vps-demo-001',
    'datastore' => 'datastore1',
    'network' => 'VM Network',
    'num_cpus' => 1,
    'memory_mb' => 1024,
    'disk_gb' => 20,
    'guest_id' => 'ubuntu64Guest',
    'adapter_type' => 'vmxnet3',
]);
```

使用已有模板磁盘：

```php
$result = $client->vps()->create([
    'name' => 'vps-demo-002',
    'datastore' => 'datastore1',
    'network' => 'VM Network',
    'num_cpus' => 1,
    'memory_mb' => 1024,
    'use_existing_disk' => true,
    'disk_path' => '[datastore1] vps-demo-002/Ubuntu18.vmdk',
    'disk_gb' => 20,
    'guest_id' => 'ubuntu64Guest',
]);
```

### 5.11 `vps()->resize()`

#### 方法说明

修改 VM 配置，内部调用 `vps()->modifyConfig()`。

#### 请求

```php
$client->vps()->resize(mixed $vm, array $params, bool $wait = true): array
```

#### 参数说明

同 `vps()->modifyConfig()`。

#### 返回说明

同 `vps()->modifyConfig()`。

#### 调用示例

```php
$client->vps()->resize('Ubuntu18', [
    'num_cpus' => 2,
    'memory_mb' => 2048,
]);
```

### 5.12 `vps()->modifyConfig()`

#### 方法说明

修改 VM 配置。支持修改 CPU、内存、扩容已有硬盘、添加硬盘、添加网卡。

#### 请求

```php
$client->vps()->modifyConfig(mixed $vm, array $params, bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要修改的 VM。 |
| `params.num_cpus` | 否 | int/string | - | 正整数 | 修改 vCPU 数。 |
| `params.cpu` | 否 | int/string | - | 正整数 | `num_cpus` 的别名。 |
| `params.memory_mb` | 否 | int/string | - | 正整数 | 修改内存 MB。 |
| `params.disk_gb` | 否 | int/string | - | 正整数 | 扩容已有硬盘到指定 GB。 |
| `params.disk_size_gb` | 否 | int/string | - | 正整数 | `disk_gb` 的别名。 |
| `params.capacity_gb` | 否 | int/string | - | 正整数 | `disk_gb` 的别名。 |
| `params.capacity_kb` | 否 | int/string | - | 正整数 | 扩容已有硬盘到指定 KB。 |
| `params.disk_key` | 否 | int/string | - | 虚拟磁盘 key | 指定要扩容的磁盘。 |
| `params.key` | 否 | int/string | - | 虚拟磁盘 key | 指定要扩容的磁盘；也用于添加设备 key。 |
| `params.controller_key` | 否 | int/string | - | 控制器 key | 和 `unit_number` 配合选择磁盘，或添加磁盘时指定控制器。 |
| `params.unit_number` | 否 | int/string | 自动选择 | `0-15`，不能为 `7` | SCSI 单元号。 |
| `params.add_disk` | 否 | array | - | 见下方添加硬盘参数 | 添加一个硬盘。 |
| `params.add_disks` | 否 | array | - | 硬盘参数数组 | 添加多个硬盘。 |
| `params.add_network` | 否 | array/string | - | 端口组名或网卡参数 | 添加一个网卡。 |
| `params.add_networks` | 否 | array | - | 网卡参数数组 | 添加多个网卡。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

添加硬盘参数：

| 参数 | 是否必填 | 类型 | 默认值 | 说明 |
|---|---:|---|---|---|
| `disk_gb` / `size_gb` / `capacity_gb` | 新建磁盘必填 | int/string | - | 新磁盘容量 GB。 |
| `capacity_kb` | 否 | int/string | - | 新磁盘容量 KB。 |
| `use_existing_disk` / `existing_disk` / `disk_file_operation` | 否 | bool/string/null | `false` | 是否使用已有 VMDK。 |
| `disk_path` | 使用已有磁盘必填；新建磁盘可选 | string | 自动生成 | VMDK datastore 路径。 |
| `datastore` | 自动生成路径时必填其一 | string | - | 目标 Datastore。 |
| `folder` | 否 | string | VM 名称 | 自动生成磁盘路径时使用的目录。 |
| `disk_mode` | 否 | string | `persistent` | 磁盘模式。 |
| `thin_provision` | 否 | bool | `true` | 新建磁盘是否精简置备。 |
| `controller_key` | 否 | int/string | 第一个 SCSI 控制器 | 指定控制器。 |
| `unit_number` | 否 | int/string | 自动选择 | SCSI 单元号，`0-15` 且不能为 `7`。 |
| `key` | 否 | int | 自动负数 key | 设备 key。 |

添加网卡参数：

| 参数 | 是否必填 | 类型 | 默认值 | 说明 |
|---|---:|---|---|---|
| `network` / `port_group` / `name` | 是 | string | - | 端口组名称。 |
| `adapter_type` | 否 | string | `vmxnet3` | `vmxnet3`、`e1000`、`e1000e` 或 VMware 原始类型名。 |
| `start_connected` | 否 | bool | `true` | 开机时连接。 |
| `allow_guest_control` | 否 | bool | `true` | 是否允许 Guest 控制连接。 |
| `connected` | 否 | bool | `true` | 当前是否连接。 |
| `address_type` | 否 | string | `generated` | MAC 地址类型。 |
| `key` | 否 | int | 自动负数 key | 设备 key。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->modifyConfig('Ubuntu18', [
    'num_cpus' => 2,
    'memory_mb' => 2048,
    'disk_gb' => 40,
    'add_disk' => [
        'datastore' => 'datastore1',
        'disk_gb' => 100,
    ],
    'add_network' => [
        'network' => 'VPC-100',
        'adapter_type' => 'vmxnet3',
    ],
]);
```

### 5.13 `vps()->resizeDisk()`

#### 方法说明

扩容虚拟机已有硬盘。可直接传整数 GB，也可传数组指定磁盘选择参数。

#### 请求

```php
$client->vps()->resizeDisk(mixed $vm, int|array $params, bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要扩容的 VM。 |
| `params` | 是 | int/array | - | `40` 或 `['disk_gb' => 40]` | 目标容量。数组可包含 `disk_key`、`controller_key`、`unit_number`。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->resizeDisk('Ubuntu18', 40);
```

### 5.14 `vps()->addDisk()`

#### 方法说明

给虚拟机添加一块硬盘，内部调用 `modifyConfig(['add_disk' => ...])`。

#### 请求

```php
$client->vps()->addDisk(mixed $vm, array $params, bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 目标 VM。 |
| `params` | 是 | array | - | 见 `modifyConfig()` 添加硬盘参数 | 硬盘参数。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->addDisk('Ubuntu18', [
    'datastore' => 'datastore1',
    'disk_gb' => 50,
]);
```

### 5.15 `vps()->addNetwork()`

#### 方法说明

给虚拟机添加一张网卡并绑定到指定端口组。

#### 请求

```php
$client->vps()->addNetwork(mixed $vm, string $networkName, array $params = [], bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 目标 VM。 |
| `networkName` | 是 | string | - | PortGroup 名称 | 要绑定的端口组。 |
| `params.adapter_type` | 否 | string | `vmxnet3` | `vmxnet3`、`e1000`、`e1000e` | 网卡类型。 |
| `params.start_connected` | 否 | bool | `true` | `true` / `false` | 开机时连接。 |
| `params.allow_guest_control` | 否 | bool | `true` | `true` / `false` | 允许 Guest 控制。 |
| `params.connected` | 否 | bool | `true` | `true` / `false` | 当前连接状态。 |
| `params.address_type` | 否 | string | `generated` | `generated` 等 | MAC 地址类型。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->addNetwork('Ubuntu18', 'VPC-100', [
    'adapter_type' => 'vmxnet3',
]);
```

### 5.16 `vps()->reconfigure()`

#### 方法说明

直接调用 ESXi `ReconfigVM_Task`，用于高级自定义配置。普通业务优先使用 `modifyConfig()`、`resizeDisk()`、`addDisk()`、`addNetwork()`。

#### 请求

```php
use Cwx1227\Esxi\Value\DataObject;

$client->vps()->reconfigure(mixed $vm, array|DataObject $configSpec, bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 目标 VM。 |
| `configSpec` | 是 | array/`DataObject` | - | `VirtualMachineConfigSpec` 字段 | ESXi VM 配置规格。数组会自动包装成 `VirtualMachineConfigSpec`。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->reconfigure('Ubuntu18', [
    'annotation' => 'created by sdk',
]);
```

### 5.17 `vps()->powerOn()`

#### 方法说明

开启虚拟机电源。

#### 请求

```php
$client->vps()->powerOn(mixed $vm, bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 目标 VM。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->powerOn('Ubuntu18');
```

### 5.18 `vps()->powerOff()`

#### 方法说明

关闭虚拟机电源，属于电源级操作。

#### 请求

```php
$client->vps()->powerOff(mixed $vm, bool $wait = true): array
```

#### 参数说明

同 `vps()->powerOn()`。

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->powerOff('Ubuntu18');
```

### 5.19 `vps()->reset()`

#### 方法说明

重置虚拟机电源，类似按下重启按钮。

#### 请求

```php
$client->vps()->reset(mixed $vm, bool $wait = true): array
```

#### 参数说明

同 `vps()->powerOn()`。

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->reset('Ubuntu18');
```

### 5.20 `vps()->suspend()`

#### 方法说明

挂起虚拟机。

#### 请求

```php
$client->vps()->suspend(mixed $vm, bool $wait = true): array
```

#### 参数说明

同 `vps()->powerOn()`。

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->suspend('Ubuntu18');
```

### 5.21 `vps()->shutdownGuest()`

#### 方法说明

向 Guest OS 发送系统关机指令。SDK 只封装 ESXi API 调用，不在方法内部等待或检查 VMware Tools 状态；是否等待 `guest.toolsRunningStatus = guestToolsRunning` 属于调用方业务逻辑。

#### 请求

```php
$client->vps()->shutdownGuest(mixed $vm): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 目标 VM。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | API 调用成功时为 `true`。 |
| `data` | array | 空数组；系统关机本身不是 Task。 |

#### 调用示例

```php
$client->vps()->shutdownGuest('Ubuntu18');
```

### 5.22 `vps()->rebootGuest()`

#### 方法说明

向 Guest OS 发送系统重启指令。SDK 只封装 ESXi API 调用，不在方法内部等待或检查 VMware Tools 状态。

#### 请求

```php
$client->vps()->rebootGuest(mixed $vm): array
```

#### 参数说明

同 `vps()->shutdownGuest()`。

#### 返回说明

同 `vps()->shutdownGuest()`。

#### 调用示例

```php
$client->vps()->rebootGuest('Ubuntu18');
```

### 5.23 `vps()->delete()`

#### 方法说明

删除虚拟机，内部调用 ESXi `Destroy_Task`。

#### 请求

```php
$client->vps()->delete(mixed $vm, bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要删除的 VM。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。

#### 调用示例

```php
$client->vps()->delete('Ubuntu18');
```

### 5.24 `vps()->destroy()`

#### 方法说明

`vps()->delete()` 的别名。

#### 请求

```php
$client->vps()->destroy(mixed $vm, bool $wait = true): array
```

#### 参数说明

同 `vps()->delete()`。

#### 返回说明

同 `vps()->delete()`。

#### 调用示例

```php
$client->vps()->destroy('Ubuntu18');
```

### 5.25 `vps()->nics()`

#### 方法说明

查询虚拟机网卡设备列表。只返回 `VirtualE1000`、`VirtualE1000e`、`VirtualVmxnet3` 类型设备。

#### 请求

```php
$client->vps()->nics(mixed $vm): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 目标 VM。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data` | array | 网卡设备数组。 |
| `data.*._xsi_type` | string | 网卡类型，例如 `VirtualVmxnet3`。 |
| `data.*.key` | int | 设备 key。 |
| `data.*.backing.deviceName` | string | 绑定的端口组名称。 |
| `data.*.connectable` | array | 连接配置。 |
| `data.*.macAddress` | string/null | MAC 地址。 |

#### 调用示例

```php
$nics = $client->vps()->nics('Ubuntu18');
```

### 5.26 `vps()->setNetwork()`

#### 方法说明

把虚拟机第一张网卡切换到指定端口组。如果 VM 没有网卡，则添加一张网卡。

#### 请求

```php
$client->vps()->setNetwork(mixed $vm, string $networkName, array $params = [], bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 目标 VM。 |
| `networkName` | 是 | string | - | PortGroup 名称 | 目标端口组，不能为空。 |
| `params.adapter_type` | 否 | string | 当前网卡类型或 `VirtualVmxnet3` | `vmxnet3`、`e1000`、`e1000e` 或 VMware 原始类型名 | 网卡类型。 |
| `params.start_connected` | 否 | bool | `true` | `true` / `false` | 开机时连接。 |
| `params.allow_guest_control` | 否 | bool | `true` | `true` / `false` | 允许 Guest 控制。 |
| `params.connected` | 否 | bool | `true` | `true` / `false` | 当前连接状态。 |
| `params.address_type` | 否 | string | `generated` | `generated` 等 | MAC 地址类型。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。`data.network` 为目标端口组名称。

#### 调用示例

```php
$client->vps()->setNetwork('Ubuntu18', 'VPC-200', [
    'adapter_type' => 'vmxnet3',
]);
```

---

## 6. Network 网络服务

### 6.1 `network()->listVirtualSwitches()`

#### 方法说明

查询宿主机标准虚拟交换机列表。

#### 请求

```php
$client->network()->listVirtualSwitches(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

该方法返回原始数组，不包裹 `success/data`。

| 字段 | 类型 | 说明 |
|---|---|---|
| `*.name` | string | vSwitch 名称。 |
| `*.key` | string | vSwitch key。 |
| `*.numPorts` | int | 端口数量。 |
| `*.mtu` | int | MTU。 |
| `*.pnic` | array | 绑定的物理网卡。 |
| `*.spec` | array | vSwitch 配置规格。 |

#### 调用示例

```php
$switches = $client->network()->listVirtualSwitches();
```

### 6.2 `network()->listPortGroups()`

#### 方法说明

查询宿主机端口组列表。VPS 绑定网络时使用的 `network` 参数就是端口组名称。

#### 请求

```php
$client->network()->listPortGroups(mixed $host = null): array
```

#### 参数说明

同 `network()->listVirtualSwitches()`。

#### 返回说明

该方法返回原始数组，不包裹 `success/data`。

| 字段 | 类型 | 说明 |
|---|---|---|
| `*.key` | string | 端口组 key。 |
| `*.spec.name` | string | 端口组名称。 |
| `*.spec.vswitchName` | string | 所属 vSwitch 名称。 |
| `*.spec.vlanId` | int | VLAN ID，`0-4095`。 |
| `*.spec.policy` | array | 安全策略、流量整形策略等。 |

#### 调用示例

```php
$portGroups = $client->network()->listPortGroups();
```

### 6.3 `network()->listPhysicalNics()`

#### 方法说明

查询宿主机物理网卡列表。

#### 请求

```php
$client->network()->listPhysicalNics(mixed $host = null): array
```

#### 参数说明

同 `network()->listVirtualSwitches()`。

#### 返回说明

该方法返回原始数组，不包裹 `success/data`。

| 字段 | 类型 | 说明 |
|---|---|---|
| `*.device` | string | 物理网卡名称，例如 `vmnic0`。 |
| `*.mac` | string | MAC 地址。 |
| `*.linkSpeed` | array/null | 链路速度。 |
| `*.spec` | array | 物理网卡配置。 |

#### 调用示例

```php
$pnics = $client->network()->listPhysicalNics();
```

### 6.4 `network()->listVmKernelNics()`

#### 方法说明

查询 VMkernel 网卡列表，例如管理网口 `vmk0`。

#### 请求

```php
$client->network()->listVmKernelNics(mixed $host = null): array
```

#### 参数说明

同 `network()->listVirtualSwitches()`。

#### 返回说明

该方法返回原始数组，不包裹 `success/data`。

| 字段 | 类型 | 说明 |
|---|---|---|
| `*.device` | string | VMkernel 网卡名称，例如 `vmk0`。 |
| `*.portgroup` | string | 所属端口组。 |
| `*.spec.ip` | array | IP 配置。 |
| `*.spec.mtu` | int | MTU。 |

#### 调用示例

```php
$vmk = $client->network()->listVmKernelNics();
```

### 6.5 `network()->createVirtualSwitch()`

#### 方法说明

创建标准虚拟交换机，可指定端口数量、MTU、绑定物理网卡。

#### 请求

```php
$client->network()->createVirtualSwitch(array $params, mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `params.name` | 是 | string | - | 非空字符串 | vSwitch 名称。 |
| `params.num_ports` | 否 | int/string | `128` | 正整数 | 端口数量。 |
| `params.mtu` | 否 | int/string | ESXi 默认 | 正整数 | MTU。 |
| `params.pnics` | 否 | array/string | 不绑定 | `['vmnic1']` | 绑定的物理网卡设备名。 |
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 创建的 vSwitch 名称。 |

#### 调用示例

```php
$client->network()->createVirtualSwitch([
    'name' => 'vSwitchVPC',
    'num_ports' => 128,
    'mtu' => 1500,
    'pnics' => ['vmnic1'],
]);
```

### 6.6 `network()->removeVirtualSwitch()`

#### 方法说明

删除标准虚拟交换机。

#### 请求

```php
$client->network()->removeVirtualSwitch(string $name, mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `name` | 是 | string | - | vSwitch 名称 | 要删除的 vSwitch。 |
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 删除的 vSwitch 名称。 |

#### 调用示例

```php
$client->network()->removeVirtualSwitch('vSwitchVPC');
```

### 6.7 `network()->createPortGroup()`

#### 方法说明

创建端口组，可设置 VLAN ID、端口组安全策略、流量整形 / 带宽限制。VPS 创建或修改网络时传入该端口组名称即可绑定到该网络。

#### 请求

```php
$client->network()->createPortGroup(array $params, mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `params.name` | 是 | string | - | 非空字符串 | 端口组名称。 |
| `params.vswitch` | 是 | string | - | vSwitch 名称 | 所属标准虚拟交换机。 |
| `params.vlan_id` | 否 | int/string | `0` | `0-4095` | VLAN ID。`0` 表示不打 VLAN；`4095` 常用于 VGT。 |
| `params.security.allow_promiscuous` | 否 | bool | 不设置 | `true` / `false` | 混杂模式。 |
| `params.security.mac_changes` | 否 | bool | 不设置 | `true` / `false` | 是否允许 MAC 地址更改。 |
| `params.security.forged_transmits` | 否 | bool | 不设置 | `true` / `false` | 是否允许伪传输。 |
| `params.bandwidth_limit_bps` | 否 | int/string | 不设置 | 正整数 | 快捷限速参数，同时设置平均带宽和峰值带宽。 |
| `params.burst_size` | 否 | int/string | `1048576` | 正整数 | 使用 `bandwidth_limit_bps` 时的突发大小。 |
| `params.shaping.enabled` | 否 | bool | 不设置 | `true` / `false` | 是否启用流量整形。 |
| `params.shaping.average_bandwidth` | 否 | int/string | 不设置 | 正整数 | 平均带宽 bps。 |
| `params.shaping.peak_bandwidth` | 否 | int/string | 不设置 | 正整数 | 峰值带宽 bps。 |
| `params.shaping.burst_size` | 否 | int/string | 不设置 | 正整数 | 突发大小。 |
| `params.bandwidth` | 否 | array | - | 同 `shaping` | `shaping` 的别名。 |
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 端口组名称。 |
| `data.vswitch` | string | 所属 vSwitch。 |
| `data.vlan_id` | int | VLAN ID。 |

#### 调用示例

```php
$client->network()->createPortGroup([
    'name' => 'VPC-100',
    'vswitch' => 'vSwitch0',
    'vlan_id' => 100,
    'security' => [
        'allow_promiscuous' => false,
        'mac_changes' => false,
        'forged_transmits' => false,
    ],
    'bandwidth_limit_bps' => 100 * 1000 * 1000,
]);
```

### 6.8 `network()->updatePortGroup()`

#### 方法说明

更新端口组配置。参数结构与 `createPortGroup()` 相同。

#### 请求

```php
$client->network()->updatePortGroup(array $params, mixed $host = null): array
```

#### 参数说明

同 `network()->createPortGroup()`。

#### 返回说明

同 `network()->createPortGroup()`。

#### 调用示例

```php
$client->network()->updatePortGroup([
    'name' => 'VPC-100',
    'vswitch' => 'vSwitch0',
    'vlan_id' => 101,
]);
```

### 6.9 `network()->removePortGroup()`

#### 方法说明

删除端口组。

#### 请求

```php
$client->network()->removePortGroup(string $name, mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `name` | 是 | string | - | PortGroup 名称 | 要删除的端口组。 |
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | 删除的端口组名称。 |

#### 调用示例

```php
$client->network()->removePortGroup('VPC-100');
```

---

## 7. Storage 存储与文件服务

### 7.1 `storage()->list()`

#### 方法说明

查询 Datastore 列表。

#### 请求

```php
$client->storage()->list(array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `properties` | 否 | array | 默认 Datastore 字段 | Datastore 属性路径数组 | 指定返回字段。 |

默认读取字段：

| 字段 | 说明 |
|---|---|
| `name` | Datastore 名称。 |
| `summary.name` | 摘要名称。 |
| `summary.type` | 类型。 |
| `summary.url` | URL。 |
| `summary.capacity` | 总容量字节。 |
| `summary.freeSpace` | 可用容量字节。 |
| `summary.uncommitted` | 未提交容量字节。 |
| `summary.accessible` | 是否可访问。 |
| `summary.maintenanceMode` | 维护模式。 |
| `browser` | 文件浏览器引用。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data` | array | Datastore 行数组。 |
| `data.*.mor` | `ManagedObjectReference` | Datastore 引用。 |
| `data.*.<property>` | mixed | 请求的 Datastore 属性。 |

#### 调用示例

```php
$stores = $client->storage()->list();
```

### 7.2 `storage()->rows()`

#### 方法说明

查询 Datastore 原始列表，不包裹 `success/data`。

#### 请求

```php
$client->storage()->rows(array $properties = []): array
```

#### 参数说明

同 `storage()->list()`。

#### 返回说明

返回结构等同 `storage()->list()['data']`。

#### 调用示例

```php
$rows = $client->storage()->rows(['name', 'summary.freeSpace']);
```

### 7.3 `storage()->info()`

#### 方法说明

查询指定 Datastore 详情。

#### 请求

```php
$client->storage()->info(mixed $datastore, array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `datastore` | 是 | mixed | - | 见 [Datastore 参数](#datastore-参数-datastore) | 要查询的 Datastore。 |
| `properties` | 否 | array | 默认详情字段 | Datastore 属性路径数组 | 指定返回字段。 |

默认读取字段：

| 字段 | 说明 |
|---|---|
| `name` | Datastore 名称。 |
| `summary` | 摘要信息。 |
| `info` | 详细信息。 |
| `host` | 挂载该 Datastore 的 Host。 |
| `vm` | 使用该 Datastore 的 VM。 |
| `browser` | 文件浏览器引用。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.mor` | `ManagedObjectReference` | Datastore 引用。 |
| `data.<property>` | mixed | 请求的 Datastore 属性。 |

#### 调用示例

```php
$info = $client->storage()->info('datastore1');
```

### 7.4 `storage()->usage()`

#### 方法说明

查询一个或全部 Datastore 用量，并计算已用容量和使用率。

#### 请求

```php
$client->storage()->usage(mixed $datastore = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `datastore` | 否 | mixed | `null` | 见 [Datastore 参数](#datastore-参数-datastore) | 不传返回全部；传入则返回单个。 |

#### 返回说明

不传 `datastore` 时，`data` 是数组；传入 `datastore` 时，`data` 是单个对象。

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.*.name` / `data.name` | string/null | Datastore 名称。 |
| `data.*.type` / `data.type` | string/null | 存储类型。 |
| `data.*.capacity_bytes` / `data.capacity_bytes` | int | 总容量字节。 |
| `data.*.free_bytes` / `data.free_bytes` | int | 可用容量字节。 |
| `data.*.used_bytes` / `data.used_bytes` | int | 已用容量字节。 |
| `data.*.uncommitted_bytes` / `data.uncommitted_bytes` | int | 未提交容量字节。 |
| `data.*.used_percent` / `data.used_percent` | float/null | 使用率百分比。 |
| `data.*.accessible` / `data.accessible` | bool/null | 是否可访问。 |
| `data.*.mor` / `data.mor` | mixed | Datastore 引用。 |

#### 调用示例

```php
$allUsage = $client->storage()->usage();
$oneUsage = $client->storage()->usage('datastore1');
```

### 7.5 `storage()->files()`

#### 方法说明

查询 datastore 路径下的文件。支持递归、文件类型过滤、匹配模式、返回详情控制。

#### 请求

```php
$client->storage()->files(string $datastorePath, array $params = [], bool $wait = true): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `datastorePath` | 是 | string | - | `[datastore1] folder` | Datastore 路径，必须以 `[datastore]` 开头。 |
| `params.datastore` | 否 | mixed | 从路径解析 | Datastore 参数 | 指定 Datastore，用于解析 browser。 |
| `params.recursive` | 否 | bool | `false` | `true` / `false` | 是否递归搜索子目录。 |
| `params.file_types` | 否 | array/string | 不过滤 | `folder`、`vm`、`vmconfig`、`config`、`disk`、`vmdk`、`log`、`iso`、`floppy` 或 VMware 原始 Query 类型 | 文件类型过滤。 |
| `params.details.fileType` | 否 | bool | `true` | `true` / `false` | 是否返回文件类型。 |
| `params.details.fileSize` | 否 | bool | `true` | `true` / `false` | 是否返回文件大小。 |
| `params.details.modification` | 否 | bool | `true` | `true` / `false` | 是否返回修改时间。 |
| `params.details.fileOwner` | 否 | bool | `false` | `true` / `false` | 是否返回文件所有者。 |
| `params.search_case_insensitive` | 否 | bool | ESXi 默认 | `true` / `false` | 是否大小写不敏感。 |
| `params.match_pattern` | 否 | array/string | 不过滤 | `['*.vmdk']` | 文件名匹配模式。 |
| `params.sort_folders_first` | 否 | bool | `true` | `true` / `false` | 是否目录优先。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待搜索 Task 完成。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。`wait=true` 时 `data` 额外包含：

| 字段 | 类型 | 说明 |
|---|---|---|
| `data.path` | string | 查询路径。 |
| `data.recursive` | bool | 是否递归。 |
| `data.info.result` | array/null | ESXi 文件搜索结果，通常包含 `folderPath`、`file` 等。 |

#### 调用示例

```php
$files = $client->storage()->files('[datastore1] template', [
    'recursive' => false,
    'file_types' => ['vmdk'],
    'match_pattern' => ['*.vmdk'],
]);
```

### 7.6 `storage()->copyFile()`

#### 方法说明

复制 datastore 文件。该方法标记为内部辅助，但当前可调用。若源和目标都以 `.vmdk` 结尾，默认自动改用 `copyVirtualDisk()`，以符合 VMDK 复制要求。

#### 请求

```php
$client->storage()->copyFile(
    string $sourceName,
    string $destinationName,
    bool $force = false,
    bool $wait = true,
    array $options = []
): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `sourceName` | 是 | string | - | `[datastore1] template/a.iso` | 源 datastore 路径。 |
| `destinationName` | 是 | string | - | `[datastore1] vm/a.iso` | 目标 datastore 路径，不能和源相同。 |
| `force` | 否 | bool | `false` | `true` / `false` | 目标存在时是否覆盖。 |
| `wait` | 否 | bool | `true` | `true` / `false` | 是否等待 Task 完成。 |
| `options.virtual_disk` | 否 | bool | 自动判断 | `true` / `false` | 是否按虚拟磁盘复制。传 `false` 可强制普通文件复制。 |
| `options.source_datacenter` | 否 | mixed | `null` | Datacenter MoRef 格式 | 源 Datacenter。单台 ESXi 一般不传。 |
| `options.destination_datacenter` | 否 | mixed | `null` | Datacenter MoRef 格式 | 目标 Datacenter。单台 ESXi 一般不传。 |
| `options.destination_spec` | 否 | array/DataObject | `null` | `FileBackedVirtualDiskSpec` | VMDK 复制目标规格。 |
| `options.dest_spec` | 否 | array/DataObject | `null` | 同上 | `destination_spec` 别名。 |

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。`data` 包含：

| 字段 | 类型 | 说明 |
|---|---|---|
| `data.source` | string | 源路径。 |
| `data.destination` | string | 目标路径。 |
| `data.force` | bool | 是否覆盖。 |
| `data.virtual_disk` | bool | 使用 `copyVirtualDisk()` 时为 `true`。 |

#### 调用示例

```php
$client->storage()->copyFile(
    '[datastore1] template/Ubuntu18.vmdk',
    '[datastore1] vps-demo-001/Ubuntu18.vmdk',
    false,
    true
);
```

### 7.7 `storage()->copyVirtualDisk()`

#### 方法说明

显式使用 ESXi `CopyVirtualDisk_Task` 复制 VMDK。

#### 请求

```php
$client->storage()->copyVirtualDisk(
    string $sourceName,
    string $destinationName,
    bool $force = false,
    bool $wait = true,
    array $options = []
): array
```

#### 参数说明

同 `storage()->copyFile()`，但固定按虚拟磁盘方式复制。

#### 返回说明

Task 方法返回，见 [Task 等待规则](#25-task-等待规则)。`data.virtual_disk` 为 `true`。

#### 调用示例

```php
$client->storage()->copyVirtualDisk(
    '[datastore1] template/Ubuntu18.vmdk',
    '[datastore1] vps-demo-001/Ubuntu18.vmdk'
);
```

### 7.8 `storage()->makeDirectory()`

#### 方法说明

在 datastore 中创建目录。该方法标记为内部辅助，但当前可调用，模板创建 VPS 时会用到。

#### 请求

```php
$client->storage()->makeDirectory(
    string $name,
    bool $createParentDirectories = true,
    array $options = []
): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `name` | 是 | string | - | `[datastore1] vps-demo-001` | 要创建的 datastore 目录路径。 |
| `createParentDirectories` | 否 | bool | `true` | `true` / `false` | 父目录不存在时是否一起创建。 |
| `options.datacenter` | 否 | mixed | `null` | Datacenter MoRef 格式 | Datacenter，单台 ESXi 一般不传。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.path` | string | 创建的目录路径。 |
| `data.create_parent_directories` | bool | 是否创建父目录。 |

#### 调用示例

```php
$client->storage()->makeDirectory('[datastore1] vps-demo-001');
```

---

## 8. Task 任务服务

### 8.1 `task()->list()`

#### 方法说明

查询最近任务，内部调用 `task()->recent()`。

#### 请求

```php
$client->task()->list(int $limit = 50): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `limit` | 否 | int | `50` | 正整数 | 最多返回任务数量。 |

#### 返回说明

同 `task()->recent()`。

#### 调用示例

```php
$tasks = $client->task()->list(20);
```

### 8.2 `task()->recent()`

#### 方法说明

查询 ESXi TaskManager 的最近任务列表，并读取每个任务详情。

#### 请求

```php
$client->task()->recent(int $limit = 50): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `limit` | 否 | int | `50` | 正整数 | 最多返回任务数量。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data` | array | 任务详情数组。 |
| `data.*.info.key` | string | Task key。 |
| `data.*.info.name` | string | Task 名称。 |
| `data.*.info.entityName` | string/null | 关联对象名称。 |
| `data.*.info.state` | string | Task 状态。 |
| `data.*.info.progress` | int/null | 进度。 |
| `data.*.info.error` | array/null | 错误信息。 |
| `data.*.info.result` | mixed | Task 结果。 |

#### 调用示例

```php
$recent = $client->task()->recent(10);
```

### 8.3 `task()->info()`

#### 方法说明

查询指定任务详情。

#### 请求

```php
$client->task()->info(mixed $task): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `task` | 是 | mixed | - | 见 [Task 参数](#task-参数-task) | 要查询的 Task。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.info.key` | string | Task key。 |
| `data.info.name` | string | Task 名称。 |
| `data.info.descriptionId` | string | 描述 ID。 |
| `data.info.entity` | mixed | 关联对象引用。 |
| `data.info.entityName` | string/null | 关联对象名称。 |
| `data.info.state` | string | 状态。 |
| `data.info.cancelled` | bool | 是否取消。 |
| `data.info.cancelable` | bool | 是否可取消。 |
| `data.info.progress` | int/null | 进度。 |
| `data.info.queueTime` | string/null | 入队时间。 |
| `data.info.startTime` | string/null | 开始时间。 |
| `data.info.completeTime` | string/null | 完成时间。 |
| `data.info.error` | array/null | 错误信息。 |
| `data.info.result` | mixed | 结果。 |

#### 调用示例

```php
$info = $client->task()->info('haTask-xxx');
```

### 8.4 `task()->wait()`

#### 方法说明

等待指定 Task 完成。成功返回成功结构；失败抛出 `TaskFailedException`；超时抛出 `EsxiException`。

#### 请求

```php
$client->task()->wait(mixed $task, int $timeoutSeconds = 300, int $intervalMs = 1000): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `task` | 是 | mixed | - | 见 [Task 参数](#task-参数-task) | 要等待的 Task。 |
| `timeoutSeconds` | 否 | int | `300` | 正整数 | 最长等待秒数。 |
| `intervalMs` | 否 | int | `1000` | 正整数 | 轮询间隔，单位毫秒。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 成功为 `true`。 |
| `task.id` | string | Task ID。 |
| `task.state` | string | 成功时为 `success`。 |
| `data.info.state` | string | Task 状态。 |
| `data.info.result` | mixed | Task 结果。 |

#### 调用示例

```php
$created = $client->vps()->powerOn('Ubuntu18', false);
$done = $client->task()->wait($created['task'], 300, 1000);
```

### 8.5 `task()->rawInfo()`

#### 方法说明

查询指定 Task 原始属性，不包裹 `success/data`。

#### 请求

```php
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;

$client->task()->rawInfo(Mor $task, array $properties = []): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `task` | 是 | `Mor` | - | `new Mor('Task', 'haTask-xxx')` | Task 引用对象。 |
| `properties` | 否 | array | 默认 Task 字段 | Task 属性路径数组 | 指定返回字段。 |

#### 返回说明

返回 Task 原始属性数组，字段见 [常用 Task 属性路径](#常用-task-属性路径)。

#### 调用示例

```php
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;

$raw = $client->task()->rawInfo(new Mor('Task', 'haTask-xxx'), [
    'info.key',
    'info.state',
]);
```

---

## 9. Monitor 监控服务

### 9.1 `monitor()->vm()`

#### 方法说明

查询虚拟机监控原始字段，包括电源、CPU、内存、磁盘、IP、Tools 状态。

#### 请求

```php
$client->monitor()->vm(mixed $vm): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `vm` | 是 | mixed | - | 见 [VM 参数](#vm-参数-vm) | 要监控的 VM。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data.name` | string | VM 名称。 |
| `data.runtime.powerState` | string | 电源状态。 |
| `data.summary.quickStats.overallCpuUsage` | int/null | CPU 使用 MHz。 |
| `data.summary.quickStats.overallCpuDemand` | int/null | CPU 需求 MHz。 |
| `data.summary.quickStats.hostMemoryUsage` | int/null | 宿主机侧内存 MB。 |
| `data.summary.quickStats.guestMemoryUsage` | int/null | Guest 侧内存 MB。 |
| `data.summary.quickStats.uptimeSeconds` | int/null | 运行秒数。 |
| `data.summary.storage.committed` | int/null | 已提交磁盘字节。 |
| `data.summary.storage.uncommitted` | int/null | 未提交磁盘字节。 |
| `data.summary.guest.ipAddress` | string/null | Guest IP。 |
| `data.summary.guest.toolsStatus` | string/null | Tools 状态。 |

#### 调用示例

```php
$vmMonitor = $client->monitor()->vm('Ubuntu18');
```

### 9.2 `monitor()->host()`

#### 方法说明

查询宿主机监控数据，内部调用 `host()->performance()`。

#### 请求

```php
$client->monitor()->host(mixed $host = null): array
```

#### 参数说明

同 `host()->performance()`。

#### 返回说明

同 `host()->performance()`。

#### 调用示例

```php
$hostMonitor = $client->monitor()->host();
```

### 9.3 `monitor()->storage()`

#### 方法说明

查询存储监控数据，内部调用 `storage()->usage()`。

#### 请求

```php
$client->monitor()->storage(mixed $datastore = null): array
```

#### 参数说明

同 `storage()->usage()`。

#### 返回说明

同 `storage()->usage()`。

#### 调用示例

```php
$storageMonitor = $client->monitor()->storage('datastore1');
```

---

## 10. Log 日志服务

### 10.1 `logs()->descriptions()`

#### 方法说明

查询 ESXi 可读取的日志类型描述。

#### 请求

```php
$client->logs()->descriptions(mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data` | array | ESXi 返回的日志描述数组。 |
| `data.*.key` | string | 日志 key，例如 `hostd`。 |
| `data.*.label` | string | 日志显示名称。 |
| `data.*.summary` | string | 日志说明。 |

#### 调用示例

```php
$descriptions = $client->logs()->descriptions();
```

### 10.2 `logs()->browse()`

#### 方法说明

读取指定 ESXi 日志内容。

#### 请求

```php
$client->logs()->browse(string $key = 'hostd', int $start = 0, int $lines = 200, mixed $host = null): array
```

#### 参数说明

| 参数 | 是否必填 | 类型 | 默认值 | 可传值 / 格式 | 说明 |
|---|---:|---|---|---|---|
| `key` | 否 | string | `hostd` | `hostd`、`vmkernel` 等 | 日志 key，可从 `descriptions()` 获取。 |
| `start` | 否 | int | `0` | `0` 或正整数 | 起始行。 |
| `lines` | 否 | int | `200` | 正整数 | 读取行数。 |
| `host` | 否 | mixed | `null` | 见 [Host 参数](#host-参数-host) | 指定宿主机。 |

#### 返回说明

| 字段 | 类型 | 说明 |
|---|---|---|
| `success` | bool | 固定为 `true`。 |
| `data` | array | ESXi 返回的日志内容结构。 |
| `data.lineText` | array/string | 日志文本行。 |
| `data.start` | int | 返回内容起始行。 |
| `data.end` | int | 返回内容结束行。 |

#### 调用示例

```php
$logs = $client->logs()->browse('hostd', 0, 100);
```

---

## 11. Inventory 清单服务

### 11.1 `inventory()->virtualMachines()`

#### 方法说明

查询虚拟机清单，内部调用 `vps()->list()`。

#### 请求

```php
$client->inventory()->virtualMachines(array $properties = []): array
```

#### 参数说明

同 `vps()->list()`。

#### 返回说明

同 `vps()->list()`。

#### 调用示例

```php
$vms = $client->inventory()->virtualMachines(['name', 'runtime.powerState']);
```

### 11.2 `inventory()->hosts()`

#### 方法说明

查询宿主机清单，内部调用 `host()->list()`。

#### 请求

```php
$client->inventory()->hosts(array $properties = []): array
```

#### 参数说明

同 `host()->list()`。

#### 返回说明

同 `host()->list()`，返回原始数组，不包裹 `success/data`。

#### 调用示例

```php
$hosts = $client->inventory()->hosts(['name']);
```

### 11.3 `inventory()->datastores()`

#### 方法说明

查询 Datastore 清单，内部调用 `storage()->list()`。

#### 请求

```php
$client->inventory()->datastores(array $properties = []): array
```

#### 参数说明

同 `storage()->list()`。

#### 返回说明

同 `storage()->list()`。

#### 调用示例

```php
$datastores = $client->inventory()->datastores(['name', 'summary.freeSpace']);
```

---

## 12. 模板创建 VPS 调用链路

下面是“先创建目录，再复制模板 VMDK，再使用已有硬盘创建 VM”的完整调用顺序。

### 方法说明

适用于已有系统模板：

```text
[datastore1] template/Ubuntu18.vmdk
```

创建过程：

1. 确定 VM 名称。
2. 创建 VM 目录。
3. 复制模板 VMDK 到 VM 目录。
4. 使用复制后的 VMDK 作为已有硬盘创建 VM。
5. 按需开机。

### 请求

```php
$vmName = 'vps-demo-001';
$datastore = 'datastore1';
$network = 'VM Network';
$template = '[datastore1] template/Ubuntu18.vmdk';
$targetDisk = "[{$datastore}] {$vmName}/Ubuntu18.vmdk";

$client->storage()->makeDirectory("[{$datastore}] {$vmName}");

$client->storage()->copyFile(
    $template,
    $targetDisk,
    false,
    true
);

$client->vps()->create([
    'name' => $vmName,
    'datastore' => $datastore,
    'network' => $network,
    'num_cpus' => 1,
    'memory_mb' => 1024,
    'use_existing_disk' => true,
    'disk_path' => $targetDisk,
    'disk_gb' => 20,
    'guest_id' => 'ubuntu64Guest',
]);

$client->vps()->powerOn($vmName);
```

### 参数说明

| 参数 | 说明 |
|---|---|
| `$vmName` | VPS / VM 名称，也作为目录名。 |
| `$datastore` | 目标 Datastore 名称。 |
| `$network` | 要绑定的端口组名称。传相同端口组名称的 VPS 会连接到同一端口组。 |
| `$template` | 模板 VMDK datastore 路径。 |
| `$targetDisk` | 复制后的目标 VMDK datastore 路径。 |

### 返回说明

每一步返回对应方法的结果：

| 步骤 | 返回 |
|---|---|
| `makeDirectory()` | `success/data.path` |
| `copyFile()` | Task 返回，VMDK 会自动走 `CopyVirtualDisk_Task` |
| `create()` | Task 返回，`data.name` / `data.datastore` / `data.network` |
| `powerOn()` | Task 返回 |

### 调用示例

```php
$vmName = 'vps-' . date('YmdHis');

$client->storage()->makeDirectory("[datastore1] {$vmName}");
$client->storage()->copyVirtualDisk(
    '[datastore1] template/Ubuntu18.vmdk',
    "[datastore1] {$vmName}/Ubuntu18.vmdk"
);
$client->vps()->create([
    'name' => $vmName,
    'datastore' => 'datastore1',
    'network' => 'VPC-100',
    'num_cpus' => 1,
    'memory_mb' => 1024,
    'use_existing_disk' => true,
    'disk_path' => "[datastore1] {$vmName}/Ubuntu18.vmdk",
    'disk_gb' => 20,
]);
```
