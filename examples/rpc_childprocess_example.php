<?php

/**
 * RPC 文件系统子进程模式示例
 * 
 * 此示例演示如何使用 RPC 适配器的子进程模式
 * 子进程模式通过进程间通信（stdin/stdout）进行 RPC 调用，无需外部服务器
 * 
 * 使用方法:
 *   php examples/rpc_childprocess_example.php
 */

require __DIR__ . '/../vendor/autoload.php';

use React\Filesystem\Factory;
use React\EventLoop\Loop;

echo "========================================\n";
echo "RPC 文件系统子进程模式示例\n";
echo "========================================\n";
echo "此示例使用子进程模式，无需外部 RPC 服务器\n";
echo "所有操作在子进程中执行，通过进程间通信\n";
echo "========================================\n\n";

// 创建 RPC 适配器，使用子进程模式
// 参数说明：
//   第一个参数: 地址（子进程模式下不使用，但需要提供）
//   第二个参数: true = 使用子进程模式
$filesystem = Factory::createRpc('127.0.0.1:8080', true);

echo "RPC 适配器已创建（子进程模式）\n";
echo "开始测试文件操作...\n\n";

// 测试1: 读取当前文件
$filesystem->file(__FILE__)->getContents()->then(function ($contents) {
    echo "✓ 测试1 - 读取文件成功\n";
    echo "  文件大小: " . strlen($contents) . " 字节\n";
    echo "  前50个字符: " . substr($contents, 0, 50) . "...\n\n";
})->catch(function ($error) {
    echo "✗ 测试1 - 读取文件失败: " . $error->getMessage() . "\n\n";
});

// 测试2: 列出目录
$filesystem->directory(__DIR__)->ls()->then(function ($nodes) {
    echo "✓ 测试2 - 列出目录成功\n";
    echo "  找到 " . count($nodes) . " 个项目\n";
    $fileCount = 0;
    $dirCount = 0;
    foreach ($nodes as $node) {
        if (str_ends_with(get_class($node), 'File')) {
            $fileCount++;
        } elseif (str_ends_with(get_class($node), 'Directory')) {
            $dirCount++;
        }
    }
    echo "  文件: {$fileCount}, 目录: {$dirCount}\n\n";
})->catch(function ($error) {
    echo "✗ 测试2 - 列出目录失败: " . $error->getMessage() . "\n\n";
});

// 测试3: 创建新文件
$testFile = __DIR__ . '/test_rpc_childprocess.txt';
$filesystem->file($testFile)->putContents('Hello from RPC ChildProcess! ' . date('Y-m-d H:i:s'))->then(function ($bytesWritten) {
    echo "✓ 测试3 - 创建文件成功\n";
    echo "  写入字节数: {$bytesWritten}\n\n";
    
    // 测试4: 读取刚创建的文件
    global $filesystem, $testFile;
    return $filesystem->file($testFile)->getContents();
})->then(function ($contents) {
    echo "✓ 测试4 - 读取新文件成功\n";
    echo "  内容: {$contents}\n\n";
    
    // 测试5: 删除测试文件
    global $filesystem, $testFile;
    return $filesystem->file($testFile)->unlink();
})->then(function ($result) {
    echo "✓ 测试5 - 删除文件成功\n";
    echo "  结果: " . ($result ? 'true' : 'false') . "\n\n";
    
    echo "========================================\n";
    echo "所有测试完成！\n";
    echo "========================================\n";
    echo "模式: 子进程模式（ChildProcess）\n";
    echo "特点: 无需外部服务器，进程间通信\n";
    echo "========================================\n";
    
    Loop::stop();
})->catch(function ($error) {
    echo "✗ 测试失败: " . $error->getMessage() . "\n";
    Loop::stop();
});

Loop::run();

