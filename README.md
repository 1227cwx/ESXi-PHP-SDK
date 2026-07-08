# 🚀 1227cwx/esxi-php-sdk

> 面向 PHP 应用的 VMware ESXi SDK，用于直接管理 **单台 ESXi 主机**。当前已实现 **ESXi 6.7** 的 vim25 SOAP 基础管理能力。

![Packagist Version](https://img.shields.io/packagist/v/1227cwx/esxi-php-sdk?style=flat-square&label=packagist) ![PHP](https://img.shields.io/badge/php-%3E%3D8.2-777BB4?style=flat-square&logo=php&logoColor=white) ![ESXi](https://img.shields.io/badge/ESXi-6.7-607078?style=flat-square&logo=vmware&logoColor=white) ![hyperf/guzzle](https://img.shields.io/badge/hyperf%2Fguzzle-%5E3.2-44cc11?style=flat-square) ![ext-dom](https://img.shields.io/badge/ext--dom-required-blue?style=flat-square) ![ext-libxml](https://img.shields.io/badge/ext--libxml-required-blue?style=flat-square) ![ext-xmlwriter](https://img.shields.io/badge/ext--xmlwriter-required-blue?style=flat-square) ![License](https://img.shields.io/badge/license-MIT-blue?style=flat-square)

---

## 📚 目录

<p align="center">
  <a href="#-项目介绍">✨ 项目介绍</a> ·
  <a href="#-环境要求">🧩 环境要求</a> ·
  <a href="#-安装方式">📦 安装方式</a> ·
  <a href="#-文档入口">📖 文档入口</a> ·
  <a href="#-license">📄 License</a>
</p>

---

## ✨ 项目介绍

`1227cwx/esxi-php-sdk` 面向 ESXi 6.7 单机节点管理场景，封装虚拟机、网络、存储、任务、日志与监控等常用能力，提供统一的 PHP 调用方式。

适用框架：

| 框架 / 环境 | 说明 |
|---|---|
| webman | 可在 webman 项目中安装调用。 |
| ThinkPHP | 可在 TP 项目中安装调用。 |
| Hyperf | 使用 `hyperf/guzzle`，兼容 Swoole 协程环境。 |
| Laravel | 可作为普通 Composer 包安装调用。 |
| PHP CLI / PHP-FPM | 非协程环境下自动走普通 Guzzle 请求。 |

### 依赖说明

| 依赖 | 版本 | 作用 |
|---|---:|---|
| `hyperf/guzzle` | `^3.2` | HTTPS 请求客户端，自动适配 Swoole 协程和非协程环境。 |
| `ext-dom` | PHP 扩展 | 解析 SOAP XML 响应。 |
| `ext-libxml` | PHP 扩展 | 提供 XML 底层解析能力。 |
| `ext-xmlwriter` | PHP 扩展 | 构造 SOAP XML 请求。 |

> [!NOTE]
> 当前版本基于单机 ESXi 6.7 的 `/sdk` vim25 SOAP API，不依赖 vCenter。

---

## 🧩 环境要求

### 运行环境

| 项目 | 要求 |
|---|---|
| PHP | `>= 8.2` |
| ESXi | 当前支持 `6.7` |
| Composer | 建议 Composer 2.x |
| HTTPS Client | `hyperf/guzzle ^3.2` |
| PHP 扩展 | `ext-dom`、`ext-libxml`、`ext-xmlwriter` |

> [!IMPORTANT]
> 运行环境不能低于 **PHP 8.2**。本包不依赖 `ext-soap`，也不直接依赖 `ext-curl`。

> [!TIP]
> 在 Hyperf、webman、Swoole 协程环境下，`hyperf/guzzle` 会自动使用协程请求；普通 PHP CLI 或 PHP-FPM 环境下会自动走普通 Guzzle。

---

## 📦 安装方式

通过 Packagist 安装：

```bash
composer require 1227cwx/esxi-php-sdk
```

---

## 📖 文档入口

| ESXi 版本 | 调用文档 | PHP 命名空间 | 状态 |
|---|---|---|---|
| 6.7 | [docs/6-7/README.md](docs/6-7/README.md) | `Cwx1227\Esxi\Version\V67` | 已支持基础功能 |

> [!IMPORTANT]
> 调用 ESXi 6.7 时，客户端版本参数使用 `67`。

---

## 📄 License

MIT
