# WebMKS Console Demo

这个目录用于演示 ESXi WebMKS 控制台连接。

## 内容

- `vendor/webmks/`：WebMKS 前端库、CSS、图片资源；`wmks.min.js` 使用 ESXi 6.7 Host Client 同版本构建。
- `vendor/jquery/`：ESXi 6.7 Host Client 同版本 jQuery 与 jQuery UI。
- `api/console-ticket.php`：调用本 Composer 包获取 ESXi `webmks` Ticket。
- `demo/index.html`：浏览器控制台 Demo。

## 启动示例

```powershell
$env:ESXI_HOST='your-esxi-host'
$env:ESXI_USER='your-username'
$env:ESXI_PASSWORD='your-password'
php -S 127.0.0.1:8787 -t web
```

打开：

```text
http://127.0.0.1:8787/demo/?vm=test
```

如果浏览器拒绝 `wss://ESXi/ticket/...`，请先在浏览器访问 ESXi HTTPS 地址并信任证书，或在生产环境改成自己的 WebSocket 网关代理。
