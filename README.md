# 🚀 1227cwx/esxi-php-sdk

> 面向 PHP 应用的 VMware ESXi SDK。当前已实现 **ESXi 6.7 单机节点** 的 vim25 SOAP 基础管理能力。

![Packagist Version](https://img.shields.io/packagist/v/1227cwx/esxi-php-sdk?style=flat-square&label=packagist)
![PHP](https://img.shields.io/badge/php-%3E%3D8.2-777BB4?style=flat-square&logo=php&logoColor=white)
![ESXi](https://img.shields.io/badge/ESXi-6.7-607078?style=flat-square&logo=vmware&logoColor=white)
![hyperf/guzzle](https://img.shields.io/badge/hyperf%2Fguzzle-%5E3.2-44cc11?style=flat-square)
![ext-dom](https://img.shields.io/badge/ext--dom-required-blue?style=flat-square)
![ext-libxml](https://img.shields.io/badge/ext--libxml-required-blue?style=flat-square)
![ext-xmlwriter](https://img.shields.io/badge/ext--xmlwriter-required-blue?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-blue?style=flat-square)

## ✨ 项目介绍

`1227cwx/esxi-php-sdk` 是一个独立 Composer 包，用于在 PHP 应用中直接管理单台 ESXi 主机。

适用场景：

- webman
- ThinkPHP
- Hyperf
- Laravel
- 普通 PHP CLI
- 其他 Composer 项目

> [!NOTE]
> 当前版本基于单机 ESXi 6.7 的 `/sdk` vim25 SOAP API，不依赖 vCenter。

## 🧩 运行环境

| 项目 | 要求 |
|---|---|
| PHP | `>= 8.2` |
| ESXi | 当前支持 `6.7` |
| Composer | 建议 Composer 2.x |
| HTTPS Client | `hyperf/guzzle ^3.2` |
| PHP 扩展 | `ext-dom`、`ext-libxml`、`ext-xmlwriter` |

> [!IMPORTANT]
> 运行环境不能低于 **PHP 8.2**。本包不依赖 `ext-soap`，也不直接依赖 `ext-curl`。

## 📦 使用的依赖

| 依赖 | 版本 | 作用 |
|---|---:|---|
| `hyperf/guzzle` | `^3.2` | HTTPS 请求客户端，自动适配 Swoole 协程和非协程环境 |
| `ext-dom` | PHP 扩展 | 解析 SOAP XML 响应 |
| `ext-libxml` | PHP 扩展 | XML 底层解析支持 |
| `ext-xmlwriter` | PHP 扩展 | 构造 SOAP XML 请求 |

> [!TIP]
> 在 Hyperf、webman、Swoole 协程环境下，`hyperf/guzzle` 会自动使用协程请求；普通 PHP CLI 或 PHP-FPM 环境下会自动走普通 Guzzle。

## 📥 安装

发布到 Packagist 后，直接安装：

```bash
composer require 1227cwx/esxi-php-sdk
```

本地开发调试时，可以临时使用 path repository：

```bash
composer config repositories.esxi-php-sdk path E:/webman-vps/esxi-php-sdk
composer require 1227cwx/esxi-php-sdk:*
```

## ⚡ 快速使用

当前版本调用文档：

- 👉 [ESXi 6.7 调用文档](docs/6-7/README.md)

## 🧱 架构设计

```text
用户代码
  -> Version Client
  -> Service 层
  -> Operation 层
  -> SoapExecutor / Transport
  -> ESXi /sdk
```

- **Service 层**：对外业务 API，负责参数校验、业务编排、Task 等待和统一返回。
- **Operation 层**：ESXi 原始 SOAP 接口的一对一封装，按需增加。
- **Transport 层**：统一使用 `hyperf/guzzle` 发送 HTTPS 请求。

> [!IMPORTANT]
> 用户业务代码应优先调用 Service 层，例如 `$client->vps()`、`$client->network()`、`$client->host()`、`$client->monitor()`，不要直接拼 SOAP XML。

## 🌐 版本规划

| ESXi 版本 | 调用文档 | PHP 命名空间 | 状态 |
|---|---|---|---|
| 6.7 | [docs/6-7](docs/6-7/README.md) | `Version\V67` | 已支持基础功能 |
| 8.0 | `docs/8-0` | `Version\V80` | 规划中 |

> [!TIP]
> 对外版本参数使用 `67`、`80` 这种短版本号；PHP 命名空间使用 `V67`、`V80`，避免使用不合法的 `6-7` 作为 namespace。

## 📄 License

MIT
