<?php

/**
 * RPC 文件系统客户端示例
 * 
 * 使用方法:
 *   php examples/rpc_example.php [address]
 * 
 * 参数:
 *   address: RPC 服务器地址，格式为 host:port，默认为 127.0.0.1:8080
 * 
 * 示例:
 *   php examples/rpc_example.php 127.0.0.1:8080
 *   php examples/rpc_example.php localhost:9000
 */

require __DIR__ . '/../vendor/autoload.php';

use React\Filesystem\Factory;
use React\EventLoop\Loop;

// 获取命令行参数
$address = $argv[1] ?? '127.0.0.1:8080';

// 验证地址格式
if (!str_contains($address, ':')) {
    echo "错误: 地址格式不正确，应为 host:port (例如: 127.0.0.1:8080)\n";
    exit(1);
}

echo "连接到 RPC 服务器: {$address}\n";
echo "注意: 请确保 RPC 服务器已启动 (php examples/rpc_server.php {$address})\n\n";

// 创建 RPC 适配器，连接到 RPC 服务器
$filesystem = Factory::createRpc($address);

// 读取文件内容
$filesystem->file(__FILE__)->getContents()->then(function ($contents) {
    echo "文件内容长度: " . strlen($contents) . " 字节\n";
    echo "前100个字符: " . substr($contents, 0, 100) . "...\n";
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

// 列出目录
$filesystem->directory(__DIR__)->ls()->then(function ($nodes) {
    echo "\n目录列表:\n";
    foreach ($nodes as $node) {
        echo "- " . $node->name() . " (" . get_class($node) . ")\n";
    }
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

// 创建新文件
$filesystem->file(__DIR__ . '/test_rpc.txt')->putContents('Hello from RPC!')->then(function ($bytesWritten) {
    echo "\n写入了 $bytesWritten 字节\n";
    
    // 读取刚创建的文件
    global $filesystem;
    return $filesystem->file(__DIR__ . '/test_rpc.txt')->getContents();
})->then(function ($contents) {
    echo "读取内容: $contents\n";
    
    // 删除测试文件
    global $filesystem;
    return $filesystem->file(__DIR__ . '/test_rpc.txt')->unlink();
})->then(function ($result) {
    echo "文件已删除: " . ($result ? 'true' : 'false') . "\n";
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

Loop::run();

