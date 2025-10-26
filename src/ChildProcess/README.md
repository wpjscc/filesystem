# ChildProcess 适配器

ChildProcess 适配器是 React\Filesystem 的一个实现，它将阻塞的文件系统操作下放到子进程中执行，从而避免阻塞主事件循环。

## 原理

与 Fallback 适配器直接在主进程中执行阻塞操作不同，ChildProcess 适配器使用 `react/child-process` 和 `reactphp-x/tunnel-stream` 创建一个持久化的子进程，并通过该子进程执行所有阻塞的文件系统操作。

## 优势

1. **非阻塞**: 所有文件操作都在子进程中执行，不会阻塞主事件循环
2. **兼容性**: 无需安装 ext-eio 或 ext-uv 扩展
3. **高性能**: 相比同步阻塞操作，更适合在 ReactPHP 异步环境中使用
4. **简单**: 使用方式与其他适配器完全相同

## 使用方法

### 基本使用

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use React\Filesystem\Factory;
use React\EventLoop\Loop;

// 创建 ChildProcess 适配器
$filesystem = Factory::createChildProcess();

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
ChildProcess/
├── Adapter.php       # 适配器主类，实现 AdapterInterface
├── File.php          # 文件节点实现
├── Directory.php     # 目录节点实现
├── NotExist.php      # 不存在节点实现
├── StatTrait.php     # Stat 操作的通用实现
├── Process.php       # 子进程管理和调用封装
└── child_process_init.php  # 子进程初始化脚本
```

### Process 类

`Process` 类是核心组件，它管理子进程的生命周期并提供 `call()` 方法来执行闭包：

```php
Process::call(function () {
    // 这里的代码会在子进程中执行
    return file_get_contents('/path/to/file');
});
```

### 与 Fallback 的对比

| 特性 | Fallback | ChildProcess |
|------|----------|--------------|
| 是否阻塞主循环 | 是 | 否 |
| 需要扩展 | 否 | 否 |
| 性能 | 低（阻塞） | 高（异步） |
| 资源消耗 | 低 | 中（需要子进程） |
| 适用场景 | 简单脚本 | 生产环境 |

## 注意事项

1. **子进程开销**: 第一次调用时会创建子进程，有一定的初始化开销
2. **序列化限制**: 传递给子进程的数据和返回的数据需要可序列化
3. **资源管理**: 确保正确关闭事件循环以清理子进程

## 性能建议

- 对于大量小文件操作，ChildProcess 适配器比 Fallback 更优
- 对于单次大文件操作，两者性能相近
- 在高并发场景下，ChildProcess 能显著提升整体性能

