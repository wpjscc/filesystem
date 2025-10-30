<?php

/**
 * RPC 文件系统内存监控示例
 * 
 * 此示例演示如何使用 RPC 适配器进行文件操作，并监控内存使用情况
 * 
 * 使用方法:
 *   php examples/rpc_memory_monitor.php [address]
 * 
 * 参数:
 *   address: RPC 服务器地址，格式为 host:port，默认为 127.0.0.1:8080
 * 
 * 示例:
 *   php examples/rpc_memory_monitor.php 127.0.0.1:8080
 *   php examples/rpc_memory_monitor.php localhost:9000
 */

require __DIR__ . '/../vendor/autoload.php';

use React\Filesystem\Factory;
use function React\Async\async;
use function React\Async\delay;
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

// 创建 RPC 适配器
$filesystem = Factory::createRpc($address);

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

$file = $filesystem->file(__DIR__ . '/test_rpc_memory.txt');
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
        )->then(function ($bytesWritten) {
            // echo 'file written ' . $bytesWritten . ' bytes' . "\n";
        },function ($error) {
            echo "写入错误: " . $error->getMessage() . "\n";
        });
    }
})();

Loop::run();

