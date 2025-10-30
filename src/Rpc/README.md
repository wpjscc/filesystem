# RPC 适配器

RPC 适配器是 React\Filesystem 的一个实现，它使用 TCP 协议通过 JSON-RPC 2.0 在远程服务器上执行文件系统操作。

## 原理

RPC 适配器使用 `reactphp-x/rpc` 库创建一个 TCP 客户端，连接到运行在远程服务器上的 RPC 服务器。所有文件系统操作都通过 JSON-RPC 2.0 协议发送到服务器执行。

## 优势

1. **远程操作**: 可以在远程服务器上执行文件系统操作
2. **非阻塞**: 所有文件操作都是异步的，不会阻塞主事件循环
3. **兼容性**: 无需安装 ext-eio 或 ext-uv 扩展
4. **简单**: 使用方式与其他适配器完全相同

## 使用方法

### 启动 RPC 服务器

首先需要启动 RPC 服务器：

```bash
php src/Rpc/rpc_server_init.php 127.0.0.1:8080 true
```

参数说明：
- `127.0.0.1:8080`: 服务器监听地址和端口
- `true`: 启用访问日志（可选，设置为 false 或不设置则不启用）

### 基本使用

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use React\Filesystem\Factory;
use React\EventLoop\Loop;

// 创建 RPC 适配器，连接到 RPC 服务器
$filesystem = Factory::createRpc('127.0.0.1:8080');

// 读取文件
$filesystem->file('/path/to/file.txt')->getContents()->then(function ($contents) {
    echo $contents;
});

Loop::run();
```

### 支持的操作

#### 文件操作 (File)

- `stat()` - 获取文件状态
- `getContents($offset, $maxlen)` - 读取文件内容
- `putContents($contents, $flags)` - 写入文件内容
- `unlink()` - 删除文件

#### 目录操作 (Directory)

- `stat()` - 获取目录状态
- `ls()` - 列出目录内容
- `unlink()` - 删除空目录

#### 不存在节点操作 (NotExist)

- `createDirectory()` - 创建目录
- `createFile()` - 创建文件
- `stat()` - 获取状态（返回 null）

## 实现细节

### 架构

```
Rpc/
├── Adapter.php           # 适配器主类，实现 AdapterInterface
├── File.php              # 文件节点实现
├── Directory.php         # 目录节点实现
├── NotExist.php          # 不存在节点实现
├── StatTrait.php         # Stat 操作的通用实现
├── Process.php           # RPC 客户端连接管理
├── Evaluator.php         # RPC 方法评估器（服务器端）
└── rpc_server_init.php   # RPC 服务器初始化脚本
```

### RPC 方法

服务器端实现的 RPC 方法：

- `stat($path)` - 获取文件/目录状态
- `file_get_contents($path, $offset, $maxlen)` - 读取文件内容
- `file_put_contents($path, $contents, $flags)` - 写入文件内容
- `unlink($path)` - 删除文件
- `scandir($path)` - 列出目录内容
- `mkdir($path)` - 创建目录
- `rmdir($path)` - 删除空目录

### 通信协议

使用 JSON-RPC 2.0 协议通过 TCP 连接进行通信。数据格式使用 NDJSON (Newline Delimited JSON)，每个请求和响应都在单独的行中。

## 注意事项

1. **服务器启动**: 使用 RPC 适配器前，必须先启动 RPC 服务器
2. **网络连接**: 确保客户端能够连接到 RPC 服务器
3. **错误处理**: 网络错误和服务器错误都会通过 Promise 的 reject 回调传递
4. **连接管理**: RPC 客户端会自动管理连接，使用持久连接提高性能

## 与 ChildProcess 适配器的区别

- **ChildProcess**: 在本地子进程中执行操作，使用进程间通信
- **RPC**: 在远程服务器上执行操作，使用网络通信

两者都提供了非阻塞的文件系统操作，但 RPC 适配器更适合分布式场景。

