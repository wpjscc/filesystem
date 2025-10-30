<?php

/**
 * RPC 文件系统子进程模式内存监控示例
 * 
 * 此示例演示如何使用 RPC 适配器的子进程模式进行文件操作，并监控内存使用情况
 * 
 * 使用方法:
 *   php examples/rpc_childprocess_memory_monitor.php
 */

require __DIR__ . '/../vendor/autoload.php';

use React\Filesystem\Factory;
use function React\Async\async;
use function React\Async\delay;
use React\EventLoop\Loop;

echo "========================================\n";
echo "RPC 文件系统子进程模式内存监控示例\n";
echo "========================================\n";
echo "此示例使用子进程模式，无需外部 RPC 服务器\n";
echo "所有操作在子进程中执行，通过进程间通信\n";
echo "========================================\n\n";

// 创建 RPC 适配器，使用子进程模式
$filesystem = Factory::createRpc('127.0.0.1:8080', true);

// 添加内存监控定时器
Loop::addPeriodicTimer(1, function () {
    echo 'current_memory_usage: ' . round(memory_get_usage() / 1024 / 1024, 3) . " MB\n";
});

function getMemoryUsage(): array
{
    return [
        'date' => date('Y-m-d H:i:s'),
        'current_usage_mb' => round(memory_get_usage() / 1024 / 1024, 3),
        'current_usage_real_mb' => round(memory_get_usage(true) / 1024 / 1024, 3),
        'peak_usage_mb' => round(memory_get_peak_usage() / 1024 / 1024, 3),
        'peak_usage_real_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
    ];
}

$file = $filesystem->file(__DIR__ . '/test_rpc_childprocess_memory.txt');
async(function () use ($file) {
    $startMemoryUsage = getMemoryUsage();
    echo "开始内存监控...\n";
    echo "初始内存使用: " . json_encode($startMemoryUsage, JSON_PRETTY_PRINT) . "\n\n";
    
    while (true) {
        delay(0.001);
        $file->putContents(
            json_encode([
                'start_memory_usage' => $startMemoryUsage,
                'current_memory_usage' => getMemoryUsage(),
                'memory_limit' => ini_get('memory_limit'),
                'timestamp' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT),
        )->then(function () {
            // echo 'file written' . "\n";
        })->catch(function ($error) {
            echo "写入错误: " . $error->getMessage() . "\n";
        });
    }
})();

Loop::run();

